<?php

namespace MyBatis\Mapping;

use MyBatis\Cache\CacheInterface;
use MyBatis\Executor\Keygen\{
    DbalKeyGenerator,
    KeyGeneratorInterface,
    NoKeyGenerator
};
use MyBatis\Scripting\LanguageDriverInterface;
use MyBatis\Session\Configuration;

class MappedStatementBuilder
{
    private $mappedStatement;

    public function __construct(Configuration $configuration, string $id, SqlSourceInterface $sqlSource, string $sqlCommandType)
    {
        $this->mappedStatement = new MappedStatement();
        $this->mappedStatement->configuration = $configuration;
        $this->mappedStatement->id = $id;
        $this->mappedStatement->sqlSource = $sqlSource;
        $this->mappedStatement->statementType = StatementType::PREPARED;
        $this->mappedStatement->resultSetType = ResultSetType::DEFAULT;
        $parameterMappings = [];
        $this->mappedStatement->parameterMap = (new ParameterMapBuilder($configuration, "defaultParameterMap", null, $parameterMappings))->build();
        $this->mappedStatement->resultMaps = [];
        $this->mappedStatement->sqlCommandType = $sqlCommandType;
        $this->mappedStatement->keyGenerator = ($configuration->isUseGeneratedKeys() && SqlCommandType::INSERT == $sqlCommandType) ? DbalKeyGenerator::instance() : NoKeyGenerator::instance();
        /*String logId = id;
        if (configuration.getLogPrefix() != null) {
            logId = configuration.getLogPrefix() + id;
        }
        $this->mappedStatement->statementLog = LogFactory.getLog(logId);*/
        $this->mappedStatement->lang = $configuration->getDefaultScriptingLanguageInstance();
    }

    public function resource(string $resource): MappedStatementBuilder
    {
        $this->mappedStatement->resource = $resource;
        return $this;
    }

    public function id(): string
    {
        return $this->mappedStatement->id;
    }

    public function parameterMap(ParameterMap $parameterMap): MappedStatementBuilder
    {
        $this->mappedStatement->parameterMap = $parameterMap;
        return $this;
    }

    public function resultMaps(array $resultMaps): MappedStatementBuilder
    {
        $this->mappedStatement->resultMaps = $resultMaps;
        foreach ($resultMaps as $resultMap) {
            $this->mappedStatement->hasNestedResultMaps = $this->mappedStatement->hasNestedResultMaps || $resultMap->hasNestedResultMaps();
        }
        return $this;
    }

    public function fetchSize(?int $fetchSize): MappedStatementBuilder
    {
        $this->mappedStatement->fetchSize = $fetchSize;
        return $this;
    }

    public function timeout(?int $timeout): MappedStatementBuilder
    {
        $this->mappedStatement->timeout = $timeout;
        return $this;
    }

    public function statementType(string $statementType): MappedStatementBuilder
    {
        $this->mappedStatement->statementType = $statementType;
        return $this;
    }

    public function resultSetType(?ResultSetType $resultSetType): MappedStatementBuilder
    {
        $this->mappedStatement->resultSetType = $resultSetType === null ? ResultSetType::default() : $resultSetType;
        return $this;
    }

    public function cache(?CacheInterface $cache): MappedStatementBuilder
    {
        $this->mappedStatement->cache = $cache;
        return $this;
    }

    public function flushCacheRequired(bool $flushCacheRequired): MappedStatementBuilder
    {
        $this->mappedStatement->flushCacheRequired = $flushCacheRequired;
        return $this;
    }

    public function useCache(bool $useCache): MappedStatementBuilder
    {
        $this->mappedStatement->useCache = $useCache;
        return $this;
    }

    public function resultOrdered(bool $resultOrdered): MappedStatementBuilder
    {
        $this->mappedStatement->resultOrdered = $resultOrdered;
        return $this;
    }

    public function keyGenerator(KeyGeneratorInterface $keyGenerator): MappedStatementBuilder
    {
        $this->mappedStatement->keyGenerator = $keyGenerator;
        return $this;
    }

    public function keyProperty(?string $keyProperty): MappedStatementBuilder
    {
        $this->mappedStatement->keyProperties = MappedStatement::delimitedStringToArray($keyProperty);
        return $this;
    }

    public function keyColumn(?string $keyColumn): MappedStatementBuilder
    {
        $this->mappedStatement->keyColumns = MappedStatement::delimitedStringToArray($keyColumn);
        return $this;
    }

    public function databaseId(?string $databaseId): MappedStatementBuilder
    {
        $this->mappedStatement->databaseId = $databaseId;
        return $this;
    }

    public function lang(LanguageDriverInterface $driver): MappedStatementBuilder
    {
        $this->mappedStatement->lang = $driver;
        return $this;
    }

    public function resultSets(?string $resultSet): MappedStatementBuilder
    {
        $this->mappedStatement->resultSets = MappedStatement::delimitedStringToArray($resultSet);
        return $this;
    }

    public function build(): MappedStatement
    {
        if (
            $this->mappedStatement->configuration === null ||
            $this->mappedStatement->id === null ||
            $this->mappedStatement->sqlSource === null ||
            $this->mappedStatement->lang === null
        ) {
            throw new \Exception("Invalid mapped statement configuration");
        }
        return $this->mappedStatement;
    }
}
