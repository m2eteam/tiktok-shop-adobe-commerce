<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\Settings\Tabs\Main;

class PackageWidthFields
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
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWidth $packageWidth
    ) {
        $this->addCustomAttributeField($fieldset, $packageWidth);
        $this->addModeField($fieldset, $packageWidth);
    }

    private function addCustomAttributeField(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWidth $packageWidth
    ) {
        $fieldset->addField(
            "package_width_custom_attribute",
            'hidden',
            [
                'name' => "package_width_custom_attribute",
                'value' => $packageWidth->getCustomAttribute(),
            ]
        );
    }

    private function addModeField(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWidth $packageWidth
    ) {
        $afterHtml = $this->getCustomValueInput($packageWidth)
            . $this->getInvalidSelectedAttributeWarningTooltip($packageWidth);

        $fieldset->addField(
            'package_width_mode',
            \M2E\TikTokShop\Block\Adminhtml\Magento\Form\AbstractForm::SELECT,
            [
                'name' => 'package_width_mode',
                'label' => __('Width'),
                'class' => 'validator-required-when-visible',
                'values' => [
                    \M2E\TikTokShop\Model\TikTokShop\Settings\PackageInterface::MODE_NOT_SET => __('Not Set'),
                    \M2E\TikTokShop\Model\TikTokShop\Settings\PackageInterface::MODE_CUSTOM_VALUE => __('Custom Value'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $this->getMagentoAttributesOptions($packageWidth),
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $packageWidth->getCustomAttribute(),
                'create_magento_attribute' => true,
                'tooltip' => (string)__("The package width needs to be a whole number that's not negative. " .
                    "Possible Units: US: CENTIMETER, INCH, Other regions: CENTIMETER"),
                'after_element_html' => $afterHtml,
                'required' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');
    }

    private function isValidSelectedCustomAttribute(
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWidth $packageWidth
    ): bool {
        if (!$packageWidth->isModeCustomAttribute()) {
            return true;
        }

        return $this->magentoAttributeHelper->isExistInAttributesArray(
            $packageWidth->getCustomAttribute(),
            $this->getTextMagentoAttributes()
        );
    }

    private function getMagentoAttributesOptions(
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWidth $packageWidth
    ): array {
        $preparedAttributes = [];

        if (!$this->isValidSelectedCustomAttribute($packageWidth)) {
            $attrs = ['attribute_code' => $packageWidth->getCustomAttribute()];
            $attrs['selected'] = 'selected';
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode::MODE_CUSTOM_ATTRIBUTE,
                'label' => $this->magentoAttributeHelper
                    ->getAttributeLabel($packageWidth->getCustomAttribute()),
            ];
        }

        foreach ($this->getTextMagentoAttributes() as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];

            if (
                $packageWidth->isModeCustomAttribute()
                && $attribute['code'] == $packageWidth->getCustomAttribute()
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
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWidth $packageWidth
    ): string {
        if ($this->isValidSelectedCustomAttribute($packageWidth)) {
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

    private function getCustomValueInput(
        \M2E\TikTokShop\Model\TikTokShop\Settings\PackageWidth $packageWidth
    ): string {
        $classes = [
            'TikTokShop-required-when-visible',
            'admin__control-text',
            'validator-greater-than-zero',
            'validator-tts-width',
        ];

        $attributes = [
            'type' => 'text',
            'class' => implode(' ', $classes),
            'style' => 'max-width: 150px;',
            'id' => "package_width_custom_value",
            'name' => "package_width_custom_value",
            'value' => $packageWidth->getCustomValue(),
            'placeholder' => __('Enter value here'),
        ];

        if (!$packageWidth->isModeCustomValue()) {
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
}
