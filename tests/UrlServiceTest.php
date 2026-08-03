<?php

namespace App\Tests;

use App\Entity\Url;
use App\Entity\UrlCheck;
use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;
use App\Service\UrlService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class UrlServiceTest extends TestCase
{
    protected UrlRepository $urlRepository;
    protected UrlCheckRepository $urlCheckRepository;
    protected UrlService $urlService;

    public function setUp(): void
    {
        Carbon::setTestNow('2024-03-09 16:00:00');
        $this->urlRepository = $this->createMock(UrlRepository::class);
        $this->urlCheckRepository = $this->createMock(UrlCheckRepository::class);
        $this->urlService = new UrlService($this->urlRepository, $this->urlCheckRepository);
    }

    public function tearDown(): void
    {
        Carbon::setTestNow(null);
    }

    public function testAddUrl()
    {
        $this->urlRepository
            ->method('findByName')
            ->with('https://example.com')
            ->willReturnOnConsecutiveCalls(
                false,
                new Url('https://example.com')
                    ->setId(1)
                    ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00'))
            );
        $this->urlRepository
            ->expects($this->once())
            ->method('save')
            ->with('https://example.com')
            ->willReturn(true);

        $result = $this->urlService->addUrl('https://example.com/test?key=value');

        $this->assertTrue($result['isNew']);
        $this->assertInstanceOf(Url::class, $result['url']);
    }

    public function testAddUrlWithExistingName()
    {
        $url = new Url('https://example.com')
            ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00'))
            ->setId(5);

        $this->urlRepository
            ->method('findByName')
            ->with('https://example.com')
            ->willReturn($url);

        $result = $this->urlService->addUrl('https://example.com/test?key=value');

        $this->assertFalse($result['isNew']);
        $this->assertInstanceOf(Url::class, $result['url']);
    }

    public function testGetUrls()
    {
        $url = new Url('https://example.com')
            ->setId(5)
            ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00'));
        $check = new UrlCheck(
            5,
            200,
            'testH1',
            'testTitle',
            'testDescription'
        )
            ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-10 16:00:10'))
            ->setId(1);

        $this->urlRepository
            ->method('getAll')
            ->willReturn([$url]);
        $this->urlCheckRepository
            ->method('findLatestChecks')
            ->willReturn([$check]);

        $result = $this->urlService->getUrls();

        $this->assertEquals([$url], $result['urls']);
        $this->assertEquals('2024-03-10 16:00:10', $result['lastChecks'][5]['created_at']);
        $this->assertEquals(200, $result['lastChecks'][5]['status_code']);
    }
}
