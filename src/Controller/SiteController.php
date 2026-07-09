<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use PDO;

class SiteController
{
    private PhpRenderer $renderer;
    private Messages $messages;
    private RouteParser $routeParser;
    private PDO $pdo;

    public function __construct(
        PhpRenderer $renderer,
        Messages $messages,
        RouteParser $routeParser,
        PDO $pdo
    )
    {
        $this->renderer = $renderer;
        $this->messages = $messages;
        $this->routeParser = $routeParser;
        $this->pdo = $pdo;
    }

    public function home (ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withRedirect($this->routeParser->urlFor('sites.new'), 302);
    }

    public function create (ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = [
            'flash' => $this->messages->getMessages(),
            'content' => $this->renderer->fetch('sites/new.phtml', []),
        ];
        return $this->renderer->render($response, 'layout.phtml', $params);
    }

    public function index (ServerRequestInterface $request, ResponseInterface $response)
    {
        
        $params = [
            'sites' => 'test',
            'content' => $this->renderer->fetch('sites/index.phtml', []),
        ];
        return $this->renderer->render($response, 'layout.phtml', $params);
    }

    public function store (ServerRequestInterface $request, ResponseInterface $response)
    {
        
    }
}
