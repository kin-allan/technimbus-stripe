<?php

namespace TechNimbus\Stripe\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Sales\Model\Order;
use TechNimbus\Stripe\Model\Order\Status as StripeOrderStatus;

class SaveOrderAfterSubmit implements ObserverInterface {

    /**
     * @var \TechNimbus\Stripe\Model\Radar
     */
    protected $radar;

    /**
     * @var \TechNimbus\Stripe\Model\CardInfo
     */
    protected $cardInfo;

    /**
     * @var \TechNimbus\Stripe\Model\Configs
     */
    protected $configs;

    /**
     * Constructor
     * @param \TechNimbus\Stripe\Model\Radar   $radar
     * @param \TechNimbus\Stripe\Model\Configs $configs
     */
    public function __construct(
        \TechNimbus\Stripe\Model\Radar $radar,
        \TechNimbus\Stripe\Model\CardInfo $cardInfo,
        \TechNimbus\Stripe\Model\Configs $configs
    ) {
        $this->radar = $radar;
        $this->cardInfo = $cardInfo;
        $this->configs = $configs;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(EventObserver $observer)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getData('order');
        $payment = $order->getPayment();

        if ($payment->getMethod() == \TechNimbus\Stripe\Model\Stripe::CODE) {

            $transactionKey = $payment->getLastTransId();
            $suspectFraud = false;

            if (stripos($transactionKey, "pi_") !== false) {
                if ($this->configs->isRadarEnable()) {
                    $suspectFraud = $this->radar->isRiskOrder($order, $transactionKey);
                }

                $cardInfo = $this->cardInfo->get($transactionKey);

                if ($cardInfo) {
                    $payment->setAdditionalInformation('card_brand', $cardInfo->brand);
                    $payment->setAdditionalInformation('card_number', $cardInfo->number);
                    $payment->setAdditionalInformation('card_expiry', $cardInfo->expiry);
                    $payment->setAdditionalInformation('avs_check', $cardInfo->avsCheck);
                    $payment->setAdditionalInformation('cvc_check', $cardInfo->cvcCheck);
                    $payment->save();

                    if ($this->configs->holdOrdersWhenAVSFails() && !$cardInfo->avsCheck) {
                        $suspectFraud = true;
                    }

                    if ($this->configs->holdOrdersWhenCVCFails() && !$cardInfo->cvcCheck) {
                        $suspectFraud = true;
                    }
                }
            }

            if ($suspectFraud) {
                $order->setState(Order::STATE_COMPLETE)->setStatus(StripeOrderStatus::STRIPE_SUSPECT_FRAUD)->save();
                $order->addStatusHistoryComment(__('Stripe hold this order due the validation failed. Please check if it\'s not a fraud order'))->save();
            } else {
                $order->setState(Order::STATE_COMPLETE)->setStatus(Order::STATE_COMPLETE)->save();
            }
        }

        return $this;
    }

}
