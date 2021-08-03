define([
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'uiRegistry',
    'mage/url',
    'Magento_Ui/js/modal/alert'
    ], function ($, checkoutLoader, uiRegistry, mageurl, mageAlert) {
        'use strict';

        return {
            onCheckoutCompleteAction: function (response) {
                $.ajax({
                    type: "POST",
                    url: mageurl.build("easycheckout/order/confirmOrder/"),
                    data: {pid: response.paymentId},
                    dataType: 'json',
                    beforeSend: function () {
                        checkoutLoader.startLoader();
                    },
                    complete: function () {
                        checkoutLoader.stopLoader();
                    },
                    success: function (response) {
                        if (response.error && response.messages) {
                            mageAlert({content: $.mage.__(response.messages)});
                            return false;
                        }

                        if (response.redirectTo) {
                            $.mage.redirect(mageurl.build(response.redirectTo));
                        }
                    },
                    error: function(_jqXhr) {
                        mageAlert({content: $.mage.__('Sorry, there has been an error processing your order. Please contact customer support.')});
                    }
                });
            },
            updatePayment: function() {
                $.ajax({
                    type: "POST",
                    context: this,
                    url: mageurl.build("easycheckout/order/cart/"),
                    complete: function () {
                        let payment = uiRegistry.get('nwtdibsCheckout').getPayment();
                        payment.freezeCheckout();
                        payment.thawCheckout();
                    }
                });
            },
            validatePayment: function(paymentId) {
                $.ajax({
                    url: mageurl.build("easycheckout/order/SaveOrder"),
                    type: "POST",
                    context: this,
                    data: {pid: paymentId},
                    dataType: 'json',
                    beforeSend: function () {
                        checkoutLoader.startLoader();
                    },
                    complete: function () {
                        checkoutLoader.stopLoader();
                    },
                    success: function (response) {
                        if ($.type(response) === 'object' && !$.isEmptyObject(response)) {
                            this.sendPaymentOrderFinalizedEvent(!response.error);
                            if (response.messages) {
                                mageAlert({content: $.mage.__(response.messages)});
                            }
                        } else {
                            checkoutLoader.stopLoader();
                            this.sendPaymentOrderFinalizedEvent(false);
                            mageAlert({content: $.mage.__('Sorry, something went wrong. Please try again later.')});
                        }
                    },
                    error: function(data) {
                        // tell dibs not to finish order!
                        this.sendPaymentOrderFinalizedEvent(false);
                        mageAlert({content: $.mage.__('Sorry, something went wrong. Please try again later.')});
                    }
                });
            }
        };
    }
);