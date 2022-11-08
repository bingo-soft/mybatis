<?php

namespace MyBatis\Builder\Annotation;

use MyBatis\Annotations\{
    Arg,
    CacheNamespace,
    CacheNamespaceRef,
    Cases,
    Delete,
    DeleteProvider,
    Insert,
    InsertProvider,
    Lang,
    MapKey,
    Options,
    FlushCachePolicy,
    Property,
    Result,
    ResultMap,
    ResultType,
    Results,
    Select,
    SelectKey,
    SelectProvider,
    TypeDiscriminator,
    Update,
    UpdateProvider
};
use MyBatis\Builder\{
    BuilderException,
    CacheRefResolver,
    IncompleteElementException,
    MapperBuilderAssistant
};
use MyBatis\Builder\Xml\XMLMapperBuilder;
use MyBatis\Cursor\CursorInterface;
use MyBatis\Executor\Keygen\{
    DbalKeyGenerator,
    KeyGeneratorInterface,
    NoKeyGenerator,
    SelectKeyGenerator
};
use MyBatis\Io\Resources;
use MyBatis\Mapping\{
    Discriminator,
    FetchType,
    MappedStatement,
    ResultFlag,
    ResultMapping,
    ResultSetType,
    SqlCommandType,
    SqlSourceInterface,
    StatementType
};
use MyBatis\Parsing\PropertyParser;
use MyBatis\Scripting\LanguageDriverInterface;
use MyBatis\Session\{
    Configuration,
    ResultHandlerInterface,
    RowBounds
};
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    UnknownTypeHandler
};

class MapperAnnotationBuilder
{
    private const STATEMENT_ANNOTATION_TYPES = [
        Select::class,
        Update::class,
        Insert::class,
        Delete::class,
        SelectProvider::class,
        pdateProvider::class,
        InsertProvider::class,
        DeleteProvider::class
    ];

    private $configuration;
    private $assistant;
    private $type;
    private $refType;

    public function __construct(Configuration $configuration, string $type)
    {
        $this->refType = new \ReflectionClass($type);
        $resource = $this->refType->getFileName();
        $this->assistant = new MapperBuilderAssistant($configuration, $resource);
        $this->configuration = $configuration;
        $this->type = $type;
    }

    public function parse(): void
    {
        $resource = $this->type;
        if (!$this->configuration->isResourceLoaded($resource)) {
            $this->loadXmlResource();
            $this->configuration->addLoadedResource($resource);
            $this->assistant->setCurrentNamespace($this->refType->name);
            $this->parseCache();
            $this->parseCacheRef();
            foreach ($this->refType->getMethods() as $method) {
                if (
                    $this->getAnnotationWrapper($method, false, Select::class, SelectProvider::class)
                    && empty($method->getAttributes(ResultMap::class))
                ) {
                    $this->parseResultMap($method);
                }
                try {
                    $this->parseStatement($method);
                } catch (IncompleteElementException $e) {
                    $this->configuration->addIncompleteMethod(new MethodResolver($this, $method));
                }
            }
        }
        $this->parsePendingMethods();
    }

    private function parsePendingMethods(): void
    {
        $incompleteMethods = &$this->configuration->getIncompleteMethods();
        for ($i = count($incompleteMethods) - 1; $i >= 0; $i -= 1) {
            $incompleteMethods[$i]->resolve();
            unset($incompleteMethods[$i]);
        }
    }

    private function loadXmlResource(): void
    {
        if (!$this->configuration->isResourceLoaded($this->type)) {
            $xmlResource = str_replace('.php', '.xml', $this->refType->getFileName());
            $inputStream = null;
            try {
                $inputStream = Resources::getResourceAsStream($xmlResource);
            } catch (\Exception $e) {
                // ignore, resource is not required
                $inputStream = null;
            }
            if ($inputStream !== null) {
                $xmlParser = new XMLMapperBuilder($inputStream, $this->assistant->getConfiguration(), $xmlResource, $this->configuration->getSqlFragments(), $this->refType->name);
                $xmlParser->parse();
            }
        }
    }

