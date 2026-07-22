<?php

namespace App\Tests;

use App\Entity\Url;
use App\Entity\UrlCheck;
use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;
use App\Service\UrlCheckService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class UrlCheckServiceTest extends TestCase
{
    protected UrlCheckRepository $urlCheckRepository;
    protected UrlRepository $urlRepository;
    protected Client $client;
    protected UrlCheckService $urlCheckService;

    public function setUp(): void
    {
        $this->urlCheckRepository = $this->createMock(UrlCheckRepository::class);
        $this->urlRepository = $this->createMock(UrlRepository::class);
        $this->client = $this->createMock(Client::class);
        $this->urlCheckService = new UrlCheckService(
            $this->urlCheckRepository,
            $this->urlRepository,
            $this->client
        );
    }

    public function testCheckUrl()
    {
        Carbon::setTestNow('2024-03-09 16:00:00');

        $this->urlRepository
            ->method('findById')
            ->with(10)
            ->willReturn(new Url(
                10,
                'https://example.com',
                Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 15:00:00')
            ));
        $this->client
            ->method('get')
            ->with('https://example.com', ['timeout' => 15])
            ->willReturn(new Response(
                200,
                [],
                '<html>
                    <head>
                        <title>Test title</title>
                        <meta name="description" content="Test description">
                    </head>
                    <body>
                        <h1>Test h1</h1>
                    </body>
                </html>'
            ));
        $this->urlCheckRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (UrlCheck $urlCheck) {
                return $urlCheck->getUrlId() === 10
                    && $urlCheck->getStatusCode() === 200
                    && $urlCheck->getH1() === 'Test h1'
                    && $urlCheck->getTitle() === 'Test title'
                    && $urlCheck->getDescription() === 'Test description'
                    && $urlCheck->getCreatedAt()->format('Y-m-d H:i:s') === '2024-03-09 16:00:00';
            }))
            ->willReturn(true);

        $result = $this->urlCheckService->checkUrl(10);

        $this->assertEquals('check_saved', $result['status']);

        Carbon::setTestNow(null);
    }

    public function testCheckUrlWithConnectError()
    {
        $this->urlRepository
            ->method('findById')
            ->with(10)
            ->willReturn(new Url(
                10,
                'https://example.com',
                Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 15:00:00')
            ));
        $this->client
            ->method('get')
            ->willThrowException(new ConnectException(
                'Connection failed',
                new Request('GET', 'https://example.com')
            ));

        $result = $this->urlCheckService->checkUrl(10);

        $this->assertEquals('connect_failed', $result['status']);
    }

    public function testCheckUrlWhenSaveFails()
    {
        $this->urlRepository
            ->method('findById')
            ->with(10)
            ->willReturn(new Url(
                10,
                'https://example.com',
                Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 15:00:00')
            ));
        $this->client
            ->method('get')
            ->willReturn(new Response(200, [], '<html></html>'));
        $this->urlCheckRepository
            ->method('save')
            ->willReturn(false);

        $result = $this->urlCheckService->checkUrl(10);

        $this->assertEquals('check_not_saved', $result['status']);
    }
}
