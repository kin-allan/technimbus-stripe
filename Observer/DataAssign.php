<?php

namespace TechNimbus\Stripe\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssign extends AbstractDataAssignObserver {

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        $paymentModel = $this->readPaymentModelArgument($observer);

        $paymentModel->setAdditionalInformation(
            \TechNimbus\Stripe\Model\Stripe::REQUEST_DATA_PARAM, [
            'stripe_token'  => $this->assignIfExist($additionalData, 'stripe_token'),
            'quote_id'      => $this->assignIfExist($additionalData, 'quote_id')
        ]);
    }

    /**
     * Get the value is it exist
     * @param array $array the array to be search
     * @param string $key the array key to be search
     * @return mixed the value of the array key or null if not found
     */
    private function assignIfExist($array, $key)
    {
        if ($array && $key && is_array($array)) {
            if (array_key_exists($key, $array)) {
                return $array[$key];
            }
        }

        return null;
    }
}
