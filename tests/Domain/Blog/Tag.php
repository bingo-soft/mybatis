<?php

namespace Tests\Domain\Blog;

class Tag
{
    private $id;
    private $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return "Tag: " . $this->id . " : " . $this->name;
    }
}
