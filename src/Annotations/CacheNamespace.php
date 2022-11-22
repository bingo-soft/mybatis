<?php

namespace MyBatis\Annotations;

use Attribute;
use MyBatis\Cache\Decorators\LruCache;
use MyBatis\Cache\Impl\PerpetualCache;

#[Attribute(Attribute::TARGET_CLASS)]
class CacheNamespace
{
    public function __construct(
        private string $implementation = PerpetualCache::class,
        private string $eviction = LruCache::class,
        private int $flushInterval = 0,
        private int $size = 1024,
        private bool $blocking = false,
        private bool $readWrite = true,
        private array $properties = []
    ) {
    }

    public function implementation(): string
    {
        return $this->implementation;
    }

    public function eviction(): string
    {
        return $this->eviction;
    }

    public function flushInterval(): int
    {
        return $this->flushInterval;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function blocking(): bool
    {
        return $this->blocking;
    }

    public function readWrite(): bool
    {
        return $this->readWrite;
    }

    public function properties(): array
    {
        return $this->properties;
    }
}
