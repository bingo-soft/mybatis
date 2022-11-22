<?php

namespace MyBatis\Executor\ResultSet;

use Doctrine\DBAL\{
    Result,
    Statement
};
use MyBatis\Annotations\{
    AutomapConstructor,
    Param
};
use MyBatis\Binding\ParamMap;
use MyBatis\Cache\CacheKey;
use MyBatis\Cursor\CursorInterface;
use MyBatis\Cursor\Defaults\DefaultCursor;
use MyBatis\Executor\{
    ExecutorInterface,
    ExecutorException
};
use MyBatis\Executor\Loader\{
    ResultLoader,
    ResultLoaderMap
};
use MyBatis\Executor\Parameter\ParameterHandlerInterface;
use MyBatis\Executor\Result\{
    DefaultResultContext,
    DefaultResultHandler,
    ResultMapException
};
use MyBatis\Mapping\{
    BoundSql,
    Discriminator,
    MappedStatement,
    ParameterMapping,
    ParameterMode,
    ResultMap,
    ResultMapping
};
use Util\Reflection\{
    MetaClass,
    MetaObject
};
use MyBatis\Session\{
    AutoMappingBehavior,
    Configuration,
    ResultContextInterface,
    ResultHandlerInterface,
    RowBounds
};
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    TypeHandlerRegistry
};
use Util\Reflection\MapUtil;

class DefaultResultSetHandler implements ResultSetHandlerInterface
{
    private static $DEFERRED;
    private const PRIMITIVES = ["int", "integer", "float", "double", "bool", "boolean", "string"];
    private $executor;
    private $configuration;
    private $mappedStatement;
    private $rowBounds;
    private $parameterHandler;
    private $resultHandler;
    private $boundSql;
    private $typeHandlerRegistry;
    //private $objectFactory;
    //private $reflectorFactory;

    // nested resultmaps
    private $nestedResultObjects = [];
    private $ancestorObjects = [];
    private $previousRowValue;

    // multiple resultsets
    private $nextResultMaps = [];
    private $pendingRelations = [];

    // Cached Automappings
    private $autoMappingsCache = [];
    private $constructorAutoMappingColumns = [];

    // temporary marking flag that indicate using constructor mapping (use field to reduce memory usage)
    private $useConstructorMappings = false;

    public function __construct(
        ExecutorInterface $executor,
        MappedStatement $mappedStatement,
        ParameterHandlerInterface $parameterHandler,
        ?ResultHandlerInterface $resultHandler,
        BoundSql $boundSql,
        ?RowBounds $rowBounds
    ) {
        if (self::$DEFERRED === null) {
            self::$DEFERRED = new \stdClass();
        }
        $this->executor = $executor;
        $this->configuration = $mappedStatement->getConfiguration();
        $this->mappedStatement = $mappedStatement;
        $this->rowBounds = $rowBounds;
        $this->parameterHandler = $parameterHandler;
        $this->boundSql = $boundSql;
        $this->typeHandlerRegistry = $this->configuration->getTypeHandlerRegistry();
        $this->resultHandler = $resultHandler;
    }

    //
    // HANDLE OUTPUT PARAMETER
    //

    /*
    public void handleOutputParameters(CallableStatement cs);
    private void handleRefCursorOutputParameter(ResultSet rs, ParameterMapping parameterMapping, MetaObject metaParam);
    */

    //
    // HANDLE RESULT SETS
    //
    public function handleResultSets(Statement $stmt): array
    {
        $multipleResults = [];

        $resultSetCount = 0;
        $rsw = $this->getFirstResultSet($stmt);
        $resultMaps = $this->mappedStatement->getResultMaps();
        $resultMapCount = count($resultMaps);
        $this->validateResultMapsCount($rsw, $resultMapCount);
        while ($rsw !== null && $resultMapCount > $resultSetCount) {
            $resultMap = $resultMaps[$resultSetCount];
            $this->handleResultSet($rsw, $resultMap, $multipleResults, null);
            $rsw = $this->getNextResultSet($stmt);
            $this->cleanUpAfterHandlingResultSet();
            $resultSetCount += 1;
        }

        $resultSets = $this->mappedStatement->getResultSets();
        if (!empty($resultSets)) {
            while ($rsw !== null && $resultSetCount < count($resultSets)) {
                if (array_key_exists($resultSets[$resultSetCount], $this->nextResultMaps)) {
                    $parentMapping = $this->nextResultMaps[$resultSets[$resultSetCount]];
                    $nestedResultMapId = $parentMapping->getNestedResultMapId();
                    $resultMap = $this->configuration->getResultMap($nestedResultMapId);
                    $this->handleResultSet($rsw, $resultMap, null, $parentMapping);
                }
                $rsw = $this->getNextResultSet($stmt);
                $this->cleanUpAfterHandlingResultSet();
                $resultSetCount += 1;
            }
        }

        return $this->collapseSingleResultList($multipleResults);
    }

    public function handleCursorResultSets(Statement $stmt): CursorInterface
    {
        $rsw = $this->getFirstResultSet($stmt);

        $resultMaps = $mappedStatement->getResultMaps();

        $resultMapCount = count($resultMaps);
        $this->validateResultMapsCount($rsw, $resultMapCount);
        if ($resultMapCount !== 1) {
            throw new ExecutorException("Cursor results cannot be mapped to multiple resultMaps");
        }

        $resultMap = $resultMaps[0];
        return new DefaultCursor($this, $resultMap, $rsw, $this->rowBounds);
    }

    private function getFirstResultSet(Statement $stmt): ResultSetWrapper
    {
        //$rs = $stmt->execute();
        return new ResultSetWrapper($stmt, $this->configuration);
    }

    private function getNextResultSet(Statement $stmt): ?ResultSetWrapper
    {
        return null;
    }

    private function closeResultSet(?Result $rs): void
    {
        try {
            if ($rs !== null) {
                $rs->free();
            }
        } catch (\Exception $e) {
            // ignore
        }
    }

    private function cleanUpAfterHandlingResultSet(): void
    {
        $this->nestedResultObjects = [];
    }

    private function validateResultMapsCount(?ResultSetWrapper $rsw, int $resultMapCount): void
    {
        if ($rsw !== null && $resultMapCount < 1) {
            throw new ExecutorException(
                "A query was run and no Result Maps were found for the Mapped Statement '" . $this->mappedStatement->getId()
                . "'.  It's likely that neither a Result Type nor a Result Map was specified."
            );
        }
    }