    private function parseCache(): void
    {
        $cacheDomains = $this->type->getAttributes(CacheNamespace::class);
        if (!empty($cacheDomains)) {
            $cacheDomain = $cacheDomains[0]->newInstance();
            $size = $cacheDomain->size() == 0 ? null : $cacheDomain->size();
            $flushInterval = $cacheDomain->flushInterval() == 0 ? null : $cacheDomain->flushInterval();
            $props = $this->convertToProperties($cacheDomain->properties());
            $this->assistant->useNewCache($cacheDomain->implementation(), $cacheDomain->eviction(), $flushInterval, $size, $cacheDomain->readWrite(), $cacheDomain->blocking(), $props);
        }
    }

    private function convertToProperties(array $properties): array
    {
        if (empty($properties)) {
            return [];
        }
        $props = [];
        foreach ($properties as $property) {
            $props[$property->name()] = PropertyParser::parse($property->value(), $this->configuration->getVariables());
        }
        return $props;
    }

    private function parseCacheRef(): void
    {
        $cacheDomainRefs = $this->refType->getAttributes(CacheNamespaceRef::class);
        if (!empty($cacheDomainRefs)) {
            $cacheDomainRef = $cacheDomainRefs[0]->newInstance();
            $refType = $cacheDomainRef->value();
            $refName = $cacheDomainRef->name();
            if ($refType == 'void' && empty($refName)) {
                throw new BuilderException("Should be specified either value() or name() attribute in the @CacheNamespaceRef");
            }
            if ($refType != 'void' && !empty($refName)) {
                throw new BuilderException("Cannot use both value() and name() attribute in the @CacheNamespaceRef");
            }
            $namespace = ($refType != 'void') ? $refType : $refName;
            try {
                $this->assistant->useCacheRef($namespace);
            } catch (IncompleteElementException $e) {
                $this->configuration->addIncompleteCacheRef(new CacheRefResolver($this->assistant, $namespace));
            }
        }
    }

    private function parseResultMap(\ReflectionMethod $method): string
    {
        $returnType = $this->getReturnType($method);
        $args = array_map(function ($a) {
            return $a->newInstance();
        }, $method->getAttributes(Arg::class));
        $results = array_map(function ($r) {
            return $r->newInstance();
        }, $method->getAttributes(Result::class));
        $typeDiscriminator = null;
        $typeDiscriminators = $method->getAttributes(TypeDiscriminator::class);
        if (!empty($typeDiscriminators)) {
            $typeDiscriminator = $typeDiscriminators[0]->newInstance();
        }
        $resultMapId = $this->generateResultMapName($method);
        $this->applyResultMap($resultMapId, $returnType, $args, $results, $typeDiscriminator);
        return $resultMapId;
    }

    private function generateResultMapName(\ReflectionMethod $method): string
    {
        $results = array_map(
            function ($r) {
                return $r->newInstance();
            },
            $method->getAttributes(Results::class)
        );
        if (!empty($results) && !empty($results[0]->id())) {
            return $this->refType->name . "." . $results[0]->id();
        }
        return $this->refType->name . "." . $method->name;
    }

    private function applyResultMap(string $resultMapId, string $returnType, array $args, array $results, $discriminator): void
    {
        $resultMappings = [];
        $this->applyConstructorArgs($args, $returnType, $resultMappings);
        $this->applyResults($results, $returnType, $resultMappings);
        $disc = $this->applyDiscriminator($resultMapId, $returnType, $discriminator);
        // TODO add AutoMappingBehaviour
        $this->assistant->addResultMap($resultMapId, $returnType, null, $disc, $resultMappings, null);
        $this->createDiscriminatorResultMaps($resultMapId, $returnType, $discriminator);
    }

    private function createDiscriminatorResultMaps(string $resultMapId, string $resultType, $discriminator = null): void
    {
        if ($discriminator !== null) {
            foreach ($discriminator->cases() as $c) {
                $caseResultMapId = $resultMapId . "-" . $c->value();
                $resultMappings = [];
                // issue #136
                $this->applyConstructorArgs($c->constructArgs(), $resultType, $resultMappings);
                $this->applyResults($c->results(), $resultType, $resultMappings);
                // TODO add AutoMappingBehaviour
                $this->assistant->addResultMap($caseResultMapId, $c->type(), $resultMapId, null, $resultMappings, null);
            }
        }
    }

