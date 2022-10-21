<?php

namespace MyBatis\Executor;

use MyBatis\Cache\CacheKey;
use MyBatis\Cache\Impl\PerpetualCache;
use Util\Reflection\MetaObject;
use MyBatis\Session\Configuration;

class DeferredLoad
{
    private $resultObject;
    private $property;
    private $targetType;
    private $key;
    private $localCache;
    private $resultExtractor;

    // issue #781
    public function __construct(
        MetaObject $resultObject,
        string $property,
        CacheKey $key,
        PerpetualCache $localCache,
        Configuration $configuration,
        string $targetType
    ) {
        $this->resultObject = $resultObject;
        $this->property = $property;
        $this->key = $key;
        $this->localCache = $localCache;
        $this->resultExtractor = new ResultExtractor($configuration);
        $this->targetType = $targetType;
    }

    public function canLoad(): bool
    {
        return $this->localCache->getObject($this->key) !== null && $this->localCache->getObject($key) !== ExecutionPlaceholder::EXECUTION_PLACEHOLDER;
    }

    public function load(): void
    {
        // we suppose we get back a List
        $list = $this->localCache->getObject($this->key);
        $value = $this->resultExtractor->extractObjectFromList($list, $this->targetType);
        $this->resultObject->setValue($this->property, $value);
    }
}
