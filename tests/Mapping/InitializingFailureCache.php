<?php

namespace Tests\Mapping;

use MyBatis\Builder\InitializingObjectInterface;
use MyBatis\Cache\Impl\PerpetualCache;

class InitializingFailureCache extends PerpetualCache implements InitializingObjectInterface
{
    public function __construct(string $id)
    {
        parent::__construct($id);
    }

    public function initialize(): void
    {
        throw new \Exception("error");
    }
}
