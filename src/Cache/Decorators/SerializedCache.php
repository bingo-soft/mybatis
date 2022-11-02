<?php

namespace MyBatis\Cache\Decorators;

use MyBatis\Cache\{
    CacheException,
    CacheInterface
};

class SerializedCache implements CacheInterface
{
    private $delegate;

    public function __construct(CacheInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    public function getId(): string
    {
        return $this->delegate->getId();
    }

    public function getSize(): int
    {
        return $this->delegate->getSize();
    }

    public function putObject($key, $object): void
    {
        if ($object == null || method_exists($object, '__serialize')) {
            $this->delegate->putObject($key, serialize($object));
        } else {
            throw new CacheException("SharedCache failed to make a copy of a non-serializable object");
        }
    }

    public function getObject($key)
    {
        $object = $this->delegate->getObject($key);
        return $object === null ? null : unserialize($object);
    }

    public function removeObject($key)
    {
        return $this->delegate->removeObject($key);
    }

    public function clear(): void
    {
        $this->delegate->clear();
    }

    public function equals($obj): bool
    {
        return $this->delegate->equals($obj);
    }
}
