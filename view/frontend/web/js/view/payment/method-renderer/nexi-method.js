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
        'Nexi_Checkout/js/view/payment/validate',
        'Magento_Checkout/js/model/payment/place-order-hooks',
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
        validatePayment,
        placeOrderHooks,
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: window.checkoutConfig.payment.nexi.integrationType == 'HostedPaymentPage'
                    ? (window.checkoutConfig.payment.nexi.payTypeSplitting
                        ? 'Nexi_Checkout/payment/nexi-hosted-type-split.html'
                        : 'Nexi_Checkout/payment/nexi-hosted.html')
                    : (window.checkoutConfig.payment.nexi.payTypeSplitting
                        ? 'Nexi_Checkout/payment/nexi-embedded-type-split.html'
                        : 'Nexi_Checkout/payment/nexi-embedded.html'),
                config: window.checkoutConfig.payment.nexi,
                creditCardType: '',
                creditCardExpYear: '',
                creditCardExpMonth: '',
                creditCardNumber: '',
                creditCardVerificationNumber: '',
                selectedCardType: null
            },
            payTypeSplitting: ko.observable(window.checkoutConfig.payment.nexi.payTypeSplitting),
            isEmbedded: ko.observable(false),
            dibsCheckout: ko.observable(false),
            isRendering: ko.observable(false),
            eventsSubscribed: ko.observable(false),
            subselection: ko.observable(false),
            initialize: function () {
                this._super();
                if (this.config.integrationType === 'EmbeddedCheckout') {
                    this.isEmbedded(true);
                }
                if (this.payTypeSplitting()) {
                    if (this.isActive()) {
                        this.subselection(checkoutData.getNexiSubselection() || false);
                        this.moveContentToSubselection(this.subselection());
                    }
                    this.subselection.subscribe(function (newSubselection) {
                        if (newSubselection) {
                            this.moveContentToSubselection(newSubselection);
                            this.selectPaymentMethod();
                        }
                        checkoutData.setNexiSubselection(newSubselection);
                    }, this);

                }
                if (this.isActive() && this.isEmbedded() && (!this.payTypeSplitting() || this.subselection())) {
                    this.renderCheckout();
                }

                quote.paymentMethod.subscribe(function (method) {
                    this.hideIframeIfNeeded();
                    this.clearSubselection(method);
                }, this);

                placeOrderHooks.requestModifiers.push(
                    function (headers, payload) {

                        if (payload.paymentMethod.extension_attributes === undefined) {
                            payload.paymentMethod.extension_attributes = {};
                        }
                        payload.paymentMethod.extension_attributes.subselection = this.subselection();
                    }.bind(this)
                );
            },
            afterRender: function () {
                if (this.isActive()) {
                    this.moveContentToSubselection(this.subselection());
                }
            },
            moveContentToSubselection: function (subselection) {
                const contentElement = document.getElementById("nexi-content");
                const placeholderElement = document.getElementById("nexi-content-" + subselection);
                if (contentElement && placeholderElement) {
                    if (subselection) {
                        placeholderElement.appendChild(contentElement);
                    } else {
                        const originalContainer = document.getElementById("nexi-original-container");
                        if (originalContainer) {
                            originalContainer.appendChild(contentElement);
                        }
                    }
                }
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
                    if (!this.isActive()) {
                        return;
                    }
                    await renderEmbeddedCheckout.call(this);
                    this.subscribeToEvents();
                }, this);
            },
            clearNexiCheckout() {
                if (this.dibsCheckout()) {
                    this.dibsCheckout().cleanup();
                }
                if (document.getElementById("nexi-checkout-container")) {
                    document.getElementById("nexi-checkout-container").innerHTML = "";
                }
            },
            selectPaymentMethod: function () {
                this.clearNexiCheckout();
                this._super();
                checkoutData.setNexiSubselection(this.subselection());
                if (this.isEmbedded() && (!this.payTypeSplitting() || this.subselection())) {
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
                                    console.error("Payment validation failed. paymentId:", paymentId);
                                    window.location.reload();
                                }

                                await this.placeOrder(); // Ensure the order is placed before proceeding

                                // Trigger Dibs processing only after the order is placed
                                // Use the same instance reference to send the event
                                currentDibsCheckout.send("payment-order-finalized", true);
                            } catch (error) {
                                console.error("Error during payment initialization:", error);
                                window.location.reload();
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
            hideIframeIfNeeded: function () {
                const method = quote.paymentMethod();
                const container = document.getElementById("nexi-checkout-container");
                if (!container) {
                    return;
                }

                if (!method || method.method !== this.getCode()) {
                    container.style.display = "none";
                } else {
                    container.style.display = "block";
                }
            },
            getData: function () {
                return {
                    method: this.getCode(),
                    additional_data: {
                        integrationType: this.config.integrationType,
                        paymentMethod: this.config.paymentMethod,
                        subselection: this.subselection(),
                    }
                };
            },
            clearSubselection(method) {
                if (method && method.method !== this.getCode()) {
                    this.subselection(false);
                }
            },
            getSubselections: function () {
                if (this.config.payTypeSplitting && this.config.subselections) {
                    return this.config.subselections.map(function (subselection) {
                        return {
                            value: subselection.value,
                            label: subselection.label,
                        };
                    }, this);
                }
                return [];
            }
        });
    }
);
