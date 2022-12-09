<?php

namespace Tests\Domain\Blog\Mappers;

use MyBatis\Annotations\{
    ListType,
    MapType,
    ResultType,
    Select
};
use MyBatis\Session\ResultHandlerInterface;
use Tests\Domain\Blog\Author;

interface AuthorMapper
{
    //public function selectAllAuthors(): array;

    public function selectAllAuthorsSet(): array;

    public function selectAllAuthorsVector(): array;

    public function selectAllAuthorsLinkedList(): array;

    public function selectAllAuthorsArray(): array;

    #[ResultType(new ListType(Author::class))]
    public function selectAllAuthors(?ResultHandlerInterface $handler = null): array;

    public function selectAllAuthorsWithHandler(ResultHandlerInterface $handler = null): void;

    //public function selectAuthor(int $id): Author;

    public function selectAuthorLinkedHashMap(int $id): array;

    #[ResultType(Author::class)]
    public function selectAuthor(int $id, ?ResultHandlerInterface $handler = null): Author;

    public function selectAuthorWithHandler(int $id, ResultHandlerInterface $handler): void;

    #[Select("select")]
    public function selectAuthor2(int $id, ResultHandlerInterface $handler): void;

    public function insertAuthor(Author $author): void;

    public function deleteAuthor(int $id): int;

    public function updateAuthor(Author $author): int;
}
