var config = {
    map: {
        '*': {
            dibsEasyCheckout: 'Dibs_EasyCheckout/js/checkout',
            'Magento_Checkout/js/model/shipping-save-processor/default': 'Dibs_EasyCheckout/js/mixin/model/muodc/shipping-rate-processor',
            checkIframe: 'Dibs_EasyCheckout/js/action/check-iframe',
            vanillaCheckoutHandler: 'Dibs_EasyCheckout/js/action/checkout-handler',
            checkoutMode: 'Dibs_EasyCheckout/js/action/checkoutMode',
            successActions: 'Dibs_EasyCheckout/js/action/success-actions'
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
