<?php

namespace MyBatis\Binding;

use MyBatis\Mapping\{
    MappedStatement,
    SqlCommandType
};
use MyBatis\Session\Configuration;

class SqlCommand
{
    private $name;
    private $type;

    public function __construct(Configuration $configuration, string $mapperInterface, \ReflectionMethod $method)
    {
        $methodName = $method->getName();
        $declaringClass = $method->getDeclaringClass();
        $ms = $this->resolveMappedStatement($mapperInterface, $methodName, $declaringClass, $configuration);
        if ($ms === null) {
            throw new BindingException("Invalid bound statement (not found): " . $mapperInterface . "." . $methodName);
        } else {
            $this->name = $ms->getId();
            $this->type = $ms->getSqlCommandType();
            if ($this->type == SqlCommandType::UNKNOWN) {
                throw new BindingException("Unknown execution method for: " . $name);
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    private function resolveMappedStatement(
        string $mapperInterface,
        string $methodName,
        string $declaringClass,
        Configuration $configuration
    ): ?MappedStatement {
        $statementId = (new \ReflectionClass($mapperInterface))->getShortName() . "." . $methodName;
        if ($configuration->hasStatement($statementId)) {
            return $configuration->getMappedStatement($statementId);
        } elseif ($mapperInterface == $declaringClass) {
            return null;
        }
        foreach ((new \ReflectionClass($mapperInterface))->getInterfaces() as $superInterface) {
            if (is_a($superInterface, $declaringClass, true)) {
                $ms = $this->resolveMappedStatement($superInterface, $methodName, $declaringClass, $configuration);
                if ($ms !== null) {
                    return $ms;
                }
            }
        }
        return null;
    }
}
