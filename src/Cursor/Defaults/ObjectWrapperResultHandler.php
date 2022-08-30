<?php

namespace MyBatis\Cursor\Defaults;

use MyBatis\Session\{
    ResultContextInterface,
    ResultHandlerInterface
};

class ObjectWrapperResultHandler implements ResultHandlerInterface
{
    public $result;
    public $fetched;

    public function handleResult(ResultContextInterface $context): void
    {
        $this->result = $context->getResultObject();
        $context->stop();
        $this->fetched = true;
    }
}
