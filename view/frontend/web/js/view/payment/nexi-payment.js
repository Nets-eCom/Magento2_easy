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
                type: 'nexi',
                component: 'Nexi_Checkout/js/view/payment/method-renderer/nexi-method'
            }
        );

        return Component.extend({});
    }
);
