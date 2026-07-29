<?php

namespace App\Repository;

use PDO;
use App\Entity\UrlCheck;
use Carbon\Carbon;

class UrlCheckRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(int $urlId, string $statusCode, string $h1, string $title, string $description): bool
    {
        $urlCheck = new UrlCheck(
            $urlId,
            $statusCode,
            $h1,
            $title,
            $description
            )
        ->setCreatedAt(Carbon::now());

        $sql = "INSERT INTO url_checks (
            url_id, status_code, h1, title, description, created_at)
            VALUES (
            :urlId, :statusCode, :h1, :title, :description, :createdAt)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'urlId' => $urlCheck->getUrlId(),
            'statusCode' => (int) $urlCheck->getStatusCode(),
            'h1' => $urlCheck->getH1(),
            'title' => $urlCheck->getTitle(),
            'description' => $urlCheck->getDescription(),
            'createdAt' => $urlCheck->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
        return true;
    }

    public function findById(int $checkId): UrlCheck|bool
    {
        $sql = "SELECT * FROM url_checks WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $checkId]);
        if ($check = $stmt->fetch()) {
            return new UrlCheck(
                $check['url_id'],
                $check['status_code'],
                $check['h1'],
                $check['title'],
                $check['description']
                )
                ->setId($check['id'])
                ->setCreatedAt(Carbon::parse($check['created_at'])
            );
        }
        return false;
    }

    public function findAllByUrlId(int $urlId): array
    {
        $result = [];
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        while ($check = $stmt->fetch()) {
            $result[] = new UrlCheck(
                $check['url_id'],
                $check['status_code'],
                $check['h1'],
                $check['title'],
                $check['description']
                )
                ->setId($check['id'])
                ->setCreatedAt(Carbon::parse($check['created_at'])
            );
        }
        return $result;
    }

    public function findLatestByUrlId(int $urlId): UrlCheck|bool
    {
        $sql = "SELECT DISTINCT ON (url_id) *
                FROM url_checks
                WHERE url_id = :url_id
                ORDER BY url_id, created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        if ($check = $stmt->fetch()) {
            return new UrlCheck(
                $check['url_id'],
                $check['status_code'],
                $check['h1'],
                $check['title'],
                $check['description']
                )
                ->setId($check['id'])
                ->setCreatedAt(Carbon::parse($check['created_at'])
            );
        }
        return false;
    }

    public function getLastInsertId(): string|bool
    {
        return $this->pdo->lastInsertId();
    }
}
