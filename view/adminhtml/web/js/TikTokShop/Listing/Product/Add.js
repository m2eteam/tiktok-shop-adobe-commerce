define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'TikTokShop/Common'
], function (jQuery, modal) {
    window.TikTokShopListingProductAdd = Class.create(Common, {

        // ---------------------------------------

        options: {
            show_autoaction_popup: false,

            get_selected_products: function (callback) {
            }
        },

        // ---------------------------------------

        initialize: function (options) {
            this.options = Object.extend(this.options, options);
        },

        // ---------------------------------------

        continue: function () {
            var self = this;

            self.options.get_selected_products(function (selectedProducts) {

                if (!selectedProducts) {
                    self.alert(TikTokShop.translator.translate('Please select the Products you want to perform the Action on.'));
                    return;
                }

                self.add(selectedProducts);

            });
        },

        // ---------------------------------------

        add: function (products) {
            var self = this;

            self.products = products;

            var parts = self.makeProductsParts();

            ProgressBarObj.reset();
            ProgressBarObj.setTitle('Adding Products to Listing');
            ProgressBarObj.setStatus('Adding in process. Please wait...');
            ProgressBarObj.show();
            self.scrollPageToTop();

            WrapperObj.lock();

            self.sendPartsProducts(parts, parts.length);
        },

        makeProductsParts: function () {
            var self = this;

            var productsInPart = 50;
            var productsArray = explode(',', self.products);
            var parts = new Array();

            if (productsArray.length < productsInPart) {
                return parts[0] = productsArray;
            }

            var result = new Array();
            for (var i = 0; i < productsArray.length; i++) {
                if (result.length == 0 || result[result.length - 1].length == productsInPart) {
                    result[result.length] = new Array();
                }
                result[result.length - 1][result[result.length - 1].length] = productsArray[i];
            }

            return result;
        },

        sendPartsProducts: function (parts, partsCount) {
            var self = this;

            if (parts.length == 0) {
                return;
            }

            var part = parts.splice(0, 1);
            part = part[0];
            var partString = implode(',', part);

            var isLastPart = '';
            if (parts.length <= 0) {
                isLastPart = 'yes';
            }

            new Ajax.Request(TikTokShop.url.get('tiktokshop_listing_product_add/add'), {
                method: 'post',
                parameters: {
                    is_last_part: isLastPart,
                    products: partString
                },
                onSuccess: function (transport) {

                    var percents = (100 / partsCount) * (partsCount - parts.length);

                    if (percents <= 0) {
                        ProgressBarObj.setPercents(0, 0);
                    } else if (percents >= 100) {
                        ProgressBarObj.setPercents(100, 0);
                        ProgressBarObj.setStatus('Adding has been completed.');

                        setLocation(TikTokShop.url.get('tiktokshop_listing_product_category_settings', {step: 1}));
                    } else {
                        ProgressBarObj.setPercents(percents, 1);
                    }

                    setTimeout(function () {
                        self.sendPartsProducts(parts, partsCount);
                    }, 500);
                }
            });

            $$('.loading-mask').invoke('setStyle', {visibility: 'hidden'});
        },

        // ---------------------------------------
    });
});
