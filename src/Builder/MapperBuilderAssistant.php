<?php

namespace MyBatis\Builder;

use MyBatis\Cache\CacheInterface;
use MyBatis\Cache\Decorators\LruCache;
use MyBatis\Cache\Impl\PerpetualCache;
use MyBatis\Executor\Keygen\KeyGeneratorInterface;
use MyBatis\Mapping\{
    CacheBuilder,
    Discriminator,
    DiscriminatorBuilder,
    MappedStatement,
    MappedStatementBuilder,
    ParameterMap,
    ParameterMapBuilder,
    ParameterMapping,
    ParameterMappingBuilder,
    ParameterMode,
    ResultFlag,
    ResultMap,
    ResultMapBuilder,
    ResultMapping,
    ResultMappingBuilder,
    ResultSetType,
    SqlCommandType,
    SqlSourceInterface,
    StatementType
};
use MyBatis\Scripting\LanguageDriverInterface;
use MyBatis\Session\Configuration;
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface
};
use Util\Reflection\MetaClass;

class MapperBuilderAssistant extends BaseBuilder
{
    private $currentNamespace;
    private $resource;
    private $currentCache;
    private $unresolvedCacheRef = false;

    public function __construct(Configuration $configuration, string $resource)
    {
        parent::__construct($configuration);
        $this->resource = $resource;
    }

    public function getCurrentNamespace(): string
    {
        return $this->currentNamespace;
    }

    public function setCurrentNamespace(?string $currentNamespace): void
    {
        if ($currentNamespace === null) {
            throw new BuilderException("The mapper element requires a namespace attribute to be specified.");
        }

        if (!empty($this->currentNamespace) && $this->currentNamespace != $currentNamespace) {
            throw new BuilderException(
                "Wrong namespace. Expected '"
                . $this->currentNamespace . "' but found '" . $currentNamespace . "'."
            );
        }

        $this->currentNamespace = $currentNamespace;
    }

    public function applyCurrentNamespace(?string $base, bool $isReference): ?string
    {
        if ($base === null) {
            return null;
        }
        if ($isReference) {
            // is it qualified with any namespace yet?
            if (strpos($base, ".") !== false) {
                return $base;
            }
        } else {
            // is it qualified with this namespace yet?
            if (strpos($base, $this->currentNamespace . ".") === 0) {
                return $base;
            }
            if (strpos($base, ".") !== false) {
                throw new BuilderException("Dots are not allowed in element names, please remove it from " . $base);
            }
        }
        return $this->currentNamespace . "." . $base;
    }

    public function useCacheRef(?string $namespace): CacheInterface
    {
        if ($namespace === null) {
            throw new BuilderException("cache-ref element requires a namespace attribute.");
        }
        try {
            $this->unresolvedCacheRef = true;
            $cache = $this->configuration->getCache($namespace);
            if ($cache === null) {
                throw new IncompleteElementException("No cache for namespace '" . $namespace . "' could be found.");
            }
            $this->currentCache = $cache;
            $this->unresolvedCacheRef = false;
            return $cache;
        } catch (\Exception $e) {
            throw new IncompleteElementException("No cache for namespace '" . $namespace . "' could be found.");
        }
    }

    public function useNewCache(
        string $typeClass,
        string $evictionClass,
        ?int $flushInterval,
        int $size,
        bool $readWrite,
        bool $blocking,
        array $props
    ): CacheInterface {
        $cache = (new CacheBuilder($this->currentNamespace))
            ->implementation($this->valueOrDefault($typeClass, PerpetualCache::class))
            ->addDecorator($this->valueOrDefault($evictionClass, LruCache::class))
            ->clearInterval($flushInterval)
            ->size($size)
            ->readWrite($readWrite)
            //->blocking($blocking)
            ->properties($props)
            ->build();
        $this->configuration->addCache($cache);
        $this->currentCache = $cache;
        return $cache;
    }

