<?php

namespace Tests\Submitted\OgnlEnum;

use MyBatis\Annotations\{
    Arg,
    CacheNamespace,
    ConstructorArgs,
    Flush,
    ListType,
    Param,
    Result,
    Results,
    ResultType,
    Select
};

interface PersonMapper
{
    #[ResultType(new ListType(Person::class))]
    public function selectAllByType(Type $type): array;

    #[ResultType(new ListType(Person::class))]
    public function selectAllByTypeNameAttribute(Type $type);

    #[ResultType(new ListType(Person::class))]
    public function selectAllByTypeWithInterface(PersonType $personType);

    #[ResultType(new ListType(Person::class))]
    public function selectAllByTypeNameAttributeWithInterface(PersonType $personType);
}
