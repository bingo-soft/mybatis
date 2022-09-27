<?php

namespace Tests\Type;

use Doctrine\DBAL\Types\Types;
use MyBatis\Type\DbalType;
use PHPUnit\Framework\TestCase;

class DbalTypeTest extends TestCase
{
    public function testType(): void
    {
        $type = DbalType::forCode(Types::JSON);
        $this->assertEquals(Types::JSON, $type->typeCode);
    }
}
