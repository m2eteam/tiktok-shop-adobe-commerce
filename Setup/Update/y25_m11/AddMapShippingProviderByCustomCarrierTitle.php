<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Setup\Update\y25_m11;

use M2E\TikTokShop\Helper\Module\Database\Tables;

class AddMapShippingProviderByCustomCarrierTitle extends \M2E\Core\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->createTableModifier(Tables::TABLE_NAME_ACCOUNT);

        $modifier->addColumn(
            \M2E\TikTokShop\Model\ResourceModel\Account::COLUMN_MAP_SHIPPING_PROVIDER_BY_CUSTOM_CARRIER_TITLE,
            'SMALLINT NOT NULL',
            '0',
            \M2E\TikTokShop\Model\ResourceModel\Account::COLUMN_OTHER_LISTINGS_RELATED_STORES,
            false,
            false
        );

        $modifier->commit();
    }
}
