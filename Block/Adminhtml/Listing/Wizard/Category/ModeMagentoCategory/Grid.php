<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\Listing\Wizard\Category\ModeMagentoCategory;

use M2E\TikTokShop\Block\Adminhtml\TikTokShop\Grid\Column\Filter\CategoryMode as CategoryModeFilter;

class Grid extends \M2E\TikTokShop\Block\Adminhtml\Category\Grid
{
    private \M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory\CategoryDataCollection $categoryDataCollection;
    private \M2E\TikTokShop\Model\Listing\Wizard\Ui\RuntimeStorage $uiWizardRuntimeStorage;
    private \M2E\Core\Helper\Magento\Category $categoryHelper;

    public function __construct(
        \M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory\CategoryDataCollection $categoryDataCollection,
        \M2E\TikTokShop\Model\Listing\Wizard\Ui\RuntimeStorage $uiWizardRuntimeStorage,
        \M2E\Core\Helper\Magento\Category $categoryHelper,
        \M2E\TikTokShop\Model\ResourceModel\Magento\Category\CollectionFactory $categoryCollectionFactory,
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($categoryCollectionFactory, $context, $backendHelper, $data);
        $this->categoryDataCollection = $categoryDataCollection;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
        $this->categoryHelper = $categoryHelper;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('tikTokShopListingCategoryMagentoGrid');

        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');

        $magentoProductIds = $this->uiWizardRuntimeStorage->getManager()->getProductsIds();
        $magentoCategoryIds = $this->categoryHelper->getLimitedCategoriesByProducts($magentoProductIds);

        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', 'in' => $magentoCategoryIds],
        ]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('magento_category', [
            'header' => $this->__('Magento Category'),
            'align' => 'left',
            'width' => '500px',
            'type' => 'text',
            'index' => 'name',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnMagentoCategory'],
        ]);

        $this->addColumn('tiktok_shop_category', [
            'header' => __('%channel_title Category', [
                'channel_title' => \M2E\TikTokShop\Helper\Module::getChannelTitle(),
            ]),
            'align' => 'left',
            'width' => '*',
            'type' => 'options',
            'options' => [
                CategoryModeFilter::MODE_SELECTED => __('Category Selected'),
                CategoryModeFilter::MODE_NOT_SELECTED => __('Category Not Selected'),
            ],
            'index' => 'category',
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnCategories'],
            'filter_condition_callback' => [$this, 'callbackFilterCategories'],
        ]);

        $this->addColumn('actions', [
            'header' => $this->__('Actions'),
            'align' => 'center',
            'width' => '100px',
            'type' => 'text',
            'sortable' => false,
            'filter' => false,
            'renderer' => \M2E\TikTokShop\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'actions' => $this->getColumnActionsItems(),
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');

        $this->getMassactionBlock()->addItem('editCategories', [
            'label' => __('Edit Category'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('resetCategories', [
            'label' => __('Reset Category'),
            'url' => '',
        ]);

        return parent::_prepareMassaction();
    }

    public function callbackColumnCategories($value, $row, $column, $isExport)
    {
        $magentoCategoryId = $row->getData('entity_id');
        $categoryMappingItem = $this->categoryDataCollection
            ->findByMagentoCategoryId((int)$magentoCategoryId);

        if ($categoryMappingItem !== null) {
            return sprintf(
                '%s (%s)',
                $categoryMappingItem->tikTokShopCategoryPath,
                $categoryMappingItem->tikTokShopCategoryId,
            );
        }

        return sprintf(
            '<img src="%s" alt="">&nbsp;<span style="font-style: italic; color: gray">%s</span>',
            $this->getViewFileUrl('M2E_TikTokShop::images/warning.png'),
            __('Not Selected'),
        );
    }

    protected function callbackFilterCategories($collection, $column)
    {
        $filter = $column->getFilter()->getValue();
        $magentoCategoryIds = [];
        foreach ($this->categoryDataCollection->getItems() as $categoryData) {
            $magentoCategoryIds[] = $categoryData->magentoCategoryId;
        }

        $operator = (int)$filter === CategoryModeFilter::MODE_SELECTED ? 'in' : 'nin';

        $collection->addFieldToFilter('entity_id', [$operator => $magentoCategoryIds]);
    }

    private function getColumnActionsItems(): array
    {
        return [
            'editCategories' => [
                'caption' => $this->__('Edit Categories'),
                'field' => 'id',
                'onclick_action' => "ListingWizardCategoryModeMagentoCategoryGridObj.actions['editCategoriesAction']",
            ],

            'resetCategories' => [
                'caption' => $this->__('Reset Categories'),
                'field' => 'id',
                'onclick_action' => "ListingWizardCategoryModeMagentoCategoryGridObj.actions['resetCategoriesAction']",
            ],
        ];
    }

    protected function _toHtml()
    {
        $hasNotMappedCategory = (int)$this->hasNotMappedCategory();

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingWizardCategoryModeMagentoCategoryGridObj.afterInitPage();
    ListingWizardCategoryModeMagentoCategoryGridObj.validateCategories($hasNotMappedCategory);
JS,
            );

            return parent::_toHtml();
        }

        $this->jsUrl->add(
            $this->getUrl(
                '*/listing_wizard_category/chooserBlockModeMagentoCategory',
            ),
            'listing_wizard_category/chooserBlockModeMagentoCategory',
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/listing_wizard_category/assignModeMagentoCategory',
            ),
            'listing_wizard_category/assignModeMagentoCategory',
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/listing_wizard_category/resetModeMagentoCategory',
            ),
            'listing_wizard_category/resetModeMagentoCategory',
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/listing_wizard_category/validateModeManually',
            ),
            'listing_wizard_category/validateModeManually',
        );

        $this->jsUrl->add(
            $this->getUrl('*/listing_wizard_category/completeStep'),
            'listing_wizard_category/complete_step',
        );

        // ---------------------------------------

        $gridId = $this->getId();

        $this->js->addOnReadyJs(
            <<<JS
require([
    'TikTokShop/Plugin/AreaWrapper',
    'TikTokShop/Listing/Wizard/Category/Mode/MagentoCategory/Grid'
], function(){

    window.ListingWizardCategoryModeMagentoCategoryGridObj = new ListingWizardCategoryModeMagentoCategoryGrid('$gridId');
    window.WrapperObj = new AreaWrapper('$gridId');

    ListingWizardCategoryModeMagentoCategoryGridObj.afterInitPage();
    ListingWizardCategoryModeMagentoCategoryGridObj.validateCategories($hasNotMappedCategory);
})
JS,
        );

        return parent::_toHtml();
    }

    private function hasNotMappedCategory(): bool
    {
        $magentoProductIds = $this->uiWizardRuntimeStorage->getManager()->getProductsIds();
        $magentoCategoryIds = $this->categoryHelper->getLimitedCategoriesByProducts($magentoProductIds);
        foreach ($magentoCategoryIds as $magentoCategoryId) {
            if ($this->categoryDataCollection->findByMagentoCategoryId($magentoCategoryId) === null) {
                return true;
            }
        }

        return false;
    }
}
