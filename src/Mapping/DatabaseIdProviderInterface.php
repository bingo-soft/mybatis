<?php

namespace MyBatis\Mapping;

use MyBatis\DataSource\DataSourceInterface;

interface DatabaseIdProviderInterface
{
    public function getDatabaseId(DataSourceInterface $dataSource): string;
}
