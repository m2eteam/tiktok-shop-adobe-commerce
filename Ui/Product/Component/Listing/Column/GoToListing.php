<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Ui\Product\Component\Listing\Column;

class GoToListing extends \Magento\Ui\Component\Listing\Columns\Column
{
    private \M2E\TikTokShop\Model\Product\Ui\RuntimeStorage $productUiRuntimeStorage;
    private \M2E\Core\Helper\Url $urlHelper;
    private \M2E\Core\Helper\Magento\Assets $magentoAssets;

    public function __construct(
        \M2E\TikTokShop\Model\Product\Ui\RuntimeStorage $productUiRuntimeStorage,
        \M2E\Core\Helper\Url $urlHelper,
        \M2E\Core\Helper\Magento\Assets $magentoAssets,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->productUiRuntimeStorage = $productUiRuntimeStorage;
        $this->urlHelper = $urlHelper;
        $this->magentoAssets = $magentoAssets;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$row) {
            $product = $this->productUiRuntimeStorage->findProduct((int)$row['product_id']);
            if (empty($product)) {
                continue;
            }

            $html = sprintf(
                '%s%s',
                $this->createFoundInChildrenIcon($row),
                $this->createGoToListingLink($product),
            );

            $row['go_to_listing'] = $html;
        }

        return $dataSource;
    }

    private function createGoToListingLink(\M2E\TikTokShop\Model\Product $product): string
    {
        $goToListingUrl = $this->urlHelper->getUrlWithFilter(
            'm2e_tiktokshop/tiktokshop_listing/view/',
            [
                'product_id' => [
                    'from' => $product->getMagentoProductId(),
                    'to' => $product->getMagentoProductId(),
                ],
            ],
            [
                'back' => $this->urlHelper->makeBackUrlParam('m2e_tiktokshop/product_grid/allItems'),
                'id' => $product->getListingId(),
                'view_mode' => \M2E\TikTokShop\Block\Adminhtml\Listing\View\Switcher::VIEW_MODE_TIKTOKSHOP,
            ]
        );

        $goToListingIcon = sprintf(
            '<img alt="%s" src="%s" />',
            __('Go to Listing'),
            $this->magentoAssets->getViewFileUrl('M2E_TikTokShop::images/goto_listing.png'),
        );

        return sprintf(
            '<a title="%s" target="_blank" href="%s" style="line-height: 0">%s</a>',
            __('Go to Listing'),
            $goToListingUrl,
            $goToListingIcon,
        );
    }

    private function createFoundInChildrenIcon(array $row): string
    {
        if (!$this->isFoundedInChildren($row)) {
            return '';
        }

        $tooltipContent = (string)__('A Product you are searching for is found as part of a Multi-Variational' .
            ' Product. Click on the arrow icon to manage it individually.');

        return <<<HTML
<div class="fix-magento-tooltip searched_child_product">
    <div class="m2epro-field-tooltip admin__field-tooltip">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content">$tooltipContent</div>
    </div>
</div>
HTML;
    }

    private function isFoundedInChildren(array $row): bool
    {
        if (
            !isset($row['founded_in_variation'])
            || !isset($row['founded_in_parent'])
        ) {
            return false;
        }

        return $row['founded_in_variation'] && !$row['founded_in_parent'];
    }
}
