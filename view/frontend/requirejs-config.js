var config = {
    map: {
        '*': {
            dibsEasyCheckout: 'Dibs_EasyCheckout/js/checkout',
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

if (window.dibs_msuodc_enabled) {
    config.config = {
        mixins: {
            'Dibs_EasyCheckout/js/checkout': {
                'Dibs_EasyCheckout/js/view/mixin/easycheckout/checkout': true
            }
        }
    };
}
