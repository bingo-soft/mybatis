<?php

namespace Tests\Io;

use MyBatis\Io\Resources;
use PHPUnit\Framework\TestCase;

class ResourcesTest extends TestCase
{
    private const JPETSTORE_PROPERTIES = "tests/Resources/jpetstore-hsqldb.properties";

    public function testShouldGetUrlAsProperties(): void
    {
        $url = Resources::getResourceURL(self::JPETSTORE_PROPERTIES);
        $props = Resources::getUrlAsProperties($url);
        $this->assertNotNull($props["driver"]);
    }

    public function testShouldGetResourceAsProperties(): void
    {
        $props = Resources::getResourceAsProperties(self::JPETSTORE_PROPERTIES);
        $this->assertNotNull($props["driver"]);
    }

    public function testShouldGetUrlAsStream(): void
    {
        $url = Resources::getResourceURL(self::JPETSTORE_PROPERTIES);
        $in = Resources::getUrlAsStream($url);
        $this->assertTrue(gettype($in) === "resource");
        fclose($in);
        $this->assertTrue(gettype($in) === "resource (closed)");
    }

    public function testShouldGetResourceAsStream(): void
    {
        $in = Resources::getResourceAsStream(self::JPETSTORE_PROPERTIES);
        $this->assertTrue(gettype($in) === "resource");
        fclose($in);
        $this->assertTrue(gettype($in) === "resource (closed)");
    }

    public function testShouldGetResourceAsFile(): void
    {
        $file = Resources::getResourceAsFile(self::JPETSTORE_PROPERTIES);
        $this->assertTrue(gettype($file) === "resource");
        fclose($file);
        $this->assertTrue(gettype($file) === "resource (closed)");
    }
}
