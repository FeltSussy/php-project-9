<?php

namespace App\Controller;

use App\Repository\UrlRepository;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use App\Service\UrlService;

class UrlController
{
    private PhpRenderer $renderer;
    private Messages $messages;
    private RouteParser $routeParser;
    private UrlService $service;
    private UrlRepository $repo;

    public function __construct(
        PhpRenderer $renderer,
        Messages $messages,
        RouteParser $routeParser,
        UrlService $service,
        UrlRepository $repo,
    ) {
        $this->renderer = $renderer;
        $this->messages = $messages;
        $this->routeParser = $routeParser;
        $this->service = $service;
        $this->repo = $repo;
    }

    private function setLayoutWithDefaultAttributes(): void
    {
        $this->renderer->setAttributes([
            'routeParser' => $this->routeParser,
            'flash' => $this->messages->getMessages(),
        ]);
        $this->renderer->setLayout('layout.phtml');
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withHeader('Location', $this->routeParser->urlFor('urls.new'))->withStatus(302);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->setLayoutWithDefaultAttributes();
        $params = [
            'routeParser' => $this->routeParser,
        ];
        return $this->renderer->render($response, 'urls/new.phtml', $params);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->setLayoutWithDefaultAttributes();
        $allUrls = $this->repo->getAll();
        $params = [
            'urls' => $allUrls,
        ];
        return $this->renderer->render($response, 'urls/index.phtml', $params);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response)
    {
        $url = $request->getParsedBody();
        $result = $this->service->addUrl($url['url']);
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
            $this->setLayoutWithDefaultAttributes();
            $params = [
                'url' => $url,
            ];
            return $this->renderer->render($response, 'urls/show.phtml', $params);
        }
        return $response->withStatus(404);
    }

    public function checks(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $urlId = (int) $args['id'];
        
    }
}
