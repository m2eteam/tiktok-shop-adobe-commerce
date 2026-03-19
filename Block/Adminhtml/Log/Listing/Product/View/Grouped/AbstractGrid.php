<?php

namespace M2E\TikTokShop\Block\Adminhtml\Log\Listing\Product\View\Grouped;

use M2E\TikTokShop\Block\Adminhtml\Log\Listing\View;
use M2E\TikTokShop\Model\ResourceModel\Listing\Log as ListingLogResource;

abstract class AbstractGrid extends \M2E\TikTokShop\Block\Adminhtml\Log\Listing\Product\AbstractGrid
{
    protected array $nestedLogs = [];
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
        return View\Switcher::VIEW_MODE_GROUPED;
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->getColumn('description')
             ->setData('sortable', false);

        return $this;
    }

    protected function _prepareCollection()
    {
        $logCollection = $this->listingLogCollectionFactory->create();
        $logCollection->joinLeft(
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

        $this->applyFilters($logCollection);

        $logCollection->getSelect()
                      ->order(new \Zend_Db_Expr('main_table.id DESC'))
                      ->limit(1, $this->getMaxLastHandledRecordsCount() - 1);

        $lastAllowedLog = $logCollection->getFirstItem();

        if ($lastAllowedLog->getId() !== null) {
            $logCollection->getSelect()->limit($this->getMaxLastHandledRecordsCount());
            $this->addMaxAllowedLogsCountExceededNotification($lastAllowedLog->getCreateDate());
        } else {
            $logCollection->getSelect()
                          ->reset(\Magento\Framework\DB\Select::ORDER)
                          ->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
                          ->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        }

        $groupedCollection = $this->wrapperCollectionFactory->create();
        $groupedCollection->setConnection($this->resourceConnection->getConnection());
        $groupedCollection->getSelect()
                          ->reset()
                          ->from(
                              ['main_table' => $logCollection->getSelect()],
                              [
                                  'id' => sprintf('main_table.%s', ListingLogResource::COLUMN_ID),
                                  self::LISTING_PRODUCT_ID_FIELD => sprintf('main_table.%s', ListingLogResource::COLUMN_LISTING_PRODUCT_ID),
                                  self::LISTING_ID_FIELD => sprintf('main_table.%s', ListingLogResource::COLUMN_LISTING_ID),
                                  'product_id' => sprintf('main_table.%s', ListingLogResource::COLUMN_PRODUCT_ID),
                                  'action_id' => sprintf('main_table.%s', ListingLogResource::COLUMN_ACTION_ID),
                                  'action' => sprintf('main_table.%s', ListingLogResource::COLUMN_ACTION),
                                  'store_id' => sprintf('main_table.%s', \M2E\TikTokShop\Model\ResourceModel\Listing::COLUMN_STORE_ID),
                                  'listing_title' => sprintf('main_table.%s', ListingLogResource::COLUMN_LISTING_TITLE),
                                  'product_title' => sprintf('main_table.%s', ListingLogResource::COLUMN_PRODUCT_TITLE),
                                  'initiator' => sprintf('main_table.%s', ListingLogResource::COLUMN_INITIATOR),
                                  'additional_data' => sprintf('main_table.%s', ListingLogResource::COLUMN_ADDITIONAL_DATA),
                                  'create_date' => new \Zend_Db_Expr(sprintf('MAX(main_table.%s)', ListingLogResource::COLUMN_CREATE_DATE)),
                                  'description' => new \Zend_Db_Expr(sprintf('GROUP_CONCAT(main_table.%s)', ListingLogResource::COLUMN_DESCRIPTION)),
                                  'type' => new \Zend_Db_Expr(sprintf('MAX(main_table.%s)', ListingLogResource::COLUMN_TYPE)),
                                  'nested_log_ids' => new \Zend_Db_Expr(sprintf('GROUP_CONCAT(main_table.%s)', ListingLogResource::COLUMN_ID)),
                              ],
                          );

        $groupedCollection->getSelect()->group([ListingLogResource::COLUMN_LISTING_PRODUCT_ID, ListingLogResource::COLUMN_ACTION_ID]);

        $resultCollection = $this->wrapperCollectionFactory->create();
        $resultCollection->setConnection($this->resourceConnection->getConnection());
        $resultCollection->getSelect()->reset()->from(
            ['main_table' => $groupedCollection->getSelect()]
        );

        $this->setCollection($resultCollection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        if (!$this->getCollection()->getSize()) {
            return parent::_afterLoadCollection();
        }

        $logCollection = $this->listingLogCollectionFactory->create();

        $logCollection->getSelect()
                      ->reset(\Magento\Framework\DB\Select::COLUMNS)
                      ->columns([
                          'id',
                          self::LISTING_PRODUCT_ID_FIELD,
                          self::LISTING_ID_FIELD,
                          'action_id',
                          'description',
                          'type',
                          'create_date',
                      ])
                      ->order(new \Zend_Db_Expr('id DESC'));

        $nestedLogsIds = [];
        foreach ($this->getCollection()->getItems() as $log) {
            $nestedLogsIds[] = new \Zend_Db_Expr($log->getNestedLogIds());
        }

        $logCollection->getSelect()->where(
            new \Zend_Db_Expr('main_table.id IN (?)'),
            $nestedLogsIds
        );

        foreach ($logCollection->getItems() as $log) {
            $this->nestedLogs[$this->getLogHash($log)][] = $log;
        }

        $sortOrder = \M2E\TikTokShop\Block\Adminhtml\Log\Grid\LastActions::$actionsSortOrder;

        foreach ($this->nestedLogs as &$logs) {
            usort($logs, function ($a, $b) use ($sortOrder) {
                return $sortOrder[$a['type']] <=> $sortOrder[$b['type']];
            });
        }

        return parent::_afterLoadCollection();
    }

    public function callbackColumnDescription($value, $row, $column, $isExport)
    {
        $description = '';
        $nestedLogs = $this->nestedLogs[$this->getLogHash($row)];

        /** @var \M2E\TikTokShop\Model\Listing\Log $log */
        foreach ($nestedLogs as $log) {
            $messageType = '';
            $createDate = '';

            if (count($nestedLogs) > 1) {
                $messageType = $this->callbackColumnType(
                    '[' . $this->_getLogTypeList()[$log->getType()] . ']',
                    $log,
                    $column,
                    $isExport
                );
                $createDate = $this->_localeDate->formatDate($log->getCreateDate(), \IntlDateFormatter::MEDIUM, true);
            }

            $logDescription = parent::callbackColumnDescription(
                $log->getData($column->getIndex()),
                $log,
                $column,
                $isExport
            );

            $description .= <<<HTML
<div class="log-description-group">
    <span class="log-description">
        <span class="log-type">{$messageType}</span>
        {$logDescription}
    </span>
    <div class="log-date">{$createDate}</div>
</div>
HTML;
        }

        return $description;
    }
}