    public function addParameterMap(string $id, string $parameterClass, array $parameterMappings): ParameterMap
    {
        $id = $this->applyCurrentNamespace($id, false);
        $parameterMap = (new ParameterMapBuilder($this->configuration, $id, $parameterClass, $parameterMappings))->build();
        $this->configuration->addParameterMap($parameterMap);
        return $parameterMap;
    }

    public function buildParameterMapping(
        string $parameterType,
        string $property,
        ?string $phpType,
        ?DbalType $dbalType,
        ?string $resultMap,
        string $parameterMode,
        ?string $typeHandler,
        int $numericScale
    ): ParameterMapping {
        $resultMap = $this->applyCurrentNamespace($resultMap, true);

        // Class parameterType = parameterMapBuilder.type();
        $phpTypeClass = $this->resolveParameterPhpType($parameterType, $property, $phpType, $dbalType);
        $typeHandlerInstance = $this->resolveTypeHandler($phpTypeClass, $typeHandler);

        return (new ParameterMappingBuilder($this->configuration, $property, $phpTypeClass))
            ->dbalType($dbalType)
            ->resultMapId($resultMap)
            ->mode($parameterMode)
            ->numericScale($numericScale)
            ->typeHandler($typeHandlerInstance)
            ->build();
    }

    public function addResultMap(
        string $id,
        ?string $type,
        ?string $extend,
        ?Discriminator $discriminator,
        array $resultMappings,
        ?bool $autoMapping
    ): ResultMap {
        $id = $this->applyCurrentNamespace($id, false);
        $extend = $this->applyCurrentNamespace($extend, true);

        if (!empty($extend)) {
            if (!$this->configuration->hasResultMap($extend)) {
                throw new IncompleteElementException("Could not find a parent resultmap with id '" . $extend . "'");
            }
            $resultMap = $this->configuration->getResultMap($extend);
            $extendedResultMappings = $resultMap->getResultMappings();
            foreach ($extendedResultMappings as $key => $outer) {
                foreach ($resultMappings as $inner) {
                    unset($extendedResultMappings[$key]);
                }
            }
            //extendedResultMappings.removeAll(resultMappings);
            // Remove parent constructor if this resultMap declares a constructor.
            $declaresConstructor = false;
            foreach ($resultMappings as $resultMapping) {
                if (in_array(ResultFlag::CONSTRUCTOR, $resultMapping->getFlags())) {
                    $declaresConstructor = true;
                    break;
                }
            }
            if ($declaresConstructor) {
                //extendedResultMappings.removeIf(resultMapping -> resultMapping.getFlags().contains(ResultFlag.CONSTRUCTOR));
                foreach ($extendedResultMappings as $key => $resultMapping) {
                    if (in_array(ResultFlag::CONSTRUCTOR, $resultMapping->getFlags())) {
                        unset($extendedResultMappings[$key]);
                    }
                }
            }
            $resultMappings = array_merge($resultMappings, $extendedResultMappings);
        }
        $resultMap = (new ResultMapBuilder($this->configuration, $id, $type, $resultMappings, $autoMapping))
            ->discriminator($discriminator)
            ->build();
        $this->configuration->addResultMap($resultMap);
        return $resultMap;
    }

    public function buildDiscriminator(
        string $resultType,
        string $column,
        string $phpType,
        ?DbalType $dbalType,
        ?string $typeHandler,
        array $discriminatorMap
    ): Discriminator {
        $resultMapping = $this->buildResultMapping(
            $resultType,
            null,
            $column,
            $phpType,
            $dbalType,
            null,
            null,
            null,
            null,
            $typeHandler,
            [],
            null,
            null,
            false
        );
        $namespaceDiscriminatorMap = [];
        foreach ($discriminatorMap as $key => $value) {
            $resultMap = $this->applyCurrentNamespace($value, true);
            $namespaceDiscriminatorMap[$key] = $value;
        }
        return (new DiscriminatorBuilder($this->configuration, $resultMapping, $namespaceDiscriminatorMap))->build();
    }

