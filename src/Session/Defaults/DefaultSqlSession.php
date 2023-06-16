<?php

namespace MyBatis\Session\Defaults;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionLost;
use MyBatis\Cursor\CursorInterface;
use MyBatis\Executor\ExecutorInterface;
use MyBatis\Executor\Result\{
    DefaultMapResultHandler,
    DefaultResultContext
};
use MyBatis\Reflection\ParamNameResolver;
use MyBatis\Session\{
    Configuration,
    ResultHandlerInterface,
    RowBounds,
    SqlSessionInterface
};

class DefaultSqlSession implements SqlSessionInterface
{
    private $configuration;
    private $executor;

    private $autoCommit;
    private $dirty = false;
    private $cursorList = [];

    public function __construct(Configuration $configuration, ExecutorInterface $executor, ?bool $autoCommit = false)
    {
        $this->configuration = $configuration;
        $this->executor = $executor;
        $this->autoCommit = $autoCommit;
    }

    public function selectOne(string $statement, $parameter = null)
    {
        $list = $this->selectList($statement, $parameter);
        if (count($list) == 1) {
            return $list[0];
        } elseif (count($list) > 1) {
            throw new \Exception("Expected one result (or null) to be returned by selectOne(), but found: " . count($list));
        } else {
            return null;
        }
    }

    public function selectList(string $statement, $parameter = null, ?RowBounds $rowBounds = null, ?ResultHandlerInterface $handler = null): array
    {
        $attempts = 0;
        $maxReconnectAttempts = 0;
        $dataSource = null;
        $mappedStatement = null;
        while (true) {
            try {
                $mappedStatement = $this->configuration->getMappedStatement($statement);
                return $this->executor->query($mappedStatement, $this->wrapCollection($parameter), $rowBounds, $handler);
            } catch (ConnectionLost $e) {
                if ($dataSource == null) {
                    $dataSource = $mappedStatement->getConfiguration()->getEnvironment()->getDataSource();
                    $maxReconnectAttempts = $dataSource->getReconnectAttempts();
                }
                if ($maxReconnectAttempts > 0 && $attempts < $maxReconnectAttempts) {
                    $attempts += 1;
                    $dataSource->reconnect();
                } else {
                    throw new \Exception("Error querying database. Connection lost, number of attempts $attempts. Cause: " . $e->getMessage());
                }
            } catch (\Throwable $e) {
                throw new \Exception("Error querying database. Cause: " . $e->getMessage());
            }
        }
    }

    public function selectCursor(string $statement, $parameter = null, ?RowBounds $rowBounds = null): CursorInterface
    {
        $attempts = 0;
        $maxReconnectAttempts = 0;
        $dataSource = null;
        $mappedStatement = null;
        while (true) {
            try {
                $mappedStatement = $this->configuration->getMappedStatement($statement);
                $cursor = $this->executor->queryCursor($mappedStatement, $this->wrapCollection($parameter), $rowBounds);
                return $cursor;
            } catch (ConnectionLost $e) {
                if ($dataSource == null) {
                    $dataSource = $mappedStatement->getConfiguration()->getEnvironment()->getDataSource();
                    $maxReconnectAttempts = $dataSource->getReconnectAttempts();
                }
                if ($maxReconnectAttempts > 0 && $attempts < $maxReconnectAttempts) {
                    $attempts += 1;
                    $dataSource->reconnect();
                } else {
                    throw new \Exception("Error querying database. Connection lost, number of attempts $attempts. Cause: " . $e->getMessage());
                }
            } catch (\Throwable $e) {
                throw new \Exception("Error querying database. Cause: " . $e->getMessage());
            }
        }
    }

    public function selectMap(string $statement, $parameter, string $mapKey, ?RowBounds $rowBounds = null): array
    {
        $rowBounds ??= RowBounds::default();
        $list = $this->selectList($statement, $parameter, $rowBounds);
        $mapResultHandler = new DefaultMapResultHandler($mapKey);
        $context = new DefaultResultContext();
        foreach ($list as $o) {
            $context->nextResultObject($o);
            $mapResultHandler->handleResult($context);
        }
        return $mapResultHandler->getMappedResults();
    }

