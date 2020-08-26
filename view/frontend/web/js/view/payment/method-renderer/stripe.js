define([
    'ko',
    'underscore',
    'Magento_Checkout/js/view/payment/default'
], function (ko, _, Component) {
    'use strict';

    return Component.extend({
        /**
         * Default Settings
         */
        defaults: {
            template: 'TechNimbus_Stripe/payment/stripe',
            method_code: 'technimbus_stripe',
            active: ko.observable(false),
            stripe: false,
            cardholder_name: '',
            token: false,
            quote_id: null,
            cart_token: null,
            customer_token: null,
            elements: false,
            cardNumberElement: false,
            cardExpiryElement: false,
            cardCvcElement: false,
            errors: [],
            base_url: false
        },
        /**
         * Initialize view
         * @return {exports}
         */
        initialize: function () {
            this._super();
            this.active(window.checkoutConfig.payment.technimbus.stripe.active);
            if (this.isActive()) {
                this.quote_id               = window.checkoutConfig.payment.technimbus.stripe.quote_id;
                this.cart_token             = window.checkoutConfig.payment.technimbus.stripe.cart_token;
                this.pub_key                = window.checkoutConfig.payment.technimbus.stripe.pub_key
                this.base_url               = window.checkoutConfig.payment.technimbus.stripe.base_url;
                this.errors                 = window.checkoutConfig.payment.technimbus.stripe.errors;
                this.customer_token         = window.checkoutConfig.payment.technimbus.stripe.customer_token;
            }

            return this;
        },
        /**
         * Get Context
         */
        context: function() {
            return this;
        },
        /**
         * Get payment method code
         */
        getCode: function() {
            return this.method_code;
        },
        /**
         * @return {Boolean}
         */
        isActive: function() {
            return this.active();
        },
        /**
         * Get payment method data
         */
        getData: function() {
            return {
                method: this.method_code,
                additional_data: {
                    stripe_token: this.token,
                    quote_id: this.quote_id
                }
            }
        },
        /**
         * Initialize Stripe
         */
        initStripe: function() {
            if (!this.isActive()) {
                return false;
            }

            if (!this.stripe) {
                if (this.pub_key) {
                    this.stripe = Stripe(this.pub_key);
                }
            }

            if (this.stripe) {

                this.elements = this.stripe.elements();
                this.cardNumberElement = this.elements.create('cardNumber', {
                    showIcon: true,
                    style: this.getStyle()
                });
                this.cardNumberElement.mount('#card-number');
                this.cardNumberElement.addEventListener('change', function(event) {
                    let msgError = event.error ? event.error.message : '';
                    this.showError(msgError);
                }.bind(this));

                this.cardExpiryElement = this.elements.create('cardExpiry', {
                    style: this.getStyle()
                });
                this.cardExpiryElement.mount('#card-expiry');
                this.cardExpiryElement.addEventListener('change', function(event) {
                    let msgError = event.error ? event.error.message : '';
                    this.showError(msgError);
                }.bind(this));

                this.cardCvcElement = this.elements.create('cardCvc', {
                    style: this.getStyle()
                });
                this.cardCvcElement.mount('#card-cvc');
                this.cardCvcElement.addEventListener('change', function(event) {
                    let msgError = event.error ? event.error.message : '';
                    this.showError(msgError);
                }.bind(this));
            }
        },
        /**
         * Display errors on the credit card box
         * @param  {String} message
         */
        showError: function(message) {
            document.getElementById('card-errors').textContent = message;
        },
        /**
         * @return {Object}
         */
        getStyle: function() {
            return {
                base: {
                    color: '#32325D',
                    lineHeight: '30px',
                    fontFamily: '"Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '14px',
                    backgroundColor: '#FFF',
                    '::placeholder' : {
                        color: '#AAB7C4'
                    }
                },
                invalid: {
                    color: '#F91C1C',
                    iconColor: '#F91C1C'
                }
            }
        },
        /**
         * Generate the Payment Intent and try to place order
         * @return {Boolean}
         */
        saveOrder: async function() {
            if (this.token) {
                this.placeOrder();
            }

            if (!this.cardholder_name) {
                this.showError(this.errors.cardholdername);
                return false;
            }

            let requestUrl = this.base_url + 'rest/V1/technimbus_stripe/createSetupIntent';

            if (this.cart_token) {
                requestUrl += '/'  + this.cart_token;
            }

            let setupHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };

            if (this.customer_token) {
                setupHeaders.Authorization = 'Bearer ' + this.customer_token;
            }

            let clientSecret = await fetch(requestUrl, {
                method: 'POST',
                mode: 'same-origin',
                cache: 'no-cache',
                headers: setupHeaders
            }).then(function (response) {
                return response.json();
            }).then(function(data) {
                return data;
            });

            let caller = this;

            if (clientSecret) {
                let billing_details = {
                    name: this.cardholder_name
                }

                let postalCode      = document.querySelector('input[name="postcode"]');
                let addressLine1    = document.querySelector('input[name="street[0]"]');

                if (postalCode && addressLine1) {
                    billing_details.address = {
                        line1: addressLine1.value,
                        postal_code: postalCode.value
                    }
                }

                let cardSetup = await this.stripe.confirmCardSetup(clientSecret, {
                    payment_method: {
                        'card': caller.cardNumberElement,
                        'billing_details': billing_details
                    }
                });

                if (cardSetup && cardSetup.setupIntent && cardSetup.setupIntent.status == "succeeded") {
                    this.token = cardSetup.setupIntent.payment_method;
                    this.placeOrder();
                    return true;
                } else {
                    this.showError(this.errors.confirm_failed);
                }
            } else {
                this.showError(this.errors.default);
            }

            return false;
        }
    })
});
