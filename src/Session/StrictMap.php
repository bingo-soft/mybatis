<?php

namespace MyBatis\Session;

class StrictMap extends \ArrayObject
{
    public function __construct(private string $name, array $data = [])
    {
        parent::__construct($data);
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->getArrayCopy())) {
            return $this[$key];
        }
        throw new \Exception($this->name . " does not contain value for " . $key);
    }

    public function put(string $key, $value)
    {
        if ($this->containsKey($key)) {
            //throw new \Exception($this->name . " already contains value for " . $key);
            return;
        }
        if (strpos($key, ".") !== false) {
            $shortKey = $this->getShortName($key);
            if (!$this->containsKey($shortKey)) {
                $this[$shortKey] = $value;
            } else {
                $this[$shortKey] = new Ambiguity($shortKey);
            }
        }
        return $this[$key] = $value;
    }

    public function containsKey($key): bool
    {
        return array_key_exists($key, $this->getArrayCopy());
    }

    private function getShortName(string $key): ?string
    {
        $keyParts = explode('.', $key);
        return $keyParts[count($keyParts) - 1];
    }
}
