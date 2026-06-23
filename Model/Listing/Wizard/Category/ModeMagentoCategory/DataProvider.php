<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory;

class DataProvider
{
    private \M2E\TikTokShop\Model\Category\Dictionary\Repository $categoryDictionaryRepository;

    public function __construct(\M2E\TikTokShop\Model\Category\Dictionary\Repository $categoryDictionaryRepository)
    {
        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
    }

    public function getCategoryDataCollection(\M2E\TikTokShop\Model\Listing $listing): CategoryDataCollection
    {
        $categoryMappingItems = $listing->getCategoryMappingItems();

        $categoriesData = [];
        foreach ($categoryMappingItems as $categoryMappingItem) {
            $dictionary = $this->categoryDictionaryRepository->find($categoryMappingItem->dictionaryId);
            if ($dictionary === null) {
                continue;
            }

            $categoriesData[] = new CategoryData(
                $categoryMappingItem->magentoCategoryId,
                $dictionary->getId(),
                $dictionary->getCategoryId(),
                $dictionary->getPath()
            );
        }

        return new CategoryDataCollection($categoriesData);
    }
}
