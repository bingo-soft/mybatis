<?php

namespace MyBatis\Util;

class MapUtil
{
    public static function &computeIfAbsent(array &$map, $key, $mappingFunction)
    {
        if (array_key_exists($key, $map) && $map[$key] !== null) {
            return $map[$key];
        }
        $map[$key] = $mappingFunction();
        return $map[$key];
    }
}
