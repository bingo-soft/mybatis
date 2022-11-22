<?php

namespace MyBatis\Session;

use MyBatis\Binding\MapperRegistry;
use MyBatis\Builder\{
    CacheRefResolver,
    IncompleteElementException,
    ResultMapResolver
};
use MyBatis\Builder\Annotation\MethodResolver;
use MyBatis\Builder\Xml\XMLStatementBuilder;
use MyBatis\Cache\CacheInterface;
use MyBatis\Cache\Decorators\{
    FifoCache,
    LruCache
};
use MyBatis\Cache\Impl\PerpetualCache;
use MyBatis\DataSource\Unpooled\UnpooledDataSourceFactory;
use MyBatis\Executor\{
    CachingExecutor,
    ExecutorInterface,
    ReuseExecutor,
    SimpleExecutor
};
use MyBatis\Executor\Keygen\KeyGeneratorInterface;
use MyBatis\Executor\Loader\ProxyFactoryImpl;
use MyBatis\Executor\Parameter\ParameterHandlerInterface;
use MyBatis\Executor\ResultSet\{
    DefaultResultSetHandler,
    ResultSetHandlerInterface
};
use MyBatis\Executor\Statement\{
    RoutingStatementHandler,
    StatementHandlerInterface
};
use MyBatis\Mapping\{
    BoundSql,
    Environment,
    MappedStatement,
    ParameterMap,
    ResultMap,
    ResultSetType,
    VendorDatabaseIdProvider
};
use MyBatis\Parsing\XNode;
use MyBatis\Plugin\{
    Interceptor,
    InterceptorChain
};
use MyBatis\Scripting\{
    LanguageDriverInterface,
    LanguageDriverRegistry
};
use MyBatis\Scripting\Defaults\RawLanguageDriver;
use MyBatis\Scripting\XmlTags\XMLLanguageDriver;
use MyBatis\Transaction\TransactionInterface;
use MyBatis\Transaction\Dbal\DbalTransactionFactory;
use MyBatis\Transaction\Managed\ManagedTransactionFactory;
use MyBatis\Type\{
    DbalType,
    TypeAliasRegistry,
    TypeHandlerInterface,
    TypeHandlerRegistry
};
use Util\Reflection\{
    DefaultReflectorFactory,
    ReflectorFactoryInterface,
    MetaObject
};

class Configuration
{
    protected $environment;
    protected $safeRowBoundsEnabled = false;
    protected $safeResultHandlerEnabled = true;
    protected $mapUnderscoreToCamelCase = false;
    protected $aggressiveLazyLoading = false;
    protected $multipleResultSetsEnabled = true;
    protected $useGeneratedKeys = false;
    protected $useColumnLabel = true;
    protected $cacheEnabled = true;
    protected $callSettersOnNulls = false;
    protected $useActualParamName = true;
    protected $returnInstanceForEmptyRow = false;
    protected $shrinkWhitespacesInSql = false;
    protected $nullableOnForEach = false;
    protected $argNameBasedConstructorAutoMapping = false;
    /*protected $logPrefix;
    protected $logImpl;
    protected $vfsImpl;*/
    protected $defaultSqlProviderType;
    protected $localCacheScope = LocalCacheScope::SESSION;
    protected $dbalTypeForNull;
    protected $lazyLoadTriggerMethods = ["equals", "clone", "hashCode", "toString"];
    protected $defaultStatementTimeout;
    protected $defaultFetchSize;
    protected $defaultResultSetType;
    protected $defaultExecutorType = ExecutorType::SIMPLE;
    protected $autoMappingBehavior = AutoMappingBehavior::PARTIAL;
    protected $autoMappingUnknownColumnBehavior;

    protected $variables = [];
    protected $reflectorFactory;

    protected $lazyLoadingEnabled = false;
    protected $proxyFactory;

    protected $databaseId;

    protected $configurationFactory;

    protected $mapperRegistry;
    protected $interceptorChain;
    protected $typeHandlerRegistry;
    protected $typeAliasRegistry;
    protected $languageRegistry;

