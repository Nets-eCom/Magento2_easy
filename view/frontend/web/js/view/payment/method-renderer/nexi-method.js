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
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Ui/js/modal/modal',
        'Nexi_Checkout/js/sdk/loader',
        'Nexi_Checkout/js/view/payment/initialize-payment',
        'Nexi_Checkout/js/view/payment/render-embedded'
    ],
    function (
        ko,
        $,
        _,
        storage,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        additionalValidators,
        quote,
        getTotalsAction,
        urlBuilder,
        url,
        fullScreenLoader,
        errorProcessor,
        customer,
        checkoutData,
        totals,
        messageList,
        $t,
        modal,
        sdkLoader,
        initializeCartPayment,
        renderEmbeddedCheckout
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Nexi_Checkout/payment/nexi',
                config: window.checkoutConfig.payment.nexi,
            },
            isEmbedded: ko.observable(false),
            dibsCheckout: ko.observable(false),

            isHosted: function () {
                return !this.isEmbedded();
            },
            initialize: function () {
                this._super();
                if (this.config.integrationType === 'EmbeddedCheckout') {
                    this.isEmbedded(true);
                }

                if (this.isActive()) {
                    this.renderCheckout();
                }
            },
            isActive: function () {
                return this.getCode() === this.isChecked();
            },
            placeOrder: function (data, event) {
                let placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                return $.when(placeOrder).done(function (response) {
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
            },
            renderCheckout() {
                renderEmbeddedCheckout.call(this);
                // Subscribe to changes in quote totals
                quote.totals.subscribe(function (quote) {
                    // Reload Nexi checkout on quote change
                    console.log('Quote totals changed. Reloading the Checkout.', quote);
                    renderEmbeddedCheckout.call(this);
                }, this);
            }, selectPaymentMethod: function () {
                this._super();
                this.renderCheckout();

                return true;
            }
        });
    }
);
