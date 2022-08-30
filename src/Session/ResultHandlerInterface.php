<?php

namespace MyBatis\Session;

interface ResultHandlerInterface
{
    public function handleResult(ResultContextInterface $resultContext): void;
}
