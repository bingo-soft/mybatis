<?php

namespace Tests\Mapping;

use PHPUnit\Framework\TestCase;
use MyBatis\Cache\{
    CacheInterface,
    CacheException
};
use MyBatis\Mapping\CacheBuilder;

class CacheBuilderTest extends TestCase
{
    public function testInitializing(): void
    {
        $cache = (new CacheBuilder("test"))->implementation(InitializingCache::class)->build();
        $this->assertTrue($cache->initialized);
    }

    public function testInitializingFailure(): void
    {
        $this->expectException(CacheException::class);
        $cache = (new CacheBuilder("test"))->implementation(InitializingFailureCache::class)->build();
    }
}
