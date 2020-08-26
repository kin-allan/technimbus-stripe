<?php

namespace TechNimbus\Stripe\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as SessionQuote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Customer\Model\Session as CustomerSession;
use TechNimbus\Stripe\Model\Configs;
use Magento\Integration\Model\Oauth\TokenFactory;

class Provider implements ConfigProviderInterface {

    /**
     * @var SessionQuote
     */
    protected $quoteSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteMaskFactory;

    /**
     * @var \TechNimbus\Stripe\Model\Configs
     */
    protected $configs;

    /**
     * @var TokenFactory
     */
    protected $tokenFactory;

    /**
     * Constructor
     * @param Configs $configs
     */
    public function __construct(
        CustomerSession $customerSession,
        SessionQuote $quoteSession,
        QuoteIdMaskFactory $quoteMaskFactory,
        Configs $configs,
        TokenFactory $tokenFactory
    ) {
        $this->customerSession  = $customerSession;
        $this->quoteSession     = $quoteSession;
        $this->quoteMaskFactory = $quoteMaskFactory;
        $this->configs          = $configs;
        $this->tokenFactory     = $tokenFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config =  [
            'technimbus' => [
                'stripe' => [
                    'active' => false
                ]
            ]
        ];

        if ($this->configs->isActive()) {
            $config['payment']['technimbus']['stripe'] = [
                'active'        => true,
                'pub_key'       => $this->configs->getPublishableKey(),
                'base_url'      => $this->configs->getStoreUrl(),
                'cart_token'    => $this->getCartToken(),
                'quote_id'      => $this->quoteSession->getQuote()->getId(),
                'customer_token' => null,
                'errors'        => [
                    'default'           => __('Unknow Error.'),
                    'cardholdername'    => __('Credit card owner name is required.'),
                    'confirm_failed'    => __('Confirmation failed.')
                ]
            ];

            if ($this->customerSession->isLoggedIn()) {
                $token = $this->tokenFactory->create()->createCustomerToken($this->customerSession->getCustomer()->getId())->getToken();
                $config['payment']['technimbus']['stripe']['customer_token'] = $token;
            }
        }

        return $config;
    }

    /**
     * Retrieve session cart Id
     * @return string|null
     */
    private function getCartToken()
    {
        $cartToken = null;

        $quote = $this->quoteSession->getQuote();

        if ($quote) {
            $quoteMask = $this->quoteMaskFactory->create()->load($quote->getId(), 'quote_id');
            $cartToken = $quoteMask->getMaskedId();
        }

        return $cartToken;
    }
}
