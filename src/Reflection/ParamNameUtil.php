<?php

namespace MyBatis\Reflection;

class ParamNameUtil
{
    public static function getParamNames(\ReflectionMethod $method): array
    {
        return array_map(function (\ReflectionParameter $param) {
            return $param->name;
        }, $method->getParameters());
    }

    private function __construct()
    {
    }
}
