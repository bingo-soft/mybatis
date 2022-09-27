<?php

namespace MyBatis\Reflection\Wrapper;

use MyBatis\Reflection\{
    MetaClass,
    MetaObject,
    NullObject
};
use MyBatis\Reflection\Property\PropertyTokenizer;

class ObjectWrapper extends BaseWrapper
{
    private $object;
    private $metaClass;

    public function __construct(MetaObject $metaObject, $object, ?MetaObject $scope = null, ?string $propertyName = null)
    {
        parent::__construct($metaObject, $scope, $propertyName);
        $this->object = $object;
        $this->metaClass = new MetaClass(get_class($object));
    }

    public function get(PropertyTokenizer $prop)
    {
        if ($prop->getIndex() !== null) {
            $collection = $this->resolveCollection($prop, $this->object);
            return $this->getCollectionValue($prop, $collection);
        } else {
            return $this->getObjectProperty($prop, $this->object);
        }
    }

    public function set(PropertyTokenizer $prop, &$value): void
    {
        if ($prop->getIndex() !== null) {
            $collection = $this->resolveCollection($prop, $this->object);
            $this->setCollectionValue($prop, $collection, $value);
            //collection is not reference (can not get it, because ReflectionProperty does not return reference)
            $metaValue = new MetaObject($this->object);
            $metaValue->setValue($prop->getName(), $collection);
        } else {
            $this->setObjectProperty($prop, $this->object, $value);
        }
    }

    public function findProperty(string $name, bool $useCamelCaseMapping = false): ?string
    {
        return $this->metaClass->findProperty($name, $useCamelCaseMapping);
    }

    public function getGetterNames(): array
    {
        return $this->metaClass->getGetterNames();
    }

    public function getSetterNames(): array
    {
        return $this->metaClass->getSetterNames();
    }

    private function getObjectProperty(PropertyTokenizer $prop, $object)
    {
        $propertyName = $this->findProperty($prop->getName());
        $prop = $this->metaObject->getProperty($propertyName);
        if ($prop->isPrivate() || $prop->isProtected()) {
            $prop->setAccessible(true);
        }
        return $prop->getValue($object);
    }

    private function setObjectProperty(PropertyTokenizer $prop, $object, $value)
    {
        try {
            $method = $this->metaClass->getSetInvoker($prop->getName());
            $params = [ $value ];
            $method->invoke($object, $params);
        } catch (\Exception $t) {
            throw new \ReflectionException("Could not set property '" . $prop->getName() . "'. Cause: " . $t->getMessage());
        }
    }

    public function getSetterType(string $name): ?string
    {
        $prop = new PropertyTokenizer($name);
        if ($prop->valid()) {
            $metaValue = $this->metaObject->metaObjectForProperty($prop->getIndexedName());
            if ($metaValue->getOriginalObject() instanceof NullObject) {
                return $this->metaClass->getSetterType($name);
            } else {
                return $metaValue->getSetterType($prop->getChildren());
            }
        } else {
            return $this->metaClass->getSetterType($name);
        }
    }

    public function getGetterType(string $name): string
    {
        $prop = new PropertyTokenizer($name);
        if ($prop->valid()) {
            $metaValue = $this->metaObject->metaObjectForProperty($prop->getIndexedName());
            if ($metaValue->getOriginalObject() instanceof NullObject) {
                return $this->metaClass->getGetterType($name);
            } else {
                return $metaValue->getGetterType($prop->getChildren());
            }
        } else {
            return $this->metaClass->getGetterType($name);
        }
    }

    public function hasSetter(string $name): bool
    {
        $prop = new PropertyTokenizer($name);
        if ($prop->valid()) {
            if ($this->metaClass->hasSetter($prop->getIndexedName())) {
                $metaValue = $this->metaObject->metaObjectForProperty($prop->getIndexedName());
                if ($metaValue->getOriginalObject() instanceof NullObject) {
                    return $this->metaClass->hasSetter($name);
                } else {
                    return $metaValue->hasSetter($prop->getChildren());
                }
            } else {
                return false;
            }
        } else {
            return $this->metaClass->hasSetter($name);
        }
    }

    public function hasGetter(string $name): bool
    {
        $prop = new PropertyTokenizer($name);
        if ($prop->valid()) {
            if ($this->metaClass->hasGetter($prop->getIndexedName())) {
                $metaValue = $this->metaObject->metaObjectForProperty($prop->getIndexedName());
                if ($metaValue->getOriginalObject() instanceof NullObject) {
                    return $this->metaClass->hasGetter($name);
                } else {
                    return $metaValue->hasGetter($prop->getChildren());
                }
            } else {
                return false;
            }
        } else {
            return $this->metaClass->hasGetter($name);
        }
    }

    public function instantiatePropertyValue(string $name, PropertyTokenizer $prop): MetaObject
    {
        $metaValue = null;
        $type = $this->getSetterType($prop->getName());
        try {
            $newObject = new $type();
            $metaValue = new MetaObject($newObject, null, $prop->getName());
            $this->set($prop, $newObject);
        } catch (\Exception $e) {
            throw new \ReflectionException("Cannot set value of property '" . $name . "'. Cause:" . $e->getMessage());
        }
        return $metaValue;
    }
}