    private function handleResultSet(ResultSetWrapper $rsw, ResultMap $resultMap, array &$multipleResults, ?ResultMapping $parentMapping): void
    {
        try {
            if ($parentMapping !== null) {
                $this->handleRowValues($rsw, $resultMap, null, RowBounds::default(), $parentMapping);
            } else {
                if ($this->resultHandler === null) {
                    $defaultResultHandler = new DefaultResultHandler();
                    $this->handleRowValues($rsw, $resultMap, $defaultResultHandler, $this->rowBounds, null);
                    $multipleResults[] = $defaultResultHandler->getResultList();
                } else {
                    $this->handleRowValues($rsw, $resultMap, $this->resultHandler, $this->rowBounds, null);
                }
            }
        } finally {
            $this->closeResultSet($rsw->getResultSet());
        }
    }

    private function collapseSingleResultList(array $multipleResults): array
    {
        return count($multipleResults) == 1 ? $multipleResults[0] : $multipleResults;
    }

    //
    // HANDLE ROWS FOR SIMPLE RESULTMAP
    //

    public function handleRowValues(ResultSetWrapper $rsw, ResultMap $resultMap, ResultHandlerInterface $resultHandler, ?RowBounds $rowBounds, ?ResultMapping $parentMapping): void
    {
        if ($resultMap->hasNestedResultMaps()) {
            $this->ensureNoRowBounds();
            $this->checkResultHandler();
            $this->handleRowValuesForNestedResultMap($rsw, $resultMap, $resultHandler, $rowBounds, $parentMapping);
        } else {
            $this->handleRowValuesForSimpleResultMap($rsw, $resultMap, $resultHandler, $rowBounds, $parentMapping);
        }
    }

    private function ensureNoRowBounds(): void
    {
        if ($this->configuration->isSafeRowBoundsEnabled() && $this->rowBounds !== null && ($this->rowBounds->getLimit() < RowBounds::NO_ROW_LIMIT || $rowBounds->getOffset() > RowBounds::NO_ROW_OFFSET)) {
            throw new ExecutorException(
                "Mapped Statements with nested result mappings cannot be safely constrained by RowBounds. "
                . "Use safeRowBoundsEnabled=false setting to bypass this check."
            );
        }
    }

    protected function checkResultHandler(): void
    {
        if ($this->resultHandler !== null && $this->configuration->isSafeResultHandlerEnabled() && !$this->mappedStatement->isResultOrdered()) {
            throw new ExecutorException(
                "Mapped Statements with nested result mappings cannot be safely used with a custom ResultHandler. "
                . "Use safeResultHandlerEnabled=false setting to bypass this check "
                . "or ensure your statement returns ordered data and set resultOrdered=true on it."
            );
        }
    }

    private function handleRowValuesForSimpleResultMap(ResultSetWrapper $rsw, ResultMap $resultMap, ResultHandlerInterface $resultHandler, ?RowBounds $rowBounds, ?ResultMapping $parentMapping): void
    {
        $resultContext = new DefaultResultContext();
        $resultSet = $rsw->getResultSet();
        $this->skipRows($resultSet, $rowBounds);
        while ($this->shouldProcessMoreRows($resultContext, $rowBounds) && ($rs = $resultSet->fetchAssociative())) {
            $discriminatedResultMap = $this->resolveDiscriminatedResultMap($rs, $resultMap, null);
            $rowValue = $this->getRowValue($rsw, $discriminatedResultMap, $rs, null);
            $this->storeObject($resultHandler, $resultContext, $rowValue, $parentMapping, $rs);
        }
    }

    private function storeObject(ResultHandlerInterface $resultHandler, DefaultResultContext $resultContext, $rowValue, ?ResultMapping $parentMapping, array $rs): void
    {
        if ($parentMapping !== null) {
            $this->linkToParents($rs, $parentMapping, $rowValue);
        } else {
            $this->callResultHandler($resultHandler, $resultContext, $rowValue);
        }
    }

    private function callResultHandler(ResultHandlerInterface $resultHandler, DefaultResultContext $resultContext, $rowValue): void
    {
        $resultContext->nextResultObject($rowValue);
        $resultHandler->handleResult($resultContext);
    }

    private function shouldProcessMoreRows(ResultContextInterface $context, ?RowBounds $rowBounds): bool
    {
        return !$context->isStopped() && $context->getResultCount() < ($rowBounds === null ? PHP_INT_MAX : $rowBounds->getLimit());
    }

    private function skipRows(Result $rs, ?RowBounds $rowBounds): void
    {
        if ($rowBounds !== null) {
            for ($i = 0; $i < $rowBounds->getOffset(); $i += 1) {
                if (!$rs->fetchOne()) {
                    break;
                }
            }
        }
    }

    //
    // GET VALUE FROM ROW FOR SIMPLE RESULT MAP
    //

    private function getRowValue(ResultSetWrapper $rsw, ResultMap $resultMap, array $rowData, /*string|CacheKey*/$prefixOrKey, ?string $columnPrefix = null, $partialObject = null)
    {
        if ($prefixOrKey instanceof CacheKey) {
            $resultMapId = $resultMap->getId();
            $rowValue = $partialObject;
            if ($rowValue !== null) {
                $metaObject = $this->configuration->newMetaObject($rowValue);
                $this->putAncestor($rowValue, $resultMapId);
                $this->applyNestedResultMappings($rsw, $resultMap, $metaObject, $columnPrefix, $prefixOrKey, false);
                if (array_key_exists($resultMapId, $this->ancestorObjects)) {
                    unset($this->ancestorObjects[$resultMapId]);
                }
            } else {
                $lazyLoader = new ResultLoaderMap();
                $rowValue = $this->createResultObject($rsw, $resultMap, $rowData, $lazyLoader, $columnPrefix);
                if ($rowValue !== null && !$this->hasTypeHandlerForResultObject($rsw, $resultMap->getType())) {
                    $metaObject = $this->configuration->newMetaObject($rowValue);
                    $foundValues = $this->useConstructorMappings;
                    if ($this->shouldApplyAutomaticMappings($resultMap, true)) {
                        $foundValues = $this->applyAutomaticMappings($rsw, $resultMap, $rowData, $metaObject, $columnPrefix) || $foundValues;
                    }
                    $foundValues = $this->applyPropertyMappings($rsw, $resultMap, $rowData, $metaObject, $lazyLoader, $columnPrefix) || $foundValues;
                    $this->putAncestor($rowValue, $resultMapId);
                    $foundValues = $this->applyNestedResultMappings($rsw, $resultMap, $metaObject, $columnPrefix, $prefixOrKey, true) || $foundValues;
                    if (array_key_exists($resultMapId, $this->ancestorObjects)) {
                        unset($this->ancestorObjects[$resultMapId]);
                    }
                    $foundValues = $lazyLoader->size() > 0 || $foundValues;
                    $rowValue = $foundValues || $this->configuration->isReturnInstanceForEmptyRow() ? $rowValue : null;
                }
                if ($prefixOrKey != CacheKey::nullCacheKey()) {
                    $this->nestedResultObjects[$prefixOrKey] = $rowValue;
                }
            }
            return $rowValue;
        } else {
            $lazyLoader = new ResultLoaderMap();
            $rowValue = $this->createResultObject($rsw, $resultMap, $rowData, $lazyLoader, $prefixOrKey);
            if ($rowValue !== null && !$this->hasTypeHandlerForResultObject($rsw, $resultMap->getType())) {
                $metaObject = $this->configuration->newMetaObject($rowValue);
                $foundValues = $this->useConstructorMappings;
                if ($this->shouldApplyAutomaticMappings($resultMap, false)) {
                    $foundValues = $this->applyAutomaticMappings($rsw, $resultMap, $rowData, $metaObject, $prefixOrKey) || $foundValues;
                }
                $foundValues = $this->applyPropertyMappings($rsw, $resultMap, $rowData, $metaObject, $lazyLoader, $prefixOrKey) || $foundValues;
                $foundValues = $lazyLoader->size() > 0 || $foundValues;
                $rowValue = $foundValues || $this->configuration->isReturnInstanceForEmptyRow() ? $rowValue : null;
            }
            return $rowValue;
        }
    }

