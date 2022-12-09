<?php

namespace MyBatis\Scripting\XmlTags;

use Util\Reflection\MetaObject;

class ContextMap extends \ArrayObject
{
    private $parameterMetaObject;
    private $fallbackParameterObject;

    public function __construct(/*MetaObject*/$parameterMetaObject = null, bool $fallbackParameterObject = false)
    {
        if (is_array($parameterMetaObject) || is_iterable($parameterMetaObject)) {
            parent::__construct($parameterMetaObject);
        } else {
            $this->parameterMetaObject = $parameterMetaObject;
        }
        $this->fallbackParameterObject = $fallbackParameterObject;
    }

    public function put(string $name, $value)
    {
        return $this[$name] = $value;
    }

    public function putAll(array $toMerge = []): void
    {
        foreach ($toMerge as $key => $value) {
            $this->put($key, $value);
        }
    }

    public function clear(): void
    {
        foreach ($this as $key => $value) {
            unset($this[$key]);
        }
    }

    public function containsKey($key): bool
    {
        return array_key_exists($key, $this->getArrayCopy());
    }

    public function containsValue($value): bool
    {
        return in_array($value, $this->getArrayCopy());
    }

    public function entrySet(): array
    {
        return $this->getArrayCopy();
    }

    public function get($key)
    {
        $result = $this->getValue($key);

        if ($this->containsKey($key) || $result !== null) {
            return $result;
        }

        $parameterObject = $this->getValue(DynamicContext::PARAMETER_OBJECT_KEY);
        if (is_array($parameterObject) && array_key_exists($key, $parameterObject)) {
            return $parameterObject[$key];
        }

        if (!is_object($parameterObject) && !is_array($parameterObject)) {
            return $parameterObject;
        }

        return null;
    }

    private function getValue($key)
    {
        if ($this->containsKey($key)) {
            return $this[$key];
        }

        if (
            $this->containsKey(DynamicContext::PARAMETER_OBJECT_KEY)
            && is_array($map = $this[DynamicContext::PARAMETER_OBJECT_KEY])
            && array_key_exists($key, $map)
        ) {
            return $map[$key];
        }

        if ($this->parameterMetaObject == null) {
            return null;
        }

        if ($this->fallbackParameterObject && !$this->parameterMetaObject->hasGetter($key)) {
            return $this->parameterMetaObject->getOriginalObject();
        } elseif ($this->parameterMetaObject->hasGetter($key)) {
            return $this->parameterMetaObject->getValue($key);
        }
        return null;
    }

    public function isEmpty(): bool
    {
        return empty($this->getArrayCopy());
    }

    public function keySet(): array
    {
        return array_keys($this->getArrayCopy());
    }

    public function remove($key)
    {
        if ($this->containsKey($key)) {
            $value = $this[$key];
            unset($this[$key]);
            return $value;
        }
        return null;
    }

    public function size(): int
    {
        return count($this->getArrayCopy());
    }

    public function values(): array
    {
        return array_values($this->getArrayCopy());
    }
}
