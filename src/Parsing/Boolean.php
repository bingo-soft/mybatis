<?php

namespace MyBatis\Parsing;

class Boolean
{
    public static function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) && ($value == 0 || $value == 1)) {
            return boolval($value);
        }
        if (is_string($value)) {
            $value = strtolower($value);
            return $value == "true";
        }
        return false;
    }
}
