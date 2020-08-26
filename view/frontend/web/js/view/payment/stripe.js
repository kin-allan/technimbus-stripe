define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function(Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'technimbus_stripe',
        component: 'TechNimbus_Stripe/js/view/payment/method-renderer/stripe'
    });

    return Component.extend({});
})
