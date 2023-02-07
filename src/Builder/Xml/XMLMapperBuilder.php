<?php

namespace MyBatis\Builder\Xml;

use MyBatis\Builder\{
    BaseBuilder,
    BuilderException,
    CacheRefResolver,
    IncompleteElementException,
    MapperBuilderAssistant,
    ResultMapResolver
};
use MyBatis\Mapping\{
    Discriminator,
    ParameterMapping,
    ParameterMode,
    ResultFlag,
    ResultMap,
    ResultMapping
};
use MyBatis\Parsing\{
    XNode,
    XPathParser
};
use MyBatis\Session\{
    Configuration,
    StrictMap
};
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface
};
use Util\Reflection\MetaClass;

class XMLMapperBuilder extends BaseBuilder
{
    private $parser;
    private $builderAssistant;
    private $sqlFragments;
    private $resource;

    public function __construct($stream, Configuration $configuration, string $resource, StrictMap $sqlFragments, ?string $namespace = null)
    {
        parent::__construct($configuration);
        $this->builderAssistant = new MapperBuilderAssistant($configuration, $resource);
        $this->parser = new XPathParser($stream, true, $configuration->getVariables(), new XMLMapperEntityResolver());
        $this->sqlFragments = $sqlFragments;
        $this->resource = $resource;
        if ($namespace !== null) {
            $this->builderAssistant->setCurrentNamespace($namespace);
        }
    }

    public function parse(): void
    {
        if (!$this->configuration->isResourceLoaded($this->resource)) {
            $this->configurationElement($this->parser->evalNode("/mapper"));
            $this->configuration->addLoadedResource($this->resource);
            $this->bindMapperForNamespace();
        }

        $this->parsePendingResultMaps();
        $this->parsePendingCacheRefs();
        $this->parsePendingStatements();
    }

    public function getSqlFragment(string $refid): ?XNode
    {
        if (array_key_exists($refid, $this->sqlFragments->getArrayCopy())) {
            return $this->sqlFragments[$refid];
        }
        return null;
    }

    private function configurationElement(XNode $context): void
    {
        try {
            $namespace = $context->getStringAttribute("namespace");
            if (empty($namespace)) {
                throw new BuilderException("Mapper's namespace cannot be empty");
            }
            $this->builderAssistant->setCurrentNamespace($namespace);
            $this->cacheRefElement($context->evalNode("cache-ref"));
            $this->cacheElement($context->evalNode("cache"));
            $this->parameterMapElement($context->evalNodes("/mapper/parameterMap"));
            $this->resultMapElements($context->evalNodes("/mapper/resultMap"));
            $this->sqlElement($context->evalNodes("/mapper/sql"));
            $this->buildStatementFromContext($context->evalNodes("select|insert|update|delete"));
        } catch (\Exception $e) {
            throw new BuilderException("Error parsing Mapper XML. The XML location is '" . $this->resource . "'. Cause: " . $e->getMessage());
        }
    }

    private function buildStatementFromContext(array $list, ?string $requiredDatabaseId = null): void
    {
        if ($requiredDatabaseId === null && $this->configuration->getDatabaseId() !== null) {
            $requiredDatabaseId = $this->configuration->getDatabaseId();
        }
        foreach ($list as $context) {
            $statementParser = new XMLStatementBuilder($this->configuration, $this->builderAssistant, $context, $requiredDatabaseId);
            try {
                $statementParser->parseStatementNode();
            } catch (IncompleteElementException $e) {
                $this->configuration->addIncompleteStatement($statementParser);
            }
        }
    }

    private function parsePendingResultMaps(): void
    {
        $incompleteResultMaps = $this->configuration->getIncompleteResultMaps();
        foreach ($incompleteResultMaps as $res) {
            try {
                $res->resolve();
            } catch (IncompleteElementException $e) {
                // ResultMap is still missing a resource...
            }
        }
    }

    private function parsePendingCacheRefs(): void
    {
        $incompleteCacheRefs = $this->configuration->getIncompleteCacheRefs();
        foreach ($incompleteCacheRefs as $res) {
            try {
                $res->resolveCacheRef();
            } catch (IncompleteElementException $e) {
                // ResultMap is still missing a resource...
            }
        }
    }