    private function putAncestor($resultObject, string $resultMapId): void
    {
        $this->ancestorObjects[$resultMapId] = $resultObject;
    }

    private function shouldApplyAutomaticMappings(ResultMap $resultMap, bool $isNested): bool
    {
        if ($resultMap->getAutoMapping() !== null) {
            return $resultMap->getAutoMapping();
        } else {
            if ($isNested) {
                return AutoMappingBehavior::FULL == $this->configuration->getAutoMappingBehavior();
            } else {
                return AutoMappingBehavior::NONE != $this->configuration->getAutoMappingBehavior();
            }
        }
    }

    //
    // PROPERTY MAPPINGS
    //

    private function applyPropertyMappings(ResultSetWrapper $rsw, ResultMap $resultMap, array $rowData, MetaObject $metaObject, ResultLoaderMap $lazyLoader, ?string $columnPrefix): bool
    {
        $mappedColumnNames = $rsw->getMappedColumnNames($resultMap, $columnPrefix);
        $foundValues = false;
        $propertyMappings = $resultMap->getPropertyResultMappings();
        foreach ($propertyMappings as $propertyMapping) {
            $column = $this->prependPrefix($propertyMapping->getColumn(), $columnPrefix);
            if ($propertyMapping->getNestedResultMapId() !== null) {
                // the user added a column attribute to a nested result map, ignore it
                $column = null;
            }
            if (
                $propertyMapping->isCompositeResult()
                || ($column !== null && in_array(strtoupper($column), $mappedColumnNames))
                || $propertyMapping->getResultSet() !== null
            ) {
                $value = $this->getPropertyMappingValue($rowData, $metaObject, $propertyMapping, $lazyLoader, $columnPrefix);
                // issue #541 make property optional
                $property = $propertyMapping->getProperty();
                if ($property === null) {
                    continue;
                } elseif ($value == self::$DEFERRED) {
                    $foundValues = true;
                    continue;
                }
                if ($value !== null) {
                    $foundValues = true;
                }
                $type = $metaObject->getSetterType($property);
                if ($value !== null || ($this->configuration->isCallSettersOnNulls() && !in_array($type, self::PRIMITIVES))) {
                    // gcode issue #377, call setter on nulls (value is not 'found')
                    $metaObject->setValue($property, $value);
                }
            }
        }
        return $foundValues;
    }

    private function getPropertyMappingValue(array $rs, MetaObject $metaResultObject, ResultMapping $propertyMapping, ResultLoaderMap $lazyLoader, ?string $columnPrefix)
    {
        if ($propertyMapping->getNestedQueryId() !== null) {
            return $this->getNestedQueryMappingValue($rs, $metaResultObject, $propertyMapping, $lazyLoader, $columnPrefix);
        } elseif ($propertyMapping->getResultSet() !== null) {
            $this->addPendingChildRelation($rs, $metaResultObject, $propertyMapping);   // TODO is that OK?
            return self::$DEFERRED;
        } else {
            $typeHandler = $propertyMapping->getTypeHandler();
            $column = $this->prependPrefix($propertyMapping->getColumn(), $columnPrefix);
            return $typeHandler->getResult($rs, $column);
        }
    }

    private function createAutomaticMappings(ResultSetWrapper $rsw, ResultMap $resultMap, MetaObject $metaObject, ?string $columnPrefix): array
    {
        $mapKey = $resultMap->getId() . ":" . $columnPrefix;
        if (array_key_exists($mapKey, $this->autoMappingsCache)) {
            $autoMapping = $this->autoMappingsCache[$mapKey];
        } else {
            $autoMapping = [];
            $unmappedColumnNames = $rsw->getUnmappedColumnNames($resultMap, $columnPrefix);
            // Remove the entry to release the memory
            $mappedInConstructorAutoMapping = null;
            if (array_key_exists($mapKey, $this->constructorAutoMappingColumns)) {
                $value = $this->constructorAutoMappingColumns[$mapKey];
                unset($this->constructorAutoMappingColumns[$mapKey]);
                $mappedInConstructorAutoMapping = $value;
            }
            if ($mappedInConstructorAutoMapping !== null) {
                foreach ($mappedInConstructorAutoMapping as $mapKey => $value) {
                    if (array_key_exists($mapKey, $unmappedColumnNames)) {
                        unset($unmappedColumnNames[$mapKey]);
                    }
                }
            }
            foreach ($unmappedColumnNames as $columnName) {
                $propertyName = $columnName;
                if ($columnPrefix !== null && !empty($columnPrefix)) {
                    // When columnPrefix is specified,
                    // ignore columns without the prefix.
                    if (strpos(strtoupper($columnName), $columnPrefix) === 0) {
                        $propertyName = substr($columnName, strlen($columnPrefix));
                    } else {
                        continue;
                    }
                }
                $property = $metaObject->findProperty($propertyName, $this->configuration->isMapUnderscoreToCamelCase());
                if ($property !== null && $metaObject->hasSetter($property)) {
                    if (in_array($property, $resultMap->getMappedProperties())) {
                        continue;
                    }
                    $propertyType = $metaObject->getSetterType($property);
                    if ($this->typeHandlerRegistry->hasTypeHandler($propertyType)) {
                        $typeHandler = $rsw->getTypeHandler($propertyType, $columnName);
                        $autoMapping[] = new UnMappedColumnAutoMapping($columnName, $property, $typeHandler, in_array($propertyType, self::PRIMITIVES));
                    } else {
                        $this->configuration->getAutoMappingUnknownColumnBehavior()
                            ->doAction($this->mappedStatement, $columnName, $property, $propertyType);
                    }
                } else {
                    $this->configuration->getAutoMappingUnknownColumnBehavior()
                        ->doAction($this->mappedStatement, $columnName, ($property !== null) ? $property : $propertyName, null);
                }
            }
            $this->autoMappingsCache[$mapKey] = $autoMapping;
        }
        return $autoMapping;
    }

