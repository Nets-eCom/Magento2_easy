define([
    'Magento_Customer/js/customer-data'
], function (
    storage
) {
    'use strict';

    return function (checkoutData) {

        var cacheKey = 'checkout-data',

            /**
             * @param {Object} data
             */
            saveData = function (data) {
                storage.set(cacheKey, data);
            },

            /**
             * @return {Object}
             */
            getData = function () {
                //Makes sure that checkout storage is initiated (any method can be used)
                checkoutData.getSelectedShippingAddress();

                return storage.get(cacheKey)();
            };

        /**
         * Save the pickup address in persistence storage
         *
         * @param {Object} data
         */
        checkoutData.setNexiSubselection = function (data) {
            var obj = getData();

            obj.nexiSubselection = data;
            saveData(obj);
        };

        /**
         * Get the pickup address from persistence storage
         *
         * @return {*}
         */
        checkoutData.getNexiSubselection = function () {
            return getData().nexiSubselection || null;
        };

        return checkoutData;
    };
});
