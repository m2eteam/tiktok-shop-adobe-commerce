define([
    'jquery',
    'mage/translate',
    'TikTokShop/Common',
    'TikTokShop/Category/Chooser/SelectedProductsData',
    'TikTokShop/Category/Chooser'
], function (jQuery, $t) {
    window.TikTokShopCategoryRecommended = Class.create(Common, {
        categoryChooser: null,

        initialize: function () {
            this.categoryChooser = window.TikTokShopCategoryChooserObj;

            if (window.SelectedProductsDataObj) {
                this.search(
                        window.SelectedProductsDataObj.getShopId(),
                        window.SelectedProductsDataObj.getWizardId(),
                        window.SelectedProductsDataObj.getProductId(),
                        window.SelectedProductsDataObj.getMagentoCategoryId()
                );
            }
        },

        initObservers: function () {
            const self = this;

            jQuery('#recommended_results_container').on('click', '.choice-category', function (event) {
                const choiceLink = jQuery(event.currentTarget);
                self.categoryChooser.selectCategory(choiceLink.attr('data-category-id'))
            })
        },

        search: function (
            shopId,
            wizardId,
            productId,
            magentoCategoryId
        ) {
            const url = TikTokShop.url.get('*/category/recommended');
            new Ajax.Request(url, {
                method: 'post',
                asynchronous: true,
                parameters: {
                    shop_id: shopId,
                    wizard_id: wizardId,
                    product_id: productId,
                    magento_category_id: magentoCategoryId
                },
                onSuccess: function (transport) {
                    const resultTable = jQuery('#recommended_results_table');
                    resultTable.empty()

                    if (!Array.isArray(transport.responseText.evalJSON().categories)) {
                        jQuery.each(transport.responseText.evalJSON(), function (index, category) {
                            let categoryName = `${category['path']} (${category['id']})`;
                            let style = '';
                            let choiceLink = `<a class="choice-category" data-category-id="${category['id']}">${$t('Select')}</a>`

                            if (category['is_invite']) {
                                categoryName += ` [${ $t('INVITE ONLY') }]`;
                                style = 'color:gray';
                                choiceLink = '';
                            }

                            const row = `
                            <tr>
                                <td><span style="${style}">${categoryName}</span></td>
                                <td style="text-align: right">${choiceLink}</td>
                            </tr>
                        `
                            resultTable.append(jQuery(row))
                        });
                    } else {
                        const row = '<tr><td colspan="2" style="padding-left: 200px"><span>' + $t('No matching Categories are found.') + '</span></td></tr>';
                        resultTable.append(jQuery(row))
                    }
                }.bind(this)
            });

            this.initObservers();
        }
    });
});
