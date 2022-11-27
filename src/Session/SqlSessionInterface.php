<?php

namespace MyBatis\Session;

use Doctrine\DBAL\Connection;
use MyBatis\Cursor\CursorInterface;

interface SqlSessionInterface
{
    public function selectOne(string $statement, $parameter = null);

    public function selectList(string $statement, $parameter = null, ?RowBounds $rowBounds = null, ?ResultHandlerInterface $handler = null): array;

    public function selectMap(string $statement, $parameter, string $mapKey, ?RowBounds $rowBounds = null): array;

    //public function selectCursor(string $statement, $parameter = null, ?RowBounds $rowBounds = null): CursorInterface;

    public function select(string $statement, $parameter = null, ?RowBounds $rowBounds = null, ?ResultHandlerInterface $handler = null): void;

    public function insert(string $statement, $parameter = null);

    public function update(string $statement, $parameter = null);

    public function delete(string $statement, $parameter = null);

    public function commit(bool $force = false): void;

    public function rollback(bool $force = false): void;

    public function flushStatements(): array;

    public function close(): void;

    public function getConfiguration(): Configuration;

    public function getConnection(): Connection;
}
