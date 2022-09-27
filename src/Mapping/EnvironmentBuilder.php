<?php

namespace MyBatis\Mapping;

use MyBatis\DataSource\DataSourceInterface;
use MyBatis\Transaction\TransactionFactoryInterface;

class EnvironmentBuilder
{
    private $id;
    private $transactionFactory;
    private $dataSource;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function transactionFactory(TransactionFactoryInterface $transactionFactory): EnvironmentBuilder
    {
        $this->transactionFactory = $transactionFactory;
        return $this;
    }

    public function dataSource(DataSourceInterface $dataSource): EnvironmentBuilder
    {
        $this->dataSource = $dataSource;
        return $this;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function build(): Environment
    {
        return new Environment($this->id, $this->transactionFactory, $this->dataSource);
    }
}
