<?php

namespace App\Tests;

use App\Controller\UrlController;
use App\Entity\Url;
use App\Entity\UrlCheck;
use App\Service\UrlCheckService;
use App\Service\UrlService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use Slim\Views\PhpRenderer;

class UrlControllerTest extends TestCase
{
    protected UrlController $urlController;
    protected RouteParser $routeParser;
    protected UrlService $urlService;
    protected UrlCheckService $urlCheckService;
    protected Messages $messages;
    protected ServerRequestInterface $request;
    protected ResponseInterface $response;

    public function setUp(): void
    {
        $this->routeParser = $this->createMock(RouteParser::class);
        $this->urlService = $this->createMock(UrlService::class);
        $this->urlCheckService = $this->createMock(UrlCheckService::class);
        $this->messages = $this->createMock(Messages::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = new \Slim\Psr7\Response();

        $this->urlController = new UrlController(
            new PhpRenderer(__DIR__ . '/../templates'),
            $this->messages,
            $this->routeParser,
            $this->urlService,
            $this->urlCheckService,
        );
    }

    public function testHome()
    {
        $this->routeParser
            ->method('urlFor')
            ->with('urls.new')
            ->willReturn('/urls/new');

        $result = $this->urlController->home($this->request, $this->response);

        $this->assertEquals(302, $result->getStatusCode());
        $this->assertSame(['/urls/new'], $result->getHeader('Location'));
    }

    public function testCreate()
    {
        $result = $this->urlController->create($this->request, $this->response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString("id=\"add-url-form\"", $result->getBody());
    }

    public function testIndex()
    {
        $this->urlService
            ->method('getAllUrls')
            ->willReturn([
                new Url(
                    10,
                    'testName',
                    Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00')
                )
            ]);
        $this->urlCheckService
            ->method('getLatestChecksOfAllUrls')
            ->willReturn([ 10 =>
                new UrlCheck(
                    1,
                    10,
                    200,
                    'testH1',
                    'testTitle',
                    'testDescription',
                    Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:10')
                )
            ]);

        $result = $this->urlController->index($this->request, $this->response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('2024-03-09 16:00:10', $result->getBody());
    }

    public function testStore()
    {
        $this->routeParser
            ->expects($this->once())
            ->method('urlFor')
            ->with('urls.id', ['id' => 1])
            ->willReturn('/urls/1');
        $this->request
            ->method('getParsedBody')
            ->willReturn([
                'url' => 'https://example.com/path?key=value'
            ]);
        $this->urlService
            ->expects($this->once())
            ->method('addUrl')
            ->with('https://example.com/path?key=value')
            ->willReturn([
                'key' => 'success',
                'message' => 'Страница успешно добавлена',
                'urlId' => 1
            ]);

        $result = $this->urlController->store($this->request, $this->response);

        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/urls/1', $result->getHeaderLine('Location'));
    }

    public function testStoreWithError()
    {
        $this->request
            ->method('getParsedBody')
            ->willReturn([
                'url' => 'wrong-url'
            ]);
        $this->urlService
            ->expects($this->once())
            ->method('addUrl')
            ->with('wrong-url')
            ->willReturn([
                'key' => 'warning',
                'message' => 'Некорректный URL',
                'urlId' => null
            ]);

        $result = $this->urlController->store($this->request, $this->response);

        $this->assertEquals(422, $result->getStatusCode());
        $this->assertStringContainsString('Некорректный URL', $result->getBody());
        $this->assertStringContainsString('id="add-url-form"', $result->getBody());
    }

    public function testShow()
    {
        $this->routeParser
            ->method('urlFor')
            ->willReturn('/test-url');
        $this->urlService
            ->method('getUrlById')
            ->with(10)
            ->willReturn(new Url(
                10,
                'https://example.com',
                Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00')
            ));
        $this->urlCheckService
            ->method('getAllChecksOfSpecificUrlId')
            ->with(10)
            ->willReturn([
                new UrlCheck(
                    1,
                    10,
                    200,
                    'testH1',
                    'testTitle',
                    'testDescription',
                    Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:10')
                )
            ]);

        $result = $this->urlController->show($this->request, $this->response, ['id' => 10]);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('https://example.com', $result->getBody());
        $this->assertStringContainsString('testH1', $result->getBody());
        $this->assertStringContainsString('2024-03-09 16:00:10', $result->getBody());
    }

    public function testShowNotFound()
    {
        $this->urlService
            ->method('getUrlById')
            ->with(404)
            ->willReturn(false);

        $result = $this->urlController->show($this->request, $this->response, ['id' => 404]);

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testStoreCheck()
    {
        $this->routeParser
            ->expects($this->once())
            ->method('urlFor')
            ->with('urls.id', ['id' => 10])
            ->willReturn('/urls/10');
        $this->urlCheckService
            ->expects($this->once())
            ->method('checkUrl')
            ->with(10)
            ->willReturn([
                'key' => 'success',
                'message' => 'Страница успешно проверена',
            ]);

        $result = $this->urlController->storeCheck($this->request, $this->response, ['url_id' => 10]);

        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/urls/10', $result->getHeaderLine('Location'));
    }
}
