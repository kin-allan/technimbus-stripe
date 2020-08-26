<?php

namespace TechNimbus\Stripe\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Stripe extends AbstractMethod {

    /**
     * Payment module method code
     */
    CONST CODE = 'technimbus_stripe';

    /**
     * Request data param key retrieve by payment request
     */
    CONST REQUEST_DATA_PARAM = 'technimbus_stripe_data';

    /**
     * @var \TechNimbus\Stripe\Model\Stripe\Capture
     */
    protected $stripeCapture;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var string
     */
    protected $_code = 'technimbus_stripe';

    /**
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Constructor
     * @param \Magento\Framework\Model\Context                          $context
     * @param \Magento\Framework\Registry                               $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory         $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory              $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                              $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface        $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                      $logger
     * @param \TechNimbus\Stripe\Model\Stripe\Capture                   $stripeCapture
     * @param \Magento\Quote\Api\CartRepositoryInterface                $cartRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource   $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb             $resourceCollection
     * @param array                                                     $data
     * @param \Magento\Directory\Helper\Data                            $directory
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \TechNimbus\Stripe\Model\Stripe\Capture $stripeCapture,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Directory\Helper\Data $directory = null
    ) {
        $this->cart             = $cart;
        $this->stripeCapture    = $stripeCapture;
        $this->cartRepository   = $cartRepository;

        return parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
    }

    /**
     * {@inheritdoc}
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$payment->hasAdditionalInformation(self::REQUEST_DATA_PARAM)) {
            throw new LocalizedException(__('Unable to retrieve payment data'));
        }

        $payment->setIsTransactionClosed(0);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$payment->hasAdditionalInformation(self::REQUEST_DATA_PARAM)) {
            throw new LocalizedException(__('Unable to retrieve payment data'));
        }

        $data = $payment->getAdditionalInformation(self::REQUEST_DATA_PARAM);

        if (!array_key_exists('stripe_token', $data)) {
            throw new LocalizedException('Token missing.');
        }

        $quote = false;

        if (isset($data['quote_id'])) {
            try {
                $quote = $this->cartRepository->get($data['quote_id']);
            } catch (NoSuchEntityException $e) {
                $quote = false;
            }
        } else {
            $quote = $this->cart->getQuote();
        }

        if (!$quote || !$quote->getId()) {
            throw new LocalizedException(__('Unable to find quote.'));
        }

        $paymentIntentId = $this->stripeCapture->capture($quote, $data['stripe_token']);

        if ($paymentIntentId) {
            $payment->setParentTransactionId($paymentIntentId);
            $payment->setTransactionId($paymentIntentId);
            $payment->setIsTransactionClosed(1);
            return $this;
        } else {
            throw new LocalizedException(__('Payment failed.'));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigPaymentAction()
    {
        return self::ACTION_AUTHORIZE_CAPTURE;
    }
}
