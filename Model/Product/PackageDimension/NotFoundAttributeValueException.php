<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Product\PackageDimension;

class NotFoundAttributeValueException extends PackageDimensionException
{
    public static function createPackageWeightException(string $attributeCode): self
    {
        $message = (string)__(
            'Package weight: Attribute %attribute_code were not found in this Product and its value was not sent.',
            ['attribute_code' => $attributeCode]
        );

        return new self($message);
    }

    public static function createPackageLengthException(string $attributeCode): self
    {
        $message = (string)__(
            'Package length: Attribute %attribute_code were not found in this Product and its value was not sent.',
            ['attribute_code' => $attributeCode]
        );

        return new self($message);
    }

    public static function createPackageWidthException(string $attributeCode): self
    {
        $message = (string)__(
            'Package width: Attribute %attribute_code were not found in this Product and its value was not sent.',
            ['attribute_code' => $attributeCode]
        );

        return new self($message);
    }

    public static function createPackageHeightException(string $attributeCode): self
    {
        $message = (string)__(
            'Package height: Attribute %attribute_code were not found in this Product and its value was not sent.',
            ['attribute_code' => $attributeCode]
        );

        return new self($message);
    }
}
