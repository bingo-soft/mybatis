<?php

namespace MyBatis\Executor\Keygen;

use Doctrine\DBAL\Statement;
use MyBatis\Executor\ExecutorInterface;
use MyBatis\Mapping\MappedStatement;

class NoKeyGenerator implements KeyGeneratorInterface
{
    /**
     * A shared instance.
     */
    private static $INSTANCE;

    public static function instance(): KeyGeneratorInterface
    {
        if (self::$INSTANCE === null) {
            self::$INSTANCE = new NoKeyGenerator();
        }
        return self::$INSTANCE;
    }

    public function processBefore(ExecutorInterface $executor, MappedStatement $ms, Statement $stmt, $parameter): void
    {
    }

    public function processAfter(ExecutorInterface $executor, MappedStatement $ms, Statement $stmt, $parameter): void
    {
    }
}
