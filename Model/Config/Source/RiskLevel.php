<?php

namespace TechNimbus\Stripe\Model\Config\Source;

class RiskLevel implements \Magento\Framework\Option\ArrayInterface
{
    const NORMAL    = 'normal';
    const ELEVATED  = 'elevated';
    const HIGHEST   = 'highest';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::NORMAL, 'label' => __('Normal')],
            ['value' => self::ELEVATED, 'label' => __('Elevated')],
            ['value' => self::HIGHEST, 'label' => __('Highest')]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            self::NORMAL => __('Normal'), 
            self::ELEVATED => __('Elevated'),
            self::HIGHEST => __('Highest')
        ];
    }
}
