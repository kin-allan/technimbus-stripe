<?php

namespace TechNimbus\Stripe\Model\Subscription\Source;

class Yesno extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource {

    CONST NO  = 0;
    CONST YES = 1;

    /**
     * {@inheritdoc}
     */
    public function getAllOptions()
    {
        return [
            ['value' => self::NO,  'label' => __('No')],
            ['value' => self::YES, 'label' => __('Yes')]
        ];
    }
}
