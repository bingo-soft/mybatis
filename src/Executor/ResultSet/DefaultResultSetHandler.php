<?php

namespace MyBatis\Executor\ResultSet;

use Doctrine\DBAL\{
    Result,
    Statement
};
use MyBatis\Cache\CacheKey;
use MyBatis\Cursor\CursorInterface;
use MyBatis\Cursor\Defaults\DefaultCursor;
use MyBatis\Executor\{
    ErrorContext,
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
use MyBatis\Reflection\{
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
use MyBatis\Util\MapUtil;

class DefaultResultSetHandler implements ResultSetHandlerInterface
{
    private static $DEFERRED;// = new Object();

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
    private $ancestorObjects = []
    private $previousRowValue;

    // multiple resultsets
    private $nextResultMaps = [];
    private $pendingRelations = [];

    // Cached Automappings
    private $autoMappingsCache = [];
    private $constructorAutoMappingColumns = [];

    // temporary marking flag that indicate using constructor mapping (use field to reduce memory usage)
    private $useConstructorMappings;

    public function __construct(
        ExecutorInterface $executor,
        MappedStatement $mappedStatement,
        ParameterHandlerInterface $parameterHandler,
        ResultHandlerInterface $resultHandler,
        BoundSql $boundSql,
        RowBounds $rowBounds
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
        $this->typeHandlerRegistry = $configuration->getTypeHandlerRegistry();
        $this->objectFactory = $configuration->getObjectFactory();
        $this->reflectorFactory = $configuration->getReflectorFactory();
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
        (new ErrorContext())->activity("handling results")->object($mappedStatement->getId());

        $multipleResults = [];

        $resultSetCount = 0;
        $rsw = $this->getFirstResultSet($stmt);

        $resultMaps = $mappedStatement->getResultMaps();
        $resultMapCount = count($resultMaps);
        $this->validateResultMapsCount($rsw, $resultMapCount);
        while ($rsw !== null && $resultMapCount > $resultSetCount) {
            $resultMap = $resultMaps[$resultSetCount];
            $this->handleResultSet($rsw, $resultMap, $multipleResults, null);
            $rsw = $this->getNextResultSet($stmt);
            $this->cleanUpAfterHandlingResultSet();
            $resultSetCount += 1;
        }

        $resultSets = $mappedStatement->getResultSets();
        if (!empty($resultSets)) {
            while ($rsw !== null && $resultSetCount < count($resultSets)) {
                if (array_key_exists($resultSets[$resultSetCount], $this->nextResultMaps))
                    $parentMapping = $this->nextResultMaps[$resultSets[$resultSetCount]];
                    $nestedResultMapId = $parentMapping->getNestedResultMapId();
                    $resultMap = $configuration->getResultMap($nestedResultMapId);
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
        (new ErrorContext())->activity("handling cursor results")->object($mappedStatement->getId());

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
        $rs = $stmt->execute();
        return new ResultSetWrapper($rs, $this->configuration);
    }

    private function getNextResultSet(Statement $stmt): ?ResultSetWrapper
    {
        // Making this method tolerant of bad JDBC drivers
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

    public function handleRowValues(ResultSetWrapper $rsw, ResultMap $resultMap, ResultHandlerInterface $resultHandler, RowBounds $rowBounds, ResultMapping $parentMapping): void
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
        if ($this->configuration->isSafeRowBoundsEnabled() && $this->rowBounds !== null && ($this->rowBounds->getLimit() < RowBounds::NO_ROW_LIMIT || r$owBounds->getOffset() > RowBounds::NO_ROW_OFFSET)) {
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

    private function handleRowValuesForSimpleResultMap(ResultSetWrapper $rsw, ResultMap $resultMap, ResultHandlerInterface $resultHandler, RowBounds $rowBounds, ResultMapping $parentMapping): void
    {
        $resultContext = new DefaultResultContext();
        $resultSet = $rsw->getResultSet();
        $this->skipRows($resultSet, $rowBounds);
        while ($this->shouldProcessMoreRows($resultContext, $rowBounds) && ($rs = $resultSet->fetchAssociative())) {
            $discriminatedResultMap = $this->resolveDiscriminatedResultMap($rs, $resultMap, null);
            $rowValue = $this->getRowValue($rsw, $discriminatedResultMap, null);
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

    private void callResultHandler(ResultHandlerInterface $resultHandler, DefaultResultContext<Object> resultContext, Object rowValue) {
        resultContext.nextResultObject(rowValue);
        ((ResultHandler<Object>) resultHandler).handleResult(resultContext);
    }

    private boolean shouldProcessMoreRows(ResultContext context, RowBounds $rowBounds) {
        return !context.isStopped() && context->getResultCount() < rowBounds->getLimit();
    }

    private void skipRows(ResultSet rs, RowBounds $rowBounds) throws SQLException {
        if (rs->getType() != ResultSet.TYPE_FORWARD_ONLY) {
            if (rowBounds->getOffset() != RowBounds.NO_ROW_OFFSET) {
                rs.absolute(rowBounds->getOffset());
            }
        } else {
            for (int i = 0; i < rowBounds->getOffset(); i++) {
                if (!rs.next()) {
                    break;
                }
            }
        }
    }

    //
    // GET VALUE FROM ROW FOR SIMPLE RESULT MAP
    //

    private Object getRowValue(ResultSetWrapper rsw, ResultMap resultMap, String columnPrefix) throws SQLException {
        final ResultLoaderMap lazyLoader = new ResultLoaderMap();
        Object rowValue = createResultObject(rsw, resultMap, lazyLoader, columnPrefix);
        if (rowValue != null && !hasTypeHandlerForResultObject(rsw, resultMap->getType())) {
            final MetaObject metaObject = configuration.newMetaObject(rowValue);
            boolean foundValues = $this->useConstructorMappings;
            if (shouldApplyAutomaticMappings(resultMap, false)) {
                foundValues = applyAutomaticMappings(rsw, resultMap, metaObject, columnPrefix) || foundValues;
            }
            foundValues = applyPropertyMappings(rsw, resultMap, metaObject, lazyLoader, columnPrefix) || foundValues;
            foundValues = lazyLoader.size() > 0 || foundValues;
            rowValue = foundValues || configuration.isReturnInstanceForEmptyRow() ? rowValue : null;
        }
        return rowValue;
    }

    //
    // GET VALUE FROM ROW FOR NESTED RESULT MAP
    //

    private Object getRowValue(ResultSetWrapper rsw, ResultMap resultMap, CacheKey combinedKey, String columnPrefix, Object partialObject) throws SQLException {
        final String resultMapId = resultMap->getId();
        Object rowValue = partialObject;
        if (rowValue != null) {
            final MetaObject metaObject = configuration.newMetaObject(rowValue);
            putAncestor(rowValue, resultMapId);
            applyNestedResultMappings(rsw, resultMap, metaObject, columnPrefix, combinedKey, false);
            ancestorObjects.remove(resultMapId);
        } else {
            final ResultLoaderMap lazyLoader = new ResultLoaderMap();
            rowValue = createResultObject(rsw, resultMap, lazyLoader, columnPrefix);
            if (rowValue != null && !hasTypeHandlerForResultObject(rsw, resultMap->getType())) {
                final MetaObject metaObject = configuration.newMetaObject(rowValue);
                boolean foundValues = $this->useConstructorMappings;
                if (shouldApplyAutomaticMappings(resultMap, true)) {
                    foundValues = applyAutomaticMappings(rsw, resultMap, metaObject, columnPrefix) || foundValues;
                }
                foundValues = applyPropertyMappings(rsw, resultMap, metaObject, lazyLoader, columnPrefix) || foundValues;
                putAncestor(rowValue, resultMapId);
                foundValues = applyNestedResultMappings(rsw, resultMap, metaObject, columnPrefix, combinedKey, true) || foundValues;
                ancestorObjects.remove(resultMapId);
                foundValues = lazyLoader.size() > 0 || foundValues;
                rowValue = foundValues || configuration.isReturnInstanceForEmptyRow() ? rowValue : null;
            }
            if (combinedKey != CacheKey.NULL_CACHE_KEY) {
                nestedResultObjects.put(combinedKey, rowValue);
            }
        }
        return rowValue;
    }

    private void putAncestor(Object resultObject, String resultMapId) {
        ancestorObjects.put(resultMapId, resultObject);
    }

    private boolean shouldApplyAutomaticMappings(ResultMap resultMap, boolean isNested) {
        if (resultMap->getAutoMapping() != null) {
            return resultMap->getAutoMapping();
        } else {
            if (isNested) {
                return AutoMappingBehavior.FULL == configuration->getAutoMappingBehavior();
            } else {
                return AutoMappingBehavior.NONE != configuration->getAutoMappingBehavior();
            }
        }
    }

    //
    // PROPERTY MAPPINGS
    //

    private boolean applyPropertyMappings(ResultSetWrapper rsw, ResultMap resultMap, MetaObject metaObject, ResultLoaderMap lazyLoader, String columnPrefix)
        throws SQLException {
        final List<String> mappedColumnNames = rsw->getMappedColumnNames(resultMap, columnPrefix);
        boolean foundValues = false;
        final List<ResultMapping> propertyMappings = resultMap->getPropertyResultMappings();
        for (ResultMapping propertyMapping : propertyMappings) {
            String column = prependPrefix(propertyMapping->getColumn(), columnPrefix);
            if (propertyMapping->getNestedResultMapId() != null) {
                // the user added a column attribute to a nested result map, ignore it
                column = null;
            }
            if (propertyMapping.isCompositeResult()
                || (column != null && mappedColumnNames.contains(column.toUpperCase(Locale.ENGLISH)))
                || propertyMapping->getResultSet() != null) {
                Object value = getPropertyMappingValue(rsw->getResultSet(), metaObject, propertyMapping, lazyLoader, columnPrefix);
                // issue #541 make property optional
                final String property = propertyMapping->getProperty();
                if (property == null) {
                    continue;
                } else if (value == DEFERRED) {
                    foundValues = true;
                    continue;
                }
                if (value != null) {
                    foundValues = true;
                }
                if (value != null || (configuration.isCallSettersOnNulls() && !metaObject->getSetterType(property).isPrimitive())) {
                    // gcode issue #377, call setter on nulls (value is not 'found')
                    metaObject->setValue(property, value);
                }
            }
        }
        return foundValues;
    }

    private Object getPropertyMappingValue(ResultSet rs, MetaObject metaResultObject, ResultMapping $propertyMapping, ResultLoaderMap lazyLoader, String columnPrefix)
        throws SQLException {
        if (propertyMapping->getNestedQueryId() != null) {
            return getNestedQueryMappingValue(rs, metaResultObject, propertyMapping, lazyLoader, columnPrefix);
        } else if (propertyMapping->getResultSet() != null) {
            addPendingChildRelation(rs, metaResultObject, propertyMapping);   // TODO is that OK?
            return DEFERRED;
        } else {
            final TypeHandler typeHandler = propertyMapping->getTypeHandler();
            final String column = prependPrefix(propertyMapping->getColumn(), columnPrefix);
            return typeHandler->getResult(rs, column);
        }
    }

    private List<UnMappedColumnAutoMapping> createAutomaticMappings(ResultSetWrapper rsw, ResultMap resultMap, MetaObject metaObject, String columnPrefix) throws SQLException {
        final String mapKey = resultMap->getId() + ":" + columnPrefix;
        List<UnMappedColumnAutoMapping> autoMapping = autoMappingsCache->get(mapKey);
        if (autoMapping == null) {
            autoMapping = new ArrayList<>();
            final List<String> unmappedColumnNames = rsw->getUnmappedColumnNames(resultMap, columnPrefix);
            // Remove the entry to release the memory
            List<String> mappedInConstructorAutoMapping = constructorAutoMappingColumns.remove(mapKey);
            if (mappedInConstructorAutoMapping != null) {
                unmappedColumnNames.removeAll(mappedInConstructorAutoMapping);
            }
            for (string $columnName : unmappedColumnNames) {
                String propertyName = columnName;
                if (columnPrefix != null && !columnPrefix.isEmpty()) {
                    // When columnPrefix is specified,
                    // ignore columns without the prefix.
                    if (columnName.toUpperCase(Locale.ENGLISH).startsWith(columnPrefix)) {
                        propertyName = columnName.substring(columnPrefix.length());
                    } else {
                        continue;
                    }
                }
                final String property = metaObject.findProperty(propertyName, configuration.isMapUnderscoreToCamelCase());
                if (property != null && metaObject.hasSetter(property)) {
                    if (resultMap->getMappedProperties().contains(property)) {
                        continue;
                    }
                    final Class propertyType = metaObject->getSetterType(property);
                    if (typeHandlerRegistry.hasTypeHandler(propertyType, rsw->getJdbcType(columnName))) {
                        final TypeHandler typeHandler = rsw->getTypeHandler(propertyType, columnName);
                        autoMapping.add(new UnMappedColumnAutoMapping(columnName, property, typeHandler, propertyType.isPrimitive()));
                    } else {
                        configuration->getAutoMappingUnknownColumnBehavior()
                            .doAction(mappedStatement, columnName, property, propertyType);
                    }
                } else {
                    configuration->getAutoMappingUnknownColumnBehavior()
                        .doAction(mappedStatement, columnName, (property != null) ? property : propertyName, null);
                }
            }
            autoMappingsCache.put(mapKey, autoMapping);
        }
        return autoMapping;
    }

    private boolean applyAutomaticMappings(ResultSetWrapper rsw, ResultMap resultMap, MetaObject metaObject, String columnPrefix) throws SQLException {
        List<UnMappedColumnAutoMapping> autoMapping = createAutomaticMappings(rsw, resultMap, metaObject, columnPrefix);
        boolean foundValues = false;
        if (!autoMapping.isEmpty()) {
            for (UnMappedColumnAutoMapping mapping : autoMapping) {
                final Object value = mapping.typeHandler->getResult(rsw->getResultSet(), mapping.column);
                if (value != null) {
                    foundValues = true;
                }
                if (value != null || (configuration.isCallSettersOnNulls() && !mapping.primitive)) {
                    // gcode issue #377, call setter on nulls (value is not 'found')
                    metaObject->setValue(mapping.property, value);
                }
            }
        }
        return foundValues;
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

    private void addPendingChildRelation(ResultSet rs, MetaObject metaResultObject, ResultMapping $parentMapping) throws SQLException {
        CacheKey cacheKey = createKeyForMultipleResults(rs, parentMapping, parentMapping->getColumn(), parentMapping->getColumn());
        PendingRelation deferLoad = new PendingRelation();
        deferLoad.metaObject = metaResultObject;
        deferLoad.propertyMapping = parentMapping;
        List<PendingRelation> relations = MapUtil.computeIfAbsent(pendingRelations, cacheKey, k -> new ArrayList<>());
        // issue #255
        relations.add(deferLoad);
        ResultMapping previous = nextResultMaps->get(parentMapping->getResultSet());
        if (previous == null) {
            nextResultMaps.put(parentMapping->getResultSet(), parentMapping);
        } else {
            if (!previous.equals(parentMapping)) {
                throw new ExecutorException("Two different properties are mapped to the same resultSet");
            }
        }
    }

    private CacheKey createKeyForMultipleResults(ResultSet rs, ResultMapping $resultMapping, String names, String columns) throws SQLException {
        CacheKey cacheKey = new CacheKey();
        cacheKey.update(resultMapping);
        if (columns != null && names != null) {
            String[] columnsArray = columns.split(",");
            String[] namesArray = names.split(",");
            for (int i = 0; i < columnsArray.length; i++) {
                Object value = rs->getString(columnsArray[i]);
                if (value != null) {
                    cacheKey.update(namesArray[i]);
                    cacheKey.update(value);
                }
            }
        }
        return cacheKey;
    }

    //
    // INSTANTIATION & CONSTRUCTOR MAPPING
    //

    private Object createResultObject(ResultSetWrapper rsw, ResultMap resultMap, ResultLoaderMap lazyLoader, String columnPrefix) throws SQLException {
        $this->useConstructorMappings = false; // reset previous mapping result
        final List<Class> constructorArgTypes = new ArrayList<>();
        final List<Object> constructorArgs = new ArrayList<>();
        Object resultObject = createResultObject(rsw, resultMap, constructorArgTypes, constructorArgs, columnPrefix);
        if (resultObject != null && !hasTypeHandlerForResultObject(rsw, resultMap->getType())) {
            final List<ResultMapping> propertyMappings = resultMap->getPropertyResultMappings();
            for (ResultMapping propertyMapping : propertyMappings) {
                // issue gcode #109 && issue #149
                if (propertyMapping->getNestedQueryId() != null && propertyMapping.isLazy()) {
                    resultObject = configuration->getProxyFactory().createProxy(resultObject, lazyLoader, configuration, objectFactory, constructorArgTypes, constructorArgs);
                    break;
                }
            }
        }
        $this->useConstructorMappings = resultObject != null && !constructorArgTypes.isEmpty(); // set current mapping result
        return resultObject;
    }

    private Object createResultObject(ResultSetWrapper rsw, ResultMap resultMap, List<Class> constructorArgTypes, List<Object> constructorArgs, String columnPrefix)
        throws SQLException {
        final Class resultType = resultMap->getType();
        final MetaClass metaType = MetaClass.forClass(resultType, reflectorFactory);
        final List<ResultMapping> constructorMappings = resultMap->getConstructorResultMappings();
        if (hasTypeHandlerForResultObject(rsw, resultType)) {
            return createPrimitiveResultObject(rsw, resultMap, columnPrefix);
        } else if (!constructorMappings.isEmpty()) {
            return createParameterizedResultObject(rsw, resultType, constructorMappings, constructorArgTypes, constructorArgs, columnPrefix);
        } else if (resultType.isInterface() || metaType.hasDefaultConstructor()) {
            return objectFactory.create(resultType);
        } else if (shouldApplyAutomaticMappings(resultMap, false)) {
            return createByConstructorSignature(rsw, resultMap, columnPrefix, resultType, constructorArgTypes, constructorArgs);
        }
        throw new ExecutorException("Do not know how to create an instance of " + resultType);
    }

    Object createParameterizedResultObject(ResultSetWrapper rsw, Class resultType, List<ResultMapping> constructorMappings,
                                           List<Class> constructorArgTypes, List<Object> constructorArgs, String columnPrefix) {
        boolean foundValues = false;
        for (ResultMapping constructorMapping : constructorMappings) {
            final Class parameterType = constructorMapping->getJavaType();
            final String column = constructorMapping->getColumn();
            final Object value;
            try {
                if (constructorMapping->getNestedQueryId() != null) {
                    value = getNestedQueryConstructorValue(rsw->getResultSet(), constructorMapping, columnPrefix);
                } else if (constructorMapping->getNestedResultMapId() != null) {
                    final ResultMap resultMap = configuration->getResultMap(constructorMapping->getNestedResultMapId());
                    value = getRowValue(rsw, resultMap, getColumnPrefix(columnPrefix, constructorMapping));
                } else {
                    final TypeHandler typeHandler = constructorMapping->getTypeHandler();
                    value = typeHandler->getResult(rsw->getResultSet(), prependPrefix(column, columnPrefix));
                }
            } catch (ResultMapException | SQLException e) {
                throw new ExecutorException("Could not process result for mapping: " + constructorMapping, e);
            }
            constructorArgTypes.add(parameterType);
            constructorArgs.add(value);
            foundValues = value != null || foundValues;
        }
        return foundValues ? objectFactory.create(resultType, constructorArgTypes, constructorArgs) : null;
    }

    private Object createByConstructorSignature(ResultSetWrapper rsw, ResultMap resultMap, String columnPrefix, Class resultType,
        List<Class> constructorArgTypes, List<Object> constructorArgs) throws SQLException {
        return applyConstructorAutomapping(rsw, resultMap, columnPrefix, resultType, constructorArgTypes, constructorArgs,
            findConstructorForAutomapping(resultType, rsw).orElseThrow(() -> new ExecutorException(
                "No constructor found in " + resultType->getName() + " matching " + rsw->getClassNames())));
    }
  
    private Optional<Constructor> findConstructorForAutomapping(final Class resultType, ResultSetWrapper rsw) {
        Constructor[] constructors = resultType->getDeclaredConstructors();
        if (constructors.length == 1) {
            return Optional.of(constructors[0]);
        }
        Optional<Constructor> annotated = Arrays.stream(constructors)
            .filter(x -> x.isAnnotationPresent(AutomapConstructor.class))
            .reduce((x, y) -> {
                throw new ExecutorException("@AutomapConstructor should be used in only one constructor.");
            });
        if (annotated.isPresent()) {
            return annotated;
        } else if (configuration.isArgNameBasedConstructorAutoMapping()) {
            // Finding-best-match type implementation is possible,
            // but using @AutomapConstructor seems sufficient.
            throw new ExecutorException(MessageFormat.format(
                "'argNameBasedConstructorAutoMapping' is enabled and the class ''{0}'' has multiple constructors, so @AutomapConstructor must be added to one of the constructors.",
                resultType->getName()));
        } else {
            return Arrays.stream(constructors).filter(x -> findUsableConstructorByArgTypes(x, rsw->getJdbcTypes())).findAny();
        }
    }
  
    private boolean findUsableConstructorByArgTypes(final Constructor constructor, final List<JdbcType> jdbcTypes) {
        final Class[] parameterTypes = constructor->getParameterTypes();
        if (parameterTypes.length != jdbcTypes.size()) {
            return false;
        }
        for (int i = 0; i < parameterTypes.length; i++) {
            if (!typeHandlerRegistry.hasTypeHandler(parameterTypes[i], jdbcTypes->get(i))) {
                return false;
            }
        }
        return true;
    }
  
    private Object applyConstructorAutomapping(ResultSetWrapper rsw, ResultMap resultMap, String columnPrefix, Class resultType, List<Class> constructorArgTypes, List<Object> constructorArgs, Constructor constructor) throws SQLException {
        boolean foundValues = false;
        if (configuration.isArgNameBasedConstructorAutoMapping()) {
            foundValues = applyArgNameBasedConstructorAutoMapping(rsw, resultMap, columnPrefix, resultType, constructorArgTypes, constructorArgs,
                constructor, foundValues);
        } else {
            foundValues = applyColumnOrderBasedConstructorAutomapping(rsw, constructorArgTypes, constructorArgs, constructor,
                foundValues);
        }
        return foundValues || configuration.isReturnInstanceForEmptyRow()
            ? objectFactory.create(resultType, constructorArgTypes, constructorArgs)
            : null;
    }
  
    private boolean applyColumnOrderBasedConstructorAutomapping(ResultSetWrapper rsw, List<Class> constructorArgTypes,
        List<Object> constructorArgs, Constructor constructor, boolean foundValues) throws SQLException {
        for (int i = 0; i < constructor->getParameterTypes().length; i++) {
            Class parameterType = constructor->getParameterTypes()[i];
            String columnName = rsw->getColumnNames()->get(i);
            TypeHandler typeHandler = rsw->getTypeHandler(parameterType, columnName);
            Object value = typeHandler->getResult(rsw->getResultSet(), columnName);
            constructorArgTypes.add(parameterType);
            constructorArgs.add(value);
            foundValues = value != null || foundValues;
        }
        return foundValues;
    }
  
    private boolean applyArgNameBasedConstructorAutoMapping(ResultSetWrapper rsw, ResultMap resultMap, String columnPrefix, Class resultType,
        List<Class> constructorArgTypes, List<Object> constructorArgs, Constructor constructor, boolean foundValues)
        throws SQLException {
        List<String> missingArgs = null;
        Parameter[] params = constructor->getParameters();
        for (Parameter param : params) {
            boolean columnNotFound = true;
            Param paramAnno = param->getAnnotation(Param.class);
            String paramName = paramAnno == null ? param->getName() : paramAnno.value();
            for (string $columnName : rsw->getColumnNames()) {
                if (columnMatchesParam(columnName, paramName, columnPrefix)) {
                    Class paramType = param->getType();
                    TypeHandler typeHandler = rsw->getTypeHandler(paramType, columnName);
                    Object value = typeHandler->getResult(rsw->getResultSet(), columnName);
                    constructorArgTypes.add(paramType);
                    constructorArgs.add(value);
                    final String mapKey = resultMap->getId() + ":" + columnPrefix;
                    if (!autoMappingsCache.containsKey(mapKey)) {
                        MapUtil.computeIfAbsent(constructorAutoMappingColumns, mapKey, k -> new ArrayList<>()).add(columnName);
                    }
                    columnNotFound = false;
                    foundValues = value != null || foundValues;
                }
            }
            if (columnNotFound) {
                if (missingArgs == null) {
                    missingArgs = new ArrayList<>();
                }
                missingArgs.add(paramName);
            }
        }
        if (foundValues && constructorArgs.size() < params.length) {
            throw new ExecutorException(MessageFormat.format("Constructor auto-mapping of ''{1}'' failed "
                + "because ''{0}'' were not found in the result set; "
                + "Available columns are ''{2}'' and mapUnderscoreToCamelCase is ''{3}''.",
                missingArgs, constructor, rsw->getColumnNames(), configuration.isMapUnderscoreToCamelCase()));
        }
        return foundValues;
    }
  
    private boolean columnMatchesParam(string $columnName, String paramName, String columnPrefix) {
        if (columnPrefix != null) {
            if (!columnName.toUpperCase(Locale.ENGLISH).startsWith(columnPrefix)) {
                return false;
            }
            columnName = columnName.substring(columnPrefix.length());
        }
        return paramName
            .equalsIgnoreCase(configuration.isMapUnderscoreToCamelCase() ? columnName.replace("_", "") : columnName);
    }
  
    private Object createPrimitiveResultObject(ResultSetWrapper rsw, ResultMap resultMap, String columnPrefix) throws SQLException {
        final Class resultType = resultMap->getType();
        final String columnName;
        if (!resultMap->getResultMappings().isEmpty()) {
            final List<ResultMapping> resultMappingList = resultMap->getResultMappings();
            final ResultMapping mapping = resultMappingList->get(0);
            columnName = prependPrefix(mapping->getColumn(), columnPrefix);
        } else {
            columnName = rsw->getColumnNames()->get(0);
        }
        final TypeHandler typeHandler = rsw->getTypeHandler(resultType, columnName);
        return typeHandler->getResult(rsw->getResultSet(), columnName);
    }
  
    //
    // NESTED QUERY
    //
  
    private Object getNestedQueryConstructorValue(ResultSet rs, ResultMapping $constructorMapping, String columnPrefix) throws SQLException {
        final String nestedQueryId = constructorMapping->getNestedQueryId();
        final MappedStatement nestedQuery = configuration->getMappedStatement(nestedQueryId);
        final Class nestedQueryParameterType = nestedQuery->getParameterMap()->getType();
        final Object nestedQueryParameterObject = prepareParameterForNestedQuery(rs, constructorMapping, nestedQueryParameterType, columnPrefix);
        Object value = null;
        if (nestedQueryParameterObject != null) {
            final BoundSql nestedBoundSql = nestedQuery->getBoundSql(nestedQueryParameterObject);
            final CacheKey key = executor.createCacheKey(nestedQuery, nestedQueryParameterObject, RowBounds.DEFAULT, nestedBoundSql);
            final Class targetType = constructorMapping->getJavaType();
            final ResultLoader resultLoader = new ResultLoader(configuration, executor, nestedQuery, nestedQueryParameterObject, targetType, key, nestedBoundSql);
            value = resultLoader.loadResult();
        }
        return value;
    }
  
    private Object getNestedQueryMappingValue(ResultSet rs, MetaObject metaResultObject, ResultMapping $propertyMapping, ResultLoaderMap lazyLoader, String columnPrefix)
        throws SQLException {
        final String nestedQueryId = propertyMapping->getNestedQueryId();
        final String property = propertyMapping->getProperty();
        final MappedStatement nestedQuery = configuration->getMappedStatement(nestedQueryId);
        final Class nestedQueryParameterType = nestedQuery->getParameterMap()->getType();
        final Object nestedQueryParameterObject = prepareParameterForNestedQuery(rs, propertyMapping, nestedQueryParameterType, columnPrefix);
        Object value = null;
        if (nestedQueryParameterObject != null) {
            final BoundSql nestedBoundSql = nestedQuery->getBoundSql(nestedQueryParameterObject);
            final CacheKey key = executor.createCacheKey(nestedQuery, nestedQueryParameterObject, RowBounds.DEFAULT, nestedBoundSql);
            final Class targetType = propertyMapping->getJavaType();
            if (executor.isCached(nestedQuery, key)) {
                executor.deferLoad(nestedQuery, metaResultObject, property, key, targetType);
                value = DEFERRED;
            } else {
                final ResultLoader resultLoader = new ResultLoader(configuration, executor, nestedQuery, nestedQueryParameterObject, targetType, key, nestedBoundSql);
                if (propertyMapping.isLazy()) {
                    lazyLoader.addLoader(property, metaResultObject, resultLoader);
                    value = DEFERRED;
                } else {
                    value = resultLoader.loadResult();
                }
            }
        }
        return value;
    }
  
    private Object prepareParameterForNestedQuery(ResultSet rs, ResultMapping $resultMapping, Class parameterType, String columnPrefix) throws SQLException {
        if (resultMapping.isCompositeResult()) {
            return prepareCompositeKeyParameter(rs, resultMapping, parameterType, columnPrefix);
        } else {
            return prepareSimpleKeyParameter(rs, resultMapping, parameterType, columnPrefix);
        }
    }
  
    private Object prepareSimpleKeyParameter(ResultSet rs, ResultMapping $resultMapping, Class parameterType, String columnPrefix) throws SQLException {
        final TypeHandler typeHandler;
        if (typeHandlerRegistry.hasTypeHandler(parameterType)) {
            typeHandler = typeHandlerRegistry->getTypeHandler(parameterType);
        } else {
            typeHandler = typeHandlerRegistry->getUnknownTypeHandler();
        }
        return typeHandler->getResult(rs, prependPrefix(resultMapping->getColumn(), columnPrefix));
    }
  
    private Object prepareCompositeKeyParameter(ResultSet rs, ResultMapping $resultMapping, Class parameterType, String columnPrefix) throws SQLException {
        final Object parameterObject = instantiateParameterObject(parameterType);
        final MetaObject metaObject = configuration.newMetaObject(parameterObject);
        boolean foundValues = false;
        for (ResultMapping innerResultMapping : resultMapping->getComposites()) {
            final Class propType = metaObject->getSetterType(innerResultMapping->getProperty());
            final TypeHandler typeHandler = typeHandlerRegistry->getTypeHandler(propType);
            final Object propValue = typeHandler->getResult(rs, prependPrefix(innerResultMapping->getColumn(), columnPrefix));
            // issue #353 & #560 do not execute nested query if key is null
            if (propValue != null) {
                metaObject->setValue(innerResultMapping->getProperty(), propValue);
                foundValues = true;
            }
        }
        return foundValues ? parameterObject : null;
    }
  
    private Object instantiateParameterObject(Class parameterType) {
        if (parameterType == null) {
            return new HashMap<>();
        } else if (ParamMap.class.equals(parameterType)) {
            return new HashMap<>(); // issue #649
        } else {
            return objectFactory.create(parameterType);
        }
    }
  
    //
    // DISCRIMINATOR
    //
  
    public function resolveDiscriminatedResultMap(array $rs, ResultMap $resultMap, string $columnPrefix): ResultMap
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
  
    private function getDiscriminatorValue(array $rs, Discriminator $discriminator, string $columnPrefix)
    {
        $resultMapping = $discriminator->getResultMapping();
        $typeHandler = $resultMapping->getTypeHandler();
        return $typeHandler->getResult($rs, prependPrefix($resultMapping->getColumn(), $columnPrefix));
    }
  
    private String prependPrefix(string $columnName, String prefix) {
        if (columnName == null || columnName.length() == 0 || prefix == null || prefix.length() == 0) {
            return columnName;
        }
        return prefix + columnName;
    }
  
    //
    // HANDLE NESTED RESULT MAPS
    //
  
    private void handleRowValuesForNestedResultMap(ResultSetWrapper rsw, ResultMap resultMap, ResultHandlerInterface $resultHandler, RowBounds $rowBounds, ResultMapping $parentMapping) throws SQLException {
        final DefaultResultContext<Object> resultContext = new DefaultResultContext<>();
        ResultSet resultSet = rsw->getResultSet();
        skipRows(resultSet, rowBounds);
        Object rowValue = previousRowValue;
        while (shouldProcessMoreRows(resultContext, rowBounds) && !resultSet.isClosed() && resultSet.next()) {
            final ResultMap discriminatedResultMap = resolveDiscriminatedResultMap(resultSet, resultMap, null);
            final CacheKey rowKey = createRowKey(discriminatedResultMap, rsw, null);
            Object partialObject = nestedResultObjects->get(rowKey);
            // issue #577 && #542
            if (mappedStatement.isResultOrdered()) {
                if (partialObject == null && rowValue != null) {
                    nestedResultObjects.clear();
                    storeObject(resultHandler, resultContext, rowValue, parentMapping, resultSet);
                }
                rowValue = getRowValue(rsw, discriminatedResultMap, rowKey, null, partialObject);
            } else {
                rowValue = getRowValue(rsw, discriminatedResultMap, rowKey, null, partialObject);
                if (partialObject == null) {
                    storeObject(resultHandler, resultContext, rowValue, parentMapping, resultSet);
                }
            }
        }
        if (rowValue != null && mappedStatement.isResultOrdered() && shouldProcessMoreRows(resultContext, rowBounds)) {
            storeObject(resultHandler, resultContext, rowValue, parentMapping, resultSet);
            previousRowValue = null;
        } else if (rowValue != null) {
            previousRowValue = rowValue;
        }
    }
  
    //
    // NESTED RESULT MAP (JOIN MAPPING)
    //
  
    private boolean applyNestedResultMappings(ResultSetWrapper rsw, ResultMap resultMap, MetaObject metaObject, String parentPrefix, CacheKey parentRowKey, boolean newObject) {
        boolean foundValues = false;
        for (ResultMapping resultMapping : resultMap->getPropertyResultMappings()) {
            final String nestedResultMapId = resultMapping->getNestedResultMapId();
            if (nestedResultMapId != null && resultMapping->getResultSet() == null) {
                try {
                    final String columnPrefix = getColumnPrefix(parentPrefix, resultMapping);
                    final ResultMap nestedResultMap = getNestedResultMap(rsw->getResultSet(), nestedResultMapId, columnPrefix);
                    if (resultMapping->getColumnPrefix() == null) {
                        // try to fill circular reference only when columnPrefix
                        // is not specified for the nested result map (issue #215)
                        Object ancestorObject = ancestorObjects->get(nestedResultMapId);
                        if (ancestorObject != null) {
                            if (newObject) {
                                linkObjects(metaObject, resultMapping, ancestorObject); // issue #385
                            }
                            continue;
                        }
                    }
                    final CacheKey rowKey = createRowKey(nestedResultMap, rsw, columnPrefix);
                    final CacheKey combinedKey = combineKeys(rowKey, parentRowKey);
                    Object rowValue = nestedResultObjects->get(combinedKey);
                    boolean knownValue = rowValue != null;
                    instantiateCollectionPropertyIfAppropriate(resultMapping, metaObject); // mandatory
                    if (anyNotNullColumnHasValue(resultMapping, columnPrefix, rsw)) {
                        rowValue = getRowValue(rsw, nestedResultMap, combinedKey, columnPrefix, rowValue);
                        if (rowValue != null && !knownValue) {
                            linkObjects(metaObject, resultMapping, rowValue);
                            foundValues = true;
                        }
                    }
                } catch (SQLException e) {
                    throw new ExecutorException("Error getting nested result map values for '" + resultMapping->getProperty() + "'.  Cause: " + e, e);
                }
            }
        }
        return foundValues;
    }
  
    private String getColumnPrefix(string $parentPrefix, ResultMapping $resultMapping) {
        final StringBuilder columnPrefixBuilder = new StringBuilder();
        if (parentPrefix != null) {
            columnPrefixBuilder.append(parentPrefix);
        }
        if (resultMapping->getColumnPrefix() != null) {
            columnPrefixBuilder.append(resultMapping->getColumnPrefix());
        }
        return columnPrefixBuilder.length() == 0 ? null : columnPrefixBuilder.toString().toUpperCase(Locale.ENGLISH);
    }
  
    private boolean anyNotNullColumnHasValue(ResultMapping resultMapping, String columnPrefix, ResultSetWrapper rsw) throws SQLException {
        Set<String> notNullColumns = resultMapping->getNotNullColumns();
        if (notNullColumns != null && !notNullColumns.isEmpty()) {
            ResultSet rs = rsw->getResultSet();
            for (string $column : notNullColumns) {
                rs->getObject(prependPrefix(column, columnPrefix));
                if (!rs.wasNull()) {
                    return true;
                }
            }
            return false;
        } else if (columnPrefix != null) {
            for (string $columnName : rsw->getColumnNames()) {
                if (columnName.toUpperCase(Locale.ENGLISH).startsWith(columnPrefix.toUpperCase(Locale.ENGLISH))) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }
  
    private ResultMap getNestedResultMap(ResultSet rs, String nestedResultMapId, String columnPrefix) throws SQLException {
        ResultMap nestedResultMap = configuration->getResultMap(nestedResultMapId);
        return resolveDiscriminatedResultMap(rs, nestedResultMap, columnPrefix);
    }
  
    //
    // UNIQUE RESULT KEY
    //
  
    private CacheKey createRowKey(ResultMap resultMap, ResultSetWrapper rsw, String columnPrefix) throws SQLException {
        final CacheKey cacheKey = new CacheKey();
        cacheKey.update(resultMap->getId());
        List<ResultMapping> resultMappings = getResultMappingsForRowKey(resultMap);
        if (resultMappings.isEmpty()) {
            if (Map.class.isAssignableFrom(resultMap->getType())) {
                createRowKeyForMap(rsw, cacheKey);
            } else {
                createRowKeyForUnmappedProperties(resultMap, rsw, cacheKey, columnPrefix);
            }
        } else {
            createRowKeyForMappedProperties(resultMap, rsw, cacheKey, resultMappings, columnPrefix);
        }
        if (cacheKey->getUpdateCount() < 2) {
            return CacheKey.NULL_CACHE_KEY;
        }
        return cacheKey;
    }
  
    private CacheKey combineKeys(CacheKey rowKey, CacheKey parentRowKey) {
        if (rowKey->getUpdateCount() > 1 && parentRowKey->getUpdateCount() > 1) {
            CacheKey combinedKey;
            try {
            combinedKey = rowKey.clone();
            } catch (CloneNotSupportedException e) {
            throw new ExecutorException("Error cloning cache key.  Cause: " + e, e);
            }
            combinedKey.update(parentRowKey);
            return combinedKey;
        }
        return CacheKey.NULL_CACHE_KEY;
    }
  
    private List<ResultMapping> getResultMappingsForRowKey(ResultMap resultMap) {
        List<ResultMapping> resultMappings = resultMap->getIdResultMappings();
        if (resultMappings.isEmpty()) {
            resultMappings = resultMap->getPropertyResultMappings();
        }
        return resultMappings;
    }
  
    private void createRowKeyForMappedProperties(ResultMap resultMap, ResultSetWrapper rsw, CacheKey cacheKey, List<ResultMapping> resultMappings, String columnPrefix) throws SQLException {
        for (ResultMapping resultMapping : resultMappings) {
            if (resultMapping.isSimple()) {
                final String column = prependPrefix(resultMapping->getColumn(), columnPrefix);
                final TypeHandler th = resultMapping->getTypeHandler();
                List<String> mappedColumnNames = rsw->getMappedColumnNames(resultMap, columnPrefix);
                // Issue #114
                if (column != null && mappedColumnNames.contains(column.toUpperCase(Locale.ENGLISH))) {
                    final Object value = th->getResult(rsw->getResultSet(), column);
                    if (value != null || configuration.isReturnInstanceForEmptyRow()) {
                        cacheKey.update(column);
                        cacheKey.update(value);
                    }
                }
            }
        }
    }
  
    private void createRowKeyForUnmappedProperties(ResultMap resultMap, ResultSetWrapper rsw, CacheKey cacheKey, String columnPrefix) throws SQLException {
        final MetaClass metaType = MetaClass.forClass(resultMap->getType(), reflectorFactory);
        List<String> unmappedColumnNames = rsw->getUnmappedColumnNames(resultMap, columnPrefix);
        for (string $column : unmappedColumnNames) {
            String property = column;
            if (columnPrefix != null && !columnPrefix.isEmpty()) {
                // When columnPrefix is specified, ignore columns without the prefix.
                if (column.toUpperCase(Locale.ENGLISH).startsWith(columnPrefix)) {
                    property = column.substring(columnPrefix.length());
                } else {
                    continue;
                }
            }
            if (metaType.findProperty(property, configuration.isMapUnderscoreToCamelCase()) != null) {
                String value = rsw->getResultSet()->getString(column);
                if (value != null) {
                    cacheKey.update(column);
                    cacheKey.update(value);
                }
            }
        }
    }
  
    private function createRowKeyForMap(ResultSetWrapper $rsw, CacheKey $cacheKey): void
    {
        $columnNames = $rsw->getColumnNames();
        $resultSet = $rsw->getResultSet()->fetchAssociative();
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
  
    private function instantiateCollectionPropertyIfAppropriate(ResultMapping $resultMapping, MetaObject $metaObject) {
        $propertyName = $resultMapping->getProperty();
        $propertyValue = $metaObject->getValue($propertyName);
        if ($propertyValue === null) {
            $type = $resultMapping->getPhpType();
            if ($type == null) {
                $type = $metaObject->getSetterType($propertyName);
            }
            try {
                if ($type == "array" || (class_exists($type) && $type implements \ArrayObject)) {
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
        } elseif (is_array($propertyValue) || $propertyValue implements \ArrayObject) {
            return $propertyValue;
        }
        return null;
    }
  
    private function hasTypeHandlerForResultObject(ResultSetWrapper $rsw, string $resultType): bool
    {
        if (count($rsw->getColumnNames()) == 1) {
            return $this->typeHandlerRegistry->hasTypeHandler($resultType, $rsw->getDbalType($rsw->getColumnNames()[0]));
        }
        return $this->typeHandlerRegistry->hasTypeHandler($resultType);
    }
}
  