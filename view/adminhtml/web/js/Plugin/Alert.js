define([
    'jquery',
    'underscore',
    'jquery/ui',
    'Magento_Ui/js/modal/confirm'
], function ($, _) {
    'use strict';

    $.widget('mage.m2eAlert', $.mage.confirm, {
        options: {
            modalClass: 'confirm',
            title: $.mage.__('Attention'),
            actions: {

                /**
                 * Callback always - called on all actions.
                 */
                always: function () {
                }
            },
            buttons: [{
                text: $.mage.__('OK'),
                class: 'action-primary action-accept',

                /**
                 * Click handler.
                 */
                click: function () {
                    this.closeModal(true);
                }
            }]
        },

        /**
         * Close modal window.
         */
        closeModal: function () {
            this.options.actions.always();
            this.element.bind('alertclosed', _.bind(this._remove, this));

            return this._super();
        }
    });

    return function (config) {
        return $('<div></div>').html(config.content).m2eAlert(config);
    };
});
