define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (quote, urlBuilder, storage, url, errorProcessor, customer, fullScreenLoader) {
        'use strict';

        return function (paymentMethod, afterSuccess) {
            var serviceUrl, payload;


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

            fullScreenLoader.startLoader();

            return storage.post(
                serviceUrl,
                JSON.stringify(payload)
            ).done(
                function () {
                    fullScreenLoader.stopLoader(true);

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
