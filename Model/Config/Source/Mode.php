<?php

namespace TechNimbus\Stripe\Model\Config\Source;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    const TEST = 'test';
    const LIVE = 'live';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::TEST, 'label' => __('Test')],
            ['value' => self::LIVE, 'label' => __('Live')]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            self::TEST => __('Test'),
            self::LIVE => __('Live')
        ];
    }
}
