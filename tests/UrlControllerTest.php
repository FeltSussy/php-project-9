<?php

namespace App\Tests;

use App\Controller\UrlController;
use App\Entity\Url;
use App\Entity\UrlCheck;
use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;
use App\Service\UrlCheckService;
use App\Service\UrlService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
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
    protected UrlRepository $urlRepository;   
    protected UrlCheckRepository $urlCheckRepository;

    public function setUp(): void
    {
        $this->routeParser = $this->createMock(RouteParser::class);
        $this->urlService = $this->createMock(UrlService::class);
        $this->urlCheckService = $this->createMock(UrlCheckService::class);
        $this->messages = $this->createMock(Messages::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = new \Slim\Psr7\Response();
        $this->urlRepository = $this->createMock(UrlRepository::class);
        $this->urlCheckRepository = $this->createMock(UrlCheckRepository::class);

        $renderer = new PhpRenderer(__DIR__ . '/../templates');
        $renderer->setAttributes([
            'routeParser' => $this->routeParser
        ]);

        $this->urlController = new UrlController(
            $renderer,
            $this->messages,
            $this->routeParser,
            $this->urlService,
            $this->urlCheckService,
            $this->urlRepository,
            $this->urlCheckRepository
        );
    }

    public function testHome()
    {
        $this->routeParser
            ->method('urlFor')
            ->with('index')
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

    public function testList()
    {
        $this->urlRepository
            ->method('getAll')
            ->willReturn([
                new Url('https://example.com')
                ->setId(10)
                ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:10'))
            ]);
        $this->urlCheckRepository
            ->method('findLatestByUrlId')
            ->willReturn(
                new UrlCheck(
                    10,
                    200,
                    'testH1',
                    'testTitle',
                    'testDescription',
                )
                ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:10'))
                ->setId(1)
            );

        $result = $this->urlController->list($this->request, $this->response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('2024-03-09 16:00:10', $result->getBody());
    }

    public function testStore()
    {
        $this->routeParser
            ->method('urlFor')
            ->with('urls.show', ['id' => 10])
            ->willReturn('/urls/10');
        $this->request
            ->method('getParsedBody')
            ->willReturn([
                'url' => 'https://example.com/path?key=value'
            ]);
        $this->urlService
            ->expects($this->once())
            ->method('addUrl')
            ->with('https://example.com/path?key=value')
            ->willReturn(
                new Url('https://example.com')
                ->setId(10)
                ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:10'))
            );
        $this->urlRepository
            ->method('getLastInsertId')
            ->willReturn('10');

        $result = $this->urlController->store($this->request, $this->response);

        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/urls/10', $result->getHeaderLine('Location'));
    }

    public function testStoreWithError()
    {
        $this->request
            ->method('getParsedBody')
            ->willReturn([
                'url' => 'wrong-url'
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
        $this->urlRepository
            ->method('findById')
            ->with(10)
            ->willReturn(new Url('https://example.com')
                ->setId(10)
                ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00'))
            );
        $this->urlCheckRepository
            ->method('findAllByUrlId')
            ->with(10)
            ->willReturn([
                new UrlCheck(
                    10,
                    200,
                    'testH1',
                    'testTitle',
                    'testDescription',
                )
                ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:10'))
                ->setId(1)
            ]);

        $result = $this->urlController->show($this->request, $this->response, ['id' => 10]);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('https://example.com', $result->getBody());
        $this->assertStringContainsString('testH1', $result->getBody());
        $this->assertStringContainsString('2024-03-09 16:00:10', $result->getBody());
    }

    public function testShowNotFound()
    {
        $this->urlRepository
            ->method('findById')
            ->with(404)
            ->willReturn(false);

        $this->expectException(HttpNotFoundException::class);

        $result = $this->urlController->show($this->request, $this->response, ['id' => 404]);
    }

    public function testCheck()
    {
        $this->routeParser
            ->expects($this->once())
            ->method('urlFor')
            ->with('urls.show', ['id' => 10])
            ->willReturn('/urls/10');
        $this->urlCheckService
            ->expects($this->once())
            ->method('checkUrl')
            ->with(10)
            ->willReturn(true);

        $result = $this->urlController->check($this->request, $this->response, ['url_id' => 10]);

        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals('/urls/10', $result->getHeaderLine('Location'));
    }
}
