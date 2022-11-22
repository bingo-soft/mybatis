<?php

namespace Tests\Domain\Blog;

class Comment
{
    private $id;
    private $post;
    private $name;
    private $comment;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function setPost(Post $post): void
    {
        $this->post = $post;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function __toString()
    {
        return "Comment: " . $this->id . " : " . $this->name . " : " . $this->comment;
    }
}
