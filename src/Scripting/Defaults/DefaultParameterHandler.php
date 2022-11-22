<?php

namespace MyBatis\Scripting\Defaults;

use Doctrine\DBAL\Statement;
use MyBatis\Executor\Parameter\ParameterHandlerInterface;
use MyBatis\Mapping\{
    BoundSql,
    MappedStatement,
    ParameterMapping,
    ParameterMode
};
use MyBatis\Type\{
    DbalType,
    TypeException,
    TypeHandlerInterface,
    TypeHandlerRegistry
};

class DefaultParameterHandler implements ParameterHandlerInterface
{
    private $typeHandlerRegistry;
    private $mappedStatement;
    private $parameterObject;
    private $boundSql;
    private $configuration;

    public function __construct(MappedStatement $mappedStatement, $parameterObject, BoundSql $boundSql)
    {
        $this->mappedStatement = $mappedStatement;
        $this->configuration = $mappedStatement->getConfiguration();
        $this->typeHandlerRegistry = $mappedStatement->getConfiguration()->getTypeHandlerRegistry();
        $this->parameterObject = $parameterObject;
        $this->boundSql = $boundSql;
    }

    public function getParameterObject()
    {
        return $this->parameterObject;
    }

    public function setParameters(Statement $ps): void
    {
        $parameterMappings = $this->boundSql->getParameterMappings();
        if (!empty($parameterMappings)) {
            $metaObject = null;
            for ($i = 0; $i < count($parameterMappings); $i += 1) {
                $parameterMapping = $parameterMappings[$i];
                if ($parameterMapping->getMode() != ParameterMode::OUT) {
                    $value = null;
                    $propertyName = $parameterMapping->getProperty();
                    if ($this->boundSql->hasAdditionalParameter($propertyName)) {
                        $value = $this->boundSql->getAdditionalParameter($propertyName);
                    } elseif ($this->parameterObject === null) {
                        $value = null;
                    } elseif ($this->typeHandlerRegistry->hasTypeHandler(is_object($this->parameterObject) ? get_class($this->parameterObject) : gettype($this->parameterObject))) {
                        $value = $this->parameterObject;
                    } else {
                        if ($metaObject === null) {
                            $metaObject = $this->configuration->newMetaObject($this->parameterObject);
                        }
                        $value = $metaObject->getValue($propertyName);
                    }
                    $typeHandler = $parameterMapping->getTypeHandler();
                    $dbalType = $parameterMapping->getDbalType();
                    if ($value == null && $dbalType === null) {
                        $dbalType = $this->configuration->getDbalTypeForNull();
                    }
                    try {
                        $typeHandler->setParameter($ps, $i + 1, $value, $dbalType);
                    } catch (\Exception $e) {
                        throw new TypeException("Could not set parameters for mapping. Cause: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
