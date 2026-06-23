<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Listing;

class CategoryMappingItem
{
    private const KEY_MAGENTO_CATEGORY_ID = 'magento_category_id';
    private const KEY_DICTIONARY_ID = 'dictionary_id';

    public int $magentoCategoryId;
    public int $dictionaryId;

    public function __construct(
        int $magentoCategoryId,
        int $dictionaryId
    ) {
        $this->magentoCategoryId = $magentoCategoryId;
        $this->dictionaryId = $dictionaryId;
    }

    public static function createFromArray(array $rawData): self
    {
        return new self(
            $rawData[self::KEY_MAGENTO_CATEGORY_ID],
            $rawData[self::KEY_DICTIONARY_ID]
        );
    }

    public function toArray(): array
    {
        return [
            self::KEY_MAGENTO_CATEGORY_ID => $this->magentoCategoryId,
            self::KEY_DICTIONARY_ID => $this->dictionaryId,
        ];
    }
}
