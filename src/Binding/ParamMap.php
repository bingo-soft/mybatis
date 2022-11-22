<?php

namespace MyBatis\Binding;

class ParamMap extends \ArrayObject
{
    public function get($key)
    {
        if (!array_key_exists($key, $this->getArrayCopy())) {
            throw new BindingException("Parameter '" . $key . "' not found. Available parameters are " . json_encode(array_keys($this->getArrayCopy())));
        }
        return $this[$key];
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
