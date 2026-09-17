define([
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, fullScreenLoader) {
    'use strict';

    return {
        loadSdk: function (sdkUrl) {
            return new Promise((resolve, reject) => {
                if (window.Dibs?.Checkout) {
                    resolve();
                    return;
                }

                fullScreenLoader.startLoader();

                const script = document.createElement('script');
                script.src = sdkUrl;
                script.async = true;
                script.onload = () => {
                    fullScreenLoader.stopLoader();
                    resolve();
                };
                script.onerror = () => {
                    fullScreenLoader.stopLoader();
                    reject(new Error('Failed to load Nexi Checkout SDK'));
                };

                document.head.appendChild(script);
            });
        }
    };
});
