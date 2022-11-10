<?php

namespace MyBatis\Executor\ResultSet;

use Doctrine\DBAL\{
    Result,
    Statement
};
use MyBatis\Mapping\ResultMap;
use MyBatis\Session\Configuration;
use MyBatis\Type\{
    DbalType,
    ObjectTypeHandler,
    TypeHandlerInterface,
    TypeHandlerRegistry,
    UnknownTypeHandler
};

class ResultSetWrapper
{
    private $statement;
    private $resultSet;
    private $typeHandlerRegistry;
    private $columnNames = [];
    //private $classNames = [];
    //private $dbalTypes = [];
    private $phpTypes = [];
    private $typeHandlerMap = [];
    private $mappedColumnNamesMap = [];
    private $unMappedColumnNamesMap = [];

    public function __construct(Statement $stmt, Configuration $configuration)
    {
        $this->typeHandlerRegistry = $this->configuration->getTypeHandlerRegistry();
        $this->statement = $stmt;
        $this->resultSet = $stmt->execute();
        //will work with PDO drivers only
        $metaData = $stmt->getWrappedStatement()->getColumnMeta();
        $columnCount = $this->resultSet->columnCount();
        for ($i = 1; $i <= $columnCount; $i += 1) {
            $this->columnNames[] = $metaData['name'];
            $this->phpTypes[] = $metaData['native_type'];
        }
    }

    public function getResultSet(): Result
    {
        return $this->resultSet;
    }

    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    public function getPhpTypes(): array
    {
        return $this->phpTypes;
    }

    public function getPhpType(string $columnName): ?string
    {
        for ($i = 0; $i < count($this->columnNames); $i += 1) {
            if (strtoupper($columnNames[$i]) == strtoupper($columnName)) {
                return $this->phpTypes[$i];
            }
        }
        return null;
    }

    /**
     * Gets the type handler to use when reading the result set.
     * Tries to get from the TypeHandlerRegistry by searching for the property type.
     * If not found it gets the column DBAL type and tries to get a handler for it.
     *
     * @param propertyType
     *          the property type
     * @param columnName
     *          the column name
     * @return the type handler
     */
    public function getTypeHandler(string $propertyType, string $columnName): TypeHandlerInterface
    {
        $handler = null;
        $columnHandlers = null;
        if (array_key_exists($columnName, $this->typeHandlerMap)) {
            $columnHandlers = &$this->typeHandlerMap[$columnName];
        }
        if ($columnHandlers === null) {
            $columnHandlers = [];
            $this->typeHandlerMap[$columnName] = &$columnHandlers;
        } else {
            if (array_key_exists($propertyType, $this->columnHandlers)) {
                $handler = $this->columnHandlers[$propertyType];
            }
        }

        if ($handler === null) {
            $phpType = $this->getPhpType($columnName);
            $handler = $this->typeHandlerRegistry->getTypeHandler($propertyType);
            // Replicate logic of UnknownTypeHandler#resolveTypeHandler
            // See issue #59 comment 10
            if ($handler === null || $handler instanceof UnknownTypeHandler) {
                $index = array_search($columnName, $this->columnNames);
                if ($phpType !== null) {
                    $handler = $this->typeHandlerRegistry->getTypeHandler($phpType);
                }
            }
            if ($handler === null || $handler instanceof UnknownTypeHandler) {
                $handler = new ObjectTypeHandler();
            }
            $columnHandlers[$propertyType] = $handler;
        }
        return $handler;
    }

    private function loadMappedAndUnmappedColumnNames(ResultMap $resultMap, string $columnPrefix): void
    {
        $mappedColumnNames = [];
        $unmappedColumnNames = [];
        $upperColumnPrefix = $columnPrefix === null ? null : strtoupper($columnPrefix);
        $mappedColumns = $this->prependPrefixes($resultMap->getMappedColumns(), $upperColumnPrefix);
        foreach ($columnNames as $columnName) {
            $upperColumnName = strtoupper($columnName);
            if (in_array($upperColumnName, $mappedColumns)) {
                $mappedColumnNames[] = $upperColumnName;
            } else {
                $unmappedColumnNames[] = $columnName;
            }
        }
        $this->mappedColumnNamesMap[$this->getMapKey($resultMap, $columnPrefix)] = $mappedColumnNames;
        $this->unMappedColumnNamesMap[$this->getMapKey($resultMap, $columnPrefix)] = $unmappedColumnNames;
    }

    public function getMappedColumnNames(ResultMap $resultMap, string $columnPrefix): array
    {
        $key = $this->getMapKey($resultMap, $columnPrefix);
        $mappedColumnNames = null;
        if (array_key_exists($key, $this->mappedColumnNamesMap)) {
            $mappedColumnNames = $this->mappedColumnNamesMap[$key];
        }
        if ($mappedColumnNames === null) {
            $this->loadMappedAndUnmappedColumnNames($resultMap, $columnPrefix);
            if (array_key_exists($key, $this->mappedColumnNamesMap)) {
                $mappedColumnNames = $this->mappedColumnNamesMap[$key];
            }
        }
        return $mappedColumnNames ?? [];
    }

    public function getUnmappedColumnNames(ResultMap $resultMap, string $columnPrefix): array
    {
        $key = $this->getMapKey($resultMap, $columnPrefix);
        $unMappedColumnNames = null;
        if (array_key_exists($key, $this->unMappedColumnNamesMap)) {
            $unMappedColumnNames = $this->unMappedColumnNamesMap[$key];
        }
        if ($unMappedColumnNames === null) {
            $this->loadMappedAndUnmappedColumnNames($resultMap, $columnPrefix);
            if (array_key_exists($key, $this->unMappedColumnNamesMap)) {
                $unMappedColumnNames = $this->unMappedColumnNamesMap[$key];
            }
        }
        return $unMappedColumnNames ?? [];
    }

    private function getMapKey(ResultMap $resultMap, string $columnPrefix): string
    {
        return $resultMap->getId() . ":" . $columnPrefix;
    }

    private function prependPrefixes(array $columnNames, ?string $prefix): array
    {
        if (empty($columnNames) || empty($prefix)) {
            return $columnNames;
        }
        $prefixed = [];
        foreach ($columnNames as $columnName) {
            $prefixed[] = $prefix . $columnName;
        }
        return array_unique($prefixed);
    }
}
