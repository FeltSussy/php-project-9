<?php

use App\Controller\UrlController;
use App\Service\UrlCheckService;
use App\Service\UrlService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface as InterfacesResponseInterface;
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
        $this->routeParser
            ->
    }
}