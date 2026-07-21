<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

$databaseUrl = parse_url($_ENV['DATABASE_URL']);

$pdo = new PDO(
        sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $databaseUrl['host'],
            $databaseUrl['port'] ?? 5432,
            ltrim($databaseUrl['path'], '/')
        ),
        $databaseUrl['user'],
        $databaseUrl['pass'],
        [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$sql = file_get_contents(__DIR__ . '/../database.sql');

if ($sql === false) {
    throw new RuntimeException('Migration failed');
}

$pdo->exec($sql);
