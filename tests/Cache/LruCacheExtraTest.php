<?php

namespace Tests\Cache;

use MyBatis\Cache\Decorators\LruCache;
use MyBatis\Cache\Impl\PerpetualCache;
use PHPUnit\Framework\TestCase;

class LruCacheExtraTest extends TestCase
{
    public function testShouldRemoveLeastRecentlyUsedItemInBeyondFiveEntries(): void
    {
        $cache = new LruCache(new PerpetualCache("default"));
        $cache->setSize(5);
        for ($i = 0; $i < 5; $i += 1) {
            $cache->putObject($i, $i);
        }
        $this->assertEquals(0, $cache->getObject(0));
        $cache->putObject(5, 5);
        $this->assertNull($cache->getObject(1));
        $this->assertEquals(5, $cache->getSize());
    }

    public function testShouldRemoveItemOnDemand(): void
    {
        $cache = new LruCache(new PerpetualCache("default"));
        $cache->putObject(0, 0);
        $this->assertNotNull($cache->getObject(0));
        $cache->removeObject(0);
        $this->assertNull($cache->getObject(0));
    }

    public function testShouldFlushAllItemsOnDemand(): void
    {
        $cache = new LruCache(new PerpetualCache("default"));
        for ($i = 0; $i < 5; $i += 1) {
            $cache->putObject($i, $i);
        }
        $this->assertNotNull($cache->getObject(0));
        $this->assertNotNull($cache->getObject(4));
        $cache->clear();
        $this->assertNull($cache->getObject(0));
        $this->assertNull($cache->getObject(4));
    }
}
