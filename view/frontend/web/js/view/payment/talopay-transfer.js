/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push({
            type: 'talopay_transfer',
            component: 'TaloPay_Transfer/js/view/payment/method-renderer/talopay-transfer-method'
        });
        return Component.extend({});
    }
);
