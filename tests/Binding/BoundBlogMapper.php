<?php

namespace Tests\Binding;

use MyBatis\Annotations\{
    Arg,
    CacheNamespace,
    Cases,
    ConstructorArgs,
    Flush,
    ListType,
    Many,
    MapType,
    MapKey,
    One,
    Param,
    Result,
    Results,
    ResultType,
    Select,
    SelectProvider,
    TypeDiscriminator
};
use MyBatis\Cursor\CursorInterface;
use MyBatis\Mapping\FetchType;
use MyBatis\Session\{
    ResultHandlerInterface,
    RowBounds
};
use Tests\Domain\Blog\{
    Author,
    Blog,
    DraftPost,
    Post,
    Section
};

#[CacheNamespace(readWrite: false)]
interface BoundBlogMapper
{
    //======================================================

    public function selectBlogWithPostsUsingSubSelect(int $id): Blog;

    //======================================================

    public function selectRandom(): int;

    //======================================================

    #[Select([ "SELECT * FROM blog"])]
    #[MapKey("id")]
    #[ResultType(new MapType(Blog::class))]
    public function selectBlogsAsMapById(): array;

    #[Select(["SELECT * FROM blog ORDER BY id"])]
    #[MapKey("id")]
    public function selectRangeBlogsAsMapById(RowBounds $rowBounds): array;

    //======================================================

    #[Select([
        "SELECT *",
        "FROM blog"
    ])]
    #[ResultType(new ListType(Blog::class))]
    public function selectBlogs(): array;

    #[Select([
            "SELECT *",
            "FROM blog",
            "ORDER BY id"
    ])]
    #[ResultType(Blog::class)]
    public function collectRangeBlogs(ResultHandlerInterface $blog, RowBounds $rowBounds): void;


    #[Select([
            "SELECT *",
            "FROM blog",
            "ORDER BY id"
    ])]
    public function openRangeBlogs(RowBounds $rowBounds): CursorInterface;

    //======================================================

    public function selectBlogsFromXML(): array;

    //======================================================

    #[Select([
        "SELECT *",
        "FROM blog"
    ])]
    #[ResultType(new ListType(new MapType("array")))]
    public function selectBlogsAsMaps(): array;

    //======================================================

    #[SelectProvider(type: BoundBlogSql::class, method: "selectBlogsSql")]
    #[ResultType(new ListType(Blog::class))]
    public function selectBlogsUsingProvider(): array;

    //======================================================

    #[Select("SELECT * FROM post ORDER BY id")]
    #[TypeDiscriminator(
        column: "draft",
        phpType: "string",
        cases: [ new Cases(value: "1", type: DraftPost::class) ]
    )]
    public function selectPosts(): array;

    //======================================================

    #[Select("SELECT * FROM post ORDER BY id")]
    #[Results([
        new Result(id: true, property: "id", column: "id")
    ])]
    #[TypeDiscriminator(
        column: "draft",
        phpType: "int",
        cases: [new Cases(
            value: "1",
            type: DraftPost::class,
            results: [new Result(id: true, property: "id", column: "id")]
        )]
    )]
    public function selectPostsWithResultMap(): array;

    //======================================================

    #[Select("SELECT * FROM blog WHERE id = #{id}")]
    public function selectBlog(int $id): Blog;

    //======================================================

    #[Select("SELECT * FROM blog WHERE id = #{id}")]
    #[ConstructorArgs([
        new Arg(column: "id", phpType: "int", id: true),
        new Arg(column: "title", phpType: "string"),
        new Arg(column: "author_id", phpType: Author::class, select: "Tests\Binding\BoundAuthorMapper.selectAuthor"),
        new Arg(column: "id", phpType: "array", select: "selectPostsForBlog")
    ])]
    public function selectBlogUsingConstructor(int $id): Blog;

    public function selectBlogUsingConstructorWithResultMap(int $i): Blog;

    public function selectBlogUsingConstructorWithResultMapAndProperties(int $i): Blog;

    public function selectBlogUsingConstructorWithResultMapCollection(int $i): Blog;

    public function selectBlogByIdUsingConstructor(int $id): Blog;

    //======================================================

    #[Select("SELECT * FROM blog WHERE id = #{id}")]
    #[ResultType(new MapType("array"))]
    public function selectBlogAsMap(array $params): array;

    //======================================================

    #[Select("SELECT * FROM post WHERE subject like #{query}")]
    #[ResultType(new ListType(Post::class))]
    public function selectPostsLike(RowBounds $bounds, string $query): array;

    //======================================================

    #[Select("SELECT * FROM post WHERE subject like #{subjectQuery} and body like #{bodyQuery}")]
    #[ResultType(new ListType(Post::class))]
    public function selectPostsLikeSubjectAndBody(
        RowBounds $bounds,
        #[Param("subjectQuery")]
        string $subjectQuery,
        #[Param("bodyQuery")]
        string $bodyQuery
    ): array;

    //======================================================

    #[Select("SELECT * FROM post WHERE id = #{id}")]
    public function selectPostsById(int $id): array;

    //======================================================

    #[Select("SELECT * FROM blog WHERE id = #{id} AND title = #{nonExistentParam,phpType=VARCHAR}")]
    public function selectBlogByNonExistentParam(
        #[Param("id")]
        int $id
    ): Blog;

    #[Select("SELECT * FROM blog WHERE id = #{id} AND title = #{params.nonExistentParam,phpType=VARCHAR}")]
    public function selectBlogByNonExistentNestedParam(
        #[Param("id")]
        int $id,
        #[Param("params")]
        array $params
    ): Blog;

    #[Select("SELECT * FROM blog WHERE id = #{id}")]
    public function selectBlogByNullParam(?int $id): Blog;

    //======================================================

    #[Select("SELECT * FROM blog WHERE id = #{0} AND title = #{1}")]
    public function selectBlogByDefault30ParamNames(int $id, string $title): Blog;

    #[Select("SELECT * FROM blog WHERE id = #{param1} AND title = #{param2}")]
    public function selectBlogByDefault31ParamNames(int $id, string $title): Blog;

    //======================================================

    #[Select('SELECT * FROM blog WHERE ${column} = #{id} AND title = #{value}')]
    public function selectBlogWithAParamNamedValue(
        #[Param("column")]
        string $column,
        #[Param("id")]
        int $id,
        #[Param("value")]
        string $title
    ): Blog;

    //======================================================

    #[Select([
        "SELECT *",
        "FROM blog"
    ])]
    #[Results([
        new Result(property: "author", column: "author_id", one: new One(select: "Tests\Binding\BoundAuthorMapper.selectAuthor")),
        new Result(property: "posts", column: "id", many: new Many(select: "selectPostsById"))
    ])]
    public function selectBlogsWithAutorAndPosts(): array;

    #[Select([
        "SELECT * FROM blog"
    ])]
    #[Results([
        new Result(property: "author", column: "author_id", one: new One(select: "Tests\Binding\BoundAuthorMapper.selectAuthor", fetchType: FetchType::EAGER)),
        new Result(property: "posts", column: "id", many: new Many(select: "selectPostsById", fetchType: FetchType::EAGER))
    ])]
    public function selectBlogsWithAutorAndPostsEagerly(): array;
}
