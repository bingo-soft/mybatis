<?php

namespace MyBatis\DataSource\Unpooled;

use MyBatis\DataSource\{
    DataSourceException,
    DataSourceFactoryInterface,
    DataSourceInterface
};
use MyBatis\Parsing\Boolean;
use MyBatis\Reflection\{
    MetaObject,
    SystemMetaObject
};

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
        $metaDataSource = SystemMetaObject::forObject($this->dataSource);
        foreach (array_keys($properties) as $key) {
            $propertyName = $key;
            if (strpos(self::DRIVER_PROPERTY_PREFIX, $propertyName) === 0) {
                $value = $properties[$propertyName];
                $driverProperties[substr($propertyName, strlen(self::DRIVER_PROPERTY_PREFIX))] = $value;
            } elseif ($metaDataSource->hasSetter($propertyName)) {
                $value = $properties[$propertyName];
                $convertedValue = $this->convertValue($metaDataSource, $propertyName, $value);
                $metaDataSource->setValue($propertyName, $convertedValue);
            } else {
                throw new DataSourceException('Unknown DataSource property: ' . $propertyName);
            }
        }
        if (count($driverProperties) > 0) {
            $metaDataSource->setValue("driverProperties", $driverProperties);
        }
    }

    public function getDataSource(): DataSourceInterface
    {
        return $this->dataSource;
    }

    private function convertValue(MetaObject $metaDataSource, string $propertyName, string $value)
    {
        $convertedValue = $value;
        $targetType = $metaDataSource->getSetterType($propertyName);
        if ($targetType == "int" || $targetType == "integer") {
            $convertedValue = intval($value);
        } elseif ($targetType == "bool" || $targetType == "boolean") {
            $convertedValue = Boolean::parseBoolean($value);
        }
        return $convertedValue;
    }
}
