<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Controller\Adminhtml\Listing\Wizard\Category;

use M2E\TikTokShop\Model\Listing\Wizard\StepDeclarationCollectionFactory;

class CompleteStep extends \M2E\TikTokShop\Controller\Adminhtml\AbstractListing
{
    use \M2E\TikTokShop\Controller\Adminhtml\Listing\Wizard\WizardTrait;

    private \M2E\TikTokShop\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory;

    public function __construct(
        \M2E\TikTokShop\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory
    ) {
        parent::__construct();

        $this->wizardManagerFactory = $wizardManagerFactory;
    }

    public function execute()
    {
        $id = $this->getWizardIdFromRequest();

        $manager = $this->wizardManagerFactory->createById($id);

        if ($this->isNeedRemoveProductsWithoutCategory()) {
            $manager->removeProductsWithoutCategory();
        }

        $manager->completeStep(StepDeclarationCollectionFactory::STEP_SELECT_CATEGORY);

        return $this->redirectToIndex($id);
    }

    private function isNeedRemoveProductsWithoutCategory(): bool
    {
        return (bool)$this->getRequest()->getParam('remove_products_without_category');
    }
}
