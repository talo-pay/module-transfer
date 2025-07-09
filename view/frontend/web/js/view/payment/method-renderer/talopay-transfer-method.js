/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/set-payment-information'
], function ($, Component, customerData, quote, setPaymentInformation) {
    'use strict';

    return Component.extend({
        redirectAfterPlaceOrder: false,

        defaults: {
            template: 'TaloPay_Transfer/payment/talopay-transfer'
        },

        getInstructions: function () {
            return window.checkoutConfig.payment.talopay_transfer.instructions;
        },

        afterPlaceOrder: function () {
            window.location.replace(window.checkoutConfig.payment.talopay_transfer.redirectUrl);
        }
    });
});
