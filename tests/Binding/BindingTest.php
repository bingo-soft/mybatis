<?php

namespace Tests\Bindibg;

use PHPUnit\Framework\TestCase;
use Tests\BaseDataTest;

abstract class BindingTest extends TestCase
{
    private $dataSource;

    public function setUp(): void
    {
        $this->dataSource = BaseDataTest::createBlogDataSource();
    }
}
