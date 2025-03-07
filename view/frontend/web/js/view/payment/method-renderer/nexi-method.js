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
        'Nexi_Checkout/js/sdk/loader'
    ],
    function (ko, $, _, storage, Component, placeOrderAction, selectPaymentMethodAction, additionalValidators, quote, getTotalsAction, urlBuilder, url, fullScreenLoader, errorProcessor, customer, checkoutData, totals, messageList, $t, modal, sdkLoader) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Nexi_Checkout/payment/nexi',
                config: window.checkoutConfig.payment.nexi
            },
            isEmbedded: ko.observable(false),
            dibsCheckout: ko.observable(false),

            initialize: function () {
                this._super();
                if (this.config.integrationType === 'EmbeddedCheckout') {
                    this.isEmbedded(true);
                }
                if (this.isEmbedded()) {
                    this.renderEmbeddedCheckout();
                }
                // Subscribe to changes in quote totals
                quote.totals.subscribe(function (quote) {
                    // Reload Nexi checkout on quote change
                    console.log('Quote totals changed...', quote);
                    if (this.dibsCheckout()) {
                        this.dibsCheckout().thawCheckout();
                    }
                }, this);

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
            selectPaymentMethod: function () {
                this._super();

            },
            renderEmbeddedCheckout: async function () {
                try {
                    await sdkLoader.loadSdk(this.config.environment === 'test');
                    const response = await this.initializeNexiPayment();
                    if (response.paymentId) {
                        let checkoutOptions = {
                            checkoutKey: response.checkoutKey,
                            paymentId: response.paymentId,
                            containerId: "nexi-checkout-container",
                            language: "en-GB",
                            theme: {
                                buttonRadius: "5px"
                            }
                        };
                        this.dibsCheckout(new Dibs.Checkout(checkoutOptions));

                        this.dibsCheckout().on('payment-completed', async function () {
                            await this.placeOrder();
                            window.location.href = url.build('checkout/onepage/success');
                        }.bind(this));

                        this.dibsCheckout().on('pay-initialized', function (paymentId) {
                            console.log('DEBUG: Payment initialized with ID:', paymentId);
                            //TODO: validate with backend
                            this.dibsCheckout().send('payment-order-finalized', true);
                        }.bind(this));
                    }
                } catch (error) {
                    console.error('Error loading Nexi SDK or initializing payment:', error);
                }

                sdkLoader.loadSdk(this.config.environment === 'test')
                    .then(() => {
                        console.log('Nexi SDK loaded successfully');
                        this.initializeNexiPayment()
                            .catch(function (error) {
                                console.error('Error loading Nexi SDK or initializing payment:', error);
                            });
                    })
                    .catch(x => {
                        console.error('Error loading Nexi SDK:', x);
                    });

            },
            initializeNexiPayment() {
                const payload = {
                    cartId: quote.getQuoteId(),
                    paymentMethod: {
                        method: this.getCode()
                    },
                    integrationType: this.config.integrationType
                };

                const serviceUrl = customer.isLoggedIn()
                    ? urlBuilder.createUrl('/nexi/carts/mine/payment-initialize', {})
                    : urlBuilder.createUrl('/nexi/guest-carts/:quoteId/payment-initialize', {
                        quoteId: quote.getQuoteId()
                    });

                fullScreenLoader.startLoader();

                return new Promise((resolve, reject) => {
                    storage.post(
                        serviceUrl,
                        JSON.stringify(payload)
                    ).done(function (response) {
                        resolve(JSON.parse(response));
                    }).fail(function (response) {
                        errorProcessor.process(response, this.messageContainer);
                        let redirectURL = response.getResponseHeader('errorRedirectAction');

                        if (redirectURL) {
                            setTimeout(function () {
                                errorProcessor.redirectTo(redirectURL);
                            }, 3000);
                        }
                        reject(response);
                    }).always(function () {
                        fullScreenLoader.stopLoader();
                    });
                });
            }
        });
    }
);
