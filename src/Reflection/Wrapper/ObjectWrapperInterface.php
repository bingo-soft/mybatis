<?php

namespace MyBatis\Reflection\Wrapper;

use MyBatis\Reflection\MetaObject;
use MyBatis\Reflection\Property\PropertyTokenizer;

interface ObjectWrapperInterface
{
    public function get(PropertyTokenizer $prop);

    public function set(PropertyTokenizer $prop, &$value): void;

    public function findProperty(string $name, bool $useCamelCaseMapping = false): ?string;

    public function instantiatePropertyValue(string $name, PropertyTokenizer $prop): MetaObject;
}
