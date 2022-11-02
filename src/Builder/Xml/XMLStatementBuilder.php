<?php

namespace MyBatis\Builder\Xml;

use MyBatis\Builder\{
    BaseBuilder,
    MapperBuilderAssistant
};
use MyBatis\Executor\Keygen\{
    DbalKeyGenerator,
    KeyGeneratorInterface,
    NoKeyGenerator,
    SelectKeyGenerator
};
use MyBatis\Mapping\{
    MappedStatement,
    ResultSetType,
    SqlCommandType,
    SqlSourceInterface,
    StatementType
};
use MyBatis\Parsing\XNode;
use MyBatis\Scripting\LanguageDriverInterface;
use MyBatis\Session\Configuration;

class XMLStatementBuilder extends BaseBuilder
{
    private $builderAssistant;
    private $context;
    private $requiredDatabaseId;

    public function __construct(Configuration $configuration, MapperBuilderAssistant $builderAssistant, XNode $context, ?string $databaseId = null)
    {
        parent::__construct($configuration);
        $this->builderAssistant = $builderAssistant;
        $this->context = $context;
        $this->requiredDatabaseId = $databaseId;
    }

    public function parseStatementNode(): void
    {
        $id = $this->context->getStringAttribute("id");
        $databaseId = $this->context->getStringAttribute("databaseId");

        if (!$this->databaseIdMatchesCurrent($id, $databaseId, $this->requiredDatabaseId)) {
            return;
        }

        $nodeName = $this->context->getNode()->nodeName;
        $sqlCommandType = constant("SqlCommandType::", strtoupper($nodeName));
        $isSelect = $sqlCommandType == SqlCommandType::SELECT;
        $flushCache = $this->context->getBooleanAttribute("flushCache", !$isSelect);
        $useCache = $this->context->getBooleanAttribute("useCache", $isSelect);
        $resultOrdered = $this->context->getBooleanAttribute("resultOrdered", false);

        // Include Fragments before parsing
        $includeParser = new XMLIncludeTransformer($this->configuration, $this->builderAssistant);
        $includeParser->applyIncludes($this->context->getNode());

        $parameterType = $this->context->getStringAttribute("parameterType");
        $parameterTypeClass = $this->resolveClass($parameterType);

        $lang = $this->context->getStringAttribute("lang");
        $langDriver = $this->getLanguageDriver($lang);

        // Parse selectKey after includes and remove them.
        $this->processSelectKeyNodes($id, $parameterTypeClass, $langDriver);

        // Parse the SQL (pre: <selectKey> and <include> were parsed and removed)
        $keyGenerator = null;
        $keyStatementId = $id . SelectKeyGenerator::SELECT_KEY_SUFFIX;
        $keyStatementId = $this->builderAssistant->applyCurrentNamespace($keyStatementId, true);
        if ($this->configuration->hasKeyGenerator($keyStatementId)) {
            $keyGenerator = $this->configuration->getKeyGenerator($keyStatementId);
        } else {
            $keyGenerator = $this->context->getBooleanAttribute(
                "useGeneratedKeys",
                $this->configuration->isUseGeneratedKeys() && SqlCommandType::INSERT == $sqlCommandType
            ) ? DbalKeyGenerator::instance() : NoKeyGenerator::instance();
        }

        $sqlSource = $langDriver->createSqlSource($this->configuration, $this->context, $parameterTypeClass);
        $statementType = constant("SqlCommandType::", strtoupper($this->context->getStringAttribute("statementType", StatementType::PREPARED)));
        $fetchSize = $this->context->getIntAttribute("fetchSize");
        $timeout = $this->context->getIntAttribute("timeout");
        $parameterMap = $this->context->getStringAttribute("parameterMap");
        $resultType = $this->context->getStringAttribute("resultType");
        $resultTypeClass = $this->resolveClass($resultType);
        $resultMap = $this->context->getStringAttribute("resultMap");
        $resultSetType = $this->context->getStringAttribute("resultSetType");
        $resultSetTypeEnum = resolveResultSetType($resultSetType);
        if ($resultSetTypeEnum === null) {
            $resultSetTypeEnum = $this->configuration->getDefaultResultSetType();
        }
        $keyProperty = $this->context->getStringAttribute("keyProperty");
        $keyColumn = $this->context->getStringAttribute("keyColumn");
        $resultSets = $this->context->getStringAttribute("resultSets");

        $this->builderAssistant->addMappedStatement(
            $id,
            $sqlSource,
            $statementType,
            $sqlCommandType,
            $fetchSize,
            $timeout,
            $parameterMap,
            $parameterTypeClass,
            $resultMap,
            $resultTypeClass,
            $resultSetTypeEnum,
            $flushCache,
            $useCache,
            $resultOrdered,
            $keyGenerator,
            $keyProperty,
            $keyColumn,
            $databaseId,
            $langDriver,
            $resultSets
        );
    }

