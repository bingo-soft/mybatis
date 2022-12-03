<?php

namespace MyBatis\Executor\Keygen;

use MyBatis\Executor\ExecutorException;
use MyBatis\Session\Configuration;
use MyBatis\Type\DbalType;

class KeyAssigner
{
    private $configuration;
    private $rows;
    private $typeHandlerRegistry;
    private $columnPosition;
    private $paramName;
    private $propertyName;
    private $typeHandler;

    public function __construct(Configuration $configuration, array $rows, int $columnPosition, string $paramName, string $propertyName)
    {
        $this->configuration = $configuration;
        $this->rows = $rows;
        $this->typeHandlerRegistry = $configuration->getTypeHandlerRegistry();
        $this->columnPosition = $columnPosition;
        $this->paramName = $paramName;
        $this->propertyName = $propertyName;
    }

    public function assign(array $record, $param): void
    {
        if ($this->paramName !== null) {
            // If paramName is set, param is ParamMap
            if (array_key_exists($this->paramName, $param)) {
                $param = $param[$this->paramName];
            }
        }
        $metaParam = $this->configuration->newMetaObject($param);
        try {
            if ($this->typeHandler === null) {
                if ($metaParam->hasMethod(sprintf("set%s", ucfirst($this->propertyName)))) {
                    $this->typeHandler = $this->typeHandlerRegistry->getTypeHandler(DbalType::forCode(gettype($rows[0][$this->propertyName])));
                } else {
                    throw new ExecutorException("No setter found for the keyProperty '" . $this->propertyName . "'");
                }
            }
            if ($this->typeHandler == null) {
                // Error?
            } else {
                $value = $this->typeHandler->getResult($record, $this->columnPosition);
                //metaParam.setValue(propertyName, value);
                $prop = $metaParam->getProperty($propertyName);
                if ($prop->isPrivate() || $prop->isProtected()) {
                    $prop->setAccessible(true);
                    $prop->setValue($param, $value);
                }
            }
        } catch (\Exception $e) {
            throw new ExecutorException("Error getting generated key or setting result to parameter object. Cause: " . $e->getMessage());
        }
    }
}
