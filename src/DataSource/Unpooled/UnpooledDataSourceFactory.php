<?php

namespace MyBatis\DataSource\Unpooled;

use MyBatis\DataSource\{
    DataSourceException,
    DataSourceFactoryInterface,
    DataSourceInterface
};
use MyBatis\Parsing\Boolean;

class UnpooledDataSourceFactory implements DataSourceFactoryInterface
{
    private const DRIVER_PROPERTY_PREFIX = 'driver.';

    protected $dataSource;

    public function __construct()
    {
        $this->dataSource = new UnpooledDataSource();
    }

    public function setProperties(array $properties): void
    {
        $driverProperties = [];
        $metaDataSource = new \ReflectionClass($this->dataSource);
        foreach (array_keys($properties) as $key) {
            $propertyName = $key;
            if (strpos(self::DRIVER_PROPERTY_PREFIX, $propertyName) === 0) {
                $value = $properties[$propertyName];
                $driverProperties[substr($propertyName, strlen(self::DRIVER_PROPERTY_PREFIX))] = $value;
            } elseif ($metaDataSource->hasProperty($propertyName) && $metaDataSource->hasMethod('set' . ucfirst($propertyName))) {
                $value = $properties[$propertyName];
                $convertedValue = $this->convertValue($metaDataSource, $propertyName, $value);
                $method = $metaDataSource->getMethod('set' . ucfirst($propertyName));
                $method->invoke($this->dataSource, $convertedValue);
            } else {
                throw new DataSourceException('Unknown DataSource property: ' . $propertyName);
            }
        }
        if (count($driverProperties) > 0) {
            $method = $metaDataSource->getMethod('setDriverProperties');
            $method->invoke($this->dataSource, $driverProperties);
        }
    }

    public function getDataSource(): DataSourceInterface
    {
        return $this->dataSource;
    }

    private function convertValue(\ReflectionClass $metaDataSource, string $propertyName, string $value)
    {
        $convertedValue = $value;
        $method = $metaDataSource->getMethod('get' . ucfirst($propertyName));
        $retType = $method->getReturnType();
        if ($retType instanceof \ReflectionNamedType) {
            $type = $retType->getName();
            if ($type == 'int') {
                $convertedValue = intval($convertedValue);
            } elseif ($type == 'float') {
                $convertedValue = floatval($convertedValue);
            } elseif ($type == 'bool') {
                $convertedValue = Boolean::parseBoolean($convertedValue);
            }
        }
        return $convertedValue;
    }
}
