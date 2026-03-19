<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Product\PackageDimension;

class NotConfiguredException extends PackageDimensionException
{
    public static function createPackageWeightException(): self
    {
        return new self(
            (string)__('Package Weight not configured'),
            [],
            PackageDimensionException::CODE_WEIGHT_NOT_CONFIGURED
        );
    }

    public static function createPackageLengthException(): self
    {
        return new self(
            (string)__('Package Length not configured'),
            [],
            PackageDimensionException::CODE_LENGTH_NOT_CONFIGURED
        );
    }

    public static function createPackageWidthException(): self
    {
        return new self(
            (string)__('Package Width not configured'),
            [],
            PackageDimensionException::CODE_WIDTH_NOT_CONFIGURED
        );
    }

    public static function createPackageHeightException(): self
    {
        return new self(
            (string)__('Package Height not configured'),
            [],
            PackageDimensionException::CODE_HEIGHT_NOT_CONFIGURED
        );
    }
}
