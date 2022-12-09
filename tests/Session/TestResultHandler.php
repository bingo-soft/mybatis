<?php

namespace Tests\Session;

use MyBatis\Session\{
    ResultContextInterface,
    ResultHandlerInterface
};

class TestResultHandler implements ResultHandlerInterface
{
    public $count = 0;

    public function handleResult(ResultContextInterface $context): void
    {
        $this->count += 1;
    }
}
