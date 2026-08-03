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
        $dotenv->safeLoad();
        $databaseConfig = getDatabaseConfig($_ENV['DATABASE_URL'] ?? null);
        $this->pdo = new PDO(
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
        $this->pdo->exec("CREATE TEMP TABLE urls (
            id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
            name VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP NOT NULL
        )");

        $this->repository = new UrlRepository($this->pdo);
    }

    public function testSave()
    {
        $url = new Url('https://example.com')
            ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00'));

        $result = $this->repository->save($url->getName());
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
