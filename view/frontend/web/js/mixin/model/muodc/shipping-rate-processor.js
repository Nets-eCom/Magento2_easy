/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/model/shipping-save-processor/payload-extender',
    'uiRegistry',
    'vanillaCheckoutHandler'
], function (
    ko,
    quote,
    resourceUrlManager,
    storage,
    paymentService,
    methodConverter,
    errorProcessor,
    fullScreenLoader,
    selectBillingAddressAction,
    payloadExtender,
    uiRegistry,
    vanillaCheckoutHandler
) {
    'use strict';

    return {
        /**
         * @return {jQuery.Deferred}
         */
        saveShippingInformation: function () {
            var payload;

            if (!quote.billingAddress() && quote.shippingAddress().canUseForBilling()) {
                selectBillingAddressAction(quote.shippingAddress());
            }

            payload = {
                addressInformation: {
                    'shipping_address': quote.shippingAddress(),
                    'billing_address': quote.billingAddress(),
                    'shipping_method_code': quote.shippingMethod()['method_code'],
                    'shipping_carrier_code': quote.shippingMethod()['carrier_code']
                }
            };

            payloadExtender(payload);

            return storage.post(
                resourceUrlManager.getUrlForSetShippingInformation(quote),
                JSON.stringify(payload)
            ).done(
                function (response) {
                    quote.setTotals(response.totals);
                    paymentService.setPaymentMethods(methodConverter(response['payment_methods']));

                    // Update DIBS iframe in overlay mode if exists
                    if (null !== document.getElementById('nets-popup-iframe')) {
                        document.getElementById('nets-popup-iframe').src += '';
                    }

                    var dibsCheckout = uiRegistry.get('nwtdibsCheckout');
                    if (dibsCheckout) {
                        dibsCheckout.initializeNewPayment();
                    }

                    if (window.dibs_msuodc_enabled && dibsCheckout) {
                        dibsCheckout._ajaxSubmit(
                            window.checkoutConfig.saveUdcShippingMethodUrl, {
                                shipping_method: 'msuodc_msuodc_widget'
                            }
                        );
                    }
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response);
                    fullScreenLoader.stopLoader();
                }
            );
        }
    };
});
