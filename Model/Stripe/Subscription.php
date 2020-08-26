<?php

namespace TechNimbus\Stripe\Model\Stripe;

use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;

class Subscription {

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \TechNimbus\Stripe\Model\Configs
     */
    protected $configs;

    /**
     * @var \TechNimbus\Stripe\Model\Stripe\Plan
     */
    protected $stripePlan;

    /**
     * @var \TechNimbus\Stripe\Model\Stripe\Customer
     */
    protected $stripeCustomer;

    /**
     * Constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \echNimbus\Stripe\Model\StripePlan              $stripePlan
     * @param \echNimbus\Stripe\Model\Configs                 $configs
     * @param \echNimbus\Stripe\Model\Stripe\Customer         $stripeCustomer
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \TechNimbus\Stripe\Model\Stripe\Plan $stripePlan,
        \TechNimbus\Stripe\Model\Configs $configs,
        \TechNimbus\Stripe\Model\Stripe\Customer $stripeCustomer
    ) {
        $this->productRepository    = $productRepository;
        $this->stripePlan           = $stripePlan;
        $this->configs              = $configs;
        $this->stripeCustomer       = $stripeCustomer;
    }

    /**
     * Create subscription and plans complete
     * @param  Quote  $quote
     * @param  string $paymentMethodId
     * @return string
     * @throws LocalizedException
     */
    public function complete(\Magento\Quote\Model\Quote $quote, $paymentMethodId)
    {
        $magentoCustomer = $this->stripeCustomer->getMagentoCustomer($quote);

        if (!$magentoCustomer) {
            throw new LocalizedException(__('You must be logged in to purchase subscription products.'));
        }

        $stripeCustomer = $this->stripeCustomer->getStripeCustomer($magentoCustomer, $paymentMethodId);

        $rollback = false;
        $subscriptions = [];
        $plans = [];
        $directAmount = 0;
        $quoteItems = $quote->getAllVisibleItems();

        /* 1 - Create Plans and Subscriptions */
        foreach ($quoteItems as $quoteItem) {
            $product = $this->productRepository->get($quoteItem->getSku());
            if ($product->getIsStripeSubscription() == \TechNimbus\Stripe\Model\Subscription\Source\Yesno::YES) {
                $planId = $this->stripePlan->createPlan($quoteItem, $magentoCustomer);
                if ($planId) {
                    $plans[] = $planId;
                    $subscriptionId = $this->createSubscription($quote, $planId, $stripeCustomer->id);
                    if ($subscriptionId) {
                        $subscriptions[] = $subscriptionId;
                    } else {
                        $rollback = true;
                        break;
                    }
                } else {
                    $rollback = true;
                    break;
                }
            } else {
                $directAmount = ($quoteItem->getPrice() * $quoteItem->getQty()) - $quoteItem->getDiscountAmount();
            }
        }

        /* 2 - Rollback plans if fail */
        if ($rollback) {
            foreach ($subscriptions as $subscription) {
                $this->cancelSubscription($subscription);
            }

            foreach ($plans as $plan) {
                $this->stripePlan->deletePlan($plan);
            }

            throw new LocalizedException(__('Failed to create subscription.'));
        }

        /* 3 - Charge amount of non subscription products */
        $directChargeCaptured = false;
        $paymentIntent = false;

        if ($directAmount > 0) {
            try {
                if ($stripeCustomer) {
                    $paymentIntent = \Stripe\PaymentIntent::create([
                        'amount'            => round($directAmount * 100),
                        'currency'          => strtolower($quote->getQuoteCurrencyCode()),
                        'customer'          => $stripeCustomer->id,
                        'payment_method'    => $paymentMethodId,
                        'off_session'       => true,
                        'confirm'           => true
                    ]);

                    if ($paymentIntent) {
                        if ($paymentIntent->status != "succeeded") {
                            $paymentIntent = $paymentIntent->confirm();
                            if ($paymentIntent->status != "succeeded") {
                                $directChargeCaptured = false;
                            } else {
                                $directChargeCaptured = true;
                            }
                        } else {
                            $directChargeCaptured = true;
                        }
                    }
                }
            } catch (\Exception $e) {
                $directChargeCaptured = false;
            }
        } else {
            $directChargeCaptured = true;
        }

        if (!$directChargeCaptured) {
            /* 4 - Rollback plans if fail */
            foreach ($subscriptions as $subscription) {
                $this->cancelSubscription($subscription);
            }

            foreach ($plans as $plan) {
                $this->stripePlan->deletePlan($plan);
            }

            try {
                if ($paymentIntent) {
                    $paymentIntent->cancel();
                }
            } catch (\Exception $e) {}

            throw new LocalizedException(__('Failed to capture remaining amount after completed subscriptions.'));
        } else {
            $lastSubscriptionId = end($subscriptions);
            return $lastSubscriptionId;
        }
    }

    /**
     * Check if the quote is subscription payment.
     * @param Quote $quote the quote
     * @return boolean if is a subscription or not.
     */
    public function isSubscription(Quote $quote)
    {
        $isSubscription = false;
        $quoteItems = $quote->getAllVisibleItems();

        foreach ($quoteItems as $quoteItem) {
            $product = $this->productRepository->get($quoteItem->getSku());

            if ($product->getIsStripeSubscription() == \TechNimbus\Stripe\Model\Subscription\Source\Yesno::YES) {
                $isSubscription = true;
                break;
            }
        }

        return $isSubscription;
    }

    /**
     * Create The Subscription
     * @param string $quote
     * @param string $planId the plan ID create by stripe Plan object
     * @param string $stripeCustomerId the customer to be charged
     * @return string|boolean the subscription id or false if fails
     */
    private function createSubscription(Quote $quote, $planId, $stripeCustomerId)
    {
        try {
            if ($stripeCustomerId) {
                $subscription = \Stripe\Subscription::create([
                    'customer' => $stripeCustomerId,
                    'items' => [
                        ['plan' => $planId, 'quantity' => 1]
                    ]
                ]);

                return $subscription->id;
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
            return false;
        }

        return false;
    }

    /**
     * Try to cancel subscription
     * @param  string $subscriptionId
     * @return boolean
     */
    private function cancelSubscription($subscriptionId)
    {
        try {
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            $subscription->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
