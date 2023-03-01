define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_CheckoutAgreements/js/view/checkout-agreements'
    ],
    function ($,quote, urlBuilder, storage, url, errorProcessor, customer, fullScreenLoader) {
        'use strict';

        return function (paymentMethod, agreementsCheck, afterSuccess) {
            var serviceUrl, payload;
            var agreementForm = $('.payment-method._active div[data-role=checkout-agreements] input');
            var agreementData = agreementForm.serializeArray();
            var agreementIds = [];

            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/set-payment-information', {
                    quoteId: quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId(),
                    email: quote.guestEmail,
                    paymentMethod: {
                        method: paymentMethod,
                    },
                    billingAddress: quote.billingAddress()
                };
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/set-payment-information', {});
                payload = {
                    cartId: quote.getQuoteId(),
                    paymentMethod: {
                        method: paymentMethod,
                    },
                    billingAddress: quote.billingAddress()
                };
            }

            if (agreementsCheck) {
                agreementData.forEach(function (item) {
                    agreementIds.push(item.value);
                });
                payload.paymentMethod.extension_attributes = {
                    "agreement_ids": agreementIds
                };
            }
            fullScreenLoader.startLoader();

            return storage.post(
                serviceUrl,
                JSON.stringify(payload)
            ).done(
                function () {

                    if (typeof afterSuccess === 'function') {
                        afterSuccess();
                    }

                }
            ).fail(
                function (response) {
                    fullScreenLoader.stopLoader(true);
                }
            );
        };
    }
);
