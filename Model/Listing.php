<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model;

use M2E\TikTokShop\Model\ResourceModel\Listing as ListingResource;

class Listing extends \M2E\TikTokShop\Model\ActiveRecord\AbstractModel
{
    private const ADDITIONAL_DATA_CATEGORY_MAPPING_KEY = 'tiktok_shop_category_mapping';

    public const LOCK_NICK = 'listing';

    public const INSTRUCTION_TYPE_PRODUCT_ADDED = 'listing_product_added';
    public const INSTRUCTION_INITIATOR_ADDING_PRODUCT = 'adding_product_to_listing';

    public const INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER = 'listing_product_moved_from_other';
    public const INSTRUCTION_INITIATOR_MOVING_PRODUCT_FROM_OTHER = 'moving_product_from_other_to_listing';

    public const INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING = 'listing_product_moved_from_listing';
    public const INSTRUCTION_INITIATOR_MOVING_PRODUCT_FROM_LISTING = 'moving_product_from_listing_to_listing';

    public const INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING = 'listing_product_remap_from_listing';
    public const INSTRUCTION_INITIATOR_REMAPING_PRODUCT_FROM_LISTING = 'remaping_product_from_listing_to_listing';

    public const INSTRUCTION_TYPE_CHANGE_LISTING_STORE_VIEW = 'change_listing_store_view';
    public const INSTRUCTION_INITIATOR_CHANGED_LISTING_STORE_VIEW = 'changed_listing_store_view';

    public const INSTRUCTION_TYPE_CHANGE_WAREHOUSE = 'change_listing_warehouse';
    public const INSTRUCTION_INITIATOR_CHANGED_LISTING_WAREHOUSE = 'changed_listing_warehouse';

    public const CREATE_LISTING_SESSION_DATA = 'tts_listing_create';

    private ?\M2E\TikTokShop\Model\Account $account = null;
    private ?\M2E\TikTokShop\Model\Shop $shop = null;
    private ?\M2E\TikTokShop\Model\Warehouse $warehouse = null;
    private \M2E\TikTokShop\Model\Template\SellingFormat $templateSellingFormat;
    private \M2E\TikTokShop\Model\Template\Synchronization $templateSynchronization;
    private \M2E\TikTokShop\Model\Template\Description $templateDescription;

    private \M2E\TikTokShop\Model\Product\Repository $listingProductRepository;
    private \M2E\TikTokShop\Model\Account\Repository $accountRepository;
    private \M2E\TikTokShop\Model\Shop\Repository $shopRepository;
    private \M2E\TikTokShop\Model\Template\SellingFormat\Repository $sellingFormatTemplateRepository;
    private \M2E\TikTokShop\Model\Template\Description\Repository $descriptionTemplateRepository;
    private \M2E\TikTokShop\Model\Template\Synchronization\Repository $synchronizationTemplateRepository;
    private \M2E\TikTokShop\Model\Warehouse\Repository $warehouseRepository;

