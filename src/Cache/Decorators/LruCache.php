<?php

namespace MyBatis\Cache\Decorators;

use Heap\Tree\FibonacciHeap;
use MyBatis\Cache\CacheInterface;

class LruCache implements CacheInterface
{
    private $delegate;
    private $heap;
    private $keyRanks = [];
    private $rank = 0;
    private $size = 1024;

    public function __construct(CacheInterface $delegate)
    {
        $this->delegate = $delegate;
        $this->heap = new FibonacciHeap();
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
        $this->rank += 1;
        if (array_key_exists($key, $this->keyRanks)) {
            $this->keyRanks[$key] = $this->rank;
            $this->delegate->putObject($key, $value);
            return;
        }

        if ($this->size == 0) {
            $this->cycleKeyList();
        }

        $this->keyRanks[$key] = $this->rank;
        $this->delegate->putObject($key, $value);
        $this->heap->insert($this->rank, $key);
        $this->size -= 1;
    }

    public function getObject($key)
    {
        if (!array_key_exists($key, $this->keyRanks)) {
            return null;
        }
        $value = $this->keyRanks[$key];
        $this->rank += 1;
        $this->keyRanks[$key] = $this->rank;

        return $this->delegate->getObject($key);
    }

    public function removeObject($key)
    {
        return $this->delegate->removeObject($key);
    }

    public function clear(): void
    {
        $this->delegate->clear();
        $this->heap->clear();
    }

    private function cycleKeyList(): void
    {
        while (true) {
            $minNode = $this->heap->findMin();
            $lruAt = $minNode->getKey();
            $key = $minNode->getValue();

            $cur = $this->keyRanks[$key];
            if ($cur > $lruAt) {
                $this->heap->deleteMin();
                $this->heap->insert($cur, $key);
            } else {
                $this->heap->deleteMin();
                unset($this->keyRanks[$key]);
                $this->delegate->removeObject($key);
                $this->size += 1;
                return;
            }
        }
    }
}
