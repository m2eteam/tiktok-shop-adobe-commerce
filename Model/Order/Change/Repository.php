<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Order\Change;

use M2E\TikTokShop\Model\ResourceModel\Order\Change as OrderChangeResource;

class Repository
{
    private OrderChangeResource $resource;
    private \M2E\TikTokShop\Model\ResourceModel\Order\Change\CollectionFactory $collectionFactory;
    private \M2E\TikTokShop\Model\ResourceModel\Order $orderResource;

    public function __construct(
        OrderChangeResource $resource,
        \M2E\TikTokShop\Model\ResourceModel\Order\Change\CollectionFactory $collectionFactory,
        \M2E\TikTokShop\Model\ResourceModel\Order $orderResource
    ) {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->orderResource = $orderResource;
    }

    public function create(\M2E\TikTokShop\Model\Order\Change $change): void
    {
        $this->resource->save($change);
    }

    public function save(\M2E\TikTokShop\Model\Order\Change $change): void
    {
        $this->resource->save($change);
    }

    public function delete(\M2E\TikTokShop\Model\Order\Change $change): void
    {
        $this->resource->delete($change);
    }

    public function findExist(int $orderId, string $action, string $hash): ?\M2E\TikTokShop\Model\Order\Change
    {
        $collection = $this->collectionFactory->create();
        $collection
            ->addFieldToFilter(
                OrderChangeResource::COLUMN_ORDER_ID,
                ['eq' => $orderId]
            )
            ->addFieldToFilter(
                OrderChangeResource::COLUMN_ACTION,
                ['eq' => $action]
            )
            ->addFieldToFilter(
                OrderChangeResource::COLUMN_HASH,
                ['eq' => $hash]
            )
            ->setPageSize(1);

        /** @var \M2E\TikTokShop\Model\Order\Change $change */
        $change = $collection->getFirstItem();
        if ($change->isObjectNew()) {
            return null;
        }

        return $change;
    }

    /**
     * @return \M2E\TikTokShop\Model\Order\Change[]
     */
    public function findShippingNotStarted(int $orderId): array
    {
        $collection = $this->collectionFactory->create();
        $collection
            ->addFieldToFilter(
                OrderChangeResource::COLUMN_ORDER_ID,
                ['eq' => $orderId]
            )
            ->addFieldToFilter(
                OrderChangeResource::COLUMN_ACTION,
                ['eq' => \M2E\TikTokShop\Model\Order\Change::ACTION_UPDATE_SHIPPING]
            )
            ->addFieldToFilter(
                OrderChangeResource::COLUMN_PROCESSING_ATTEMPT_COUNT,
                ['eq' => 0]
            );

        return array_values($collection->getItems());
    }

    /**
     * @param \M2E\TikTokShop\Model\Account $account
     * @param int $limit
     *
     * @return \M2E\TikTokShop\Model\Order\Change[]
     */
    public function findShippingReadyForProcess(\M2E\TikTokShop\Model\Account $account, int $limit): array
    {
        return $this->findReadyForProcessByAction(
            \M2E\TikTokShop\Model\Order\Change::ACTION_UPDATE_SHIPPING,
            $account,
            $limit
        );
    }

    /**
     * @return \M2E\TikTokShop\Model\Order\Change[]
     */
    public function findCanceledReadyForProcess(\M2E\TikTokShop\Model\Account $account, int $limit): array
    {
        return $this->findReadyForProcessByAction(\M2E\TikTokShop\Model\Order\Change::ACTION_CANCEL, $account, $limit);
    }

    /**
     * @param string $action
     * @param \M2E\TikTokShop\Model\Account $account
     * @param int $limit
     *
     * @return \M2E\TikTokShop\Model\Order\Change[]
     */
    private function findReadyForProcessByAction(
        string $action,
        \M2E\TikTokShop\Model\Account $account,
        int $limit
    ): array {
        $collection = $this->collectionFactory->create();
        $collection->joinInner(
            ['orders' => $this->orderResource->getMainTable()],
            sprintf(
                '`orders`.`%s` = `main_table`.`%s`',
                \M2E\TikTokShop\Model\ResourceModel\Order::COLUMN_ID,
                OrderChangeResource::COLUMN_ORDER_ID,
            ),
            [
                'account_id' => \M2E\TikTokShop\Model\ResourceModel\Order::COLUMN_ACCOUNT_ID,
            ],
        );
        $collection->addFieldToFilter(
            sprintf('orders.%s', \M2E\TikTokShop\Model\ResourceModel\Order::COLUMN_ACCOUNT_ID),
            ['eq' => $account->getId()],
        );

        $hourAgo = \M2E\Core\Helper\Date::createCurrentGmt();
        $hourAgo->modify('-1 hour');

        $collection->addFieldToFilter(
            [
                OrderChangeResource::COLUMN_PROCESSING_ATTEMPT_DATE,
                OrderChangeResource::COLUMN_PROCESSING_ATTEMPT_DATE,
            ],
            [
                ['null' => true],
                ['lteq' => $hourAgo->format('Y-m-d H:i:s')],
            ],
        );

        $collection->addFieldToFilter(
            OrderChangeResource::COLUMN_ACTION,
            ['eq' => $action],
        );

        $collection->setPageSize($limit);
        $collection->getSelect()->group(['main_table.id']);

        return array_values($collection->getItems());
    }

    public function incrementAttemptCount(array $ids, int $increment = 1): void
    {
        if ($increment <= 0) {
            return;
        }

        $this->resource->getConnection()->update(
            $this->resource->getMainTable(),
            [
                OrderChangeResource::COLUMN_PROCESSING_ATTEMPT_COUNT => new \Zend_Db_Expr(
                    'processing_attempt_count + ' . $increment
                ),
                OrderChangeResource::COLUMN_PROCESSING_ATTEMPT_DATE => \M2E\Core\Helper\Date::createCurrentGmt()->format(
                    'Y-m-d H:i:s'
                ),
            ],
            [
                sprintf('%s IN (?)', OrderChangeResource::COLUMN_ID) => $ids,
            ]
        );
    }

    /**
     * @param int $count
     *
     * @return \M2E\TikTokShop\Model\Order\Change[]
     */
    public function getItemsExceededProcessingAttemptCount(int $count): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            OrderChangeResource::COLUMN_PROCESSING_ATTEMPT_COUNT,
            ['gteq' => $count]
        );

        return array_values($collection->getItems());
    }
}
