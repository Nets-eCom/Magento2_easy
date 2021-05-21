define([
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'uiRegistry'
    ], function ($, checkoutLoader, uiRegistry) {
        'use strict';

        return {
            onCheckoutCompleteAction: function (response) {
                $.ajax({
                    url: BASE_URL + "easycheckout/order/SaveOrder/pid/" + response.paymentId,
                    type: "POST",
                    context: this,
                    data: "",
                    dataType: 'json',
                    beforeSend: function () {
                        checkoutLoader.startLoader();
                    },
                    complete: function () {
                        checkoutLoader.startLoader();
                    },
                    success: function (response) {
                        if (response.redirectTo) {
                            window.location.href = response.redirectTo;
                        }
                    },
                    error: function(data) {
                        console.log(response); alert('Payment ERROR')
                    }
                });
            },
            updatePayment: function() {
                $.ajax({
                    type: "POST",
                    context: this,
                    url: BASE_URL + "easycheckout/order/cart/",
                    complete: function () {
                        let payment = uiRegistry.get('nwtdibsCheckout').getPayment();
                        payment.freezeCheckout();
                        payment.thawCheckout();
                    }
                });
            },
            validatePayment: function(response) {
                $.ajax({
                    url: BASE_URL + "easycheckout/order/ValidateOrder",
                    type: "POST",
                    context: this,
                    data: "",
                    dataType: 'json',
                    beforeSend: function () {
                        checkoutLoader.startLoader();
                    },
                    complete: function () {
                        checkoutLoader.stopLoader();
                    },
                    success: function (response) {
                        if (jQuery.type(response) === 'object' && !jQuery.isEmptyObject(response)) {
                            this.sendPaymentOrderFinalizedEvent(!response.error);
                            if (response.messages) {
                                alert({
                                    content: jQuery.mage.__(response.messages)
                                });
                            }
                        } else {
                            checkoutLoader.stopLoader();
                            this.sendPaymentOrderFinalizedEvent(false);
                            alert({content: jQuery.mage.__('Sorry, something went wrong. Please try again later.')});
                        }
                    },
                    error: function(data) {
                        // tell dibs not to finish order!
                        this.sendPaymentOrderFinalizedEvent(false);
                        alert({
                            content: jQuery.mage.__('Sorry, something went wrong. Please try again later.')
                        });
                    }
                });
            }
        };
    }
);