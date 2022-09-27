<?php

namespace Tests\Domain\Misc\Generics;

class GenericConcrete extends GenericSubclass implements GenericInterface
{
    private $id;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = intval($id);
    }
}
