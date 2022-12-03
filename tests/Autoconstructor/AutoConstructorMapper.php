<?php

namespace Tests\Autoconstructor;

use MyBatis\Annotations\{
    ResultType,
    ListType,
    Select
};

interface AutoConstructorMapper
{
    #[Select("SELECT * FROM subject WHERE id = #{id}")]
    public function getSubject(int $id): PrimitiveSubject;

    #[Select("SELECT * FROM subject")]
    #[ResultType(new ListType(PrimitiveSubject::class))]
    public function getSubjects(): array;

    #[Select("SELECT * FROM subject")]
    #[ResultType(new ListType(AnnotatedSubject::class))]
    public function getAnnotatedSubjects(): array;

    #[Select("SELECT * FROM subject")]
    #[ResultType(new ListType(BadSubject::class))]
    public function getBadSubjects(): array;
}
