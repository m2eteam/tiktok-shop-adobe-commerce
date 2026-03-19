<?php

namespace M2E\TikTokShop\Model\Product;

use M2E\TikTokShop\Model\ResourceModel\Listing as ListingResource;
use M2E\TikTokShop\Model\ResourceModel\Product as ListingProductResource;
use M2E\TikTokShop\Model\ResourceModel\Product\VariantSku as VariantSkuResource;
use M2E\TikTokShop\Model\ResourceModel\Promotion\Product as PromotionProductResource;

class Repository
{
    private ListingProductResource $listingProductResource;
    private ListingProductResource\CollectionFactory $listingProductCollectionFactory;
    private \M2E\TikTokShop\Model\ProductFactory $listingProductFactory;
    private \M2E\TikTokShop\Model\ResourceModel\Listing $listingResource;
    /** @var VariantSkuResource */
    private ListingProductResource\VariantSku $variantSkuResource;
    /** @var \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku\CollectionFactory */
    private ListingProductResource\VariantSku\CollectionFactory $variantSkuCollectionFactory;
    /** @var \M2E\TikTokShop\Model\Product\VariantSkuFactory */
    private VariantSkuFactory $variantSkuFactory;
    private \M2E\TikTokShop\Model\Product\AffectedProduct\Finder $affectedVariantSkuFinder;
    private \M2E\TikTokShop\Helper\Module\Database\Structure $dbStructureHelper;
    private \M2E\TikTokShop\Model\ResourceModel\Promotion\Product $promotionProductResource;

    public function __construct(
        VariantSkuFactory $variantSkuFactory,
        \M2E\TikTokShop\Model\ResourceModel\Listing $listingResource,
        ListingProductResource $listingProductResource,
        ListingProductResource\CollectionFactory $listingProductCollectionFactory,
        \M2E\TikTokShop\Model\ProductFactory $listingProductFactory,
        ListingProductResource\VariantSku $variantSkuResource,
        \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku\CollectionFactory $variantSkuCollectionFactory,
        \M2E\TikTokShop\Model\Product\AffectedProduct\Finder $affectedVariantSkuFinder,
        \M2E\TikTokShop\Helper\Module\Database\Structure $dbStructureHelper,
        \M2E\TikTokShop\Model\ResourceModel\Promotion\Product $promotionProductResource
    ) {
        $this->listingProductResource = $listingProductResource;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->listingProductFactory = $listingProductFactory;
        $this->listingResource = $listingResource;
        $this->variantSkuResource = $variantSkuResource;
        $this->variantSkuCollectionFactory = $variantSkuCollectionFactory;
        $this->variantSkuFactory = $variantSkuFactory;
        $this->affectedVariantSkuFinder = $affectedVariantSkuFinder;
        $this->dbStructureHelper = $dbStructureHelper;
        $this->promotionProductResource = $promotionProductResource;
    }

    public function create(\M2E\TikTokShop\Model\Product $product): void
    {
        $this->listingProductResource->save($product);
    }

    public function save(
        \M2E\TikTokShop\Model\Product $product
    ): \M2E\TikTokShop\Model\Product {
        $this->listingProductResource->save($product);

        return $product;
    }

    public function find(int $id): ?\M2E\TikTokShop\Model\Product
    {
        $listingProduct = $this->listingProductFactory->create();
        $this->listingProductResource->load($listingProduct, $id);

        if ($listingProduct->isObjectNew()) {
            return null;
        }

        return $listingProduct;
    }

    public function get(int $id): \M2E\TikTokShop\Model\Product
    {
        $listingProduct = $this->find($id);
        if ($listingProduct === null) {
            throw new \M2E\TikTokShop\Model\Exception\Logic('Listing Product not found.');
        }

        return $listingProduct;
    }

    public function getListingProductsByMagentoProductId(
        int $magentoProductId,
        array $listingFilters = [],
        array $listingProductFilters = []
    ): \M2E\TikTokShop\Model\Product\AffectedProduct\Collection {
        return $this->affectedVariantSkuFinder->find(
            $magentoProductId,
            $listingFilters,
            $listingProductFilters,
        );
    }

    public function delete(\M2E\TikTokShop\Model\Product $listingProduct): void
    {
        $this->listingProductResource->delete($listingProduct);
    }

    public function deleteVariantSku(\M2E\TikTokShop\Model\Product\VariantSku $variantSku): void
    {
        $this->variantSkuResource->delete($variantSku);
    }