    public function select(string $statement, $parameter = null, ?RowBounds $rowBounds = null, ?ResultHandlerInterface $handler = null): void
    {
        $this->selectList($statement, $parameter, $rowBounds ?? RowBounds::default(), $handler);
    }

    public function insert(string $statement, $parameter = null)
    {
        return $this->update($statement, $parameter);
    }

    public function update(string $statement, $parameter = null)
    {
        $attempts = 0;
        $maxReconnectAttempts = 0;
        $dataSource = null;
        $mappedStatement = null;
        while (true) {
            try {
                $this->dirty = true;
                $mappedStatement = $this->configuration->getMappedStatement($statement);
                return $this->executor->update($mappedStatement, $this->wrapCollection($parameter));
            } catch (ConnectionLost $e) {
                if ($dataSource == null) {
                    $dataSource = $mappedStatement->getConfiguration()->getEnvironment()->getDataSource();
                    $maxReconnectAttempts = $dataSource->getReconnectAttempts();
                }
                if ($maxReconnectAttempts > 0 && $attempts < $maxReconnectAttempts) {
                    $attempts += 1;
                    $dataSource->reconnect();
                } else {
                    throw new \Exception("Error updating database. Connection lost, number of attempts $attempts. Cause: " . $e->getMessage());
                }
            } catch (\Throwable $e) {
                throw new \Exception("Error updating database. Cause: " . $e->getMessage());
            }
        }
    }

    public function delete(string $statement, $parameter = null)
    {
        return $this->update($statement, $parameter);
    }

    public function commit(bool $force = false): void
    {
        try {
            $this->executor->commit($this->isCommitOrRollbackRequired($force));
            $dirty = false;
        } catch (\Exception $e) {
            throw new \Exception("Error committing transaction.  Cause: " . $e->getMessage());
        } finally {
        }
    }

    public function rollback(bool $force = false): void
    {
        try {
            $this->executor->rollback($this->isCommitOrRollbackRequired($force));
            $this->dirty = false;
        } catch (\Exception $e) {
            throw new \Exception("Error rolling back transaction.  Cause: " . $e->getMessage());
        } finally {
        }
    }

    public function flushStatements(): array
    {
        try {
            return $this->executor->flushStatements();
        } catch (\Exception $e) {
            throw new \Exception("Error flushing statements.  Cause: " . $e->getMessage());
        } finally {
        }
    }

    public function close(): void
    {
        try {
            $this->executor->close($this->isCommitOrRollbackRequired(false));
            $this->closeCursors();
            $this->dirty = false;
        } finally {
        }
    }

    private function closeCursors(): void
    {
        if (!empty($this->cursorList)) {
            foreach ($this->cursorList as $cursor) {
                try {
                    $cursor->close();
                } catch (\Exception $e) {
                    throw  new \Exception("Error closing cursor.  Cause: " . $e->getMessage());
                }
            }
            $this->cursorList = [];
        }
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getMapper(string $type)
    {
        return $this->configuration->getMapper($type, $this);
    }

    public function getConnection(): Connection
    {
        try {
            return $this->executor->getTransaction()->getConnection();
        } catch (\Exception $e) {
            throw new \Exception("Error getting a new connection.  Cause: " . $e->getMessage());
        }
    }

    public function clearCache(): void
    {
        $this->executor->clearLocalCache();
    }

    private function registerCursor(CursorInterface $cursor): void
    {
        $this->cursorList[] = $cursor;
    }

    private function isCommitOrRollbackRequired(bool $force): bool
    {
        return (!$this->autoCommit && $this->dirty) || $force;
    }

    private function wrapCollection($object)
    {
        return ParamNameResolver::wrapToMapIfCollection($object, null);
    }
}
