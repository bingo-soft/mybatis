<?php

namespace Tests\Domain\Blog;

class Blog
{
    public function __construct(private int $id = -1, private ?string $title = null, private ?Author $author = null, private ?array $posts = [])
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): void
    {
        $this->author = $author;
    }

    public function getPosts(): array
    {
        return $this->posts;
    }

    public function setPosts(array $posts): void
    {
        $this->posts = $posts;
    }

    public function __toString()
    {
        return "Blog: " . $this->id . " : " . $this->title . " (" . $this->author . ")";
    }
}
