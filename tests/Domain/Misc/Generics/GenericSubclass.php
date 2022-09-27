<?php

namespace Tests\Domain\Misc\Generics;

abstract class GenericSubclass extends GenericAbstract
{
    abstract public function getId(): int;
}
