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

class MappedStatement
{
    public $resource;
    public $configuration;
    public $id;
    public $fetchSize;
    public $timeout;
    public $statementType;
    public $resultSetType;
    public $sqlSource;
    public $cache;
    public $parameterMap;
    public $resultMaps;
    public $flushCacheRequired = false;
    public $useCache = false;
    public $resultOrdered = false;
    public $sqlCommandType;
    public $keyGenerator;
    public $keyProperties = [];
    public $keyColumns = [];
    public $hasNestedResultMaps = false;
    public $databaseId;
    //private Log statementLog;
    public $lang;
    public $resultSets = [];

    public function getKeyGenerator(): KeyGeneratorInterface
    {
        return $this->keyGenerator;
    }

    public function getSqlCommandType(): string
    {
        return $this->sqlCommandType;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function hasNestedResultMaps(): bool
    {
        return $this->hasNestedResultMaps;
    }

    public function getFetchSize(): int
    {
        return $this->fetchSize;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getStatementType(): string
    {
        return $this->statementType;
    }

    public function getResultSetType(): ResultSetType
    {
        return $this->resultSetType;
    }

    public function getSqlSource(): SqlSourceInterface
    {
        return $this->sqlSource;
    }

    public function getParameterMap(): ParameterMap
    {
        return $this->parameterMap;
    }

    public function getResultMaps(): array
    {
         return $this->resultMaps;
    }

    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }

    public function isFlushCacheRequired(): bool
    {
        return $this->flushCacheRequired;
    }

    public function isUseCache(): bool
    {
        return $this->useCache;
    }

    public function isResultOrdered(): bool
    {
        return $this->resultOrdered;
    }

    public function getDatabaseId(): ?string
    {
        return $this->databaseId;
    }

    public function getKeyProperties(): array
    {
        return $this->keyProperties;
    }

    public function getKeyColumns(): array
    {
        return $this->keyColumns;
    }

    /*public Log getStatementLog() {
        return statementLog;
    }*/

    public function getLang(): LanguageDriverInterface
    {
        return $this->lang;
    }

    public function getResultSets(): array
    {
        return $this->resultSets;
    }

    public function getBoundSql($parameterObject): BoundSql
    {
        $boundSql = $this->sqlSource->getBoundSql($parameterObject);
        $parameterMappings = $boundSql->getParameterMappings();
        if (empty($parameterMappings)) {
            $boundSql = new BoundSql($this->configuration, $boundSql->getSql(), $this->parameterMap->getParameterMappings(), $parameterObject);
        }

        // check for nested result maps in parameter mappings (issue #30)
        foreach ($boundSql->getParameterMappings() as $pm) {
            $rmId = $pm->getResultMapId();
            if ($rmId !== null) {
                $rm = $this->configuration->getResultMap($rmId);
                if ($rm !== null) {
                    $this->hasNestedResultMaps |= $rm->hasNestedResultMaps();
                }
            }
        }

        return $boundSql;
    }

    public static function delimitedStringToArray(?string $in): array
    {
        if (empty($in)) {
            return [];
        } else {
            return explode(",", $in);
        }
    }
}
