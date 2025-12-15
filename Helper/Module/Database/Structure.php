<?php

namespace M2E\TikTokShop\Helper\Module\Database;

class Structure
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Magento\Framework\Filesystem\Directory\ReadFactory */
    private $directoryReaderFactory;

    /** @var \Magento\Framework\Component\ComponentRegistrar */
    private $componentRegistrar;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var \M2E\TikTokShop\Helper\Magento */
    private $magentoHelper;

    /** @var \M2E\TikTokShop\Helper\Data\Cache\Runtime */
    private $runtimeCacheHelper;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryReaderFactory,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \M2E\TikTokShop\Helper\Magento $magentoHelper,
        \M2E\TikTokShop\Helper\Data\Cache\Runtime $runtimeCacheHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->directoryReaderFactory = $directoryReaderFactory;
        $this->componentRegistrar = $componentRegistrar;
        $this->objectManager = $objectManager;
        $this->magentoHelper = $magentoHelper;
        $this->runtimeCacheHelper = $runtimeCacheHelper;
    }

    /**
     * @return array|mixed
     */
    public function getMysqlTables()
    {
        $cacheData = $this->runtimeCacheHelper->getValue(__METHOD__);
        if (null !== $cacheData) {
            return $cacheData;
        }

        $result = [];

        $queryStmt = $this->resourceConnection->getConnection()
                                              ->select()
                                              ->from('information_schema.tables', ['table_name'])
                                              ->where('table_schema = ?', $this->magentoHelper->getDatabaseName())
                                              ->where('table_name LIKE ?', "%TikTokShop\_%")
                                              ->query();

        while ($tableName = $queryStmt->fetchColumn()) {
            $result[] = $tableName;
        }

        $this->runtimeCacheHelper->setValue(__METHOD__, $result);

        return $result;
    }

    /**
     * @return int[]|string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function getModuleTables(): array
    {
        return array_keys($this->getTablesModels());
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    public function isModuleTable(string $tableName): bool
    {
        $coreTables = [
            \M2E\Core\Helper\Module\Database\Tables::TABLE_NAME_SETUP,
            \M2E\Core\Helper\Module\Database\Tables::TABLE_NAME_CONFIG,
            \M2E\Core\Helper\Module\Database\Tables::TABLE_NAME_REGISTRY,
        ];

        if (in_array($tableName, $coreTables, true)) {
            return true;
        }

        return strpos($tableName, \M2E\TikTokShop\Helper\Module\Database\Tables::PREFIX) !== false;
    }

    /**
     * @param string $tableName
     *
     * @return bool|mixed
     * @throws \Zend_Db_Statement_Exception
     */
    public function isTableExists(string $tableName, bool $force = false)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData && !$force) {
            return $cacheData !== false;
        }

        $connection = $this->resourceConnection->getConnection();

        $databaseName = $this->magentoHelper->getDatabaseName();
        $tableName = $this->getTableNameWithPrefix($tableName);

        $result = $connection->query("SHOW TABLE STATUS FROM `$databaseName` WHERE `name` = '$tableName'")
                             ->fetch();

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return $result !== false;
    }

    /**
     * @param string $tableName
     *
     * @return bool|mixed
     * @throws \M2E\TikTokShop\Model\Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function isTableStatusOk(string $tableName)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();

        if (!$this->isTableExists($tableName)) {
            throw new \M2E\TikTokShop\Model\Exception("Table '$tableName' is not exists.");
        }

        $result = true;

        try {
            $tableName = $this->getTableNameWithPrefix($tableName);
            $connection->select()->from($tableName, new \Zend_Db_Expr('1'))
                       ->limit(1)
                       ->query();
        } catch (\Exception $e) {
            $result = false;
        }

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return $result;
    }

    /**
     * @param string $tableName
     *
     * @return bool
     * @throws \M2E\TikTokShop\Model\Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function isTableReady(string $tableName): bool
    {
        return $this->isTableExists($tableName)
            && $this->isTableStatusOk($tableName);
    }

    /**
     * @param string $tableName
     *
     * @return int|mixed
     */
    public function getCountOfRecords(string $tableName)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->getTableNameWithPrefix($tableName);

        $result = $connection->select()->from($tableName, new \Zend_Db_Expr('COUNT(*)'))
                             ->query()
                             ->fetchColumn();

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return (int)$result;
    }

    /**
     * @param string $tableName
     *
     * @return float|mixed
     */
    public function getDataLength(string $tableName)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        $connection = $this->resourceConnection->getConnection();

        $databaseName = $this->magentoHelper->getDatabaseName();
        $tableName = $this->getTableNameWithPrefix($tableName);

        $dataLength = $connection->select()
                                 ->from('information_schema.tables', [new \Zend_Db_Expr('data_length + index_length')])
                                 ->where('`table_name` = ?', $tableName)
                                 ->where('`table_schema` = ?', $databaseName)
                                 ->query()
                                 ->fetchColumn();

        $result = round($dataLength / 1024 / 1024, 2);

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return $result;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     * @throws \Zend_Db_Statement_Exception
     */
    public function getModuleTablesInfo(): array
    {
        $tablesInfo = [];
        foreach ($this->getModuleTables() as $currentTable) {
            $currentTableInfo = $this->getTableInfo($currentTable);
            $currentTableInfo && $tablesInfo[$currentTable] = $currentTableInfo;
        }

        return $tablesInfo;
    }

    /**
     * @param string $tableName
     *
     * @return array|false|mixed
     * @throws \Zend_Db_Statement_Exception
     */
    public function getTableInfo(string $tableName)
    {
        $cacheKey = __METHOD__ . $tableName;
        $cacheData = $this->runtimeCacheHelper->getValue($cacheKey);

        if (null !== $cacheData) {
            return $cacheData;
        }

        if (!$this->isTableExists($this->getTableNameWithoutPrefix($tableName))) {
            return false;
        }

        $moduleTableName = $this->getTableNameWithPrefix($tableName);

        $stmtQuery = $this->resourceConnection->getConnection()->query(
            "SHOW COLUMNS FROM $moduleTableName"
        );

        $result = [];
        while ($row = $stmtQuery->fetch()) {
            $result[strtolower($row['Field'])] = [
                'name' => strtolower($row['Field']),
                'type' => strtolower($row['Type']),
                'null' => strtolower($row['Null']),
                'key' => strtolower($row['Key']),
                'default' => strtolower($row['Default'] ?? ''),
                'extra' => strtolower($row['Extra']),
            ];
        }

        $this->runtimeCacheHelper->setValue($cacheKey, $result);

        return $result;
    }

    /**
     * @param string $table
     * @param string $columnName
     *
     * @return mixed|null
     * @throws \Zend_Db_Statement_Exception
     */
    public function getColumnInfo(string $table, string $columnName)
    {
        $info = $this->getTableInfo($table);

        return $info[$columnName] ?? null;
    }

    /**
     * @param string $tableName
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function getTableModel(string $tableName): ?string
    {
        $tablesModels = $this->getTablesModels();

        return $tablesModels[$tableName] ?? null;
    }

    /**
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    protected function getTablesModels()
    {
        $cacheData = $this->runtimeCacheHelper->getValue(__METHOD__);
        if (null !== $cacheData) {
            return $cacheData;
        }

        $path = $this->componentRegistrar->getPath(
            \Magento\Framework\Component\ComponentRegistrar::MODULE,
            \M2E\TikTokShop\Helper\Module::IDENTIFIER
        );
        $path .= '/Model/ResourceModel';

        /** @var \Magento\Framework\Filesystem\Directory\Read $directoryReader */
        $directoryReader = $this->directoryReaderFactory->create($path);

        $tablesModels = [];
        foreach ($directoryReader->readRecursively() as $directoryItem) {
            if (!$directoryReader->isFile($directoryItem)) {
                continue;
            }

            $modelName = preg_replace('/\.php$/', '', str_replace('/', '\\', $directoryItem));
            $className = '\M2E\TikTokShop\Model\ResourceModel\\' . $modelName;

            $reflectionClass = new \ReflectionClass($className);
            if (
                $reflectionClass->isAbstract()
                || !$reflectionClass->isSubclassOf(
                    \M2E\TikTokShop\Model\ResourceModel\ActiveRecord\AbstractModel::class
                )
            ) {
                continue;
            }

            /** @var \M2E\TikTokShop\Model\ResourceModel\ActiveRecord\AbstractModel $object */
            $object = $this->objectManager->get($className);

            $tableName = $object->getMainTable();
            $tableName = $this->getTableNameWithoutPrefix($tableName);

            $tablesModels[$tableName] = $className;
        }

        $this->runtimeCacheHelper->setValue(__METHOD__, $tablesModels);

        return $tablesModels;
    }

    /**
     * @param string $table
     *
     * @return string
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function getIdColumn(string $table): string
    {
        $tableModel = $this->getTableModel($table);
        $tableModel = $this->objectManager->get($tableModel);

        return $tableModel->getIdFieldName();
    }

    /**
     * @param string $table
     *
     * @return bool
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     * @throws \Zend_Db_Statement_Exception
     */
    public function isIdColumnAutoIncrement(string $table): bool
    {
        $idColumn = $this->getIdColumn($table);
        $columnInfo = $this->getColumnInfo($table, $idColumn);

        return isset($columnInfo['extra']) && strpos($columnInfo['extra'], 'increment') !== false;
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    public function getTableNameWithPrefix(string $tableName): string
    {
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * @param string $tableName
     *
     * @return array|string|string[]
     */
    public function getTableNameWithoutPrefix(string $tableName)
    {
        $tableName = $this->getTableNameWithPrefix($tableName);

        $prefix = $this->magentoHelper->getDatabaseTablesPrefix();
        if (substr($tableName, 0, strlen($prefix)) !== $prefix) {
            return $tableName;
        }

        return substr($tableName, strlen($prefix));
    }

    /**
     * @param string $table
     *
     * @return array
     * @throws \M2E\TikTokShop\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \ReflectionException
     */
    public function getConfigSnapshot(string $table): array
    {
        $tableModel = $this->getTableModel($table);
        $modelName = str_replace('ResourceModel\\', '', $tableModel);
        $tableModel = $this->objectManager->get($modelName);

        $collection = $tableModel->getCollection()->toArray();

        $result = [];
        foreach ($collection['items'] as $item) {
            $codeHash = strtolower($item['group']) . '#' . strtolower($item['key']);
            $result[$codeHash] = [
                'id' => (int)$item['id'],
                'group' => $item['group'],
                'key' => $item['key'],
                'value' => $item['value'],
            ];
        }

        return $result;
    }
}
