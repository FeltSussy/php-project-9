<?php

namespace App\Repository;

use PDO;
use App\Entity\Url;
use App\Entity\Check;
use Carbon\Carbon;

class UrlCheckRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Url $url): array
    {
        $sql = "INSERT INTO url_checks (url_id, created_at) VALUES (:urlId, :createdAt)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            'urlId' => $url->getId(),
            'createdAt' => $url->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
        return $result ? [
                'key' => 'success',
                'message' => 'Страница успешно проверена',
                'checkId' => (int) $this->pdo->lastInsertId(),
                ]
            :
                [
                'key' => 'warning',
                'message' => 'Произошла ошибка при проверке, не удалось подключиться',
                ];
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
                null,
                null,
                null,
                null,
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
                null,
                null,
                null,
                null,
                Carbon::parse($check['created_at'])
            );
        }
        return $result;
    }
}
