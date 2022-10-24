<?php

namespace Tests\Plugin;

use PHPUnit\Framework\TestCase;
use MyBatis\Plugin\{
    Intercepts,
    Signature
};

class AnnotationTest extends TestCase
{
    public function testAnnotations(): void
    {
        $plugin = new ExamplePlugin();
        $ref = new \ReflectionObject($plugin);
        $refAttributes = $ref->getAttributes(Intercepts::class);
        $this->assertCount(1, $refAttributes);
        $interceptsAnnotation = $refAttributes[0];
        $sigs = $interceptsAnnotation->getArguments();
        $this->assertCount(1, $sigs);
        foreach ($sigs as $arr) {
            $sig = $arr[0];
            $this->assertEquals("Tests\Plugin\Simple", $sig->type());
            $this->assertEquals("update", $sig->method());
        }
    }
}
