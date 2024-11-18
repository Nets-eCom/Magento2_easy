/*browser:true*/
/*global define*/
define(
        [
            'jquery',
            'Magento_Checkout/js/view/payment/default',
            'mage/url',
            'Dibs_EasyCheckout/js/action/set-payment-information',
            'Magento_Checkout/js/model/payment/additional-validators',
            'uiRegistry',
            'vanillaCheckoutHandler',
            'Magento_SalesRule/js/action/set-coupon-code',
            'Magento_SalesRule/js/action/cancel-coupon',
            'Magento_Checkout/js/model/quote',
        ],
        function (
                $,
                Component,
                url,
                dibsSetPaymentInformation,
                additionalValidators,
                uiRegistry,
                vanillaCheckoutHandler,
                setCouponCodeAction,
                cancelCouponCodeAction,
                quoteModel,
                ) {
            'use strict';
            var agreementsConfig = window.checkoutConfig.checkoutAgreements;
            var agreementsCheck = agreementsConfig.isEnabled;
            var prevAddress;

            var component = Component.extend({
                defaults: {
                    template: 'Dibs_EasyCheckout/payment/checkout',
                    iframeUrl: null,
                    hideLoader: false
                },
                continueToDibsRedirect: function () {
                    const billingAddress = uiRegistry.get('checkout.steps.billing-step.payment.payments-list.'+this.getCode()+'-form');
                    if (billingAddress) {
                        billingAddress.updateAddress();
                    }

                    if (additionalValidators.validate()) {
                        const jqxhr = dibsSetPaymentInformation(
                            this.getCode(),
                            agreementsCheck,
                            this.messageContainer
                        );

                        jqxhr.done(function () {
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
                    }
                },
                continueToDibs: function () {
                    dibsSetPaymentInformation(
                        this.getCode(),
                        agreementsCheck,
                        this.messageContainer
                    );

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
                        this.subscribeBillingAddressChange();
                    }

                    uiRegistry.set('nwtdibsCheckout', this);
                },
                initializeNewPayment: function () {
                    if (!window.isVanillaEmbeded) {
                        return;
                    }
                    let placeholder = document.getElementById('nets-placeholder');
                    if (placeholder) {
                        placeholder.innerHTML = "";
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
                            self.dibsPayment.ctrlkey = paymentConfiguration.ctrlkey;
                            self.dibsPayment.on('pay-initialized', vanillaCheckoutHandler.saveOrder);
                            self.dibsPayment.on('payment-completed', vanillaCheckoutHandler.onCheckoutCompleteAction);

                            setCouponCodeAction.registerSuccessCallback(vanillaCheckoutHandler.updatePayment);
                            cancelCouponCodeAction.registerSuccessCallback(vanillaCheckoutHandler.updatePayment);

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
                },
                subscribeBillingAddressChange: function () {
                    quoteModel.billingAddress.subscribe(function (newAddress) {
                        if (!window.isVanillaEmbeded) {
                            return;
                        }

                        if (newAddress && prevAddress && newAddress.getKey() !== prevAddress.getKey()) {
                            prevAddress = newAddress;
                            if (newAddress) {
                                this.initializeNewPayment();
                            }
                        }

                        if (!prevAddress) {
                            prevAddress = newAddress;
                        }
                    }, this);
                }
            });
            return component
        }
);
