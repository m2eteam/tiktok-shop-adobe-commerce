<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\DataBuilder;

use M2E\TikTokShop\Model\Magento\Product as MagentoProduct;
use M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\DataBuilder\VariantSku as VariantSkuPart;

class VariantSku extends AbstractDataBuilder
{
    public const NICK = 'VariantSku';

    private static array $identifierTypeMap = [
        \M2E\TikTokShop\Helper\Data\Product\Identifier::GTIN => 'GTIN',
        \M2E\TikTokShop\Helper\Data\Product\Identifier::EAN => 'EAN',
        \M2E\TikTokShop\Helper\Data\Product\Identifier::UPC => 'UPC',
        \M2E\TikTokShop\Helper\Data\Product\Identifier::ISBN => 'ISBN',
    ];

    private \M2E\TikTokShop\Helper\Component\TikTokShop\Configuration $ttsConfiguration;

    private array $onlineDataForSku = [];
    /** @var \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\DataBuilder\VariantSku\Item\ItemPartFactory */
    private VariantSku\Item\ItemPartFactory $itemPartFactory;

    public function __construct(
        \M2E\TikTokShop\Helper\Component\TikTokShop\Configuration $ttsConfiguration,
        \M2E\TikTokShop\Helper\Magento\Attribute $magentoAttributeHelper,
        \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\DataBuilder\VariantSku\Item\ItemPartFactory $itemPartFactory
    ) {
        parent::__construct($magentoAttributeHelper);
        $this->ttsConfiguration = $ttsConfiguration;
        $this->itemPartFactory = $itemPartFactory;
    }

    public function getBuilderData(): array
    {
        $variantSettings = $this->getVariantSettings();

        $parentMagentoProduct = $this->getListingProduct()->getMagentoProduct();
        $isConfigurable = $parentMagentoProduct->isConfigurableType();
        $configurableAttributes = $isConfigurable ? $parentMagentoProduct->getConfigurableAttributes() : [];

        $variants = $this->getListingProduct()->getVariants();
        $skuItems = new \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\DataBuilder\VariantSku\Collection();

        foreach ($variants as $variant) {
            if ($variantSettings->isSkipAction($variant->getId())) {
                continue;
            }

            if ($variantSettings->isStopAction($variant->getId())) {
                $qty = 0;
            } else {
                $qty = $variant->getQty();
            }

            $skuItems->addItem(
                $this->createVariantItem(
                    $variant,
                    (float)$variant->getFixedPrice(),
                    $qty,
                    $isConfigurable,
                    $configurableAttributes,
                )
            );
        }

        $skuItems->selectBaseSalesAttributeForImage();

        $this->onlineDataForSku = $skuItems->collectOnlineData();

        return $skuItems->toArray();
    }

    public function getMetaData(): array
    {
        return [self::NICK => $this->onlineDataForSku];
    }

    // ----------------------------------------

    private function createVariantItem(
        \M2E\TikTokShop\Model\Product\VariantSku $variant,
        float $price,
        int $qty,
        bool $isParentConfigurable,
        array $configurableAttributes
    ): VariantSkuPart\Item {
        $item = new VariantSkuPart\Item();
        $item->setSellerSku($variant->getSku());

        if (!$variant->isStatusNotListed()) {
            $item->setSkuId($variant->getSkuId());
        }

        if (($identifier = $this->findIdentifiers($variant)) !== null) {
            $item->setIdentifier(
                $this->itemPartFactory->createIdentifier(
                    $identifier->getId(),
                    $identifier->getType(),
                )
            );
        }

        if ($isParentConfigurable) {
            $variantMagentoProduct = $variant->getMagentoProduct();

            foreach ($configurableAttributes as $attribute) {
                $item->addSalesAttribute(
                    $this->itemPartFactory->createSalesAttribute(
                        $attribute->getDefaultFrontendLabel(),
                        $variantMagentoProduct->getAttributeValue($attribute->getAttributeCode()),
                        $variant->getImage()
                    )
                );
            }
        }

        $item->setPrice(
            $this->itemPartFactory->createPrice(
                $price,
                $this->getListingProduct()->getShop()->getCurrencyCode()
            )
        );

        if ($variant->hasWarehouse()) {
            $ttsWarehouseId = $variant->getWarehouse()->getWarehouseId();
        } else {
            $ttsWarehouseId = $this->getListingProduct()->getShop()->getDefaultWarehouse()->getWarehouseId();
        }

        $item->addInventory(
            $this->itemPartFactory->createInventory($ttsWarehouseId, $qty)
        );

        $this->checkQtyWarnings($variant);

        return $item;
    }

    private function checkQtyWarnings(\M2E\TikTokShop\Model\Product\VariantSku $variantSku): void
    {
        $qtyMode = $this->getListingProduct()->getSellingFormatTemplate()->getQtyMode();
        if (
            $qtyMode === \M2E\TikTokShop\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED
            || $qtyMode === \M2E\TikTokShop\Model\Template\SellingFormat::QTY_MODE_PRODUCT
        ) {
            $staticId = $variantSku->getId();
            $productId = $variantSku->getMagentoProductId();
            $storeId = $this->getListingProduct()->getListing()->getStoreId();

            if (!empty(MagentoProduct::$statistics[$staticId][$productId][$storeId]['qty'])) {
                $qtys = MagentoProduct::$statistics[$staticId][$productId][$storeId]['qty'];
                foreach ($qtys as $type => $override) {
                    $this->addQtyWarnings((int)$type);
                }
            }
        }
    }

    private function addQtyWarnings(int $type): void
    {
        if ($type === MagentoProduct::FORCING_QTY_TYPE_MANAGE_STOCK_NO) {
            $this->addWarningMessage(
                'During the Quantity Calculation the Settings in the "Manage Stock No" ' .
                'field were taken into consideration.',
            );
        }

        if ($type === MagentoProduct::FORCING_QTY_TYPE_BACKORDERS) {
            $this->addWarningMessage(
                'During the Quantity Calculation the Settings in the "Backorders" ' .
                'field were taken into consideration.',
            );
        }
    }

    private function findIdentifiers(
        \M2E\TikTokShop\Model\Product\VariantSku $variantSku
    ): ?\M2E\TikTokShop\Model\Product\VariantSku\Identifier {
        $onlineIdentifier = $variantSku->getOnlineIdentifier();
        if ($onlineIdentifier !== null) {
            return $onlineIdentifier;
        }

        if (!$this->ttsConfiguration->isIdentifierCodeModeCustomAttribute()) {
            return null;
        }

        $this->searchNotFoundAttributes($this->getListingProduct()->getMagentoProduct());

        $attributeCode = $this->ttsConfiguration->getIdentifierCodeCustomAttribute();
        $value = $variantSku->getMagentoProduct()->getAttributeValue($attributeCode);
        if (empty($value)) {
            $this->processNotFoundAttributes((string)__('Product ID'), $this->getListingProduct()->getMagentoProduct());

            return null;
        }

        $type = $this->getIdentifierType($value);
        if ($type === null) {
            $this->addWarningMessage('Product ID Type invalid');

            return null;
        }

        return new \M2E\TikTokShop\Model\Product\VariantSku\Identifier($value, $type);
    }

    private function getIdentifierType(string $identifierValue): ?string
    {
        $type = \M2E\TikTokShop\Helper\Data\Product\Identifier::getIdentifierType($identifierValue);
        if ($type === null) {
            return null;
        }

        return self::$identifierTypeMap[$type] ?? null;
    }
}