    protected $mappedStatements;
    protected $caches;
    protected $resultMaps;
    protected $parameterMaps;
    protected $keyGenerators;

    protected $loadedResources = [];
    protected $sqlFragments;// = new StrictMap<>("XML fragments parsed from previous mappers");

    protected $incompleteStatements = [];
    protected $incompleteCacheRefs = [];
    protected $incompleteResultMaps = [];
    protected $incompleteMethods = [];

    /*
     * A map holds cache-ref relationship. The key is the namespace that
     * references a cache bound to another namespace and the value is the
     * namespace which the actual cache is bound to.
     */
    protected $cacheRefMap = [];

    public function __construct(?Environment $environment = null)
    {
        $this->environment = $environment;

        $this->dbalTypeForNull = DbalType::forCode('OTHER');
        $this->autoMappingUnknownColumnBehavior = AutoMappingUnknownColumnBehavior::none();
        $this->proxyFactory = new ProxyFactoryImpl();
        $this->reflectorFactory = new DefaultReflectorFactory();
        $this->mapperRegistry = new MapperRegistry($this);
        $this->interceptorChain = new InterceptorChain();
        $this->typeHandlerRegistry = new TypeHandlerRegistry($this);
        $this->typeAliasRegistry = new TypeAliasRegistry();
        $this->languageRegistry = new LanguageDriverRegistry();
        $this->mappedStatements = new StrictMap("Mapped Statements collection");
        $this->caches = new StrictMap("Caches collection");
        $this->resultMaps = new StrictMap("Result Maps collection");
        $this->parameterMaps = new StrictMap("Parameter Maps collection");
        $this->keyGenerators = new StrictMap("Key Generators collection");
        $this->sqlFragments = new StrictMap("XML fragments parsed from previous mappers");

        $this->typeAliasRegistry->registerAlias("DBAL", DbalTransactionFactory::class);
        $this->typeAliasRegistry->registerAlias("MANAGED", ManagedTransactionFactory::class);

        $this->typeAliasRegistry->registerAlias("UNPOOLED", UnpooledDataSourceFactory::class);

        $this->typeAliasRegistry->registerAlias("PERPETUAL", PerpetualCache::class);
        $this->typeAliasRegistry->registerAlias("FIFO", FifoCache::class);
        $this->typeAliasRegistry->registerAlias("LRU", LruCache::class);

        $this->typeAliasRegistry->registerAlias("DB_VENDOR", VendorDatabaseIdProvider::class);

        $this->typeAliasRegistry->registerAlias("XML", XMLLanguageDriver::class);
        $this->typeAliasRegistry->registerAlias("RAW", RawLanguageDriver::class);

        $this->typeAliasRegistry->registerAlias("PROXY", ProxyFactoryImpl::class);

        $this->languageRegistry->setDefaultDriverClass(XMLLanguageDriver::class);
        $this->languageRegistry->register(RawLanguageDriver::class);
    }
    /**
     * Gets an applying type when omit a type on sql provider annotation
     *
     * @return the default type for sql provider annotation
     */
    public function getDefaultSqlProviderType(): ?string
    {
        return $this->defaultSqlProviderType;
    }

    /**
     * Sets an applying type when omit a type on sql provider annotation
     *
     * @param defaultSqlProviderType
     *          the default type for sql provider annotation
     */
    public function setDefaultSqlProviderType(?string $defaultSqlProviderType): void
    {
        $this->defaultSqlProviderType = $defaultSqlProviderType;
    }

    public function isCallSettersOnNulls(): bool
    {
        return $this->callSettersOnNulls;
    }

    public function setCallSettersOnNulls(bool $callSettersOnNulls): void
    {
        $this->callSettersOnNulls = $callSettersOnNulls;
    }

    public function isUseActualParamName(): bool
    {
        return $this->useActualParamName;
    }

    public function setUseActualParamName(bool $useActualParamName): void
    {
        $this->useActualParamName = $useActualParamName;
    }

    public function isReturnInstanceForEmptyRow(): bool
    {
        return $this->returnInstanceForEmptyRow;
    }

