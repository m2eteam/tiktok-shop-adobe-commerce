<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\Settings\Tabs\Main;

class PackageWeightFields
{
    use \M2E\TikTokShop\Block\Adminhtml\Traits\TooltipTrait;

    private array $magentoTextAttributes;

    private \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper;
    private \Magento\Framework\Escaper $escaper;

    public function __construct(
        \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->escaper = $escaper;
    }

    public function addFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWeight $packageWeight
    ) {
        $this->addCustomAttributeField($fieldset, $packageWeight);
        $this->addModeField($fieldset, $packageWeight);
    }

    private function isValidSelectedCustomAttribute(
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWeight $packageWeight
    ): bool {
        if (!$packageWeight->isModeCustomAttribute()) {
            return true;
        }

        return $this->magentoAttributeHelper->isExistInAttributesArray(
            $packageWeight->getCustomAttribute(),
            $this->getTextMagentoAttributes()
        );
    }

    private function getMagentoAttributesOptions(
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWeight $packageWeight
    ): array {
        $preparedAttributes = [];

        if (!$this->isValidSelectedCustomAttribute($packageWeight)) {
            $attrs = ['attribute_code' => $packageWeight->getCustomAttribute()];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode::MODE_CUSTOM_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper
                    ->getAttributeLabel($packageWeight->getCustomAttribute()),
            ];
        }

        foreach ($this->getTextMagentoAttributes() as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];

            if (
                $packageWeight->isModeCustomAttribute()
                && $attribute['code'] == $packageWeight->getCustomAttribute()
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
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWeight $packageWeight
    ): string {
        if ($this->isValidSelectedCustomAttribute($packageWeight)) {
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

    private function addCustomAttributeField(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWeight $packageWeight
    ) {
        $fieldset->addField(
            "package_weight_custom_attribute",
            'hidden',
            [
                'name' => "package_weight_custom_attribute",
                'value' => $packageWeight->getCustomAttribute(),
            ]
        );
    }

    private function addModeField(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWeight $packageWeight
    ) {
        $afterHtml = $this->getCustomValueInput($packageWeight)
            . $this->getInvalidSelectedAttributeWarningTooltip($packageWeight);

        $fieldset->addField(
            'package_weight_mode',
            \M2E\TikTokShop\Block\Adminhtml\Magento\Form\AbstractForm::SELECT,
            [
                'name' => 'package_weight_mode',
                'label' => __('Weight'),
                'class' => 'validator-required-when-visible',
                'values' => [
                    \M2E\TikTokShop\Model\TikTokShop\Settings\PackageInterface::MODE_NOT_SET => __('Not Set'),
                    \M2E\TikTokShop\Model\TikTokShop\Settings\PackageInterface::MODE_CUSTOM_VALUE => __('Custom Value'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $this->getMagentoAttributesOptions($packageWeight),
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $this->getSelectedValue($packageWeight),
                'create_magento_attribute' => true,
                'tooltip' => (string)__("The package weight must be a positive number. ( kg (UK), lb (US))"),
                'after_element_html' => $afterHtml,
                'required' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');
    }

    private function getCustomValueInput(
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWeight $packageWeight
    ): string {
        $classes = [
            'TikTokShop-required-when-visible',
            'admin__control-text',
            'validator-greater-than-zero',
            'validator-tts-weight',
        ];

        $attributes = [
            'type' => 'text',
            'class' => implode(' ', $classes),
            'style' => 'max-width: 150px;',
            'id' => "package_weight_custom_value",
            'name' => "package_weight_custom_value",
            'value' => $packageWeight->getCustomValue(),
            'placeholder' => __('Enter value here'),
        ];

        if (!$packageWeight->isModeCustomValue()) {
            $attributes['style'] .= 'display:none';
        }

        return '<input ' . $this->renderHtmlAttributes($attributes) . '>';
    }

    private function renderHtmlAttributes(array $attributes): string
    {
        $preparedAttributes = [];
        foreach ($attributes as $attributeName => $attributeValue) {
            $preparedAttributes[] = sprintf(
                '%s="%s"',
                $attributeName,
                $this->escaper->escapeHtml($attributeValue)
            );
        }

        return implode(' ', $preparedAttributes);
    }

    private function getSelectedValue(\M2E\TikTokShop\Model\TikTokShop\Settings\PackageWeight $packageWeight): string
    {
        if ($packageWeight->isModeCustomAttribute()) {
            return '';
        }

        return (string)$packageWeight->getMode();
    }
}
