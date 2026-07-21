<?php

namespace App\Tests;

use App\Entity\Url;
use App\Repository\UrlRepository;
use Carbon\Carbon;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use PDO;

class UrlRepositoryTest extends TestCase
{
    protected PDO $pdo;
    protected UrlRepository $repository;

    public function setUp(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/..");
        $dotenv->load();
        $databaseUrl = parse_url($_ENV['DATABASE_URL']);
        $this->pdo = new PDO(
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
        $schema = file_get_contents(__DIR__ . '/../database.sql');
        $this->pdo->exec('DROP TABLE IF EXISTS url_checks');
        $this->pdo->exec('DROP TABLE IF EXISTS urls');
        $this->pdo->exec($schema);

        $this->repository = new UrlRepository($this->pdo);
    }

    public function testSave()
    {
        $url = Url::create(
            'https://example.com',
            Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00')
        );

        $result = $this->repository->save($url);
        $savedUrl = $this->repository->findByName('https://example.com');

        $this->assertTrue($result);
        $this->assertEquals('https://example.com', $savedUrl->getName());
    }

    public function testFindByName()
    {
        $this->pdo->exec("INSERT INTO urls (name, created_at)
            VALUES ('https://example.com', '2024-03-09 16:00:00')");

        $result = $this->repository->findByName('https://example.com');

        $this->assertEquals('https://example.com', $result->getName());
        $this->assertEquals('2024-03-09 16:00:00', $result->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testFindById()
    {
        $this->pdo->exec("INSERT INTO urls (name, created_at)
            VALUES ('https://example.com', '2024-03-09 16:00:00')");

        $result = $this->repository->findById(1);

        $this->assertEquals(1, $result->getId());
        $this->assertEquals('https://example.com', $result->getName());
    }

    public function testGetAll()
    {
        $this->pdo->exec("INSERT INTO urls (name, created_at)
            VALUES ('https://first.com', '2024-03-09 16:00:00')");
        $this->pdo->exec("INSERT INTO urls (name, created_at)
            VALUES ('https://second.com', '2024-03-10 16:00:00')");

        $result = $this->repository->getAll();

        $this->assertCount(2, $result);
        $this->assertEquals('https://second.com', $result[0]->getName());
        $this->assertEquals('https://first.com', $result[1]->getName());
    }
}
