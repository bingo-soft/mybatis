<?php

namespace MyBatis\Executor\Loader;

use Util\Reflection\MetaObject;

class ResultLoaderMap
{
    private $loaderMap = [];

    public function addLoader(string $property, MetaObject $metaResultObject, ResultLoader $resultLoader): void
    {
        $upperFirst = strtoupper($property);
        if (strtolower($upperFirst) != strtolower($property) && array_key_exists($upperFirst, $this->loaderMap)) {
            throw new ExecutorException(
                "Nested lazy loaded result property '" . $property
                . "' for query id '" . $resultLoader->mappedStatement->getId()
                . " already exists in the result map. The leftmost property of all lazy loaded properties must be unique within a result map."
            );
        }
        $this->loaderMap[$upperFirst] = new LoadPair($property, $metaResultObject, $resultLoader);
    }

    public function getProperties(): array
    {
        return [ $this->loaderMap ];
    }

    public function getPropertyNames(): array
    {
        return array_keys($this->loaderMap);
    }

    public function size(): int
    {
        return count($this->loaderMap);
    }

    public function hasLoader(string $property): bool
    {
        return array_key_exists(strtoupper($property), $this->loaderMap);
    }

    public function load(/*string*/$property = null)
    {
        if ($this->hasLoader($property)) {
            $key = strtoupper($property);
            $pair = $this->loaderMap[$key];
            $pair->load();
            unset($this->loaderMap[$key]);
            return true;
        }
        return false;
    }

    public function remove(string $property): void
    {
        if ($this->hasLoader($property)) {
            $key = strtoupper($property);
            unset($this->loaderMap[$key]);
        }
    }

    public function loadAll(): void
    {
        $methodNames = array_keys($this->loaderMap);
        foreach ($methodNames as $methodName) {
            $this->load($methodName);
        }
    }
}
