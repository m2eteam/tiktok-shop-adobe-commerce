<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\ResourceModel\Listing\Log;

use M2E\TikTokShop\Model\ResourceModel\Listing\Log\Collection as ListingLogCollection;

class CollectionFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): ListingLogCollection
    {
        return $this->objectManager->create(ListingLogCollection::class);
    }
}
