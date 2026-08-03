<?php

function truncate(?string $text, int $length = 200): string
{
    if ($text === null) {
        return '';
    }

    return mb_strlen($text) > $length
        ? mb_substr($text, 0, $length) . '...'
        : $text;
}

function e(string $string): string
{
    return htmlspecialchars($string);
}

function getDatabaseConfig(?string $url): array
{
    $databaseUrl = $url === null ? [] : parse_url($url);

    if ($databaseUrl === false) {
        $databaseUrl = [];
    }

    return [
        'host' => $databaseUrl['host'] ?? 'localhost',
        'port' => $databaseUrl['port'] ?? 5432,
        'name' => isset($databaseUrl['path']) ? ltrim($databaseUrl['path'], '/') : 'postgres',
        'user' => $databaseUrl['user'] ?? 'postgres',
        'password' => $databaseUrl['pass'] ?? '',
    ];
}
