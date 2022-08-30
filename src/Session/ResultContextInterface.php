<?php

namespace MyBatis\Session;

interface ResultContextInterface
{
    public function getResultObject();

    public function getResultCount(): int;

    public function isStopped(): bool;

    public function stop(): void;
}
