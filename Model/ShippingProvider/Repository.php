<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\ShippingProvider;

use M2E\TikTokShop\Model\ResourceModel\ShippingProvider as ShippingProviderResource;
use M2E\TikTokShop\Model\ResourceModel\ShippingProvider\CollectionFactory as ShippingProviderCollectionFactory;

class Repository
{
    private ShippingProviderCollectionFactory $collectionFactory;
    private ShippingProviderResource $shippingProviderResource;

    public function __construct(
        ShippingProviderCollectionFactory $collectionFactory,
        ShippingProviderResource $ShippingProviderResource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->shippingProviderResource = $ShippingProviderResource;
    }

    public function create(\M2E\TikTokShop\Model\ShippingProvider $shippingProvider): void
    {
        $this->shippingProviderResource->save($shippingProvider);
    }

    public function save(\M2E\TikTokShop\Model\ShippingProvider $shippingProvider): void
    {
        $this->shippingProviderResource->save($shippingProvider);
    }

    /**
     * @return \M2E\TikTokShop\Model\ShippingProvider[]
     */
    public function getByAccountShopWarehouse(
        \M2E\TikTokShop\Model\Account $account,
        \M2E\TikTokShop\Model\Shop $shop,
        \M2E\TikTokShop\Model\Warehouse $warehouse
    ): array {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ShippingProviderResource::COLUMN_ACCOUNT_ID, ['eq' => $account->getId()])
                   ->addFieldToFilter(ShippingProviderResource::COLUMN_SHOP_ID, ['eq' => $shop->getId()])
                   ->addFieldToFilter(ShippingProviderResource::COLUMN_WAREHOUSE_ID, ['eq' => $warehouse->getId()]);

        return array_values($collection->getItems());
    }

    /**
     * @return \M2E\TikTokShop\Model\ShippingProvider[]
     */
    public function getByAccountShopDeliveryOption(
        \M2E\TikTokShop\Model\Account $account,
        \M2E\TikTokShop\Model\Shop $shop,
        string $deliveryOptionId
    ): array {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ShippingProviderResource::COLUMN_ACCOUNT_ID, ['eq' => $account->getId()])
                   ->addFieldToFilter(ShippingProviderResource::COLUMN_SHOP_ID, ['eq' => $shop->getId()])
                   ->addFieldToFilter(ShippingProviderResource::COLUMN_DELIVERY_OPTION_ID, ['eq' => $deliveryOptionId]);

        return array_values($collection->getItems());
    }

    public function removeByAccountId(int $accountId): void
    {
        $collection = $this->collectionFactory->create();
        $collection->getConnection()->delete(
            $collection->getMainTable(),
            ['account_id = ?' => $accountId],
        );
    }

    /**
     * @return \M2E\TikTokShop\Model\ShippingProvider[]
     */
    public function findByShippingProviderIds(array $shippingProviderIds): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_ID,
            ['in' => $shippingProviderIds]
        );

        return array_values($collection->getItems());
    }

    public function findByShippingProviderId(string $shippingProviderId): ?\M2E\TikTokShop\Model\ShippingProvider
    {
        $providers = $this->findByShippingProviderIds([$shippingProviderId]);
        if (empty($providers)) {
            return null;
        }

        return reset($providers);
    }

    public function findExistedShippingProvider(
        \M2E\TikTokShop\Model\ShippingProvider $object
    ): ?\M2E\TikTokShop\Model\ShippingProvider {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ShippingProviderResource::COLUMN_ACCOUNT_ID, $object->getAccountId());
        $collection->addFieldToFilter(ShippingProviderResource::COLUMN_SHOP_ID, $object->getShopId());
        $collection->addFieldToFilter(ShippingProviderResource::COLUMN_WAREHOUSE_ID, $object->getWarehouseId());
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_DELIVERY_OPTION_ID,
            $object->getDeliveryOptionId()
        );
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_ID,
            $object->getShippingProviderId()
        );

        /** @var \M2E\TikTokShop\Model\ShippingProvider $shippingProvider */
        $shippingProvider = $collection->getFirstItem();

        if ($shippingProvider->isObjectNew()) {
            return null;
        }

        return $shippingProvider;
    }

    public function findByAccountShopWarehouseAndTitle(
        int $accountId,
        int $shopId,
        int $warehouseId,
        string $carrierTitle
    ): ?\M2E\TikTokShop\Model\ShippingProvider {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_ACCOUNT_ID,
            ['eq' => $accountId]
        );
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_SHOP_ID,
            ['eq' => $shopId]
        );
        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_WAREHOUSE_ID,
            ['eq' => $warehouseId]
        );

        $collection->addFieldToFilter(
            ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_NAME,
            ['eq' => $carrierTitle]
        );

        /** @var \M2E\TikTokShop\Model\ShippingProvider $shippingProvider */
        $shippingProvider = $collection->getFirstItem();

        if ($shippingProvider->isObjectNew()) {
            return null;
        }

        return $shippingProvider;
    }

    public function delete(\M2E\TikTokShop\Model\ShippingProvider $shippingProvider): void
    {
        $this->shippingProviderResource->delete($shippingProvider);
    }
}
