<?php

namespace Tests\Submitted\AncestorRef;

class User
{
    private $id;
    private $name;
    private $friend;
    private $friends = [];

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

    public function getFriend(): ?User
    {
        return $this->friend;
    }

    public function setFriend(User $friend): void
    {
        $this->friend = $friend;
    }

    public function getFriends(): array
    {
        return $this->friends;
    }

    public function setFriends(array $friends): void
    {
        $this->friends = $friends;
    }
}
