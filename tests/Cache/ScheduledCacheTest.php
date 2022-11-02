<?php

namespace Tests\Cache;

use MyBatis\Cache\Decorators\{
    LoggingCache,
    ScheduledCache
};
use MyBatis\Cache\Impl\PerpetualCache;
use PHPUnit\Framework\TestCase;

class ScheduledCacheTest extends TestCase
{
    public function testShouldDemonstrateHowAllObjectsAreFlushedAfterBasedOnTime(): void
    {
        $cache = new PerpetualCache("DefaultCache");
        $cache = new ScheduledCache($cache);
        $cache->setClearInterval(3);
        $cache = new LoggingCache($cache);
        for ($i = 0; $i < 100; $i += 1) {
            $cache->putObject($i, $i);
            $this->assertEquals($i, $cache->getObject($i));
        }
        sleep(5);
        $this->assertEquals(0, $cache->getSize());
    }

    public function testShouldRemoveItemOnDemand(): void
    {
        $cache = new PerpetualCache("DefaultCache");
        $cache = new ScheduledCache($cache);
        $cache->setClearInterval(60);
        $cache = new LoggingCache($cache);
        $cache->putObject(0, 0);
        $this->assertNotNull($cache->getObject(0));
        $cache->removeObject(0);
        $this->assertNull($cache->getObject(0));
    }

    public function testShouldFlushAllItemsOnDemand(): void
    {
        $cache = new PerpetualCache("DefaultCache");
        $cache = new ScheduledCache($cache);
        $cache->setClearInterval(60);
        $cache = new LoggingCache($cache);
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
