<?php

namespace MyBatis\Builder;

use MyBatis\Mapping\SqlSourceInterface;
use MyBatis\Parsing\GenericTokenParser;
use MyBatis\Session\Configuration;

class SqlSourceBuilder extends BaseBuilder
{
    private const PARAMETER_PROPERTIES = "phpType,dbalType,mode,numericScale,resultMap,typeHandler,dbalTypeName";

    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);
    }

    public function parse(string $originalSql, string $parameterType, array $additionalParameters): SqlSourceInterface
    {
        $handler = new ParameterMappingTokenHandler($this->configuration, $parameterType, $additionalParameters);
        $parser = new GenericTokenParser("#{", "}", $handler);
        $sql = null;
        if ($this->configuration->isShrinkWhitespacesInSql()) {
            $sql = $parser->parse(self::removeExtraWhitespaces($originalSql));
        } else {
            $sql = $parser->parse($originalSql);
        }
        return new StaticSqlSource($this->configuration, $sql, $handler->getParameterMappings());
    }

    public static function removeExtraWhitespaces(string $original): string
    {
        return preg_replace("/\s+/", " ", $original);
    }
}
