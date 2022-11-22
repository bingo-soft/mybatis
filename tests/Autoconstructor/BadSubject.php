<?php

namespace Tests\Autoconstructor;

class BadSubject
{
    public function __construct(private int $id, private string $name, private int $age, private Height $height, private ?float $weight = 0)
    {
    }
}
