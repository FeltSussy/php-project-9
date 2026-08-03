<?php

namespace App\Tests;

use App\Entity\UrlCheck;
use App\Repository\UrlCheckRepository;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use PDO;

class UrlCheckRepositoryTest extends TestCase
{
    protected PDO $pdo;
    protected UrlCheckRepository $repository;

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
        $this->pdo->exec("CREATE TEMP TABLE url_checks (
            id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
            url_id BIGINT NOT NULL REFERENCES urls(id),
            status_code INT,
            h1 VARCHAR(1000),
            title TEXT,
            description TEXT,
            created_at TIMESTAMP NOT NULL
        )");
        $this->pdo->exec("INSERT INTO urls (name, created_at) VALUES ('https://example.com', '2024-03-09 16:00:00')");
        $this->pdo->exec("INSERT INTO urls (name, created_at) VALUES ('https://example2.com', '2024-03-09 16:00:10')");

        $this->repository = new UrlCheckRepository($this->pdo);
    }

    public function testSave()
    {
        $urlCheck = new UrlCheck(
            1,
            200,
            'testH1',
            'testTitle',
            'testDescription'
        )
        ->setId(1);

        $result = $this->repository->save(
            $urlCheck->getUrlId(),
            $urlCheck->getStatusCode(),
            $urlCheck->getH1(),
            $urlCheck->getTitle(),
            $urlCheck->getDescription()
        );

        $savedCheck = $this->repository->findById(1);

        $this->assertTrue($result);
        $this->assertEquals(1, $savedCheck->getUrlId());
        $this->assertEquals(200, $savedCheck->getStatusCode());
    }

    public function testFindById()
    {
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (1, 200, 'testH1', 'testTitle', 'testDescription', '2024-03-09 16:00:00')");

        $result = $this->repository->findById(1);

        $this->assertEquals(1, $result->getId());
        $this->assertEquals(1, $result->getUrlId());
        $this->assertEquals('testH1', $result->getH1());
    }

    public function testFindAllByUrlId()
    {
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (1, 200, 'firstH1', 'firstTitle', 'firstDescription', '2024-03-09 16:00:00')");
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (1, 201, 'secondH1', 'secondTitle', 'secondDescription', '2024-03-10 16:00:10')");

        $result = $this->repository->findAllByUrlId(1);

        $this->assertCount(2, $result);
        $this->assertEquals('secondH1', $result[0]->getH1());
        $this->assertEquals('firstH1', $result[1]->getH1());
    }

    public function testFindLatestByUrlId()
    {
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (1, 200, 'firstH1', 'firstTitle', 'firstDescription', '2024-03-09 16:00:00')");
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (1, 404, 'secondH1', 'secondTitle', 'secondDescription', '2024-03-10 16:00:10')");
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (2, 500, 'otherH1', 'otherTitle', 'otherDescription', '2024-03-11 16:00:10')");

        $result = $this->repository->findLatestByUrlId(1);

        $this->assertEquals('secondH1', $result->getH1());

        $result = $this->repository->findLatestByUrlId(2);

        $this->assertEquals('otherH1', $result->getH1());
    }

    public function testFindLatestChecks()
    {
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (1, 200, 'firstH1', 'firstTitle', 'firstDescription', '2024-03-09 16:00:00')");
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (1, 404, 'secondH1', 'secondTitle', 'secondDescription', '2024-03-10 16:00:10')");
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (2, 500, 'otherH1', 'otherTitle', 'otherDescription', '2024-03-11 16:00:10')");

        $result = $this->repository->findLatestChecks();

        $this->assertCount(2, $result);
        $this->assertEquals('secondH1', $result[0]->getH1());
        $this->assertEquals('otherH1', $result[1]->getH1());
    }
}
