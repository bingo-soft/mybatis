<?php

namespace MyBatis\Executor\Keygen;

use Doctrine\DBAL\Statement;
use MyBatis\Executor\{
    ExecutorInterface,
    ExecutorException
};
use MyBatis\Mapping\MappedStatement;
use MyBatis\Reflection\ParamNameResolver;
use MyBatis\Session\Configuration;
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
        $this->processBatch($ms, $stmt, $parameter);
    }

    public function processBatch(MappedStatement $ms, Statement $stmt, $parameter): void
    {
        $keyProperties = $ms->getKeyProperties();
        if (empty($keyProperties)) {
            return;
        }
        $rs = $stmt->execute();
        $rows = $rs->fetchAllAssociative();
        if (!empty($rows)) {
            if (count(array_keys($rows[0])) < count($keyProperties)) {
                // Error?
            } else {
                $this->asseignKeys($ms->getConfiguration(), $rs, $rows, $keyProperties, $parameter);
            }
        }
    }

    private function assignKeys(Configuration $configuration, Result $rs, array $rows, array $keyProperties, $parameter): void
    {
        if (is_array($parameter)) {
            // Multi-param or single param with @Param in batch operation
            $this->assignKeysToParamMapList($configuration, $rs, $rows, $keyProperties, $parameter);
        } else {
            // Single param without @Param
            $this->assignKeysToParam($configuration, $rs, $rows, $keyProperties, $parameter);
        }
    }

    private function assignKeysToParam(Configuration $configuration, Result $rs, ResultSetMetaData $rows, array $keyProperties, $parameter): void
    {
        $params = self::collectionize($parameter);
        if (empty($params)) {
            return;
        }
        $assignerList = [];
        for ($i = 0; $i < count($keyProperties); $i += 1) {
            $assignerList[] = new KeyAssigner($configuration, $rows, $i + 1, null, $keyProperties[$i]);
        }
        foreach ($params as $param) {
            foreach ($assignerList as $x) {
                $x->assign($rs, $param);
            }
        }
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
