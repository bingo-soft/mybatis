<?php

namespace Tests\Mapping;

use PHPUnit\Framework\TestCase;
use MyBatis\Mapping\BoundSql;
use MyBatis\Session\Configuration;

class BoundSqlTest extends TestCase
{
    public function testHasAdditionalParameter(): void
    {
        $params = [];
        $boundSql = new BoundSql(new Configuration(), "some sql", $params, new \stdClass());
        $map = [];
        $map['key'] = 'value1';
        $boundSql->setAdditionalParameter("map", $map);
        $bean = new Person();
        $bean->id = 1;
        $boundSql->setAdditionalParameter("person", $bean);
        $array = ['User1', 'User2'];
        $boundSql->setAdditionalParameter("array", $array);
        $this->assertFalse($boundSql->hasAdditionalParameter("pet"));
        $this->assertFalse($boundSql->hasAdditionalParameter("pet.name"));
        $this->assertTrue($boundSql->hasAdditionalParameter("map"));
        $this->assertTrue($boundSql->hasAdditionalParameter("map.key1"));
        $this->assertTrue($boundSql->hasAdditionalParameter("map.key2"));
        $this->assertTrue($boundSql->hasAdditionalParameter("person"));
        $this->assertTrue($boundSql->hasAdditionalParameter("person.id"));
        $this->assertTrue($boundSql->hasAdditionalParameter("person.name"));
        $this->assertTrue($boundSql->hasAdditionalParameter("array[0]"));
        $this->assertTrue($boundSql->hasAdditionalParameter("array[99]"));
    }
}
