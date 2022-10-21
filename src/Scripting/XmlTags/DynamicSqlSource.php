<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Builder\SqlSourceBuilder;
use MyBatis\Mapping\{
    BoundSql,
    SqlSourceInterface
};
use MyBatis\Session\Configuration;

class DynamicSqlSource implements SqlSourceInterface
{
    private $configuration;
    private $rootSqlNode;

    public function __construct(Configuration $configuration, SqlNodeInterface $rootSqlNode)
    {
        $this->configuration = $configuration;
        $this->rootSqlNode = $rootSqlNode;
    }

    public function getBoundSql($parameterObject): BoundSql
    {
        $context = new DynamicContext($this->configuration, $parameterObject);
        $this->rootSqlNode->apply($context);
        $sqlSourceParser = new SqlSourceBuilder($this->configuration);
        $parameterType = $parameterObject == null ? "object" : get_class($parameterObject);
        $sqlSource = $sqlSourceParser->parse($context->getSql(), $parameterType, $context->getBindings());
        $boundSql = $sqlSource->getBoundSql($parameterObject);
        foreach ($context->getBindings() as $key => $value) {
            $boundSql->setAdditionalParameter($key, $value);
        }
        return $boundSql;
    }
}
