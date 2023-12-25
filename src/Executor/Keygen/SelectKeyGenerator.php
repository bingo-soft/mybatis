<?php

namespace MyBatis\Executor\Keygen;

use Doctrine\DBAL\Statement;
use MyBatis\Executor\{
    ExecutorInterface,
    ExecutorException
};
use MyBatis\Mapping\MappedStatement;
use Util\Reflection\MetaObject;
use MyBatis\Session\{
    Configuration,
    ExecutorType,
    RowBounds
};

class SelectKeyGenerator implements KeyGeneratorInterface
{
    public const SELECT_KEY_SUFFIX = "!selectKey";
    private $executeBefore;
    private $keyStatement;

    public function __construct(MappedStatement $keyStatement, bool $executeBefore)
    {
        $this->executeBefore = $executeBefore;
        $this->keyStatement = $keyStatement;
    }

    public function processBefore(ExecutorInterface $executor, MappedStatement $ms, ?Statement $stmt, $parameter): void
    {
        if ($this->executeBefore) {
            $this->processGeneratedKeys($executor, $ms, $parameter);
        }
    }

    public function processAfter(ExecutorInterface $executor, MappedStatement $ms, ?Statement $stmt, $parameter): void
    {
        if (!$this->executeBefore) {
            $this->processGeneratedKeys($executor, $ms, $parameter);
        }
    }

    private function processGeneratedKeys(ExecutorInterface $executor, MappedStatement $ms, $parameter): void
    {
        try {
            if ($parameter !== null && $this->keyStatement !== null && !empty($this->keyStatement->getKeyProperties())) {
                $keyProperties = $this->keyStatement->getKeyProperties();
                $configuration = $ms->getConfiguration();
                $metaParam = $configuration->newMetaObject($parameter);
                // Do not close keyExecutor.
                // The transaction will be closed by parent executor.
                $keyExecutor = $configuration->newExecutor($executor->getTransaction(), ExecutorType::SIMPLE);
                $values = $keyExecutor->query($this->keyStatement, $parameter, RowBounds::default(), null);
                if (count($values) == 0) {
                    throw new ExecutorException("SelectKey returned no data.");
                } elseif (count($values) > 1) {
                    throw new ExecutorException("SelectKey returned more than one value.");
                } else {
                    if (is_object($values[0])) {
                        $metaResult = $configuration->newMetaObject($values[0]);
                    } else {
                        $object = new \stdClass();
                        foreach ($keyProperties as $key => $value) {
                            $object->{$value} = $values[$key];
                        }
                        $metaResult = $configuration->newMetaObject($object);
                    }
                    if (count($keyProperties) == 1) {
                        if ($metaResult->hasGetter($keyProperties[0])) {
                            $this->setValue($metaParam, $keyProperties[0], $metaResult->getValue($keyProperties[0]));
                        } else {
                            // no getter for the property - maybe just a single value object
                            // so try that
                            $this->setValue($metaParam, $keyProperties[0], $values[0]);
                        }
                    } else {
                        $this->handleMultipleProperties($keyProperties, $metaParam, $metaResult);
                    }
                }
            }
        } catch (ExecutorException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ExecutorException("Error selecting key or setting result to parameter object. Cause: " . $e->getMessage());
        }
    }

    private function handleMultipleProperties(array $keyProperties, MetaObject $metaParam, MetaObject $metaResult): void
    {
        $keyColumns = $this->keyStatement->getKeyColumns();

        if (empty($keyColumns)) {
            // no key columns specified, just use the property names
            foreach ($keyProperties as $keyProperty) {
                $this->setValue($metaParam, $keyProperty, $metaResult->getValue($keyProperty));
            }
        } else {
            if (count($keyColumns) != count($keyProperties)) {
                throw new ExecutorException("If SelectKey has key columns, the number must match the number of key properties.");
            }
            $this->setValue($metaParam, $keyProperties[$i], $metaResult->getValue($keyColumns[$i]));
        }
    }

    private function setValue(MetaObject $metaParam, string $property, $value): void
    {
        if ($metaParam->hasSetter($property)) {
            $metaParam->setValue($property, $value);
        } else {
            throw new ExecutorException("No setter found for the keyProperty '" . $property . "' in " . get_class($metaParam->getOriginalObject()) . ".");
        }
    }
}
