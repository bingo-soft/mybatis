<?php

namespace MyBatis\DataSource;

use Doctrine\DBAL\Connection;

interface DataSourceInterface
{
    public function getConnection(): Connection;

    public function getReconnectAttempts(): int;

    public function reconnect(): void;
}
