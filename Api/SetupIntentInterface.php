<?php

namespace TechNimbus\Stripe\Api;

interface SetupIntentInterface {

    /**
     * Create Setup Intent for customer cart
     * @param  integer $cartId
     * @return string|boolean the setup intent client secret or false if fails
     */
    public function createAsCustomer($cartId);

    /**
     * Create Setup Intent for guest cart
     * @param  string $cartToken
     * @return string|boolean the setup intent client secret or false if fails
     */
    public function createAsGuest($cartToken);
}
