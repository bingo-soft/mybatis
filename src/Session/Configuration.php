<?php

namespace MyBatis\Session;

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
}
