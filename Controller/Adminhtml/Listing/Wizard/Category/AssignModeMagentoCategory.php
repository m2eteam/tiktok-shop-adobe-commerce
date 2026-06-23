<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Controller\Adminhtml\Listing\Wizard\Category;

use M2E\TikTokShop\Controller\Adminhtml\TikTokShop\AbstractListing;

class AssignModeMagentoCategory extends AbstractListing
{
    use \M2E\TikTokShop\Controller\Adminhtml\Listing\Wizard\WizardTrait;

    private \M2E\TikTokShop\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory;
    private \M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory\AssignCategory $assignCategoryForMagentoCategory;

    public function __construct(
        \M2E\TikTokShop\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory,
        \M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory\AssignCategory $assignCategoryForMagentoCategory
    ) {
        parent::__construct();
        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->assignCategoryForMagentoCategory = $assignCategoryForMagentoCategory;
    }

    public function execute()
    {
        $wizardId = $this->getWizardIdFromRequest();
        $manager = $this->wizardManagerFactory->createById($wizardId);
        $dictionaryId = $this->getDictionaryIdFromRequest();

        foreach ($this->getMagentoCategoryIds() as $magentoCategoryId) {
            $this->assignCategoryForMagentoCategory
                ->assignAndUpdateCategoryMappingInListing(
                    $manager,
                    $magentoCategoryId,
                    $dictionaryId
                );
        }

        return $this->getResult();
    }

    /**
     * @return int[]
     */
    private function getMagentoCategoryIds(): array
    {
        return array_map('intval', $this->getRequestIds('products_id'));
    }

    private function getDictionaryIdFromRequest(): int
    {
        $templateData = $this->getRequest()->getParam('template_data');
        $templateData = (array)\M2E\Core\Helper\Json::decode($templateData);

        return (int)$templateData['dictionaryId'];
    }
}
