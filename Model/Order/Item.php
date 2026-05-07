<?php

namespace M2E\TikTokShop\Model\Order;

use M2E\TikTokShop\Model\Order\ReturnRequest\Status as ReturnRequestStatus;
use M2E\TikTokShop\Model\ResourceModel\Order\Item as OrderItemResource;

class Item extends \M2E\TikTokShop\Model\ActiveRecord\AbstractModel
{
    public const ITEM_STATUS_UNPAID = 0;
    public const ITEM_STATUS_AWAITING_SHIPMENT = 1;
    public const ITEM_STATUS_AWAITING_COLLECTION = 2;
    public const ITEM_STATUS_IN_TRANSIT = 3;
    public const ITEM_STATUS_DELIVERED = 4;
    public const ITEM_STATUS_COMPLETED = 5;
    public const ITEM_STATUS_CANCELLED = 6;
    public const ITEM_STATUS_UNKNOWN = 7;

    public const SHIPPING_IS_IN_PROGRESS_NO = 0;
    public const SHIPPING_IS_IN_PROGRESS_YES = 1;

    private \M2E\TikTokShop\Model\Order $order;
    private ?\M2E\TikTokShop\Model\Magento\Product $magentoProduct = null;
    private ?\M2E\TikTokShop\Model\Order\Item\ProxyObject $proxy = null;

    private \M2E\TikTokShop\Model\Magento\ProductFactory $magentoProductFactory;

    // ----------------------------------------

    private \M2E\TikTokShop\Model\Order\Item\BundleSkuFinderFactory $bundleSkuFinderFactory;
    private \M2E\TikTokShop\Model\Order\Item\ProxyObjectFactory $proxyObjectFactory;
    private \M2E\Core\Helper\Magento\Store $magentoStoreHelper;
    private \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $magentoProductCollectionFactory;
    private \M2E\TikTokShop\Model\Product\Repository $listingProductRepository;
    private \M2E\TikTokShop\Model\Product\VariantSku $variantSku;
    private \M2E\TikTokShop\Model\Order\Item\ProductAssignService $productAssignService;
    /** @var \M2E\TikTokShop\Model\Order\Repository */
    private Repository $orderRepository;

