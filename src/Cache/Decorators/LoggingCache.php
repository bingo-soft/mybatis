<?php

namespace MyBatis\Cache\Decorators;

use MyBatis\Cache\CacheInterface;

class LoggingCache implements CacheInterface
{
    private $log;
    private $delegate;
    protected $requests = 0;
    protected $hits = 0;

    public function __construct(CacheInterface $delegate)
    {
        $this->delegate = $delegate;
        //$this->log = LogFactory.getLog(getId());
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
        $this->delegate->putObject($key, $object);
    }

    public function getObject($key)
    {
        $this->requests += 1;
        $value = $this->delegate->getObject($key);
        if ($value !== null) {
            $this->hits += 1;
        }
        /*if (log.isDebugEnabled()) {
            log.debug("Cache Hit Ratio [" + getId() + "]: " + getHitRatio());
        }*/
        return $value;
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
        return $this->delegate == $obj;
    }

    private function getHitRatio(): float
    {
        return $this->hits / $this->requests;
    }
}
