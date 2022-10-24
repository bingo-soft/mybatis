<?php

namespace Tests\Plugin;

use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testMapPluginShouldInterceptGet(): void
    {
        $map = new CustomArray();
        $map = (new AlwaysMapPlugin())->plugin($map);
        $map["Anything"] = "Someday";
        $this->assertEquals("Always", $map->get("Anything"));
    }

    public function testShouldNotInterceptToString(): void
    {
        $map = new CustomArray();
        $map = (new AlwaysMapPlugin())->plugin($map);
        $map["Anything"] = "Someday";
        $this->assertEquals("Someday", $map["Anything"]);
    }
}
