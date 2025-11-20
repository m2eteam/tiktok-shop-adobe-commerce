<?php

namespace M2E\TikTokShop\Block\Adminhtml\TikTokShop\Account\Edit\Tabs;

use M2E\TikTokShop\Block\Adminhtml\Magento\Form\AbstractForm;

class InvoicesAndShipments extends AbstractForm
{
    private ?\M2E\TikTokShop\Model\Account $account;

    public function __construct(
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        ?\M2E\TikTokShop\Model\Account $account = null,
        array $data = []
    ) {
        $this->account = $account;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $invoicesAndShipmentSettings = new \M2E\TikTokShop\Model\Account\Settings\InvoicesAndShipment();
        if ($this->account !== null) {
            $invoicesAndShipmentSettings = $this->account->getInvoiceAndShipmentSettings();
        }

        $form = $this->_formFactory->create();

        $form->addField(
            'invoices_and_shipments',
            self::HELP_BLOCK,
            [
                'content' => __(
                    '<p>Under this tab, you can set %extension_title to automatically create ' .
                    'invoices and shipments in your Magento. To do that, keep Magento ' .
                    '<i>Invoice/Shipment Creation</i> options enabled.</p>',
                    [
                        'extension_title' => \M2E\TikTokShop\Helper\Module::getExtensionTitle(),
                    ]
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'invoices',
            [
                'legend' => __('Invoices'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'create_magento_invoice',
            'select',
            [
                'label' => __('Magento Invoice Creation'),
                'title' => __('Magento Invoice Creation'),
                'name' => 'create_magento_invoice',
                'values' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
                'value' => (int)$invoicesAndShipmentSettings->isCreateMagentoInvoice(),
                'tooltip' => __('Enable to automatically create Magento Invoices when payment is completed.'),
            ]
        );

        $fieldset = $form->addFieldset(
            'shipments',
            [
                'legend' => __('Shipments'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'create_magento_shipment',
            \Magento\Framework\Data\Form\Element\Select::class,
            [
                'label' => __('Magento Shipment Creation'),
                'title' => __('Magento Shipment Creation'),
                'name' => 'create_magento_shipment',
                'values' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
                'value' => (int)$invoicesAndShipmentSettings->isCreateMagentoShipment(),
                'tooltip' => __(
                    'Enable to automatically create shipment for the Magento order when the ' .
                    'associated order on Channel is shipped.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'shipments_carrier_mapping',
            [
                'legend' => __('Shipping Carrier Mapping'),
                'collapsable' => false,
                'tooltip' => __(
                    'Link TikTok Shop carriers to the shipping services in Magento ' .
                    'to ensure that the correct shipping service is used for each order.'
                ),
            ]
        );

        $fieldset->addField(
            'map_shipping_provider_by_custom_carrier_title',
            'select',
            [
                'name' => 'map_shipping_provider_by_custom_carrier_title',
                'label' => __('Map by Magento Shipping Title'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => (int)$invoicesAndShipmentSettings->isMapShippingProviderByCustomCarrierTitle(),
                'tooltip' => __(
                    'Enable it if your Magento shipments use Custom Value for carrier. ' .
                    'The system will attempt to match TikTok Shop carriers by the carrier title provided ' .
                    'in the order Shipment'
                ),
            ]
        );

        $shippingMappingField = $fieldset->addField(
            'shipping_provider_mapping',
            \M2E\TikTokShop\Block\Adminhtml\TikTokShop\Account\Edit\Form\Element\ShippingProviderMapping::class,
            [
                'account' => $this->account,
                'exist_shipping_provider_mapping' => $this->getShippingProviderMappingData(),
            ]
        );

        /** @var \M2E\TikTokShop\Block\Adminhtml\TikTokShop\Account\Edit\Form\Render $render */
        $render = $this
            ->getLayout()
            ->createBlock(\M2E\TikTokShop\Block\Adminhtml\TikTokShop\Account\Edit\Form\Render::class);
        $shippingMappingField->setRenderer($render);

        $form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function getShippingProviderMappingData(): array
    {
        $shippingProviderMapping = [];
        foreach ($this->account->getShops() as $shop) {
            foreach ($shop->getWarehouses() as $warehouse) {
                $shippingProviderMapping[$warehouse->getWarehouseId()] = $warehouse
                    ->getShippingProviderMapping()
                    ->toArray();
            }
        }

        return $shippingProviderMapping;
    }
}
