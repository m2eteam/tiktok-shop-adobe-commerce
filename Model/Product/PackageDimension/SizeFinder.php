<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Product\PackageDimension;

class SizeFinder
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
    public function find(\M2E\TikTokShop\Model\Product $product): Size
    {
        $unit = $this->getUnit($product);

        try {
            return new Size(
                $this->getPackageLength($product->getMagentoProduct()),
                $this->getPackageWidth($product->getMagentoProduct()),
                $this->getPackageHeight($product->getMagentoProduct()),
                $unit
            );
        } catch (NotConfiguredException $exception) {
            throw $exception;
        } catch (PackageDimensionException $exception) {
        }

        $childSizes = [];
        foreach ($product->getVariants() as $variant) {
            try {
                $packageSize = new Size(
                    $this->getPackageLength($variant->getMagentoProduct()),
                    $this->getPackageWidth($variant->getMagentoProduct()),
                    $this->getPackageHeight($variant->getMagentoProduct()),
                    $unit
                );
                $childSizes[$packageSize->getVolumeWeight()] = $packageSize;
            } catch (PackageDimensionException $exception) {
                continue;
            }
        }

        if (count($childSizes) === 0) {
            $message = (string)__(
                'Package Dimensions are missing. To list the Product, please make sure that the ' .
                'Package settings are correct.'
            );

            throw new PackageDimensionException(
                $message,
                [],
                PackageDimensionException::CODE_DIMENSIONS_MISSING
            );
        }

        krsort($childSizes, SORT_NUMERIC);

        return reset($childSizes);
    }

    /**
     * @throws \M2E\TikTokShop\Model\Product\PackageDimension\NotConfiguredException
     * @throws \M2E\TikTokShop\Model\Product\PackageDimension\NotFoundAttributeValueException
     */
    private function getPackageLength(\M2E\TikTokShop\Model\Magento\Product $magentoProduct): string
    {
        $packageLengthSettings = $this->settingsRepository->getPackageLength();

        if ($packageLengthSettings->isModeCustomValue()) {
            $customValue = $packageLengthSettings->getCustomValue();

            if (empty($customValue)) {
                throw NotConfiguredException::createPackageLengthException();
            }

            return (string)$this->toInteger($customValue);
        }

        if ($packageLengthSettings->isModeCustomAttribute()) {
            $attributeCode = $packageLengthSettings->getCustomAttribute();
            if (empty($attributeCode)) {
                throw NotConfiguredException::createPackageLengthException();
            }

            $attributeValue = (float)$magentoProduct->getAttributeValue($attributeCode);

            if (empty($attributeValue)) {
                throw NotFoundAttributeValueException::createPackageLengthException($attributeCode);
            }

            return (string)$this->toInteger($attributeValue);
        }

        throw NotConfiguredException::createPackageLengthException();
    }

    /**
     * @throws \M2E\TikTokShop\Model\Product\PackageDimension\NotFoundAttributeValueException
     * @throws \M2E\TikTokShop\Model\Product\PackageDimension\NotConfiguredException
     */
    private function getPackageWidth(\M2E\TikTokShop\Model\Magento\Product $magentoProduct): string
    {
        $packageWidthSettings = $this->settingsRepository->getPackageWidth();

        if ($packageWidthSettings->isModeCustomValue()) {
            $customValue = $packageWidthSettings->getCustomValue();

            if (empty($customValue)) {
                throw NotConfiguredException::createPackageWidthException();
            }

            return (string)$this->toInteger($customValue);
        }

        if ($packageWidthSettings->isModeCustomAttribute()) {
            $attributeCode = $packageWidthSettings->getCustomAttribute();
            if (empty($attributeCode)) {
                throw NotConfiguredException::createPackageWidthException();
            }

            $attributeValue = (float)$magentoProduct->getAttributeValue($attributeCode);

            if (empty($attributeValue)) {
                throw NotFoundAttributeValueException::createPackageWidthException($attributeCode);
            }

            return (string)$this->toInteger($attributeValue);
        }

        throw NotConfiguredException::createPackageWidthException();
    }

    /**
     * @throws \M2E\TikTokShop\Model\Product\PackageDimension\NotConfiguredException
     * @throws \M2E\TikTokShop\Model\Product\PackageDimension\NotFoundAttributeValueException
     */
    private function getPackageHeight(\M2E\TikTokShop\Model\Magento\Product $magentoProduct): string
    {
        $packageHeightSettings = $this->settingsRepository->getPackageHeight();

        if ($packageHeightSettings->isModeCustomValue()) {
            $customValue = $packageHeightSettings->getCustomValue();

            if (empty($customValue)) {
                throw NotConfiguredException::createPackageHeightException();
            }

            return (string)$this->toInteger($customValue);
        }

        if ($packageHeightSettings->isModeCustomAttribute()) {
            $attributeCode = $packageHeightSettings->getCustomAttribute();
            if (empty($attributeCode)) {
                throw NotConfiguredException::createPackageHeightException();
            }

            $attributeValue = (float)$magentoProduct->getAttributeValue($attributeCode);

            if (empty($attributeValue)) {
                throw NotFoundAttributeValueException::createPackageHeightException($attributeCode);
            }

            return (string)$this->toInteger($attributeValue);
        }

        throw NotConfiguredException::createPackageHeightException();
    }

    private function toInteger($length): int
    {
        return (int)ceil((float)$length);
    }

    private function getUnit(\M2E\TikTokShop\Model\Product $product): string
    {
        return $product->getShop()->getRegion()->getSizeDimension();
    }
}
