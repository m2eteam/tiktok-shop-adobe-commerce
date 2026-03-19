<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Controller\Adminhtml\Wizard\InstallationTikTokShop;

class SettingsContinue extends Installation
{
    private \M2E\TikTokShop\Model\TikTokShop\Settings\Save $settingsSave;
    private \M2E\TikTokShop\Model\Warehouse\ShippingMappingUpdater $shippingMappingUpdater;

    public function __construct(
        \M2E\TikTokShop\Model\TikTokShop\Settings\Save $settingsSave,
        \M2E\TikTokShop\Model\Warehouse\ShippingMappingUpdater $shippingMappingUpdater,
        \M2E\TikTokShop\Helper\Magento $magentoHelper,
        \M2E\TikTokShop\Helper\Module\Wizard $wizardHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \M2E\Core\Model\LicenseService $licenseService
    ) {
        parent::__construct(
            $magentoHelper,
            $wizardHelper,
            $nameBuilder,
            $licenseService,
        );
        $this->settingsSave = $settingsSave;
        $this->shippingMappingUpdater = $shippingMappingUpdater;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params)) {
            return $this->indexAction();
        }

        $this->settingsSave->execute($params);
        $this->saveShippingMapping($params['shipping_provider_mapping'] ?? []);

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }

    private function saveShippingMapping(array $data): void
    {
        if (empty($data)) {
            return;
        }

        foreach ($data as $warehouseId => $warehouseData) {
            $this->shippingMappingUpdater->update((string)$warehouseId, $warehouseData);
        }
    }
}
