<?php

namespace App\Entity;

use DateTimeInterface;

class Url
{
    private ?int $id;
    private string $name;
    private DateTimeInterface $createdAt;

    private function __construct(?int $id, string $name, DateTimeInterface $createdAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
    }

    public static function create(string $name, DateTimeInterface $createdAt)
    {
        return new Url(null, $name, $createdAt);
    }

    public static function createFromDatabase(int $id, string $name, DateTimeInterface $createdAt)
    {
        return new Url($id, $name, $createdAt);
    }

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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
