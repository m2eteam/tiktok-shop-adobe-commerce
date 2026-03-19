<?php

namespace M2E\TikTokShop\Block\Adminhtml\Traits;

trait BlockTrait
{
    use TooltipTrait;

    public function __(...$args): string
    {
        return (string)__(...$args);
    }

    public function appendHelpBlock($data)
    {
        return $this->getLayout()->addBlock(\M2E\TikTokShop\Block\Adminhtml\HelpBlock::class, '', 'main.top')
                    ->setData(
                        $data,
                    );
    }

    /**
     * @param string $block
     * @param string $name
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setPageActionsBlock(string $block, string $name = '')
    {
        return $this->getLayout()->addBlock($block, $name, 'page.main.actions');
    }
}
