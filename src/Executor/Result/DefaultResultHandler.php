<?php

namespace MyBatis\Executor\Result;

use MyBatis\Session\{
    ResultContextInterface,
    ResultHandlerInterface
};

class DefaultResultHandler implements ResultHandlerInterface
{
    private $list = [];

    public function handleResult(ResultContextInterface $resultContext): void
    {
        $this->list[] = $resultContext->getResultObject();
    }

    public function getResultList(): array
    {
        return $this->list;
    }
}
