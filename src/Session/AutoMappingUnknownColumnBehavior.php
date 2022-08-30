<?php

namespace MyBatis\Session;

use MyBatis\Mapping\MappedStatement;

abstract class AutoMappingUnknownColumnBehavior
{
    private static $NONE;
    private static $WARNING;
    private static $FAILING;

    public static function none(): AutoMappingUnknownColumnBehavior
    {
        if (self::$NONE === null) {
            self::$NONE = new class () extends AutoMappingUnknownColumnBehavior
            {
                public function doAction(MappedStatement $mappedStatement, string $columnName, string $propertyName, string $propertyType = null): void
                {
                }
            };
        }
        return self::$NONE;
    }

    public static function warning(): AutoMappingUnknownColumnBehavior
    {
        if (self::$WARNING === null) {
            self::$WARNING = new class () extends AutoMappingUnknownColumnBehavior
            {
                public function doAction(MappedStatement $mappedStatement, string $columnName, string $propertyName, string $propertyType = null): void
                {
                    //Logger
                }
            };
        }
        return self::$WARNING;
    }

    public static function failing(): AutoMappingUnknownColumnBehavior
    {
        if (self::$FAILING === null) {
            self::$FAILING = new class () extends AutoMappingUnknownColumnBehavior
            {
                public function doAction(MappedStatement $mappedStatement, string $columnName, string $propertyName, string $propertyType = null): void
                {
                    throw new \Exception(self::buildMessage($mappedStatement, $columnName, $propertyName, $propertyType));
                }
            };
        }
        return self::$FAILING;
    }

    private static function buildMessage(MappedStatement $mappedStatement, string $columnName, string $property, string $propertyType = null)
    {
        return "Unknown column is detected on '"
           . $mappedStatement->getId()
           . "' auto-mapping. Mapping parameters are "
           . "["
           . "columnName=" . $columnName
           . ",propertyName=" . $property
           . ",propertyType=" . $propertyType
           . "]";
    }

    abstract public function doAction(MappedStatement $mappedStatement, string $columnName, string $propertyName, string $propertyType = null): void;
}
