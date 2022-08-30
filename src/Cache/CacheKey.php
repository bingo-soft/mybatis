<?php

namespace MyBatis\Cache;

class CacheKey implements \Serializable
{
    private const DEFAULT_MULTIPLIER = 37;
    private $multiplier;
    private $count;
    private $updateList = [];
    private static $NULL_CACHE_KEY;

    public function __construct(array $objects = [])
    {
        $this->multiplier = self::DEFAULT_MULTIPLIER;
        $this->count = 0;
        $this->updateList = [];
        if (!empty($objects)) {
            $this->updateAll($objects);
        }
    }

    public static function nullCacheKey(): CacheKey
    {
        return new NullCacheKey();
    }

    public function serialize()
    {
        return json_encode([
            'count' => $this->count,
            'multiplier' => $this->multiplier,
            'updateList' => $this->updateList
        ]);
    }

    public function unserialize($data)
    {
        $json = json_decode($data);
        $this->count = $json->count;
        $this->multiplier = $json->multiplier;
        $this->updateList = $json->updateList;
    }

    public function getUpdateCount(): int
    {
        return count($this->updateList);
    }

    public function update($object): void
    {
        $this->count += 1;
        $this->updateList[] = $object;
    }

    public function updateAll(array $objects): void
    {
        foreach ($objects as $o) {
            $this->update($o);
        }
    }

    public function equals($object): bool
    {
        if ($this == $object) {
            return true;
        }
        if (!($object instanceof CacheKey)) {
            return false;
        }

        if ($this->count != $object->count) {
            return false;
        }

        for ($i = 0; $i < count($this->updateList); $i += 1) {
            $thisObject = $this->updateList[$i];
            $thatObject = $object->updateList[$i];
            if ($thisObject != $thatObject) {
                return false;
            }
        }
        return true;
    }
}
