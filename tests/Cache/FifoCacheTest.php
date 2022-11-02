<?php

namespace Tests\Cache;

use MyBatis\Cache\Decorators\FifoCache;
use MyBatis\Cache\Impl\PerpetualCache;
use PHPUnit\Framework\TestCase;

class FifoCacheTest extends TestCase
{
    public function testShouldRemoveFirstEntriesBeyondFiveEntries(): void
    {
        $cache = new FifoCache(new PerpetualCache("default"));
        $cache->setSize(5);
        for ($i = 0; $i < 5; $i += 1) {
            $cache->putObject($i, $i);
        }
        $this->assertEquals(0, $cache->getObject(0));
        $cache->putObject(5, 5);
        $cache->putObject(6, 6);
        $this->assertNull($cache->getObject(0));
        $this->assertNull($cache->getObject(1));
        $this->assertEquals(5, $cache->getSize());
        $this->assertEquals(6, $cache->getObject(6));
    }
}