    public function __construct(
        \M2E\TikTokShop\Model\Product\Repository $listingProductRepository,
        \M2E\TikTokShop\Model\Account\Repository $accountRepository,
        \M2E\TikTokShop\Model\Shop\Repository $shopRepository,
        \M2E\TikTokShop\Model\Template\SellingFormat\Repository $sellingFormatTemplateRepository,
        \M2E\TikTokShop\Model\Template\Description\Repository $descriptionTemplateRepository,
        \M2E\TikTokShop\Model\Template\Synchronization\Repository $synchronizationTemplateRepository,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \M2E\TikTokShop\Model\Warehouse\Repository $warehouseRepository,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data,
        );
        $this->listingProductRepository = $listingProductRepository;
        $this->accountRepository = $accountRepository;
        $this->shopRepository = $shopRepository;
        $this->sellingFormatTemplateRepository = $sellingFormatTemplateRepository;
        $this->descriptionTemplateRepository = $descriptionTemplateRepository;
        $this->synchronizationTemplateRepository = $synchronizationTemplateRepository;
        $this->warehouseRepository = $warehouseRepository;
    }

    // ----------------------------------------

    public function _construct()
    {
        parent::_construct();
        $this->_init(\M2E\TikTokShop\Model\ResourceModel\Listing::class);
    }

    // ----------------------------------------

    public function getAccount(): \M2E\TikTokShop\Model\Account
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->account)) {
            return $this->account;
        }

        return $this->account = $this->accountRepository->get($this->getAccountId());
    }

    // ---------------------------------------

    public function getShop(): \M2E\TikTokShop\Model\Shop
    {
        if ($this->shop !== null) {
            return $this->shop;
        }

        return $this->shop = $this->shopRepository->get($this->getShopId());
    }

    // ----------------------------------------

    public function getWarehouse(): \M2E\TikTokShop\Model\Warehouse
    {
        if ($this->warehouse !== null) {
            return $this->warehouse;
        }

        return $this->warehouse = $this->warehouseRepository->get($this->getWarehouseId());
    }

    // ----------------------------------------

    /**
     * @return \M2E\TikTokShop\Model\Product[]
     */
    public function getProducts(): array
    {
        $products = $this->listingProductRepository->findByListing($this);
        foreach ($products as $product) {
            $product->initListing($this);
        }

        return $products;
    }

    // ----------------------------------------

    /**
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function getTemplateSellingFormat(): Template\SellingFormat
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->templateSellingFormat)) {
            $this->templateSellingFormat = $this->sellingFormatTemplateRepository
                ->get($this->getTemplateSellingFormatId());
        }

        return $this->templateSellingFormat;
    }

    /**
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function getTemplateSynchronization(): Template\Synchronization
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->templateSynchronization)) {
            $this->templateSynchronization = $this->synchronizationTemplateRepository
                ->get($this->getTemplateSynchronizationId());
        }

        return $this->templateSynchronization;
    }

    /**
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function getTemplateDescription(): Template\Description
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->templateDescription)) {
            $this->templateDescription = $this->descriptionTemplateRepository
                ->get($this->getTemplateDescriptionId());
        }

        return $this->templateDescription;
    }

    // ----------------------------------------

    public function getId(): int
    {
        return (int)parent::getId();
    }

    public function getTitle(): string
    {
        return (string)$this->getData(ListingResource::COLUMN_TITLE);
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(ListingResource::COLUMN_ACCOUNT_ID);
    }

    public function getShopId(): int
    {
        return (int)$this->getData(ListingResource::COLUMN_SHOP_ID);
    }

    public function hasWarehouse(): bool
    {
        return $this->getWarehouseId() !== null;
    }

    public function getWarehouseId(): ?int
    {
        return $this->getData(ListingResource::COLUMN_WAREHOUSE_ID)
            ? (int)$this->getData(ListingResource::COLUMN_WAREHOUSE_ID)
            : null;
    }

    public function setWarehouseId(int $warehouseId): self
    {
        return $this->setData(ListingResource::COLUMN_WAREHOUSE_ID, $warehouseId);
    }

    public function getStoreId(): int
    {
        return (int)$this->getData(ListingResource::COLUMN_STORE_ID);
    }

    public function getCreateDate()
    {
        return $this->getData(ListingResource::COLUMN_CREATE_DATE);
    }

    public function getUpdateDate()
    {
        return $this->getData(ListingResource::COLUMN_UPDATE_DATE);
    }

    public function setTemplateSellingFormatId(int $sellingFormatTemplateId): void
    {
        $this->setData(ListingResource::COLUMN_TEMPLATE_SELLING_FORMAT_ID, $sellingFormatTemplateId);
    }

    public function getTemplateSellingFormatId(): int
    {
        return (int)$this->getData(ListingResource::COLUMN_TEMPLATE_SELLING_FORMAT_ID);
    }

    public function setTemplateDescriptionId(int $descriptionTemplateId): void
    {
        $this->setData(ListingResource::COLUMN_TEMPLATE_DESCRIPTION_ID, $descriptionTemplateId);
    }

    public function getTemplateDescriptionId(): int
    {
        return (int)$this->getData(ListingResource::COLUMN_TEMPLATE_DESCRIPTION_ID);
    }

    public function setTemplateSynchronizationId(int $synchronizationTemplateId): void
    {
        $this->setData(ListingResource::COLUMN_TEMPLATE_SYNCHRONIZATION_ID, $synchronizationTemplateId);
    }

    public function getTemplateSynchronizationId(): int
    {
        return (int)$this->getData(ListingResource::COLUMN_TEMPLATE_SYNCHRONIZATION_ID);
    }

    // ---------------------------------------

    public function getAdditionalData(): array
    {
        $data = $this->getData(ListingResource::COLUMN_ADDITIONAL_DATA);
        if ($data === null) {
            return [];
        }

        return json_decode($data, true);
    }

    public function setAdditionalData(array $additionalData): void
    {
        $this->setData(
            ListingResource::COLUMN_ADDITIONAL_DATA,
            json_encode($additionalData, JSON_THROW_ON_ERROR),
        );
    }

    public function setStoreId(int $id): void
    {
        $this->setData(ListingResource::COLUMN_STORE_ID, $id);
    }

    /**
     * @return \M2E\TikTokShop\Model\Listing\CategoryMappingItem[]
     */
    public function getCategoryMappingItems(): array
    {
        $additionalData = $this->getAdditionalData();
        $rawItems = $additionalData[self::ADDITIONAL_DATA_CATEGORY_MAPPING_KEY] ?? [];

        $mappingItems = [];
        foreach ($rawItems as $rawItem) {
            $mappingItems[] = \M2E\TikTokShop\Model\Listing\CategoryMappingItem::createFromArray($rawItem);
        }

        return $mappingItems;
    }

    /**
     * @param \M2E\TikTokShop\Model\Listing\CategoryMappingItem[] $categoryMappingItems
     */
    public function setCategoryMappingItems(array $categoryMappingItems): void
    {
        $additionalData = $this->getAdditionalData();
        $mappingsToSave = [];
        foreach ($categoryMappingItems as $categoryMappingItem) {
            $mappingsToSave[] = $categoryMappingItem->toArray();
        }

        $additionalData[self::ADDITIONAL_DATA_CATEGORY_MAPPING_KEY] = $mappingsToSave;

        $this->setAdditionalData($additionalData);
    }
}
