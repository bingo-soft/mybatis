<?php

namespace MyBatis\Executor\Loader;

use MyBatis\Executor\ExecutorException;
use Util\Reflection\MetaObject;
use MyBatis\Session\Configuration;

class LoadPair
{
    /**
     * Name of factory method which returns database connection.
     */
    private const FACTORY_METHOD = "getConfiguration";
    /**
     * Object to check whether we went through serialization..
     */
    private $serializationCheck = false;
    /**
     * Meta object which sets loaded properties.
     */
    private $metaResultObject;
    /**
     * Result loader which loads unread properties.
     */
    private $resultLoader;
    /**
     * Wow, logger.
     */
    //private transient Log log;
    /**
     * Factory class through which we get database connection.
     */
    private $configurationFactory;
    /**
     * Name of the unread property.
     */
    private $property;
    /**
     * ID of SQL statement which loads the property.
     */
    private $mappedStatement;
    /**
     * Parameter of the sql statement.
     */
    private $mappedParameter;

    public function __construct(string $property, ?MetaObject $metaResultObject, ?ResultLoader $resultLoader)
    {
        $this->property = $property;
        $this->metaResultObject = $metaResultObject;
        $this->resultLoader = $resultLoader;

        /* Save required information only if original object can be serialized. */
        if ($metaResultObject !== null && method_exists($metaResultObject->getOriginalObject(), '__serialize')) {
            $mappedStatementParameter = $resultLoader->parameterObject;

            /* @todo May the parameter be null? */
            if (method_exists($mappedStatementParameter, '__serialize')) {
                $this->mappedStatement = $resultLoader->mappedStatement->getId();
                $this->mappedParameter = $mappedStatementParameter;
                $this->configurationFactory = $resultLoader->configuration->getConfigurationFactory();
            } else {
                //log
            }
        }
    }

    public function __serialize(): array
    {
        $this->serializationCheck = true;
        return [
            'property' => $this->property,
            'mappedStatement' => $this->mappedStatement,
            'mappedParameter' => $this->mappedParameter
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->property = $data['property'];
        $this->mappedStatement = $data['mappedStatement'];
        $this->mappedParameter = $data['mappedParameter'];
    }

    public function load($userObject = null): void
    {
        if ($this->metaResultObject == null || $this->resultLoader == null) {
            if ($this->mappedParameter == null) {
                throw new ExecutorException(
                    "Property [" . $this->property . "] cannot be loaded because "
                    . "required parameter of mapped statement ["
                    . $this->mappedStatement . "] is not serializable."
                );
            }

            $config = $this->getConfiguration();
            $ms = $config->getMappedStatement($this->mappedStatement);
            if ($ms === null) {
                throw new ExecutorException(
                    "Cannot lazy load property [" . $this->property
                    . "] of deserialized object [" . get_class($userObject)
                    . "] because configuration does not contain statement ["
                    . $this->mappedStatement . "]"
                );
            }

            $this->metaResultObject = $config->newMetaObject($userObject);
            $this->resultLoader = new ResultLoader(
                $config,
                new ClosedExecutor(),
                $ms,
                $this->mappedParameter,
                $this->metaResultObject->getSetterType($this->property),
                null,
                null
            );
        }

        if ($this->serializationCheck === true) {
            $old = $this->resultLoader;
            $this->resultLoader = new ResultLoader(
                $old->configuration,
                new ClosedExecutor(),
                $old->mappedStatement,
                $old->parameterObject,
                $old->targetType,
                $old->cacheKey,
                $old->boundSql
            );
        }

        $this->metaResultObject->setValue($this->property, $this->resultLoader->loadResult());
    }

    private function getConfiguration(): Configuration
    {
        if ($this->configurationFactory === null) {
            throw new ExecutorException("Cannot get Configuration as configuration factory was not set.");
        }

        $configurationObject = null;
        try {
            $factoryMethod = (new \ReflectionClass($this->configurationFactory))->getMethod(self::FACTORY_METHOD);
            if (!$factoryMethod->isStatic()) {
                throw new ExecutorException(
                    "Cannot get Configuration as factory method ["
                    . $this->configurationFactory . "]#["
                    . FACTORY_METHOD . "] is not static."
                );
            }
            if ($factoryMethod->isPrivate() || $factoryMethod->isProtected()) {
                $factoryMethod->setAccessible(true);
            }
            $configurationObject = $factoryMethod->invoke($this->configurationFactory, null);
        } catch (\Exception $ex) {
            throw new ExecutorException(
                "Cannot get Configuration as factory method ["
                . $this->configurationFactory . "]#["
                . self::FACTORY_METHOD . "] threw an exception."
            );
        }

        if (!($configurationObject instanceof Configuration)) {
            throw new ExecutorException(
                "Cannot get Configuration as factory method ["
                . $this->configurationFactory . "]#["
                . self::FACTORY_METHOD . "] didn't return [" . Configuration::class . "] but ["
                . ($configurationObject === null ? "null" : get_class($configurationObject)) . "]."
            );
        }

        return $configurationObject;
    }
}
