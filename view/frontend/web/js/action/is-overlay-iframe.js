define([],
    function () {
        'use strict';

        var isOverlayIframe = function(config, element)
        {
            window.overlayIframe = config.isOverlay;
        };

        return isOverlayIframe;
    });