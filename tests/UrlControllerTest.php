<?php

namespace App\Tests;

use App\Controller\UrlController;
use App\Entity\Url;
use App\Entity\UrlCheck;
use App\Service\UrlCheckService;
use App\Service\UrlService;
use Carbon\Carbon;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use Slim\Views\PhpRenderer;

class UrlControllerTest extends TestCase
{
    protected UrlController $urlController;
    protected UrlService $urlService;
    protected UrlCheckService $urlCheckService;
    protected Messages $messages;
    protected ServerRequestInterface $request;
    protected ResponseInterface $response;

    public function setUp(): void
    {
        $this->urlService = $this->createMock(UrlService::class);
        $this->urlCheckService = $this->createMock(UrlCheckService::class);
        $this->messages = $this->createMock(Messages::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = new \Slim\Psr7\Response();

        $app = AppFactory::create();
        $app->get('/urls/new', fn () => null)
            ->setName('index');
        $app->get('/urls/{id:\d+}', fn () => null)
            ->setName('urls.show');
        $app->post('/urls', fn () => null)
            ->setName('urls.store');
        $app->get('/urls', fn () => null)
            ->setName('urls.index');
        $app->get('/urls/{url_id:\d+}/checks', fn () => null)
            ->setName('urls.show.checks');
        $routeParser = $app->getRouteCollector()->getRouteParser();

        $renderer = new PhpRenderer(__DIR__ . '/../templates');
        $renderer->setLayout('layouts/layout.phtml');
        $renderer->setAttributes([
            'routeParser' => $routeParser
        ]);

        $this->urlController = new UrlController(
            $renderer,
            $this->messages,
            $routeParser,
            $this->urlService,
            $this->urlCheckService
        );
    }

    public function testHome()
    {
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
            ->method('getUrls')
            ->willReturn([
                'urls' => [
                    new Url('https://example.com')
                    ->setId(10)
                    ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:10'))
                ],
                'lastChecks' => [
                    10 => [
                        'created_at' => '2024-03-09 16:00:10',
                        'status_code' => 200
                    ]
                ]
            ]);

        $result = $this->urlController->index($this->request, $this->response);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('2024-03-09 16:00:10', $result->getBody());
    }

    public function testStore()
    {
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
                'url' => new Url('https://example.com')
                    ->setId(10)
                    ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:10')),
                'isNew' => false
            ]);

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
        $this->urlService
            ->method('getUrl')
            ->with(10)
            ->willReturn([
                'url' => new Url('https://example.com')
                    ->setId(10)
                    ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:00')),
                'checks' => [
                    new UrlCheck(
                        10,
                        200,
                        'testH1',
                        'testTitle',
                        'testDescription',
                    )
                    ->setCreatedAt(Carbon::createFromFormat('Y-m-d H:i:s', '2024-03-09 16:00:10'))
                    ->setId(1)
                ]
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
            ->method('getUrl')
            ->with(404)
            ->willReturn(false);

        $this->expectException(HttpNotFoundException::class);

        $result = $this->urlController->show($this->request, $this->response, ['id' => 404]);
    }

    public function testCheck()
    {
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