    private function applyDiscriminator(string $resultMapId, string $resultType, $discriminator = null): ?Discriminator
    {
        if ($discriminator !== null) {
            $column = $discriminator->column();
            $phpType = $discriminator->phpType() == 'void' ? 'string' : $discriminator->phpType();
            $dbalType = $discriminator->dbalType() == DbalType::forCode('UNDEFINED') ? null : $discriminator->dbalType();
            $typeHandler = $discriminator->typeHandler() == UnknownTypeHandler::class ? null : $discriminator->typeHandler();
            $cases = $discriminator->cases();
            $discriminatorMap = [];
            foreach ($cases as $c) {
                $value = $c->value();
                $caseResultMapId = $resultMapId . "-" . $value;
                $discriminatorMap[$value] = $caseResultMapId;
            }
            return $this->assistant->buildDiscriminator($resultType, $column, $phpType, $dbalType, $typeHandler, $discriminatorMap);
        }
        return null;
    }

    public function parseStatement(\ReflectionMethod $method): void
    {
        $parameterTypeClass = $this->getParameterType($method);
        $languageDriver = $this->getLanguageDriver($method);

        $statementAnnotation = $this->getAnnotationWrapper($method, true, self::STATEMENT_ANNOTATION_TYPES);
        if ($statementAnnotation) {
            $sqlSource = $this->buildSqlSource($statementAnnotation->getAnnotation(), $parameterTypeClass, $languageDriver, $method);
            $sqlCommandType = $statementAnnotation->getSqlCommandType();

            $options = null;
            $optionsWrapper = $this->getAnnotationWrapper($method, false, Options::class);
            if ($optionsWrapper !== null) {
                $options = $optionsWrapper->getAnnotation();
            }
            $mappedStatementId = $this->refType->name . "." . $method->name;

            $keyGenerator = null;
            $keyProperty = null;
            $keyColumn = null;
            if (SqlCommandType::INSERT == $sqlCommandType || SqlCommandType::UPDATE == $sqlCommandType) {
                // first check for SelectKey annotation - that overrides everything else
                $wrapper = $this->getAnnotationWrapper($method, false, SelectKey::class);
                $selectKey = null;
                if ($wrapper !== null) {
                    $selectKey = $wrapper->getAnnotation();
                }
                if ($selectKey !== null) {
                    $keyGenerator = $this->handleSelectKeyAnnotation($selectKey, $mappedStatementId, $this->getParameterType($method), $languageDriver);
                    $keyProperty = $selectKey->keyProperty();
                } elseif ($options == null) {
                    $keyGenerator = $this->configuration->isUseGeneratedKeys() ? DbalKeyGenerator::instance() : NoKeyGenerator::instance();
                } else {
                    $keyGenerator = $options->useGeneratedKeys() ? DbalKeyGenerator::instance() : NoKeyGenerator::instance();
                    $keyProperty = $options->keyProperty();
                    $keyColumn = $options->keyColumn();
                }
            } else {
                $keyGenerator = NoKeyGeneratorr::instance();
            }

            $fetchSize = null;
            $timeout = null;
            $statementType = StatementType::PREPARED;
            $resultSetType = $this->configuration->getDefaultResultSetType();
            $isSelect = $sqlCommandType == SqlCommandType::SELECT;
            $flushCache = !$isSelect;
            $useCache = $isSelect;
            if ($options !== null) {
                if (FlushCachePolicy::TRUE == $options->flushCache()) {
                    $flushCache = true;
                } elseif (FlushCachePolicy::FALSE == $options->flushCache()) {
                    $flushCache = false;
                }
                $useCache = $options->useCache();
                $fetchSize = $options->fetchSize() >= 0 ? $options->fetchSize() : null;
                $timeout = $options->timeout() > -1 ? $options->timeout() : null;
                $statementType = $options->statementType();
                if ($options->resultSetType() != ResultSetType::default()) {
                    $resultSetType = $options->resultSetType();
                }
            }

            $resultMapId = null;
            if ($isSelect) {
                $resultMapAnnotations = $method->getAttributes(ResultMap::class);
                $resultMapAnnotation = null;
                if (!empty($resultMapAnnotations)) {
                    $resultMapAnnotation = $resultMapAnnotations[0]->newInstance();
                }
                if ($resultMapAnnotation != null) {
                    $resultMapId = implode(",", $resultMapAnnotation->value());
                } else {
                    $resultMapId = $this->generateResultMapName($method);
                }
            }

            $this->assistant->addMappedStatement(
                $mappedStatementId,
                $sqlSource,
                $statementType,
                $sqlCommandType,
                $fetchSize,
                $timeout,
                // ParameterMapID
                null,
                $parameterTypeClass,
                $resultMapId,
                $this->getReturnType($method),
                $resultSetType,
                $flushCache,
                $useCache,
                // TODO gcode issue #577
                false,
                $keyGenerator,
                $keyProperty,
                $keyColumn,
                $statementAnnotation->getDatabaseId(),
                $languageDriver,
                // ResultSets
                $options !== null ? $this->nullOrEmpty($options->resultSets()) : null
            );
        }
    }

