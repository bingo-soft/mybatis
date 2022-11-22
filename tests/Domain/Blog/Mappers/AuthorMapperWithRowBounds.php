<?php

namespace Tests\Domain\Blog\Mappers;

use MyBatis\Annotations\Select;
use MyBatis\Session\RowBounds;

interface AuthorMapperWithRowBounds
{
    #[Select("select id, username, password, email, bio, favourite_section from author where id = #{id}")]
    public function selectAuthor(int $id, RowBounds $bounds1, RowBounds $bounds2): void;
}
