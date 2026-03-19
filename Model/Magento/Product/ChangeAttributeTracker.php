<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Magento\Product;

class ChangeAttributeTracker
{
    private const INSTRUCTION_INITIATOR = 'magento_product_change_processor';

    public const INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED = 'magento_product_data_potentially_changed';
    public const INSTRUCTION_TYPE_TITLE_DATA_CHANGED = 'magento_product_title_data_changed';
    public const INSTRUCTION_TYPE_DESCRIPTION_DATA_CHANGED = 'magento_product_description_data_changed';
    public const INSTRUCTION_TYPE_IMAGES_DATA_CHANGED = 'magento_product_images_data_changed';
    public const INSTRUCTION_TYPE_CATEGORIES_DATA_CHANGED = 'magento_product_categories_data_changed';

    private array $instructionsData = [];

    private \M2E\TikTokShop\Model\Product $listingProduct;
    private \M2E\TikTokShop\Model\Template\Description $templateDescription;
    private \M2E\TikTokShop\Model\InstructionService $instructionService;
    private \M2E\TikTokShop\Model\TikTokShop\Settings\Repository $settingsRepository;

    public function __construct(
        \M2E\TikTokShop\Model\InstructionService $instructionService,
        \M2E\TikTokShop\Model\Product $listingProduct,
        \M2E\TikTokShop\Model\Template\Description $templateDescription,
        \M2E\TikTokShop\Model\TikTokShop\Settings\Repository $settingsRepository
    ) {
        $this->listingProduct = $listingProduct;
        $this->templateDescription = $templateDescription;
        $this->instructionService = $instructionService;
        $this->settingsRepository = $settingsRepository;
    }

    public function addInstructionWithPotentiallyChangedType(): void
    {
        $this->addInstruction(self::INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED, 100);
    }

    public function addInstructionsByChangedAttributes($changedAttributes): void
    {
        $priority = $this->listingProduct->isStatusListed() ? 30 : 5;

        if (array_intersect($changedAttributes, $this->getTitleTrackingAttributes())) {
            $this->addInstruction(self::INSTRUCTION_TYPE_TITLE_DATA_CHANGED, $priority);
        }

        if (array_intersect($changedAttributes, $this->getDescriptionTrackingAttributes())) {
            $this->addInstruction(self::INSTRUCTION_TYPE_DESCRIPTION_DATA_CHANGED, $priority);
        }

        if (array_intersect($changedAttributes, $this->getImagesTrackingAttributes())) {
            $this->addInstruction(self::INSTRUCTION_TYPE_IMAGES_DATA_CHANGED, $priority);
        }

        if (array_intersect($changedAttributes, $this->getCategoriesTrackingAttributes())) {
            $this->addInstruction(self::INSTRUCTION_TYPE_CATEGORIES_DATA_CHANGED, $priority);
        }
    }

    private function addInstruction(string $type, int $priority): void
    {
        $this->instructionsData[$type] = [
            'listing_product_id' => $this->listingProduct->getId(),
            'type' => $type,
            'initiator' => self::INSTRUCTION_INITIATOR,
            'priority' => $priority,
        ];
    }

    public function flushInstructions(): void
    {
        if (empty($this->instructionsData)) {
            return;
        }

        $instructionsData = array_values($this->instructionsData);
        $this->instructionService->createBatch($instructionsData);

        $this->instructionsData = [];
    }

    public function getTrackingAttributes(): array
    {
        return array_unique(
            array_merge(
                $this->getTitleTrackingAttributes(),
                $this->getDescriptionTrackingAttributes(),
                $this->getImagesTrackingAttributes(),
                $this->getCategoriesTrackingAttributes(),
                $this->getPackageTrackingAttributes(),
            )
        );
    }

    private function getTitleTrackingAttributes(): array
    {
        return array_unique($this->templateDescription->getTitleTrackedAttributes());
    }

    private function getDescriptionTrackingAttributes(): array
    {
        return array_unique($this->templateDescription->getDescriptionTrackedAttributes());
    }

    private function getImagesTrackingAttributes(): array
    {
        $trackingAttributes = array_merge(
            $this->templateDescription->getImageMainTrackedAttributes(),
            $this->templateDescription->getGalleryImagesTrackedAttributes(),
        );

        return array_unique($trackingAttributes);
    }

    private function getCategoriesTrackingAttributes(): array
    {
        $syncTemplate = $this->listingProduct->getSynchronizationTemplate();
        if (!$syncTemplate->isReviseUpdateCategories()) {
            return [];
        }

        if (!$this->listingProduct->hasCategoryTemplate()) {
            return [];
        }

        return array_unique($this->listingProduct->getCategoryDictionary()->getTrackedAttributes());
    }

    private function getPackageTrackingAttributes(): array
    {
        $attributes = [
            $this->settingsRepository->getPackageWidth()->getCustomAttribute(),
            $this->settingsRepository->getPackageLength()->getCustomAttribute(),
            $this->settingsRepository->getPackageHeight()->getCustomAttribute(),
            $this->settingsRepository->getPackageWeight()->getCustomAttribute(),
        ];

        return array_unique(array_filter($attributes));
    }
}
