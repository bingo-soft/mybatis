<?php

namespace MyBatis\Session;

use Doctrine\DBAL\Connection;
use MyBatis\Cursor\CursorInterface;
use Util\Proxy\ProxyFactory;

class SqlSessionManager implements SqlSessionFactoryInterface, SqlSessionInterface
{
    private $sqlSessionFactory;
    private $sqlSessionProxy;

    private $localSqlSession = null;

    private function __construct(SqlSessionFactoryInterface $sqlSessionFactory)
    {
        $this->sqlSessionFactory = $sqlSessionFactory;
        $enhancer = new ProxyFactory();
        $enhancer->setSuperclass(get_class($sqlSessionFactory));
        $enhancer->setInterfaces([ SqlSessionInterface::class ]);
        $this->sqlSessionProxy = $enhancer->create([]);
        $this->sqlSessionProxy->setHandler(new SqlSessionInterceptor($this));
    }

    public static function newInstance($input, /*string|array*/$envOrProperties = null, array $props = []): SqlSessionManager
    {
        if ($input instanceof SqlSessionFactoryInterface) {
            return new SqlSessionManager($input);
        } else {
            $environment = null;
            $properties = [];
            if (is_string($envOrProperties)) {
                $environment = $envOrProperties;
            } elseif (is_array($envOrProperties)) {
                $properties = $envOrProperties;
            }
            if (!empty($props)) {
                $properties = $props;
            }
            return new SqlSessionManager((new SqlSessionFactoryBuilder())->build($input, $environment, $properties));
        }
    }

    public function startManagedSession(?Connection $connection = null): void
    {
        $this->localSqlSession = $this->openSession($connection);
    }

    public function isManagedSessionStarted(): bool
    {
        return $this->localSqlSession !== null;
    }

    public function getLocalSqlSession()
    {
        return $this->localSqlSession;
    }

    public function openSession(/*?Connection*/$connectionOrIsolationLevel = null): SqlSessionInterface
    {
        return $this->sqlSessionFactory->openSession($connectionOrIsolationLevel);
    }

    public function getConfiguration(): Configuration
    {
        return $this->sqlSessionFactory->getConfiguration();
    }

    public function selectOne(string $statement, $parameter = null)
    {
        return $this->sqlSessionProxy->selectOne($statement, $parameter);
    }

    public function selectList(string $statement, $parameter = null, ?RowBounds $rowBounds = null, ?ResultHandlerInterface $handler = null): array
    {
        return $this->sqlSessionProxy->selectList($statement, $parameter, $rowBounds, $handler);
    }

    public function select(string $statement, $parameter = null, ?RowBounds $rowBounds = null, ?ResultHandlerInterface $handler = null): void
    {
        $this->sqlSessionProxy->select($statement, $parameter, $rowBounds, $handler);
    }

    public function insert(string $statement, $parameter = null)
    {
        return $this->sqlSessionProxy->insert($statement, $parameter);
    }

    public function update(string $statement, $parameter = null)
    {
        return $this->sqlSessionProxy->update($statement, $parameter);
    }

    public function delete(string $statement, $parameter = null)
    {
        return $this->sqlSessionProxy->delete($statement, $parameter);
    }

    public function getMapper(string $type)
    {
        return $this->getConfiguration()->getMapper($type, $this);
    }

    public function getConnection(): Connection
    {
        $sqlSession = $this->localSqlSession;
        if ($sqlSession == null) {
            throw new SqlSessionException("Error:  Cannot get connection.  No managed session is started.");
        }
        return $sqlSession->getConnection();
    }

    public function clearCache(): void
    {
        //
    }

    public function commit(bool $force = false): void
    {
        $sqlSession = $this->localSqlSession;
        if ($sqlSession == null) {
            throw new SqlSessionException("Error:  Cannot commit.  No managed session is started.");
        }
        $sqlSession->commit($force);
    }

    public function rollback(bool $force = false): void
    {
        $sqlSession = $this->localSqlSession;
        if ($sqlSession === null) {
            throw new SqlSessionException("Error:  Cannot rollback.  No managed session is started.");
        }
        $sqlSession->rollback($force);
    }

    public function flushStatements(): array
    {
        $sqlSession = $this->localSqlSession;
        if ($sqlSession === null) {
            throw new SqlSessionException("Error:  Cannot rollback.  No managed session is started.");
        }
        return $sqlSession->flushStatements();
    }

    public function close(): void
    {
        $sqlSession = $this->localSqlSession;
        if ($sqlSession == null) {
            throw new SqlSessionException("Error:  Cannot close.  No managed session is started.");
        }
        try {
            $sqlSession->close();
        } finally {
            $this->localSqlSession = null;
        }
    }
}
