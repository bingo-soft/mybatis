<?php

namespace MyBatis\Reflection;

use MyBatis\Reflection\Property\PropertyTokenizer;

class MetaClass extends \ReflectionClass
{
    private $reflector;

    public function __construct($object)
    {
        parent::__construct($object);
        $this->reflector = new MetaReflector($object);
    }

    public function getSetterType(string $name): ?string
    {
        $prop = new PropertyTokenizer($name);
        if ($prop->valid()) {
            $metaProp = $this->metaClassForProperty($prop->getName());
            return $metaProp->getSetterType($prop->getChildren());
        } else {
            return $this->reflector->getSetterType($prop->getName());
        }
    }

    public function getGetterType(/*string|PropertyTokenizer*/$name): ?string
    {
        if (is_string($name)) {
            $prop = new PropertyTokenizer($name);
            if ($prop->valid()) {
                $metaProp = $this->metaClassForProperty($prop->getName());
                return $metaProp->getGetterType($prop->getChildren());
            } else {
                return $this->getGetterType($prop);
            }
        } else {
            $type = $this->reflector->getGetterType($name->getName());
            return $type;
        }
    }

    public function getSetInvoker(string $propertyName)
    {
        return $this->reflector->getSetInvoker($propertyName);
    }

    public function getGetInvoker(string $propertyName)
    {
        return $this->reflector->getGetInvoker($propertyName);
    }

    public function findProperty(string $name, bool $useCamelCaseMapping = false): ?string
    {
        if ($useCamelCaseMapping) {
            $name = str_replace("_", "", $name);
        }
        $builder = "";
        $prop = $this->buildProperty($name, $builder);
        return strlen($prop) > 0 ? $prop : null;
    }

    private function buildProperty(string $name, string &$builder): string
    {
        $prop = new PropertyTokenizer($name);
        if ($prop->valid()) {
            $propertyName = $this->reflector->findPropertyName($prop->getName());
            if ($propertyName !== null) {
                $builder .= $propertyName;
                $builder .= ".";
                $metaProp = $this->metaClassForProperty($propertyName);
                $metaProp->buildProperty($prop->getChildren(), $builder);
            }
        } else {
            $propertyName = $this->reflector->findPropertyName($name);
            if ($propertyName !== null) {
                $builder .= $propertyName;
            }
        }
        return $builder;
    }

    private function metaClassForProperty(/*string|PropertyTokenizer*/$prop): MetaClass
    {
        $propType = $this->getGetterType($prop);
        return new MetaClass($propType);
    }

    public function getGetterNames(): array
    {
        return $this->reflector->getGetablePropertyNames();
    }

    public function getSetterNames(): array
    {
        return $this->reflector->getSetablePropertyNames();
    }

    public function hasSetter(string $name): bool
    {
        $prop = new PropertyTokenizer($name);
        if ($prop->valid()) {
            if ($this->reflector->hasSetter($prop->getName())) {
                $metaProp = $this->metaClassForProperty($prop->getName());
                return $metaProp->hasSetter($prop->getChildren());
            } else {
                return false;
            }
        } else {
            return $this->reflector->hasSetter($prop->getName());
        }
    }

    public function hasGetter(string $name): bool
    {
        $prop = new PropertyTokenizer($name);
        if ($prop->valid()) {
            if ($this->reflector->hasGetter($prop->getName())) {
                $metaProp = $this->metaClassForProperty($prop);
                return $metaProp->hasGetter($prop->getChildren());
            } else {
                return false;
            }
        } else {
            return $this->reflector->hasGetter($prop->getName());
        }
    }
}