    private function parsePendingStatements(): void
    {
        $incompleteStatements = $this->configuration->getIncompleteStatements();
        foreach ($incompleteStatements as $res) {
            try {
                $res->parseStatementNode();
            } catch (IncompleteElementException $e) {
                // ResultMap is still missing a resource...
            }
        }
    }

    private function cacheRefElement(?XNode $context): void
    {
        if ($context !== null) {
            $this->configuration->addCacheRef($this->builderAssistant->getCurrentNamespace(), $context->getStringAttribute("namespace"));
            $cacheRefResolver = new CacheRefResolver($this->builderAssistant, $context->getStringAttribute("namespace"));
            try {
                $cacheRefResolver->resolveCacheRef();
            } catch (IncompleteElementException $e) {
                $this->configuration->addIncompleteCacheRef($cacheRefResolver);
            }
        }
    }

    private function cacheElement(?XNode $context): void
    {
        if ($context !== null) {
            $type = $context->getStringAttribute("type", "PERPETUAL");
            $typeClass = $this->typeAliasRegistry->resolveAlias($type);
            $eviction = $context->getStringAttribute("eviction", "LRU");
            $evictionClass = $this->typeAliasRegistry->resolveAlias($eviction);
            $flushInterval = $context->getLongAttribute("flushInterval");
            $size = $context->getIntAttribute("size");
            $readWrite = !$context->getBooleanAttribute("readOnly", false);
            $blocking = $context->getBooleanAttribute("blocking", false);
            $props = $context->getChildrenAsProperties();
            $this->builderAssistant->useNewCache($typeClass, $evictionClass, $flushInterval, $size, $readWrite, $blocking, $props);
        }
    }

    private function parameterMapElement(array $list): void
    {
        foreach ($list as $parameterMapNode) {
            $id = $parameterMapNode->getStringAttribute("id");
            $type = $parameterMapNode->getStringAttribute("type");
            $parameterClass = $this->resolveClass($type);
            $parameterNodes = $parameterMapNode->evalNodes("parameter");
            $parameterMappings = [];
            foreach ($parameterNodes as $parameterNode) {
                $property = $parameterNode->getStringAttribute("property");
                $phpType = $parameterNode->getStringAttribute("phpType");
                $dbalType = $parameterNode->getStringAttribute("dbalType");
                $resultMap = $parameterNode->getStringAttribute("resultMap");
                $mode = $parameterNode->getStringAttribute("mode");
                $typeHandler = $parameterNode->getStringAttribute("typeHandler");
                $numericScale = $parameterNode->getIntAttribute("numericScale");
                $modeEnum = $this->resolveParameterMode($mode);
                $phpTypeClass = $this->resolveClass($phpType);
                $dbalTypeEnum = $this->resolveDbalType($dbalType);
                $typeHandlerClass = $this->resolveClass($typeHandler);
                $parameterMapping = $this->builderAssistant->buildParameterMapping($parameterClass, $property, $phpTypeClass, $dbalTypeEnum, $resultMap, $modeEnum, $typeHandlerClass, $numericScale);
                $parameterMappings[] = $parameterMapping;
            }
            $this->builderAssistant->addParameterMap($id, $parameterClass, $parameterMappings);
        }
    }

    private function resultMapElements(array $list): void
    {
        foreach ($list as $resultMapNode) {
            try {
                $this->resultMapElement($resultMapNode);
            } catch (IncompleteElementException $e) {
            // ignore, it will be retried
            }
        }
    }

