<?php

namespace MyBatis\Cache\Decorators;

use MyBatis\Cache\{
    CacheInterface,
    CacheKey
};

class TransactionalCache implements CacheInterface
{
    private $delegate;
    private $clearOnCommit = false;
    private $entriesToAddOnCommit = [];
    private $entriesMissedInCache = [];

    public function __construct(?CacheInterface $delegate)
    {
        $this->delegate = $delegate;
        $this->clearOnCommit = false;
    }

    public function getId(): string
    {
        return $this->delegate->getId();
    }

    public function getSize(): int
    {
        return $this->delegate->getSize();
    }

    public function getObject($key)
    {
        $object = $this->delegate->getObject($key);
        if ($object === null) {
            $this->entriesMissedInCache[] = $key;
        }
        if ($this->clearOnCommit) {
            return null;
        } else {
            return $object;
        }
    }

    public function putObject($key, $object): void
    {
        if ($key instanceof CacheKey) {
            $exists = false;
            foreach ($this->entriesToAddOnCommit as $it => $pair) {
                if ($pair[0]->equals($key)) {
                    $exists = true;
                    $this->entriesToAddOnCommit[$it] = [ $key, $object ];
                    break;
                }
            }
            if (!$exists) {
                $this->entriesToAddOnCommit[] = [ $key, $object ];
            }
        } else {
            $this->entriesToAddOnCommit[$key] = $object;
        }
    }

    public function removeObject($key)
    {
        return null;
    }

    public function clear(): void
    {
        $this->clearOnCommit = true;
        $this->entriesToAddOnCommit = [];
    }

    public function commit(): void
    {
        if ($this->clearOnCommit) {
            $this->delegate->clear();
        }
        $this->flushPendingEntries();
        $this->reset();
    }

    public function rollback(): void
    {
        $this->unlockMissedEntries();
        $this->reset();
    }

    private function reset(): void
    {
        $this->clearOnCommit = false;
        $this->entriesToAddOnCommit = [];
        $this->entriesMissedInCache = [];
    }

    private function flushPendingEntries(): void
    {
        foreach ($this->entriesToAddOnCommit as $key => $value) {
            if (is_array($value) && $value[0] instanceof CacheKey) {
                $this->delegate->putObject($value[0], $value[1]);
            } else {
                $this->delegate->putObject($key, $value);
            }
        }
        foreach ($this->entriesMissedInCache as $key) {
            if ($key instanceof CacheKey) {
                $exists = false;
                foreach ($this->entriesToAddOnCommit as $pair) {
                    if ($pair[0]->equals($key)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $this->delegate->putObject($key, null);
                }
            } elseif (!array_key_exists($key, $this->entriesToAddOnCommit)) {
                $this->delegate->putObject($key, null);
            }
        }
    }

    private function unlockMissedEntries(): void
    {
        foreach ($this->entriesMissedInCache as $key) {
            try {
                $this->delegate->removeObject($key);
            } catch (\Exception $e) {
                //log.warn("Unexpected exception while notifying a rollback to the cache adapter. "
                //    + "Consider upgrading your cache adapter to the latest version. Cause: " + e);
            }
        }
    }
}
