<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Validator;

use M2E\TikTokShop\Model\Category\Dictionary\Attribute\SizeChartAttribute;

class SizeChartImageUrlValidator implements ValidatorInterface
{
    use CategoryAttributeValueTrait;

    private \M2E\TikTokShop\Model\Category\Attribute\Repository $attributeRepository;

    /** @var \M2E\TikTokShop\Model\Category\CategoryAttribute[] */
    private array $sizeChartAttributes = [];
    /** @var \M2E\TikTokShop\Model\Category\Dictionary\AbstractAttribute[] */
    private array $dictionaryAttributes = [];

    public function __construct(
        \M2E\TikTokShop\Model\Category\Attribute\Repository $attributeRepository
    ) {
        $this->attributeRepository = $attributeRepository;
    }

    public function validate(
        \M2E\TikTokShop\Model\Product $product,
        \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Configurator $configurator
    ): ?ValidatorMessage {
        if (!$configurator->isCategoriesAllowed()) {
            return null;
        }

        $sizeChart = $this->getSizeChartByDictionaryId($product);
        if (
            $sizeChart !== null
            && $sizeChart->isRequired()
            && !$this->isSizeChartValid($product)
        ) {
            return new ValidatorMessage(
                (string)__('An invalid image URL is set for the Product Size Chart'),
                \M2E\TikTokShop\Model\Tag\ValidatorIssues::ERROR_INVALID_SIZE_CHART_IMAGE_URL
            );
        }

        return null;
    }

    private function getSizeChartByDictionaryId(\M2E\TikTokShop\Model\Product $product): ?SizeChartAttribute
    {
        if (!$product->hasCategoryTemplate()) {
            return null;
        }

        $dictionaryId = $product->getTemplateCategoryId();
        if (!isset($this->dictionaryAttributes[$dictionaryId])) {
            $dictionary = $product->getCategoryDictionary();
            $this->dictionaryAttributes[$dictionaryId] = $dictionary->getSizeChartAttribute();
        }

        return $this->dictionaryAttributes[$dictionaryId];
    }

    private function isSizeChartValid(\M2E\TikTokShop\Model\Product $product): bool
    {
        $attribute = $this->getSizeChartAttribute($product->getTemplateCategoryId());
        if ($attribute !== null) {
            $value = $this->getAttributeValue($attribute, $product->getMagentoProduct());
            if (
                !empty($value)
                && !\M2E\TikTokShop\Helper\Data::isValidUrl($value)
            ) {
                return false;
            }
        }

        return true;
    }

    private function getSizeChartAttribute(int $categoryId): ?\M2E\TikTokShop\Model\Category\CategoryAttribute
    {
        if (!isset($this->sizeChartAttributes[$categoryId])) {
            $this->sizeChartAttributes[$categoryId] = [];
            $attributes = $this->attributeRepository->findByDictionaryId($categoryId, [
                \M2E\TikTokShop\Model\Category\CategoryAttribute::ATTRIBUTE_TYPE_SIZE_CHART,
            ]);

            $this->sizeChartAttributes[$categoryId] = count($attributes) > 0 ? reset($attributes) : null;
        }

        return $this->sizeChartAttributes[$categoryId];
    }
}
