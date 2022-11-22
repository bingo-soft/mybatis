<?php

namespace MyBatis\Builder;

use MyBatis\Mapping\{
    BoundSql,
    ParameterMapping,
    SqlSourceInterface
};
use MyBatis\Session\Configuration;

class StaticSqlSource implements SqlSourceInterface
{
    private $sql;
    private $parameterMappings;
    private $configuration;

    public function __construct(Configuration $configuration, string $sql, ?array $parameterMappings = [])
    {
        $this->sql = $sql;
        $this->parameterMappings = $parameterMappings;
        $this->configuration = $configuration;
    }

    public function getBoundSql($parameterObject): BoundSql
    {
        return new BoundSql($this->configuration, $this->sql, $this->parameterMappings, $parameterObject);
    }
}