    private function resultMapElement(XNode $resultMapNode, array &$resultMappings = [], string $enclosingType = null): ResultMap
    {
        $type = $resultMapNode->getStringAttribute(
            "type",
            $resultMapNode->getStringAttribute(
                "ofType",
                $resultMapNode->getStringAttribute(
                    "resultType",
                    $resultMapNode->getStringAttribute("phpType")
                )
            )
        );
        $typeClass = $this->resolveClass($type);
        if ($typeClass === null) {
            $typeClass = $this->inheritEnclosingType($resultMapNode, $enclosingType);
        }
        $discriminator = null;
        $resultChildren = $resultMapNode->getChildren();
        foreach ($resultChildren as $resultChild) {
            if ("constructor" == $resultChild->getName()) {
                $this->processConstructorElement($resultChild, $typeClass, $resultMappings);
            } elseif ("discriminator" == $resultChild->getName()) {
                $discriminator = $this->processDiscriminatorElement($resultChild, $typeClass, $resultMappings);
            } else {
                $flags = [];
                if ("id" == $resultChild->getName()) {
                    $flags[] = ResultFlag::ID;
                }
                $resultMappings[] = $this->buildResultMappingFromContext($resultChild, $typeClass, $flags);
            }
        }
        $id = $resultMapNode->getStringAttribute(
            "id",
            $resultMapNode->getValueBasedIdentifier()
        );
        $extend = $resultMapNode->getStringAttribute("extends");
        $autoMapping = $resultMapNode->getBooleanAttribute("autoMapping");
        $resultMapResolver = new ResultMapResolver($this->builderAssistant, $id, $typeClass, $extend, $discriminator, $resultMappings, $autoMapping);
        try {
            return $resultMapResolver->resolve();
        } catch (IncompleteElementException $e) {
            $this->configuration->addIncompleteResultMap($resultMapResolver);
            throw $e;
        }
    }

    protected function inheritEnclosingType(XNode $resultMapNode, ?string $enclosingType): ?string
    {
        if ("association" == $resultMapNode->getName() && $resultMapNode->getStringAttribute("resultMap") === null) {
            $property = $resultMapNode->getStringAttribute("property");
            if ($property !== null && $enclosingType !== null) {
                $metaResultType = new MetaClass($enclosingType);
                return $metaResultType->getSetterType($property);
            }
        } elseif ("case" == $resultMapNode->getName() && $resultMapNode->getStringAttribute("resultMap") === null) {
            return $enclosingType;
        }
        return null;
    }

    private function processConstructorElement(XNode $resultChild, ?string $resultType, array &$resultMappings): void
    {
        $argChildren = $resultChild->getChildren();
        foreach ($argChildren as $argChild) {
            $flags = [];
            $flags[] = ResultFlag::CONSTRUCTOR;
            if ("idArg" == $argChild->getName()) {
                $flags[] = ResultFlag::ID;
            }
            $resultMappings[] = $this->buildResultMappingFromContext($argChild, $resultType, $flags);
        }
    }

    private function processDiscriminatorElement(XNode $context, string $resultType, array &$resultMappings): Discriminator
    {
        $column = $context->getStringAttribute("column");
        $phpType = $context->getStringAttribute("phpType");
        $dbalType = $context->getStringAttribute("dbalType");
        $typeHandler = $context->getStringAttribute("typeHandler");
        $phpTypeClass = $this->resolveClass($phpType);
        $typeHandlerClass = $this->resolveClass($typeHandler);
        $dbalTypeEnum = $this->resolveDbalType($dbalType);
        $discriminatorMap = [];
        foreach ($context->getChildren() as $caseChild) {
            $value = $caseChild->getStringAttribute("value");
            $resultMap = $caseChild->getStringAttribute("resultMap", $this->processNestedResultMappings($caseChild, $resultMappings, $resultType));
            $discriminatorMap[$value] = $resultMap;
        }
        return $this->builderAssistant->buildDiscriminator($resultType, $column, $phpTypeClass, $dbalTypeEnum, $typeHandlerClass, $discriminatorMap);
    }

    private function sqlElement(array $list, ?string $requiredDatabaseId = null): void
    {
        if ($requiredDatabaseId === null && $this->configuration->getDatabaseId() !== null) {
            $requiredDatabaseId = $this->configuration->getDatabaseId();
        }
        foreach ($list as $context) {
            $databaseId = $context->getStringAttribute("databaseId");
            $id = $context->getStringAttribute("id");
            $id = $this->builderAssistant->applyCurrentNamespace($id, false);
            if ($this->databaseIdMatchesCurrent($id, $databaseId, $requiredDatabaseId)) {
                $this->sqlFragments[$id] = $context;
            }
        }
    }

