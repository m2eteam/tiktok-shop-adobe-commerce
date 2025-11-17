<?php

namespace M2E\TikTokShop\Model\HealthStatus\Task\Orders;

use M2E\TikTokShop\Model\HealthStatus\Task\IssueType;
use M2E\TikTokShop\Model\HealthStatus\Task\Result as TaskResult;
use M2E\TikTokShop\Model\Order;

class MagentoCreationFailed extends IssueType
{
    private TaskResult\Factory $resultFactory;
    private \Magento\Framework\UrlInterface $urlBuilder;
    private \M2E\TikTokShop\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory;

    public function __construct(
        \M2E\TikTokShop\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \M2E\TikTokShop\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        parent::__construct();
        $this->resultFactory = $resultFactory;
        $this->urlBuilder = $urlBuilder;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function process()
    {
        $result = $this->resultFactory->create($this);
        $result->setTaskResult(TaskResult::STATE_SUCCESS);

        if ($failedOrders = $this->getCountOfFailedOrders()) {
            $result->setTaskResult(TaskResult::STATE_WARNING);
            $result->setTaskData($failedOrders);
            $result->setTaskMessage(
                (string)__(
                    'During the last 24 hours, %extension_title has not created Magento ' .
                    'orders for <strong>%failed_orders_count</strong> imported Channel orders. ' .
                    'See the <a target="_blank" href="%url">Order Log</a> for more details.',
                    [
                        'failed_orders_count' => $failedOrders,
                        'url' => $this->urlBuilder->getUrl(
                            '*/tiktokshop_log_order/index',
                            ['magento_order_failed' => true],
                        ),
                        'extension_title' => \M2E\TikTokShop\Helper\Module::getExtensionTitle()
                    ],
                )
            );
        }

        return $result;
    }

    private function getCountOfFailedOrders()
    {
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('- 1 days');

        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('magento_order_id', ['null' => true]);
        $collection->addFieldToFilter('magento_order_creation_failure', Order::MAGENTO_ORDER_CREATION_FAILED_YES);
        $collection->addFieldToFilter(
            'magento_order_creation_latest_attempt_date',
            ['gt' => $backToDate->format('Y-m-d H:i:s')]
        );

        return $collection->getSize();
    }
}
