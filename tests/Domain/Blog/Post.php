<?php

namespace Tests\Domain\Blog;

class Post
{
    protected $id;
    protected $author;
    protected $blog;
    protected $createdOn;
    protected $section;
    protected $subject;
    protected $body;
    protected $comments = [];
    protected $tags = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getBlog(): Blog
    {
        return $this->blog;
    }

    public function setBlog(Blog $blog): void
    {
        $this->blog = $blog;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): void
    {
        $this->author = $author;
    }

    public function getCreatedOn(): string
    {
        return $this->createdOn;
    }

    public function setCreatedOn(string $createdOn): void
    {
        $this->createdOn = $createdOn;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function setSection(string $section): void
    {
        $this->section = $section;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getComments(): array
    {
        return $this->comments;
    }

    public function setComments(array $comments): void
    {
        $this->comments = $comments;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function __toString()
    {
        return "Post: " . $this->id . " : " . $this->subject . " : " . $this->body . " : " . $this->section . " : " . $this->createdOn . " (" . $this->author . ") (" . $this->blog . ")";
    }
}
