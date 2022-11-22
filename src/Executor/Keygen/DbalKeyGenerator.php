<?php

namespace MyBatis\Executor\Keygen;

use Doctrine\DBAL\Statement;
use MyBatis\Binding\ParamMap;
use MyBatis\Executor\{
    ExecutorInterface,
    ExecutorException
};
use MyBatis\Mapping\MappedStatement;
use MyBatis\Reflection\ParamNameResolver;
use MyBatis\Session\{
    Configuration,
    StrictMap
};
use MyBatis\Type\{
    DbalType,
    TypeHandlerInterface,
    TypeHandlerRegistry
};

class DbalKeyGenerator implements KeyGeneratorInterface
{
    private const SECOND_GENERIC_PARAM_NAME = ParamNameResolver::GENERIC_NAME_PREFIX . "2";

    /**
     * A shared instance.
     */
    private static $INSTANCE;

    public static function instance(): KeyGeneratorInterface
    {
        if (self::$INSTANCE === null) {
            self::$INSTANCE = new DbalKeyGenerator();
        }
        return self::$INSTANCE;
    }

    private const MSG_TOO_MANY_KEYS = "Too many keys are generated. There are only %d target objects. "
        . "You either specified a wrong 'keyProperty' or encountered a driver bug like #1523.";

    public function processBefore(ExecutorInterface $executor, MappedStatement $ms, Statement $stmt, $parameter): void
    {
        // do nothing
    }

    public function processAfter(ExecutorInterface $executor, MappedStatement $ms, Statement $stmt, $parameter): void
    {
        //$this->processBatch($ms, $stmt, $parameter);
    }

    private static function nameOfSingleParam(array $paramMap): string
    {
        // There is virtually one parameter, so any key works.
        return array_keys($paramMap)[0];
    }

    private static function collectionize($param): array
    {
        if (is_array($param)) {
            return $param;
        }
        return [ $param ];
    }
}
