<?php

namespace MyBatis\Scripting\Defaults;

use MyBatis\Builder\SqlSourceBuilder;
use MyBatis\Mapping\{
    BoundSql,
    SqlSourceInterface
};
use MyBatis\Scripting\XmlTags\{
    DynamicContext,
    DynamicSqlSource,
    SqlNodeInterface
};
use MyBatis\Session\Configuration;

class RawSqlSource implements SqlSourceInterface
{
    private $sqlSource;

    public function __construct(Configuration $configuration, /*SqlNodeInterface|string*/$sqlOrRootNode, ?string $parameterType)
    {
        if ($sqlOrRootNode instanceof SqlNodeInterface) {
            $sql = self::getSql($configuration, $sqlOrRootNode);
        } else {
            $sql = $sqlOrRootNode;
        }
        $sqlSourceParser = new SqlSourceBuilder($configuration);
        $clazz = $parameterType === null ? "\\stdClass" : $parameterType;
        $arr = [];
        $this->sqlSource = $sqlSourceParser->parse($sql, $clazz, $arr);
    }

    private static function getSql(Configuration $configuration, SqlNodeInterface $rootSqlNode): string
    {
        $context = new DynamicContext($configuration, null);
        $rootSqlNode->apply($context);
        return $context->getSql();
    }

    public function getBoundSql($parameterObject): BoundSql
    {
         return $this->sqlSource->getBoundSql($parameterObject);
    }
}
