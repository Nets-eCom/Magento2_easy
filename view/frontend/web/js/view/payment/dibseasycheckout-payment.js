/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'dibseasycheckout',
                component: 'Dibs_EasyCheckout/js/view/payment/method-renderer/checkout-method'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);