    public function addMappedStatement(
        string $id,
        SqlSourceInterface $sqlSource,
        string $statementType,
        string $sqlCommandType,
        ?int $fetchSize,
        ?int $timeout,
        ?string $parameterMap,
        ?string $parameterType,
        ?string $resultMap,
        ?string $resultType,
        ?ResultSetType $resultSetType,
        bool $flushCache,
        bool $useCache,
        bool $resultOrdered,
        KeyGeneratorInterface $keyGenerator,
        ?string $keyProperty,
        ?string $keyColumn,
        ?string $databaseId,
        LanguageDriverInterface $lang,
        ?string $resultSets = null
    ): MappedStatement {
        if ($this->unresolvedCacheRef) {
            throw new IncompleteElementException("Cache-ref not yet resolved");
        }

        $id = $this->applyCurrentNamespace($id, false);
        $isSelect = $sqlCommandType == SqlCommandType::SELECT;
        $statementBuilder = (new MappedStatementBuilder($this->configuration, $id, $sqlSource, $sqlCommandType))
            ->resource($this->resource)
            ->fetchSize($fetchSize)
            ->timeout($timeout)
            ->statementType($statementType)
            ->keyGenerator($keyGenerator)
            ->keyProperty($keyProperty)
            ->keyColumn($keyColumn)
            ->databaseId($databaseId)
            ->lang($lang)
            ->resultOrdered($resultOrdered)
            ->resultSets($resultSets)
            ->resultMaps($this->getStatementResultMaps($resultMap, $resultType, $id))
            ->resultSetType($resultSetType)
            ->flushCacheRequired($this->valueOrDefault($flushCache, !$isSelect))
            ->useCache($this->valueOrDefault($useCache, $isSelect))
            ->cache($this->currentCache);

        $statementParameterMap = $this->getStatementParameterMap($parameterMap, $parameterType, $id);
        if ($statementParameterMap !== null) {
            $statementBuilder->parameterMap($statementParameterMap);
        }

        $statement = $statementBuilder->build();
        $this->configuration->addMappedStatement($statement);
        return $statement;
    }

    private function valueOrDefault($value, $defaultValue)
    {
        return $value ?? $defaultValue;
    }

    private function getStatementParameterMap(
        ?string $parameterMapName,
        ?string $parameterTypeClass,
        string $statementId
    ): ?ParameterMap {
        $parameterMapName = $this->applyCurrentNamespace($parameterMapName, true);
        $parameterMap = null;
        if (!empty($parameterMapName)) {
            try {
                $parameterMap = $this->configuration->getParameterMap($parameterMapName);
            } catch (\Exception $e) {
                throw new IncompleteElementException("Could not find parameter map " . $parameterMapName);
            }
        } elseif ($parameterTypeClass !== null) {
            $parameterMappings = [];
            $parameterMap = (new ParameterMapBuilder(
                $this->configuration,
                $statementId . "-Inline",
                $parameterTypeClass,
                $parameterMappings
            ))->build();
        }
        return $parameterMap;
    }

    private function getStatementResultMaps(
        ?string $resultMap,
        ?string $resultType,
        string $statementId
    ): array {
        $resultMap = $this->applyCurrentNamespace($resultMap, true);

        $resultMaps = [];
        if ($resultMap !== null) {
            $resultMapNames = explode(',', $resultMap);
            foreach ($resultMapNames as $resultMapName) {
                try {
                    $resultMaps[] = $this->configuration->getResultMap(trim($resultMapName));
                } catch (\Exception $e) {
                    throw new IncompleteElementException("Could not find result map '" . $resultMapName . "' referenced from '" . $statementId . "'");
                }
            }
        } elseif (!empty($resultType)) {
            $inlineResultMap = (new ResultMapBuilder(
                $this->configuration,
                $statementId . "-Inline",
                $resultType,
                [],
                null
            ))->build();
            $resultMaps[] = $inlineResultMap;
        }
        return $resultMaps;
    }

