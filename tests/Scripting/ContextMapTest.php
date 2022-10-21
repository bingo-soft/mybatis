<?php

namespace Tests\Scripting;

use PHPUnit\Framework\TestCase;
use MyBatis\Scripting\XmlTags\ContextMap;
use Tests\Domain\Blog\Author;
use Util\Reflection\MetaObject;

class ContextMapTest extends TestCase
{
    public function testArrayMethods(): void
    {
        $auth = new Author(1, "cbegin", "******", "cbegin@apache.org", "N/A", "news");
        $meta = new MetaObject($auth);
        $context = new ContextMap($meta);

        $this->assertEquals(1, $context->get('id'));
        $this->assertEquals('cbegin', $context->get('username'));
        $this->assertEquals('******', $context->get('password'));
        $this->assertEquals('cbegin@apache.org', $context->get('email'));
        $this->assertEquals('N/A', $context->get('bio'));
        $this->assertEquals('news', $context->get('favouriteSection'));
        $this->assertTrue($context->isEmpty());

        $context->put('var1', 1);
        $context->put('var2', 2);

        foreach ($context as $key => $val) {
            echo $val, "\n";
        }

        $this->assertFalse($context->isEmpty());
        $this->assertEquals(1, $context->get('var1'));
        $this->assertEquals(2, $context->get('var2'));

        $this->assertEquals(['var1', 'var2'], $context->keySet());
        $this->assertEquals(2, $context->size());
        $context->remove('var2');
        $this->assertEquals(1, $context->size());

        $this->assertEquals([1], $context->values());
        $context->clear();
        $this->assertEquals(0, $context->size());
    }
}
