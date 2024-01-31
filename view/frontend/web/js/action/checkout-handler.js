define([
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'uiRegistry',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/view/messages',
    'Magento_Ui/js/model/messageList',
    'Magento_Ui/js/modal/alert'
    ], function ($, checkoutLoader, uiRegistry, mageurl, customerData, messages, messageList, netsAlert) {
        'use strict';

        return {
            onCheckoutCompleteAction: function (response) {
                $.ajax({
                    type: "POST",
                    url: mageurl.build("easycheckout/order/confirmOrder/"),
                    data: {pid: response.paymentId},
                    dataType: 'json',
                    beforeSend: function () {
                        checkoutLoader.startLoader();
                    },
                    complete: function () {
                        checkoutLoader.stopLoader();
                    },
                    success: function (response) {
                        if (response.error && response.messages) {
                            alert($.mage.__(response.messages));
                            return false;
                        }

                        let sections = ['cart'];
                        customerData.invalidate(sections);
                        customerData.reload(sections, true);

                        if (response.redirectTo) {
                            $.mage.redirect(mageurl.build(response.redirectTo));
                        }
                    },
                    error: function(_jqXhr) {
                        alert($.mage.__('Sorry, there has been an error processing your order. Please contact customer support.'));
                    }
                });
            },
            updatePayment: function() {
                $.ajax({
                    type: "POST",
                    context: this,
                    url: mageurl.build("easycheckout/order/cart/"),
                    complete: function () {
                        let payment = uiRegistry.get('nwtdibsCheckout').getPayment();
                        payment.freezeCheckout();
                        payment.thawCheckout();
                    }
                });
            },
            validateTest: function(paymentId) { 
                //alert('pay created');
            },
            saveOrder: function(paymentId) {
                //return this.sendPaymentOrderFinalizedEvent(true);
                const ctrlkey = this.ctrlkey;
                $.ajax({
                    url: mageurl.build("easycheckout/order/EmbeddedSaveOrder")+'?ctrlkey='+ctrlkey,
                    type: "POST",
                    context: this,
                    data: {pid: paymentId},
                    dataType: 'json',
                    beforeSend: function () {
                        checkoutLoader.startLoader();
                    },
                    complete: function () {
                        checkoutLoader.stopLoader();
                    },
                    success: function (response) {
                        if ($.type(response) === 'object' && !$.isEmptyObject(response) && !response.reload) {
                            this.sendPaymentOrderFinalizedEvent(true);
                            if (response.messages) {
                                alert(jQuery.mage.__(response.messages));
                            }
                        } else {
                            netsAlert({
                                title: 'Error',
                                content: response.messages,
                                clickableOverlay: false,
                                responsive: true,
                                innerScroll: true,
                                closed: function () {
                                    $.mage.redirect(mageurl.build("checkout/cart"));
                                },
                                buttons: [{
                                    text: $.mage.__('Close'),
                                    class: 'modal-close',
                                    click: function (){
                                        this.closeModal();
                                    }
                                }]
                                /*actions: {
                                    always: function () {

                                        alert("reload here");

                                    }
                                }*/
                            });
                            checkoutLoader.stopLoader();
                            this.sendPaymentOrderFinalizedEvent(false);
                            messageList.addErrorMessage({
                               message: response.messages
                            });

                            //alert(jQuery.mage.__(response.messages));
                        }
                    },
                    error: function(data) {
                        // tell dibs not to finish order!
                        netsAlert({
                                title: 'Error',
                                content: "Error happened, please try again",
                                clickableOverlay: false,
                                responsive: true,
                                innerScroll: true,
                                closed: function () {
                                    $.mage.redirect(mageurl.build("checkout/cart"));
                                },
                                buttons: [{
                                    text: $.mage.__('Close'),
                                    class: 'modal-close',
                                    click: function (){
                                        this.closeModal();
                                    }
                                }]
                                /*actions: {
                                    always: function () {

                                        alert("reload here");

                                    }
                                }*/
                            });
                        this.sendPaymentOrderFinalizedEvent(false);
                        //alert(jQuery.mage.__('Sorry, something went wrong. Please try again later.'));
                    }
                });
            }
        };
    }
);
