<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Listing\Wizard;

class CompleteProcessor
{
    private \M2E\TikTokShop\Model\Listing\AddProductsService $addProductsService;
    private \M2E\TikTokShop\Model\Listing\Other\Repository $listingOtherRepository;
    private \M2E\TikTokShop\Model\Listing\Other\DeleteService $unmanagedProductDeleteService;

    public function __construct(
        \M2E\TikTokShop\Model\Listing\AddProductsService $addProductsService,
        \M2E\TikTokShop\Model\Listing\Other\Repository $listingOtherRepository,
        \M2E\TikTokShop\Model\Listing\Other\DeleteService $unmanagedProductDeleteService
    ) {
        $this->addProductsService = $addProductsService;
        $this->listingOtherRepository = $listingOtherRepository;
        $this->unmanagedProductDeleteService = $unmanagedProductDeleteService;
    }

    public function process(Manager $wizardManager): array
    {
        $listing = $wizardManager->getListing();

        $processedWizardProductIds = [];
        $listingProducts = [];
        foreach ($wizardManager->getNotProcessedProducts() as $wizardProduct) {
            $listingProduct = null;

            $processedWizardProductIds[] = $wizardProduct->getId();

            if ($wizardManager->isWizardTypeGeneral()) {
                $listingProduct = $this->addProductsService
                    ->addProduct(
                        $listing,
                        $wizardProduct->getMagentoProductId(),
                        $wizardProduct->getCategoryDictionaryId(),
                        \M2E\TikTokShop\Helper\Data::INITIATOR_USER,
                    );
            } elseif ($wizardManager->isWizardTypeUnmanaged()) {
                $unmanagedProduct = $this->listingOtherRepository->findById($wizardProduct->getUnmanagedProductId());
                if ($unmanagedProduct === null) {
                    continue;
                }

                if (!$unmanagedProduct->getMagentoProduct()->exists()) {
                    continue;
                }

                $listingProduct = $this->addProductsService
                    ->addFromUnmanaged(
                        $listing,
                        $unmanagedProduct,
                        $wizardProduct->getCategoryDictionaryId(),
                        \M2E\TikTokShop\Helper\Data::INITIATOR_USER,
                    );

                $this->unmanagedProductDeleteService->process($unmanagedProduct);
            }

            if ($listingProduct === null) {
                continue;
            }

            $listingProducts[] = $listingProduct;

            if (count($processedWizardProductIds) % 100 === 0) {
                $wizardManager->markProductsAsProcessed($processedWizardProductIds);
                $processedWizardProductIds = [];
            }
        }

        if (!empty($processedWizardProductIds)) {
            $wizardManager->markProductsAsProcessed($processedWizardProductIds);
        }

        return $listingProducts;
    }
}