    // ----------------------------------------

    /**
     * @return \M2E\TikTokShop\Model\Product[]
     */
    public function findByListing(\M2E\TikTokShop\Model\Listing $listing): array
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(
            ListingProductResource::COLUMN_LISTING_ID,
            ['eq' => $listing->getId()],
        );

        return array_values($collection->getItems());
    }

    public function findByListingAndMagentoProductId(
        \M2E\TikTokShop\Model\Listing $listing,
        int $magentoProductId
    ): ?\M2E\TikTokShop\Model\Product {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(
            ListingProductResource::COLUMN_LISTING_ID,
            ['eq' => $listing->getId()],
        );
        $collection->addFieldToFilter(
            ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID,
            ['eq' => $magentoProductId],
        );

        $product = $collection->getFirstItem();
        if ($product->isObjectNew()) {
            return null;
        }

        return $product;
    }

    /**
     * @return \M2E\TikTokShop\Model\Product[]
     */
    public function findByIds(array $listingProductsIds): array
    {
        if (empty($listingProductsIds)) {
            return [];
        }

        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(
            ListingProductResource::COLUMN_ID,
            ['in' => $listingProductsIds],
        );

        return array_values($collection->getItems());
    }

    /**
     * @return \M2E\TikTokShop\Model\Product[]
     */
    public function findByMagentoProductId(int $magentoProductId): array
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(
            ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID,
            ['eq' => $magentoProductId],
        );

        return array_values($collection->getItems());
    }

    /**
     * @param array $ttsProductsIds
     * @param int $accountId
     * @param int $shopId
     * @param int|null $listingId
     *
     * @return \M2E\TikTokShop\Model\Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function findByTtsProductIds(
        array $ttsProductsIds,
        int $accountId,
        int $shopId,
        ?int $listingId = null
    ): array {
        if (empty($ttsProductsIds)) {
            return [];
        }

        $collection = $this->listingProductCollectionFactory->create();
        $collection
            ->join(
                ['l' => $this->listingResource->getMainTable()],
                sprintf(
                    '`l`.%s = `main_table`.%s',
                    ListingResource::COLUMN_ID,
                    ListingProductResource::COLUMN_LISTING_ID,
                ),
                [],
            )
            ->addFieldToFilter(
                sprintf('main_table.%s', ListingProductResource::COLUMN_TTS_PRODUCT_ID),
                ['in' => $ttsProductsIds],
            )
            ->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ACCOUNT_ID), $accountId)
            ->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_SHOP_ID), $shopId);

        if ($listingId !== null) {
            $collection->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ID), $listingId);
        }

        return array_values($collection->getItems());
    }

    public function findByTtsProductId(
        string $ttsProductsId,
        int $accountId,
        int $shopId
    ): ?\M2E\TikTokShop\Model\Product {
        $collection = $this->listingProductCollectionFactory->create();
        $collection
            ->join(
                ['l' => $this->listingResource->getMainTable()],
                sprintf(
                    '`l`.%s = `main_table`.%s',
                    ListingResource::COLUMN_ID,
                    ListingProductResource::COLUMN_LISTING_ID,
                ),
                [],
            )
            ->addFieldToFilter(sprintf('main_table.%s', ListingProductResource::COLUMN_TTS_PRODUCT_ID), $ttsProductsId)
            ->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ACCOUNT_ID), $accountId)
            ->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_SHOP_ID), $shopId);

        $product = $collection->getFirstItem();
        if ($product->isObjectNew()) {
            return null;
        }

        return $product;
    }

    public function getCountListedProductsForListing(\M2E\TikTokShop\Model\Listing $listing): int
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection
            ->addFieldToFilter(ListingProductResource::COLUMN_LISTING_ID, $listing->getId())
            ->addFieldToFilter(ListingProductResource::COLUMN_STATUS, \M2E\TikTokShop\Model\Product::STATUS_LISTED);

        return (int)$collection->getSize();
    }

    public function hasListedProducts(): bool
    {
        return $this->getCountOfListedProducts() > 0;
    }

    public function getCountOfListedProducts(): int
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(
            ListingProductResource::COLUMN_STATUS,
            \M2E\TikTokShop\Model\Product::STATUS_LISTED
        );

        return (int)$collection->getSize();
    }

    public function getCountOfNotListedProducts(): int
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(
            ListingProductResource::COLUMN_STATUS,
            ['neq' => \M2E\TikTokShop\Model\Product::STATUS_LISTED]
        );

        return (int)$collection->getSize();
    }

    public function getTotalCountOfListingProducts(): int
    {
        $collection = $this->listingProductCollectionFactory->create();

        return (int)$collection->getSize();
    }

    // ----------------------------------------

    public function setCategoryTemplate(array $productsIds, int $templateCategoryId): void
    {
        if (empty($productsIds)) {
            return;
        }

        $this->listingProductResource
            ->getConnection()
            ->update(
                $this->listingProductResource->getMainTable(),
                [
                    ListingProductResource::COLUMN_TEMPLATE_CATEGORY_ID => $templateCategoryId,
                ],
                ['id IN (?)' => $productsIds],
            );
    }

    // ----------------------------------------

    /**
     * @param \M2E\TikTokShop\Model\Product\VariantSku[] $variantsSku
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createVariantsSku(array $variantsSku): void
    {
        foreach ($variantsSku as $variantSku) {
            $this->variantSkuResource->save($variantSku);
        }
    }

    /**
     * @param \M2E\TikTokShop\Model\Product\VariantSku[] $variantsSku
     *
     * @return void
     */
    public function saveVariantsSku(array $variantsSku): void
    {
        foreach ($variantsSku as $variantSku) {
            $this->saveVariantSku($variantSku);
        }
    }

    public function saveVariantSku(VariantSku $variantSku): void
    {
        $this->variantSkuResource->save($variantSku);
    }

    /**
     * @param \M2E\TikTokShop\Model\Product $product
     *
     * @return \M2E\TikTokShop\Model\Product\VariantSku[]
     */
    public function findVariantsByProduct(\M2E\TikTokShop\Model\Product $product): array
    {
        $collection = $this->variantSkuCollectionFactory->create();
        $collection->addFieldToFilter(VariantSkuResource::COLUMN_PRODUCT_ID, $product->getId());

        $items = array_values($collection->getItems());
        foreach ($items as $item) {
            $item->initProduct($product);
        }

        return $items;
    }

    public function findVariantSkyByProduct(int $productId): ?VariantSku
    {
        $variantSku = $this->variantSkuFactory->create();
        $this->variantSkuResource->load($variantSku, $productId, VariantSkuResource::COLUMN_PRODUCT_ID);

        if ($variantSku->isObjectNew()) {
            return null;
        }

        return $variantSku;
    }

    public function findVariantSkuByTtsProductIdAndSkuId(string $ttsProductId, string $skuId): ?VariantSku
    {
        $collection = $this->variantSkuCollectionFactory->create();
        $collection
            ->join(
                ['p' => $this->listingProductResource->getMainTable()],
                sprintf(
                    'main_table.%s = p.%s',
                    VariantSkuResource::COLUMN_PRODUCT_ID,
                    ListingProductResource::COLUMN_ID,
                ),
                [],
            )
            ->addFieldToFilter(sprintf('p.%s', $this->listingProductResource::COLUMN_TTS_PRODUCT_ID), $ttsProductId)
            ->addFieldToFilter(sprintf('main_table.%s', VariantSkuResource::COLUMN_SKU_ID), $skuId)
            ->setPageSize(1);

        $variantSku = $collection->getFirstItem();
        if ($variantSku->isObjectNew()) {
            return null;
        }

        return $variantSku;
    }

    // ----------------------------------------

    /**
     * @param int $listingId
     *
     * @return int[]
     */
    public function findMagentoProductIdsByListingId(int $listingId): array
    {
        $collection = $this->listingProductCollectionFactory->create();

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);

        $collection
            ->addFieldToSelect(ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID)
            ->addFieldToSelect(ListingProductResource::COLUMN_ID) // for load collection
            ->addFieldToFilter(ListingProductResource::COLUMN_LISTING_ID, $listingId);

        $result = [];
        foreach ($collection->getItems() as $product) {
            $result[] = $product->getMagentoProductId();
        }

        return $result;
    }

    // ----------------------------------------

    /**
     * @return \M2E\TikTokShop\Model\Product\VariantSku[]
     */
    public function findVariantSkusByMagentoProductId(int $magentoProductId): array
    {
        return $this->findVariantSkusByMagentoProductIds([$magentoProductId]);
    }

    /**
     * @return \M2E\TikTokShop\Model\Product\VariantSku[]
     */
    public function findVariantSkusByMagentoProductIds(array $magentoProductIds): array
    {
        if (empty($magentoProductIds)) {
            return [];
        }

        $collection = $this->variantSkuCollectionFactory->create();
        $collection->addFieldToFilter(
            VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID,
            ['in' => $magentoProductIds],
        );

        return array_values($collection->getItems());
    }

    /**
     * @return \M2E\TikTokShop\Model\Product\VariantSku[]
     */
    public function findActiveVariantSkusByMagentoProductIds(array $magentoProductIds): array
    {
        if (empty($magentoProductIds)) {
            return [];
        }

        $collection = $this->variantSkuCollectionFactory->create();
        $collection->addFieldToFilter(
            VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID,
            ['in' => $magentoProductIds],
        )->addFieldToFilter(VariantSkuResource::COLUMN_STATUS, \M2E\TikTokShop\Model\Product::STATUS_LISTED);

        return array_values($collection->getItems());
    }

    // ----------------------------------------

    /**
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     *
     * @return \M2E\TikTokShop\Model\Product[]
     */
    public function massActionSelectedProducts(\Magento\Ui\Component\MassAction\Filter $filter): array
    {
        $collection = $this->listingProductCollectionFactory->create();
        $filter->getCollection($collection);

        return array_values($collection->getItems());
    }

    /**
     * @return int[]
     */
    public function findRemovedMagentoProductIds(int $limit): array
    {
        $entityTableName = $this->dbStructureHelper->getTableNameWithPrefix('catalog_product_entity');

        $productCollection = $this->listingProductCollectionFactory->create();
        $productCollectionSelect = $productCollection->getSelect();
        $productCollectionSelect
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID)
            ->joinLeft(
                ['cpe' => $entityTableName],
                sprintf(
                    'cpe.entity_id = `main_table`.%s',
                    ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID,
                ),
                [],
            )
            ->where('cpe.entity_id IS NULL');

        $variantCollection = $this->variantSkuCollectionFactory->create();
        $variantCollectionSelect = $variantCollection->getSelect();
        $variantCollectionSelect
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID)
            ->joinLeft(
                ['cpe' => $entityTableName],
                sprintf(
                    'cpe.entity_id = `main_table`.%s',
                    ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID,
                ),
                [],
            )
            ->where('cpe.entity_id IS NULL');

        $unionSelect = $productCollection
            ->getConnection()
            ->select()
            ->union([$productCollectionSelect, $variantCollectionSelect])
            ->distinct()
            ->limit($limit);

        $result = [];
        foreach ($unionSelect->query()->fetchAll() as $row) {
            $result[] = (int)$row[ListingProductResource::COLUMN_MAGENTO_PRODUCT_ID];
        }

        return $result;
    }

    public function findIdsByListingId(int $listingId): array
    {
        $select = $this->listingProductResource->getConnection()
                                               ->select()
                                               ->from($this->listingProductResource->getMainTable(), 'id')
                                               ->where('listing_id = ?', $listingId);

        return array_column($select->query()->fetchAll(), 'id');
    }

    public function findActiveIdsByListingId(int $listingId): array
    {
        $select = $this->listingProductResource
            ->getConnection()
            ->select()
            ->from($this->listingProductResource->getMainTable(), ListingProductResource::COLUMN_ID)
            ->where(ListingProductResource::COLUMN_LISTING_ID . ' = ?', $listingId)
            ->where(VariantSkuResource::COLUMN_STATUS . ' = ?', \M2E\TikTokShop\Model\Product::STATUS_LISTED);

        return array_column($select->query()->fetchAll(), ListingProductResource::COLUMN_ID);
    }

    public function updateLastBlockingErrorDate(array $listingProductIds, \DateTime $dateTime): void
    {
        if (empty($listingProductIds)) {
            return;
        }

        $this->listingProductResource->getConnection()->update(
            $this->listingProductResource->getMainTable(),
            [ListingProductResource::COLUMN_LAST_BLOCKING_ERROR_DATE => $dateTime->format('Y-m-d H:i:s')],
            ['id IN (?)' => $listingProductIds]
        );
    }

    /**
     * @param \M2E\TikTokShop\Model\Promotion $promotion
     * @param string[] $specificsPromotionChannelProductsIds
     *
     * @return \M2E\TikTokShop\Model\Product[]
     */
    public function findProductsByPromotion(
        \M2E\TikTokShop\Model\Promotion $promotion,
        array $specificsPromotionChannelProductsIds = []
    ): array {
        if (!$promotion->isProductLevelByProduct()) {
            return [];
        }

        $collection = $this->listingProductCollectionFactory->create();

        $collection
            ->join(
                ['p' => $this->promotionProductResource->getMainTable()],
                sprintf(
                    '`p`.%s = `main_table`.%s',
                    PromotionProductResource::COLUMN_PRODUCT_ID,
                    ListingProductResource::COLUMN_TTS_PRODUCT_ID,
                ),
                [],
            );

        $collection->addFieldToFilter(
            'p.' . PromotionProductResource::COLUMN_PROMOTION_ID,
            $promotion->getId()
        );

        if (!empty($specificsPromotionChannelProductsIds)) {
            $collection->addFieldToFilter(
                ListingProductResource::COLUMN_TTS_PRODUCT_ID,
                ['in' => $specificsPromotionChannelProductsIds]
            );
        }

        return array_values($collection->getItems());
    }

    /**
     * @param \M2E\TikTokShop\Model\Promotion $promotion
     * @param array $specificsPromotionChannelProductsSkus
     *
     * @return \M2E\TikTokShop\Model\Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function findProductsWithVariantOnPromotionByPromotion(
        \M2E\TikTokShop\Model\Promotion $promotion,
        array $specificsPromotionChannelProductsSkus = []
    ): array {
        if (!$promotion->isProductLevelByVariation()) {
            return [];
        }

        $collection = $this->listingProductCollectionFactory->create();
        $collection->getSelect()->distinct(true);

        $collection
            ->join(
                ['v' => $this->variantSkuResource->getMainTable()],
                sprintf(
                    'main_table.%s = v.%s',
                    ListingProductResource::COLUMN_ID,
                    VariantSkuResource::COLUMN_PRODUCT_ID
                ),
                []
            );

        $collection
            ->join(
                ['p' => $this->promotionProductResource->getMainTable()],
                sprintf(
                    '`p`.%s = `v`.%s',
                    PromotionProductResource::COLUMN_SKU_ID,
                    VariantSkuResource::COLUMN_SKU_ID
                ),
                []
            );

        $collection->addFieldToFilter(
            'p.' . PromotionProductResource::COLUMN_PROMOTION_ID,
            $promotion->getId()
        );

        if (!empty($specificsPromotionChannelProductsSkus)) {
            $collection->addFieldToFilter(
                'v.' . VariantSkuResource::COLUMN_SKU_ID,
                ['in' => $specificsPromotionChannelProductsSkus]
            );
        }

        return array_values($collection->getItems());
    }

    public function getIds(int $fromId, int $limit): array
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter('id', ['gt' => $fromId]);
        $collection->getSelect()->order(['id ASC']);
        $collection->getSelect()->limit($limit);

        return array_map('intval', $collection->getColumnValues('id'));
    }

    /**
     * @return \M2E\TikTokShop\Model\Product[]
     */
    public function getByIds(array $productIds): array
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter('id', ['in' => $productIds]);

        return array_values($collection->getItems());
    }

    /**
     * @param int $templateCategoryId
     * @param int|null $limit
     *
     * @return \M2E\TikTokShop\Model\Product[]
     */
    public function findProductsForValidateCategoryAttributes(
        int $templateCategoryId,
        int $limit
    ): array {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(ListingProductResource::COLUMN_TEMPLATE_CATEGORY_ID, $templateCategoryId);
        $collection->addFieldToFilter(ListingProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES, ['null' => true]);
        $collection->addFieldToFilter(
            ListingProductResource::COLUMN_STATUS,
            \M2E\TikTokShop\Model\Product::STATUS_NOT_LISTED
        );

        $collection->getSelect()->limit($limit);

        return array_values($collection->getItems());
    }

    /**
     * @param int $templateCategoryId
     *
     * @return int
     */
    public function getCountProductsByCategoryId(int $templateCategoryId): int
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(ListingProductResource::COLUMN_TEMPLATE_CATEGORY_ID, $templateCategoryId);

        return (int)$collection->getSize();
    }

    public function resetCategoryAttributesValidationData(int $categoryId): void
    {
        $this->listingProductResource
            ->getConnection()
            ->update(
                $this->listingProductResource->getMainTable(),
                [
                    ListingProductResource::COLUMN_IS_VALID_CATEGORY_ATTRIBUTES => null,
                    ListingProductResource::COLUMN_CATEGORY_ATTRIBUTES_ERRORS => null,
                ],
                [
                    sprintf('%s = %d', ListingProductResource::COLUMN_TEMPLATE_CATEGORY_ID, $categoryId),
                ],
            );
    }
}
