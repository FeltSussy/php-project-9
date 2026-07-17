<?php

namespace App\Controller;

use App\Repository\UrlRepository;
use App\Repository\UrlCheckRepository;
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
    private UrlRepository $urlRepository;
    private UrlCheckRepository $urlCheckRepository;

    public function __construct(
        PhpRenderer $renderer,
        Messages $messages,
        RouteParser $routeParser,
        UrlService $urlService,
        UrlCheckService $urlCheckService,
        UrlRepository $urlRepository,
        UrlCheckRepository $urlCheckRepository,
    ) {
        $this->renderer = $renderer;
        $this->messages = $messages;
        $this->routeParser = $routeParser;
        $this->urlService = $urlService;
        $this->urlCheckService = $urlCheckService;
        $this->urlRepository = $urlRepository;
        $this->urlCheckRepository = $urlCheckRepository;
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
        $allUrls = $this->urlRepository->getAll();
        $latestChecksByUrlId = $this->urlCheckRepository->findLatestForEachUrl();
        $params = [
            'urls' => $allUrls,
            'lastChecks' => $latestChecksByUrlId,
        ];
        return $this->renderer->render($response, 'urls/index.phtml', $params);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response)
    {
        $url = $request->getParsedBody();
        $result = $this->urlService->addUrl($url['url']);
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

        if ($url = $this->urlRepository->findById($urlId)) {
            $this->setLayoutWithDefaultAttributes();
            $checks = $this->urlCheckRepository->findAllByUrlId($urlId);
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
        $this->messages->addMessage($key, $message);
        return $response->withHeader(
            'Location',
            $this->routeParser->urlFor('urls.id', ['id' => $urlId])
        )->withStatus(302);
    }
}
