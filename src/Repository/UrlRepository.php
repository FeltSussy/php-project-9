<?php

namespace App\Repository;

use PDO;
use App\Entity\Url;
use Carbon\Carbon;

class UrlRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Url $url): bool
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $url->getName(),
            'created_at' => $url->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function findByName(string $name): ?Url
    {
        $sql = "SELECT * FROM urls WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);
        if ($url = $stmt->fetch()) {
            return Url::createFromDatabase(
                $url['id'],
                $url['name'],
                Carbon::parse($url['created_at'])
            );
        }
        return null;
    }

    public function findById(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        if ($url = $stmt->fetch()) {
            return Url::createFromDatabase(
                $url['id'],
                $url['name'],
                Carbon::parse($url['created_at'])
            );
        }
        return null;
    }

    public function getAll(): array
    {
        $result = [];
        $sql = "SELECT * FROM urls ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        while ($url = $stmt->fetch()) {
            $result[] = Url::createFromDatabase(
                $url['id'],
                $url['name'],
                Carbon::parse($url['created_at'])
            );
        }
        return $result;
    }

    public function getLastInsertId(): string|bool
    {
        return $this->pdo->lastInsertId();
    }
}
