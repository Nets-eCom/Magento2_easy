var config = {
    map: {
        '*': {
            dibsEasyCheckout: 'Dibs_EasyCheckout/js/checkout',
            'Magento_Checkout/js/model/shipping-save-processor/default': 'Dibs_EasyCheckout/js/mixin/model/muodc/shipping-rate-processor',
            checkIframe: 'Dibs_EasyCheckout/js/action/check-iframe',
            isOverlayIframe: 'Dibs_EasyCheckout/js/action/is-overlay-iframe'
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
