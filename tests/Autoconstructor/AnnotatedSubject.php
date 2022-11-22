<?php

namespace Tests\Autoconstructor;

use MyBatis\Annotations\AutomapConstructor;

class AnnotatedSubject
{
    #[AutomapConstructor]
    public function __construct(private int $id, private string $name, private int $age, private int $height, private int $weight)
    {
    }
}
