/**
 * Payment Methods Sortable Widget
 * Provides drag-and-drop sorting and enable/disable functionality for payment methods
 */
define([
    'jquery',
    'jquery/ui',
    'mage/translate'
], function ($) {
    'use strict';

    $.widget('nexi.paymentMethodsSortable', {
        options: {
            elementId: ''
        },

        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            this.hiddenSelect = $('#' + this.options.elementId);
            this.listContainer = $('#' + this.options.elementId + '_list');
            this.enableSplittingSelect = $('select[id$="pay_type_splitting"]');

            this._makeSortable();
            this._bindEvents();

            this._hideOriginalSelect();
            this.listContainer.show();
        },

        /**
         * Make the list sortable using jQuery UI
         * @private
         */
        _makeSortable: function () {
            var self = this;
            
            this.listContainer.sortable({
                handle: '.payment-method-drag-handle',
                placeholder: 'payment-method-placeholder',
                cursor: 'move',
                opacity: 0.8,
                tolerance: 'pointer',
                update: function() {
                    self._updateValue();
                }
            });
        },

        /**
         * Bind events for toggle switches
         * @private
         */
        _bindEvents: function () {
            const self = this;

            this.listContainer.on('click', '.admin__actions-switch', function() {
                const checkbox = $(this).find('.admin__actions-switch-checkbox');
                checkbox.prop('checked', !checkbox.is(':checked'));
                
                self._updateValue();
            });
        },

        /**
         * Update the hidden input value
         * @private
         */
        _updateValue: function () {
            const selected = [];
            const sortedOptions = [];
            const select = this.hiddenSelect;

            this.listContainer.find('.payment-method-item').each(function() {
                const item = $(this);
                const value = item.data('value');
                const enabled = item.find('.admin__actions-switch-checkbox').is(':checked');
                const option = select.find('option[value="'+value+'"]');

                sortedOptions.push(option);
                if (enabled) {
                    selected.push(value);
                }
            });

            this.hiddenSelect.empty().append(sortedOptions);

            this.hiddenSelect.val(selected).trigger('change');
        },

        /**
         * Hide hiddenSelect when splitPayment enabled
         * @private
         */
        _hideOriginalSelect: function () {
            const select = this.hiddenSelect;

            select.css('display', 'none');
            this.enableSplittingSelect.on('change', function() {
                if (this.value == 1) {
                    select.css('display', 'none');
                }
            });
        },
    });

    return $.nexi.paymentMethodsSortable;
});
