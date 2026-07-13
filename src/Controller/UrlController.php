<?php

namespace App\Controller;

use App\Repository\UrlRepository;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use App\Service\UrlRegistrationService;

class UrlController
{
    private PhpRenderer $renderer;
    private Messages $messages;
    private RouteParser $routeParser;
    private UrlRegistrationService $service;
    private UrlRepository $repo;

    public function __construct(
        PhpRenderer $renderer,
        Messages $messages,
        RouteParser $routeParser,
        UrlRegistrationService $service,
        UrlRepository $repo,
    ) {
        $this->renderer = $renderer;
        $this->messages = $messages;
        $this->routeParser = $routeParser;
        $this->service = $service;
        $this->repo = $repo;
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withHeader('Location', $this->routeParser->urlFor('urls.new'))->withStatus(302);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = [
            'routeParser' => $this->routeParser,
            'flash' => $this->messages->getMessages(),
            'content' => $this->renderer->fetch('urls/new.phtml', ['routeParser' => $this->routeParser]),
        ];
        return $this->renderer->render($response, 'layout.phtml', $params);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        $allUrls = $this->repo->getAll();
        $params = [
            'routeParser' => $this->routeParser,
            'content' => $this->renderer->fetch('urls/index.phtml', ['urls' => $allUrls, 'routeParser' => $this->renderer]),
        ];
        return $this->renderer->render($response, 'layout.phtml', $params);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response)
    {
        $url = $request->getParsedBody();
        $result = $this->service->add($url['url']);
        $key = $result['key'];
        $message = $result['message'];
        $urlId = $result['urlId'];
        $this->messages->addMessage($key, $message);
        if ($key === 'success') {
            return $response->withHeader(
                'Location',
                $this->routeParser->urlFor('urls.id', ['id' => $urlId])
            )->withStatus(302);
        }
        if ($key === 'warning') {
            return $response->withHeader(
                'Location',
                $this->routeParser->urlFor('urls.new')
            )->withStatus(302);
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $urlId = (int) $args['id'];
        if ($url = $this->repo->findById($urlId)) {
            $params = [
                'flash' => $this->messages->getMessages(),
                'routeParser' => $this->routeParser,
                'content' => $this->renderer->fetch('urls/show.phtml', ['url' => $url, 'routeParser' => $this->routeParser]),
            ];
            return $this->renderer->render($response, 'layout.phtml', $params);
        }
        return $response->withStatus(404);
    }
}
