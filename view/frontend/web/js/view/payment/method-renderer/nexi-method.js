/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'underscore',
        'mage/storage',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Ui/js/modal/modal'
    ],
    function (ko, $, _, storage, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Nexi_Checkout/payment/nexi'
            },
            /**
             * Returns send check to info.
             *
             * @return {*}
             */
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            /**
             * Returns payable to info.
             *
             * @return {*}
             */
            getPayableTo: function () {
                return window.checkoutConfig.payment.checkmo.payableTo;
            },
            paymentMethod: 'nexi',
            redirectAfterPlaceOrder: false,

        });


    }
);
