<?php

namespace MyBatis\Session;

use MyBatis\Binding\MapperRegistry;
use MyBatis\Builder\{
    CacheRefResolver,
    IncompleteElementException,
    ResultMapResolver
};
use Util\Reflection\MetaObject;

class Configuration
{
    protected $useActualParamName = true;

    public function isUseActualParamName(): bool
    {
        return $this->useActualParamName;
    }

    public function setUseActualParamName(bool $useActualParamName): void
    {
        $this->useActualParamName = $useActualParamName;
    }

    public function newMetaObject(&$object): MetaObject
    {
        return new MetaObject($object);
    }
}
