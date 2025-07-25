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
                template: window.checkoutConfig.payment.nexi.integrationType == 'HostedPaymentPage'
                    ? 'Nexi_Checkout/payment/nexi-hosted.html'
                    : 'Nexi_Checkout/payment/nexi-embedded.html',
                config: window.checkoutConfig.payment.nexi
            },
            isEmbedded: ko.observable(false),
            dibsCheckout: ko.observable(false),
            isRendering: ko.observable(false),
            eventsSubscribed: ko.observable(false),

            initialize: function () {
                this._super();
                if (this.config.integrationType === 'EmbeddedCheckout') {
                    this.isEmbedded(true);
                }

                if (this.isActive() && this.isEmbedded()) {
                    this.renderCheckout();
                }

                quote.paymentMethod.subscribe(function(method) {
                    this.hideIframeIfNeeded();
                }, this);
            },
            isActive: function () {
                return this.getCode() === this.isChecked();
            },
            async placeOrder(data, event) {
                    const response = await placeOrderAction(this.getData(), false, this.messageContainer);
                    this.afterPlaceOrder(response);
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
                this.subscribeToEvents();
                quote.totals.subscribe(async function (quote) {
                    await renderEmbeddedCheckout.call(this);
                    this.subscribeToEvents();

                }, this);
            },
            selectPaymentMethod: function () {
                this._super();

                if (this.isEmbedded()) {
                    this.renderCheckout();
                }

                return true;
            },
            subscribeToEvents: function () {
                if (this.dibsCheckout()) {
                    // If events are already subscribed to this instance, don't subscribe again
                    if (this.eventsSubscribed() === true) {
                        return;
                    }

                    // Store a reference to the current dibsCheckout instance to ensure we're subscribing to the right one
                    const currentDibsCheckout = this.dibsCheckout();

                    currentDibsCheckout.on(
                        "payment-completed",
                        async function () {
                            window.location.href = url.build("checkout/onepage/success");
                        }.bind(this)
                    );

                    currentDibsCheckout.on(
                        "pay-initialized",
                        async function (paymentId) {
                            try {
                                const validationResult = await validatePayment.call(this);
                                if (!validationResult.success) {
                                    await renderEmbeddedCheckout.call(this);
                                    return;
                                }

                                await this.placeOrder(); // Ensure the order is placed before proceeding
                                document.getElementById("nexi-checkout-container").style.position = "relative";
                                document.getElementById("nexi-checkout-container").style.zIndex = "9999";

                                // Trigger Dibs processing only after the order is placed
                                // Use the same instance reference to send the event
                                currentDibsCheckout.send("payment-order-finalized", true);
                            } catch (error) {
                                await renderEmbeddedCheckout.call(this);
                            }
                        }.bind(this)
                    );

                    currentDibsCheckout.on(
                        "payment-cancelled",
                        async function (paymentId) {
                            fullScreenLoader.stopLoader();
                        }.bind(this)
                    );

                    this.eventsSubscribed(true);
                }
            },
            isHosted: function () {
                return this.config.integrationType === 'HostedPaymentPage';
            },
            hideIframeIfNeeded: function() {
                const method = quote.paymentMethod();
                const container = document.getElementById("nexi-checkout-container");
                if (!container) {
                    return;
                }

                if (!method || method.method !== this.getCode()) {
                    // Hide the iframe container if another payment method is selected
                    container.style.display = "none";
                } else {
                    // Show the iframe container if Nexi payment method is selected
                    container.style.display = "block";
                }
            },
        });
    }
);