    private function applyAutomaticMappings(ResultSetWrapper $rsw, ResultMap $resultMap, array $rowData, MetaObject $metaObject, ?string $columnPrefix): bool
    {
        $autoMapping = $this->createAutomaticMappings($rsw, $resultMap, $metaObject, $columnPrefix);
        $foundValues = false;
        if (!empty($autoMapping)) {
            foreach ($autoMapping as $mapping) {
                $value = $mapping->typeHandler->getResult($rowData, $mapping->column);
                if ($value !== null) {
                    $foundValues = true;
                }
                if ($value !== null || ($this->configuration->isCallSettersOnNulls() && !$mapping->primitive)) {
                    // gcode issue #377, call setter on nulls (value is not 'found')
                    $metaObject->setValue($mapping->property, $value);
                }
            }
        }
        return $foundValues;
    }

    // MULTIPLE RESULT SETS

    private function linkToParents(array $rs, ResultMapping $parentMapping, $rowValue): void
    {
        $parentKey = $this->createKeyForMultipleResults($rs, $parentMapping, $parentMapping->getColumn(), $parentMapping->getForeignColumn());
        if (array_key_exists($parentKey, $this->pendingRelations)) {
            $parents = $this->pendingRelations[$parentKey];
            foreach ($parents as $parent) {
                if ($parent !== null && $rowValue !== null) {
                    $this->linkObjects($parent->metaObject, $parent->propertyMapping, $rowValue);
                }
            }
        }
    }

    private function addPendingChildRelation(array $rs, MetaObject $metaResultObject, ResultMapping $parentMapping): void
    {
        $cacheKey = $this->createKeyForMultipleResults($rs, $parentMapping, $parentMapping->getColumn(), $parentMapping->getColumn());
        $deferLoad = new PendingRelation();
        $deferLoad->metaObject = $metaResultObject;
        $deferLoad->propertyMapping = $parentMapping;
        $relations = &MapUtil::computeIfAbsent($pendingRelations, $cacheKey, function () {
            return [];
        });
        // issue #255
        $relations[] = $deferLoad;
        $key = $parentMapping->getResultSet();
        $previous = null;
        if (array_key_exists($key, $this->nextResultMaps)) {
            $previous = $this->nextResultMaps[$key];
        }
        if ($previous === null) {
            $this->nextResultMaps[$parentMapping->getResultSet()] = $parentMapping;
        } else {
            if ($previous != $parentMapping) {
                throw new ExecutorException("Two different properties are mapped to the same resultSet");
            }
        }
    }

    private function createKeyForMultipleResults(array $rs, ResultMapping $resultMapping, ?string $names, ?string $columns): CacheKey
    {
        $cacheKey = new CacheKey();
        $cacheKey->update($resultMapping);
        if (!empty($columns) && !empty($names)) {
            $columnsArray = explode(',', $columns);
            $namesArray = exolode(',', $names);
            for ($i = 0; i < count($columnsArray); $i += 1) {
                $key = $columnsArray[$i];
                if (array_key_exists($key, $rs)) {
                    $value = $rs[$key];
                    $cacheKey->update($namesArray[$i]);
                    $cacheKey->update($value);
                }
            }
        }
        return $cacheKey;
    }

    //
    // INSTANTIATION & CONSTRUCTOR MAPPING
    //

    private function createResultObject(ResultSetWrapper $rsw, ResultMap $resultMap, array $rowData, /*ResultLoaderMap|array*/ $lazyLoaderOrConstructorArgs, ?string $columnPrefix)
    {
        if ($lazyLoaderOrConstructorArgs instanceof ResultLoaderMap) {
            $this->useConstructorMappings = false; // reset previous mapping result
            $constructorArgs = [];
            $resultObject = $this->createResultObject($rsw, $resultMap, $rowData, $constructorArgs, $columnPrefix);
            if ($resultObject !== null && !$this->hasTypeHandlerForResultObject($rsw, $resultMap->getType())) {
                $propertyMappings = $resultMap->getPropertyResultMappings();
                foreach ($propertyMappings as $propertyMapping) {
                    if ($propertyMapping->getNestedQueryId() !== null && $propertyMapping->isLazy()) {
                        $resultObject = $this->configuration->getProxyFactory()->createProxy($resultObject, $lazyLoaderOrConstructorArgs, $this->configuration, $constructorArgs);
                        break;
                    }
                }
            }
            $this->useConstructorMappings = $resultObject !== null; // set current mapping result
            return $resultObject;
        } else {
            $resultType = $resultMap->getType();
            $constructorMappings = $resultMap->getConstructorResultMappings();
            if ($this->hasTypeHandlerForResultObject($rsw, $resultType)) {
                return $this->createPrimitiveResultObject($rsw, $resultMap, $rowData, $columnPrefix);
            } elseif (!empty($constructorMappings)) {
                return $this->createParameterizedResultObject($rsw, $resultType, $rowData, $constructorMappings, $lazyLoaderOrConstructorArgs, $columnPrefix);
            } elseif (class_exists($resultType)) {
                return new $resultType();
            } elseif ($this->shouldApplyAutomaticMappings($resultMap, false)) {
                return $this->createByConstructorSignature($rsw, $resultMap, $rowData, $columnPrefix, $resultType, $lazyLoaderOrConstructorArgs);
            }
            throw new ExecutorException("Do not know how to create an instance of " . $resultType);
        }
    }

