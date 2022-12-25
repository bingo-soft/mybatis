<?php

namespace Tests\Submitted\ArrayResultType;

use MyBatis\Annotations\{
    ListType,
    ResultType,
    Select
};

interface Mapper
{
    #[Select("select * from users")]
    #[ResultType(new ListType(User::class))]
    public function getUsers(): array;

    #[ResultType(new ListType(User::class))]
    public function getUsersXml(): array;

    #[Select("select id from users")]
    #[ResultType(new ListType("int"))]
    public function getUserIds(): array;

    #[Select("select id from users")]
    #[ResultType(new ListType("int"))]
    public function getUserIdsPrimitive(): array;
}
