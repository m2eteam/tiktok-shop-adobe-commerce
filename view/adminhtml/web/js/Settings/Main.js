define([
    'jquery',
    'mage/translate',
    'TikTokShop/Common'
], function ($, $t) {
    window.TikTokShopSettingsMain = Class.create(Common, {

        initialize: function () {
            this.initValidators();
            this.initObservers();
        },

        initValidators: function () {
            $.validator.addMethod(
                    'validator-required-when-visible',
                    (value) => value > 0,
                    $t('This is a required field.')
            );
            $.validator.addMethod(
                    'validator-tts-weight',
                    this.weightValidator.bind(this),
                    $t("The package weight must be a positive number")
            );
            $.validator.addMethod(
                    'validator-tts-length',
                    this.sizeValidator.bind(this),
                    $t("The package length needs to be a whole number that's not negative")
            );
            $.validator.addMethod(
                    'validator-tts-width',
                    this.sizeValidator.bind(this),
                    $t("The package width needs to be a whole number that's not negative")
            );
            $.validator.addMethod(
                    'validator-tts-height',
                    this.sizeValidator.bind(this),
                    $t("The package height needs to be a whole number that's not negative")
            );
        },

        initObservers: function () {
            $(document).on('change', '#identifier_code_mode', (event) => {
                this.identifierCodeModeChange(event, $('#identifier_code_custom_attribute'))
            });

            $(document).on('change', '#package_weight_mode', (event) => {
                this.packageModeChange(event, $('#package_weight_custom_attribute'), $('#package_weight_custom_value'))
            });

            $(document).on('change', '#package_length_mode', (event) => {
                this.packageModeChange(event, $('#package_length_custom_attribute'), $('#package_length_custom_value'))
            });

            $(document).on('change', '#package_width_mode', (event) => {
                this.packageModeChange(event, $('#package_width_custom_attribute'), $('#package_width_custom_value'))
            });

            $(document).on('change', '#package_height_mode', (event) => {
                this.packageModeChange(event, $('#package_height_custom_attribute'), $('#package_height_custom_value'))
            });
        },

        // ---------------------------------------

        identifierCodeModeChange: function (event, customAttributeInput) {
            const option = $(event.currentTarget).find('option:selected');
            const mode = Number(option.val());

            customAttributeInput.val('');

            if (mode === TikTokShop.php.constant('M2E_TikTokShop_Model_TikTokShop_Settings_IdentifierCode::MODE_CUSTOM_ATTRIBUTE')) {
                customAttributeInput.val(option.attr('attribute_code'));
            }
        },

        packageModeChange: function (event, customAttributeInput, customValueInput) {
            const option = $(event.currentTarget).find('option:selected');
            const mode = Number(option.val());

            customAttributeInput.val('');
            customValueInput.val('').hide();

            if (mode === TikTokShop.php.constant('M2E_TikTokShop_Model_TikTokShop_Settings_Package::MODE_CUSTOM_ATTRIBUTE')) {
                customAttributeInput.val(option.attr('attribute_code'));
            }

            if (mode === TikTokShop.php.constant('M2E_TikTokShop_Model_TikTokShop_Settings_Package::MODE_CUSTOM_VALUE')) {
                customValueInput.show();
            }
        },

        // ---------------------------------------

        weightValidator: function (value, element) {
            if (this.isElementHiddenFromPage(element)) {
                return true;
            }

            if (typeof value === 'string') {
                value = value.trim();
            }

            return new RegExp(/^(?:[0-9]*[.])?[0-9]+$/).test(value);
        },

        sizeValidator: function (value, element) {
            if (this.isElementHiddenFromPage(element)) {
                return true;
            }

            if (typeof value === 'string') {
                value = value.trim();
            }

            return new RegExp(/^[0-9]+$/).test(value);
        }
    })
})
