<?php

namespace TechNimbus\Stripe\Block\Adminhtml;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Payment;

class CardInfo extends \Magento\Backend\Block\Template {

    /**
     * @var \Magento\Sales\Model\Order\Payment
     */
    protected $payment;

    /**
     * Constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $coreRegistry
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->payment = $coreRegistry->registry('current_order')->getPayment();
        parent::__construct($context, $data);
    }

    /**
     * Validate if the current order is a stripe payment method
     * @return boolean
     */
    public function isStripeMethod()
    {
        return $this->payment->getMethod() == \TechNimbus\Stripe\Model\Stripe::CODE;
    }

    /**
     * Get payment credit card brand
     * @return string
     */
    public function getBrand()
    {
        return $this->payment->getAdditionalInformation('card_brand') ?? '';
    }

    /**
     * Get payment credit card number
     * @return string
     */
    public function getCardNumber()
    {
        return $this->payment->getAdditionalInformation('card_number') ?? '****';
    }

    /**
     * Get payment credit card expiry
     * @return string
     */
    public function getCardExpiry()
    {
        return $this->payment->getAdditionalInformation('card_expiry') ?? '00/0000';
    }

    /**
     * Get payment credit card avs check
     * @return boolean
     */
    public function getAvsCheck()
    {
        return $this->payment->getAdditionalInformation('avs_check') ? true : false;
    }

    /**
     * Get payment credit card cvc check
     * @return boolean
     */
    public function getCvcCheck()
    {
        return $this->payment->getAdditionalInformation('cvc_check') ? true : false;
    }
}
