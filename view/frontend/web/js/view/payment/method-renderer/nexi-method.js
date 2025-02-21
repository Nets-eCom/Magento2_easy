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
    function (ko, $, _, storage, Component, placeOrderAction, selectPaymentMethodAction, additionalValidators, quote, getTotalsAction, urlBuilder, url, fullScreenLoader, customer, checkoutData, totals, messageList, $t, modal) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Nexi_Checkout/payment/nexi',
                config: window.checkoutConfig.payment.nexi
            },
            placeOrder: function (data, event) {
                let placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                $.when(placeOrder).done(function (response) {
                    this.afterPlaceOrder(response);
                }.bind(this));
            },
            afterPlaceOrder: function (response) {
                if (this.config.integrationType === 'HostedPaymentPage') {
                    let redirectUrl = JSON.parse(response).redirect_url;
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                }
            }
        });
    }
);
