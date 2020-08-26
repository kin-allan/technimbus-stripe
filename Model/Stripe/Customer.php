<?php

namespace TechNimbus\Stripe\Model\Stripe;

use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\Exception\LocalizedException;

class Customer {

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * Constructor
     * @param \Magento\Customer\Model\Session           $customerSession
     * @param \Magento\Customer\Model\CustomerFactory   $customerFactory
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory
        )
    {
        $this->customerSession      = $customerSession;
        $this->customerFactory      = $customerFactory;
    }

    /**
     * Get Stripe Customer
     * @param  Quote   $quote
     * @param  string  $paymentMethodId
     * @return object
     * @throws LocalizedException
     */
    public function get(Quote $quote, string $paymentMethodId)
    {
        $magentoCustomer = $this->getMagentoCustomer($quote);

        if (!$magentoCustomer) {
            $billingAddress = $quote->getBillingAddress();
            $customer = $this->customerFactory->create();
            $customer->setEmail($billingAddress->getEmail());
            $customer->setFirstname($billingAddress->getFirstname());
            $customer->setLastname($billingAddress->getLastname());
        }

        $stripeCustomer = $this->getStripeCustomer($customer, $paymentMethodId);

        if ($stripeCustomer) {
            return $stripeCustomer;
        } else {
            throw new LocalizedException(__('Unable to use stripe customer'));
        }
    }

    /**
     * Get Magento customer
     * @param  Quote  $quote
     * @return MagentoCustomer|boolean
     */
    public function getMagentoCustomer(Quote $quote)
    {
        $customer = false;

        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
        } else {
            $customerId = $quote->getCustomerId();
            if ($customerId) {
                try {
                    $customer = $this->customerFactory->create()->load($customerId);
                } catch (LocalizedException $e) {
                    $customer = false;
                }
            }
        }

        return $customer;
    }

    /**
     * Try to get stripe customer if exists, otherwise create. Then attach the payment method
     * @param MagentoCustomer $customer
     * @param string $paymentMethodId
     * @return object|boolean the Stripe Customer object or false if fails
     */
    public function getStripeCustomer(MagentoCustomer $customer, $paymentMethodId)
    {
        $stripeCustomer = false;

        try {
            $customerList = \Stripe\Customer::all([ 'limit' => 1, 'email' => $customer->getEmail() ]);
            if ($customerList) {
                if (count($customerList->data) > 0) {
                    if ($customerList->data[0]->email == $customer->getEmail()) {
                        $stripeCustomer = $customerList->data[0];
                        $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
                        if ($paymentMethod) {
                            $paymentMethod->attach(['customer' => $stripeCustomer->id ]);
                            \Stripe\Customer::update($stripeCustomer->id, [
                                'invoice_settings' => [
                                    'default_payment_method' => $paymentMethod->id
                                ]
                            ]);
                        } else {
                            $stripeCustomer = false;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $stripeCustomer = false;
        }

        if (!$stripeCustomer) {
            try {
                $stripeCustomer = \Stripe\Customer::create([
                    'email'             => $customer->getEmail(),
                    'name'              => $customer->getFirstname() . ' ' . $customer->getLastname(),
                    'payment_method'    => $paymentMethodId
                ]);
            } catch (\Exception $e) {
                $stripeCustomer = false;
            }
        }

        return $stripeCustomer;
    }
}
