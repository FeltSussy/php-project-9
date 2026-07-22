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
    private const MESSAGE_URL_REQUIRED = 'URL не должен быть пустым';
    private const MESSAGE_URL_INVALID = 'Некорректный URL';
    private const MESSAGE_URL_TOO_LONG = 'URL превышает 255 символов';
    private const MESSAGE_URL_ALREADY_EXISTS = 'Страница уже существует';
    private const MESSAGE_URL_ADDED = 'Страница успешно добавлена';
    private const MESSAGE_CONNECT_FAILED = 'Произошла ошибка при проверке, не удалось подключиться';
    private const MESSAGE_CHECK_SAVED = 'Страница успешно проверена';
    private const MESSAGE_CHECK_NOT_SAVED = 'Произошла ошибка, проверка не была сохранена';

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
        $status = $result['status'];

        if ($status === 'url_already_exists' || $status === 'url_added') {
            $urlId = $result['urlId'];
            if ($status === 'url_already_exists') {
                $this->messages->addMessage('warning', self::MESSAGE_URL_ALREADY_EXISTS);
            }
            if ($status === 'url_added') {
                $this->messages->addMessage('success', self::MESSAGE_URL_ADDED);
            }
            return $response->withHeader(
                'Location',
                $this->routeParser->urlFor('urls.id', ['id' => $urlId])
            )->withStatus(302);
        }

        if ($status === 'url_required' || $status === 'url_invalid' || $status === 'url_too_long') {
            $this->renderer->setLayout('layout.phtml');
            $params = [
                'routeParser' => $this->routeParser,
                'error' => 
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
