<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Shop\Region;

use M2E\TikTokShop\Model\Shop\Region;

class Provider
{
    /**
     * @return Region[]
     */
    public function retrieve(): iterable
    {
        return [
            new Region(
                Region::REGION_GB,
                (string)__('United Kingdom'),
                Region::CURRENCY_GBP,
                Region::SIZE_DIMENSION_CENTIMETER,
                Region::WEIGHT_DIMENSION_KILOGRAM,
                new ProductPriceRestrictions(0.01, 5600)
            ),
            new Region(
                Region::REGION_US,
                (string)__('United States'),
                Region::CURRENCY_USD,
                Region::SIZE_DIMENSION_INCH,
                Region::WEIGHT_DIMENSION_POUND,
                new ProductPriceRestrictions(0.01, 7600)
            ),
            new Region(
                Region::REGION_ES,
                (string)__('Spain'),
                Region::CURRENCY_EUR,
                Region::SIZE_DIMENSION_CENTIMETER,
                Region::WEIGHT_DIMENSION_KILOGRAM,
                new ProductPriceRestrictions(0.01, 6300)
            ),
            new Region(
                Region::REGION_IE,
                (string)__('Ireland'),
                Region::CURRENCY_EUR,
                Region::SIZE_DIMENSION_CENTIMETER,
                Region::WEIGHT_DIMENSION_KILOGRAM,
                new ProductPriceRestrictions(0.01, 6300)
            ),
            new Region(
                Region::REGION_MX,
                (string)__('Mexico'),
                Region::CURRENCY_MXN,
                Region::SIZE_DIMENSION_CENTIMETER,
                Region::WEIGHT_DIMENSION_KILOGRAM,
                new ProductPriceRestrictions(0.1, 50000)
            ),
            new Region(
                Region::REGION_DE,
                (string)__('Germany'),
                Region::CURRENCY_EUR,
                Region::SIZE_DIMENSION_CENTIMETER,
                Region::WEIGHT_DIMENSION_KILOGRAM,
                new ProductPriceRestrictions(0.01, 6300)
            ),
            new Region(
                Region::REGION_FR,
                (string)__('France'),
                Region::CURRENCY_EUR,
                Region::SIZE_DIMENSION_CENTIMETER,
                Region::WEIGHT_DIMENSION_KILOGRAM,
                new ProductPriceRestrictions(0.01, 6300)
            ),
            new Region(
                Region::REGION_IT,
                (string)__('Italy'),
                Region::CURRENCY_EUR,
                Region::SIZE_DIMENSION_CENTIMETER,
                Region::WEIGHT_DIMENSION_KILOGRAM,
                new ProductPriceRestrictions(0.01, 6300)
            ),
            new Region(
                Region::REGION_BR,
                (string)__('Brazil'),
                Region::CURRENCY_BRL,
                Region::SIZE_DIMENSION_CENTIMETER,
                Region::WEIGHT_DIMENSION_KILOGRAM,
                new ProductPriceRestrictions(0.05, 10000)
            ),
        ];
    }
}
