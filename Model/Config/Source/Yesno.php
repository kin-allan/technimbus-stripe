<?php

namespace TechNimbus\Stripe\Model\Config\Source;

class Yesno implements \Magento\Framework\Option\ArrayInterface
{
    const NO    = 0;
    const YES   = 1;

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::NO, 'label' => __('No')],
            ['value' => self::YES, 'label' => __('Yes')]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            self::NO => __('No'),
            self::YES => __('Yes')
        ];
    }
}
