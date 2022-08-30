<?php

namespace MyBatis\DataSource;

use Doctrine\DBAL\Connection;

interface DataSourceInterface
{
    public function getConnection(): Connection;
}
