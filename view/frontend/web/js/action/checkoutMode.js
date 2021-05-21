define([],
    function () {
        'use strict';

        var checkoutMode = function(config, element)
        {
            window.overlayIframe = config.isOverlay == "1";
            window.isVanillaEmbeded = config.isVanillaEmbeded == "1";
        };

        return checkoutMode;
    });