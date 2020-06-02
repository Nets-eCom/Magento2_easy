/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function ($,Component,url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Dibs_EasyCheckout/payment/checkout'
            },
            continueTodibs: function () {
                $.mage.redirect(url.build('easycheckout') + '?checkRedirect=1');
                return false;
            }
        });
    }
);
