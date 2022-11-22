<?php

namespace Tests\Domain\Blog;

class Author
{
    public function __construct(protected int $id = -1, protected ?string $username = null, protected ?string $password = null, protected ?string $email = null, protected ?string $bio = null, protected ?string $favouriteSection = null)
    {
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'password' => $this->password,
            'email' => $this->email,
            'bio' => $this->bio,
            'favouriteSection' => $this->favouriteSection
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->email = $data['email'];
        $this->bio = $data['bio'];
        $this->favouriteSection = $data['favouriteSection'];
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setBio(string $bio): void
    {
        $this->bio = $bio;
    }

    public function setFavouriteSection(string $favouriteSection): void
    {
        $this->favouriteSection = $favouriteSection;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getFavouriteSection(): ?string
    {
        return $this->favouriteSection;
    }

    public function __toString()
    {
        return "Author : " . $this->id . " : " . $this->username . " : " . $this->email;
    }
}
