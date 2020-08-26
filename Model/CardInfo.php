<?php

namespace TechNimbus\Stripe\Model;

class CardInfo {

    /**
     * @var \TechNimbus\Stripe\Model\Configs
     */
    protected $configs;

    /**
     * Constructor
     * @param \TechNimbus\Stripe\Model\Configs $configs
     */
    public function __construct(\TechNimbus\Stripe\Model\Configs $configs)
    {
        $this->configs = $configs;
    }

    /**
     * Get stripe credit card payment infromation
     * It will return an object with the following attributes: brand, number, expiry, avsCheck, cvcCheck
     * @param  string $paymentIntentId
     * @return object
     */
    public function get($paymentIntentId)
    {
        $cardInfo = new \stdClass;
        $cardInfo->brand = '---';
        $cardInfo->number = '---';
        $cardInfo->expiry = '---';
        $cardInfo->avsCheck = false;
        $cardInfo->cvcCheck = false;

        \Stripe\Stripe::setApiKey($this->configs->getApiKey());

        $paymentIntent = false;

        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            if ($paymentIntent->status != "succeeded") {
                $paymentIntent = false;
            }
        } catch (\Exception $e) {
            $paymentIntent = false;
        }

        if ($paymentIntent) {
            $details = $this->getPaymentDetails($paymentIntent);
            $cardInfo->brand = $this->getSafeValue($details, 'brand', '---');
            $cardInfo->number = $this->getSafeValue($details, 'last4', '---');

            $expMonth = (int) $this->getSafeValue($details, 'exp_month', 0);
            $expYear  = $this->getSafeValue($details, 'exp_year', '---');
            $expMonth = $expMonth < 10 ? "0" . $expMonth : $expMonth;
            $cardInfo->expiry = $expMonth . "/" . $expYear;

            $checks = $this->getSafeValue($details, 'checks');
            if ($checks) {
                $cardInfo->avsCheck = $this->getSafeValue($checks, 'address_postal_code_check', false) == "pass" ? true : false;
                $cardInfo->cvcCheck = $this->getSafeValue($checks, 'cvc_check', false) == "pass" ? true : false;
            }
        }

        return $cardInfo;
    }

    /**
     * Check if the payment intent is a card one and retrieve the information
     * @param  string $paymentIntent
     * @return object|boolean
     */
    private function getPaymentDetails($paymentIntent)
    {
        $paymentDetails = false;

        try {
            $paymentDetails = $paymentIntent->charges->data[0]->payment_method_details->card;
        } catch (\Exception $e) {
            $paymentDetails = false;
        }

        return $paymentDetails;
    }

    /**
     * Get value from an object if the attribute exists
     * @param  object $object
     * @param  string $attribute
     * @param  mixed $defaultValue
     * @return mixed
     */
    private function getSafeValue($object, $attribute, $defaultValue = null)
    {
        try {
            return $object->{$attribute};
        } catch (\Exception $e) {
            return $defaultValue;
        }

        return $defaultValue;
    }
}
