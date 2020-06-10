define(
['jquery', 'Magento_Checkout/js/model/quote'],
function ($, quoteModel) {
    'use strict';

    return function (target) {
        if (window.dibs_msuodc_enabled === undefined) {
            return target;
        }

        $.widget('mage.nwtdibsCheckout', target, {
            _loadShippingMethod: function () {
                var formData = $(this.options.shippingMethodLoaderSelector)
                    .serializeArray()
                    .reduce(function(obj, item) {
                    obj[item.name] = item.value;
                    return obj;
                }, {});

                if (formData && formData.postal !== undefined) {
                    var quoteAddress = quoteModel.shippingAddress();
                    quoteAddress.postcode = formData.postal;
                    quoteAddress.countryId = formData.country_id;
                    quoteModel.shippingAddress(quoteAddress)
                }

                return false;
            }
        });

        return $.mage.nwtdibsCheckout;
    };
});
