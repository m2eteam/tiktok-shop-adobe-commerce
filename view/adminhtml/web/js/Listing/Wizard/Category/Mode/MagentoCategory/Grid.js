define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'M2ECore/Plugin/Messages',
    'M2ECore/Plugin/Confirm',
    'mage/translate',
    'TikTokShop/Grid',
    'TikTokShop/Category/Chooser/SelectedProductsData'
], function ($, modal, MessageObj, Confirm, $t) {

    window.ListingWizardCategoryModeMagentoCategoryGrid = Class.create(Grid, {

        prepareActions: function () {

            this.actions = {
                editCategoriesAction: function (id) {
                    id && this.selectByRowId(id);
                    this.editCategories();
                }.bind(this),

                resetCategoriesAction: function (id) {
                    this.resetCategories(id);
                }.bind(this),

                removeItemAction: function (id) {
                    var ids = id ? [id] : this.getSelectedProductsArray();
                    this.removeItems(ids);
                }.bind(this)
            };
        },

        // ---------------------------------------

        extractIdFromUrl: function () {
            const urlParts = window.location.href.split('/');
            const idIndex = urlParts.indexOf('id');
            if (idIndex !== -1 && idIndex < urlParts.length - 1) {
                return parseInt(urlParts[idIndex + 1]);
            }
        },

        // ---------------------------------------

        editCategories: function () {
            const self = this;

            const wizard_id = self.extractIdFromUrl();
            const selectedCategoryIds = self.getSelectedProductsString();

            new Ajax.Request(TikTokShop.url.get('listing_wizard_category/chooserBlockModeMagentoCategory'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    category_ids: selectedCategoryIds,
                    id: wizard_id
                },
                onSuccess: (transport) => {
                    window.SelectedProductsDataObj.setMagentoCategoryId(selectedCategoryIds);
                    this.openPopUp($t('Category Settings'), transport.responseText);
                }
            });
        },

        resetCategories: function (id) {
            if (id && !confirm('Are you sure?')) {
                return;
            }
            const self = this;
            const wizard_id = self.extractIdFromUrl();

            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();

            new Ajax.Request(TikTokShop.url.get('listing_wizard_category/resetModeMagentoCategory'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    products_ids: this.selectedProductsIds.join(','),
                    id: wizard_id
                },
                onSuccess: function (transport) {
                    this.getGridObj().doFilter();
                    this.unselectAll();
                }.bind(this)
            });
        },

        validateCategories: function (hasNotMappedCategory) {
            MessageObj.setContainer('#anchor-content');
            const continueButton = $('#listing_category_continue_btn');

            continueButton.removeClass('disabled');
            continueButton.prop('disabled', false);
            MessageObj.clear();

            if (hasNotMappedCategory) {
                continueButton.addClass('disabled');
                continueButton.prop('disabled', true);
                MessageObj.addWarning($t('To proceed, the category data must be specified.'));
            }
        },

        openPopUp: function (title, content) {
            const self = this;
            const popupId = 'modal_view_action_dialog';

            let modalDialogMessage = document.getElementById(popupId);

            if (!modalDialogMessage) {
                modalDialogMessage = new Element('form', {
                    id: popupId
                });
            }

            modalDialogMessage.innerHTML = '';

            this.popUp = $(modalDialogMessage).modal(Object.extend({
                title: title,
                type: 'slide',
                buttons: [{
                    text: $t('Cancel'),
                    attr: {id: 'cancel_button'},
                    class: 'action-dismiss',
                    click: function (event) {
                        self.unselectAllAndReload();
                        this.closeModal(event);
                        $(popupId).remove()
                    }
                }, {
                    text: $t('Save'),
                    attr: {id: 'done_button'},
                    class: 'action-primary action-accept',
                    click: function (event) {
                        self.confirmCategoriesData();
                    }
                }]
            }));

            this.popUp.modal('openModal');

            try {
                modalDialogMessage.innerHTML = content;
                modalDialogMessage.innerHTML.evalScripts();
            } catch (ignored) {
            }
        },

        confirmCategoriesData: function () {
            this.initFormValidation('#modal_view_action_dialog');

            if (!$('#modal_view_action_dialog').valid()) {
                return;
            }

            const selectedCategory = TikTokShopCategoryChooserObj.selectedCategory;

            TikTokShopCategoryChooserObj.messagesClearAll()
            if (!selectedCategory.is_all_required_attributes_filled) {
                TikTokShopCategoryChooserObj.messageAddErrorToModalHeaderBlock($t('Please complete all required attributes to proceed.'));
                return;
            }

            this.assignCategoriesData(selectedCategory);
        },

        assignCategoriesData: function (templateData) {
            const self = this;
            const wizard_id = self.extractIdFromUrl();

            new Ajax.Request(TikTokShop.url.get('listing_wizard_category/assignModeMagentoCategory'), {
                method: 'post',
                parameters: {
                    products_ids: this.getSelectedProductsString(),
                    template_data: Object.toJSON(templateData),
                    id: wizard_id
                },
                onSuccess: function (transport) {

                    $('#modal_view_action_dialog').modal('closeModal');
                    this.unselectAllAndReload();
                }.bind(this)
            });
        },

        completeCategoriesDataStep: function (validateCategory, validateSpecifics) {
            MessageObj.clear();
            const self = this;
            const wizard_id = self.extractIdFromUrl();

            new Ajax.Request(TikTokShop.url.get('listing_wizard_category/validateModeManually'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    validate_category: validateCategory,
                    validate_specifics: validateSpecifics,
                    id: wizard_id
                },
                onSuccess: function (transport) {

                    const response = transport.responseText.evalJSON();

                    const completeUrl = TikTokShop.url.get('listing_wizard_category/complete_step', {
                        'id': wizard_id,
                        'remove_products_without_category': true
                    });

                    if (response.validation) {
                        return setLocation(completeUrl);
                    }

                    const warningContent = $t(`
<p>You have not specified full Category data for <strong>%failed_count</strong>
of <strong>%total_count</strong> Product(s).</p>
<p>Products without a TikTok Shop Category and required Product Attributes
<strong>will not be added to M2E Listing</strong> as they cannot be listed on the Channel.
TikTok Shop requires submitting full Category data when listing a new Product.</p>
<p>To set TikTok Shop Category to <strong>%failed_count</strong> Product(s) now, click <i>Cancel</i> and
complete the configurations. Otherwise, click <i>Continue</i>. You will be able to add these Products
later using the Add Products button on the View Listing page.</p>`);

                    Confirm({
                        title: $t('Set Category'),
                        content: warningContent
                                .replaceAll('%total_count', response.total_count)
                                .replaceAll('%failed_count', response.failed_count),
                        actions: {
                            confirm: () => {
                                return setLocation(completeUrl);
                            },
                            cancel: () => false,
                        },
                    });

                    return MessageObj.addError(response.message || 'Something wrong');
                }.bind(this)
            });
        },
    });

});
