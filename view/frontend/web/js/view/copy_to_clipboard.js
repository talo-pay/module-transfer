/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
require([
    'jquery'
], function ($) {
    "use strict";

    $(function () {
        $(".talopay-transfer").on("click", ".copy-button", function () {
            var $btn = $(this);
            var value = $btn.data("copy");
            var $tooltip = $btn.siblings("span");

            if (navigator.clipboard) {
                navigator.clipboard.writeText(value);
                $tooltip.addClass('show');
            } else {
                // Fallback for older browsers
                var tempInput = $("<input>");
                $("body").append(tempInput);
                tempInput.val(value).select();
                document.execCommand("copy");
                tempInput.remove();
                $tooltip.addClass('show');
            }

            if ($tooltip.length) {
                $tooltip.show();
                setTimeout(function () {
                    $tooltip.hide();
                    $tooltip.removeClass('show');
                }, 1200);
            }
        });
    });
});
