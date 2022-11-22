<?php

namespace Tests\Builder;

use Tests\Domain\Blog\Author;

interface CachedAuthorMapper
{
    public function selectAllAuthors(): Author;
    public function selectAuthorWithInlineParams(int $id): Author;
    public function insertAuthor(Author $author): void;
    public function updateAuthor(Author $author): bool;
    public function deleteAuthor(int $id): bool;
}
