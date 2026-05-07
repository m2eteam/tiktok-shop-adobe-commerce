<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Validator;

use M2E\TikTokShop\Model\Category\CategoryAttribute;

class CertificatesImageUrlValidator implements ValidatorInterface
{
    use CategoryAttributeValueTrait;

    private \M2E\TikTokShop\Model\Category\Attribute\Repository $attributeRepository;

    /** @var \M2E\TikTokShop\Model\Category\Dictionary\AbstractAttribute[] */
    private array $dictionaryAttributes = [];
    /** @var \M2E\TikTokShop\Model\Category\CategoryAttribute[] */
    private array $certificateAttributes = [];

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

        $certificates = $this->getDictionaryCertificates($product);
        foreach ($certificates as $certificate) {
            if (
                $certificate->isRequired()
                && !$this->isCertificateValid($product, $certificate->getId())
            ) {
                return new ValidatorMessage(
                    (string)__(
                        'An invalid image URL is set for the Product Certificate "%1"',
                        $certificate->getName()
                    ),
                    \M2E\TikTokShop\Model\Tag\ValidatorIssues::ERROR_INVALID_CERTIFICATE_IMAGE_URL
                );
            }
        }

        return null;
    }

    /**
     * @param \M2E\TikTokShop\Model\Product $product
     *
     * @return \M2E\TikTokShop\Model\Category\Dictionary\Attribute\CertificateAttribute[]
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     */
    private function getDictionaryCertificates(\M2E\TikTokShop\Model\Product $product): array
    {
        if (!$product->hasCategoryTemplate()) {
            return [];
        }

        $dictionaryId = $product->getTemplateCategoryId();
        if (!isset($this->dictionaryAttributes[$dictionaryId])) {
            $dictionary = $product->getCategoryDictionary();
            $this->dictionaryAttributes[$dictionaryId] = $dictionary->getCertificationsAttributes();
        }

        return $this->dictionaryAttributes[$dictionaryId];
    }

    private function isCertificateValid(\M2E\TikTokShop\Model\Product $product, string $certificateId): bool
    {
        $attributes = $this->getCertificateAttributeById($product->getTemplateCategoryId(), $certificateId);
        foreach ($attributes as $item) {
            $value = $this->getAttributeValue($item, $product->getMagentoProduct());
            if (
                !empty($value)
                && !\M2E\TikTokShop\Helper\Data::isValidUrl($value)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $categoryId
     * @param string $attributeId
     *
     * @return CategoryAttribute[]
     */
    private function getCertificateAttributeById(int $categoryId, string $attributeId): array
    {
        $attributes = $this->getCertificateAttributes($categoryId);

        $result = [];
        foreach ($attributes as $attributeItem) {
            if (
                $attributeId === $attributeItem->getAttributeId()
                || $attributeId === CategoryAttribute::getCleanAttributeId($attributeItem->getAttributeId())
            ) {
                $result[] = $attributeItem;
            }
        }

        return $result;
    }

    /**
     * @param int $categoryId
     *
     * @return CategoryAttribute[]
     */
    private function getCertificateAttributes(int $categoryId): array
    {
        if (!isset($this->certificateAttributes[$categoryId])) {
            $this->certificateAttributes[$categoryId] = [];
            $attributes = $this->attributeRepository->findByDictionaryId($categoryId, [
                CategoryAttribute::ATTRIBUTE_TYPE_CERTIFICATE,
            ]);

            foreach ($attributes as $attribute) {
                $this->certificateAttributes[$categoryId][$attribute->getAttributeId()] = $attribute;
            }
        }

        return $this->certificateAttributes[$categoryId];
    }
}
