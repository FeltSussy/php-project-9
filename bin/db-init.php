<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->safeLoad();

$databaseConfig = getDatabaseConfig($_ENV['DATABASE_URL'] ?? null);

$pdo = new PDO(
        sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $databaseConfig['host'],
            $databaseConfig['port'],
            $databaseConfig['name']
        ),
        $databaseConfig['user'],
        $databaseConfig['password'],
        [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$sql = file_get_contents(__DIR__ . '/../database.sql');

if ($sql === false) {
    throw new RuntimeException('Migration failed');
}

$pdo->exec($sql);
