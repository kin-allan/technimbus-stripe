<?php

namespace TechNimbus\Stripe\Model\Stripe;

use TechNimbus\Stripe\Model\Client;
use TechNimbus\Stripe\Model\Stripe\Charge;
use TechNimbus\Stripe\Model\Stripe\Subscription;
use TechNimbus\Stripe\Model\Stripe\Customer as StripeCustomer;
use TechNimbus\Stripe\Model\Configs as StripeConfigs;
use Magento\Framework\Exception\LocalizedException;

class Capture {

    /**
     * @var Charge
     */
    protected $charge;

    /**
     * @var Subscription
     */
    protected $subscription;

    /**
     * @var StripeCustomer
     */
    protected $stripeCustomer;

    /**
     * Constructor.
     * @param Charge         $charge
     * @param Subscription   $subscription
     * @param StripeCustomer $stripeCustomer
     */
    public function __construct(
        Client $client,
        Charge $charge,
        Subscription $subscription,
        StripeCustomer $stripeCustomer
    ) {
        $client->init();
        $this->charge           = $charge;
        $this->subscription     = $subscription;
        $this->stripeCustomer   = $stripeCustomer;
    }

    /**
     * Process the stripe transaction
     * @param  \Magento\Quote\Model\Quote $quote
     * @param  string                           $paymentMethodId
     * @return \Stripe\PaymentIntent|boolean
     * @throws LocalizedException
     */
    public function capture(\Magento\Quote\Model\Quote $quote, $paymentMethodId)
    {
        $paymentIntentId = false;

        if ($this->subscription->isSubscription($quote)) {
            $paymentIntentId = $this->subscription->complete($quote, $paymentMethodId);
        } else {
            $stripeCustomer = $this->stripeCustomer->get($quote, $paymentMethodId);

            try {
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount'            => round((float) $quote->getGrandTotal() * 100),
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
                            $paymentIntentId = false;
                        }
                    }
                    $paymentIntentId = $paymentIntent->id;
                }
            } catch (\Exception $e) {
                $paymentIntentId = false;
            }

            if (!$paymentIntentId && $paymentIntent) {
                try {
                    $paymentIntent->cancel();
                } catch (\Exception $e) {}
            }
        }

        if (!$paymentIntentId) {
            throw new LocalizedException(__('Failed to capture payment'));
        }

        return $paymentIntentId;
    }
}
