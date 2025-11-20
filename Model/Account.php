<?php

namespace M2E\TikTokShop\Model;

use M2E\TikTokShop\Model\ResourceModel\Account as AccountResource;

class Account extends \M2E\TikTokShop\Model\ActiveRecord\AbstractModel
{
    private \M2E\TikTokShop\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory;
    private Shop\Repository $shopRepository;

    /** @var \M2E\TikTokShop\Model\Shop[] */
    private array $shops;
    private Account\Settings\UnmanagedListings $unmanagedListingSettings;
    private Account\Settings\Order $ordersSettings;
    private Account\Settings\InvoicesAndShipment $invoiceAndShipmentSettings;

    public function __construct(
        \M2E\TikTokShop\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory,
        Shop\Repository $shopRepository,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
        );
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->shopRepository = $shopRepository;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(\M2E\TikTokShop\Model\ResourceModel\Account::class);
    }

    public function init(
        string $title,
        string $sellerName,
        string $openId,
        string $serverHash,
        \M2E\TikTokShop\Model\Account\Settings\UnmanagedListings $unmanagedListingsSettings,
        \M2E\TikTokShop\Model\Account\Settings\Order $orderSettings,
        \M2E\TikTokShop\Model\Account\Settings\InvoicesAndShipment $invoicesAndShipmentSettings
    ): self {
        $this
            ->setTitle($title)
            ->setSellerName($sellerName)
            ->setData(AccountResource::COLUMN_OPEN_ID, $openId)
            ->setData(AccountResource::COLUMN_SERVER_HASH, $serverHash)
            ->setUnmanagedListingSettings($unmanagedListingsSettings)
            ->setOrdersSettings($orderSettings)
            ->setInvoiceAndShipmentSettings($invoicesAndShipmentSettings);

        return $this;
    }

    // ----------------------------------------

    public function getId(): int
    {
        return (int)parent::getId();
    }

    /**
     * @param Shop[] $shops
     *
     * @return $this
     */
    public function setShops(array $shops): self
    {
        $this->shops = $shops;
        foreach ($this->shops as $shop) {
            $shop->setAccount($this);
        }

        return $this;
    }

    /**
     * @return \M2E\TikTokShop\Model\Shop[]
     */
    public function getShops(): array
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->shops)) {
            return $this->shops;
        }

        $this->shops = $this->shopRepository->findForAccount($this->getId());
        foreach ($this->shops as $shop) {
            $shop->setAccount($this);
        }

        return $this->shops;
    }

    /**
     * @return \M2E\TikTokShop\Model\Listing[]
     */
    public function getListings(): array
    {
        $listingCollection = $this->listingCollectionFactory->create();
        $listingCollection->addFieldToFilter('account_id', $this->getId());

        return $listingCollection->getItems();
    }

    // ----------------------------------------

    public function setTitle(string $title): self
    {
        $this->setData(AccountResource::COLUMN_TITLE, $title);

        return $this;
    }

    public function getTitle()
    {
        return $this->getData(AccountResource::COLUMN_TITLE);
    }

    public function getServerHash()
    {
        return $this->getData(AccountResource::COLUMN_SERVER_HASH);
    }

    public function getOpenId(): string
    {
        return (string)$this->getData(AccountResource::COLUMN_OPEN_ID);
    }

    public function getSellerName(): string
    {
        return (string)$this->getData(AccountResource::COLUMN_SELLER_NAME);
    }

    public function setSellerName(string $sellerName): self
    {
        $this->setData(AccountResource::COLUMN_SELLER_NAME, $sellerName);

        return $this;
    }

    public function setUnmanagedListingSettings(
        \M2E\TikTokShop\Model\Account\Settings\UnmanagedListings $settings
    ): self {
        $this->unmanagedListingSettings = $settings;
        $this
            ->setData(AccountResource::COLUMN_OTHER_LISTINGS_SYNCHRONIZATION, (int)$settings->isSyncEnabled())
            ->setData(AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_MODE, (int)$settings->isMappingEnabled())
            ->setData(
                AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_SETTINGS,
                json_encode(
                    [
                        'sku' => $settings->getMappingBySkuSettings(),
                        'title' => $settings->getMappingByTitleSettings(),
                        'item_id' => $settings->getMappingByItemIdSettings(),
                    ],
                ),
            )
            ->setData(
                AccountResource::COLUMN_OTHER_LISTINGS_RELATED_STORES,
                json_encode($settings->getRelatedStores()),
            );

        return $this;
    }

    public function getUnmanagedListingSettings(): \M2E\TikTokShop\Model\Account\Settings\UnmanagedListings
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->unmanagedListingSettings)) {
            return $this->unmanagedListingSettings;
        }

        $mappingSettings = $this->getData(AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_SETTINGS);
        $mappingSettings = json_decode($mappingSettings, true);

        $settings = new \M2E\TikTokShop\Model\Account\Settings\UnmanagedListings();

        return $this->unmanagedListingSettings = $settings
            ->createWithSync((bool)$this->getData(AccountResource::COLUMN_OTHER_LISTINGS_SYNCHRONIZATION))
            ->createWithMapping((bool)$this->getData(AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_MODE))
            ->createWithMappingSettings(
                $mappingSettings['sku'] ?? [],
                $mappingSettings['title'] ?? [],
                $mappingSettings['item_id'] ?? [],
            )
            ->createWithRelatedStores(
                json_decode($this->getData(AccountResource::COLUMN_OTHER_LISTINGS_RELATED_STORES), true),
            );
    }

    public function setOrdersSettings(\M2E\TikTokShop\Model\Account\Settings\Order $settings): self
    {
        $this->ordersSettings = $settings;

        $data = $settings->toArray();

        $this->setData(AccountResource::COLUMN_MAGENTO_ORDERS_SETTINGS, json_encode($data));

        return $this;
    }

    public function getOrdersSettings(): \M2E\TikTokShop\Model\Account\Settings\Order
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->ordersSettings)) {
            return $this->ordersSettings;
        }

        $data = json_decode($this->getData(AccountResource::COLUMN_MAGENTO_ORDERS_SETTINGS), true);

        $settings = new \M2E\TikTokShop\Model\Account\Settings\Order();

        return $this->ordersSettings = $settings->createWith($data);
    }

    public function setInvoiceAndShipmentSettings(
        \M2E\TikTokShop\Model\Account\Settings\InvoicesAndShipment $settings
    ): self {
        $this->invoiceAndShipmentSettings = $settings;

        $this
            ->setData(AccountResource::COLUMN_CREATE_MAGENTO_INVOICE, (int)$settings->isCreateMagentoInvoice())
            ->setData(AccountResource::COLUMN_CREATE_MAGENTO_SHIPMENT, (int)$settings->isCreateMagentoShipment())
            ->setData(
                AccountResource::COLUMN_MAP_SHIPPING_PROVIDER_BY_CUSTOM_CARRIER_TITLE,
                (int)$settings->isMapShippingProviderByCustomCarrierTitle()
            );

        return $this;
    }

    public function getInvoiceAndShipmentSettings(): \M2E\TikTokShop\Model\Account\Settings\InvoicesAndShipment
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->invoiceAndShipmentSettings)) {
            return $this->invoiceAndShipmentSettings;
        }

        $settings = new \M2E\TikTokShop\Model\Account\Settings\InvoicesAndShipment();

        return $this->invoiceAndShipmentSettings = $settings
            ->createWithMagentoInvoice((bool)$this->getData(AccountResource::COLUMN_CREATE_MAGENTO_INVOICE))
            ->createWithMagentoShipment((bool)$this->getData(AccountResource::COLUMN_CREATE_MAGENTO_SHIPMENT))
            ->createWithMapShippingProviderByCustomCarrierTitle(
                (bool)$this->getData(AccountResource::COLUMN_MAP_SHIPPING_PROVIDER_BY_CUSTOM_CARRIER_TITLE)
            );
    }

    // ----------------------------------------

    public function getCreateData(): \DateTime
    {
        $value = $this->getData(AccountResource::COLUMN_CREATE_DATE);

        return \M2E\Core\Helper\Date::createDateGmt($value);
    }

    public function hasAnyEuShop(): bool
    {
        foreach ($this->getShops() as $shop) {
            if ($shop->getRegion()->isEU()) {
                return true;
            }
        }

        return false;
    }
}