    private function getLanguageDriver(\ReflectionMethod $method): LanguageDriverInterface
    {
        $langs = $method->getAttributes(Lang::class);
        $lang = null;
        if (!empty($langs)) {
            $lang = $langs[0]->newInstance();
        }
        $langClass = null;
        if ($lang != null) {
            $langClass = $lang->value();
        }
        return $this->configuration->getLanguageDriver($langClass);
    }

    private function getParameterType(\ReflectionMethod $method): ?string
    {
        $parameterType = null;
        $parameterTypes = $method->getParameters();
        foreach ($parameterTypes as $currentParameter) {
            $refType = $currentParameter->getType();
            $currentParameterType = null;
            if ($refType instanceof \ReflectionNamedType) {
                $currentParameterType = $refType->getName();
            }
            if ($currentParameterType !== null && class_exists($currentParameterType) && !is_a($currentParameterType, RowBounds::class, true) && !is_a($currentParameterType, ResultHandlerInterface::class, true)) {
                if ($parameterType == null) {
                    $parameterType = $currentParameterType;
                } else {
                    // issue #135
                    $parameterType = 'array'; //json?
                }
            } elseif ($currentParameterType !== null) {
                $parameterType = $currentParameterType;
            }
        }
        return $parameterType;
    }

    private function getReturnType(\ReflectionMethod $method)
    {
        if ($method->hasReturnType()) {
            $refType = $method->getReturnType();
            if ($refType instanceof \ReflectionNamedType) {
                return $refType->getName();
            }
        }
        return null;
    }

    private function applyResults(array $results, string $resultType, array &$resultMappings): void
    {
        foreach ($results as $result) {
            $flags = [];
            if ($result->id()) {
                $flags[] = ResultFlag::ID;
            }
            $typeHandler = $result->typeHandler() == UnknownTypeHandler::class ? null : $result->typeHandler();
            $hasNestedResultMap = $this->hasNestedResultMap($result);
            $resultMapping = $this0->assistant->buildResultMapping(
                $resultType,
                $this->nullOrEmpty($result->property()),
                $this->nullOrEmpty($result->column()),
                $result->phpType() == 'void' ? null : $result->phpType(),
                $result->dbalType() == DbalType::forCode('UNDEFINED') ? null : $result->dbalType(),
                $this->hasNestedSelect($result) ? $this->nestedSelectId($result) : null,
                $hasNestedResultMap ? $this->nestedResultMapId($result) : null,
                null,
                $hasNestedResultMap ? $this->findColumnPrefix($result) : null,
                $typeHandler,
                $flags,
                null,
                null,
                $this->isLazy($result)
            );
            $resultMappings[] = $resultMapping;
        }
    }