    public function setReturnInstanceForEmptyRow(bool $returnEmptyInstance): void
    {
        $this->returnInstanceForEmptyRow = $returnEmptyInstance;
    }

    public function isShrinkWhitespacesInSql(): ?bool
    {
        return $this->shrinkWhitespacesInSql;
    }

    public function setShrinkWhitespacesInSql(bool $shrinkWhitespacesInSql): void
    {
        $this->shrinkWhitespacesInSql = $shrinkWhitespacesInSql;
    }

    public function setNullableOnForEach(bool $nullableOnForEach): void
    {
        $this->nullableOnForEach = $nullableOnForEach;
    }

    public function isNullableOnForEach(): bool
    {
        return $this->nullableOnForEach;
    }

    public function isArgNameBasedConstructorAutoMapping(): bool
    {
        return $this->argNameBasedConstructorAutoMapping;
    }

    public function setArgNameBasedConstructorAutoMapping(bool $argNameBasedConstructorAutoMapping): void
    {
        $this->argNameBasedConstructorAutoMapping = $argNameBasedConstructorAutoMapping;
    }

    public function getDatabaseId(): ?string
    {
        return $this->databaseId;
    }

    public function setDatabaseId(string $databaseId): void
    {
        $this->databaseId = $databaseId;
    }

    public function getConfigurationFactory(): string
    {
        return $this->configurationFactory;
    }

    public function setConfigurationFactory(string $configurationFactory): void
    {
        $this->configurationFactory = $configurationFactory;
    }

    public function isSafeResultHandlerEnabled(): bool
    {
        return $this->safeResultHandlerEnabled;
    }

    public function setSafeResultHandlerEnabled(bool $safeResultHandlerEnabled): void
    {
        $this->safeResultHandlerEnabled = $safeResultHandlerEnabled;
    }

    public function isSafeRowBoundsEnabled(): bool
    {
        return $this->safeRowBoundsEnabled;
    }

    public function setSafeRowBoundsEnabled(bool $safeRowBoundsEnabled): void
    {
        $this->safeRowBoundsEnabled = $safeRowBoundsEnabled;
    }

    public function isMapUnderscoreToCamelCase(): bool
    {
        return $this->mapUnderscoreToCamelCase;
    }

    public function setMapUnderscoreToCamelCase(bool $mapUnderscoreToCamelCase): void
    {
        $this->mapUnderscoreToCamelCase = $mapUnderscoreToCamelCase;
    }

    public function addLoadedResource(string $resource): void
    {
        $this->loadedResources[] = $resource;
    }

