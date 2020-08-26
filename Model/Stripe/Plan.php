<?php

namespace TechNimbus\Stripe\Model\Stripe;

use Magento\Quote\Model\Quote\Item;
use Magento\Customer\Model\Customer;

class Plan {

    protected $productRepository;
    protected $configs;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \TechNimbus\Stripe\Model\Configs $configs
    ) {
        $this->productRepository    = $productRepository;
        $this->configs              = $configs;
    }

    /**
     * Create a Plan based on Quote Item
     * @param Item $quoteItem
     * @param Customer $customer
     * @return string|false the plan id or false if fails
     */
    public function createPlan(Item $quoteItem, Customer $customer)
    {
        if (!$customer) {
            return false;
        }

        $storeName = $this->configs->getStoreName();

        $product    = $this->productRepository->getById($quoteItem->getProduct()->getId());
        $amount     = (int) (($quoteItem->getPrice() * $quoteItem->getQty()) - $quoteItem->getDiscountAmount()) * 100;
        $email      = $customer->getEmail();
        $planName   = 'Plan ' . $product->getName() . '. Email: ' . $email . '. Date: ' . date("Y-m-d h:i:s");
        $planId     = $storeName . ' - ' . $customer->getId() . '- (' . $quoteItem->getQty() . '.x)' . $product->getSku() . '-' . date("Ymdhis");

        try {
            $plan = \Stripe\Plan::create([
                'id'                => $planId,
                'name'              => $planName,
                'interval'          => $product->getStripeIntervalType(),
                'interval_count'    => (int) $product->getStripeIntervalCount(),
                'currency'          => $quoteItem->getQuote()->getQuoteCurrencyCode(),
                'amount'            => $amount
            ]);

            if ($plan) {
                return $plan->id;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Delete a plan
     * @param string $planId the plan id provided by stripe
     * @return boolean the plan delete result
     */
    public function deletePlan($planId)
    {
        try {
            if (\Stripe\Plan::delete($planId)) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
