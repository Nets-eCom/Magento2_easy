/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Dibs_EasyCheckout/js/action/before-order',
        'uiRegistry',
        'vanillaCheckoutHandler',
        'Magento_SalesRule/js/action/set-coupon-code',
        'Magento_SalesRule/js/action/cancel-coupon'
    ],
    function (
        $,
        Component,
        url,
        dibs,
        uiRegistry,
        vanillaCheckoutHandler,
        setCouponCodeAction,
        cancelCouponCodeAction
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
                    $.mage.redirect(url.build('easycheckout') + '?checkRedirect=1');
                });
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

                var self = this;
                $.getJSON(url.build('easycheckout/checkout/getPaymentConfiguration'), {'vanilla':1}, function (paymentConfiguration) {
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
                })
                .fail(function() {
                    alert("Payment init fail");
                })
            },
            getPayment: function () {
                return this.dibsPayment;
            },
        });

        return component


    }
);
