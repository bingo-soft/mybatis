<?php

namespace MyBatis\DataSource;

interface DataSourceFactoryInterface
{
    public function setProperties(array $properties): void;

    public function getDataSource(): DataSourceInterface;
}
