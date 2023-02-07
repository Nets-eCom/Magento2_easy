/*browser:true*/
/*global define*/
define(
        [
            'jquery',
            'Magento_Checkout/js/view/payment/default',
            'mage/url',
            'Dibs_EasyCheckout/js/action/before-order',
            'Magento_Checkout/js/model/full-screen-loader',
            'uiRegistry',
            'vanillaCheckoutHandler',
            'Magento_SalesRule/js/action/set-coupon-code',
            'Magento_SalesRule/js/action/cancel-coupon',
            'Magento_Checkout/js/model/quote'
        ],
        function (
                $,
                Component,
                url,
                dibs,
                fullScreenLoader,
                uiRegistry,
                vanillaCheckoutHandler,
                setCouponCodeAction,
                cancelCouponCodeAction,
                quoteModel
                ) {
            'use strict';

            var component = Component.extend({

                defaults: {
                    template: 'Dibs_EasyCheckout/payment/checkout',
                    iframeUrl: null,
                    hideLoader: false
                },
                eventsInstantiated: false,
                continueToDibsRedirect: function () {
                    dibs("dibseasycheckout", function () {
                        let callback = function (paymentConfiguration) {
                            if (paymentConfiguration.error_message != "") {
                                alert(paymentConfiguration.error_message);
                            } else {
                                $.mage.redirect(paymentConfiguration.checkoutUrl);
                            }
                        };

                        this.initPaymentConfiguration(0, callback);
                    }.bind(this));
                    return false;
                },
                continueToDibs: function () {
                    dibs("dibseasycheckout", function () {});
                    return false;
                },
                getNetsUrl: function () {
                    return url.build('easycheckout') + '?checkRedirect=1';
                },
                getLogoUrl : function() {
                    return window.checkoutConfig.payment.logoUrl;
                },
                getTitle : function() {
                    return window.checkoutConfig.payment.paymentName;
                },
                initObservable: function () {
                    this._super();
                    this.observe('iframeUrl');
                    this.observe('hideLoader');

                    return this;
                },
                initialize: function () {
                    this._super();
                    if (window.isVanillaEmbeded) {
                        this.initializeNewPayment();
                    }
                    uiRegistry.set('nwtdibsCheckout', this);
                },
                initializeNewPayment: function () {
                    if (!window.isVanillaEmbeded) {
                        return;
                    }
                    let placehplder = document.getElementById('nets-placeholder');
                    if (placehplder) {
                        placehplder.innerHTML = "";
                    }

                    let callback = function (paymentConfiguration) {
                        if (paymentConfiguration.error_message != "") {
                            alert(paymentConfiguration.error_message);
                        } else {
                            let self = this;
                            let checkoutOptions = {
                                checkoutKey: paymentConfiguration.checkoutKey,
                                paymentId: paymentConfiguration.paymentId,
                                containerId: "nets-placeholder",
                                language: paymentConfiguration.language
                            };
                            self.dibsPayment = new Dibs.Checkout(checkoutOptions);
                            if (self.eventsInstantiated == false) {
                                self.dibsPayment.on('payment-completed', vanillaCheckoutHandler.onCheckoutCompleteAction);
                                self.dibsPayment.on('pay-initialized', vanillaCheckoutHandler.validatePayment);
                                self.dibsPayment.on('payment-created', vanillaCheckoutHandler.validateTest);
                                setCouponCodeAction.registerSuccessCallback(vanillaCheckoutHandler.updatePayment);
                                cancelCouponCodeAction.registerSuccessCallback(vanillaCheckoutHandler.updatePayment);
                                self.eventsInstantiated = true;
                            }
                            self.hideLoader(true);
                        }
                    }.bind(this);
                    this.initPaymentConfiguration(1, callback);
                },
                getPayment: function () {
                    return this.dibsPayment;
                },
                getEmail: function () {
                    return this.dibsPayment;
                },
                initPaymentConfiguration: function (vanilla, callback) {
                    $.getJSON(
                            url.build('easycheckout/checkout/getPaymentConfiguration'),
                            {'vanilla': vanilla, 'email': quoteModel.guestEmail},
                            callback
                            );
                }
            });
            return component
        }
);
