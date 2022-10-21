<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Session\Configuration;

class DynamicContext
{
    public const PARAMETER_OBJECT_KEY = "_parameter";
    public const DATABASE_ID_KEY = "_databaseId";

    private $bindings;
    private $sqlBuilder = "";
    private $uniqueNumber = 0;

    public function __construct(Configuration $configuration, $parameterObject = null)
    {
        if ($parameterObject !== null && !(is_array($parameterObject))) {
            $metaObject = $configuration->newMetaObject($parameterObject);
            $existsTypeHandler = $configuration->getTypeHandlerRegistry()->hasTypeHandler(get_class($parameterObject));
            $this->bindings = new ContextMap($metaObject, $existsTypeHandler);
        } else {
            $this->bindings = new ContextMap(null, false);
        }
        $this->bindings->put(self::PARAMETER_OBJECT_KEY, $parameterObject);
        $this->bindings->put(self::DATABASE_ID_KEY, $configuration->getDatabaseId());
    }

    public function getBindings(): ContextMap
    {
        return $this->bindings;
    }

    public function bind(string $name, $value): void
    {
        $this->bindings->put($name, $value);
    }

    public function appendSql(?string $sql): void
    {
        $this->sqlBuilder = implode(" ", [ $this->sqlBuilder, $sql ]);
    }

    public function getSql(): string
    {
        return trim($this->sqlBuilder);
    }

    public function getUniqueNumber(): int
    {
        return $this->uniqueNumber++;
    }
}
