<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\Settings\Tabs;

class Main extends \M2E\TikTokShop\Block\Adminhtml\Magento\Form\AbstractForm
{
    private \M2E\TikTokShop\Model\TikTokShop\Settings\Repository $settingRepository;
    private Main\IdentifierCodeFields $identifierCodeFields;
    private Main\PackageWeightFields $packageWeightFields;
    private Main\PackageLengthFields $packageLengthFields;
    private Main\PackageWidthFields $packageWidthFields;
    private Main\PackageHeightFields $packageHeightFields;

    public function __construct(
        \M2E\TikTokShop\Model\TikTokShop\Settings\Repository $settingRepository,
        \M2E\TikTokShop\Block\Adminhtml\Settings\Tabs\Main\IdentifierCodeFields $identifierCodeFields,
        \M2E\TikTokShop\Block\Adminhtml\Settings\Tabs\Main\PackageWeightFields $packageWeightFields,
        \M2E\TikTokShop\Block\Adminhtml\Settings\Tabs\Main\PackageLengthFields $packageLengthFields,
        \M2E\TikTokShop\Block\Adminhtml\Settings\Tabs\Main\PackageWidthFields $packageWidthFields,
        \M2E\TikTokShop\Block\Adminhtml\Settings\Tabs\Main\PackageHeightFields $packageHeightFields,
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->settingRepository = $settingRepository;
        $this->identifierCodeFields = $identifierCodeFields;
        $this->packageWeightFields = $packageWeightFields;
        $this->packageLengthFields = $packageLengthFields;
        $this->packageWidthFields = $packageWidthFields;
        $this->packageHeightFields = $packageHeightFields;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $productFieldset = $form->addFieldset(
            'product_settings_fieldset',
            [
                'legend' => __('Product'),
                'collapsable' => false,
            ]
        );

        $this->identifierCodeFields->addFields(
            $productFieldset,
            $this->settingRepository->getIdentifierCode()
        );

        $packageFieldset = $form->addFieldset(
            'package_settings_fieldset',
            [
                'legend' => __('Package'),
                'collapsable' => false,
            ]
        );

        $this->packageWeightFields->addFields(
            $packageFieldset,
            $this->settingRepository->getPackageWeight()
        );
        $this->packageLengthFields->addFields(
            $packageFieldset,
            $this->settingRepository->getPackageLength()
        );
        $this->packageWidthFields->addFields(
            $packageFieldset,
            $this->settingRepository->getPackageWidth()
        );
        $this->packageHeightFields->addFields(
            $packageFieldset,
            $this->settingRepository->getPackageHeight()
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/settings/save'),
            \M2E\TikTokShop\Block\Adminhtml\Settings\Tabs::TAB_ID_MAIN
        );

        $this->jsPhp->addConstants([
            '\M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode::MODE_CUSTOM_ATTRIBUTE' =>
                \M2E\TikTokShop\Model\TikTokShop\Settings\IdentifierCode::MODE_CUSTOM_ATTRIBUTE,
            '\M2E\TikTokShop\Model\TikTokShop\Settings\Package::MODE_CUSTOM_ATTRIBUTE' =>
                \M2E\TikTokShop\Model\TikTokShop\Settings\PackageInterface::MODE_CUSTOM_ATTRIBUTE,
            '\M2E\TikTokShop\Model\TikTokShop\Settings\Package::MODE_CUSTOM_VALUE' =>
                \M2E\TikTokShop\Model\TikTokShop\Settings\PackageInterface::MODE_CUSTOM_VALUE,
        ]);

        $jsScript = <<<JS
require([
    'TikTokShop/Settings/Main'
], function(){
    window.TikTokShopSettingsMainObj = new TikTokShopSettingsMain();
});
JS;

        $this->js->add($jsScript);

        return parent::_beforeToHtml();
    }
}
