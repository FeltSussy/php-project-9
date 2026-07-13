<?php

namespace App\Repository;

use PDO;
use App\Entity\Site;
use Carbon\Carbon;

class SiteRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Site $site): array
    {
        if ($this->findByName($site->getName())) {
            return [
                'key' => 'warning',
                'message' => 'Страница уже существует',
                'siteId' => null,
            ];
        }
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $site->getName(),
            'created_at' => $site->getCreatedAt()->format('Y-m-d'),
        ]);
        return [
            'key' => 'success',
            'message' => 'Страница успешно добавлена',
            'siteId' => (int) $this->pdo->lastInsertId(),
        ];
    }

    public function findByName(string $name): ?Site
    {
        $sql = "SELECT * FROM urls WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);
        if ($site = $stmt->fetch()) {
            return Site::createFromDatabase(
                $site['id'],
                $site['name'],
                Carbon::createFromFormat('Y-m-d', $site['created_at'])
            );
        }
        return null;
    }

    public function findById(int $id): ?Site
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        if ($site = $stmt->fetch()) {
            return Site::createFromDatabase(
                $site['id'],
                $site['name'],
                Carbon::createFromFormat('Y-m-d', $site['created_at'])
            );
        }
        return null;
    }

    public function getAll(): array
    {
        $result = [];
        $sql = "SELECT * FROM urls ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        while ($site = $stmt->fetch()) {
            $result[] = Site::createFromDatabase(
                $site['id'],
                $site['name'],
                Carbon::createFromFormat('Y-m-d', $site['created_at'])
            );
        }
        return $result;
    }
}
// ->format('Y-m-d')
