<?php

use App\Controller\UrlController;
use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;
use App\Service\UrlCheckService;
use App\Service\UrlService;
use PHPUnit\Framework\TestCase;
use Slim\Flash\Messages;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RouteParser;
use Slim\Views\PhpRenderer;

class UrlControllerTest extends TestCase
{
    protected UrlController $urlController;
    protected RouteParser $routeParser;

    public function setUp(): void
    {
        $this->routeParser = $this->createMock(RouteParser::class);
        $this->urlController = new UrlController(
            new PhpRenderer(__DIR__ . '/../templates'),
            new Messages(),
            $this->routeParser,
            new UrlService(new UrlRepository(new PDO())),
            new UrlCheckService(),
            new UrlCheckRepository(),
            new UrlRepository
        )
    }
    public function testSetLayoutWithtAttributes()
    {
        
    }

}