define(
    ['Magento_Customer/js/customer-data'],
    function (customerData) {
        'use strict';

        var successActions = function()
        {
            let sections = ['cart'];
            customerData.invalidate(sections);
            customerData.reload(sections, true);
            inIframe();

            function inIframe ()
            {
                if (window.self !== window.top) {
                    // If the page it's in an iframe, replace the html of the parent document with the iframe's html
                    jQuery(window.parent.document.getElementsByTagName('html')).html(jQuery('html'));
                }
            }
        };

        return successActions;
    });