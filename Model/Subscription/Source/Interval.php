<?php

namespace TechNimbus\Stripe\Model\Subscription\Source;

class Interval extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource {

    CONST DAILY     = 'day';
    CONST WEEKLY    = 'week';
    CONST MONTHLY   = 'month';
    CONST ANNUALY   = 'year';

    /**
     * {@inheritdoc}
     */
    public function getAllOptions()
    {
        return [
            ['value' => self::DAILY,    'label' => __('Daily')],
            ['value' => self::WEEKLY,   'label' => __('Weekly')],
            ['value' => self::MONTHLY,  'label' => __('Monthly')],
            ['value' => self::ANNUALY,  'label' => __('Annualy')],
        ];
    }
}
