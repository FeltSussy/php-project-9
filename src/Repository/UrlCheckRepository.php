<?php

namespace App\Repository;

use PDO;

class UrlCheckRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save
}