    public function __construct(
        \M2E\TikTokShop\Model\Order\Item\BundleSkuFinderFactory $bundleSkuFinderFactory,
        \M2E\TikTokShop\Model\Order\Repository $orderRepository,
        \M2E\TikTokShop\Model\Order\Item\ProductAssignService $productAssignService,
        \M2E\TikTokShop\Model\Product\Repository $listingProductRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $magentoProductCollectionFactory,
        \M2E\Core\Helper\Magento\Store $magentoStoreHelper,
        \M2E\TikTokShop\Model\Order\Item\ProxyObjectFactory $proxyObjectFactory,
        \M2E\TikTokShop\Model\Magento\ProductFactory $magentoProductFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
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
        $this->bundleSkuFinderFactory = $bundleSkuFinderFactory;
        $this->magentoProductFactory = $magentoProductFactory;
        $this->proxyObjectFactory = $proxyObjectFactory;
        $this->magentoStoreHelper = $magentoStoreHelper;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->listingProductRepository = $listingProductRepository;
        $this->productAssignService = $productAssignService;
        $this->orderRepository = $orderRepository;
    }

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(OrderItemResource::class);
    }

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        unset($this->order);

        return parent::delete();
    }

    // ----------------------------------------

    public function create(int $orderId, string $ttsItemId): self
    {
        $this->setData(OrderItemResource::COLUMN_ORDER_ID, $orderId)
             ->setData(OrderItemResource::COLUMN_TTS_ITEM_ID, $ttsItemId)
             ->setData(OrderItemResource::COLUMN_QTY_PURCHASED, 1);

        return $this;
    }

    // ----------------------------------------

    public function getOrderId(): int
    {
        return (int)$this->getData(OrderItemResource::COLUMN_ORDER_ID);
    }

    public function getMagentoProductId(): ?int
    {
        $productId = $this->getData(OrderItemResource::COLUMN_PRODUCT_ID);
        if ($productId === null) {
            return null;
        }

        return (int)$productId;
    }

    public function getQtyReserved(): int
    {
        return (int)$this->getData(OrderItemResource::COLUMN_QTY_RESERVED);
    }

    //region Column product_details
    public function setAssociatedOptions(array $options): self
    {
        $this->setSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'associated_options',
            $options
        );

        return $this;
    }

    public function getAssociatedOptions()
    {
        return $this->getSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'associated_options',
            []
        );
    }

    public function removeAssociatedOptions(): void
    {
        $this->setAssociatedOptions([]);
    }

    public function setAssociatedProducts(array $products): Item
    {
        $this->setSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'associated_products',
            $products
        );

        return $this;
    }

    public function getAssociatedProducts()
    {
        return $this->getSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'associated_products',
            []
        );
    }

    public function removeAssociatedProducts(): void
    {
        $this->setAssociatedProducts([]);
    }

    public function setReservedProducts(array $products): Item
    {
        $this->setSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'reserved_products',
            $products
        );

        return $this;
    }

    public function getReservedProducts()
    {
        return $this->getSetting(
            OrderItemResource::COLUMN_PRODUCT_DETAILS,
            'reserved_products',
            []
        );
    }
    //endregion

    //region Order
    public function setOrder(\M2E\TikTokShop\Model\Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): \M2E\TikTokShop\Model\Order
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->order)) {
            return $this->order;
        }

        return $this->order = $this->orderRepository->get($this->getOrderId());
    }
    //endregion

    //########################################

    public function setProduct($product): self
    {
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            $this->magentoProduct = null;

            return $this;
        }

        if ($this->magentoProduct === null) {
            $this->magentoProduct = $this->magentoProductFactory->create();
        }
        $this->magentoProduct->setProduct($product);

        return $this;
    }

    /**
     * @throws \M2E\TikTokShop\Model\Exception
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function getProduct(): ?\Magento\Catalog\Model\Product
    {
        if ($this->getMagentoProductId() === null) {
            return null;
        }

        if (!$this->isMagentoProductExists()) {
            return null;
        }

        return $this->getMagentoProduct()->getProduct();
    }

    public function getMagentoProduct(): ?\M2E\TikTokShop\Model\Magento\Product
    {
        if ($this->getMagentoProductId() === null) {
            return null;
        }

        if ($this->magentoProduct === null) {
            $this->magentoProduct = $this->magentoProductFactory->createByProductId((int)$this->getMagentoProductId());
            $this->magentoProduct->setStoreId($this->getOrder()->getStoreId());
        }

        return $this->magentoProduct;
    }

    public function getStoreId(): int
    {
        $variantSku = $this->getVariantSku();

        if ($variantSku === null) {
            return $this->getOrder()->getStoreId();
        }

        $storeId = $variantSku->getListing()->getStoreId();

        if ($storeId !== \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            return $storeId;
        }

        if ($this->getMagentoProductId() === null) {
            return $this->magentoStoreHelper->getDefaultStoreId();
        }

        $storeIds = $this
            ->magentoProductFactory
            ->createByProductId((int)$this->getMagentoProductId())
            ->getStoreIds();

        if (empty($storeIds)) {
            return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        return (int)array_shift($storeIds);
    }

    //########################################

    /**
     * Associate order item with product in magento
     * @throws \M2E\TikTokShop\Model\Exception
     * @throws \Exception
     */
    public function associateWithProduct(): void
    {
        if (
            $this->getMagentoProductId() === null
            || !$this->getMagentoProduct()->exists()
        ) {
            $this->productAssignService->assign(
                [$this],
                $this->getAssociatedProduct(),
                \M2E\Core\Helper\Data::INITIATOR_EXTENSION
            );
        }

        if (
            !in_array(
                $this->getMagentoProduct()->getTypeId(),
                \M2E\TikTokShop\Model\Order::SUPPORTED_MAGENTO_PRODUCT_TYPES
            )
        ) {
            $message = \M2E\TikTokShop\Helper\Module\Log::encodeDescription(
                'Order Import does not support Product type: %type%.',
                [
                    'type' => $this->getMagentoProduct()->getTypeId(),
                ],
            );

            throw new \M2E\TikTokShop\Model\Exception($message);
        }

        $this->associateVariationWithOptions();

        if (!$this->getMagentoProduct()->isStatusEnabled()) {
            throw new \M2E\TikTokShop\Model\Exception('Product is disabled.');
        }
    }

    //########################################

    /**
     * Associate order item variation with options of magento product
     * @throws \LogicException
     * @throws \Exception
     */
    private function associateVariationWithOptions()
    {
        $magentoProduct = $this->getMagentoProduct();

        $existOptions = $this->getAssociatedOptions();
        $existProducts = $this->getAssociatedProducts();

        if (
            count($existProducts) == 1
            && ($magentoProduct->isDownloadableType()
                || $magentoProduct->isGroupedType()
                || $magentoProduct->isConfigurableType())
        ) {
            // grouped and configurable products can have only one associated product mapped with sold variation
            // so if count($existProducts) == 1 - there is no need for further actions
            return;
        }

        $productDetails = $this->getAssociatedProductDetails($magentoProduct);

        if (!isset($productDetails['associated_options'])) {
            return;
        }

        $existOptionsIds = array_keys($existOptions);
        $foundOptionsIds = array_keys($productDetails['associated_options']);

        if (empty($existOptions) && empty($existProducts)) {
            // options mapping invoked for the first time, use found options
            $this->setAssociatedOptions($productDetails['associated_options']);

            if (isset($productDetails['associated_products'])) {
                $this->setAssociatedProducts($productDetails['associated_products']);
            }

            $this->save();

            return;
        }

        if (!empty(array_diff($foundOptionsIds, $existOptionsIds))) {
            // options were already mapped, but not all of them
            throw new \M2E\TikTokShop\Model\Exception\Logic('Selected Options do not match the Product Options.');
        }
    }

    private function getAssociatedProductDetails(\M2E\TikTokShop\Model\Magento\Product $magentoProduct): array
    {
        if (!$magentoProduct->getTypeId()) {
            return [];
        }

        $combinedListingSkus = $this->getCombinedListingSkus();
        if (empty($combinedListingSkus)) {
            return [];
        }

        $bundleSkuFinder = $this->bundleSkuFinderFactory
            ->create($magentoProduct, $combinedListingSkus);

        return $bundleSkuFinder->find() ?? [];
    }

    //########################################

    public function assignProduct($productId): void
    {
        $magentoProduct = $this->magentoProductFactory->createByProductId((int)$productId);

        if (!$magentoProduct->exists()) {
            $this->setData('product_id');
            $this->setAssociatedProducts([]);
            $this->setAssociatedOptions([]);
            $this->save();

            throw new \InvalidArgumentException('Product does not exist.');
        }

        $this->setMagentoProductId((int)$productId);

        $this->save();
    }

    public function setMagentoProductId(int $productId)
    {
        $this->setData(OrderItemResource::COLUMN_PRODUCT_ID, $productId);
    }

    public function removeMagentoProductId(): void
    {
        $this->setData(OrderItemResource::COLUMN_PRODUCT_ID, null);
    }

    //########################################

    /**
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function unassignProduct()
    {
        $this->setData('product_id');
        $this->setAssociatedProducts([]);
        $this->setAssociatedOptions([]);

        if ($this->getOrder()->getReserve()->isPlaced()) {
            $this->getOrder()->getReserve()->cancel();
            $this->getOrder()->getReserve()->addSuccessLogCancelQty();
        }

        $this->save();
    }

    //########################################

    public function pretendedToBeSimple(): bool
    {
        return false;
    }

    //########################################

    /**
     * @return array
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function getAdditionalData(): array
    {
        return $this->getSettings('additional_data');
    }

    public function isMagentoProductExists(): bool
    {
        return $this->magentoProductFactory->createByProductId((int)$this->getMagentoProductId())->exists();
    }

    /**
     * @return \M2E\TikTokShop\Model\AbstractModel
     */
    public function getProxy(): \M2E\TikTokShop\Model\AbstractModel
    {
        if ($this->proxy === null) {
            $this->proxy = $this->proxyObjectFactory->create($this);
        }

        return $this->proxy;
    }

    // ----------------------------------------

    public function getAccount(): \M2E\TikTokShop\Model\Account
    {
        return $this->getOrder()->getAccount();
    }

    // ----------------------------------------

    public function getVariantSku(): ?\M2E\TikTokShop\Model\Product\VariantSku
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->variantSku)) {
            return $this->variantSku;
        }

        $variantSku = $this->listingProductRepository->findVariantSkuByTtsProductIdAndSkuId(
            $this->getChannelProductId(),
            $this->getChannelSkuId(),
        );

        if ($variantSku === null) {
            return null;
        }

        return $this->variantSku = $variantSku;
    }

    // ----------------------------------------

    public function getItemId()
    {
        return $this->getData(OrderItemResource::COLUMN_TTS_ITEM_ID);
    }

    public function setChannelProductId(string $ttsProductId): self
    {
        $this->setData(OrderItemResource::COLUMN_TTS_PRODUCT_ID, $ttsProductId);

        return $this;
    }

    public function getChannelProductId()
    {
        return $this->getData(OrderItemResource::COLUMN_TTS_PRODUCT_ID);
    }

    public function setChannelProductTitle(string $channelProductTitle): self
    {
        $this->setData(OrderItemResource::COLUMN_TITLE, $channelProductTitle);

        return $this;
    }

    public function getChannelProductTitle()
    {
        return $this->getData(OrderItemResource::COLUMN_TITLE);
    }

    public function setSku(string $sku): self
    {
        $this->setData(OrderItemResource::COLUMN_SKU, $sku);

        return $this;
    }

    public function getSku()
    {
        return $this->getData(OrderItemResource::COLUMN_SKU);
    }

    public function setSalePrice(float $price): self
    {
        $this->setData(OrderItemResource::COLUMN_SALE_PRICE, $price);

        return $this;
    }

    public function getSalePrice(): float
    {
        return (float)$this->getData(OrderItemResource::COLUMN_SALE_PRICE);
    }

    public function getSalePriceWithPlatformDiscount(): float
    {
        return $this->getSalePrice() + $this->getPlatformDiscount();
    }

    public function getQtyPurchased(): int
    {
        return (int)$this->getData(OrderItemResource::COLUMN_QTY_PURCHASED);
    }

    // ---------------------------------------

    public function setTaxDetails(array $details): self
    {
        $this->setData(OrderItemResource::COLUMN_TAX_DETAILS, json_encode($details));

        return $this;
    }

    public function getTaxDetails(): array
    {
        $taxDetails = $this->getData(OrderItemResource::COLUMN_TAX_DETAILS);
        if (empty($taxDetails)) {
            return [];
        }

        return json_decode($taxDetails, true) ?? [];
    }

    /**
     * @return float
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    public function getTaxAmount(): float
    {
        $taxDetails = $this->getTaxDetails();

        return (float)($taxDetails['amount'] ?? 0.0);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function hasVariation(): bool
    {
        return false;
    }

    public function setTrackingDetails(array $details): self
    {
        $this->setData(OrderItemResource::COLUMN_TRACKING_DETAILS, json_encode($details));

        return $this;
    }

    public function getTrackingDetails(): array
    {
        $trackingDetails = $this->getData(OrderItemResource::COLUMN_TRACKING_DETAILS);
        if (empty($trackingDetails)) {
            return [];
        }

        return json_decode($trackingDetails, true) ?? [];
    }

    public function canCreateMagentoOrder(): bool
    {
        return $this->isOrdersCreationEnabled();
    }

    public function isReservable(): bool
    {
        return $this->isOrdersCreationEnabled();
    }

    protected function isOrdersCreationEnabled(): bool
    {
        $variantSku = $this->getVariantSku();
        if ($variantSku === null) {
            return $this->getAccount()->getOrdersSettings()->isUnmanagedListingEnabled();
        }

        return $this->getAccount()->getOrdersSettings()->isListingEnabled();
    }

    /**
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     * @throws \M2E\TikTokShop\Model\Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function getAssociatedProduct(): \Magento\Catalog\Model\Product
    {
        // Item was listed by M2E
        // ---------------------------------------
        if ($this->getVariantSku() !== null) {
            return $this->getVariantSku()->getMagentoProduct()->getProduct();
        }

        // Unmanaged Item
        // ---------------------------------------
        $sku = $this->getSku();

        if (
            $sku != ''
            && strlen($sku) <= \M2E\TikTokShop\Helper\Magento\Product::SKU_MAX_LENGTH
        ) {
            $collection = $this->magentoProductCollectionFactory->create();
            $collection->setStoreId($this->getOrder()->getAssociatedStoreId());
            $collection->addAttributeToSelect('sku');
            $collection->addAttributeToFilter('sku', $sku);

            /** @var \Magento\Catalog\Model\Product $foundedProduct */
            $foundedProduct = $collection->getFirstItem();

            if (!$foundedProduct->isObjectNew()) {
                $this->associateWithProductEvent($foundedProduct);

                return $foundedProduct;
            }
        }

        // Create new Product in Magento
        // ---------------------------------------
        $newProduct = $this->createProduct();
        $this->associateWithProductEvent($newProduct);

        return $newProduct;
    }

    public function prepareMagentoOptions($options): array
    {
        return \M2E\TikTokShop\Helper\Component\TikTokShop::prepareOptionsForOrders($options);
    }

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws \M2E\TikTokShop\Model\Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function createProduct(): \Magento\Catalog\Model\Product
    {
        throw new \M2E\TikTokShop\Model\Order\Exception\ProductCreationDisabled(
            (string)__('The product associated with this order could not be found in the Magento catalog.'),
        );
    }

    protected function associateWithProductEvent(\Magento\Catalog\Model\Product $product)
    {
        if (!$this->hasVariation()) {
            $this->_eventManager->dispatch('m2e_tts_associate_order_item_to_product', [
                'product' => $product,
                'order_item' => $this,
            ]);
        }
    }

    public function setOriginalPrice(float $price): self
    {
        $this->setData(OrderItemResource::COLUMN_ORIGINAL_PRICE, $price);

        return $this;
    }

    public function getOriginalPrice(): float
    {
        return (float)$this->getData(OrderItemResource::COLUMN_ORIGINAL_PRICE);
    }

    public function setPlatformDiscount(float $discount): self
    {
        $this->setData(OrderItemResource::COLUMN_PLATFORM_DISCOUNT, $discount);

        return $this;
    }

    public function getPlatformDiscount(): float
    {
        return (float)($this->getData(OrderItemResource::COLUMN_PLATFORM_DISCOUNT) ?? 0.0);
    }

    public function setSellerDiscount(float $discount): self
    {
        $this->setData(OrderItemResource::COLUMN_SELLER_DISCOUNT, $discount);

        return $this;
    }

    public function getSellerDiscount(): float
    {
        return (float)($this->getData(OrderItemResource::COLUMN_SELLER_DISCOUNT) ?? 0.0);
    }

    public function setChannelSkuId(string $channelSkuId): self
    {
        $this->setData(OrderItemResource::COLUMN_TTS_SKU_ID, $channelSkuId);

        return $this;
    }

    public function getChannelSkuId(): string
    {
        return (string)$this->getData(OrderItemResource::COLUMN_TTS_SKU_ID);
    }

    public function setPackageId(?string $packageId): self
    {
        $this->setData(OrderItemResource::COLUMN_PACKAGE_ID, $packageId);

        return $this;
    }

    public function getPackageId(): ?string
    {
        return $this->getData(OrderItemResource::COLUMN_PACKAGE_ID);
    }

    public function setCancelReason(?string $reason): self
    {
        $this->setData(OrderItemResource::COLUMN_CANCEL_REASON, $reason);

        return $this;
    }

    public function getCancelReason(): string
    {
        return (string)$this->getData(OrderItemResource::COLUMN_CANCEL_REASON);
    }

    public function isBuyerRequestReturn(): bool
    {
        return (bool)$this->getData(OrderItemResource::COLUMN_BUYER_REQUEST_RETURN);
    }

    public function setBuyerRequestReturn(bool $status): self
    {
        $this->setData(OrderItemResource::COLUMN_BUYER_REQUEST_RETURN, (int)$status);

        return $this;
    }

    public function isChangedBuyerRequestReturn(): bool
    {
        $oldValue = (bool)$this->getOrigData(OrderItemResource::COLUMN_BUYER_REQUEST_RETURN);
        $newValue = $this->isBuyerRequestReturn();

        if ($oldValue && !$newValue) {
            return false;
        }

        return $oldValue !== $newValue;
    }

    public function isReturnRequestedProcessPossible(): bool
    {
        return $this->isBuyerRequestReturn() && $this->isRefundReturnStatusPending();
    }

    public function setBuyerRequestRefund(bool $status): self
    {
        $this->setData(OrderItemResource::COLUMN_BUYER_REQUEST_REFUND, (int)$status);

        return $this;
    }

    public function setRefundReturnId(?string $id): self
    {
        $this->setData(OrderItemResource::COLUMN_REFUND_RETURN_ID, $id);

        return $this;
    }

    public function isChangedBuyerRequestRefund(): bool
    {
        $oldValue = (bool)$this->getOrigData(OrderItemResource::COLUMN_BUYER_REQUEST_REFUND);
        $newValue = $this->isBuyerRequestRefund();

        if ($oldValue && !$newValue) {
            return false;
        }

        return $oldValue !== $newValue;
    }

    public function isBuyerRequestRefund(): bool
    {
        return (bool)$this->getData(OrderItemResource::COLUMN_BUYER_REQUEST_REFUND);
    }

    public function getRefundReturnId(): ?string
    {
        return $this->getData(OrderItemResource::COLUMN_REFUND_RETURN_ID);
    }

    public function hasRefundReturnId(): bool
    {
        return $this->getRefundReturnId() !== null;
    }

    public function setRefundReturnStatus(?string $status): self
    {
        $this->setData(OrderItemResource::COLUMN_REFUND_RETURN_STATUS, $status);

        return $this;
    }

    public function getRefundReturnStatus(): string
    {
        return (string)$this->getData(OrderItemResource::COLUMN_REFUND_RETURN_STATUS);
    }

    public function isChangedRefundReturnStatus(): bool
    {
        $oldValue = (string)$this->getOrigData(OrderItemResource::COLUMN_REFUND_RETURN_STATUS);
        $newValue = $this->getRefundReturnStatus();

        if ($oldValue && !empty($newValue)) {
            return false;
        }

        return $oldValue !== $newValue;
    }

    private function isRefundReturnStatusPending(): bool
    {
        return $this->getRefundReturnStatus() === ReturnRequestStatus::RETURN_OR_REFUND_REQUEST_PENDING;
    }

    public function isRefundReturnStatusBuyerShippedItem(): bool
    {
        return $this->getRefundReturnStatus() === ReturnRequestStatus::BUYER_SHIPPED_ITEM;
    }

    public function isRefundReturnStatusRequestCancel(): bool
    {
        return $this->getRefundReturnStatus() === ReturnRequestStatus::RETURN_OR_REFUND_REQUEST_CANCEL;
    }

    public function isRefundReturnStatusRequestComplete(): bool
    {
        return $this->getRefundReturnStatus() === ReturnRequestStatus::RETURN_OR_REFUND_REQUEST_COMPLETE;
    }

    //region shipping_in_progress column
    public function setShippingInProgressYes()
    {
        $this->setData(OrderItemResource::COLUMN_SHIPPING_IN_PROGRESS, self::SHIPPING_IS_IN_PROGRESS_YES);
    }

    public function setShippingInProgressNo()
    {
        $this->setData(OrderItemResource::COLUMN_SHIPPING_IN_PROGRESS, self::SHIPPING_IS_IN_PROGRESS_NO);
    }

    public function isShippingInProgress(): bool
    {
        return (int)$this->getData(OrderItemResource::COLUMN_SHIPPING_IN_PROGRESS)
            === self::SHIPPING_IS_IN_PROGRESS_YES;
    }

    //endregion

    public function canCreateShipments(): bool
    {
        $createShipmentStatuses = [
            self::ITEM_STATUS_AWAITING_SHIPMENT,
            self::ITEM_STATUS_AWAITING_COLLECTION,
            self::ITEM_STATUS_IN_TRANSIT,
        ];

        return in_array($this->getItemStatus(), $createShipmentStatuses, true);
    }

    //region item_status column
    public function setItemStatus(int $status)
    {
        if ($this->getItemStatus() === $status) {
            return;
        }

        $this->setData(OrderItemResource::COLUMN_ITEM_STATUS, $status);
        $this->setShippingInProgressNo();
    }

    public function isReadyToShip(): bool
    {
        return $this->getItemStatus() === self::ITEM_STATUS_AWAITING_SHIPMENT
            || $this->getItemStatus() === self::ITEM_STATUS_UNKNOWN;
    }

    public function getItemStatus(): int
    {
        return (int)$this->getData(OrderItemResource::COLUMN_ITEM_STATUS);
    }

    public function setItemStatusAsAwaitingCollection(): void
    {
        $this->setItemStatus(self::ITEM_STATUS_AWAITING_COLLECTION);
    }
    //endregion

    public function setIsGift(bool $isGift): self
    {
        $this->setData(OrderItemResource::COLUMN_IS_GIFT, (int)$isGift);

        return $this;
    }

    public function isGiftItem(): bool
    {
        return (bool)$this->getData(OrderItemResource::COLUMN_IS_GIFT);
    }

    public function setCombinedListingSkus(
        ?\M2E\TikTokShop\Model\Order\Item\CombinedListingSkus $combinedListingSkus
    ) {
        if ($combinedListingSkus !== null) {
            $combinedListingSkus = json_encode($combinedListingSkus->toArray());
        }

        $this->setData(OrderItemResource::COLUMN_COMBINED_LISTING_SKUS, $combinedListingSkus);
    }

    public function getCombinedListingSkus(): ?\M2E\TikTokShop\Model\Order\Item\CombinedListingSkus
    {
        $combinedListingSkus = $this->getData(OrderItemResource::COLUMN_COMBINED_LISTING_SKUS);
        if (empty($combinedListingSkus)) {
            return null;
        }

        $combinedListingSkus = json_decode($combinedListingSkus, true);

        $skus = [];
        foreach ($combinedListingSkus as $combinedListingSku) {
            $skus[] = new \M2E\TikTokShop\Model\Order\Item\CombinedListingSku(
                $combinedListingSku[\M2E\TikTokShop\Model\Order\Item\CombinedListingSku::KEY_SKU_ID],
                $combinedListingSku[\M2E\TikTokShop\Model\Order\Item\CombinedListingSku::KEY_SKU_COUNT],
                $combinedListingSku[\M2E\TikTokShop\Model\Order\Item\CombinedListingSku::KEY_PRODUCT_ID],
                $combinedListingSku[\M2E\TikTokShop\Model\Order\Item\CombinedListingSku::KEY_SELLER_SKU],
            );
        }

        return new \M2E\TikTokShop\Model\Order\Item\CombinedListingSkus($skus);
    }
}
