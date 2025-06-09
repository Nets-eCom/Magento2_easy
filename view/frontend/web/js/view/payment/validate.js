define([
  "mage/storage",
  "Magento_Checkout/js/model/url-builder",
  "Magento_Checkout/js/model/quote",
  "Magento_Checkout/js/model/full-screen-loader",
  "Magento_Checkout/js/model/error-processor",
  "Magento_Customer/js/model/customer",
], function (
  storage,
  urlBuilder,
  quote,
  fullScreenLoader,
  errorProcessor,
  customer
) {
  "use strict";

  return function () {
    const payload = {
      cartId: quote.getQuoteId()
    };

    const serviceUrl = customer.isLoggedIn()
      ? urlBuilder.createUrl("/nexi/carts/mine/payment-validate", {})
      : urlBuilder.createUrl("/nexi/guest-carts/:quoteId/payment-validate", {
          quoteId: quote.getQuoteId(),
        });

    fullScreenLoader.startLoader();

    return new Promise((resolve, reject) => {
      storage
        .post(serviceUrl, JSON.stringify(payload))
        .done(function (response) {
          resolve(JSON.parse(response));
        })
        .fail(
          function (response) {
            errorProcessor.process(response, this.messageContainer);
            let redirectURL = response.getResponseHeader("errorRedirectAction");

            if (redirectURL) {
              setTimeout(function () {
                errorProcessor.redirectTo(redirectURL);
              }, 3000);
            }
            reject(response);
          }.bind(this)
        )
        .always(function () {
          fullScreenLoader.stopLoader();
        });
    });
  };
});
