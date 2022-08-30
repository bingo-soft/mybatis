<?php

namespace MyBatis\Reflection\Property;

class PropertyNamer
{
    private function __construct()
    {
    }

    public static function methodToProperty(string $name): string
    {
        if (strpos($name, "is") === 0) {
            $name = substr($name, 2);
        } elseif (strpos($name, "get") === 0 || strpos($name, "set") === 0) {
            $name = substr($name, 3);
        } else {
            throw new \ReflectionException("Error parsing property name '" . $name . "'.  Didn't start with 'is', 'get' or 'set'.");
        }

        if (strlen($name) == 1 || (strlen($name) > 1 && !ctype_upper($name[1]))) {
            $name = strtolower(substr($name, 0, 1)) . substr($name, 1);
        }

        return $name;
    }

    public static function isProperty(string $name): bool
    {
        return self::isGetter($name) || self::isSetter($name);
    }

    public static function isGetter(string $name): bool
    {
        return (strpos($name, "get") === 0 && strlen($name) > 3) || (strpos($name, "is") === 0 && strlen($name) > 2);
    }

    public static function isSetter(string $name): bool
    {
        return strpos($name, "set") === 0 && strlen($name) > 3;
    }
}
