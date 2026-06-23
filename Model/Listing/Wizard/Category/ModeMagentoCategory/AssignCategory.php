<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory;

use M2E\TikTokShop\Model\Listing\CategoryMappingItem;

class AssignCategory
{
    private \M2E\Core\Helper\Magento\Category $categoryHelper;
    private \M2E\TikTokShop\Model\Listing\Repository $listingRepository;

    public function __construct(
        \M2E\Core\Helper\Magento\Category $categoryHelper,
        \M2E\TikTokShop\Model\Listing\Repository $listingRepository
    ) {
        $this->categoryHelper = $categoryHelper;
        $this->listingRepository = $listingRepository;
    }

    public function assign(
        \M2E\TikTokShop\Model\Listing\Wizard\Manager $wizardManager,
        int $magentoCategoryId,
        ?int $dictionaryId
    ) {
        $categoryProductIds = $this->categoryHelper->getProductsFromCategories([$magentoCategoryId]);
        $wizardProducts = $wizardManager->getProductsIds();

        $productsForAssignCategory = array_intersect($categoryProductIds, $wizardProducts);

        $wizardManager->setProductsCategoryIdMagentoCategory(
            $productsForAssignCategory,
            $dictionaryId
        );
    }

    public function assignAndUpdateCategoryMappingInListing(
        \M2E\TikTokShop\Model\Listing\Wizard\Manager $wizardManager,
        int $magentoCategoryId,
        ?int $dictionaryId
    ) {
        $this->assign($wizardManager, $magentoCategoryId, $dictionaryId);
        $this->updateCategoryMapping(
            $wizardManager->getListing(),
            $magentoCategoryId,
            $dictionaryId
        );
    }

    private function updateCategoryMapping(
        \M2E\TikTokShop\Model\Listing $listing,
        int $magentoCategoryId,
        ?int $dictionaryId
    ) {
        $categoryMappingItems = $listing->getCategoryMappingItems();

        $mappingsGroupedByMagentoCategoryId = [];
        foreach ($categoryMappingItems as $categoryMappingItem) {
            $mappingsGroupedByMagentoCategoryId[$categoryMappingItem->magentoCategoryId] = $categoryMappingItem;
        }

        if (
            !isset($mappingsGroupedByMagentoCategoryId[$magentoCategoryId])
            && $dictionaryId === null
        ) {
            return;
        }

        if ($dictionaryId === null) {
            unset($mappingsGroupedByMagentoCategoryId[$magentoCategoryId]);
        } else {
            $mappingsGroupedByMagentoCategoryId[$magentoCategoryId] = new CategoryMappingItem(
                $magentoCategoryId,
                $dictionaryId,
            );
        }

        $listing->setCategoryMappingItems(array_values($mappingsGroupedByMagentoCategoryId));

        $this->listingRepository->save($listing);
    }
}
