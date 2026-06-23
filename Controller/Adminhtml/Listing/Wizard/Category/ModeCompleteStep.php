<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Controller\Adminhtml\Listing\Wizard\Category;

use M2E\TikTokShop\Block\Adminhtml\Listing\Wizard\CategorySelectMode;
use M2E\TikTokShop\Model\Listing\Wizard\StepDeclarationCollectionFactory;

class ModeCompleteStep extends \M2E\TikTokShop\Controller\Adminhtml\AbstractListing
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

        $mode = $this->getRequest()->getParam('mode');
        if (empty($mode)) {
            return $this->redirectToIndex($id);
        }

        if (
            !in_array($mode, [
                CategorySelectMode::MODE_SAME,
                CategorySelectMode::MODE_MANUALLY,
                CategorySelectMode::MODE_CATEGORY,
            ])
        ) {
            throw new \LogicException(sprintf('Category mode %s not valid.', $mode));
        }

        $manager->setStepData(StepDeclarationCollectionFactory::STEP_SELECT_CATEGORY_MODE, [
            'mode' => $mode,
        ]);

        $manager->completeStep(StepDeclarationCollectionFactory::STEP_SELECT_CATEGORY_MODE);

        return $this->redirectToIndex($id);
    }
}
