<?php

namespace M2E\TikTokShop\Block\Adminhtml\Traits;

trait TooltipTrait
{
    public function getTooltipHtml(string $content, $directionToRight = false): string
    {
        $directionToRightClass = $directionToRight ? 'TikTokShop-field-tooltip-right' : '';

        return <<<HTML
<div class="TikTokShop-field-tooltip admin__field-tooltip $directionToRightClass">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$content}
    </div>
</div>
HTML;
    }
}
