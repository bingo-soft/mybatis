<?php

namespace MyBatis\Reflection\Wrapper;

use MyBatis\Reflection\MetaObject;
use MyBatis\Reflection\Property\PropertyTokenizer;

abstract class BaseWrapper implements ObjectWrapperInterface
{
    protected static $NO_ARGUMENTS = [];
    protected $metaObject;
    protected $scope;
    protected $propertyName;

    public function __construct(MetaObject $metaObject, ?MetaObject $scope = null, ?string $propertyName = null)
    {
        $this->metaObject = $metaObject;
        $this->scope = $scope;
        $this->propertyName = $propertyName;
    }

    protected function resolveCollection(PropertyTokenizer $prop, &$object)
    {
        if ("" == $prop->getName()) {
            return $object;
        } else {
            return $this->metaObject->getValue($prop->getName());
        }
    }

    protected function getCollectionValue(PropertyTokenizer $prop, &$collection)
    {
        if (is_array($collection)) {
            $i = $prop->getIndex();
            if (array_key_exists($i, $collection)) {
                return $collection[$i];
            }
            return null;
        } else {
            throw new \ReflectionException("The '" . $prop->getName() . "' property ois not an array.");
        }
    }

    protected function setCollectionValue(PropertyTokenizer $prop, array &$collection, $value): void
    {
        if (is_array($collection)) {
            $collection[$prop->getIndex()] = $value;
        } else {
            throw new \ReflectionException("The '" . $prop->getName() . "' property is not an array.");
        }
    }
}
