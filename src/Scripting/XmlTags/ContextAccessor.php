<?php

namespace MyBatis\Scripting\XmlTags;

class ContextAccessor extends ContextMap
{
    public function __construct(private ContextMap $scope)
    {
    }

    public function get($name)
    {
        $result = $this->scope->get($name);
        if ($this->scope->containsKey($name) || $result !== null) {
            return $result;
        }

        $parameterObject = $this->scope->get(DynamicContext::PARAMETER_OBJECT_KEY);

        if (is_array($parameterObject) && array_key_exists($name, $parameterObject)) {
            return $parameterObject[$name];
        }

        if ($parameterObject instanceof \ArrayObject && array_key_exists($name, $parameterObject->getArrayCopy())) {
            if (method_exists($parameterObject, 'get')) {
                return $parameterObject->get($name);
            } else {
                return $parameterObject[$name];
            }
        }

        return $parameterObject;
    }

    public function put(string $name, $value)
    {
        return $this->scope->put($name, $value);
    }

    public function putAll(array $toMerge = []): void
    {
        $this->scope->putAll($toMerge);
    }

    public function clear(): void
    {
        $this->scope->clear();
    }

    public function containsKey($key): bool
    {
        return $this->scope->containsKey($key);
    }

    public function containsValue($value): bool
    {
        return $this->scope->containsValue($value);
    }

    public function entrySet(): array
    {
        return $this->scope->entrySet();
    }
}