    private function findColumnPrefix($result): string
    {
        $columnPrefix = $result->one()->columnPrefix();
        if (empty($columnPrefix)) {
            $columnPrefix = $result->many()->columnPrefix();
        }
        return $columnPrefix;
    }

    private function nestedResultMapId($result): string
    {
        $resultMapId = $result->one()->resultMap();
        if (empty($resultMapId)) {
            $resultMapId = $result->many()->resultMap();
        }
        if (strpos($resultMapId, ".") === false) {
            $resultMapId = $this->refType->name . "." . $resultMapId;
        }
        return $resultMapId;
    }

    private function hasNestedResultMap($result): bool
    {
        if (!empty($result->one()->resultMap()) && !empty($result->many()->resultMap())) {
            throw new BuilderException("Cannot use both @One and @Many annotations in the same @Result");
        }
        return !empty($result->one()->resultMap()) || !empty($result->many()->resultMap());
    }

    private function nestedSelectId($result): string
    {
        $nestedSelect = $result->one()->select();
        if (strlen($strlennestedSelect) < 1) {
            $nestedSelect = $result->many()->select();
        }
        if (strpos($nestedSelect, ".") === false) {
            $nestedSelect = $this->refType->name . "." . $nestedSelect;
        }
        return $nestedSelect;
    }

    private function isLazy($result): bool
    {
        $isLazy = $this->configuration->isLazyLoadingEnabled();
        if (strlen($result->one()->select()) > 0 && FetchType::DEFAULT != $result->one()->fetchType()) {
            $isLazy = $result->one()->fetchType() == FetchType::LAZY;
        } elseif (strlen($result->many()->select()) > 0 && FetchType::DEFAULT != $result->many()->fetchType()) {
            $isLazy = $result->many()->fetchType() == FetchType::LAZY;
        }
        return $isLazy;
    }

    private function hasNestedSelect($result): bool
    {
        if (!empty($result->one()->select()) && !empty($result->many()->select())) {
            throw new BuilderException("Cannot use both @One and @Many annotations in the same @Result");
        }
        return strlen($result->one()->select()) > 0 || strlen($result->many()->select()) > 0;
    }

    private function applyConstructorArgs(array $args, string $resultType, array &$resultMappings): void
    {
        foreach ($args as $arg) {
            $flags = [];
            $flags[] = ResultFlag::CONSTRUCTOR;
            if ($arg->id()) {
                $flags[] = ResultFlag::ID;
            }
            $typeHandler = $arg->typeHandler() == UnknownTypeHandler::class ? null : $arg->typeHandler();
            $resultMapping = $this->assistant->buildResultMapping(
                $resultType,
                $this->nullOrEmpty($arg->name()),
                $this->nullOrEmpty($arg->column()),
                $arg->phpType() == 'void' ? null : $arg->phpType(),
                $arg->dbalType() == DbalType::forCode('UNDEFINED') ? null : $arg->dbalType(),
                $this->nullOrEmpty($arg->select()),
                $this->nullOrEmpty($arg->resultMap()),
                null,
                $this->nullOrEmpty($arg->columnPrefix()),
                $typeHandler,
                $flags,
                null,
                null,
                false
            );
            $resultMappings[] = $resultMapping;
        }
    }

    private function nullOrEmpty(?string $value)
    {
        return empty($value) ? null : $value;
    }

