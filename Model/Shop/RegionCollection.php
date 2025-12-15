<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Shop;

class RegionCollection
{
    /** @var Region[] */
    private array $regions;

    /** @var \M2E\TikTokShop\Model\Shop\Region\Provider */
    private Region\Provider $provider;

    public function __construct(Region\Provider $provider)
    {
        $this->provider = $provider;
    }

    public function getByCode(string $code): Region
    {
        $region = $this->findByCode($code);
        if (null === $region) {
            return new Region(
                $code,
                $code,
                Region::CURRENCY_EUR,
                Region::SIZE_DIMENSION_CENTIMETER,
                Region::WEIGHT_DIMENSION_KILOGRAM,
                new Region\ProductPriceRestrictions(0.01, 5600)
            );
        }

        return $region;
    }

    public function findByCode(string $code): ?Region
    {
        $this->init();

        return $this->regions[$code] ?? null;
    }

    /**
     * @return Region[]
     */
    public function getAll(): array
    {
        $this->init();

        return array_values($this->regions);
    }

    /**
     * @return Region[]
     */
    public function getInEu(): array
    {
        $this->init();

        $euRegions = [];
        foreach ($this->regions as $region) {
            if ($region->isEU()) {
                $euRegions[] = $region;
            }
        }

        return $euRegions;
    }

    private function init(): void
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->regions)) {
            return;
        }

        foreach ($this->provider->retrieve() as $region) {
            $this->regions[$region->getRegionCode()] = $region;
        }
    }
}
