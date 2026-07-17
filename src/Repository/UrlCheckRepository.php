<?php

namespace App\Repository;

use PDO;
use App\Entity\Check;
use Carbon\Carbon;

class UrlCheckRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Check $check): bool
    {
        $sql = "INSERT INTO url_checks (
            url_id, status_code, h1, title, description, created_at)
            VALUES (
            :urlId, :statusCode, :h1, :title, :description, :createdAt)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'urlId' => $check->getUrlId(),
                'statusCode' => $check->getStatusCode(),
                'h1' => $check->getH1(),
                'title' => $check->getTitle(),
                'description' => $check->getDescription(),
                'createdAt' => $check->getCreatedAt()->format('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function findById(int $checkId)
    {
        $sql = "SELECT * FROM url_checks WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $checkId]);
        if ($check = $stmt->fetch()) {
            return Check::createFromDatabase(
                $check['id'],
                $check['url_id'],
                $check['status_code'],
                $check['h1'],
                $check['title'],
                $check['description'],
                Carbon::parse($check['created_at'])
            );
        }
        return null;
    }

    public function getAllForUrl(int $urlId)
    {
        $result = [];
        $sql = "SELECT * FROM url_checks WHERE url_id = {$urlId} ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        while ($check = $stmt->fetch()) {
            $result[] = Check::createFromDatabase(
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

    public function getAllLastChecks(): array
    {
        $result = [];
        $sql = "SELECT DISTINCT ON (url_id) *
                FROM url_checks
                ORDER BY url_id, created_at DESC";
        $stmt = $this->pdo->query($sql);
        while ($check = $stmt->fetch()) {
            $result[$check['url_id']] = Check::createFromDatabase(
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
