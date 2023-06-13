<?php

namespace Tests\Binding;

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
use MyBatis\Session\RowBounds;
use Tests\Domain\Blog\{
    Author,
    Post,
    Section
};

#[CacheNamespace(readWrite: false)]
interface BoundAuthorMapper
{
    //======================================================

    public function findPostsInArray(array $ids): array;

    //======================================================

    public function findPostsInList(array $ids): array;

    //======================================================

    public function insertAuthor(Author $author): int;

    public function insertAuthorInvalidSelectKey(Author $author): int;

    public function insertAuthorInvalidInsert(Author $author): int;

    public function insertAuthorDynamic(Author $author): int;

    //======================================================

    #[ConstructorArgs([ new Arg(column: "AUTHOR_ID", phpType: "int")])]
    #[Results([
        new Result(property : "username", column : "AUTHOR_USERNAME"),
        new Result(property : "password", column : "AUTHOR_PASSWORD"),
        new Result(property : "email", column : "AUTHOR_EMAIL"),
        new Result(property : "bio", column : "AUTHOR_BIO")
    ])]
    #[Select([
        "SELECT ",
        "  ID as AUTHOR_ID,",
        "  USERNAME as AUTHOR_USERNAME,",
        "  PASSWORD as AUTHOR_PASSWORD,",
        "  EMAIL as AUTHOR_EMAIL,",
        "  BIO as AUTHOR_BIO",
        "FROM author WHERE ID = #{id}"
    ])]
    public function selectAuthor(int $id): Author;

    //======================================================

    #[Result(property : "id", column : "AUTHOR_ID", id : true)]
    #[Result(property : "username", column : "AUTHOR_USERNAME")]
    #[Result(property : "password", column : "AUTHOR_PASSWORD")]
    #[Result(property : "email", column : "AUTHOR_EMAIL")]
    #[Result(property : "bio", column : "AUTHOR_BIO")]
    #[Select([
        "SELECT ",
        "  ID as AUTHOR_ID,",
        "  USERNAME as AUTHOR_USERNAME,",
        "  PASSWORD as AUTHOR_PASSWORD,",
        "  EMAIL as AUTHOR_EMAIL,",
        "  BIO as AUTHOR_BIO",
        "FROM author WHERE ID = #{id}"])]
    public function selectAuthorMapToPropertiesUsingRepeatable(int $id): Author;

    //======================================================

    #[ConstructorArgs([
        new Arg(column : "AUTHOR_ID", phpType : "int"),
        new Arg(column : "AUTHOR_USERNAME", phpType : "string"),
        new Arg(column : "AUTHOR_PASSWORD", phpType : "string"),
        new Arg(column : "AUTHOR_EMAIL", phpType : "string"),
        new Arg(column : "AUTHOR_BIO", phpType : "string"),
        new Arg(column : "AUTHOR_SECTION", phpType : "string")
    ])]
    #[Select([
        "SELECT ",
        "  ID as AUTHOR_ID,",
        "  USERNAME as AUTHOR_USERNAME,",
        "  PASSWORD as AUTHOR_PASSWORD,",
        "  EMAIL as AUTHOR_EMAIL,",
        "  BIO as AUTHOR_BIO,",
            "  FAVOURITE_SECTION as AUTHOR_SECTION",
        "FROM author WHERE ID = #{id}"])]
    public function selectAuthorConstructor(int $id): Author;

    //======================================================

    #[Arg(column : "AUTHOR_ID", phpType : "int", id : true)]
    #[Arg(column : "AUTHOR_USERNAME", phpType : "string")]
    #[Arg(column : "AUTHOR_PASSWORD", phpType : "string")]
    #[Arg(column : "AUTHOR_EMAIL", phpType : "string")]
    #[Arg(column : "AUTHOR_BIO", phpType : "string")]
    #[Arg(column : "AUTHOR_SECTION", phpType : "string")]
    #[Select([
        "SELECT ",
        "  ID as AUTHOR_ID,",
        "  USERNAME as AUTHOR_USERNAME,",
        "  PASSWORD as AUTHOR_PASSWORD,",
        "  EMAIL as AUTHOR_EMAIL,",
        "  BIO as AUTHOR_BIO," ,
        "  FAVOURITE_SECTION as AUTHOR_SECTION",
        "FROM author WHERE ID = #{id}"])]
    public function selectAuthorMapToConstructorUsingRepeatable(int $id): Author;

    //======================================================

    #[Arg(column : "AUTHOR_ID", phpType : "int")]
    #[Result(property : "username", column : "AUTHOR_USERNAME")]
    #[Select([
        "SELECT ",
        "  ID as AUTHOR_ID,",
        "  USERNAME as AUTHOR_USERNAME,",
        "  PASSWORD as AUTHOR_PASSWORD,",
        "  EMAIL as AUTHOR_EMAIL,",
        "  BIO as AUTHOR_BIO",
        "FROM author WHERE ID = #{id}"])]
    public function selectAuthorUsingSingleRepeatable(int $id): Author;

    //======================================================

    #[ConstructorArgs([
        new Arg(column : "AUTHOR_ID", phpType : "int"),
        new Arg(column : "AUTHOR_USERNAME", phpType : "string"),
        new Arg(column : "AUTHOR_PASSWORD", phpType : "string"),
        new Arg(column : "AUTHOR_EMAIL", phpType : "string"),
        new Arg(column : "AUTHOR_BIO", phpType : "string")
    ])]
    #[Arg(column : "AUTHOR_SECTION", phpType : "string")]
    #[Select([
        "SELECT ",
        "  ID as AUTHOR_ID,",
        "  USERNAME as AUTHOR_USERNAME,",
        "  PASSWORD as AUTHOR_PASSWORD,",
        "  EMAIL as AUTHOR_EMAIL,",
        "  BIO as AUTHOR_BIO," ,
        "  FAVOURITE_SECTION as AUTHOR_SECTION",
        "FROM author WHERE ID = #{id}"])]
    public function selectAuthorUsingBothArgAndConstructorArgs(int $id): Author;

    //======================================================

    #[Results(
        new Result(property : "id", column : "AUTHOR_ID")
    )]
    #[Result(property : "username", column : "AUTHOR_USERNAME")]
    #[Select([
        "SELECT ",
        "  ID as AUTHOR_ID,",
        "  USERNAME as AUTHOR_USERNAME",
        "FROM author WHERE ID = #{id}"])]
    public function selectAuthorUsingBothResultAndResults(int $id): Author;

    //======================================================

    public function findThreeSpecificPosts(
        #[Param("one")]
        int $one,
        RowBounds $rowBounds,
        #[Param("two")]
        int $two,
        int $three
    ): array;

    //@Flush
    //ListType<BatchResult> flush();
}
