<?php

namespace MyBatis\Executor\Keygen;

use Doctrine\DBAL\Statement;
use MyBatis\Executor\ExecutorInterface;
use MyBatis\Mapping\MappedStatement;

interface KeyGeneratorInterface
{
    public function processBefore(ExecutorInterface $executor, MappedStatement $ms, ?Statement $stmt, $parameter): void;

    public function processAfter(ExecutorInterface $executor, MappedStatement $ms, ?Statement $stmt, $parameter): void;
}