    public function createParameterizedResultObject(ResultSetWrapper $rsw, string $resultType, array $rowData, array $constructorMappings, array $constructorArgs, ?string $columnPrefix)
    {
        $foundValues = false;
        foreach ($constructorMappings as $constructorMapping) {
            $parameterType = $constructorMapping->getPhpType();
            $column = $constructorMapping->getColumn();
            $value = null;
            try {
                if ($constructorMapping->getNestedQueryId() !== null) {
                    $value = $this->getNestedQueryConstructorValue($rowData, $constructorMapping, $columnPrefix);
                } elseif ($constructorMapping->getNestedResultMapId() !== null) {
                    $resultMap = $this->configuration->getResultMap($constructorMapping->getNestedResultMapId());
                    $value = $this->getRowValue($rsw, $resultMap, $rowData, $this->getColumnPrefix($columnPrefix, $constructorMapping));
                } else {
                    $typeHandler = $constructorMapping->getTypeHandler();
                    $value = $typeHandler->getResult($rowData, $this->prependPrefix($column, $columnPrefix));
                }
            } catch (\Exception $e) {
                throw new ExecutorException("Could not process result for mapping: " . $e->getMessage());
            }
            $constructorArgs[] = $value;
            $foundValues = $value !== null || $foundValues;
        }
        return $foundValues ? new $resultType(...$constructorArgs) : null;
    }

    private function createByConstructorSignature(ResultSetWrapper $rsw, ResultMap $resultMap, array $rowData, ?string $columnPrefix, string $resultType, array $constructorArgs)
    {
        $cons = $this->findConstructorForAutomapping($resultType, $rsw);
        if ($cons !== null) {
            return $this->applyConstructorAutomapping($rsw, $resultMap, $rowData, $columnPrefix, $resultType, $constructorArgs, $cons);
        } else {
            throw new \Exception("Constructor not found");
        }
    }

    private function findConstructorForAutomapping(string $resultType, ResultSetWrapper $rsw): ?\ReflectionMethod
    {
        $ref = new \ReflectionClass($resultType);
        $refMethods = $ref->getMethods();
        $annotated = null;
        foreach ($refMethods as $method) {
            $annotations = $method->getAttributes(AutomapConstructor::class);
            if (!empty($annotations)) {
                return $method;
            }
        }

        if ($this->configuration->isArgNameBasedConstructorAutoMapping()) {
            throw new ExecutorException("@AutomapConstructor must be added");
        } else {
            return $ref->getConstructor();
        }
    }

    private function applyConstructorAutomapping(ResultSetWrapper $rsw, ResultMap $resultMap, array $rowData, ?string $columnPrefix, string $resultType, array $constructorArgs, \ReflectionMethod $constructor)
    {
        $foundValues = false;
        if ($this->configuration->isArgNameBasedConstructorAutoMapping()) {
            $foundValues = $this->applyArgNameBasedConstructorAutoMapping($rsw, $resultMap, $rowData, $columnPrefix, $resultType, $constructorArgs, $constructor, $foundValues);
        } else {
            $foundValues = $this->applyColumnOrderBasedConstructorAutomapping($rsw, $rowData, $constructorArgs, $constructor, $foundValues);
        }
        return $foundValues || $this->configuration->isReturnInstanceForEmptyRow()
            ?  new $resultType(...$constructorArgs) : null;
    }

    private function applyColumnOrderBasedConstructorAutomapping(ResultSetWrapper $rsw, array $rowData, array &$constructorArgs, \ReflectionMethod $constructor, bool $foundValues): bool
    {
        $params = $constructor->getParameters();
        $cols = $rsw->getColumnNames();
        for ($i = 0; $i < count($params); $i += 1) {
            $param = $params[$i];
            $refType = $param->getType();
            $parameterType = null;
            if ($refType instanceof \ReflectionNamedType) {
                $parameterType = $refType->getName();
            }
            $columnName = $cols[$i];
            $typeHandler = $rsw->getTypeHandler($parameterType, $columnName);
            $value = $typeHandler->getResult($rowData, $columnName);
            $constructorArgs[] = $value;
            $foundValues = $value !== null || $foundValues;
        }
        return $foundValues;
    }

    private function applyArgNameBasedConstructorAutoMapping(ResultSetWrapper $rsw, ResultMap $resultMap, array $rowData, ?string $columnPrefix, string $resultType, array $constructorArgs, \ReflectionMethod $constructor, bool $foundValues): bool
    {
        $missingArgs = [];
        $params = $constructor->getParameters();
        $cols = $rsw->getColumnNames();
        foreach ($params as $param) {
            $columnNotFound = true;
            $paramAnnos = $param->getAttributes(Param::class);
            if (!empty($paramAnnos)) {
                $paramAnno = $paramAnnos[0]->newInstance();
                $paramName = $paramAnno->value();
            } else {
                $paramName = $param->name;
            }
            foreach ($cols as $columnName) {
                if ($this->columnMatchesParam($columnName, $paramName, $columnPrefix)) {
                    $refParamType = $param->getType();
                    $paramType = null;
                    if ($refParamType instanceof \ReflectionNamedType) {
                        $paramType = $refParamType->getName();
                    }
                    $typeHandler = $rsw->getTypeHandler($paramType, $columnName);
                    $value = $typeHandler->getResult($rowData, $columnName);
                    $constructorArgs[] = $value;
                    $mapKey = $resultMap->getId() . ":" . $columnPrefix;
                    if (!array_key_exists($mapKey, $autoMappingsCache)) {
                        $arr = &MapUtil::computeIfAbsent($this->constructorAutoMappingColumns, $mapKey, function () {
                            return [];
                        });
                        $arr[] = $columnName;
                    }
                    $columnNotFound = false;
                    $foundValues = $value !== null || $foundValues;
                }
            }
            if ($columnNotFound) {
                if (empty($missingArgs)) {
                    $missingArgs = [];
                }
                $missingArgs[] = $paramName;
            }
        }
        if ($foundValues && count($constructorArgs) < count($params)) {
            throw new ExecutorException("Constructor auto-mapping failed");
        }
        return $foundValues;
    }

    private function columnMatchesParam(string $columnName, string $paramName, ?string $columnPrefix): bool
    {
        if ($columnPrefix !== null) {
            if (strpos(strtoupper($columnName), $columnPrefix) !== 0) {
                return false;
            }
            $columnName = substr($columnName, strlen($columnPrefix));
        }
        return strtoupper($paramName) == strtoupper($this->configuration->isMapUnderscoreToCamelCase() ? str_replace("_", "", $columnName) : $columnName);
    }

