<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Cron\Task\Order\Sync;

class OrdersProcessorFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        \M2E\TikTokShop\Model\Shop $shop,
        \M2E\TikTokShop\Model\Synchronization\LogService $logService
    ): OrdersProcessor {
        return $this->objectManager->create(
            OrdersProcessor::class,
            ['logService' => $logService, 'shop' => $shop],
        );
    }
}