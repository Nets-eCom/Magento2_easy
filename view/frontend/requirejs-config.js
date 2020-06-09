var config = {
    map: {
        '*': {
            dibsEasyCheckout: 'Dibs_EasyCheckout/js/checkout',
            'Magento_Checkout/js/model/shipping-save-processor/default': 'Dibs_EasyCheckout/js/mixin/model/muodc/shipping-rate-processor'
        }
    },
    paths: {
        slick: 'Dibs_EasyCheckout/js/lib/slick.min'
    },
    shim: {
        slick: {
            deps: ['jquery']
        }
    }
};
