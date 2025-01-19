define([
    "Magento_Checkout/js/view/payment/default",
    "jquery",
    "Magento_Checkout/js/model/payment/additional-validators",
    "Magento_Ui/js/model/messageList",
], function (Component, $, validators, messageList) {
    "use strict";
    return Component.extend({
        defaults: {
            template: "TaloPay_Transfer/payment/talopay_transfer",
        },

        getData: function () {
            return {
                method: this.item.method,
            };
        },
        getCode: function () {
            return "talopay_transfer";
        },
        // add required logic here
    });
});
