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

        [$type, $message] = match ($status) {
            'url_already_exists' => ['warning', self::MESSAGE_URL_ALREADY_EXISTS],
            'url_added' => ['success', self::MESSAGE_URL_ADDED],
            'url_required' => ['warning', self::MESSAGE_URL_REQUIRED],
            'url_invalid' => ['warning', self::MESSAGE_URL_INVALID],
            'url_too_long' => ['warning', self::MESSAGE_URL_TOO_LONG],
            default => throw new \InvalidArgumentException("Unknown status: $status")
        };

        $error = [
            self::MESSAGE_URL_REQUIRED,
            self::MESSAGE_URL_INVALID,
            self::MESSAGE_URL_TOO_LONG
        ];
        $success = [
            self::MESSAGE_URL_ALREADY_EXISTS,
            self::MESSAGE_URL_ADDED
        ];

        if (in_array($message, $success)) {
            $urlId = $result['urlId'];
            $this->messages->addMessage($type, $message);
            return $response->withHeader(
                'Location',
                $this->routeParser->urlFor('urls.id', ['id' => $urlId])
            )->withStatus(302);
        }

        if (in_array($message, $error)) {
            $this->renderer->setLayout('layout.phtml');
            $params = [
                'routeParser' => $this->routeParser,
                'error' => ['key' => $type, 'message' => $message]
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

    public function check(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $urlId = $args['url_id'];
        $checkResult = $this->urlCheckService->checkUrl($urlId);
        $status = $checkResult['status'];

        [$type, $message] = match ($status) {
            'connect_failed' => ['danger', self::MESSAGE_CONNECT_FAILED],
            'check_saved' => ['success', self::MESSAGE_CHECK_SAVED],
            'check_not_saved' => ['danger', self::MESSAGE_CHECK_NOT_SAVED],
            default => throw new \InvalidArgumentException("Unknown status: $status")
        };

        $url = $this->urlService->getUrlById($urlId);
        $this->setLayoutWithtAttributes([
            'routeParser' => $this->routeParser,
            'error' => ['key' => $type, 'message' => $message]
        ]);
        $checks = $this->urlCheckService->getAllChecksOfSpecificUrlId($urlId);
        $params = [
            'url' => $url,
            'checks' => $checks
        ];
        return $this->renderer->render(
            $response = $type === 'success'
                ? $response->withStatus(200)
                : $response->withStatus(422),
            'urls/show.phtml',
            $params
        );
    }
}
