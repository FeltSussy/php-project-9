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

    public function save (Site $site): array
    {
        if ($this->findByName($site->getName())) {
            return [
                'success' => false,
                'message' => 'Страница уже существует',
            ];
        }
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $site->getName(),
            'created_at' => $site->getCreatedAt()->format('Y-m-d'),
        ]);
        return [
            'success' => true,
            'message' => 'Страница успешно добавлена',
        ];
    }

    public function findByName (string $name): ?Site
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
}
// ->format('Y-m-d')