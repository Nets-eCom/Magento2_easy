define([
        'jquery',
        'ko',
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Catalog/js/price-utils',
        'mage/translate'],
    function($, ko, Component, quote, checkoutData, customerData, totals, abstractTotal, priceUtils, $t){

        return Component.extend({
            defaults: {
                displayMode: window.checkoutConfig.reviewShippingDisplayMode,
                template: 'Nexi_Checkout/checkout/summary/recurring_total'
            },

            getRecurringTotalsText: function (){
                return $t('Recurring Payment');
            },

            isRecurringScheduled: function (){
                return window.checkoutConfig.isRecurringScheduled;
            },

            getRecurringSubtotal: function (){
                return priceUtils.formatPrice(window.checkoutConfig.recurringSubtotal);
            },

            getRecurringShipping: function (){
                return priceUtils.formatPrice(totals.totals()['shipping_amount']);
            },

            getRecurringTotal: function (){
                return priceUtils.formatPrice(
                    window.checkoutConfig.recurringSubtotal + totals.totals()['shipping_amount']
                );
            }
        });
    });
