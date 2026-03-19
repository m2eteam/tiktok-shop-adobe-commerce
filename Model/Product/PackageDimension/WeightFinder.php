<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Product\PackageDimension;

class WeightFinder
{
    private \M2E\TikTokShop\Model\TikTokShop\Settings\Repository $settingsRepository;

    public function __construct(
        \M2E\TikTokShop\Model\TikTokShop\Settings\Repository $settingsRepository
    ) {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @throws \M2E\TikTokShop\Model\Product\PackageDimension\PackageDimensionException
     */
    public function find(\M2E\TikTokShop\Model\Product $product): Weight
    {
        $unit = $this->getUnit($product);

        try {
            return new Weight(
                $this->getPackageWeight($product->getMagentoProduct()),
                $unit
            );
        } catch (NotConfiguredException $exception) {
            throw $exception;
        } catch (PackageDimensionException $exception) {
        }

        $childWeights = [];
        foreach ($product->getVariants() as $variant) {
            try {
                $childWeight = new Weight(
                    $this->getPackageWeight($variant->getMagentoProduct()),
                    $unit
                );
                $childWeights[$childWeight->getValue()] = $childWeight;
            } catch (PackageDimensionException $exception) {
                continue;
            }
        }

        if (count($childWeights) === 0) {
            $message = (string)__(
                'Package Weight is missing. To list the Product, please make sure ' .
                'that the Package settings are correct.'
            );

            throw new PackageDimensionException(
                $message,
                [],
                PackageDimensionException::CODE_WEIGHT_MISSING
            );
        }

        krsort($childWeights, SORT_NUMERIC);

        return reset($childWeights);
    }

    private function getUnit(\M2E\TikTokShop\Model\Product $product): string
    {
        return $product->getShop()->getRegion()->getWeightDimension();
    }

    /**
     * @throws NotConfiguredException
     * @throws \M2E\TikTokShop\Model\Product\PackageDimension\NotFoundAttributeValueException
     */
    public function getPackageWeight(\M2E\TikTokShop\Model\Magento\Product $magentoProduct): string
    {
        $packageWeightSettings = $this->settingsRepository->getPackageWeight();

        if ($packageWeightSettings->isModeCustomValue()) {
            $customValue = (float)$packageWeightSettings->getCustomValue();

            if (empty($customValue)) {
                throw NotConfiguredException::createPackageWeightException();
            }

            return (string)$this->toFloat($customValue);
        }

        if ($packageWeightSettings->getCustomAttribute()) {
            $attributeCode = $packageWeightSettings->getCustomAttribute();
            if (empty($attributeCode)) {
                throw NotConfiguredException::createPackageWeightException();
            }

            $attributeValue = (float)$magentoProduct->getAttributeValue($attributeCode);

            if (empty($attributeValue)) {
                throw NotFoundAttributeValueException::createPackageWeightException($attributeCode);
            }

            return (string)$this->toFloat($attributeValue);
        }

        throw NotConfiguredException::createPackageWeightException();
    }

    private function toFloat($length): float
    {
        return round((float)$length, 2);
    }
}
