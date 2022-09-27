<?php

namespace MyBatis\Executor\Result;

use MyBatis\Reflection\MetaObject;
use MyBatis\Session\{
    ResultContextInterface,
    ResultHandlerInterface
};

class DefaultMapResultHandler implements ResultHandlerInterface
{
    private $mappedResults;
    private $mapKey;

    public function __construct(string $mapKey)
    {
        $this->mappedResults = [];
        $this->mapKey = $mapKey;
    }

    public function handleResult(ResultContextInterface $context): void
    {
        $value = $context->getResultObject();
        $mo = new MetaObject($value);
        // TODO is that assignment always true?
        $key = $mo->getValue($this->mapKey);
        $this->mappedResults[$key] = $value;
    }

    public function getMappedResults(): array
    {
        return $this->mappedResults;
    }
}
