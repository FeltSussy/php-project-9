<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use App\Service\SiteRegistrationService;

class SiteController
{
    private PhpRenderer $renderer;
    private Messages $messages;
    private RouteParser $routeParser;
    private SiteRegistrationService $service;

    public function __construct(
        PhpRenderer $renderer,
        Messages $messages,
        RouteParser $routeParser,
        SiteRegistrationService $service
    )
    {
        $this->renderer = $renderer;
        $this->messages = $messages;
        $this->routeParser = $routeParser;
        $this->service = $service;
    }

    public function home (ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withHeader('Location', $this->routeParser->urlFor('sites.new'))->withStatus(302);
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
        $site = $request->getParsedBody();
        $result = $this->service->add($site['url']);
    }
}
