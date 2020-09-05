<?php

namespace TechNimbus\Stripe\Model\Resolver\Product;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;

class IsStripeSubscription implements ResolverInterface {

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info,array $value = null, array $args = null) {

        if (!isset($value['model'])) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }

        $product = $value['model'];

        if ($product->getIsStripeSubscription() == TechNimbus\Stripe\Model\Subscription\Source\Yesno::YES) {
            return true;
        }

        return false;
    }
}
