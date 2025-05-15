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
        'Nexi_Checkout/js/view/payment/render-embedded',
        'Nexi_Checkout/js/view/payment/validate'
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
        renderEmbeddedCheckout,
        validatePayment
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: window.checkoutConfig.payment.nexi.integrationType ? 'Nexi_Checkout/payment/nexi-hosted' : 'Nexi_Checkout/payment/nexi-embedded.html',
                config: window.checkoutConfig.payment.nexi
                template: 'Nexi_Checkout/payment/nexi',
                config: window.checkoutConfig.payment.nexi,
            },
            isEmbedded: ko.observable(false),
            dibsCheckout: ko.observable(false),
            isRendering: ko.observable(false),
            eventsSubscribed: ko.observable(false),

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
                if (this.isHosted()) {
                    let redirectUrl = JSON.parse(response).redirect_url;
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                }
            },
            async renderCheckout() {
                await renderEmbeddedCheckout.call(this);
                this.subscribeToEvents();
                quote.totals.subscribe(async function (quote) {
                    await renderEmbeddedCheckout.call(this);
                    this.subscribeToEvents();

                }, this);
            },
            selectPaymentMethod: function () {
                this._super();
                this.renderCheckout();

                return true;
            },
            subscribeToEvents: function () {
                if (this.dibsCheckout() && this.eventsSubscribed() === false) {
                    console.log("DEBUG: Subscribing to events");
                    this.dibsCheckout().on(
                        "payment-completed",
                        async function () {
                            window.location.href = url.build("checkout/onepage/success");
                        }.bind(this)
                    );

                    this.dibsCheckout().on(
                        "pay-initialized",
                        async function (paymentId) {
                            try {
                                const validationResult = await validatePayment.call(this);
                                if (!validationResult.success) {
                                    console.warn("DEBUG: Validation failed, reloading the checkout. Nexi paymentId: ", paymentId);

                                    await renderEmbeddedCheckout.call(this);
                                    this.subscribeToEvents();
                                } else {
                                    console.log("DEBUG: Validation ok, placing the order. Nexi paymentId: ", paymentId);
                                    await this.placeOrder();
                                    fullScreenLoader.startLoader();
                                    document.getElementById("nexi-checkout-container").style.position = "relative";
                                    document.getElementById("nexi-checkout-container").style.zIndex = "9999";
                                    this.dibsCheckout().send("payment-order-finalized", true);
                                    // add some mask to block the screen, only allow to deal with the iframe

                                }
                            }catch (error) {
                                console.error("DEBUG: Error in payment initialization:", error);
                                await renderEmbeddedCheckout.call(this);
                                this.subscribeToEvents();
                            }
                        }.bind(this)
                    );

                    // TODO: check how to trigger remove mask if payment cancelled in the iframe,
                    //  as this seems to not work
                    this.dibsCheckout().on(
                        "payment-cancelled",
                        async function (paymentId) {
                            fullScreenLoader.stopLoader();
                            console.log("DEBUG: Payment cancelled with ID:", paymentId);
                        }.bind(this)
                    );

                    this.eventsSubscribed(true);
                }
            },
            isHosted: function () {
                return this.config.integrationType === 'HostedPaymentPage';
            },
        });
    }
);
