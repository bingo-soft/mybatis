<?php

namespace Tests\Autoconstructor;

use MyBatis\Annotations\Select;

interface AutoConstructorMapper
{
    #[Select("SELECT * FROM subject WHERE id = #{id}")]
    public function getSubject(int $id);

    #[Select("SELECT * FROM subject")]
    public function getSubjects(): array;

    #[Select("SELECT * FROM subject")]
    public function getAnnotatedSubjects(): array;

    #[Select("SELECT * FROM subject")]
    public function getBadSubjects(): array;
}
