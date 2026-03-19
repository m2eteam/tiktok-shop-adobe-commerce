<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Listing;

class RemoveDeletedProduct
{
    private const INSTRUCTION_INITIATOR_DELETE_PRODUCT_FROM_MAGENTO = 'delete_product_from_magento';

    private \M2E\TikTokShop\Model\Product\Repository $productRepository;
    private \M2E\TikTokShop\Model\StopQueue\CreateService $stopQueueCreateService;
    private \M2E\TikTokShop\Model\Product\DeleteService $productDeleteService;
    private \M2E\TikTokShop\Model\Listing\LogService $listingLogService;
    private \M2E\TikTokShop\Model\InstructionService $instructionService;
    private \M2E\TikTokShop\Model\GlobalProduct\DeleteService $globalProductDeleteService;

    public function __construct(
        \M2E\TikTokShop\Model\Product\Repository $productRepository,
        \M2E\TikTokShop\Model\StopQueue\CreateService $stopQueueCreateService,
        \M2E\TikTokShop\Model\Product\DeleteService $productDeleteService,
        \M2E\TikTokShop\Model\Listing\LogService $listingLogService,
        \M2E\TikTokShop\Model\InstructionService $instructionService,
        \M2E\TikTokShop\Model\GlobalProduct\DeleteService $globalProductDeleteService
    ) {
        $this->productRepository = $productRepository;
        $this->stopQueueCreateService = $stopQueueCreateService;
        $this->productDeleteService = $productDeleteService;
        $this->listingLogService = $listingLogService;
        $this->instructionService = $instructionService;
        $this->globalProductDeleteService = $globalProductDeleteService;
    }

    /**
     * @param \Magento\Catalog\Model\Product|int $magentoProduct
     *
     * @return void
     */
    public function process($magentoProduct): void
    {
        $magentoProductId = $magentoProduct instanceof \Magento\Catalog\Model\Product
            ? (int)$magentoProduct->getId()
            : (int)$magentoProduct;

        $this->processSimpleProducts($magentoProductId);
        $this->processVariantSku($magentoProductId);
    }

    private function processSimpleProducts(int $magentoProductId): void
    {
        $this->globalProductDeleteService->byMagentoProductId($magentoProductId);

        $listingsProducts = $this->productRepository
            ->findByMagentoProductId($magentoProductId);

        $processedListings = [];
        foreach ($listingsProducts as $listingProduct) {
            $message = (string)__('Item was deleted from Magento.');
            if (!$listingProduct->isStatusNotListed()) {
                $message = (string)__('Item was deleted from Magento and stopped on the Channel.');
            }

            if ($listingProduct->isStoppable()) {
                $this->stopQueueCreateService->create($listingProduct);
            }

            $listingProduct->setStatusInactive(\M2E\TikTokShop\Model\Product::STATUS_CHANGER_USER);
            $this->productRepository->save($listingProduct);

            $this->productDeleteService->process($listingProduct, \M2E\Core\Helper\Data::INITIATOR_EXTENSION);

            $listingId = $listingProduct->getListingId();
            if (isset($processedListings[$listingId])) {
                continue;
            }

            $processedListings[$listingId] = true;

            $this->listingLogService->addProduct(
                $listingProduct,
                \M2E\Core\Helper\Data::INITIATOR_EXTENSION,
                \M2E\TikTokShop\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_MAGENTO,
                null,
                $message,
                \M2E\TikTokShop\Model\Log\AbstractModel::TYPE_WARNING,
            );
        }
    }

    private function processVariantSku(int $magentoProductId): void
    {
        $this->globalProductDeleteService->variantByMagentoProductId($magentoProductId);

        $variantSkus = $this->productRepository
            ->findVariantSkusByMagentoProductId($magentoProductId);

        if (empty($variantSkus)) {
            return;
        }

        foreach ($variantSkus as $variantSku) {
            $parent = $variantSku->getProduct();

            if ($parent->isStatusListed()) {
                $this->addReviseInstruction($parent);
            }

            $this->addDeleteVariantSkuLog($variantSku);
            $this->deleteVariantSku($variantSku);
        }
    }

    private function deleteVariantSku(\M2E\TikTokShop\Model\Product\VariantSku $variantSku): void
    {
        $this->productRepository->deleteVariantSku($variantSku);
    }

    private function addReviseInstruction(\M2E\TikTokShop\Model\Product $product): void
    {
        $this->instructionService->create(
            $product->getId(),
            \M2E\TikTokShop\Model\Product::INSTRUCTION_TYPE_VARIANT_SKU_REMOVED,
            self::INSTRUCTION_INITIATOR_DELETE_PRODUCT_FROM_MAGENTO,
            80,
        );
    }

    private function addDeleteVariantSkuLog(\M2E\TikTokShop\Model\Product\VariantSku $variantSku): void
    {
        try {
            $message = (string)__(
                'SKU %sku: Item was deleted from Magento.',
                ['sku' => $variantSku->getSku()]
            );
        } catch (\M2E\TikTokShop\Model\Exception $exception) {
            $message = "A Product's Variation was deleted from the Magento catalog.";
        }

        $this->listingLogService->addProduct(
            $variantSku->getProduct(),
            \M2E\Core\Helper\Data::INITIATOR_EXTENSION,
            \M2E\TikTokShop\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_MAGENTO,
            null,
            $message,
            \M2E\TikTokShop\Model\Log\AbstractModel::TYPE_WARNING,
        );
    }
}
