/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Dibs_EasyCheckout/js/action/before-order'
    ],
    function (
        $,
        Component,
        url,
        dibs
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Dibs_EasyCheckout/payment/checkout'
            },
            continueTodibs: function () {
                // dibs("dibseasycheckout", function () {
                //     $.mage.redirect(url.build('easycheckout') + '?checkRedirect=1');
                // });
                return false;
            },
            getNetsUrl: function () {
                return url.build('easycheckout') + '?checkRedirect=1';
            }
        });
    }
);
