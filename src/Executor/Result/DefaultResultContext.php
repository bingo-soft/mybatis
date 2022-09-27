<?php

namespace MyBatis\Executor\Result;

use MyBatis\Session\ResultContextInterface;

class DefaultResultContext implements ResultContextInterface
{
    private $resultObject = null;
    private $resultCount = 0;
    private $stopped = false;

    public function getResultObject()
    {
        return $this->resultObject;
    }

    public function getResultCount(): int
    {
        return $this->resultCount;
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function nextResultObject($resultObject): void
    {
        $this->resultCount += 1;
        $this->resultObject = $resultObject;
    }

    public function stop(): void
    {
        $this->stopped = true;
    }
}
