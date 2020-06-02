/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define([
    "jquery",
    'Magento_Ui/js/modal/alert',
    'Magento_Checkout/js/model/quote',
    "jquery/ui",
    "mage/translate",
    "mage/mage",
    "mage/validation"
], function (jQuery, alert, quoteModel) {
    "use strict";
    jQuery.widget('mage.nwtdibsCheckout', {
        options: {
            shippingMethodFormSelector: '#shipping-method-form',
            shippingMethodLoaderSelector: '#shipping-method-loader',
            shippingMethodsListSelector: '#dibs-easy-checkout_shipping_method',
            shippingMethodCheckBoxHolder: '.dibs-easy-checkout-radio-row',
            getShippingMethodButton: '#shipping-method-button',
            newsletterFormSelector: '#dibs-easy-checkout-newsletter-form',
            couponFormSelector: '#discount-coupon-form',
            cartContainerSelector: '#details-table',
            waitLoadingContainer: '#review-please-wait',
            ctrlkey: null,
            ctrlcookie: 'dibs-easy-checkoutCartCtrlKey',
            ctrkeyCheck: true,
            dibsCountryChange: false,
            dibsShippingChange: false,
            hasInitFlag: false,
            shippingAjaxInProgress: false,
            scrollTrigger: '.go-to.dibs-btn',
            scrollTarget: '#dibsOrder'
        },
        _create: function () {
            jQuery.mage.cookies.set(this.options.ctrlcookie, this.options.ctrlkey);
            this._checkIfCartWasUpdated();
            this.hidePaymentAndIframe();
            this._bindEvents();
            this.uiManipulate();
            this.scrollToPayment();
            this.checkShippingMethod();
        },

        _checkIfCartWasUpdated: function () {
            var checkIfCartWasUpdated = setInterval((function () {
                if (!this.options.ctrkeyCheck) {
                    return true;
                }
                var ctrlkey = jQuery.mage.cookies.get(this.options.ctrlcookie);
                if (ctrlkey && ctrlkey !== this.options.ctrlkey) {

                    // clear the interval
                    clearInterval(checkIfCartWasUpdated);


                    // msg popup, then reload!
                    jQuery(this.options.waitLoadingContainer).html('<span class="error">Cart was updated, please wait for the Checkout to reload...</span>').show();
                    window.location.reload();

                }
            }).bind(this), 1000);
        },

        _bindCartAjax: function () {
            var cart = this.options.cartContainerSelector;
            var inputs = jQuery(cart).find('.ajax-qty-change');
            var _this = this;
            jQuery.each(inputs, function (i, input) {
                var inputQty = jQuery(input);
                var data_submit_url = inputQty.data('cart-url-submit');
                var data_refresh_url = inputQty.data('cart-url-update');
                var data_remove_url = inputQty.data('cart-url-remove');
                var increment = inputQty.siblings('.input-number-increment');
                var decrement = inputQty.siblings('.input-number-decrement');
                var remove = inputQty.parent().siblings('.remove-product');
                var prevVal = false;

                if (increment.data('binded')) return;
                if (decrement.data('binded')) return;
                if (remove.data('binded')) return;

                increment.data('binded', true);
                decrement.data('binded', true);
                remove.data('binded', true);

                increment.on('click', function () {
                    inputQty.val(parseInt(inputQty.val()) + 1);
                    if (typeof ajaxActionTriggerTimeout !== "undefined") {
                        clearTimeout(ajaxActionTriggerTimeout);
                    }
                    window.ajaxActionTriggerTimeout = setTimeout(function () {
                        inputQty.trigger('change');
                    }, 1000);
                });
                decrement.on('click', function () {
                    var v = parseInt(inputQty.val());
                    if (v < 2) return;
                    inputQty.val(v - 1);
                    if (typeof ajaxActionTriggerTimeout !== "undefined") {
                        clearTimeout(ajaxActionTriggerTimeout);
                    }
                    window.ajaxActionTriggerTimeout = setTimeout(function () {
                        inputQty.trigger('change');
                    }, 1000);
                });
                remove.on('click', function () {
                    var c = confirm(jQuery.mage.__('Are you sure you want to remove this?'));
                    if (c == true) {
                        var data = {
                            item_id: inputQty.data('cart-product-id'),
                            form_key: inputQty.data('cart-form-key')
                        };
                        jQuery.ajax({
                            type: "POST",
                            url: data_remove_url,
                            data: data,
                            beforeSend: function () {
                                _this._ajaxBeforeSend();
                            },
                            complete: function () {
                            },
                            success: function (data) {
                                if (!data.success) {
                                    if (data.error_message) {
                                        confirm(data.error_message);
                                    }
                                }
                                _this._ajaxSubmit(data_refresh_url);
                            }
                        });
                    }
                });
                inputQty.on('keypress', function (e) {
                    if (e.keyCode == "1") {
                        inputQty.trigger('change');
                        return false;
                    }
                    ;
                }).on('focus', function () {
                    prevVal = jQuery(inputQty).val();
                }).on('change', function () {
                    var data = {
                        item_id: inputQty.data('cart-product-id'),
                        form_key: inputQty.data('cart-form-key'),
                        item_qty: inputQty.val()
                    };
                    if (data.item_qty == 0) {
                        jQuery(inputQty).val(prevVal);
                        return false;
                    }
                    jQuery.ajax({
                        type: "POST",
                        url: data_submit_url,
                        data: data,
                        beforeSend: function () {
                            _this._ajaxBeforeSend();
                        },
                        complete: function () {
                        },
                        success: function (data) {
                            if (!data.success) {
                                if (data.error_message) {
                                    confirm(data.error_message);
                                }
                            }
                            _this._ajaxSubmit(data_refresh_url);
                        }
                    });
                });
            });
        },

        _bindEvents: function (block) {
            //$blocks = ['shipping_method','cart','coupon','messages', 'dibs','newsletter'];

            block = block ? block : null;
            if (!block || block == 'shipping') {
                jQuery(this.options.shippingMethodLoaderSelector).on('submit', jQuery.proxy(this._loadShippingMethod, this));
                this.checkShippingMethod();
            }
            if (!block || block == 'shipping_method') {
                jQuery(this.options.shippingMethodFormSelector).find('input[type=radio]').on('change', jQuery.proxy(this._changeShippingMethod, this));
            }
            if (!block || block == 'newsletter') {
                jQuery(this.options.newsletterFormSelector).find('input[type=checkbox]').on('change', jQuery.proxy(this._changeSubscriptionStatus, this));
            }
            if (!block || block == 'cart') {
                this._bindCartAjax();
            }
            if (!block || block == 'coupon') {
                jQuery(this.options.couponFormSelector).on('submit', jQuery.proxy(this._applyCoupon, this));
                this.checkValueOfInputs(jQuery(this.options.couponFormSelector));
            }

            if (!block || block == 'dibs') {
                this.dibsApiChanges();
            }

        },

        checkValueOfInputs: function (form) {
            var checkValue = function (elem) {
                if (jQuery(elem).val()) {
                    form.find('.primary').show();
                } else {
                    form.find('.primary').hide();
                }
            }
            var field = jQuery(form).find('.dibs-easy-checkout-show-on-focus').get(0);
            jQuery(field).on("keyup", function () {
                checkValue(this)
            });
            jQuery(field).on("change", function () {
                checkValue(this)
            });
        },


        /**
         * show ajax loader
         */
        _ajaxBeforeSend: function () {
            this.options.ctrkeyCheck = false;
            this._hideDibsCheckout()
            jQuery(this.options.waitLoadingContainer).show();
        },

        /**
         * hide ajax loader
         */
        _ajaxComplete: function (dontHidePayment) {
            this._showDibsCheckout()
            jQuery(this.options.waitLoadingContainer).hide();
            this.checkShippingMethod();
            this.dibsApiChanges();
        },

        _showDibsCheckout: function() {
            if (window._dibsCheckout) {
                try {
                    window._dibsCheckout.thawCheckout();
                } catch (err) {
                }
            }
        },

        _hideDibsCheckout: function() {
            if (window._dibsCheckout) {
                try {
                    window._dibsCheckout.freezeCheckout();
                } catch (err) {
                }
            }
        },

        _changeShippingMethod: function () {
            if(!jQuery(this.options.shippingMethodFormSelector).serialize()) return; //no option selected
            this._ajaxFormSubmit(jQuery(this.options.shippingMethodFormSelector));
            jQuery(this.options.scrollTrigger).show();
        },

        _loadShippingMethod: function () {
            if (window.dibs_msuodc_enabled) {
                // We bind new callback, because we need to reload shipping methods
                if (
                    typeof(window.msuodc_widget_widget.configuration.resultCallback) == "function"
                    && typeof(window.msuodc_widget_widget.nwtWrapperApplied) == "undefined"
                ) {
                    window.msuodc_widget_widget.nwtWrapperApplied = true;
                    var msuodcCallback = window.msuodc_widget_widget.configuration.resultCallback;

                    window.msuodc_widget_widget.configuration.resultCallback = function(result) {
                        msuodcCallback(result);
                        if (result.valid) {
                            jQuery('#details-table').find('.ajax-qty-change').trigger('change');
                        }
                    };
                }

                var formData = jQuery(this.options.shippingMethodLoaderSelector)
                    .serializeArray()
                    .reduce(function(obj, item) {
                        obj[item.name] = item.value;
                        return obj;
                    }, {});

                if (formData && formData.postal !== undefined) {
                    var quoteAddress = quoteModel.shippingAddress();
                    quoteAddress.postcode = formData.postal;
                    quoteAddress.countryId = formData.country_id;
                    quoteModel.shippingAddress(quoteAddress)
                }

                return false;
            }

            this._ajaxFormSubmit(jQuery(this.options.shippingMethodLoaderSelector));
            return false;
        },

        _changeSubscriptionStatus: function () {
            this._ajaxFormSubmit(jQuery(this.options.newsletterFormSelector));
        },

        _applyCoupon: function () {
            this._ajaxFormSubmit(jQuery(this.options.couponFormSelector));
            return false;
        },


        _ajaxFormSubmit: function (form) {
            return this._ajaxSubmit(form.prop('action'), form.serialize());
        },
        /**
         * Attempt to ajax submit order
         */
        _ajaxSubmit: function (url, data, method, beforeDIBSAjax, afterDIBSAjax) {
            if (!method) method = 'post';
            var _this = this;
            if (this.options.shippingAjaxInProgress === true) {
                return false;
            }
            jQuery.ajax({
                url: url,
                type: method,
                context: this,
                data: data,
                dataType: 'json',
                beforeSend: function () {
                    _this.options.ctrkeyCheck = false;
                    _this.options.shippingAjaxInProgress = true;
                    _this._ajaxBeforeSend();
                    if (typeof beforeDIBSAjax === 'function') {
                        beforeDIBSAjax();
                    }
                },
                complete: function () {
                    _this.options.shippingAjaxInProgress = false;
                    _this._ajaxComplete();
                },
                success: function (response) {
                    if (jQuery.type(response) === 'object' && !jQuery.isEmptyObject(response)) {

                        if (response.reload || response.redirect) {
                            this.loadWaiting = false; //prevent that resetLoadWaiting hiding loader
                            if (response.messages) {
                                //alert({content: response.messages});
                                jQuery(this.options.waitLoadingContainer).html('<span class="error">' + response.messages + ' Reloading...</span>');
                            } else {
                                jQuery(this.options.waitLoadingContainer).html('<span class="error">Reloading...</span>');
                            }

                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.reload();
                            }
                            return true;
                        } //end redirect   

                        //ctlKeyy Cookie
                        if (response.ctrlkey) {
                            _this.options.ctrlkey = response.ctrlkey;
                            jQuery.mage.cookies.set(_this.options.ctrlcookie, response.ctrlkey);
                        }


                        if (response.updates) {

                            var blocks = response.updates;
                            var div = null;

                            for (var block in blocks) {
                                if (blocks.hasOwnProperty(block)) {
                                    div = jQuery('#dibs-easy-checkout_' + block);
                                    if (div.size() > 0) {
                                        div.replaceWith(blocks[block]);
                                        this._bindEvents(block);
                                    }
                                    if (block === 'shipping_method') {
                                        jQuery(this.options.shippingMethodsListSelector).show();
                                    }
                                }

                            }
                        }

                        if (typeof afterDIBSAjax === 'function') {
                            afterDIBSAjax();
                        }

                        if (response.messages) {
                            alert({
                                content: response.messages
                            });
                        }

                    } else {
                        alert({
                            content: jQuery.mage.__('Sorry, something went wrong. Please try again (reload this page)')
                        });
                        // window.location.reload();
                    }

                    // after we loaded the new ctrlkey we now can compare the keys again!
                    _this.options.ctrkeyCheck = true;

                },
                error: function () {
                    this.options.ctrkeyCheck = true;
                    alert({
                        content: jQuery.mage.__('Sorry, something went wrong. Please try again later.')
                    });
                    //window.location.reload();
//                     this._ajaxComplete();
                }
            });
        },

        dibsApiChanges: function () {
            if (!window._dibsCheckout) {
                return
            }

            var self = this;
            window._dibsCheckout.on('payment-completed', function (response) {

                jQuery.ajax({
                    url: BASE_URL + "easycheckout/order/SaveOrder/pid/" + response.paymentId,
                    type: "POST",
                    context: this,
                    data: "",
                    dataType: 'json',
                    beforeSend: function () {
                        self._hideDibsCheckout();
                    },
                    complete: function () {
                        self._showDibsCheckout();
                    },
                    success: function (response) {

                        if (jQuery.type(response) === 'object' && !jQuery.isEmptyObject(response)) {

                            if (response.chooseShippingMethod) {
                                self.checkShippingMethod();
                                self._hideDibsCheckout();
                            }

                            if (response.messages) {
                                alert({
                                    content: jQuery.mage.__(response.messages)
                                });
                            }

                            if (response.redirectTo) {
                                window.location.href = response.redirectTo;
                            }

                        } else {
                            alert({
                                content: jQuery.mage.__('Sorry, something went wrong. Please try again later.')
                            });
                        }
                    },
                    error: function(data) {
                        alert({
                            content: jQuery.mage.__('Sorry, something went wrong. Please try again later.')
                        });

                    }

                });

            });

            window._dibsCheckout.on('pay-initialized', function (response) {

                jQuery.ajax({
                    url: BASE_URL + "easycheckout/order/ValidateOrder",
                    type: "POST",
                    context: this,
                    data: "",
                    dataType: 'json',
                    beforeSend: function () {
                        self._hideDibsCheckout();
                    },
                    complete: function () {
                        self._showDibsCheckout();
                    },
                    success: function (response) {

                        if (jQuery.type(response) === 'object' && !jQuery.isEmptyObject(response)) {

                            if (response.error) {
                                window._dibsCheckout.sendPaymentOrderFinalizedEvent(false);
                            } else {
                                window._dibsCheckout.sendPaymentOrderFinalizedEvent(true);
                            }

                            if (response.chooseShippingMethod) {
                                self.checkShippingMethod();
                                self._hideDibsCheckout();
                            }

                            if (response.messages) {
                                alert({
                                    content: jQuery.mage.__(response.messages)
                                });
                            }
                        } else {

                            // tell dibs not to finish order!
                            window._dibsCheckout.sendPaymentOrderFinalizedEvent(false);

                            alert({
                                content: jQuery.mage.__('Sorry, something went wrong. Please try again later.')
                            });
                        }
                    },
                    error: function(data) {
                        // tell dibs not to finish order!
                        window._dibsCheckout.sendPaymentOrderFinalizedEvent(false);

                        alert({
                            content: jQuery.mage.__('Sorry, something went wrong. Please try again later.')
                        });

                    }

                });


            });
        },

        /**
         * UI Stuff
         */
        getViewport: function () {
            var e = window, a = 'inner';
            if (!('innerWidth' in window)) {
                a = 'client';
                e = document.documentElement || document.body;
            }
            return {width: e[a + 'Width'], height: e[a + 'Height']};
        },
        sidebarFiddled: false,
        fiddleSidebar: function () {
            var t = this;
            if ((this.getViewport().width <= 960) && !this.sidebarFiddled) {
                jQuery('.mobile-collapse').each(function () {
                    jQuery(this).collapsible('deactivate');
                    t.sidebarFiddled = true;
                });
            }
        },
        uiManipulate: function () {
            var t = this;
            jQuery(window).resize(function () {
                t.fiddleSidebar();
            });
            jQuery(document).ready(function () {
                t.fiddleSidebar();
            });
        },
        hidePaymentAndIframe: function () {
            var trigger = this.options.getShippingMethodButton;
            var pay = this.options.scrollTrigger;
            jQuery(trigger).click(function () {
                jQuery(pay).css({
                    'display': 'none'
                });
            })
        },
        scrollToPayment: function () {
            var trigger = this.options.scrollTrigger;
            var target = this.options.scrollTarget;
            var self = this;
            jQuery(trigger).click(function () {
                jQuery(target).css({
                    'visibility': 'visible',
                    'height': 'auto',
                });
                jQuery('html, body').animate({
                    scrollTop: jQuery(target).offset().top
                }, 500);
            })
        },

        checkShippingMethod: function () {
            var holder = this.options.shippingMethodCheckBoxHolder;
            jQuery(holder).click(function () {
                var $checks = jQuery(this).find('input:radio[name=shipping_method]');
                $checks.prop("checked", !$checks.is(":checked")).trigger('change');
                if ($checks.is(":checked")) {
                    jQuery(this).css('opacity', '1');
                    jQuery(this).parent().find('.dibs-easy-checkout-radio-row').not(this).css('opacity', '.5');
                }
            });
        }
    });

    return jQuery.mage.nwtdibsCheckout;
});
