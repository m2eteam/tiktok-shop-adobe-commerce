<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Order;

class AddressParserFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        \M2E\TikTokShop\Model\Shop\Region $region,
        array $serverData
    ): \M2E\TikTokShop\Model\TikTokShop\Order\BaseAddressParser {
        $arguments = [
            'serverData' => $serverData,
        ];

        if ($region->isRegionCodeUS()) {
            return $this->objectManager
                ->create(\M2E\TikTokShop\Model\TikTokShop\Order\AddressParser\US::class, $arguments);
        }

        return $this->objectManager
            ->create(\M2E\TikTokShop\Model\TikTokShop\Order\BaseAddressParser::class, $arguments);
    }
}
