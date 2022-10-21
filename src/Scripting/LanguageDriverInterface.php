<?php

namespace MyBatis\Scripting;

use MyBatis\Executor\Parameter\ParameterHandlerInterface;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement,
    SqlSourceInterface
};
use MyBatis\Parsing\XNode;
use MyBatis\Session\Configuration;

interface LanguageDriverInterface
{
    public function createParameterHandler(MappedStatement $mappedStatement, $parameterObject, BoundSql $boundSql): ParameterHandlerInterface;

    public function createSqlSource(Configuration $configuration, /*XNode|string*/$script, string $parameterType): SqlSourceInterface;
}
