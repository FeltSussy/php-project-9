<?php

namespace App\Tests;

use App\Entity\Url;
use App\Entity\UrlCheck;
use App\Repository\UrlRepository;
use App\Service\UrlService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class UrlServiceTest extends TestCase
{
    protected UrlRepository $repository;
    protected UrlService $urlService;

    public function setUp(): void
    {
        $this->repository = $this->createMock(UrlRepository::class);
        $this->urlService = new UrlService($this->repository);
    }

    public function testAddUrl()
    {
        Carbon::setTestNow('2024-03-09 16:00:00');

        $this->repository
            ->method('findByName')
            ->with('https://example.com')
            ->willReturn(false);
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with('https://example.com')
            ->willReturn(true);

        $result = $this->urlService->addUrl('https://example.com/test?key=value');

        $this->assertTrue($result);

        Carbon::setTestNow(null);
    }

    public function testAddUrlWithExistingName()
    {
        $url = new Url('https://example.com')
            ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00'))
            ->setId(5);

        $this->repository
            ->method('findByName')
            ->with('https://example.com')
            ->willReturn($url);

        $result = $this->urlService->addUrl('https://example.com/test?key=value');

        $this->assertInstanceOf(Url::class, $result);
    }
}
