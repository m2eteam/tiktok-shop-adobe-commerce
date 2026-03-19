<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Controller\Adminhtml\Settings;

use M2E\TikTokShop\Controller\Adminhtml\TikTokShop\AbstractSettings;

class Save extends AbstractSettings
{
    private \M2E\TikTokShop\Model\TikTokShop\Settings\Save $settingsSave;

    public function __construct(
        \M2E\TikTokShop\Model\TikTokShop\Settings\Save $settingsSave
    ) {
        parent::__construct();
        $this->settingsSave = $settingsSave;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();
        if (!$post) {
            $this->setJsonContent(['success' => false]);

            return $this->getResult();
        }

        $this->settingsSave->execute($post->toArray());
        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
