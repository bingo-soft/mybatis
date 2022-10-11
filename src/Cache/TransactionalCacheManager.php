<?php

namespace MyBatis\Cache;

use MyBatis\Cache\Decorators\TransactionalCache;
use MyBatis\Util\MapUtil;

class TransactionalCacheManager
{
    private $transactionalCaches = [];
  
    public function clear(CacheInterface $cache): void
    {
        $this->getTransactionalCache($cache)->clear();
    }
  
    public function getObject(CacheInterface $cache, CacheKey $key)
    {
        return $this->getTransactionalCache($cache)->getObject($key);
    }
  
    public function putObject(CacheInterface $cache, CacheKey $key, $value): void
    {
        $this->getTransactionalCache($cache)->putObject($key, $value);
    }
  
    public function commit(): void
    {
        foreach (array_values($this->transactionalCaches) as $txCache) {
            $txCache->commit();
        }
    }
  
    public function rollback(): void
    {
        foreach (array_values($this->transactionalCaches) as $txCache) {
            $txCache->rollback();
        }
    }
  
    private function getTransactionalCache(CacheInterface $cache): TransactionalCache
    {
        /*return MapUtil::computeIfAbsent($this->transactionalCaches, $cache, function () {
            return new TransactionalCache();
        });*/
        $key = get_class($cache);
        if (!array_key_exists($key, $this->transactionalCaches)) {
            $this->transactionalCaches[$key] = new TransactionalCache($cache);
        }
        return $this->transactionalCaches[$key];
    }  
}