    private function createPrimitiveResultObject(ResultSetWrapper $rsw, ResultMap $resultMap, array $rowData, ?string $columnPrefix)
    {
        $resultType = $resultMap->getType();
        $columnName = null;
        if (!empty($resultMap->getResultMappings())) {
            $resultMappingList = $resultMap->getResultMappings();
            $mapping = $resultMappingList[0];
            $columnName = $this->prependPrefix($mapping->getColumn(), $columnPrefix);
        } else {
            $columnName = $rsw->getColumnNames()[0];
        }
        $typeHandler = $rsw->getTypeHandler($resultType, $columnName);
        return $typeHandler->getResult($rowData, $columnName);
    }

    //
    // NESTED QUERY
    //

    private function getNestedQueryConstructorValue(array $rs, ResultMapping $constructorMapping, ?string $columnPrefix)
    {
        $nestedQueryId = $constructorMapping->getNestedQueryId();
        $nestedQuery = $this->configuration->getMappedStatement($nestedQueryId);
        $nestedQueryParameterType = $nestedQuery->getParameterMap()->getType();
        $nestedQueryParameterObject = $this->prepareParameterForNestedQuery($rs, $constructorMapping, $nestedQueryParameterType, $columnPrefix);
        $value = null;
        if ($nestedQueryParameterObject !== null) {
            $nestedBoundSql = $nestedQuery->getBoundSql($nestedQueryParameterObject);
            $key = $this->executor->createCacheKey($nestedQuery, $nestedQueryParameterObject, RowBounds::default(), $nestedBoundSql);
            $targetType = $constructorMapping->getPhpType();
            $resultLoader = new ResultLoader($this->configuration, $this->executor, $nestedQuery, $nestedQueryParameterObject, $targetType, $key, $nestedBoundSql);
            $value = $resultLoader->loadResult();
        }
        return $value;
    }

    private function getNestedQueryMappingValue(array $rs, MetaObject $metaResultObject, ResultMapping $propertyMapping, ResultLoaderMap $lazyLoader, ?string $columnPrefix)
    {
        $nestedQueryId = $propertyMapping->getNestedQueryId();
        $property = $propertyMapping->getProperty();
        $nestedQuery = $this->configuration->getMappedStatement($nestedQueryId);
        $nestedQueryParameterType = $nestedQuery->getParameterMap()->getType();
        $nestedQueryParameterObject = $this->prepareParameterForNestedQuery($rs, $propertyMapping, $nestedQueryParameterType, $columnPrefix);
        $value = null;
        if ($nestedQueryParameterObject !== null) {
            $nestedBoundSql = $nestedQuery->getBoundSql($nestedQueryParameterObject);
            $key = $this->executor->createCacheKey($nestedQuery, $nestedQueryParameterObject, RowBounds::default(), $nestedBoundSql);
            $targetType = $propertyMapping->getPhpType();
            if ($this->executor->isCached($nestedQuery, $key)) {
                $this->executor->deferLoad($nestedQuery, $metaResultObject, $property, $key, $targetType);
                $value = self::$DEFERRED;
            } else {
                $resultLoader = new ResultLoader($this->configuration, $this->executor, $nestedQuery, $nestedQueryParameterObject, $targetType, $key, $nestedBoundSql);
                if ($propertyMapping->isLazy()) {
                    $lazyLoader->addLoader($property, $metaResultObject, $resultLoader);
                    $value = self::$DEFERRED;
                } else {
                    $value = $resultLoader->loadResult();
                }
            }
        }
        return $value;
    }

    private function prepareParameterForNestedQuery(array $rs, ResultMapping $resultMapping, string $parameterType, ?string $columnPrefix)
    {
        if ($resultMapping->isCompositeResult()) {
            return $this->prepareCompositeKeyParameter($rs, $resultMapping, $parameterType, $columnPrefix);
        } else {
            return $this->prepareSimpleKeyParameter($rs, $resultMapping, $parameterType, $columnPrefix);
        }
    }

    private function prepareSimpleKeyParameter(array $rs, ResultMapping $resultMapping, string $parameterType, ?string $columnPrefix)
    {
        $typeHandler = null;
        if ($this->typeHandlerRegistry->hasTypeHandler($parameterType)) {
            $typeHandler = $this->typeHandlerRegistry->getTypeHandler($parameterType);
        } else {
            $typeHandler = $this->typeHandlerRegistry->getUnknownTypeHandler();
        }
        return $typeHandler->getResult($rs, $this->prependPrefix($resultMapping->getColumn(), $columnPrefix));
    }

    private function prepareCompositeKeyParameter(array $rs, ResultMapping $resultMapping, string $parameterType, ?string $columnPrefix)
    {
        $parameterObject = $this->instantiateParameterObject($parameterType);
        $metaObject = $this->configuration->newMetaObject($parameterObject);
        $foundValues = false;
        foreach ($resultMapping->getComposites() as $innerResultMapping) {
            $propType = $metaObject->getSetterType($innerResultMapping->getProperty());
            $typeHandler = $this->typeHandlerRegistry->getTypeHandler($propType);
            $propValue = $typeHandler->getResult($rs, $this->prependPrefix($innerResultMapping->getColumn(), $columnPrefix));
            // issue #353 & #560 do not execute nested query if key is null
            if ($propValue !== null) {
                $metaObject->setValue($innerResultMapping->getProperty(), $propValue);
                $foundValues = true;
            }
        }
        return $foundValues ? $parameterObject : null;
    }

    private function instantiateParameterObject(?string $parameterType)
    {
        if ($parameterType === null || ParamMap::class == $parameterType) {
            return [];
        } else {
            return new $parameterType();
        }
    }

    //
    // DISCRIMINATOR
    //

    public function resolveDiscriminatedResultMap(array $rs, ResultMap $resultMap, ?string $columnPrefix): ResultMap
    {
        $pastDiscriminators = [];
        $discriminator = $resultMap->getDiscriminator();
        while ($discriminator !== null) {
            $value = $this->getDiscriminatorValue($rs, $discriminator, $columnPrefix);
            $discriminatedMapId = $discriminator->getMapIdFor(strval($value));
            if ($this->configuration->hasResultMap($discriminatedMapId)) {
                $resultMap = $this->configuration->getResultMap($discriminatedMapId);
                $lastDiscriminator = $discriminator;
                $discriminator = $resultMap->getDiscriminator();
                if ($discriminator == $lastDiscriminator || !in_array($discriminatedMapId, $pastDiscriminators)) {
                    $pastDiscriminators[] = $discriminatedMapId;
                    break;
                }
            } else {
                break;
            }
        }
        return $resultMap;
    }

