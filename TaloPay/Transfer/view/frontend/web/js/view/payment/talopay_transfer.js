define([
    "uiComponent",
    "Magento_Checkout/js/model/payment/renderer-list",
], function (Component, rendererList) {
    "use strict";
    rendererList.push(
        {
            type: "talopay_transfer",
            component:
                "TaloPay_Transfer/js/view/payment/method_renderer/talopay_transfer_method",
        }
        // other payment method renderers if required
    );
    /** Add view logic here if needed */
    return Component.extend({});
});
