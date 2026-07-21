<?php

namespace App\Tests;

use App\Entity\UrlCheck;
use App\Repository\UrlCheckRepository;
use Carbon\Carbon;
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

        $this->repository = new UrlCheckRepository($this->pdo);
    }

    public function testSave()
    {
        $urlCheck = UrlCheck::create(
            10,
            200,
            'testH1',
            'testTitle',
            'testDescription',
            Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00')
        );

        $result = $this->repository->save($urlCheck);
        $savedCheck = $this->repository->findById(1);

        $this->assertTrue($result);
        $this->assertEquals(10, $savedCheck->getUrlId());
        $this->assertEquals(200, $savedCheck->getStatusCode());
    }

    public function testFindById()
    {
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (10, 200, 'testH1', 'testTitle', 'testDescription', '2024-03-09 16:00:00')");

        $result = $this->repository->findById(1);

        $this->assertEquals(1, $result->getId());
        $this->assertEquals(10, $result->getUrlId());
        $this->assertEquals('testH1', $result->getH1());
    }

    public function testFindAllByUrlId()
    {
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (10, 200, 'firstH1', 'firstTitle', 'firstDescription', '2024-03-09 16:00:00')");
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (10, 201, 'secondH1', 'secondTitle', 'secondDescription', '2024-03-10 16:00:10')");

        $result = $this->repository->findAllByUrlId(10);

        $this->assertCount(2, $result);
        $this->assertEquals('secondH1', $result[0]->getH1());
        $this->assertEquals('firstH1', $result[1]->getH1());
    }

    public function testFindLatestForEachUrl()
    {
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (10, 200, 'firstH1', 'firstTitle', 'firstDescription', '2024-03-09 16:00:00')");
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (10, 404, 'secondH1', 'secondTitle', 'secondDescription', '2024-03-10 16:00:10')");
        $this->pdo->exec("INSERT INTO url_checks
            (url_id, status_code, h1, title, description, created_at)
            VALUES
            (20, 500, 'otherH1', 'otherTitle', 'otherDescription', '2024-03-11 16:00:10')");

        $result = $this->repository->findLatestForEachUrl();

        $this->assertCount(2, $result);
        $this->assertEquals('secondH1', $result[10]->getH1());
        $this->assertEquals('otherH1', $result[20]->getH1());
    }
}
