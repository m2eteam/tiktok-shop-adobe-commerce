<?php

namespace M2E\TikTokShop\Block\Adminhtml\Log\Listing\Product\View\Separated;

use M2E\TikTokShop\Block\Adminhtml\Log\Listing\View;

abstract class AbstractGrid extends \M2E\TikTokShop\Block\Adminhtml\Log\Listing\Product\AbstractGrid
{
    private \M2E\TikTokShop\Model\ResourceModel\Listing\Log\CollectionFactory $listingLogCollectionFactory;
    private \M2E\TikTokShop\Model\ResourceModel\Listing $listingResource;

    public function __construct(
        \M2E\TikTokShop\Model\ResourceModel\Listing\Log\CollectionFactory $listingLogCollectionFactory,
        \M2E\TikTokShop\Model\ResourceModel\Listing $listingResource,
        \M2E\TikTokShop\Model\ResourceModel\Account $accountResource,
        \M2E\TikTokShop\Model\Config\Manager $config,
        \M2E\TikTokShop\Model\ResourceModel\Collection\WrapperFactory $wrapperCollectionFactory,
        \M2E\TikTokShop\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \M2E\TikTokShop\Helper\View $viewHelper,
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \M2E\TikTokShop\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct(
            $accountResource,
            $config,
            $wrapperCollectionFactory,
            $customCollectionFactory,
            $resourceConnection,
            $viewHelper,
            $context,
            $backendHelper,
            $dataHelper,
            $data,
        );
        $this->listingLogCollectionFactory = $listingLogCollectionFactory;
        $this->listingResource = $listingResource;
    }

    protected function getViewMode()
    {
        return View\Switcher::VIEW_MODE_SEPARATED;
    }

    protected function _prepareCollection()
    {
        $collection = $this->listingLogCollectionFactory->create();
        $collection->joinLeft(
            ['listing' => $this->listingResource->getMainTable()],
            sprintf(
                '%s = listing.%s',
                \M2E\TikTokShop\Model\ResourceModel\Listing\Log::COLUMN_LISTING_ID,
                \M2E\TikTokShop\Model\ResourceModel\Listing::COLUMN_ID
            ),
            [
                \M2E\TikTokShop\Model\ResourceModel\Listing::COLUMN_STORE_ID,
            ]
        );

        $this->applyFilters($collection);

        $isNeedCombine = $this->isNeedCombineMessages();

        if ($isNeedCombine) {
            $collection->getSelect()->columns(
                ['main_table.create_date' => new \Zend_Db_Expr('MAX(main_table.create_date)')]
            );
            $collection->getSelect()->group(['main_table.listing_product_id', 'main_table.description']);
        }

        $this->setCollection($collection);

        $result = parent::_prepareCollection();

        if ($isNeedCombine) {
            $this->prepareMessageCount($collection);
        }

        return $result;
    }
}
