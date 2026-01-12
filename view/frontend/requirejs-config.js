/**
 * Copyright © Nexi. All rights reserved.
 */
var config = {
    map: {
        '*': {
            'Magento_Checkout/js/model/shipping-save-processor/default': 'Nexi_Checkout/js/model/shipping-save-processor/default',
            'nexi-success-page': 'Nexi_Checkout/js/success-page'
        },
    },
    config: {
        mixins: {
            'Magento_Checkout/js/checkout-data': {
                'Nexi_Checkout/js/model/checkout-data-ext': true
            }
        }
    }
};
