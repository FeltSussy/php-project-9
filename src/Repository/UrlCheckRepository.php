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

    public function save(UrlCheck $urlCheck): bool
    {
        $sql = "INSERT INTO url_checks (
            url_id, status_code, h1, title, description, created_at)
            VALUES (
            :urlId, :statusCode, :h1, :title, :description, :createdAt)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'urlId' => $urlCheck->getUrlId(),
                'statusCode' => $urlCheck->getStatusCode(),
                'h1' => $urlCheck->getH1(),
                'title' => $urlCheck->getTitle(),
                'description' => $urlCheck->getDescription(),
                'createdAt' => $urlCheck->getCreatedAt()->format('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function findById(int $checkId): UrlCheck|bool
    {
        $sql = "SELECT * FROM url_checks WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $checkId]);
        if ($check = $stmt->fetch()) {
            return UrlCheck::createFromDatabase(
                $check['id'],
                $check['url_id'],
                $check['status_code'],
                $check['h1'],
                $check['title'],
                $check['description'],
                Carbon::parse($check['created_at'])
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
            $result[] = UrlCheck::createFromDatabase(
                $check['id'],
                $check['url_id'],
                $check['status_code'],
                $check['h1'],
                $check['title'],
                $check['description'],
                Carbon::parse($check['created_at'])
            );
        }
        return $result;
    }

    public function findLatestForEachUrl(): array
    {
        $result = [];
        $sql = "SELECT DISTINCT ON (url_id) *
                FROM url_checks
                ORDER BY url_id, created_at DESC";
        $stmt = $this->pdo->query($sql);
        while ($check = $stmt->fetch()) {
            $result[$check['url_id']] = UrlCheck::createFromDatabase(
                $check['id'],
                $check['url_id'],
                $check['status_code'],
                $check['h1'],
                $check['title'],
                $check['description'],
                Carbon::parse($check['created_at'])
            );
        }
        return $result;
    }
}
