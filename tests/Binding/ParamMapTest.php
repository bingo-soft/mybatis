<?php

namespace Tests\Binding;

use MyBatis\Binding\ParamMap;
use PHPUnit\Framework\TestCase;
use Util\Reflection\MetaObject;

class ParamMapTest extends TestCase
{
    public function testParamMapReflection(): void
    {
        $param = new ParamMap();
        $param->put("array", [1, 3, 5]);
        $meta = new MetaObject($param);
        $meta->setValue("array[0]", 2);
        $this->assertEquals(2, $meta->getValue("array[0]"));
    }
}