    private function databaseIdMatchesCurrent(string $id, ?string $databaseId, ?string $requiredDatabaseId): bool
    {
        if ($requiredDatabaseId !== null) {
            return $requiredDatabaseId == $databaseId;
        }
        if ($databaseId !== null) {
            return false;
        }
        if (!array_key_exists($id, $this->sqlFragments->getArrayCopy())) {
            return true;
        }
        // skip this fragment if there is a previous one with a not null databaseId
        $context = $this->sqlFragments[$id];
        return $context->getStringAttribute("databaseId") === null;
    }

    private function buildResultMappingFromContext(XNode $context, ?string $resultType, array $flags): ResultMapping
    {
        $property = null;
        if (in_array(ResultFlag::CONSTRUCTOR, $flags)) {
            $property = $context->getStringAttribute("name");
        } else {
            $property = $context->getStringAttribute("property");
        }
        $column = $context->getStringAttribute("column");
        $phpType = $context->getStringAttribute("phpType");
        $dbalType = $context->getStringAttribute("dbalType");
        $nestedSelect = $context->getStringAttribute("select");
        $resultMappings = [];
        $nestedResultMap = $context->getStringAttribute("resultMap", $this->processNestedResultMappings($context, $resultMappings, $resultType));
        $notNullColumn = $context->getStringAttribute("notNullColumn");
        $columnPrefix = $context->getStringAttribute("columnPrefix");
        $typeHandler = $context->getStringAttribute("typeHandler");
        $resultSet = $context->getStringAttribute("resultSet");
        $foreignColumn = $context->getStringAttribute("foreignColumn");
        $lazy = "lazy" == $context->getStringAttribute("fetchType", $this->configuration->isLazyLoadingEnabled() ? "lazy" : "eager");
        $phpTypeClass = $this->resolveClass($phpType);
        $typeHandlerClass = $this->resolveClass($typeHandler);
        $dbalTypeEnum = $this->resolveDbalType($dbalType);
        return $this->builderAssistant->buildResultMapping($resultType, $property, $column, $phpTypeClass, $dbalTypeEnum, $nestedSelect, $nestedResultMap, $notNullColumn, $columnPrefix, $typeHandlerClass, $flags, $resultSet, $foreignColumn, $lazy);
    }

    private function processNestedResultMappings(XNode $context, array &$resultMappings, ?string $enclosingType): ?string
    {
        if (
            in_array($context->getName(), ["association", "collection", "case"])
            && $context->getStringAttribute("select") === null
        ) {
            $this->validateCollection($context, $enclosingType);
            $resultMap = $this->resultMapElement($context, $resultMappings, $enclosingType);
            return $resultMap->getId();
        }
        return null;
    }

    protected function validateCollection(XNode $context, ?string $enclosingType): void
    {
        if (
            "collection" == $context->getName() && $context->getStringAttribute("resultMap") === null
            && $context->getStringAttribute("phpType") === null
        ) {
            $metaResultType = new MetaClass($enclosingType);
            $property = $context->getStringAttribute("property");
            if (!$metaResultType->hasSetter($property)) {
                throw new BuilderException(
                    "Ambiguous collection type for property '" . $property . "'. You must specify 'phpType' or 'resultMap'."
                );
            }
        }
    }

    private function bindMapperForNamespace(): void
    {
        $namespace = $this->builderAssistant->getCurrentNamespace();
        if ($namespace !== null) {
            $boundType = null;
            try {
                $boundType = $namespace;
            } catch (\Exception $e) {
                // ignore, bound type is not required
            }
            if ($boundType !== null && !$this->configuration->hasMapper($boundType)) {
                // Spring may not know the real resource name so we set a flag
                // to prevent loading again this resource from the mapper interface
                // look at MapperAnnotationBuilder#loadXmlResource
                $this->configuration->addLoadedResource("namespace:" . $namespace);
                $this->configuration->addMapper($boundType);
            }
        }
    }
}
