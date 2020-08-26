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
     * Check payment intent if needs to change the order status to review based on the risk level
     * @param  Order  $order
     * @param  string $paymentIntentId
     */
    public function evaluate(Order $order, $paymentIntentId)
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
                $order->setState(Order::STATE_COMPLETE)->setStatus(StripeOrderStatus::STRIPE_SUSPECT_FRAUD)->save();
                $order->addStatusHistoryComment(__('Stripe Radar hold this order due the risk level. Please check if it\'s not a fraud order'))->save();
            } else {
                $order->setState(Order::STATE_COMPLETE)->setStatus(Order::STATE_COMPLETE)->save();
            }
        }
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
