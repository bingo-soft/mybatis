<?php

namespace Tests\Submitted\DynSql;

class Conditions
{
    private $id;

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
