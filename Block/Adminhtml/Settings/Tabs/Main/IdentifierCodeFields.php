<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\Settings\Tabs\Main;

class IdentifierCodeFields
{
    use \M2E\TikTokShop\Block\Adminhtml\Traits\TooltipTrait;

    private array $magentoTextAttributes;

    private \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper;

    public function __construct(
        \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
    }

    public function addFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode $identifierCode
    ) {
        $this->addCustomAttributeCodeField($fieldset, $identifierCode);
        $this->addModeField($fieldset, $identifierCode);
    }

    private function addCustomAttributeCodeField(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode $identifierCode
    ): void {
        $fieldset->addField(
            'identifier_code_custom_attribute',
            'hidden',
            [
                'name' => 'identifier_code_custom_attribute',
                'value' => $identifierCode->getCustomAttribute(),
            ]
        );
    }

    private function addModeField(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode $identifierCode
    ) {
        $fieldset->addField(
            'identifier_code_mode',
            \M2E\TikTokShop\Block\Adminhtml\Magento\Form\AbstractForm::SELECT,
            [
                'name' => 'identifier_code_mode',
                'label' => __('EAN / UPC'),
                'class' => 'tiktokshop-identifier-code',
                'values' => [
                    \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode::MODE_NOT_SET => __('Not Set'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $this->getMagentoAttributesOptions($identifierCode),
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $identifierCode->getCustomAttribute(),
                'create_magento_attribute' => true,
                'tooltip' => __(
                    '%channel_title uses EAN/UPC to associate your Item with its catalog. ' .
                    'Select Attribute where the Product ID values are stored.',
                    [
                        'channel_title' => \M2E\TikTokShop\Helper\Module::getChannelTitle(),
                    ]
                ),
                'after_element_html' => $this->getInvalidSelectedAttributeWarningTooltip($identifierCode),
                'required' => false,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');
    }

    private function isValidSelectedCustomAttribute(
        \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode $identifierCode
    ): bool {
        if (!$identifierCode->isModeCustomAttribute()) {
            return true;
        }

        return $this->magentoAttributeHelper->isExistInAttributesArray(
            $identifierCode->getCustomAttribute(),
            $this->getTextMagentoAttributes()
        );
    }

    private function getMagentoAttributesOptions(
        \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode $identifierCode
    ): array {
        $preparedAttributes = [];

        if (!$this->isValidSelectedCustomAttribute($identifierCode)) {
            $attrs = ['attribute_code' => $identifierCode->getCustomAttribute()];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode::MODE_CUSTOM_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper
                    ->getAttributeLabel($identifierCode->getCustomAttribute()),
            ];
        }

        foreach ($this->getTextMagentoAttributes() as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];

            if (
                $identifierCode->isModeCustomAttribute()
                && $attribute['code'] == $identifierCode->getCustomAttribute()
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode::MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        return $preparedAttributes;
    }

    private function getInvalidSelectedAttributeWarningTooltip(
        \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode $identifierCode
    ): string {
        if ($this->isValidSelectedCustomAttribute($identifierCode)) {
            return '';
        }

        $warningText = __(
            'Selected Magento Attribute is invalid. Please ensure that the Attribute ' .
            'exists in your Magento, has a relevant Input Type and it is included in all Attribute Sets. ' .
            'Otherwise, select a different Attribute from the drop-down.'
        );

        return sprintf(
            '<span class="fix-magento-tooltip m2e-tooltip-grid-warning">%s</span>',
            $this->getTooltipHtml((string)$warningText)
        );
    }

    private function getTextMagentoAttributes(): array
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->magentoTextAttributes)) {
            $this->magentoTextAttributes = $this->magentoAttributeHelper->filterByInputTypes(
                $this->magentoAttributeHelper->getAll(),
                ['text', 'select', 'weight']
            );
        }

        return $this->magentoTextAttributes;
    }
}
