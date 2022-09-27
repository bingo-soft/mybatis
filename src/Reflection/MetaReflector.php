<?php

namespace MyBatis\Reflection;

use MyBatis\Reflection\Invoker\{
    GetFieldInvoker,
    InvokerInterface,
    MethodInvoker,
    SetFieldInvoker
};
use MyBatis\Reflection\Property\PropertyNamer;
use MyBatis\Util\MapUtil;

class MetaReflector
{
    private $type;
    private $readablePropertyNames = [];
    private $writablePropertyNames = [];
    private $setMethods = [];
    private $getMethods = [];
    private $setTypes = [];
    private $getTypes = [];
    private $caseInsensitivePropertyMap = [];

    public function __construct($clazz)
    {
        $this->type = new \ReflectionClass($clazz);
        $classMethods = $this->type->getMethods();
        $this->addGetMethods($classMethods);
        $this->addSetMethods($classMethods);
        $this->addFields();
        $this->readablePropertyNames = array_keys($this->getMethods);
        $this->writablePropertyNames = array_keys($this->setMethods);
        foreach ($this->readablePropertyNames as $propName) {
            $this->caseInsensitivePropertyMap[strtoupper($propName)] = $propName;
        }
    }

    private function addGetMethods(array $classMethods): void
    {
        $conflictingGetters = [];
        foreach ($classMethods as $method) {
            if (PropertyNamer::isGetter($method->name)) {
                $this->addMethodConflict($conflictingGetters, PropertyNamer::methodToProperty($method->name), $method);
            }
        }
        $this->resolveGetterConflicts($conflictingGetters);
    }

    private function addSetMethods(array $classMethods): void
    {
        $conflictingGetters = [];
        foreach ($classMethods as $method) {
            if (PropertyNamer::isSetter($method->name)) {
                $this->addMethodConflict($conflictingGetters, PropertyNamer::methodToProperty($method->name), $method);
            }
        }
        $this->resolveSetterConflicts($conflictingGetters);
    }

    private function addMethodConflict(array &$conflictingMethods, string $name, \ReflectionMethod $method): void
    {
        $list = &MapUtil::computeIfAbsent($conflictingMethods, $name, function () {
            return [];
        });
        $list[] = $method;
    }

    private function resolveGetterConflicts(array $conflictingGetters): void
    {
        foreach ($conflictingGetters as $propName => $value) {
            $winner = null;
            $isAmbiguous = false;
            foreach ($value as $candidate) {
                if ($winner === null) {
                    $winner = $candidate;
                    continue;
                }
                $winnerType = null;
                $candidateType = null;
                $winnerTypeRef = $winner->getReturnType();
                $candidateTypeRef = $candidate->getReturnType();
                if ($winnerTypeRef instanceof \ReflectionNamedType && $candidateTypeRef instanceof \ReflectionNamedType) {
                    $winnerType = $winnerTypeRef->getName();
                    $candidateType = $candidateTypeRef->getName();
                }

                if ($candidateType == $winnerType) {
                    if ($candidateType != 'boolean') {
                        $isAmbiguous = true;
                        break;
                    } elseif (strpos($candidate->getName(), "is") === 0) {
                        $winner = $candidate;
                    }
                } elseif (class_exists($winnerType) && class_exists($candidateType) && is_a($winnerType, $candidateType, true)) {
                    // OK getter type is descendant
                } elseif (class_exists($winnerType) && class_exists($candidateType) && is_a($candidateType, $winnerType, true)) {
                    $winner = $candidate;
                } else {
                    $isAmbiguous = true;
                    break;
                }
            }
            $this->addGetMethod($propName, $winner, $isAmbiguous);
        }
    }

    private function resolveSetterConflicts(array $conflictingSetters): void
    {
        foreach ($conflictingSetters as $key => $value) {
            $this->addSetMethod($key, $value[0]);
        }
    }

    private function addGetMethod(string $name, \ReflectionMethod $method, bool $isAmbiguous): void
    {
        $invoker = $isAmbiguous
            ? new AmbiguousMethodInvoker($method, "Illegal getter method $name")
            : new MethodInvoker($method);
        $this->getMethods[$name] = $invoker;

        $type = null;
        $typeRef = $method->getReturnType();
        if ($typeRef instanceof \ReflectionNamedType) {
            $type = $typeRef->getName();
        }
        $this->getTypes[$name] = $type;
    }

    private function addSetMethod(string $name, \ReflectionMethod $method): void
    {
        $invoker = new MethodInvoker($method);
        $this->setMethods[$name] = $invoker;

        $type = null;
        $params = $method->getParameters();
        $refType = $params[0]->getType();
        if ($refType instanceof \ReflectionNamedType) {
            $type = $refType->getName();
        }
        $this->setTypes[$name] = $type;
    }

    private function addFields(): void
    {
        $properties = $this->type->getProperties();
        foreach ($properties as $prop) {
            if (!$prop->isStatic()) {
                if (!array_key_exists($prop->name, $this->getMethods)) {
                    $this->addGetField($prop);
                }
                if (!array_key_exists($prop->name, $this->setMethods)) {
                    $this->addSetField($prop);
                }
            }
        }
    }

    private function addGetField(\ReflectionProperty $field): void
    {
        $this->getMethods[$field->name] = new GetFieldInvoker($field);
        $type = null;
        $refType = $field->getType();
        if ($refType instanceof \ReflectionNamedType) {
            $type = $refType->getName();
        }
        $this->getTypes[$field->name] = $type;
    }

    private function addSetField(\ReflectionProperty $field): void
    {
        $this->setMethods[$field->name] = new SetFieldInvoker($field);
        $type = null;
        $refType = $field->getType();
        if ($refType instanceof \ReflectionNamedType) {
            $type = $refType->getName();
        }
        $this->setTypes[$field->name] = $type;
    }

    public function getSetInvoker(string $propertyName)
    {
        if (array_key_exists($propertyName, $this->setMethods)) {
            return $this->setMethods[$propertyName];
        } else {
            throw new \ReflectionException("There is no setter for property named '" . $propertyName . "' in '" . $this->type . "'");
        }
    }

    public function getGetInvoker(string $propertyName)
    {
        if (array_key_exists($propertyName, $this->getMethods)) {
            return $this->getMethods[$propertyName];
        } else {
            throw new \ReflectionException("There is no getter for property named '" . $propertyName . "' in '" . $this->type . "'");
        }
    }

    public function getGetablePropertyNames(): array
    {
        return $this->readablePropertyNames;
    }

    public function getSetablePropertyNames(): array
    {
        return $this->writablePropertyNames;
    }

    public function hasSetter(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->setMethods);
    }

    public function hasGetter(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->getMethods);
    }

    public function findPropertyName(string $name): ?string
    {
        $key = strtoupper($name);
        if (array_key_exists($key, $this->caseInsensitivePropertyMap)) {
            return $this->caseInsensitivePropertyMap[$key];
        }
        return null;
    }

    public function getSetterType(string $propertyName)
    {
        if (array_key_exists($propertyName, $this->setTypes)) {
            return $this->setTypes[$propertyName];
        } else {
            throw new \ReflectionException("There is no setter for property named '" . $propertyName . "' in '" . $this->type . "'");
        }
    }

    public function getGetterType(string $propertyName)
    {
        if (array_key_exists($propertyName, $this->getTypes)) {
            return $this->getTypes[$propertyName];
        } else {
            throw new \ReflectionException("There is no getter for property named '" . $propertyName . "' in '" . $this->type . "'");
        }
    }
}
