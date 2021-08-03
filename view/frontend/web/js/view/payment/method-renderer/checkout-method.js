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
        'Magento_Ui/js/modal/mageAlert'
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
        mageAlert
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
                    let callback = function(paymentConfiguration) {
                        $.ajax({
                            url: url.build('easycheckout/order/saveOrder'),
                            data: {pid: paymentConfiguration.paymentId},
                            type: 'POST',
                            dataType: 'json',
                            beforeSend: function() {
                                fullScreenLoader.startLoader();
                            },
                            success: function(response) {
                                if(response.error) {
                                    mageAlert({content: $.mage.__(response.messages)});
                                    return false;
                                }
                                if(response.redirectTo) {
                                    $.mage.redirect(response.redirectTo);
                                }
                                $.mage.redirect(paymentConfiguration.checkoutUrl);
                            },
                            error: function() {
                                mageAlert({content: $.mage.__('Sorry, something went wrong. Please try again later.')});
                            },
                            complete: function() {
                                fullScreenLoader.stopLoader()
                            }
                        });
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
                if (! window.isVanillaEmbeded) {
                    return;
                }
                let placehplder = document.getElementById('nets-placeholder');
                if (placehplder) {
                    placehplder.innerHTML = "";
                }

                let callback = function(paymentConfiguration) {
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
                        setCouponCodeAction.registerSuccessCallback(vanillaCheckoutHandler.updatePayment);
                        cancelCouponCodeAction.registerSuccessCallback(vanillaCheckoutHandler.updatePayment);
                        self.eventsInstantiated = true;
                    }

                    self.hideLoader(true);
                }.bind(this);
                this.initPaymentConfiguration(1, callback);
            },
            getPayment: function () {
                return this.dibsPayment;
            },
            initPaymentConfiguration: function(vanilla, callback) {
                $.getJSON(
                    url.build('easycheckout/checkout/getPaymentConfiguration'),
                    {'vanilla':vanilla},
                    callback
                ).fail( function() {
                     mageAlert({content: 'Payment init fail'})
                });
            }
        });

        return component


    }
);
