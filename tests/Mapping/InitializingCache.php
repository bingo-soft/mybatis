<?php

namespace Tests\Mapping;

use MyBatis\Builder\InitializingObjectInterface;
use MyBatis\Cache\Impl\PerpetualCache;

class InitializingCache extends PerpetualCache implements InitializingObjectInterface
{
    public $initialized = false;

    public function __construct(string $id)
    {
        parent::__construct($id);
    }

    public function initialize(): void
    {
        $this->initialized = true;
    }
}
