<?php

namespace Tests\Parsing;

use PHPUnit\Framework\TestCase;
use MyBatis\Parsing\PropertyParser;

class PropertyParserTest extends TestCase
{
    public function testReplaceToVariableValue(): void
    {
        $props = [];
        $props[PropertyParser::KEY_ENABLE_DEFAULT_VALUE] = "true";
        $props["key"] = "value";
        $props["tableName"] = "members";
        $props["orderColumn"] = "member_id";
        $props["a:b"] = "c";
        $this->assertEquals('value', PropertyParser::parse('${key}', $props));
        $this->assertEquals('value', PropertyParser::parse('${key:aaaa}', $props));
        $this->assertEquals('SELECT * FROM members ORDER BY member_id', PropertyParser::parse('SELECT * FROM ${tableName:users} ORDER BY ${orderColumn:id}', $props));
        $props[PropertyParser::KEY_ENABLE_DEFAULT_VALUE] = "false";
        $this->assertEquals("c", PropertyParser::parse('${a:b}', $props));
        unset($props[PropertyParser::KEY_ENABLE_DEFAULT_VALUE]);
        $this->assertEquals("c", PropertyParser::parse('${a:b}', $props));
    }

    public function testNotReplace(): void
    {
        $props = [];
        $props[PropertyParser::KEY_ENABLE_DEFAULT_VALUE] = "true";
        $this->assertEquals('${key}', PropertyParser::parse('${key}', $props));
        $this->assertEquals('${key}', PropertyParser::parse('${key}'));
        $props[PropertyParser::KEY_ENABLE_DEFAULT_VALUE] = "false";
        $this->assertEquals('${a:b}', PropertyParser::parse('${a:b}', $props));
        unset($props[PropertyParser::KEY_ENABLE_DEFAULT_VALUE]);
        $this->assertEquals('${a:b}', PropertyParser::parse('${a:b}', $props));
    }

    public function testApplyDefaultValue(): void
    {
        $props = [];
        $props[PropertyParser::KEY_ENABLE_DEFAULT_VALUE] = "true";
        $this->assertEquals('${key}', PropertyParser::parse('${key}', $props));
        $this->assertEquals('default', PropertyParser::parse('${key:default}', $props));
        $this->assertEquals('SELECT * FROM users ORDER BY id', PropertyParser::parse('SELECT * FROM ${tableName:users} ORDER BY ${orderColumn:id}', $props));
        $this->assertEmpty(PropertyParser::parse('${key:}', $props));
        $this->assertEquals(' ', PropertyParser::parse('${key: }', $props));
        $this->assertEquals(':', PropertyParser::parse('${key::}', $props));
    }

    public function testApplyCustomSeparator(): void
    {
        $props = [];
        $props[PropertyParser::KEY_ENABLE_DEFAULT_VALUE] = "true";
        $props[PropertyParser::KEY_DEFAULT_VALUE_SEPARATOR] = "?:";
        $this->assertEquals('default', PropertyParser::parse('${key?:default}', $props));
        $this->assertEquals('SELECT * FROM prod.${tableName == null ? \'users\' : tableName} ORDER BY ${orderColumn}', PropertyParser::parse('SELECT * FROM ${schema?:prod}.${tableName == null ? \'users\' : tableName} ORDER BY ${orderColumn}', $props));
        $this->assertEmpty(PropertyParser::parse('${key?:}', $props));
        $this->assertEquals(' ', PropertyParser::parse('${key?: }', $props));
        $this->assertEquals(':', PropertyParser::parse('${key?::}', $props));
    }
}
