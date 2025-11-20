<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Setup\InstallHandler;

use M2E\TikTokShop\Helper\Module\Database\Tables as TablesHelper;
use M2E\TikTokShop\Model\ResourceModel\Account as AccountResource;
use M2E\TikTokShop\Model\ResourceModel\ShippingProvider as ShippingProviderResource;
use M2E\TikTokShop\Model\ResourceModel\Shop as ShopResource;
use Magento\Framework\DB\Ddl\Table;

class AccountHandler implements \M2E\Core\Model\Setup\InstallHandlerInterface
{
    use \M2E\TikTokShop\Setup\InstallHandlerTrait;

    public function installSchema(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $this->installAccountTable($setup);
        $this->installShopTable($setup);
        $this->installWarehouseTable($setup);
        $this->installShippingProvidersTable($setup);
    }

    public function installData(\Magento\Framework\Setup\SetupInterface $setup): void
    {
    }

    private function installAccountTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->getFullTableName(TablesHelper::TABLE_NAME_ACCOUNT);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                AccountResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                AccountResource::COLUMN_TITLE,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_SERVER_HASH,
                Table::TYPE_TEXT,
                100,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_OPEN_ID,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_SELLER_NAME,
                Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                AccountResource::COLUMN_MAGENTO_ORDERS_SETTINGS,
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                AccountResource::COLUMN_CREATE_MAGENTO_INVOICE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                AccountResource::COLUMN_CREATE_MAGENTO_SHIPMENT,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                AccountResource::COLUMN_OTHER_LISTINGS_SYNCHRONIZATION,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1]
            )
            ->addColumn(
                AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_MODE,
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                AccountResource::COLUMN_OTHER_LISTINGS_MAPPING_SETTINGS,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => '[]']
            )
            ->addColumn(
                AccountResource::COLUMN_OTHER_LISTINGS_RELATED_STORES,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => '[]']
            )
            ->addColumn(
                AccountResource::COLUMN_MAP_SHIPPING_PROVIDER_BY_CUSTOM_CARRIER_TITLE,
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                AccountResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                AccountResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('title', AccountResource::COLUMN_TITLE)
            ->addIndex('open_id', AccountResource::COLUMN_OPEN_ID)
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $setup->getConnection()->createTable($table);
    }

    private function installShopTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->getFullTableName(TablesHelper::TABLE_NAME_SHOP);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                ShopResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                ShopResource::COLUMN_ACCOUNT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                ShopResource::COLUMN_SHOP_ID,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                ShopResource::COLUMN_SHOP_NAME,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                ShopResource::COLUMN_REGION,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                ShopResource::COLUMN_TYPE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                ShopResource::COLUMN_ORDER_LAST_SYNC,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                ShopResource::COLUMN_INVENTORY_LAST_SYNC,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                ShopResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                ShopResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex(
                'shop_id__account_id',
                [ShopResource::COLUMN_SHOP_ID, ShopResource::COLUMN_ACCOUNT_ID],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            );

        $setup->getConnection()->createTable($table);
    }

    private function installWarehouseTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->getFullTableName(TablesHelper::TABLE_NAME_WAREHOUSE);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                'shop_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                'warehouse_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                'effect_status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                'is_default',
                Table::TYPE_BOOLEAN,
                null,
                ['default' => 0]
            )
            ->addColumn(
                'address',
                Table::TYPE_TEXT,
                \M2E\Core\Model\ResourceModel\Setup::LONG_COLUMN_SIZE,
            )
            ->addColumn(
                'shipping_provider_mapping',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'update_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            );

        $setup->getConnection()->createTable($table);
    }

    private function installShippingProvidersTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->getFullTableName(TablesHelper::TABLE_NAME_SHIPPING_PROVIDERS);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                ShippingProviderResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'primary' => true,
                    'nullable' => false,
                    'auto_increment' => true,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_ACCOUNT_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_SHOP_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_WAREHOUSE_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_DELIVERY_OPTION_ID,
                Table::TYPE_TEXT,
                30,
                [
                    'nullable' => false,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_ID,
                Table::TYPE_TEXT,
                30,
                [
                    'nullable' => false,
                ]
            )
            ->addColumn(
                ShippingProviderResource::COLUMN_SHIPPING_PROVIDER_NAME,
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                ]
            )
            ->addIndex('account_id', 'account_id')
            ->addIndex('shop_id', 'shop_id')
            ->addIndex('warehouse_id', 'warehouse_id')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $setup->getConnection()->createTable($table);
    }
}
