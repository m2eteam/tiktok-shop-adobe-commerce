<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Setup\Install;

use M2E\TikTokShop\Helper\Module\Database\Tables as TablesHelper;
use M2E\TikTokShop\Model\ResourceModel\Category\Attribute as CategoryAttributeResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class CategoryHandler implements \M2E\TikTokShop\Model\Setup\InstallHandlerInterface
{
    private \M2E\TikTokShop\Helper\Module\Database\Tables $tablesHelper;

    public function __construct(TablesHelper $tablesHelper)
    {
        $this->tablesHelper = $tablesHelper;
    }

    public function installSchema(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $this->installCategoryDictionaryTable($setup);
        $this->installCategoryTreeTable($setup);
        $this->installTemplateCategoryAttributesTable($setup);
    }

    public function installData(\Magento\Framework\Setup\SetupInterface $setup): void
    {
    }

    private function installCategoryDictionaryTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_CATEGORY_DICTIONARY);

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
                'category_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                'state',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                'path',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                'sales_attributes',
                Table::TYPE_TEXT,
                \M2E\TikTokShop\Model\Setup\Installer::LONG_COLUMN_SIZE
            )
            ->addColumn(
                'product_attributes',
                Table::TYPE_TEXT,
                \M2E\TikTokShop\Model\Setup\Installer::LONG_COLUMN_SIZE
            )
            ->addColumn(
                'category_rules',
                Table::TYPE_TEXT,
                \M2E\TikTokShop\Model\Setup\Installer::LONG_COLUMN_SIZE
            )
            ->addColumn(
                'authorized_brands',
                Table::TYPE_TEXT,
                \M2E\TikTokShop\Model\Setup\Installer::LONG_COLUMN_SIZE
            )
            ->addColumn(
                'total_sales_attributes',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'total_product_attributes',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'used_sales_attributes',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'used_product_attributes',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'has_required_product_attributes',
                Table::TYPE_BOOLEAN,
                null,
                ['default' => 0]
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
            )
            ->addIndex(
                'shop_id__category_id',
                ['shop_id', 'category_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );

        $setup->getConnection()->createTable($table);
    }

    private function installCategoryTreeTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_CATEGORY_TREE);

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
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                'category_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                'parent_category_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true,]
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addColumn(
                'is_leaf',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                'permission_statuses',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false,]
            )
            ->addIndex('parent_category_id', 'parent_category_id')
            ->addIndex('shop_id', 'shop_id');

        $setup->getConnection()->createTable($table);
    }

    private function installTemplateCategoryAttributesTable(\Magento\Framework\Setup\SetupInterface $setup): void
    {
        $tableName = $this->tablesHelper->getFullName(TablesHelper::TABLE_NAME_TEMPLATE_CATEGORY_ATTRIBUTES);

        $table = $setup->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                CategoryAttributeResource::COLUMN_ID,
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
                CategoryAttributeResource::COLUMN_CATEGORY_DICTIONARY_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false,]
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_ATTRIBUTE_TYPE,
                Table::TYPE_TEXT,
                30
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_ATTRIBUTE_ID,
                Table::TYPE_TEXT,
                30,
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_ATTRIBUTE_NAME,
                Table::TYPE_TEXT,
                50
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_VALUE_MODE,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_VALUE_RECOMMENDED,
                Table::TYPE_TEXT,
                \M2E\TikTokShop\Model\Setup\Installer::LONG_COLUMN_SIZE
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_VALUE_CUSTOM_VALUE,
                Table::TYPE_TEXT,
                255,
            )
            ->addColumn(
                CategoryAttributeResource::COLUMN_VALUE_CUSTOM_ATTRIBUTE,
                Table::TYPE_TEXT,
                255,
            )
            ->addIndex(
                'category_dictionary_id',
                CategoryAttributeResource::COLUMN_CATEGORY_DICTIONARY_ID,
            );

        $setup->getConnection()->createTable($table);
    }
}