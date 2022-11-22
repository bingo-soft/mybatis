<?php

namespace Tests\Autoconstructor;

class PrimitiveSubject
{
    public function __construct(private int $id, private string $name, private int $age, private int $height, private int $weight, private bool $active, private string $dt)
    {
    }
}
