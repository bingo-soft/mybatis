<?php

namespace Tests\Domain\Blog;

class ComplexImmutableAuthor
{
    public function __construct(private ?ComplexImmutableAuthorId $theComplexImmutableAuthorId = null, protected ?string $bio = null, protected ?string $section = null)
    {
    }

    public function getComplexImmutableAuthorId(): ?ComplexImmutableAuthorId
    {
        return $this->theComplexImmutableAuthorId;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getFavouriteSection(): ?string
    {
        return $this->favouriteSection;
    }

    public function __serialize(): array
    {
        return [
            'bio' => $this->bio,
            'favouriteSection' => $this->favouriteSection
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->bio = $data['bio'];
        $this->favouriteSection = $data['favouriteSection'];
    }
}
