<?php

namespace TechNimbus\Stripe\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product;
use TechNimbus\Stripe\Model\Subscription\Product\Group as StripeGroup;

use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as ResourceStatusFactory;

class InstallData implements InstallDataInterface {

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var StatusFactory
     */
    protected $statusFactory;

    /**
     * @var ResourceStatusFactory
     */
    protected $resourceStatusFactory;

    /**
     * Constructor.
     * @param EavSetupFactory       $eavSetupFactory
     * @param StatusFactory         $statusFactory
     * @param ResourceStatusFactory $resourceStatusFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        StatusFactory $statusFactory,
        ResourceStatusFactory $resourceStatusFactory
    ) {
        $this->eavSetupFactory          = $eavSetupFactory;
        $this->statusFactory            = $statusFactory;
        $this->resourceStatusFactory    = $resourceStatusFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(Product::ENTITY, 'is_stripe_subscription', [
            'group'        => StripeGroup::TAG,
            'label'        => 'Is Subscription?',
            'type'         => 'int',
            'input'        => 'select',
            'source'       => 'TechNimbus\Stripe\Model\Subscription\Source\Yesno',
            'global'       => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible'      => true,
            'required'     => false,
            'user_defined' => false,
            'default'      => \TechNimbus\Stripe\Model\Subscription\Source\Yesno::NO
        ]);

        $eavSetup->addAttribute(Product::ENTITY, 'stripe_interval_count', [
            'group'        => StripeGroup::TAG,
            'label'        => 'Interval Count',
            'type'         => 'int',
            'input'        => 'text',
            'global'       => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible'      => true,
            'required'     => false,
            'user_defined' => false,
            'default'      => 1
        ]);

        $eavSetup->addAttribute(Product::ENTITY, 'stripe_interval_type', [
            'group'        => StripeGroup::TAG,
            'label'        => 'Interval Type',
            'type'         => 'varchar',
            'input'        => 'select',
            'source'       => 'TechNimbus\Stripe\Model\Subscription\Source\Interval',
            'global'       => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible'      => true,
            'required'     => false,
            'user_defined' => false,
            'default'      => \TechNimbus\Stripe\Model\Subscription\Source\Interval::MONTHLY
        ]);

        /* Order Status */
        $resourceStatus = $this->resourceStatusFactory->create();

        $status = $this->statusFactory->create();
        $status->setData([
            'status' => \TechNimbus\Stripe\Model\Order\Status::STRIPE_SUSPECT_FRAUD,
            'label' => 'Stripe Suspect Fraud'
        ]);

        $resourceStatus->save($status);
        $status->assignState('complete', false, false);
    }
}
