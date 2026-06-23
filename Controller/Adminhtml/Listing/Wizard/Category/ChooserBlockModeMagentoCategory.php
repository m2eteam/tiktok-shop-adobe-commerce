<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Controller\Adminhtml\Listing\Wizard\Category;

use M2E\TikTokShop\Controller\Adminhtml\TikTokShop\AbstractListing;

class ChooserBlockModeMagentoCategory extends AbstractListing
{
    use \M2E\TikTokShop\Controller\Adminhtml\Listing\Wizard\WizardTrait;

    private \M2E\TikTokShop\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory;
    private \M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory\DataProvider $magentoCategoryDataProvider;

    public function __construct(
        \M2E\TikTokShop\Model\Listing\Wizard\ManagerFactory $wizardManagerFactory,
        \M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory\DataProvider $magentoCategoryDataProvider
    ) {
        parent::__construct();

        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->magentoCategoryDataProvider = $magentoCategoryDataProvider;
    }

    public function execute()
    {
        $wizardManagerId = $this->getWizardIdFromRequest();
        $wizardManager = $this->wizardManagerFactory->createById($wizardManagerId);

        $selectedCategory = $this->chooseSelectedTiktokShopCategory(
            $wizardManager->getListing(),
            $this->getMagentoCategoryIdsFromRequest()
        );

        /** @var \M2E\TikTokShop\Block\Adminhtml\Category\CategoryChooser $chooserBlock */
        $chooserBlock = $this
            ->getLayout()
            ->createBlock(
                \M2E\TikTokShop\Block\Adminhtml\Category\CategoryChooser::class,
                '',
                [
                    'listing' => $wizardManager->getListing(),
                    'selectedCategory' => $selectedCategory,
                ],
            );

        $this->setAjaxContent($chooserBlock->toHtml());

        return $this->getResult();
    }

    /**
     * @return int[]
     */
    private function getMagentoCategoryIdsFromRequest(): array
    {
        $categoriesString = $this->getRequest()->getParam('category_ids');

        return array_map('intval', explode(',', $categoriesString));
    }

    private function chooseSelectedTiktokShopCategory(
        \M2E\TikTokShop\Model\Listing $listing,
        array $magentoCategoryIds
    ): ?string {
        $categoryDataCollection = $this->magentoCategoryDataProvider->getCategoryDataCollection($listing);

        $tikTokShopCategoryIds = [];
        foreach ($magentoCategoryIds as $magentoCategoryId) {
            $mappingItem = $categoryDataCollection->findByMagentoCategoryId($magentoCategoryId);
            if ($mappingItem === null) {
                $tikTokShopCategoryIds[] = null;
            } else {
                $tikTokShopCategoryIds[] = $mappingItem->tikTokShopCategoryId;
            }
        }

        $selectedCategory = null;
        if (count(array_unique($tikTokShopCategoryIds)) === 1) {
            $selectedCategory = (string)$tikTokShopCategoryIds[0];
        }

        return $selectedCategory;
    }
}
