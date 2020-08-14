define([],
    function () {
        'use strict';

        var checkIframe = function()
        {
            inIframe();

            function inIframe ()
            {
                if (window.self !== window.top) {
                    // If the page it's in an iframe, replace the html of the parent document with the iframe's html
                    jQuery(window.parent.document.getElementsByTagName('html')).html(jQuery('html'));
                }
            }
        };

        return checkIframe;
    });