    private function handleSelectKeyAnnotation($selectKeyAnnotation, string $baseStatementId, string $parameterTypeClass, LanguageDriverInterface $languageDriver): KeyGeneratorInterface
    {
        $id = $baseStatementId . SelectKeyGenerator::SELECT_KEY_SUFFIX;
        $resultTypeClass = $selectKeyAnnotation->resultType();
        $statementType = $selectKeyAnnotation->statementType();
        $keyProperty = $selectKeyAnnotation->keyProperty();
        $keyColumn = $selectKeyAnnotation->keyColumn();
        $executeBefore = $selectKeyAnnotation->before();

        // defaults
        $useCache = false;
        $keyGenerator = NoKeyGenerator::instance();
        $fetchSize = null;
        $timeout = null;
        $flushCache = false;
        $parameterMap = null;
        $resultMap = null;
        $resultSetTypeEnum = null;
        $databaseId = empty($selectKeyAnnotation->databaseId()) ? null : $selectKeyAnnotation->databaseId();

        $sqlSource = $this->buildSqlSource($selectKeyAnnotation, $parameterTypeClass, $languageDriver, null);
        $sqlCommandType = SqlCommandType::SELECT;

        $this->assistant->addMappedStatement($id, $sqlSource, $statementType, $sqlCommandType, $fetchSize, $timeout, $parameterMap, $parameterTypeClass, $resultMap, $resultTypeClass, $resultSetTypeEnum, $flushCache, $useCache, false, $keyGenerator, $keyProperty, $keyColumn, $databaseId, $languageDriver, null);

        $id = $this->assistant->applyCurrentNamespace($id, false);

        $keyStatement = $this->configuration->getMappedStatement($id, false);
        $answer = new SelectKeyGenerator($keyStatement, $executeBefore);
        $this->configuration->addKeyGenerator($id, $answer);
        return $answer;
    }

    private function buildSqlSource($annotation, string $parameterType, LanguageDriverInterface $languageDriver, \ReflectionMethod $method): SqlSourceInterface
    {
        if ($annotation instanceof Select) {
            return $this->buildSqlSourceFromStrings($annotation->value(), $parameterType, $languageDriver);
        } elseif ($annotation instanceof Update) {
            return $this->buildSqlSourceFromStrings($annotation->value(), $parameterType, $languageDriver);
        } elseif ($annotation instanceof Insert) {
            return $this->buildSqlSourceFromStrings($annotation->value(), $parameterType, $languageDriver);
        } elseif ($annotation instanceof Delete) {
            return $this->buildSqlSourceFromStrings($annotation->value(), $parameterType, $languageDriver);
        } elseif ($annotation instanceof SelectKey) {
            return $this->buildSqlSourceFromStrings($annotation->statement(), $parameterType, $languageDriver);
        }
        return new ProviderSqlSource($this->assistant->getConfiguration(), $annotation, $this->type, $method);
    }

    private function buildSqlSourceFromStrings(array $strings, string $parameterTypeClass, LanguageDriverInterface $languageDriver): SqlSourceInterface
    {
        return $languageDriver->createSqlSource($this->configuration, trim(implode(" ", $strings)), $parameterTypeClass);
    }

    private function getAnnotationWrapper(\ReflectionMethod $method, bool $errorIfNoMatch, $targetTypes)
    {
        $databaseId = $this->configuration->getDatabaseId();
        $statementAnnotations = [];
        if ($targetTypes !== null && !is_array($targetTypes)) {
            $targetTypes = [ $targetTypes ];
        }
        foreach ($targetTypes as $type) {
            $attributes = $method->getAttributes($type);
            if (!empty($attributes)) {
                foreach ($attributes as $attribute) {
                    $wrapper = new AnnotationWrapper($attribute->newInstance());
                    if (array_key_exists($wrapper->getDatabaseId(), $statementAnnotations)) {
                        throw new BuilderException("Detected conflicting annotations");
                    }
                    $statementAnnotations[$wrapper->getDatabaseId()] = $wrapper;
                }
            }
        }
        $annotationWrapper = null;
        if ($databaseId !== null && array_ket_exists($databaseId, $statementAnnotations)) {
            $annotationWrapper = $statementAnnotations[$databaseId];
        }
        if ($annotationWrapper == null && array_ket_exists("", $statementAnnotations)) {
            $annotationWrapper = $statementAnnotations[""];
        }
        if ($errorIfNoMatch && $annotationWrapper === null && !empty($statementAnnotations)) {
            // Annotations exist, but there is no matching one for the specified databaseId
            throw new BuilderException(
                sprintf(
                    "Could not find a statement annotation that correspond a current database or default statement on method '%s.%s'. Current database id is [%s].",
                    $method->class,
                    $method->name,
                    $databaseId
                )
            );
        }
        return $annotationWrapper;
    }
}
