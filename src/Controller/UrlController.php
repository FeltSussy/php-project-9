<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use App\Service\UrlService;
use App\Service\UrlCheckService;

class UrlController
{
    private PhpRenderer $renderer;
    private Messages $messages;
    private RouteParser $routeParser;
    private UrlService $urlService;
    private UrlCheckService $urlCheckService;

    public function __construct(
        PhpRenderer $renderer,
        Messages $messages,
        RouteParser $routeParser,
        UrlService $urlService,
        UrlCheckService $urlCheckService,
    ) {
        $this->renderer = $renderer;
        $this->messages = $messages;
        $this->routeParser = $routeParser;
        $this->urlService = $urlService;
        $this->urlCheckService = $urlCheckService;
    }

    private function setLayoutWithtAttributes(array $attributes): void
    {
        $this->renderer->setAttributes($attributes);
        $this->renderer->setLayout('layout.phtml');
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withHeader('Location', $this->routeParser->urlFor('urls.new'))->withStatus(302);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->setLayoutWithtAttributes([
            'routeParser' => $this->routeParser,
            'flash' => $this->messages->getMessages(),
        ]);
        $params = [
            'routeParser' => $this->routeParser
        ];
        return $this->renderer->render($response, 'urls/new.phtml', $params);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->setLayoutWithtAttributes([
            'routeParser' => $this->routeParser,
            'flash' => $this->messages->getMessages(),
        ]);
        $allUrls = $this->urlService->getAllUrls();
        $latestChecksByUrlId = $this->urlCheckService->getLatestChecksOfAllUrls();
        $params = [
            'urls' => $allUrls,
            'lastChecks' => $latestChecksByUrlId
        ];
        return $this->renderer->render($response, 'urls/index.phtml', $params);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response)
    {
        $body = $request->getParsedBody();

        $urlName = is_array($body) && isset($body['url'])
            ? (string) $body['url']
            : '';

        $result = $this->urlService->addUrl($urlName);

        $key = $result['key'];
        $message = $result['message'];
        $urlId = $result['urlId'];

        if ($key === 'success') {
            $this->messages->addMessage($key, $message);
            return $response->withHeader(
                'Location',
                $this->routeParser->urlFor('urls.id', ['id' => $urlId])
            )->withStatus(302);
        }

        if ($key === 'warning' || $key === 'danger') {
            $this->renderer->setLayout('layout.phtml');
            $params = [
                'routeParser' => $this->routeParser,
                'error' => $message
            ];
            return $this->renderer->render(
                $response->withStatus(422),
                'urls/new.phtml',
                $params
            );
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $urlId = (int) $args['id'];

        if ($url = $this->urlService->getUrlById($urlId)) {
            $this->setLayoutWithtAttributes([
                'routeParser' => $this->routeParser,
                'flash' => $this->messages->getMessages(),
            ]);
            $checks = $this->urlCheckService->getAllChecksOfSpecificUrlId($urlId);
            $params = [
                'url' => $url,
                'checks' => $checks
            ];
            return $this->renderer->render($response, 'urls/show.phtml', $params);
        }
        return $response->withStatus(404);
    }

    public function storeCheck(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $urlId = $args['url_id'];
        $checkResult = $this->urlCheckService->checkUrl($urlId);
        $key = $checkResult['key'];
        $message = $checkResult['message'];

        if ($key === 'success') {
            $this->messages->addMessage($key, $message);
            return $response->withHeader(
                'Location',
                $this->routeParser->urlFor('urls.id', ['id' => $urlId])
            )->withStatus(302);
        }

        if ($key === 'warning' || $key === 'danger') {
            $url = $this->urlService->getUrlById($urlId);
            $this->setLayoutWithtAttributes([
                'routeParser' => $this->routeParser,
                'error' => $message
            ]);
            $checks = $this->urlCheckService->getAllChecksOfSpecificUrlId($urlId);
            $params = [
                'url' => $url,
                'checks' => $checks
            ];
            return $this->renderer->render(
                $response->withStatus(422),
                'urls/show.phtml',
                $params
            );
        }
    }
}
