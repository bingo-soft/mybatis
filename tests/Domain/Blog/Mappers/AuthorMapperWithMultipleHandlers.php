<?php

namespace Tests\Domain\Blog\Mappers;

use MyBatis\Annotations\Select;
use MyBatis\Session\ResultHandlerInterface;

interface AuthorMapperWithMultipleHandlers
{
    #[Select("select id, username, password, email, bio, favourite_section from author where id = #{id}")]
    public function selectAuthor(int $id, ResultHandlerInterface $handler1, ResultHandlerInterface $handler2): void;
}
