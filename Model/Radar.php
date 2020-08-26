<?php

namespace TechNimbus\Stripe\Model;

use Magento\Sales\Model\Order;
use TechNimbus\Stripe\Model\Config\Source\RiskLevel;
use TechNimbus\Stripe\Model\Order\Status as StripeOrderStatus;

class Radar {

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
     * Check if the order is dangerous
     * @param  Order  $order
     * @param  string $paymentIntentId
     */
    public function isRiskOrder(Order $order, $paymentIntentId)
    {
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
            $riskLevel = $this->getRiskLevel($paymentIntent);
            if (in_array($riskLevel, $this->configs->getHoldRiskLevels())) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Check if the PaymentIntent has outcome and try to get risk level if exists.
     * The outcome information is not mandatory by stripe, so it can not set.
     * @param  object $paymentIntent
     * @return string
     */
    private function getRiskLevel($paymentIntent)
    {
        $riskLevel = RiskLevel::NORMAL;

        try {
            $riskLevel = $paymentIntent->charges->data[0]->outcome->risk_level;
        } catch (\Exception $e) {
            $riskLevel = RiskLevel::NORMAL;
        }

        return strtolower($riskLevel);
    }
}