    private function getDiscriminatorValue(array $rs, Discriminator $discriminator, ?string $columnPrefix)
    {
        $resultMapping = $discriminator->getResultMapping();
        $typeHandler = $resultMapping->getTypeHandler();
        return $typeHandler->getResult($rs, $this->prependPrefix($resultMapping->getColumn(), $columnPrefix));
    }

    private function prependPrefix(?string $columnName, ?string $prefix): ?string
    {
        if (empty($columnName) || empty($prefix)) {
            return $columnName;
        }
        return $prefix . $columnName;
    }

    //
    // HANDLE NESTED RESULT MAPS
    //

    private function handleRowValuesForNestedResultMap(ResultSetWrapper $rsw, ResultMap $resultMap, ResultHandlerInterface $resultHandler, RowBounds $rowBounds, ResultMapping $parentMapping): void
    {
        $resultContext = new DefaultResultContext();
        $resultSet = $rsw->getResultSet();
        $this->skipRows($resultSet, $rowBounds);
        $rowValue = $previousRowValue;
        while ($this->shouldProcessMoreRows($resultContext, $rowBounds) && ($rs = $resultSet->fetchAssociative())) {
            $discriminatedResultMap = $this->resolveDiscriminatedResultMap($rs, $resultMap, null);
            $rowKey = $this->createRowKey($discriminatedResultMap, $rs, $rsw, null);
            $partialObject = null;
            if (array_key_exists($rowKey, $this->nestedResultObject)) {
                $partialObject = $this->nestedResultObjects[$rowKey];
            }
            // issue #577 && #542
            if ($this->mappedStatement->isResultOrdered()) {
                if ($partialObject === null && $rowValue !== null) {
                    $this->nestedResultObjects = [];
                    $this->storeObject($resultHandler, $resultContext, $rowValue, $parentMapping, $rs);
                }
                $rowValue = $this->getRowValue($rsw, $discriminatedResultMap, $rs, $rowKey, null, $partialObject);
            } else {
                $rowValue = $this->getRowValue($rsw, $discriminatedResultMap, $rs, $rowKey, null, $partialObject);
                if ($partialObject === null) {
                    $this->storeObject($resultHandler, $resultContext, $rowValue, $parentMapping, $rs);
                }
            }
        }
        if ($rowValue !== null && $this->mappedStatement->isResultOrdered() && $this->shouldProcessMoreRows($resultContext, $rowBounds)) {
            $this->storeObject($resultHandler, $resultContext, $rowValue, $parentMapping, $resultSet->fetchAssociative());
            $this->previousRowValue = null;
        } elseif ($rowValue !== null) {
            $this->previousRowValue = $rowValue;
        }
    }

    //
    // NESTED RESULT MAP (JOIN MAPPING)
    //

    private function applyNestedResultMappings(ResultSetWrapper $rsw, ResultMap $resultMap, MetaObject $metaObject, ?string $parentPrefix, ?CacheKey $parentRowKey, bool $newObject): bool
    {
        $foundValues = false;
        $rs = $rsw->getResultSet();
        foreach ($resultMap->getPropertyResultMappings() as $resultMapping) {
            $nestedResultMapId = $resultMapping->getNestedResultMapId();
            if ($nestedResultMapId !== null && $resultMapping->getResultSet() === null) {
                try {
                    $columnPrefix = $this->getColumnPrefix($parentPrefix, $resultMapping);
                    $res = $rs->fetchAssociative();
                    $nestedResultMap = $this->getNestedResultMap($res, $nestedResultMapId, $columnPrefix);
                    if ($resultMapping->getColumnPrefix() === null) {
                        // try to fill circular reference only when columnPrefix
                        // is not specified for the nested result map (issue #215)
                        if (array_key_exists($nestedResultMapId, $this->ancestorObjects)) {
                            $ancestorObject = $this->ancestorObjects[$nestedResultMapId];
                            if ($newObject) {
                                $this->linkObjects($metaObject, $resultMapping, $ancestorObject); // issue #385
                            }
                            continue;
                        }
                    }
                    $rowKey = $this->createRowKey($nestedResultMap, $res, $rsw, $columnPrefix);
                    $combinedKey = $this->combineKeys($rowKey, $parentRowKey);
                    $rowValue = null;
                    if (array_key_exists($combinedKey, $this->nestedResultObjects)) {
                        $rowValue = $this->nestedResultObjects[$combinedKey];
                    }
                    $knownValue = $rowValue !== null;
                    $this->instantiateCollectionPropertyIfAppropriate($resultMapping, $metaObject); // mandatory
                    if ($this->anyNotNullColumnHasValue($resultMapping, $columnPrefix, $rsw)) {
                        $rowValue = $this->getRowValue($rsw, $nestedResultMap, $res, $combinedKey, $columnPrefix, $rowValue);
                        if ($rowValue !== null && !$knownValue) {
                            $this->linkObjects($metaObject, $resultMapping, $rowValue);
                            $foundValues = true;
                        }
                    }
                } catch (\Exception $e) {
                    throw new ExecutorException("Error getting nested result map values for '" . $resultMapping->getProperty() . "'.  Cause: " . $e->getMessage());
                }
            }
        }
        return $foundValues;
    }

    private function getColumnPrefix(?string $parentPrefix, ResultMapping $resultMapping): ?string
    {
        $columnPrefixBuilder = "";
        if (!empty($parentPrefix)) {
            $columnPrefixBuilder .= $parentPrefix;
        }
        if ($resultMapping->getColumnPrefix() !== null) {
            $columnPrefixBuilder .= $resultMapping->getColumnPrefix();
        }
        return strlen($columnPrefixBuilder) == 0 ? null : strtoupper($columnPrefixBuilder);
    }

