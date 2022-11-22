<?php

namespace Tests\Domain\Blog\Mappers;

use MyBatis\Session\RowBounds;

interface BlogMapper
{
    public function selectAllPosts(?RowBounds $rowBounds = null, $param = null): array;
}
