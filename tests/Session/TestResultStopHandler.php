<?php

namespace Tests\Session;

use MyBatis\Session\{
    ResultContextInterface,
    ResultHandlerInterface
};

class TestResultStopHandler implements ResultHandlerInterface
{
    public $count = 0;

    public function handleResult(ResultContextInterface $context): void
    {
        $this->count += 1;
        if ($this->count == 2) {
            $context->stop();
        }
    }
}
