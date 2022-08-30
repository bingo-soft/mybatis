<?php

namespace Tests\Type;

use Doctrine\DBAL\{
    Result,
    Statement
};
use PHPUnit\Framework\TestCase;

abstract class BaseTypeHandlerTest extends TestCase
{
    protected $rs;
    protected $ps;

    public function setUp(): void
    {
        $this->rs = $this->createMock(Result::class);
        $this->ps = $this->createMock(Statement::class);
    }
}
