<?php

namespace M2E\TikTokShop\Block\Adminhtml\TikTokShop\Log;

class Order extends \M2E\TikTokShop\Block\Adminhtml\Log\Order\AbstractContainer
{
    protected function _toHtml()
    {
        if ($this->getRequest()->getParam('magento_order_failed')) {
            $message = __(
                'This Log contains information about your recent TikTokShop orders for ' .
                'which Magento orders were not created.<br/><br/>Find detailed info in ' .
                '<a href="%url" target="_blank">the article</a>.',
                ['url' => 'https://docs-m2.m2epro.com/m2e-tiktok-shop-logs-events']
            );
        } else {
            $message = __(
                'This Log contains information about Order processing.<br/><br/>' .
                'Find detailed info in <a href="%url" target="_blank">the article</a>.',
                ['url' => 'https://docs-m2.m2epro.com/m2e-tiktok-shop-logs-events']
            );
        }
        $helpBlock = $this->getLayout()->createBlock(\M2E\TikTokShop\Block\Adminhtml\HelpBlock::class)->setData([
            'content' => $message,
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }
}
