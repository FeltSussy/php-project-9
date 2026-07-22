<?php

namespace App\Tests;

use App\Entity\Url;
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
            ->with($this->callback(function (Url $url) {
                return $url->getName() === 'https://example.com'
                    && $url->getCreatedAt()->format('Y-m-d H:i:s') === '2024-03-09 16:00:00';
            }))
            ->willReturn(true);
        $this->repository
            ->method('getLastInsertId')
            ->willReturn('10');

        $result = $this->urlService->addUrl('https://example.com/test?key=value');

        $this->assertEquals('url_added', $result['status']);
        $this->assertEquals('10', $result['urlId']);

        Carbon::setTestNow(null);
    }

    public function testAddUrlWithEmptyName()
    {
        $result = $this->urlService->addUrl('');

        $this->assertEquals('url_required', $result['status']);
        $this->assertNull($result['urlId']);
    }

    public function testAddUrlWithWrongName()
    {
        $result = $this->urlService->addUrl('wrong-url');

        $this->assertEquals('url_invalid', $result['status']);
        $this->assertNull($result['urlId']);
    }

    public function testAddUrlWithNameExceeding255Chars()
    {
        $url = 'https://example.com/'
            . 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
            . 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
            . 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
            . 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $result = $this->urlService->addUrl($url);

        $this->assertEquals('url_too_long', $result['status']);
        $this->assertNull($result['urlId']);
    }

    public function testAddUrlWithExistingUrl()
    {
        $url = new Url(
            5,
            'https://example.com',
            Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00')
        );

        $this->repository
            ->method('findByName')
            ->with('https://example.com')
            ->willReturn($url);

        $result = $this->urlService->addUrl('https://example.com/test?key=value');

        $this->assertEquals('url_already_exists', $result['status']);
        $this->assertEquals(5, $result['urlId']);
    }
}