    private function processSelectKeyNodes(string $id, string $parameterTypeClass, LanguageDriverInterface $langDriver): void
    {
        $selectKeyNodes = $this->context->evalNodes("selectKey");
        if ($this->configuration->getDatabaseId() !== null) {
            $this->parseSelectKeyNodes($id, $selectKeyNodes, $parameterTypeClass, $langDriver, $this->configuration->getDatabaseId());
        }
        $this->parseSelectKeyNodes($id, $selectKeyNodes, $parameterTypeClass, $langDriver, null);
        $this->removeSelectKeyNodes($selectKeyNodes);
    }

    private function parseSelectKeyNodes(string $parentId, array $list, string $parameterTypeClass, LanguageDriverInterface $langDriver, string $skRequiredDatabaseId): void
    {
        foreach ($list as $nodeToHandle) {
            $id = $parentId . SelectKeyGenerator::SELECT_KEY_SUFFIX;
            $databaseId = $nodeToHandle->getStringAttribute("databaseId");
            if ($this->databaseIdMatchesCurrent($id, $databaseId, $skRequiredDatabaseId)) {
                $this->parseSelectKeyNode($id, $nodeToHandle, $parameterTypeClass, $langDriver, $databaseId);
            }
        }
    }

    private function parseSelectKeyNode(string $id, XNode $nodeToHandle, string $parameterTypeClass, LanguageDriverInterface $langDriver, string $databaseId): void
    {
        $resultType = $nodeToHandle->getStringAttribute("resultType");
        $resultTypeClass = $this->resolveClass($resultType);
        $statementType = constant("SqlCommandType::", strtoupper($nodeToHandle->getStringAttribute("statementType", StatementType::PREPARED)));
        $keyProperty = $nodeToHandle->getStringAttribute("keyProperty");
        $keyColumn = $nodeToHandle->getStringAttribute("keyColumn");
        $executeBefore = "BEFORE" == $nodeToHandle->getStringAttribute("order", "AFTER");

        // defaults
        $useCache = false;
        $resultOrdered = false;
        $keyGenerator = NoKeyGenerator::instance();
        $fetchSize = null;
        $timeout = null;
        $flushCache = false;
        $parameterMap = null;
        $resultMap = null;
        $resultSetTypeEnum = null;

        $sqlSource = $langDriver->createSqlSource($this->configuration, $nodeToHandle, $parameterTypeClass);
        $sqlCommandType = SqlCommandType::SELECT;

        $this->builderAssistant->addMappedStatement(
            $id,
            $sqlSource,
            $statementType,
            $sqlCommandType,
            $fetchSize,
            $timeout,
            $parameterMap,
            $parameterTypeClass,
            $resultMap,
            $resultTypeClass,
            $resultSetTypeEnum,
            $flushCache,
            $useCache,
            $resultOrdered,
            $keyGenerator,
            $keyProperty,
            $keyColumn,
            $databaseId,
            $langDriver,
            null
        );

        $id = $this->builderAssistant->applyCurrentNamespace($id, false);

        $keyStatement = $this->configuration->getMappedStatement($id, false);
        $this->configuration->addKeyGenerator($id, new SelectKeyGenerator($keyStatement, $executeBefore));
    }

    private function removeSelectKeyNodes(array $selectKeyNodes): void
    {
        foreach ($selectKeyNodes as $nodeToHandle) {
            $nodeToHandle->getParent()->getNode()->removeChild($nodeToHandle->getNode());
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
        $id = $this->builderAssistant->applyCurrentNamespace($id, false);
        if (!$this->configuration->hasStatement($id, false)) {
            return true;
        }
        // skip this statement if there is a previous one with a not null databaseId
        $previous = $this->configuration->getMappedStatement($id, false); // issue #2
        return $previous->getDatabaseId() === null;
    }

    private function getLanguageDriver(?string $lang): LanguageDriverInterface
    {
        $langClass = null;
        if ($lang !== null) {
            $langClass = $this->resolveClass($lang);
        }
        return $this->configuration->getLanguageDriver($langClass);
    }
}
