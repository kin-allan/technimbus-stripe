<?php

namespace TechNimbus\Stripe\Model;

use TechNimbus\Stripe\Api\SetupIntentInterface;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

use TechNimbus\Stripe\Model\Client as StripeClient;

class SetupIntent implements SetupIntentInterface {


    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteMaskFactory;

    /**
     * @var StripeClient
     */
    protected $client;

    /**
     * Constructor
     * @param StripeClient $client
     */
    public function __construct(
        QuoteIdMaskFactory $quoteMaskFactory,
        CartRepositoryInterface $cartRepository,
        StripeClient $client
    ) {
        $this->cartRepository   = $cartRepository;
        $this->quoteMaskFactory = $quoteMaskFactory;
        $this->client           = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function createAsCustomer($cartId)
    {
        if ($this->isValidCart($cartId)) {
            return $this->createSetupIntent();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createAsGuest($cartToken)
    {
        $quoteMask = $this->quoteMaskFactory->create()->load($cartToken, 'masked_id');

        if ($quoteMask && ($cartId = $quoteMask->getQuoteId())) {
            if ($this->isValidCart($cartId)) {
                return $this->createSetupIntent();
            }
        }

        return false;
    }

    /**
     * Validate if the cart exists
     * @param  integer  $cartId
     * @return boolean
     */
    private function isValidCart($cartId)
    {
        try {
            $cart = $this->cartRepository->get($cartId);
            if ($cart->getId()) {
                return true;
            }
        } catch (\NoSuchEntityException $noError) {
            return false;
        }

        return false;
    }

    /**
     * Create the Setup Intent at the Stripe
     * @return string|boolean the setup intent client secret or false if fails
     */
    private function createSetupIntent()
    {
        try {
            $this->client->init();
            $setupIntent = \Stripe\SetupIntent::create([
                'payment_method_types' => ['card']
            ]);

            return $setupIntent->client_secret;
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}
