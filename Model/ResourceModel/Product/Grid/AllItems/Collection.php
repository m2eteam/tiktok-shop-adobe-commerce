<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\ResourceModel\Product\Grid\AllItems;

use M2E\TikTokShop\Model\ResourceModel\Account as AccountResource;
use M2E\TikTokShop\Model\ResourceModel\Listing as ListingResource;
use M2E\TikTokShop\Model\ResourceModel\Product as ListingProductResource;
use M2E\TikTokShop\Model\ResourceModel\Product as ProductResource;
use M2E\TikTokShop\Model\ResourceModel\Product\VariantSku as VariantSkuResource;
use M2E\TikTokShop\Model\ResourceModel\Promotion as PromotionResource;
use M2E\TikTokShop\Model\ResourceModel\Promotion\Product as PromotionProductResource;
use M2E\TikTokShop\Model\ResourceModel\Shop as ShopResource;
use M2E\TikTokShop\Model\ResourceModel\Tag as TagResource;
use M2E\TikTokShop\Model\ResourceModel\Tag\ListingProduct\Relation as TagProductRelationResource;
use Magento\Framework\Api\Search\SearchResultInterface;

class Collection extends \Magento\Framework\Data\Collection implements SearchResultInterface
{
    use \M2E\TikTokShop\Model\ResourceModel\SearchResultTrait;

    private const FIELDS_SEARCH_IN_VARIANTS = ['entity_id', 'sku', 'name'];

    public const PRIMARY_COLUMN = 'product_id';
    public const FILTER_BY_ERROR_CODE_FILED_NAME = 'error_code'; // see ui xml

    private bool $isAlreadyFilteredByErrorCode = false;
    private array $variantSkuFilters = [];

    /** @var \M2E\TikTokShop\Model\ResourceModel\Product */
    private ProductResource $listingProductResource;
    /** @var \M2E\TikTokShop\Model\ResourceModel\Listing */
    private ListingResource $listingResource;
    /** @var \M2E\TikTokShop\Model\ResourceModel\Shop */
    private ShopResource $shopResource;
    /** @var \M2E\TikTokShop\Model\ResourceModel\Account */
    private AccountResource $accountResource;
    private \M2E\TikTokShop\Model\ResourceModel\Magento\Product\Collection $wrappedCollection;
    private \M2E\TikTokShop\Model\Product\Ui\RuntimeStorage $productUiRuntimeStorage;
    private \M2E\TikTokShop\Model\ResourceModel\Tag\ListingProduct\Relation $tagProductRelationResource;
    /** @var \M2E\TikTokShop\Model\ResourceModel\Tag */
    private TagResource $tagResource;
    private bool $isGetAllItemsFromFilter = false;
    private \M2E\TikTokShop\Model\ResourceModel\Promotion\Product $promotionProductResource;
    private \M2E\TikTokShop\Model\ResourceModel\Promotion $promotionResource;
    private \M2E\TikTokShop\Model\Shop\RegionCollection $regionCollection;
    private \M2E\TikTokShop\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory;
    /** @var VariantSkuResource */
    private ProductResource\VariantSku $variantSkuResource;

    public function __construct(
        ProductResource $listingProductResource,
        ListingResource $listingResource,
        ShopResource $shopResource,
        AccountResource $accountResource,
        TagProductRelationResource $tagProductRelationResource,
        TagResource $tagResource,
        \M2E\TikTokShop\Model\Product\Ui\RuntimeStorage $productUiRuntimeStorage,
        \M2E\TikTokShop\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \M2E\TikTokShop\Model\ResourceModel\Promotion\Product $promotionProductResource,
        \M2E\TikTokShop\Model\ResourceModel\Promotion $promotionResource,
        \M2E\TikTokShop\Model\Shop\RegionCollection $regionCollection,
        VariantSkuResource $variantSkuResource,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
    ) {
        parent::__construct($entityFactory);
        $this->listingProductResource = $listingProductResource;
        $this->listingResource = $listingResource;
        $this->shopResource = $shopResource;
        $this->accountResource = $accountResource;
        $this->productUiRuntimeStorage = $productUiRuntimeStorage;
        $this->wrappedCollection = $magentoProductCollectionFactory->create();
        $this->tagProductRelationResource = $tagProductRelationResource;
        $this->tagResource = $tagResource;
        $this->promotionProductResource = $promotionProductResource;
        $this->promotionResource = $promotionResource;
        $this->regionCollection = $regionCollection;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->variantSkuResource = $variantSkuResource;
        $this->prepareCollection();
    }

