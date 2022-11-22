<?php

namespace MyBatis\Mapping;

use MyBatis\Builder\InitializingObjectInterface;
use MyBatis\Cache\{
    CacheInterface,
    CacheException
};
use MyBatis\Cache\Decorators\{
    FifoCache,
    LoggingCache,
    LruCache,
    ScheduledCache,
    SerializedCache
};
use MyBatis\Cache\Impl\PerpetualCache;
use Util\Reflection\{
    MetaObject,
    SystemMetaObject
};

class CacheBuilder
{
    private $id;
    private $implementation;
    private $decorators = [];
    private $size;
    //in seconds
    private $clearInterval;
    private $readWrite = false;
    private $properties;
    private $blocking = false;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function implementation(string $implementation): CacheBuilder
    {
        $this->implementation = $implementation;
        return $this;
    }

    public function addDecorator(string $decorator): CacheBuilder
    {
        if ($decorator !== null) {
            $this->decorators[] = $decorator;
        }
        return $this;
    }

    public function size(int $size): CacheBuilder
    {
        $this->size = $size;
        return $this;
    }

    public function clearInterval(?int $clearInterval): CacheBuilder
    {
        $this->clearInterval = $clearInterval;
        return $this;
    }

    public function readWrite(bool $readWrite): CacheBuilder
    {
        $this->readWrite = $readWrite;
        return $this;
    }

    public function blocking(bool $blocking): CacheBuilder
    {
        $this->blocking = $blocking;
        return $this;
    }

    public function properties(array $properties): CacheBuilder
    {
        $this->properties = $properties;
        return $this;
    }

    public function build(): CacheInterface
    {
        $this->setDefaultImplementations();
        $cache = $this->newBaseCacheInstance($this->implementation, $this->id);
        $this->setCacheProperties($cache);

        if (PerpetualCache::class == get_class($cache)) {
            foreach ($this->decorators as $decorator) {
                $cache = $this->newCacheDecoratorInstance($decorator, $cache);
                $this->setCacheProperties($cache);
            }
            $cache = $this->setStandardDecorators($cache);
        }
        return $cache;
    }

    private function setDefaultImplementations(): void
    {
        if ($this->implementation === null) {
            $this->implementation = PerpetualCache::class;
            if (empty($this->decorators)) {
                $this->decorators[] = LruCache::class;
            }
        }
    }

    private function setStandardDecorators(CacheInterface $cache): CacheInterface
    {
        try {
            $metaCache = SystemMetaObject::forObject($cache);
            if ($this->size !== null && $metaCache->hasSetter("size")) {
                $metaCache->setValue("size", $this->size);
            }
            if ($this->clearInterval !== null) {
                $cache = new ScheduledCache($cache);
                $cache->setClearInterval($this->clearInterval);
            }
            if ($this->readWrite) {
                $cache = new SerializedCache($cache);
            }
            return $cache;
        } catch (\Exception $e) {
            throw new CacheException("Error building standard cache decorators.  Cause: " . $e->getMessage());
        }
    }

    private function setCacheProperties(CacheInterface $cache): void
    {
        if (!empty($this->properties)) {
            $metaCache = SystemMetaObject::forObject($cache);
            foreach ($this->properties as $name => $value) {
                if ($metaCache->hasSetter($name)) {
                    $type = $metaCache->getSetterType($name);
                    if ("string" == $type) {
                        $metaCache->setValue($name, $value);
                    } elseif ("int" == $type || "integer" == $type) {
                        $metaCache->setValue($name, intval($value));
                    } elseif ("float" == $type || "double" == $type) {
                        $metaCache->setValue($name, floatval($type));
                    } elseif ("bool" == $type || "boolean" == $type) {
                        $metaCache->setValue($name, Boolean::parseBoolean($value));
                    } else {
                        $metaCache->setValue($name, $value);
                    }
                }
            }
        }
        if (is_a(get_class($cache), InitializingObjectInterface::class, true)) {
            try {
                $cache->initialize();
            } catch (\Exception $e) {
                throw new CacheException("Failed cache initialization for '" . $cache->getId() . "' on '" . get_class($cache) . "'");
            }
        }
    }

    private function newBaseCacheInstance(string $cacheClass, string $id): CacheInterface
    {
        try {
            return new $cacheClass($id);
        } catch (\Exception $e) {
            throw new CacheException("Could not instantiate cache implementation (" . $cacheClass . "). Cause: " . $e->getMessage());
        }
    }

    private function newCacheDecoratorInstance(string $cacheClass, CacheInterface $base): CacheInterface
    {
        try {
            return new $cacheClass($base);
        } catch (\Exception $e) {
            throw new CacheException("Could not instantiate cache decorator (" . $cacheClass . "). Cause: " . $e->getMessage());
        }
    }
}
