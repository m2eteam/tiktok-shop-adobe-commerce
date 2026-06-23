<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\Listing\Wizard\Category;

class MagentoCategory extends \M2E\TikTokShop\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    use \M2E\TikTokShop\Block\Adminhtml\Listing\Wizard\WizardTrait;

    private \M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory\CategoryDataCollection $categoryDataCollection;
    private \M2E\TikTokShop\Model\Listing\Wizard\Ui\RuntimeStorage $uiWizardRuntimeStorage;

    public function __construct(
        \M2E\TikTokShop\Model\Listing\Wizard\Category\ModeMagentoCategory\CategoryDataCollection $categoryDataCollection,
        \M2E\TikTokShop\Model\Listing\Wizard\Ui\RuntimeStorage $uiWizardRuntimeStorage,
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->categoryDataCollection = $categoryDataCollection;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('listingCategoryManually');

        $this->prepareButtons(
            [
                'id' => 'listing_category_continue_btn',
                'class' => 'action-primary forward',
                'label' => __('Continue'),
                'onclick' => 'ListingWizardCategoryModeMagentoCategoryGridObj.completeCategoriesDataStep(1, 0);',
            ],
            $this->uiWizardRuntimeStorage->getManager(),
        );
    }

    protected function _prepareLayout()
    {
        $gridBlock = $this
            ->getLayout()
            ->createBlock(
                \M2E\TikTokShop\Block\Adminhtml\Listing\Wizard\Category\ModeMagentoCategory\Grid::class,
                '',
                [
                    'categoryDataCollection' => $this->categoryDataCollection,
                ],
            );

        $this->setChild('grid', $gridBlock);

        return parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        $this->js->add(
            <<<JS
 require([
    'TikTokShop/Category/Chooser/SelectedProductsData'
], function() {
     window.SelectedProductsDataObj = new SelectedProductsData();

     SelectedProductsDataObj.setWizardId('{$this->getWizardId()}');
     SelectedProductsDataObj.setShopId('{$this->getShopId()}');
});
JS,
        );

        return parent::_beforeToHtml();
    }

    private function getWizardId(): int
    {
        return $this->uiWizardRuntimeStorage->getManager()->getWizardId();
    }

    public function getShopId(): int
    {
        return $this->uiWizardRuntimeStorage->getManager()->getListing()->getShopId();
    }
}
