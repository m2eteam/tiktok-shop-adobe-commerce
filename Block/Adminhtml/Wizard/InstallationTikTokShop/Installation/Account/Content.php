<?php

namespace M2E\TikTokShop\Block\Adminhtml\Wizard\InstallationTikTokShop\Installation\Account;

use M2E\TikTokShop\Block\Adminhtml\Magento\Form\AbstractForm;

class Content extends AbstractForm
{
    private \M2E\TikTokShop\Model\Shop\RegionCollection $regionCollection;

    public function __construct(
        \M2E\TikTokShop\Model\Shop\RegionCollection $regionCollection,
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->regionCollection = $regionCollection;
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('wizardInstallationWizardTutorial');
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->getLayout()
             ->getBlock('wizard.help.block')
             ->setContent(
                 __('Please select Account type and click <strong>Continue</strong> to connect ' .
                     'your TikTok Shop Account. Once M2E TikTok Shop Connect is authorized to access your account, you\'ll ' .
                     'be redirected back to the application')
             );

        parent::_prepareLayout();
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
            ],
        ]);

        $regionOptions = [];
        foreach ($this->regionCollection->getAll() as $region) {
            $regionOptions[$region->getRegionCode()] = $region->getLabel();
        }

        $fieldset = $form->addFieldset('region-fieldset', []);
        $fieldset->addField(
            'region',
            'select',
            [
                'label' => $this->__('Select type of the Account you would like to connect:'),
                'id' => 'region',
                'name' => 'region',
                'values' => $regionOptions,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add(
            'An error during of account creation.',
            __('The TikToks Shop token obtaining is currently unavailable. Please try again later.')
        );

        return parent::_beforeToHtml();
    }
}
