<?php

namespace Tests\Domain\Blog;

class ComplexImmutableAuthorId
{
    public function __construct(protected int $aId = -1, protected ?string $aEmail = null, protected ?string $aUsername = null, protected ?string $aPassword = null)
    {
    }

    public function getId(): int
    {
        return $this->aId;
    }

    public function getUsername(): ?string
    {
        return $this->aUsername;
    }

    public function getPassword(): ?string
    {
        return $this->aPassword;
    }

    public function getEmail(): ?string
    {
        return $this->aEmail;
    }
}
