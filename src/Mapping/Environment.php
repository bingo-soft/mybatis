<?php

namespace MyBatis\Mapping;

use MyBatis\DataSource\DataSourceInterface;
use MyBatis\Transaction\TransactionFactoryInterface;

class Environment
{
    private $id;
    private $transactionFactory;
    private $dataSource;

    public function __construct(string $id, TransactionFactoryInterface $transactionFactory, DataSourceInterface $dataSource)
    {
        $this->id = $id;
        $this->transactionFactory = $transactionFactory;
        $this->dataSource = $dataSource;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTransactionFactory(): TransactionFactoryInterface
    {
        return $this->transactionFactory;
    }

    public function getDataSource(): DataSourceInterface
    {
        return $this->dataSource;
    }
}
