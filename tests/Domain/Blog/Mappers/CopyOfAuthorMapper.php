<?php

namespace Tests\Domain\Blog\Mappers;

use Tests\Domain\Blog\Author;
use MyBatis\Session\ResultHandlerInterface;

interface CopyOfAuthorMapper
{
    public function selectAllAuthors(?ResultHandlerInterface $handler = null);

    public function selectAuthor(int $id, ?ResultHandlerInterface $handler);

    public function insertAuthor(Author $author): void;

    public function deleteAuthor(int $id): int;

    public function updateAuthor(Author $author): int;
}