    public function buildResultMapping(
        ?string $resultType,
        ?string $property,
        ?string $column,
        ?string $phpType,
        ?DbalType $dbalType,
        ?string $nestedSelect,
        ?string $nestedResultMap,
        ?string $notNullColumn,
        ?string $columnPrefix,
        ?string $typeHandler,
        ?array $flags = [],
        ?string $resultSet = null,
        ?string $foreignColumn = null,
        ?bool $lazy = null
    ): ResultMapping {
        if ($lazy === null) {
            $lazy = $this->configuration->isLazyLoadingEnabled();
        }
        $phpTypeClass = $this->resolveResultPhpType($resultType, $property, $phpType);
        $typeHandlerInstance = $this->resolveTypeHandler($phpTypeClass, $typeHandler);
        $composites = [];
        if (!(empty($nestedSelect) && empty($foreignColumn))) {
            $composites = $this->parseCompositeColumnName($column);
        }
        return (new ResultMappingBuilder($this->configuration, $property, $column, $phpTypeClass))
            ->dbalType($dbalType)
            ->nestedQueryId($this->applyCurrentNamespace($nestedSelect, true))
            ->nestedResultMapId($this->applyCurrentNamespace($nestedResultMap, true))
            ->resultSet($resultSet)
            ->typeHandler($typeHandlerInstance)
            ->flags($flags)
            ->composites($composites)
            ->notNullColumns($this->parseMultipleColumnNames($notNullColumn))
            ->columnPrefix($columnPrefix)
            ->foreignColumn($foreignColumn)
            ->lazy($lazy)
            ->build();
    }

    private function parseMultipleColumnNames(?string $columnName): array
    {
        $columns = [];
        if ($columnName !== null) {
            if (strpos($columnName, ',') !== false) {
                $tokens = preg_split("/[{},\s]+/", $columnName);
                while (!empty($tokens)) {
                    $column = array_shift($tokens);
                    if (!empty($column)) {
                        $columns[] = $column;
                    }
                }
            } else {
                $columns[] = $columnName;
            }
        }
        return $columns;
    }

    private function parseCompositeColumnName(?string $columnName): array
    {
        $composites = [];
        if ($columnName !== null && (strpos($columnName, '=') !== false || strpos($columnName, ',') !== false)) {
            $tokens = preg_split("/[{}=,\s]+/", $columnName);
            while (!empty($tokens)) {
                $property = array_shift($tokens);
                $column = array_shift($tokens);
                if (!empty($property) && !empty($column)) {
                    $complexResultMapping = (new ResultMappingBuilder(
                        $this->configuration,
                        $property,
                        $column,
                        $this->configuration->getTypeHandlerRegistry()->getUnknownTypeHandler()
                    ))->build();
                    $composites[] = $complexResultMapping;
                }
            }
        }
        return $composites;
    }

    private function resolveResultPhpType(?string $resultType, ?string $property, ?string $phpType): string
    {
        if ($phpType === null && $property !== null) {
            if (class_exists($resultType)) {
                $metaResultType = new MetaClass($resultType, $this->configuration->getReflectorFactory());
                $phpType = $metaResultType->getSetterType($property);
            }
        }
        if ($phpType === null) {
            $phpType = 'object';
        }
        return $phpType;
    }

    private function resolveParameterPhpType(string $resultType, string $property, ?string $phpType, ?DbalType $dbalType): string
    {
        if ($phpType === null) {
            if (class_exists($resultType)) {
                $metaResultType = new MetaClass($resultType);
                $phpType = $metaResultType->getGetterType($property);
            } elseif ($resultType === 'array') {
                $phpType = 'object';
            }
        }
        if ($phpType === null) {
            $phpType = 'object';
        }
        return $phpType;
    }
}
