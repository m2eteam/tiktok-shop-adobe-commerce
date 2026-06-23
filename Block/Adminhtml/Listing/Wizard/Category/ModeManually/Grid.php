<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\Listing\Wizard\Category\ModeManually;

use M2E\TikTokShop\Block\Adminhtml\TikTokShop\Grid\Column\Filter\CategoryMode as CategoryModeFilter;
use M2E\TikTokShop\Model\ResourceModel\Listing\Wizard\Step as StepResource;

class Grid extends \M2E\TikTokShop\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private array $categoriesData;
    private \M2E\TikTokShop\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory;
    private \M2E\TikTokShop\Model\ResourceModel\Listing\Wizard\Product $productResource;
    private \M2E\TikTokShop\Model\Listing\Ui\RuntimeStorage $uiListingRuntimeStorage;
    private \M2E\TikTokShop\Model\Listing\Wizard\Ui\RuntimeStorage $uiWizardRuntimeStorage;

    private string $gridId;

    public function __construct(
        array $categoriesData,
        \M2E\TikTokShop\Model\Listing\Wizard\Ui\RuntimeStorage $uiWizardRuntimeStorage,
        \M2E\TikTokShop\Model\Listing\Ui\RuntimeStorage $uiListingRuntimeStorage,
        \M2E\TikTokShop\Model\ResourceModel\Listing\Wizard\Product $productResource,
        \M2E\TikTokShop\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->categoriesData = $categoriesData;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->productResource = $productResource;
        $this->uiListingRuntimeStorage = $uiListingRuntimeStorage;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;

        parent::__construct(
            $context,
            $backendHelper,
            $data,
        );
    }

    // ----------------------------------------

    public function _construct()
    {
        parent::_construct();

        $this->setId($this->gridId = 'listingCategoryManuallyGrid');

        $this->setDefaultSort('product_id');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    // ----------------------------------------

    protected function _prepareCollection()
    {
        $collection = $this->magentoProductCollectionFactory
            ->create()
            ->addAttributeToSelect('name');

        $collection->getSelect()->distinct();
        $store = $this->_storeManager->getStore($this->uiListingRuntimeStorage->getListing()->getStoreId());

        if ($store->getId()) {
            $collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $store->getId(),
            );
            $collection->joinAttribute(
                'thumbnail',
                'catalog_product/thumbnail',
                'entity_id',
                null,
                'left',
                $store->getId(),
            );
        } else {
            $collection->addAttributeToSelect('thumbnail');
        }

        $collection->getSelect()->__toString();

        $collection->joinTable(
            ['listing_product' => $this->productResource->getMainTable()],
            sprintf(
                '%s = entity_id',
                \M2E\TikTokShop\Model\ResourceModel\Listing\Wizard\Product::COLUMN_MAGENTO_PRODUCT_ID,
            ),
            [
                'product_id' => \M2E\TikTokShop\Model\ResourceModel\Listing\Wizard\Product::COLUMN_ID,
            ],
            sprintf(
                '{{table}}.%s = %s',
                StepResource::COLUMN_WIZARD_ID,
                $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
            ),
        );

        $collection->addFieldToFilter(
            'entity_id',
            ['in' => array_keys($this->categoriesData)],
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => __('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'store_id' => $this->uiListingRuntimeStorage->getListing()->getStoreId(),
            'renderer' => \M2E\TikTokShop\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title'),
            'align' => 'left',
            'width' => '350px',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
        ]);

        $this->addColumn('category', [
            'header' => __('%channel_title Category', [
                'channel_title' => \M2E\TikTokShop\Helper\Module::getChannelTitle(),
            ]),
            'align' => 'left',
            'width' => '*',
            'index' => 'category',
            'type' => 'options',
            'options' => [
                CategoryModeFilter::MODE_SELECTED => __('Category Selected'),
                CategoryModeFilter::MODE_NOT_SELECTED => __('Category Not Selected'),
            ],
            'sortable' => false,
            'filter_condition_callback' => [$this, 'callbackFilterCategories'],
            'frame_callback' => [$this, 'callbackColumnCategories'],
        ]);

        $this->addColumn('actions', [
            'header' => __('Actions'),
            'align' => 'center',
            'width' => '100px',
            'type' => 'text',
            'sortable' => false,
            'filter' => false,
            'field' => 'product_id',
            'renderer' => \M2E\TikTokShop\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'actions' => $this->getColumnActionsItems(),
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('product_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------

        $this->getMassactionBlock()->setGroups([
            'edit_settings' => __('Edit Settings'),
            'other' => __('Other'),
        ]);

        // ---------------------------------------

        $this->getMassactionBlock()->addItem('editCategories', [
            'label' => __('Edit Category'),
            'url' => '',
        ], 'edit_settings');

        $this->getMassactionBlock()->addItem('resetCategories', [
            'label' => __('Reset Category'),
            'url' => '',
        ], 'edit_settings');

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    // ----------------------------------------

    public function callbackColumnCategories($value, $row, $column, $isExport)
    {
        $categoryId = $row->getData('entity_id');
        $categoryInfo = $this->categoriesData;

        if (
            !empty($categoryInfo[$categoryId])
            && !empty($categoryInfo[$categoryId]['value'])
        ) {
            $categoryData = $categoryInfo[$categoryId];

            return sprintf(
                '%s (%s)',
                $categoryData['path'],
                $categoryData['value'],
            );
        }

        $iconSrc = $this->getViewFileUrl('M2E_TikTokShop::images/warning.png');

        return sprintf(
            '<img src="%s" alt="">&nbsp;<span style="font-style: italic; color: gray">%s</span>',
            $iconSrc,
            __('Not Selected'),
        );
    }

    // ----------------------------------------

    protected function callbackFilterCategories($collection, $column)
    {
        $filter = $column->getFilter()->getValue();
        $ids = [];
        foreach ($this->categoriesData as $productId => $categoryData) {
            if (
                (int)$filter === CategoryModeFilter::MODE_SELECTED
                && !empty($categoryData['value'])
            ) {
                $ids[] = $productId;
            }

            if (
                (int)$filter === CategoryModeFilter::MODE_NOT_SELECTED
                && empty($categoryData['value'])
            ) {
                $ids[] = $productId;
            }
        }

        $collection->addFieldToFilter('product_id', ['in' => $ids]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    // ----------------------------------------

    protected function getColumnActionsItems(): array
    {
        return [
            'editCategories' => [
                'caption' => __('Edit Category'),
                'field' => 'product_id',
                'onclick_action' => "ListingWizardCategoryModeManuallyGridObj.actions['editCategoriesAction']",
            ],
            'resetCategories' => [
                'caption' => __('Reset Category'),
                'field' => 'product_id',
                'onclick_action' => "ListingWizardCategoryModeManuallyGridObj.actions['resetCategoriesAction']",
            ],
        ];
    }

    // ----------------------------------------

    protected function _toHtml()
    {
        $allIdsStr = $this->getGridIdsJson();

        $isAllCategoriesSelected = (int)!$this->isAllCategoriesSelected($this->categoriesData);
        $showErrorMessage = (int)!empty($this->categoriesData);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingWizardCategoryModeManuallyGridObj.afterInitPage();
    ListingWizardCategoryModeManuallyGridObj.getGridMassActionObj().setGridIds('$allIdsStr');
    ListingWizardCategoryModeManuallyGridObj.validateCategories(
        '{$isAllCategoriesSelected}', '{$showErrorMessage}'
    );
JS,
            );

            return parent::_toHtml();
        }

        $this->jsUrl->add(
            $this->getUrl(
                '*/listing_wizard_category/chooserBlockModeManually',
            ),
            'listing_wizard_category/chooserBlockModeManually',
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/listing_wizard_category/saveModeManually',
            ),
            'listing_wizard_category/saveModeManually',
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/listing_wizard_category/resetModeManually',
            ),
            'listing_wizard_category/resetModeManually',
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

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->js->addOnReadyJs(
                <<<JS
require([
    'TikTokShop/Plugin/ProgressBar',
    'TikTokShop/Plugin/AreaWrapper',
    'TikTokShop/Listing/Wizard/Category/Mode/Manually/Grid'
], function(){

    window.ListingWizardCategoryModeManuallyGridObj
            = new ListingWizardCategoryModeManuallyGrid('{$this->getId()}');

    window.WrapperObj = new AreaWrapper('$this->gridId');

    ListingWizardCategoryModeManuallyGridObj.afterInitPage();
    ListingWizardCategoryModeManuallyGridObj.getGridMassActionObj().setGridIds('$allIdsStr');
    ListingWizardCategoryModeManuallyGridObj.validateCategories(
        '$isAllCategoriesSelected', '$showErrorMessage'
    );
})
JS,
            );
        }

        return parent::_toHtml();
    }

    // ----------------------------------------

    private function getGridIdsJson(): string
    {
        $select = clone $this->getCollection()->getSelect();
        $select->reset(\Magento\Framework\DB\Select::ORDER);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->resetJoinLeft();

        $select->columns('listing_product.id');

        $connection = $this->getCollection()->getConnection();

        return implode(',', $connection->fetchCol($select));
    }

    // ----------------------------------------

    protected function isAllCategoriesSelected($categoriesData): bool
    {
        if (empty($categoriesData)) {
            return false;
        }

        foreach ($categoriesData as $categoryData) {
            if (empty($categoryData['value'])) {
                return false;
            }
        }

        return true;
    }
}
