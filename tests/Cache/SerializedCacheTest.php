<?php

namespace Tests\Cache;

use MyBatis\Cache\CacheException;
use MyBatis\Cache\Decorators\SerializedCache;
use MyBatis\Cache\Impl\PerpetualCache;
use PHPUnit\Framework\TestCase;

class SerializedCacheTest extends TestCase
{
    public function testShouldDemonstrateSerializedObjectAreEqual(): void
    {
        $cache = new SerializedCache(new PerpetualCache("default"));
        for ($i = 0; $i < 5; $i += 1) {
            $cache->putObject($i, new CachingObject($i));
        }
        for ($i = 0; $i < 5; $i += 1) {
            $this->assertEquals(new CachingObject($i), $cache->getObject($i));
        }
    }

    public function testShouldDemonstrateNullsAreSerializable(): void
    {
        $cache = new SerializedCache(new PerpetualCache("default"));
        for ($i = 0; $i < 5; $i += 1) {
            $cache->putObject($i, null);
        }
        for ($i = 0; $i < 5; $i += 1) {
            $this->assertNull($cache->getObject($i));
        }
    }

    public function testThrowExceptionWhenTryingToCacheNonSerializableObject(): void
    {
        $cache = new SerializedCache(new PerpetualCache("default"));
        $this->expectException(CacheException::class);
        $cache->putObject(0, new CachingObjectWithoutSerializable(0));
    }
}
