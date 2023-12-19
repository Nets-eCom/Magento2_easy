define(
    [
        'jquery',
        'Magento_Checkout/js/action/set-payment-information',
    ],
    function ($, setPaymentInformationAction) {
        'use strict';

        return function (paymentMethod, agreementsCheck, messageContainer) {
            var agreementForm = $('.payment-method._active div[data-role=checkout-agreements] input');
            var agreementData = agreementForm.serializeArray();
            var agreementIds = [];

            let paymentMethodPayload = {
                method: paymentMethod
            };

            if (agreementsCheck) {
                agreementData.forEach(function (item) {
                    agreementIds.push(item.value);
                });
                paymentMethodPayload.extension_attributes = {
                    "agreement_ids": agreementIds
                };
            }

            const jqxhr = setPaymentInformationAction(
                messageContainer,
                paymentMethodPayload
            );

            return jqxhr;
        };
    }
);
