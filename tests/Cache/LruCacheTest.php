<?php

namespace Tests\Cache;

use MyBatis\Cache\Decorators\LruCache;
use MyBatis\Cache\Impl\PerpetualCache;
use PHPUnit\Framework\TestCase;

class LruCacheTest extends TestCase
{
    private $cache;

    public function setUp(): void
    {
        $this->cache = new LruCache(new PerpetualCache("default"));
        $this->cache->setSize(4);
        $elements = [];
        for ($i = 1; $i < 4; $i += 1) {
            $elements[$i] = "test$i";
            $this->cache->putObject($i, $elements[$i]);
        }
    }

    public function testConstainsKey(): void
    {
        $this->assertTrue($this->cache->getObject(2) !== null);
        $this->assertTrue($this->cache->getObject("2") !== null);
        $this->assertNull($this->cache->getObject(1000));
    }

    public function testGet(): void
    {
        $this->assertSame("test1", $this->cache->getObject(1));
        $this->assertNull($this->cache->getObject(0));
    }

    public function testRemove(): void
    {
        $this->cache->removeObject(1);
        $this->assertEquals(2, $this->cache->getSize());
        $this->assertNull($this->cache->getObject(1));
    }

    public function testPut(): void
    {
        $this->cache->putObject(100, 'new');
        $this->assertSame('new', $this->cache->getObject(100));

        // now overwrite
        $this->cache->putObject(100, 'new2');
        $this->assertSame('new2', $this->cache->getObject(100));

        // now exceed the size limit
        $this->cache->putObject(101, 'really new');
        $this->assertSame('really new', $this->cache->getObject(101));
        $this->assertNull($this->cache->getObject(1));
        $this->assertEquals(4, $this->cache->getSize());
    }

    public function testAccessUpdate(): void
    {
        // fill cache, access 1st element, and exceed limit
        $this->cache->putObject(100, 'new');
        $this->cache->getObject(1);
        $this->cache->putObject(101, 'really new');
        $this->assertTrue($this->cache->getObject(1) !== null);
        $this->assertNull($this->cache->getObject(2));
    }

    public function testClear(): void
    {
        $this->cache->clear();
        $this->assertEquals(0, $this->cache->getSize());
        $this->assertNull($this->cache->getObject(1));
    }
}
