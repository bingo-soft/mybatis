<?php

namespace MyBatis\Cache;

class NullCacheKey extends CacheKey
{
    public function __construct()
    {
        parent::__construct();
    }

    public function update($object): void
    {
        throw new CacheException("Not allowed to update a null cache key instance.");
    }

    public function updateAll(array $objects): void
    {
        throw new CacheException("Not allowed to update a null cache key instance.");
    }
}
