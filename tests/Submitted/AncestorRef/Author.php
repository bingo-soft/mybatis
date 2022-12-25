<?php

namespace Tests\Submitted\AncestorRef;

class Author
{
    private $id;
    private $name;
    private $blog;
    private $reputation;
    private int $permissions;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    public function setBlog(Blog $blog): void
    {
        $this->blog = $blog;
    }

    public function getReputation(): ?Reputation
    {
        return $this->reputation;
    }

    public function setReputation(Reputation $reputation): void
    {
        $this->reputation = $reputation;
    }

    public function getPermissions(): array
    {
        $result = [];
        $result[] = new Permission("one");
        $result[] = new Permission("two");
        return $result;
    }
}
