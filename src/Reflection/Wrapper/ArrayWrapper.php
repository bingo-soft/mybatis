<?php

namespace MyBatis\Reflection\Wrapper;

use MyBatis\Reflection\{
    MetaObject,
    NullObject
};
use MyBatis\Reflection\Property\PropertyTokenizer;

class ArrayWrapper extends BaseWrapper
{
    private $map;
    private $id;

    public function __construct(MetaObject $metaObject, array &$map, ?MetaObject $scope = null, ?string $propertyName = null)
    {
        parent::__construct($metaObject, $scope, $propertyName);
        $this->map = &$map;
        $this->id = hrtime(true);
    }

    public function get(PropertyTokenizer $prop)
    {
        if ($prop->getIndex() !== null) {
            $collection = $this->resolveCollection($prop, $this->map);
            return $this->getCollectionValue($prop, $collection);
        } else {
            $key = $prop->getName();
            if (array_key_exists($key, $this->map)) {
                return $this->map[$key];
            }
            return null;
        }
    }

    public function set(PropertyTokenizer $prop, &$value): void
    {
        if ($prop->getIndex() !== null) {
            $collection = $this->resolveCollection($prop, $this->map);
            $this->setCollectionValue($prop, $collection, $value);
        } else {
            $this->map[$prop->getName()] = &$value;
            if ($this->scope !== null && $this->propertyName !== null) {
                $this->scope->setValue($this->propertyName, $this->map);
            }
        }
    }

    public function findProperty(string $name, bool $useCamelCaseMapping = false): ?string
    {
        return $name;
    }

    public function getGetterNames(): array
    {
        return array_keys($this->map);
    }

    public function getSetterNames(): array
    {
        return array_keys($this->map);
    }

    public function add($element): void
    {
        $this->map[] = $element;
    }

    public function addAll(array $element): void
    {
        $this->map = array_merge($this->map, $element);
    }

    public function hasSetter(string $name): bool
    {
        return true;
    }

    public function hasGetter(string $name): bool
    {
        $prop = new PropertyTokenizer($name);
        if ($prop->valid()) {
            if (array_key_exists($prop->getIndexedName(), $this->map)) {
                $metaValue = $this->metaObject->metaObjectForProperty($prop->getIndexedName());
                if ($metaValue->getOriginalObject() instanceof NullObject) {
                    return true;
                } else {
                    return $metaValue->hasGetter($prop->getChildren());
                }
            } else {
                return false;
            }
        } else {
            return array_key_exists($prop->getName(), $this->map);
        }
    }

    public function instantiatePropertyValue(string $name, PropertyTokenizer $prop): MetaObject
    {
        $map = [];
        $this->set($prop, $map);
        return new MetaObject($map, null, $prop->getName());
    }

    public function isCollection(): bool
    {
        return true;
    }
}
