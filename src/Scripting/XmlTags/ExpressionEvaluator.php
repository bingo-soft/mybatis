<?php

namespace MyBatis\Scripting\XmlTags;

use MyBatis\Builder\BuilderException;
use MyBatis\Parsing\Boolean;

class ExpressionEvaluator
{
    public function evaluateBoolean(string $expression, $parameterObject): bool
    {
        if (strpos($expression, '${') === false) {
            $expression = '${' .  $expression . '}';
        }
        $value = JuelCache::getValue($expression, $parameterObject);
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return $value != 0;
        }
        if (is_string($value) && (strtolower($value) == 'true' || strtolower($value) == 'false')) {
            return Boolean::parseBoolean($value);
        }
        return !empty($value);
    }

    public function evaluateIterable(string $expression, $parameterObject)
    {
        if (strpos($expression, '${') === false) {
            $expression = '${' .  $expression . '}';
        }
        $value = JuelCache::getValue($expression, $parameterObject);
        if (is_iterable($value) || is_array($value)) {
            return $value;
        }
        throw new BuilderException("Error evaluating expression '" . $expression . "'.  value is not iterable.");
    }
}