    private function prepareCollection(): void
    {
        $this->wrappedCollection->setItemObjectClass(ProductResource\Grid\AllItems\Entity::class);

        $this->wrappedCollection->setListingProductModeOn('listing_product', ListingResource::COLUMN_ID);

        $this->wrappedCollection->getSelect()->distinct();

        $this->wrappedCollection->addAttributeToSelect('sku');
        $this->wrappedCollection->addAttributeToSelect('name');

        $this->wrappedCollection->joinTable(
            ['listing_product' => $this->listingProductResource->getMainTable()],
            sprintf('%s = entity_id', ProductResource::COLUMN_MAGENTO_PRODUCT_ID),
            [
                self::PRIMARY_COLUMN => ProductResource::COLUMN_ID,
                'product_' . ProductResource::COLUMN_IS_SIMPLE => ProductResource::COLUMN_IS_SIMPLE,
                'product_' . ProductResource::COLUMN_STATUS => ProductResource::COLUMN_STATUS,
                'product_' . ProductResource::COLUMN_LISTING_ID => ProductResource::COLUMN_LISTING_ID,
                'product_' . ProductResource::COLUMN_ONLINE_QTY => ProductResource::COLUMN_ONLINE_QTY,
                'product_' . ProductResource::COLUMN_ONLINE_MIN_PRICE => ProductResource::COLUMN_ONLINE_MIN_PRICE,
                'product_' . ProductResource::COLUMN_ONLINE_MAX_PRICE => ProductResource::COLUMN_ONLINE_MAX_PRICE,
                'product_' . ProductResource::COLUMN_TTS_PRODUCT_ID => ProductResource::COLUMN_TTS_PRODUCT_ID,
                'product_' . ProductResource::COLUMN_ONLINE_TITLE => ProductResource::COLUMN_ONLINE_TITLE,
                'product_' . ProductResource::COLUMN_TEMPLATE_CATEGORY_ID => ProductResource::COLUMN_TEMPLATE_CATEGORY_ID,
            ],
        );

        $this->wrappedCollection->joinTable(
            ['listing' => $this->listingResource->getMainTable()],
            sprintf('%s = product_%s', ListingResource::COLUMN_ID, ProductResource::COLUMN_LISTING_ID),
            [
                'listing_' . ListingResource::COLUMN_STORE_ID => ListingResource::COLUMN_STORE_ID,
                'listing_' . ListingResource::COLUMN_ACCOUNT_ID => ListingResource::COLUMN_ACCOUNT_ID,
                'listing_' . ListingResource::COLUMN_TITLE => ListingResource::COLUMN_TITLE,
                'listing_' . ListingResource::COLUMN_SHOP_ID => ListingResource::COLUMN_SHOP_ID,
                'listing_' . ListingResource::COLUMN_TEMPLATE_SELLING_FORMAT_ID => ListingResource::COLUMN_TEMPLATE_SELLING_FORMAT_ID,
                'listing_' . ListingResource::COLUMN_TEMPLATE_DESCRIPTION_ID => ListingResource::COLUMN_TEMPLATE_DESCRIPTION_ID,
                'listing_' . ListingResource::COLUMN_TEMPLATE_SYNCHRONIZATION_ID => ListingResource::COLUMN_TEMPLATE_SYNCHRONIZATION_ID,
            ],
        );

        $this->wrappedCollection->joinTable(
            ['shop' => $this->shopResource->getMainTable()],
            sprintf('%s = listing_%s', ShopResource::COLUMN_ID, ListingResource::COLUMN_SHOP_ID),
            [
                'shop_' . ShopResource::COLUMN_REGION => ShopResource::COLUMN_REGION,
                'shop_' . ShopResource::COLUMN_SHOP_NAME => ShopResource::COLUMN_SHOP_NAME,
                'shop_currency' => $this->createShopCurrencyExpression('shop'),
            ],
        );

        $this->wrappedCollection->joinTable(
            ['account' => $this->accountResource->getMainTable()],
            sprintf('%s = listing_%s', AccountResource::COLUMN_ID, ListingResource::COLUMN_ACCOUNT_ID),
            [
                'account_' . AccountResource::COLUMN_SELLER_NAME => AccountResource::COLUMN_SELLER_NAME,
            ],
        );

        $now = \M2E\Core\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        $select = $this->promotionProductResource->getConnection()->select();
        $select->from(
            $this->promotionProductResource->getMainTable(),
            [
                'promotion_product_id' => PromotionProductResource::COLUMN_PRODUCT_ID,
                'promotion_id' => PromotionProductResource::COLUMN_PROMOTION_ID,
            ]
        );
        $select->group('promotion_product_id');

        $this->wrappedCollection->getSelect()->joinLeft(
            ['promotion_product' => new \Zend_Db_Expr('(' . $select . ')')],
            sprintf(
                '%s = promotion_product.promotion_product_id',
                ListingProductResource::COLUMN_TTS_PRODUCT_ID
            ),
            [
                'promotion_id' => 'promotion_id',
            ]
        );

        $this->wrappedCollection->getSelect()->joinLeft(
            ['promotion' => $this->promotionResource->getMainTable()],
            'promotion.id = promotion_product.promotion_id',
            [
                'promotion_start_date' => PromotionResource::COLUMN_START_DATE,
                'promotion_end_date' => PromotionResource::COLUMN_END_DATE,
            ]
        );

        $this->wrappedCollection->getSelect()->columns([
            'has_promotion' => new \Zend_Db_Expr(
                "IF(
            promotion_product.promotion_product_id IS NOT NULL
            AND promotion.start_date <= '{$now}'
            AND promotion.end_date >= '{$now}',
            true,
            false
        )"
            ),
        ]);
    }

    public function getItems()
    {
        $this->joinVariantSkuCollectionIfNeed();

        $items = $this->wrappedCollection->getItems();
        $productIds = [];
        foreach ($items as $item) {
            $productIds[] = (int)$item['product_id'];
        }

        if (!$this->isGetAllItemsFromFilter) {
            $this->productUiRuntimeStorage->loadByIds(array_unique($productIds));
        }

        return $items;
    }

    public function getProducts(): array
    {
        return $this->productUiRuntimeStorage->getAll();
    }

    public function getSelect()
    {
        return $this->wrappedCollection->getSelect();
    }

    // ----------------------------------------

    public function addFieldToFilter($field, $condition)
    {
        if (in_array($field, self::FIELDS_SEARCH_IN_VARIANTS, true)) {
            $this->addFilterForVariantSku($field, $condition);

            return $this;
        }

        if ($field === 'product_online_price') {
            $this->buildFilterByPrice($condition);

            return $this;
        }

        if ($field === 'on_promotion') {
            $this->buildFilterByPromotion($condition);

            return $this;
        }

        if ($field === self::FILTER_BY_ERROR_CODE_FILED_NAME) {
            $this->addFilterByTag($condition);

            return $this;
        }

        $this->wrappedCollection->addFieldToFilter($field, $condition);

        return $this;
    }

    private function addFilterForVariantSku($field, $condition)
    {
        $this->variantSkuFilters[] = [
            'field' => $field,
            'condition' => $condition
        ];
    }

    private function buildFilterByPrice($condition): void
    {
        if (isset($condition['gteq'])) {
            $field = 'product_' . ProductResource::COLUMN_ONLINE_MIN_PRICE;
            $this->wrappedCollection->addFieldToFilter($field, $condition);
        }

        if (isset($condition['lteq'])) {
            $field = 'product_' . ProductResource::COLUMN_ONLINE_MAX_PRICE;
            $this->wrappedCollection->addFieldToFilter($field, $condition);
        }
    }

    private function buildFilterByPromotion($condition): void
    {
        $conditionValue = (int)$condition['eq'];
        $now = \M2E\Core\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');

        if ($conditionValue === \M2E\TikTokShop\Ui\Select\YesNoAnyOption::OPTION_YES) {
            $this->wrappedCollection->getSelect()->where(
                'promotion_product.promotion_product_id IS NOT NULL
            AND promotion.start_date <= ?
            AND promotion.end_date >= ?',
                $now
            );
        } else {
            $this->wrappedCollection->getSelect()->where(
                'promotion_product.promotion_product_id IS NULL
            OR promotion.start_date > ?
            OR promotion.end_date < ?',
                $now
            );
        }
    }

    private function addFilterByTag($condition): void
    {
        $errorCode = null;
        if (isset($condition['eq'])) {
            $errorCode = [$condition['eq']];
        } elseif (isset($condition['in'])) {
            $errorCode = $condition['in'];
        }

        if ($errorCode === null) {
            return;
        }

        if (!$this->isAlreadyFilteredByErrorCode) {
            $this->wrappedCollection->joinTable(
                ['tag_product_relation' => $this->tagProductRelationResource->getMainTable()],
                sprintf(
                    '%s = %s',
                    TagProductRelationResource::COLUMN_LISTING_PRODUCT_ID,
                    self::PRIMARY_COLUMN,
                ),
                [
                    'tag_product_relation_id' => TagProductRelationResource::COLUMN_LISTING_PRODUCT_ID,
                    'tag_product_relation_tag_id' => TagProductRelationResource::COLUMN_TAG_ID,
                ],
            );

            $this->wrappedCollection->joinTable(
                ['tag' => $this->tagResource->getMainTable()],
                sprintf(
                    '%s = tag_product_relation_tag_id',
                    TagResource::COLUMN_ID,
                ),
                ['tag_id' => TagResource::COLUMN_ID],
            );

            $this->isAlreadyFilteredByErrorCode = true;
        }

        $this->wrappedCollection->getSelect()
                                ->where(sprintf('tag.%s in (?)', TagResource::COLUMN_ERROR_CODE), $errorCode);
    }

    // ----------------------------------------

    public function setPageSize($size)
    {
        if ($size === false) {
            $this->isGetAllItemsFromFilter = true;
        }

        $this->wrappedCollection->setPageSize($size);

        return $this;
    }

    public function setCurPage($page)
    {
        $this->wrappedCollection->setCurPage($page);

        return $this;
    }

    public function setOrder($field, $direction = \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
    {
        if ($field === 'product_online_price') {
            if ($direction === \Magento\Framework\Data\Collection::SORT_ORDER_ASC) {
                $field = 'product_online_min_price';
            } else {
                $field = 'product_online_max_price';
            }
        } elseif ($field === 'column_title') {
            $field = 'name';
        }

        $this->wrappedCollection->setOrder($field, $direction);

        return $this;
    }

    public function getTotalCount(): int
    {
        return $this->wrappedCollection->getSize();
    }

    private function createShopCurrencyExpression(string $shopTableAlias): string
    {
        $expressionParts = [];
        foreach ($this->regionCollection->getAll() as $region) {
            $expressionParts[] = sprintf(
                "WHEN `%s`.`%s` = '%s' THEN '%s'",
                $shopTableAlias,
                ShopResource::COLUMN_REGION,
                $region->getRegionCode(),
                $region->getCurrency()
            );
        }

        $currencyExpression = new \Magento\Framework\DB\Sql\Expression(
            sprintf('(CASE %s END)', implode(' ', $expressionParts))
        );

        return (string)$currencyExpression;
    }

    // ----------------------------------------

    private function createVariantSkuCollection(): \M2E\TikTokShop\Model\ResourceModel\Magento\Product\Collection
    {
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->distinct(true);
        $collection->joinTable(
            ['variant_sku' => $this->variantSkuResource->getMainTable()],
            sprintf('%s = entity_id', VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID),
            [VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID]
        );

        foreach ($this->variantSkuFilters as $filter) {
            $collection->addFieldToFilter($filter['field'], $filter['condition']);
        }

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'founded_in_variation' => new \Zend_Db_Expr('1'),
            'product_id' => sprintf('variant_sku.%s', VariantSkuResource::COLUMN_PRODUCT_ID),
        ]);

        return $collection;
    }

    /**
     * JOIN variant_sku table and append to main collection two columns.
     * - founded_in_variation - 1 if product founded in variant_sku table;
     * - founded_in_parent - 1 if product founded in product table
     *
     * @return void
     * @throws \Zend_Db_Select_Exception
     */
    private function joinVariantSkuCollectionIfNeed(): void
    {
        if (empty($this->variantSkuFilters)) {
            return;
        }

        $variantSkuCollection = $this->createVariantSkuCollection();

        $this->wrappedCollection->getSelect()->joinLeft(
            ['variants' => $variantSkuCollection->getSelectSql()],
            sprintf('variants.product_id = listing_product.%s', ProductResource::COLUMN_ID),
            ['founded_in_variation' => 'founded_in_variation'],
        );

        $andSqlConditionParts = [];
        foreach ($this->variantSkuFilters as $filter) {
            $andSqlConditionParts[] = $this->wrappedCollection
                ->getAttributeConditionSql($filter['field'], $filter['condition']);
        }

        $andConditionSql = '(' . implode(') AND (', $andSqlConditionParts) . ')';

        $this->wrappedCollection
            ->getSelect()
            ->columns(['founded_in_parent' => new \Zend_Db_Expr($andConditionSql)])
            ->where('(' . $andConditionSql . ') OR variants.founded_in_variation = 1');
    }
}