    public function isResourceLoaded(string $resource): bool
    {
        return in_array($resource, $this->loadedResources);
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function setEnvironment(Environment $environment): void
    {
        $this->environment = $environment;
    }

    public function getAutoMappingBehavior(): string
    {
        return $this->autoMappingBehavior;
    }

    public function setAutoMappingBehavior(string $autoMappingBehavior): void
    {
        $this->autoMappingBehavior = $autoMappingBehavior;
    }

    public function getAutoMappingUnknownColumnBehavior(): AutoMappingUnknownColumnBehavior
    {
        return $this->autoMappingUnknownColumnBehavior;
    }

    public function setAutoMappingUnknownColumnBehavior(AutoMappingUnknownColumnBehavior $autoMappingUnknownColumnBehavior): void
    {
        $this->autoMappingUnknownColumnBehavior = $autoMappingUnknownColumnBehavior;
    }

    public function isLazyLoadingEnabled(): bool
    {
        return $this->lazyLoadingEnabled;
    }

    public function setLazyLoadingEnabled(bool $lazyLoadingEnabled): void
    {
        $this->lazyLoadingEnabled = $lazyLoadingEnabled;
    }

    public function getProxyFactory(): ProxyFactoryImpl
    {
        return $this->proxyFactory;
    }

    public function setProxyFactory(?ProxyFactoryImpl $proxyFactory): void
    {
        if ($proxyFactory == null) {
            $proxyFactory = new ProxyFactoryImpl();
        }
        $this->proxyFactory = $proxyFactory;
    }

    public function isAggressiveLazyLoading(): bool
    {
        return $this->aggressiveLazyLoading;
    }

    public function setAggressiveLazyLoading(bool $aggressiveLazyLoading): void
    {
        $this->aggressiveLazyLoading = $aggressiveLazyLoading;
    }

    public function isMultipleResultSetsEnabled(): bool
    {
        return $this->multipleResultSetsEnabled;
    }

    public function setMultipleResultSetsEnabled(bool $multipleResultSetsEnabled): void
    {
        $this->multipleResultSetsEnabled = $multipleResultSetsEnabled;
    }

    public function getLazyLoadTriggerMethods(): array
    {
        return $this->lazyLoadTriggerMethods;
    }

    public function setLazyLoadTriggerMethods(array $lazyLoadTriggerMethods): void
    {
        $this->lazyLoadTriggerMethods = $lazyLoadTriggerMethods;
    }

    public function isUseGeneratedKeys(): ?bool
    {
        return $this->useGeneratedKeys;
    }

    public function setUseGeneratedKeys(bool $useGeneratedKeys): void
    {
        $this->useGeneratedKeys = $useGeneratedKeys;
    }

    public function getDefaultExecutorType(): string
    {
        return $this->defaultExecutorType;
    }

    public function setDefaultExecutorType(string $defaultExecutorType): void
    {
        $this->defaultExecutorType = $defaultExecutorType;
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function setCacheEnabled(bool $cacheEnabled): void
    {
        $this->cacheEnabled = $cacheEnabled;
    }

    public function getDefaultStatementTimeout(): ?int
    {
        return $this->defaultStatementTimeout;
    }

    public function setDefaultStatementTimeout(?int $defaultStatementTimeout): void
    {
        $this->defaultStatementTimeout = $defaultStatementTimeout;
    }

    public function getDefaultFetchSize(): ?int
    {
        return $this->defaultFetchSize;
    }

    public function setDefaultFetchSize(?int $defaultFetchSize): void
    {
        $this->defaultFetchSize = $defaultFetchSize;
    }

    public function getDefaultResultSetType(): ?ResultSetType
    {
        return $this->defaultResultSetType;
    }

    public function setDefaultResultSetType(?ResultSetType $defaultResultSetType): void
    {
        $this->defaultResultSetType = $defaultResultSetType;
    }

    public function isUseColumnLabel(): bool
    {
        return $this->useColumnLabel;
    }

    public function setUseColumnLabel(bool $useColumnLabel): void
    {
        $this->useColumnLabel = $useColumnLabel;
    }

    public function getLocalCacheScope(): string
    {
        return $this->localCacheScope;
    }

    public function setLocalCacheScope(string $localCacheScope): void
    {
        $this->localCacheScope = $localCacheScope;
    }

    public function getDbalTypeForNull(): DbalType
    {
        return $this->dbalTypeForNull;
    }

    public function setDbalTypeForNull(DbalType $dbalTypeForNull): void
    {
        $this->dbalTypeForNull = $dbalTypeForNull;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    public function getTypeHandlerRegistry(): TypeHandlerRegistry
    {
        return $this->typeHandlerRegistry;
    }

    public function setDefaultEnumTypeHandler(?string $typeHandler): void
    {
        if ($typeHandler !== null) {
            $this->getTypeHandlerRegistry()->setDefaultEnumTypeHandler($typeHandler);
        }
    }

    public function getTypeAliasRegistry(): TypeAliasRegistry
    {
        return $this->typeAliasRegistry;
    }

    public function getMapperRegistry(): MapperRegistry
    {
        return $this->mapperRegistry;
    }

    public function getReflectorFactory(): ReflectorFactoryInterface
    {
        return $this->reflectorFactory;
    }

    public function setReflectorFactory(ReflectorFactoryInterface $reflectorFactory): void
    {
        $this->reflectorFactory = $reflectorFactory;
    }

    public function getInterceptors(): array
    {
        return $this->interceptorChain->getInterceptors();
    }

    public function getLanguageRegistry(): LanguageDriverRegistry
    {
        return $this->languageRegistry;
    }

    public function setDefaultScriptingLanguage(?string $driver): void
    {
        if ($driver == null) {
            $driver = XMLLanguageDriver::class;
        }
        $this->getLanguageRegistry()->setDefaultDriverClass($driver);
    }

    public function getDefaultScriptingLanguageInstance(): LanguageDriverInterface
    {
        return $this->languageRegistry->getDefaultDriver();
    }

    public function getLanguageDriver(?string $langClass): LanguageDriverInterface
    {
        if ($langClass == null) {
            return $this->languageRegistry->getDefaultDriver();
        }
        $this->languageRegistry->register($langClass);
        return $this->languageRegistry->getDriver($langClass);
    }

    public function newMetaObject(&$object): MetaObject
    {
        return new MetaObject($object);
    }

    public function newParameterHandler(MappedStatement $mappedStatement, $parameterObject, BoundSql $boundSql): ParameterHandlerInterface
    {
        $parameterHandler = $mappedStatement->getLang()->createParameterHandler($mappedStatement, $parameterObject, $boundSql);
        $parameterHandler = $this->interceptorChain->pluginAll($parameterHandler);
        return $parameterHandler;
    }

    public function newResultSetHandler(ExecutorInterface $executor, MappedStatement $mappedStatement, ?RowBounds $rowBounds, ParameterHandlerInterface $parameterHandler, ?ResultHandlerInterface $resultHandler, BoundSql $boundSql): ResultSetHandlerInterface
    {
        $resultSetHandler = new DefaultResultSetHandler($executor, $mappedStatement, $parameterHandler, $resultHandler, $boundSql, $rowBounds);
        $resultSetHandler = $this->interceptorChain->pluginAll($resultSetHandler);
        return $resultSetHandler;
    }

    public function newStatementHandler(ExecutorInterface $executor, MappedStatement $mappedStatement, $parameterObject, ?RowBounds $rowBounds, ?ResultHandlerInterface $resultHandler, ?BoundSql $boundSql): StatementHandlerInterface
    {
        $statementHandler = new RoutingStatementHandler($executor, $mappedStatement, $parameterObject, $rowBounds, $resultHandler, $boundSql);
        $statementHandler = $this->interceptorChain->pluginAll($statementHandler);
        return $statementHandler;
    }

    public function newExecutor(TransactionInterface $transaction, ?string $executorType = null): ExecutorInterface
    {
        $executorType = $executorType === null ? $this->defaultExecutorType : $executorType;
        $executor = null;
        /*if (ExecutorType::BATCH == $executorType) {
            $executor = new BatchExecutor($this, $transaction);
        } else*/
        if (ExecutorType::REUSE == $executorType) {
            $executor = new ReuseExecutor($this, $transaction);
        } else {
            $executor = new SimpleExecutor($this, $transaction);
        }
        if ($this->cacheEnabled) {
            $executor = new CachingExecutor($executor);
        }
        $executor = $this->interceptorChain->pluginAll($executor);
        return $executor;
    }

    public function addKeyGenerator(string $id, KeyGeneratorInterface $keyGenerator): void
    {
        $this->keyGenerators->put($id, $keyGenerator);
    }

    public function getKeyGeneratorNames(): array
    {
        return array_keys($this->keyGenerators);
    }

    public function getKeyGenerators(): array
    {
        return array_values($this->keyGenerators);
    }

    public function getKeyGenerator(string $id): ?KeyGeneratorInterface
    {
        if (array_key_exists($id, $this->keyGenerators->getArrayCopy())) {
            return $this->keyGenerators[$id];
        }
        return null;
    }

    public function hasKeyGenerator(string $id): bool
    {
        return array_key_exists($id, $this->keyGenerators->getArrayCopy());
    }

    public function addCache(CacheInterface $cache): void
    {
        $this->caches->put($cache->getId(), $cache);
    }

    public function getCacheNames(): array
    {
        return array_keys($this->caches);
    }

    public function getCaches(): array
    {
        return array_values($this->caches);
    }

    public function getCache(string $id): ?CacheInterface
    {
        if (array_key_exists($id, $this->caches->getArrayCopy())) {
            return $this->caches[$id];
        }
        return null;
    }

    public function hasCache(string $id): bool
    {
        return array_key_exists($id, $this->caches->getArrayCopy());
    }

    public function addResultMap(ResultMap $rm): void
    {
        $this->resultMaps->put($rm->getId(), $rm);
        $this->checkLocallyForDiscriminatedNestedResultMaps($rm);
        $this->checkGloballyForDiscriminatedNestedResultMaps($rm);
    }

    public function getResultMapNames(): array
    {
        return array_keys($this->resultMaps);
    }

    public function getResultMaps(): array
    {
        return array_values($this->resultMaps);
    }

    public function getResultMap(string $id): ?ResultMap
    {
        if (array_key_exists($id, $this->resultMaps->getArrayCopy())) {
            return $this->resultMaps[$id];
        }
        return null;
    }

    public function hasResultMap(string $id): bool
    {
        return array_key_exists($id, $this->resultMaps->getArrayCopy());
    }

    public function addParameterMap(ParameterMap $pm): void
    {
        $this->parameterMaps->put($pm->getId(), $pm);
    }

    public function getParameterMapNames(): array
    {
        return array_keys($this->parameterMaps);
    }

    public function getParameterMaps(): array
    {
        return array_values($this->parameterMaps);
    }

    public function getParameterMap(string $id): ?ParameterMap
    {
        if (array_key_exists($id, $this->parameterMaps->getArrayCopy())) {
            return $this->parameterMaps[$id];
        }
        return null;
    }

    public function hasParameterMap(string $id): bool
    {
        return array_key_exists($id, $this->parameterMaps->getArrayCopy());
    }

    public function addMappedStatement(MappedStatement $ms): void
    {
        $this->mappedStatements->put($ms->getId(), $ms);
    }

    public function getMappedStatementNames(): array
    {
        $this->buildAllStatements();
        return array_keys($this->mappedStatements);
    }

    public function getMappedStatements(): array
    {
        $this->buildAllStatements();
        return array_values($this->mappedStatements);
    }

    public function getIncompleteStatements(): array
    {
        return $this->incompleteStatements;
    }

    public function addIncompleteStatement(XMLStatementBuilder $incompleteStatement): void
    {
        $this->incompleteStatements[] = $incompleteStatement;
    }

    public function getIncompleteCacheRefs(): array
    {
        return $this->incompleteCacheRefs;
    }

    public function addIncompleteCacheRef(CacheRefResolver $incompleteCacheRef): void
    {
        $this->incompleteCacheRefs[] = $incompleteCacheRef;
    }

    public function getIncompleteResultMaps(): array
    {
        return $this->incompleteResultMaps;
    }

    public function addIncompleteResultMap(ResultMapResolver $resultMapResolver): void
    {
        $this->incompleteResultMaps[] = $resultMapResolver;
    }

    public function addIncompleteMethod(MethodResolver $builder): void
    {
        $this->incompleteMethods[] = $builder;
    }

    public function &getIncompleteMethods(): array
    {
        return $this->incompleteMethods;
    }

    public function getMappedStatement(string $id, bool $validateIncompleteStatements = true): ?MappedStatement
    {
        if ($validateIncompleteStatements) {
            $this->buildAllStatements();
        }
        if (array_key_exists($id, $this->mappedStatements->getArrayCopy())) {
            return $this->mappedStatements[$id];
        }
        return null;
    }

    public function getSqlFragments(): array
    {
        return $this->sqlFragments->getArrayCopy();
    }

    public function addInterceptor(Interceptor $interceptor): void
    {
        $this->interceptorChain->addInterceptor($interceptor);
    }

    public function addMappers(string $packageName, ?string $superType = null): void
    {
        $this->mapperRegistry->addMappers($packageName, $superType);
    }

    public function addMapper(string $type): void
    {
        $this->mapperRegistry->addMapper($type);
    }

    public function getMapper(string $type, SqlSessionInterface $sqlSession)
    {
        return $this->mapperRegistry->getMapper($type, $sqlSession);
    }

    public function hasMapper(string $type): bool
    {
        return $this->mapperRegistry->hasMapper($type);
    }

    public function hasStatement(string $statementName, bool $validateIncompleteStatements = true): bool
    {
        if ($validateIncompleteStatements) {
            $this->buildAllStatements();
        }
        return array_key_exists($statementName, $this->mappedStatements->getArrayCopy());
    }

    public function addCacheRef(string $namespace, string $referencedNamespace): void
    {
        $this->cacheRefMap[$namespace] = $referencedNamespace;
    }

    /*
     * Parses all the unprocessed statement nodes in the cache. It is recommended
     * to call this method once all the mappers are added as it provides fail-fast
     * statement validation.
     */
    protected function buildAllStatements(): void
    {
        $this->parsePendingResultMaps();
        if (!empty($this->incompleteCacheRefs)) {
            foreach ($this->incompleteCacheRefs as $key => $x) {
                if ($x->resolveCacheRef() !== null) {
                    unset($this->incompleteCacheRefs[$key]);
                }
            }
        }
        if (!empty($this->incompleteStatements)) {
            foreach ($this->incompleteStatements as $key => $x) {
                $x->parseStatementNode();
                unset($this->incompleteStatements[$key]);
            }
        }
        if (!empty($this->incompleteMethods)) {
            foreach ($this->incompleteMethods as $key => $x) {
                $x->resolve();
                unset($this->incompleteMethods[$key]);
            }
        }
    }

    private function parsePendingResultMaps(): void
    {
        if (empty($this->incompleteResultMaps)) {
            return;
        }
        $resolved = null;
        $ex = null;
        do {
            $resolved = false;
            foreach ($this->incompleteResultMaps as $key => $resolver) {
                try {
                    $resolver->resolve();
                    unset($this->incompleteResultMaps[$key]);
                    $resolved = true;
                } catch (\Exception $e) {
                    $ex = $e;
                }
            }
        } while ($resolved);
        if (!empty($this->incompleteResultMaps) && $ex !== null) {
            // At least one result map is unresolvable.
            throw $ex;
        }
    }

    /**
     * Extracts namespace from fully qualified statement id.
     *
     * @param statementId
     *          the statement id
     * @return namespace or null when id does not contain period.
     */
    protected function extractNamespace(string $statementId): ?string
    {
        $lastPeriod = strpos($statementId, '.');
        return $lastPeriod > 0 ? substr($statementId, 0, $lastPeriod) : null;
    }

    // Slow but a one time cost. A better solution is welcome.
    protected function checkGloballyForDiscriminatedNestedResultMaps(ResultMap $rm): void
    {
        if ($rm->hasNestedResultMaps()) {
            foreach ($this->resultMaps as $id => $value) {
                if ($value instanceof ResultMap) {
                    $entryResultMap = $value;
                    if (!$entryResultMap->hasNestedResultMaps() && $entryResultMap->getDiscriminator() !== null) {
                        $discriminatedResultMapNames = array_values($entryResultMap->getDiscriminator()->getDiscriminatorMap());
                        if (in_array($rm->getId(), $discriminatedResultMapNames)) {
                            $entryResultMap->forceNestedResultMaps();
                        }
                    }
                }
            }
        }
    }

    // Slow but a one time cost. A better solution is welcome.
    protected function checkLocallyForDiscriminatedNestedResultMaps(ResultMap $rm): void
    {
        if (!$rm->hasNestedResultMaps() && $rm->getDiscriminator() !== null) {
            foreach ($rm->getDiscriminator()->getDiscriminatorMap() as $key => $discriminatedResultMapName) {
                if ($this->hasResultMap($discriminatedResultMapName)) {
                    $discriminatedResultMap = $this->resultMaps[$discriminatedResultMapName];
                    if ($discriminatedResultMap->hasNestedResultMaps()) {
                        $rm->forceNestedResultMaps();
                        break;
                    }
                }
            }
        }
    }
}
