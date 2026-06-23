define([
    'TikTokShop/Common'
], function () {

    window.SelectedProductsData = Class.create(Common, {

        // ---------------------------------------

        wizardId: null,
        productId: null,
        shopId: null,
        magentoCategoryId: null,

        // ---------------------------------------

        getWizardId: function () {
            return this.wizardId;
        },

        setWizardId: function (id) {
            this.wizardId = id;
        },

        // ---------------------------------------

        getShopId: function () {
            return this.shopId;
        },

        setShopId: function (id) {
            this.shopId = id;
        },

        // ---------------------------------------

        getProductId: function () {
            return this.productId;
        },

        setProductId: function (id) {
            this.productId = id;
        },

        // ---------------------------------------

        getMagentoCategoryId: function () {
            return this.magentoCategoryId;
        },

        setMagentoCategoryId: function (id) {
            this.magentoCategoryId = id;
        }
    });
});
