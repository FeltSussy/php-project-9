<?php

namespace App\Entity;

use DateTimeInterface;

class UrlCheck
{
    private ?int $id;
    private int $urlId;
    private ?int $statusCode;
    private ?string $h1;
    private ?string $title;
    private ?string $description;
    private DateTimeInterface $createdAt;

    private function __construct(
        ?int $id,
        int $urlId,
        ?int $statusCode,
        ?string $h1,
        ?string $title,
        ?string $description,
        DateTimeInterface $createdAt
    ) {
        $this->id = $id;
        $this->urlId = $urlId;
        $this->statusCode = $statusCode;
        $this->h1 = $h1;
        $this->title = $title;
        $this->description = $description;
        $this->createdAt = $createdAt;
    }

    public static function create(
        int $urlId,
        ?int $statusCode,
        ?string $h1,
        ?string $title,
        ?string $description,
        DateTimeInterface $createdAt
    ): self {
        return new self(null, $urlId, $statusCode, $h1, $title, $description, $createdAt);
    }

    public static function createFromDatabase(
        int $id,
        int $urlId,
        ?int $statusCode,
        ?string $h1,
        ?string $title,
        ?string $description,
        DateTimeInterface $createdAt
    ): self {
        return new self($id, $urlId, $statusCode, $h1, $title, $description, $createdAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUrlId(): int
    {
        return $this->urlId;
    }

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function setH1(?string $h1): void
    {
        $this->h1 = $h1;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
