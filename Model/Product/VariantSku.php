<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Product;

use M2E\TikTokShop\Model\ResourceModel\Product\VariantSku as VariantSkuResource;

class VariantSku extends \M2E\TikTokShop\Model\ActiveRecord\AbstractModel implements
    \M2E\TikTokShop\Model\ProductInterface
{
    private int $calculatedQty;

    private \M2E\TikTokShop\Model\Product $product;

    private \M2E\TikTokShop\Model\Magento\Product\CacheFactory $magentoProductFactory;
    /** @var \M2E\TikTokShop\Model\Product\Repository */
    private Repository $productRepository;
    /** @var \M2E\TikTokShop\Model\Product\PriceCalculatorFactory */
    private PriceCalculatorFactory $priceCalculatorFactory;
    /** @var \M2E\TikTokShop\Model\Product\QtyCalculatorFactory */
    private QtyCalculatorFactory $qtyCalculatorFactory;
    private \M2E\TikTokShop\Model\Magento\Product $magentoProduct;
    private \M2E\TikTokShop\Model\ProductPromotionService $productPromotionService;

    public function __construct(
        \M2E\TikTokShop\Model\Magento\Product\CacheFactory $magentoProductFactory,
        Repository $productRepository,
        \M2E\TikTokShop\Model\Product\PriceCalculatorFactory $priceCalculatorFactory,
        \M2E\TikTokShop\Model\Product\QtyCalculatorFactory $qtyCalculatorFactory,
        \M2E\TikTokShop\Model\ProductPromotionService $productPromotionService,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context, $registry);

        $this->magentoProductFactory = $magentoProductFactory;
        $this->productRepository = $productRepository;
        $this->priceCalculatorFactory = $priceCalculatorFactory;
        $this->qtyCalculatorFactory = $qtyCalculatorFactory;
        $this->productPromotionService = $productPromotionService;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(VariantSkuResource::class);
    }

    public function init(\M2E\TikTokShop\Model\Product $product, int $magentoProductId): self
    {
        $this
            ->setData(VariantSkuResource::COLUMN_PRODUCT_ID, $product->getId())
            ->setMagentoProductId($magentoProductId);

        $product->addVariant($this);
        $this->initProduct($product);

        return $this;
    }

    public function fillFromUnmanagedVariant(
        \M2E\TikTokShop\Model\UnmanagedProduct\VariantSku $unmanagedVariantProduct
    ): self {
        $this
            ->setMagentoProductId($unmanagedVariantProduct->getMagentoProductId())
            ->setSkuId($unmanagedVariantProduct->getSkuId())
            ->setOnlineSku($unmanagedVariantProduct->getSku())
            ->setOnlineQty($unmanagedVariantProduct->getQty())
            ->setOnlineCurrentPrice($unmanagedVariantProduct->getCurrentPrice())
            ->setStatus($unmanagedVariantProduct->getStatus())
            ->setOnlineIdentifier($unmanagedVariantProduct->getIdentifier());

        return $this;
    }

    // ----------------------------------------

    public function initProduct(\M2E\TikTokShop\Model\Product $product): void
    {
        $this->product = $product;
    }

    // ----------------------------------------

    public function getListing(): \M2E\TikTokShop\Model\Listing
    {
        return $this->getProduct()->getListing();
    }

    public function getProduct(): \M2E\TikTokShop\Model\Product
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return $this->product ?? ($this->product = $this->productRepository->get($this->getProductId()));
    }

    public function getSellingFormatTemplate(): \M2E\TikTokShop\Model\Template\SellingFormat
    {
        return $this->getProduct()->getSellingFormatTemplate();
    }

    public function getMagentoProduct(): \M2E\TikTokShop\Model\Magento\Product\Cache
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->magentoProduct)) {
            $this->magentoProduct = $this->magentoProductFactory->create();
            $this->magentoProduct->setProductId($this->getMagentoProductId());
            $this->magentoProduct->setStoreId($this->getListing()->getStoreId());
            $this->magentoProduct->setStatisticId($this->getId());
        }

        return $this->magentoProduct;
    }

    // ----------------------------------------

    public function getId(): int
    {
        return (int)parent::getId();
    }

    public function getProductId(): int
    {
        return (int)$this->getData(VariantSkuResource::COLUMN_PRODUCT_ID);
    }

    private function setMagentoProductId(int $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID, $value);

        return $this;
    }

    public function getMagentoProductId(): int
    {
        return (int)$this->getData(VariantSkuResource::COLUMN_MAGENTO_PRODUCT_ID);
    }

    public function isStatusNotListed(): bool
    {
        return $this->getStatus() === \M2E\TikTokShop\Model\Product::STATUS_NOT_LISTED;
    }

    public function isStatusListed(): bool
    {
        return $this->getStatus() === \M2E\TikTokShop\Model\Product::STATUS_LISTED;
    }

    public function isStatusInactive(): bool
    {
        return $this->getStatus() === \M2E\TikTokShop\Model\Product::STATUS_INACTIVE;
    }

    public function changeStatusToNoListed(): self
    {
        $this->setStatus(\M2E\TikTokShop\Model\Product::STATUS_NOT_LISTED)
             ->resetSkuId()
             ->resetOnlineData()
             ->resetOnlineIdentifier();

        return $this;
    }

    public function changeStatusToListed(): self
    {
        $this->setStatus(\M2E\TikTokShop\Model\Product::STATUS_LISTED);

        return $this;
    }

    public function changeStatusToInactive(): self
    {
        if (!$this->isStatusListed()) {
            return $this;
        }

        $this->setStatus(\M2E\TikTokShop\Model\Product::STATUS_INACTIVE);

        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->setData(VariantSkuResource::COLUMN_STATUS, $status);

        return $this;
    }

    public function getStatus(): int
    {
        return (int)$this->getData(VariantSkuResource::COLUMN_STATUS);
    }

    private function resetSkuId(): self
    {
        $this->setData(VariantSkuResource::COLUMN_SKU_ID, null);

        return $this;
    }

    public function setSkuId(string $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_SKU_ID, $value);

        return $this;
    }

    public function getSkuId(): string
    {
        return (string)$this->getData(VariantSkuResource::COLUMN_SKU_ID);
    }

    public function setOnlineWarehouseId(?string $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_WAREHOUSE_ID, $value);

        return $this;
    }

    public function getOnlineWarehouseId(): ?string
    {
        return $this->getData(VariantSkuResource::COLUMN_ONLINE_WAREHOUSE_ID);
    }

    public function setOnlineSku(string $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_SKU, $value);

        return $this;
    }

    public function getOnlineSku(): ?string
    {
        return $this->getData(VariantSkuResource::COLUMN_ONLINE_SKU);
    }

    public function setOnlineCurrentPrice(float $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_PRICE, $value);

        return $this;
    }

    public function getOnlineCurrentPrice(): float
    {
        return (float)$this->getData(VariantSkuResource::COLUMN_ONLINE_PRICE);
    }

    //region Online Identifier
    public function setOnlineIdentifier(?\M2E\TikTokShop\Model\Product\VariantSku\Identifier $identifier): self
    {
        if ($identifier === null) {
            return $this->resetOnlineIdentifier();
        }

        $this->setData(VariantSkuResource::COLUMN_ONLINE_IDENTIFIER_ID, $identifier->getId());
        $this->setData(VariantSkuResource::COLUMN_ONLINE_IDENTIFIER_TYPE, $identifier->getType());

        return $this;
    }

    public function getOnlineIdentifier(): ?\M2E\TikTokShop\Model\Product\VariantSku\Identifier
    {
        $id = $this->getData(VariantSkuResource::COLUMN_ONLINE_IDENTIFIER_ID);
        $type = $this->getData(VariantSkuResource::COLUMN_ONLINE_IDENTIFIER_TYPE);

        if (!$id || !$type) {
            return null;
        }

        return new \M2E\TikTokShop\Model\Product\VariantSku\Identifier($id, $type);
    }

    private function resetOnlineIdentifier(): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_IDENTIFIER_ID, null);
        $this->setData(VariantSkuResource::COLUMN_ONLINE_IDENTIFIER_TYPE, null);

        return $this;
    }

    //endregion

    public function setOnlineQty(int $value): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_QTY, $value);

        return $this;
    }

    public function getOnlineQty(): int
    {
        return (int)$this->getData(VariantSkuResource::COLUMN_ONLINE_QTY);
    }

    public function setOnlineImage(string $image): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_IMAGE, $image);

        return $this;
    }

    public function getOnlineImage(): string
    {
        return (string)$this->getData(VariantSkuResource::COLUMN_ONLINE_IMAGE);
    }

    private function resetOnlineData(): self
    {
        $this->setData(VariantSkuResource::COLUMN_ONLINE_SKU, null)
             ->setData(VariantSkuResource::COLUMN_ONLINE_QTY, null)
             ->setData(VariantSkuResource::COLUMN_ONLINE_PRICE, null)
             ->setData(VariantSkuResource::COLUMN_ONLINE_IMAGE, null);

        return $this;
    }

    public function getOnlineData(): VariantSku\OnlineData
    {
        return new \M2E\TikTokShop\Model\Product\VariantSku\OnlineData(
            $this->getId(),
            $this->getOnlineQty(),
            $this->getOnlineCurrentPrice(),
            $this->getOnlineSku(),
        );
    }

    // ----------------------------------------

    public function getSku(): string
    {
        $sku = $this->getMagentoProduct()->getSku();

        if (\mb_strlen($sku) > \M2E\TikTokShop\Helper\Component\TikTokShop::ITEM_SKU_MAX_LENGTH) {
            $sku = 'RANDOM_' . sha1($sku);
        }

        return $sku;
    }

    /**
     * @return float|int
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function getFixedPrice()
    {
        $src = $this->getProduct()->getSellingFormatTemplate()->getFixedPriceSource();
        $priceModifier = $this->getProduct()->getSellingFormatTemplate()->getFixedPriceModifier();

        $price = $this->getCalculatedPriceWithModifier($src, $priceModifier);

        if ($this->getListing()->getShop()->getRegion()->isRegionCodeHU()) {
            $price = round($price);
        }

        return $price;
    }

    private function getCalculatedPriceWithModifier(array $src, array $modifier)
    {
        $calculator = $this->priceCalculatorFactory->create($this);
        $calculator->setSource($src);
        $calculator->setModifier($modifier);

        return $calculator->getProductValue();
    }

    public function getQty(): int
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->calculatedQty)) {
            return $this->calculatedQty;
        }

        $calculator = $this->qtyCalculatorFactory->create($this);

        return $this->calculatedQty = $calculator->getProductValue();
    }

    public function getImage(): ?\M2E\TikTokShop\Model\Magento\Product\Image
    {
        $mainImageSource = $this->getProduct()->getDescriptionTemplate()->getImageMainSource();
        if ($mainImageSource['mode'] === \M2E\TikTokShop\Model\Template\Description::IMAGE_MAIN_MODE_PRODUCT) {
            $imageAttributeCode = 'image';
        } else {
            $imageAttributeCode = $mainImageSource['attribute'];
        }

        return $this->getMagentoProduct()->getImage($imageAttributeCode);
    }

    // ----------------------------------------

    public function getSyncPolicy(): \M2E\TikTokShop\Model\Template\Synchronization
    {
        return $this->getProduct()->getSynchronizationTemplate();
    }

    public function hasActiveOrNotStartPromotion(): bool
    {
        return $this->productPromotionService->isProductVariantOnPromotion(
            $this,
            $this->getProduct()->getAccount(),
            $this->getProduct()->getShop()
        );
    }
}
