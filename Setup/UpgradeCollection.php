<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Setup;

class UpgradeCollection extends \M2E\Core\Model\Setup\AbstractUpgradeCollection
{
    public function getMinAllowedVersion(): string
    {
        return '1.0.0';
    }

    protected function getSourceVersionUpgrades(): array
    {
        return [
            '1.0.0' => ['to' => '1.0.1', 'upgrade' => null],
            '1.0.1' => ['to' => '1.0.2', 'upgrade' => null],
            '1.0.2' => ['to' => '1.0.3', 'upgrade' => null],
            '1.0.3' => ['to' => '1.0.4', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_0_4\Config::class],
            '1.0.4' => ['to' => '1.0.5', 'upgrade' => null],
            '1.0.5' => ['to' => '1.1.0', 'upgrade' => null],
            '1.1.0' => ['to' => '1.1.1', 'upgrade' => null],
            '1.1.1' => ['to' => '1.1.2', 'upgrade' => null],
            '1.1.2' => ['to' => '1.1.3', 'upgrade' => null],
            '1.1.3' => ['to' => '1.2.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_2_0\Config::class],
            '1.2.0' => ['to' => '1.2.1', 'upgrade' => null],
            '1.2.1' => ['to' => '1.2.2', 'upgrade' => null],
            '1.2.2' => ['to' => '1.3.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_3_0\Config::class],
            '1.3.0' => ['to' => '1.3.1', 'upgrade' => null],
            '1.3.1' => ['to' => '1.4.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_4_0\Config::class],
            '1.4.0' => ['to' => '1.5.0', 'upgrade' => null],
            '1.5.0' => ['to' => '1.6.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_6_0\Config::class],
            '1.6.0' => ['to' => '1.7.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_7_0\Config::class],
            '1.7.0' => ['to' => '1.8.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_8_0\Config::class],
            '1.8.0' => ['to' => '1.8.1', 'upgrade' => null],
            '1.8.1' => ['to' => '1.8.2', 'upgrade' => null],
            '1.8.2' => ['to' => '1.8.3', 'upgrade' => null],
            '1.8.3' => ['to' => '1.9.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_9_0\Config::class],
            '1.9.0' => ['to' => '1.9.1', 'upgrade' => null],
            '1.9.1' => ['to' => '1.9.2', 'upgrade' => null],
            '1.9.2' => ['to' => '1.9.3', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_9_3\Config::class],
            '1.9.3' => ['to' => '1.9.4', 'upgrade' => null],
            '1.9.4' => ['to' => '1.9.5', 'upgrade' => null],
            '1.9.5' => ['to' => '1.9.6', 'upgrade' => null],
            '1.9.6' => ['to' => '1.10.0', 'upgrade' => null],
            '1.10.0' => ['to' => '1.10.1', 'upgrade' => null],
            '1.10.1' => ['to' => '1.10.2', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_10_2\Config::class],
            '1.10.2' => ['to' => '1.10.3', 'upgrade' => null],
            '1.10.3' => ['to' => '1.10.4', 'upgrade' => null],
            '1.10.4' => ['to' => '1.11.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_11_0\Config::class],
            '1.11.0' => ['to' => '1.12.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_12_0\Config::class],
            '1.12.0' => ['to' => '1.13.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_13_0\Config::class],
            '1.13.0' => ['to' => '1.14.0', 'upgrade' => null],
            '1.14.0' => ['to' => '1.15.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v1_15_0\Config::class],
            '1.15.0' => ['to' => '2.0.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v2_0_0\Config::class],
            '2.0.0' => ['to' => '2.0.1', 'upgrade' => null],
            '2.0.1' => ['to' => '2.0.2', 'upgrade' => null],
            '2.0.2' => ['to' => '2.0.3', 'upgrade' => null],
            '2.0.3' => ['to' => '2.1.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v2_1_0\Config::class],
            '2.1.0' => ['to' => '2.1.1', 'upgrade' => null],
            '2.1.1' => ['to' => '2.1.2', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v2_1_2\Config::class],
            '2.1.2' => ['to' => '2.1.3', 'upgrade' => null],
            '2.1.3' => ['to' => '2.2.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v2_2_0\Config::class],
            '2.2.0' => ['to' => '2.3.0', 'upgrade' => null],
            '2.3.0' => ['to' => '2.4.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v2_4_0\Config::class],
            '2.4.0' => ['to' => '2.4.1', 'upgrade' => null],
            '2.4.1' => ['to' => '2.5.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v2_5_0\Config::class],
            '2.5.0' => ['to' => '2.5.1', 'upgrade' => null],
            '2.5.1' => ['to' => '2.6.0', 'upgrade' => null],
            '2.6.0' => ['to' => '2.7.0', 'upgrade' => null],
            '2.7.0' => ['to' => '2.8.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v2_8_0\Config::class],
            '2.8.0' => ['to' => '2.9.0', 'upgrade' => null],
            '2.9.0' => ['to' => '2.10.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v2_10_0\Config::class],
            '2.10.0' => ['to' => '2.10.1', 'upgrade' => null],
            '2.10.1' => ['to' => '2.11.0', 'upgrade' => \M2E\TikTokShop\Setup\Upgrade\v2_11_0\Config::class],
            '2.11.0' => ['to' => '2.11.1', 'upgrade' => null],
            '2.11.1' => ['to' => '2.11.2', 'upgrade' => null],
        ];
    }
}
