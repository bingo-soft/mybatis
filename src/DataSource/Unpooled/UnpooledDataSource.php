<?php

namespace MyBatis\DataSource\Unpooled;

use Doctrine\DBAL\{
    DriverManager,
    Connection
};
use MyBatis\DataSource\DataSourceInterface;

class UnpooledDataSource implements DataSourceInterface
{
    private $driverProperties = [];
    private $driver;
    private $url;
    private $username;
    private $password;
    private $autoCommit = false;
    private $defaultTransactionIsolationLevel;
    private static $connection;

    public function __construct(string $driver = null, string $url = null, string $username = null, string $password = null)
    {
        $this->driver = $driver;
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
    }

    public function getDriverProperties(): array
    {
        return $this->driverProperties;
    }

    public function setDriverProperties(array $driverProperties): void
    {
        $this->driverProperties = $driverProperties;
    }

    public function getDriver(): ?string
    {
        return $this->driver;
    }

    public function setDriver(string $driver): void
    {
        $this->driver = $driver;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function isAutoCommit(): ?bool
    {
        return $this->autoCommit;
    }

    public function setAutoCommit(bool $autoCommit): void
    {
        $this->autoCommit = $autoCommit;
    }

    public function getDefaultTransactionIsolationLevel()
    {
        return $this->defaultTransactionIsolationLevel;
    }

    public function setDefaultTransactionIsolationLevel($level): void
    {
        $this->defaultTransactionIsolationLevel = $level;
    }

    public function getConnection(): Connection
    {
        if (self::$connection === null) {
            $props = array_merge([
                'driver' => $this->driver,
                'url' => $this->url,
                'user' => $this->username,
                'password' => $this->password
            ], $this->driverProperties);
            self::$connection = DriverManager::getConnection($props);
        }
        $this->configureConnection(self::$connection);
        return self::$connection;
    }

    public function configureConnection(Connection $conn): void
    {
        if ($this->autoCommit !== null && $this->autoCommit != $conn->isAutoCommit()) {
            $conn->setAutoCommit($this->autoCommit);
        }
        if ($this->defaultTransactionIsolationLevel !== null) {
            $conn->setTransactionIsolation($this->defaultTransactionIsolationLevel);
        }
    }
}
