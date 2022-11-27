<?php

namespace MyBatis\Executor\ResultSet;

use Doctrine\DBAL\Statement;
use MyBatis\Cursor\CursorInterface;

interface ResultSetHandlerInterface
{
    public function handleResultSets(Statement $stmt): array;

    public function handleCursorResultSets(Statement $stmt): CursorInterface;
}
