<?php

namespace Tests\Submitted\DynSql;

use MyBatis\Annotations\Param;

interface DynSqlMapper
{
    public function selectDescription(
        #[Param("p")]
        string $p
    ): string;

    public function selectDescriptionById(int $id): array;

    public function selectDescriptionByConditions(Conditions $conditions): array;

    public function selectDescriptionByConditions2(Conditions $conditions): array;

    public function selectDescriptionByConditions3(Conditions $conditions): array;
}
