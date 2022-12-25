<?php

namespace Tests\Submitted\AncestorRef;

class Blog
{
    private $id;
    private $title;
    private $author;
    private $coAuthor;

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

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): void
    {
        $this->author = $author;
    }

    public function getCoAuthor(): ?Author
    {
        return $this->coAuthor;
    }

    public function setCoAuthor(Author $coAuthor): void
    {
        $this->coAuthor = $coAuthor;
    }
}
