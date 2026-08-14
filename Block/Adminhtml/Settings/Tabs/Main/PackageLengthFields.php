<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\Settings\Tabs\Main;

class PackageLengthFields
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
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageLength $packageLength
    ) {
        $this->addCustomAttributeField($fieldset, $packageLength);
        $this->addModeField($fieldset, $packageLength);
    }

    private function isValidSelectedCustomAttribute(
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageLength $packageLength
    ): bool {
        if (!$packageLength->isModeCustomAttribute()) {
            return true;
        }

        return $this->magentoAttributeHelper->isExistInAttributesArray(
            $packageLength->getCustomAttribute(),
            $this->getTextMagentoAttributes()
        );
    }

    private function getMagentoAttributesOptions(
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageLength $packageLength
    ): array {
        $preparedAttributes = [];

        if (!$this->isValidSelectedCustomAttribute($packageLength)) {
            $attrs = ['attribute_code' => $packageLength->getCustomAttribute()];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode::MODE_CUSTOM_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper
                    ->getAttributeLabel($packageLength->getCustomAttribute()),
            ];
        }

        foreach ($this->getTextMagentoAttributes() as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];

            if (
                $packageLength->isModeCustomAttribute()
                && $attribute['code'] == $packageLength->getCustomAttribute()
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
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageLength $packageLength
    ): string {
        if ($this->isValidSelectedCustomAttribute($packageLength)) {
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
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageLength $packageLength
    ) {
        $fieldset->addField(
            "package_length_custom_attribute",
            'hidden',
            [
                'name' => "package_length_custom_attribute",
                'value' => $packageLength->getCustomAttribute(),
            ]
        );
    }

    private function addModeField(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageLength $packageLength
    ) {
        $afterHtml = $this->getCustomValueInput($packageLength)
            . $this->getInvalidSelectedAttributeWarningTooltip($packageLength);

        $fieldset->addField(
            'package_length_mode',
            \M2E\TikTokShop\Block\Adminhtml\Magento\Form\AbstractForm::SELECT,
            [
                'name' => 'package_length_mode',
                'label' => __('Length'),
                'class' => 'validator-required-when-visible',
                'values' => [
                    \M2E\TikTokShop\Model\TikTokShop\Settings\PackageInterface::MODE_NOT_SET => __('Not Set'),
                    \M2E\TikTokShop\Model\TikTokShop\Settings\PackageInterface::MODE_CUSTOM_VALUE => __('Custom Value'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $this->getMagentoAttributesOptions($packageLength),
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $this->getSelectedValue($packageLength),
                'create_magento_attribute' => true,
                'tooltip' => (string)__("The package length needs to be a whole number that's not negative. " .
                    "Possible Units: US: CENTIMETER, INCH, Other  regions: CENTIMETER"),
                'after_element_html' => $afterHtml,
                'required' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');
    }

    private function getCustomValueInput(
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageLength $packageLength
    ): string {
        $classes = [
            'TikTokShop-required-when-visible',
            'admin__control-text',
            'validator-greater-than-zero',
            'validator-tts-length',
        ];

        $attributes = [
            'type' => 'text',
            'class' => implode(' ', $classes),
            'style' => 'max-width: 150px;',
            'id' => "package_length_custom_value",
            'name' => "package_length_custom_value",
            'value' => $packageLength->getCustomValue(),
            'placeholder' => __('Enter value here'),
        ];

        if (!$packageLength->isModeCustomValue()) {
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

    private function getSelectedValue(\M2E\TikTokShop\Model\TikTokShop\Settings\PackageLength $packageLength): string
    {
        if ($packageLength->isModeCustomAttribute()) {
            return '';
        }

        return (string)$packageLength->getMode();
    }
}
