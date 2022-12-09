<?php

namespace Tests\Submitted\DynSql;

class Parameter
{
    private $schema;
    private $ids = [];
    private $enabled = false;

    public function getFred(): string
    {
        throw new \Exception("This method should not be called.");
    }

    public function getSchema(): string
    {
        return $this->schema;
    }

    public function setSchema(string $schema): void
    {
        $this->schema = $schema;
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function setIds(array $ids): void
    {
        $this->ids = $ids;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
