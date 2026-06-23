<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory;

class CategoryDataCollection
{
    /**
     * @var array<int, CategoryData>
     */
    private array $items = [];

    /**
     * @param CategoryData[] $items
     */
    public function __construct(array $items)
    {
        foreach ($items as $item) {
            $this->items[$item->magentoCategoryId] = $item;
        }
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function findByMagentoCategoryId(int $magentoCategoryId): ?CategoryData
    {
        if (isset($this->items[$magentoCategoryId])) {
            return $this->items[$magentoCategoryId];
        }

        return null;
    }
}
