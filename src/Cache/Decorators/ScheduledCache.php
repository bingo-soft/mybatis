<?php

namespace MyBatis\Cache\Decorators;

use MyBatis\Cache\CacheInterface;

class ScheduledCache implements CacheInterface
{
    private $delegate;
    protected $clearInterval;
    protected $lastClear;

    public function __construct(CacheInterface $delegate)
    {
        $this->delegate = $delegate;
        $this->clearInterval = 3600;
        $this->lastClear = time();
    }

    public function setClearInterval(int $clearInterval): void
    {
        $this->clearInterval = $clearInterval;
    }

    public function getId(): string
    {
        return $this->delegate->getId();
    }

    public function getSize(): int
    {
        $this->clearWhenStale();
        return $this->delegate->getSize();
    }

    public function putObject($key, $object): void
    {
        $this->clearWhenStale();
        $this->delegate->putObject($key, $object);
    }

    public function getObject($key)
    {
        return $this->clearWhenStale() ? null : $this->delegate->getObject($key);
    }

    public function removeObject($key)
    {
        $this->clearWhenStale();
        return $this->delegate->removeObject($key);
    }

    public function clear(): void
    {
        $this->lastClear = time();
        $this->delegate->clear();
    }

    public function equals($obj): bool
    {
        return $this->delegate->equals($obj);
    }

    private function clearWhenStale(): bool
    {
        if (time() - $this->lastClear > $this->clearInterval) {
            $this->clear();
            return true;
        }
        return false;
    }
}
