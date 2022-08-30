<?php

namespace MyBatis\Binding;

use MyBatis\Mapping\{
    SqlCommandType,
    StatementType
};
use MyBatis\Session\{
    Configuration,
    SqlSessionInterface
};

class MapperMethod
{
    private $command;
    private $method;

    public function __construct(string $mapperInterface, \ReflectionMethod $method, Configuration $config)
    {
        $this->command = new SqlCommand($config, $mapperInterface, $method);
        $this->method = new MethodSignature($config, $mapperInterface, $method);
    }

    public function execute(SqlSessionInterface $sqlSession, array $args)
    {
        $result = null;
        switch ($this->command->getType()) {
            case SqlCommandType::INSERT:
                $param = $this->method->convertArgsToSqlCommandParam($args);
                $result = $this->rowCountResult($sqlSession->insert($this->command->getName(), $param));
                break;
            case SqlCommandType::UPDATE:
                $param = $this->method->convertArgsToSqlCommandParam($args);
                $result = $this->rowCountResult($sqlSession->update($this->command->getName(), $param));
                break;
            case SqlCommandType::DELETE:
                $param = $this->method->convertArgsToSqlCommandParam($args);
                $result = $this->rowCountResult($sqlSession->delete($this->command->getName(), $param));
                break;
            case SqlCommandType::SELECT:
                if ($this->method->returnsVoid() && $this->method->hasResultHandler()) {
                    $this->executeWithResultHandler($sqlSession, $args);
                    $result = null;
                } elseif ($this->method->returnsMany() || $this->method->returnsMap()) {
                    $result = $this->executeForMany($sqlSession, $args);
                } else {
                    $param = $this->method->convertArgsToSqlCommandParam($args);
                    $result = $sqlSession->selectOne($this->command->getName(), $param);
                }
                break;
            case SqlCommandType::FLUSH:
                $result = $sqlSession->flushStatements();
                break;
            default:
                throw new BindingException("Unknown execution method for: " . $this->command->getName());
        }
        return $result;
    }

    private function rowCountResult(int $rowCount)
    {
        $result = null;
        if ($this->method->returnsVoid()) {
            $result = null;
        } elseif ($this->method->getReturnType() == 'int') {
            $result = $rowCount;
        } elseif ($this->method->getReturnType() == 'bool') {
            $result = $rowCount > 0;
        } else {
            throw new BindingException("Mapper method '" . $this->command->getName() . "' has an unsupported return type: " . $this->method->getReturnType());
        }
        return $result;
    }

    private function executeWithResultHandler(SqlSessionInterface $sqlSession, array $args): void
    {
        $ms = $sqlSession->getConfiguration()->getMappedStatement($this->command->getName());
        if (
            StatementType::CALLABLE != $ms->getStatementType()
            && $ms->getResultMaps()[0]->getType() == 'void'
        ) {
            throw new BindingException(
                "method " . $this->command->getName()
                . " needs a resultType attribute in XML so a ResultHandler can be used as a parameter."
            );
        }
        $param = $this->method->convertArgsToSqlCommandParam($args);
        if ($this->method->hasRowBounds()) {
            $rowBounds = $this->method->extractRowBounds($args);
            $sqlSession->select($this->command->getName(), $param, $rowBounds, $this->method->extractResultHandler($args));
        } else {
            $sqlSession->select($this->command->getName(), $param, $this->method->extractResultHandler($args));
        }
    }

    private function executeForMany(SqlSessionInterface $sqlSession, array $args)
    {
        $result = [];
        $param = $this->method->convertArgsToSqlCommandParam($args);
        if ($this->method->hasRowBounds()) {
            $rowBounds = $this->method->extractRowBounds($args);
            $result = $sqlSession->selectList($this->command->getName(), $param, $rowBounds);
        } else {
            $result = $sqlSession->selectList($this->command->getName(), $param);
        }
        return $result;
    }
}
