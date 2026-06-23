<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory;

class CategoryData
{
    public int $magentoCategoryId;
    public int $dictionaryId;
    public string $tikTokShopCategoryId;
    public string $tikTokShopCategoryPath;

    public function __construct(
        int $magentoCategoryId,
        int $dictionaryId,
        string $tikTokShopCategoryId,
        string $tikTokShopCategoryPath
    ) {
        $this->magentoCategoryId = $magentoCategoryId;
        $this->dictionaryId = $dictionaryId;
        $this->tikTokShopCategoryId = $tikTokShopCategoryId;
        $this->tikTokShopCategoryPath = $tikTokShopCategoryPath;
    }
}
