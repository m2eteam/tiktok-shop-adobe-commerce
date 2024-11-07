<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Category\Dictionary\Attribute;

use M2E\TikTokShop\Model\Category\CategoryAttribute;

class ProductAttribute extends \M2E\TikTokShop\Model\Category\Dictionary\AbstractAttribute
{
    public function getType(): string
    {
        return CategoryAttribute::ATTRIBUTE_TYPE_PRODUCT;
    }
}