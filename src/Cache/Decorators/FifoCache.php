<?php

namespace MyBatis\Cache\Decorators;

use MyBatis\Cache\CacheInterface;

class FifoCache implements CacheInterface
{
    private $delegate;
    private $keyList = [];
    private $size;

    public function __construct(CacheInterface $delegate)
    {
        $this->delegate = $delegate;
        $this->keyList = [];
        $this->size = 1024;
    }

    public function getId(): string
    {
        return $this->delegate->getId();
    }

    public function getSize(): int
    {
        return $this->delegate->getSize();
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function putObject($key, $value): void
    {
        $this->cycleKeyList($key);
        $this->delegate->putObject($key, $value);
    }

    public function getObject($key)
    {
        return $this->delegate->getObject($key);
    }

    public function removeObject($key)
    {
        return $this->delegate->removeObject($key);
    }

    public function clear(): void
    {
        $this->delegate->clear();
        $this->keyList = [];
    }

    private function cycleKeyList($key): void
    {
        $this->keyList[] = $key;
        if (count($this->keyList) > $this->size) {
            $oldestKey = array_shift($this->keyList);
            $this->delegate->removeObject($oldestKey);
        }
    }
}
