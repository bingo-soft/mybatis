<?php

namespace MyBatis\Session;

use Util\Proxy\MethodHandlerInterface;

class SqlSessionInterceptor implements MethodHandlerInterface
{
    private $scope;

    public function __construct(SqlSessionFactoryInterface $scope)
    {
        $this->scope = $scope;
    }

    public function invoke($proxy, \ReflectionMethod $thisMethod, \ReflectionMethod $proceed, array $args)
    {
        $sqlSession = $this->scope->getLocalSqlSession();
        if ($sqlSession !== null) {
            try {
                return $proceed->invoke($sqlSession, ...$args);
            } catch (\Throwable $t) {
                throw $t;
            }
        } else {
            try {
                $autoSqlSession = $this->scope->openSession();
                $result = $proceed->invoke($autoSqlSession, ...$args);
                $autoSqlSession->commit();
                return $result;
            } catch (\Throwable $t) {
                $autoSqlSession->rollback();
                throw $t;
            }
        }
    }
}
