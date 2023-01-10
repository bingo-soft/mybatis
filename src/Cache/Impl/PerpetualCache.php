<?php

namespace MyBatis\Cache\Impl;

use MyBatis\Cache\{
    CacheException,
    CacheInterface,
    CacheKey
};

class PerpetualCache implements CacheInterface
{
    private $id;

    private $cache = [];

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSize(): int
    {
        return count($this->cache);
    }

    public function putObject($key, $value): void
    {
        if ($key instanceof CacheKey) {
            $exists = false;
            foreach ($this->cache as $id => $pair) {
                if (is_array($pair) && $pair[0]->equals($key)) {
                    $exists = true;
                    $this->cache[$id] = [$key, $value];
                    break;
                }
            }

            if (!$exists) {
                $this->cache[] = [ $key, $value ];
            }
        } else {
            $this->cache[$key] = $value;
        }
    }

    public function getObject($key)
    {
        if ($key instanceof CacheKey) {
            foreach ($this->cache as $pair) {
                if (is_array($pair) && $pair[0]->equals($key)) {
                    return $pair[1];
                }
            }
            return null;
        }
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }
        return null;
    }

    public function removeObject($key)
    {
        if ($key instanceof CacheKey) {
            foreach ($this->cache as $it => $pair) {
                if (is_array($pair) && $pair[0]->equals($key)) {
                    $del = $pair[1];
                    unset($this->cache[$it]);
                    return $del;
                }
            }
            return false;
        }
        if (array_key_exists($key, $this->cache)) {
            $del = $this->cache[$key];
            unset($this->cache[$key]);
            return $del;
        }
    }

    public function clear(): void
    {
        $this->cache = [];
    }

    public function equals($o): bool
    {
        if ($this->getId() == null) {
            throw new CacheException("Cache instances require an ID.");
        }
        if ($this == $o) {
            return true;
        }
        if (!($o instanceof CacheInterface)) {
            return false;
        }

        return $this->getId() == $o->getId();
    }
}
