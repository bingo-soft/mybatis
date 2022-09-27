<?php

namespace MyBatis\Executor\Parameter;

use Doctrine\DBAL\Statement;

interface ParameterHandlerInterface
{
    public function getParameterObject();

    public function setParameters(Statement $ps): void;
}