    private function anyNotNullColumnHasValue(ResultMapping $resultMapping, ?string $columnPrefix, ResultSetWrapper $rsw): bool
    {
        $notNullColumns = $resultMapping->getNotNullColumns();
        if ($notNullColumns !== null && !empty($notNullColumns)) {
            $rs = $rsw->getResultSet();
            $res = $rs->fetchAssociative();
            foreach ($notNullColumns as $column) {
                $key = $this->prependPrefix($column, $columnPrefix);
                if (!empty($res) && array_key_exists($key, $res)) {
                    return true;
                }
            }
            return false;
        } elseif ($columnPrefix !== null) {
            foreach ($rsw->getColumnNames() as $columnName) {
                if (strpos(strtoupper($columnName), strtoupper($columnPrefix))) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    private function getNestedResultMap(array $rs, string $nestedResultMapId, ?string $columnPrefix): ResultMap
    {
        $nestedResultMap = $this->configuration->getResultMap($nestedResultMapId);
        return $this->resolveDiscriminatedResultMap($rs, $nestedResultMap, $columnPrefix);
    }

    //
    // UNIQUE RESULT KEY
    //

    private function createRowKey(ResultMap $resultMap, array $rs, ResultSetWrapper $rsw, ?string $columnPrefix): CacheKey
    {
        $cacheKey = new CacheKey();
        $cacheKey->update($resultMap->getId());
        $resultMappings = $this->getResultMappingsForRowKey($resultMap);
        if (empty($resultMappings)) {
            $type = $resultMap->getType();
            if ($type == "array" || (class_exists($type) && ($type instanceof \ArrayObject))) {
                $this->createRowKeyForMap($rs, $cacheKey);
            } else {
                $this->createRowKeyForUnmappedProperties($resultMap, $rs, $rsw, $cacheKey, $columnPrefix);
            }
        } else {
            $this->createRowKeyForMappedProperties($resultMap, $rs, $rsw, $cacheKey, $resultMappings, $columnPrefix);
        }
        if ($cacheKey->getUpdateCount() < 2) {
            return CacheKey::nullCacheKey();
        }
        return $cacheKey;
    }

    private function combineKeys(CacheKey $rowKey, CacheKey $parentRowKey): CacheKey
    {
        if ($rowKey->getUpdateCount() > 1 && $parentRowKey->getUpdateCount() > 1) {
            $combinedKey = null;
            try {
                $combinedKey = clone($rowKey);
            } catch (\Exception $e) {
                throw new ExecutorException("Error cloning cache key.  Cause: " . $e->getMessage());
            }
            $combinedKey->update($parentRowKey);
            return $combinedKey;
        }
        return CacheKey::nullCacheKey();
    }

    private function getResultMappingsForRowKey(ResultMap $resultMap): array
    {
        $resultMappings = $resultMap->getIdResultMappings();
        if (empty($resultMappings)) {
            $resultMappings = $resultMap->getPropertyResultMappings();
        }
        return $resultMappings;
    }

    private function createRowKeyForMappedProperties(ResultMap $resultMap, array $res, ResultSetWrapper $rsw, CacheKey $cacheKey, array $resultMappings, ?string $columnPrefix): void
    {
        foreach ($resultMappings as $resultMapping) {
            if ($resultMapping->isSimple()) {
                $column = $this->prependPrefix($resultMapping->getColumn(), $columnPrefix);
                $th = $resultMapping->getTypeHandler();
                $mappedColumnNames = $rsw->getMappedColumnNames($resultMap, $columnPrefix);
                // Issue #114
                if ($column !== null && in_array(strtoupper($column), $mappedColumnNames)) {
                    $value = $th->getResult($res, $column);
                    if ($value !== null || $this->configuration->isReturnInstanceForEmptyRow()) {
                        $cacheKey->update($column);
                        $cacheKey->update($value);
                    }
                }
            }
        }
    }

    private function createRowKeyForUnmappedProperties(ResultMap $resultMap, array $row, ResultSetWrapper $rsw, CacheKey $cacheKey, ?string $columnPrefix): void
    {
        $metaType = new MetaClass($resultMap->getType());
        $unmappedColumnNames = $rsw->getUnmappedColumnNames($resultMap, $columnPrefix);
        foreach ($unmappedColumnNames as $column) {
            $property = $column;
            if ($columnPrefix !== null && !empty($columnPrefix)) {
                // When columnPrefix is specified, ignore columns without the prefix.
                if (strpos(strtoupper($column), $columnPrefix) === 0) {
                    $property = substr($column, strlen($columnPrefix));
                } else {
                    continue;
                }
            }
            if ($metaType->findProperty($property, $this->configuration->isMapUnderscoreToCamelCase()) !== null) {
                if (array_key_exists($column, $row)) {
                    $value = $row[$column];
                    $cacheKey->update($column);
                    $cacheKey->update($value);
                }
            }
        }
    }

    private function createRowKeyForMap(array $resultSet, ResultSetWrapper $rsw, CacheKey $cacheKey): void
    {
        $columnNames = $rsw->getColumnNames();
        foreach ($columnNames as $columnName) {
            if (array_key_exists($columnName, $resultSet)) {
                $value = $resultSet[$columnName];
                $cacheKey->update($columnName);
                $cacheKey->update($value);
            }
        }
    }

    private function linkObjects(MetaObject $metaObject, ResultMapping $resultMapping, $rowValue): void
    {
        $collectionProperty = $this->instantiateCollectionPropertyIfAppropriate($resultMapping, $metaObject);
        if ($collectionProperty !== null) {
            $targetMetaObject = $this->configuration->newMetaObject($collectionProperty);
            $targetMetaObject->add($rowValue);
        } else {
            $metaObject->setValue($resultMapping->getProperty(), $rowValue);
        }
    }

    private function instantiateCollectionPropertyIfAppropriate(ResultMapping $resultMapping, MetaObject $metaObject)
    {
        $propertyName = $resultMapping->getProperty();
        $propertyValue = $metaObject->getValue($propertyName);
        if ($propertyValue === null) {
            $type = $resultMapping->getPhpType();
            if ($type == null) {
                $type = $metaObject->getSetterType($propertyName);
            }
            try {
                if ($type == "array" || (class_exists($type) && $type instanceof \ArrayObject)) {
                    if ($type == "array") {
                        $propertyValue = [];
                    } else {
                        $propertyValue = new $type();
                    }
                    $metaObject->setValue($propertyName, $propertyValue);
                    return $propertyValue;
                }
            } catch (\Exception $e) {
                throw new ExecutorException("Error instantiating collection property for result '" . $resultMapping->getProperty() . "'.  Cause: " . $e->getMessage());
            }
        } elseif (is_array($propertyValue) || $propertyValue instanceof \ArrayObject) {
            return $propertyValue;
        }
        return null;
    }

    private function hasTypeHandlerForResultObject(ResultSetWrapper $rsw, ?string $resultType): bool
    {
        return $this->typeHandlerRegistry->hasTypeHandler($resultType);
    }
}
