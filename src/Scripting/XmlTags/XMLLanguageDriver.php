<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Builder\Xml\XMLMapperEntityResolver;
use MyBatis\Executor\Parameter\ParameterHandlerInterface;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement,
    SqlSourceInterface
};
use MyBatis\Parsing\{
    PropertyParser,
    XNode,
    XPathParser
};
use MyBatis\Scripting\LanguageDriverInterface;
use MyBatis\Scripting\Defaults\{
    DefaultParameterHandler,
    RawSqlSource
};
use MyBatis\Session\Configuration;

class XMLLanguageDriver implements LanguageDriverInterface
{
    public function createParameterHandler(MappedStatement $mappedStatement, $parameterObject, BoundSql $boundSql): ParameterHandlerInterface
    {
        return new DefaultParameterHandler($mappedStatement, $parameterObject, $boundSql);
    }

    public function createSqlSource(Configuration $configuration, /*XNode|string*/$script, ?string $parameterType): SqlSourceInterface
    {
        if ($script instanceof XNode) {
            $builder = new XMLScriptBuilder($configuration, $script, $parameterType);
            return $builder->parseScriptNode();
        } else {
            if (strpos($script, "<script>") === 0) {
                $parser = new XPathParser($script, false, $configuration->getVariables(), new XMLMapperEntityResolver());
                return $this->createSqlSource($configuration, $parser->evalNode("/script"), $parameterType);
            } else {
                $script = PropertyParser::parse($script, $configuration->getVariables());
                $textSqlNode = new TextSqlNode($script);
                if ($textSqlNode->isDynamic()) {
                    return new DynamicSqlSource($configuration, $textSqlNode);
                } else {
                    return new RawSqlSource($configuration, $script, $parameterType);
                }
            }
        }
    }
}
