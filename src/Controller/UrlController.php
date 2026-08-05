<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use App\Service\UrlService;
use App\Service\UrlCheckService;
use Slim\Exception\HttpNotFoundException;
use Valitron\Validator;

class UrlController
{
    private const string MESSAGE_URL_REQUIRED = 'URL не должен быть пустым';
    private const string MESSAGE_URL_INVALID = 'Некорректный URL';
    private const string MESSAGE_URL_TOO_LONG = 'URL превышает 255 символов';
    private const string MESSAGE_URL_ALREADY_EXISTS = 'Страница уже существует';
    private const string MESSAGE_URL_ADDED = 'Страница успешно добавлена';
    private const string MESSAGE_CHECK_SAVED = 'Страница успешно проверена';
    private const string MESSAGE_CHECK_NOT_SAVED = 'Произошла ошибка при проверке, не удалось подключиться';
    private const string ALERT_WARNING = 'warning';
    private const string ALERT_DANGER = 'danger';
    private const string ALERT_SUCCESS = 'success';


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
        UrlCheckService $urlCheckService
    ) {
        $this->renderer = $renderer;
        $this->messages = $messages;
        $this->routeParser = $routeParser;
        $this->urlService = $urlService;
        $this->urlCheckService = $urlCheckService;
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withHeader('Location', $this->routeParser->urlFor('index'))->withStatus(302);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = [
            'routeParser' => $this->routeParser
        ];
        return $this->renderer->render($response, 'index.phtml', $params);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->renderer->render($response, 'urls/index.phtml', $this->urlService->getUrls());
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response)
    {
        $body = $request->getParsedBody();

        $urlName = is_array($body) && isset($body['url'])
            ? (string) $body['url']
            : '';

        $validator = new Validator(['urlName' => $urlName]);

        $validator
            ->rule('required', 'urlName')
            ->message(self::MESSAGE_URL_REQUIRED);

        $validator
            ->rule('url', 'urlName')
            ->message(self::MESSAGE_URL_INVALID);

        $validator
            ->rule('lengthMax', 'urlName', 255)
            ->message(self::MESSAGE_URL_TOO_LONG);

        if (!$validator->validate()) {
            $params = [
                'routeParser' => $this->routeParser,
                'errors' => $validator->errors(),
                'alertType' => self::ALERT_WARNING
            ];
            return $this->renderer->render(
                $response->withStatus(422),
                'index.phtml',
                $params
            );
        }

        $addUrlResult = $this->urlService->addUrl($urlName);

        if (!$addUrlResult['isNew']) {
            $this->messages->addMessage(self::ALERT_WARNING, self::MESSAGE_URL_ALREADY_EXISTS);
        } else {
            $this->messages->addMessage(self::ALERT_SUCCESS, self::MESSAGE_URL_ADDED);
        }
            return $response->withHeader(
                'Location',
                $this->routeParser->urlFor('urls.show', ['id' => $addUrlResult['url']->getId()])
            )->withStatus(302);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $urlId = (int) $args['id'];

        if ($params = $this->urlService->getUrl($urlId)) {
            return $this->renderer->render($response, 'urls/show.phtml', $params);
        }
        throw new HttpNotFoundException($request);
    }

    public function check(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $urlId = (int) $args['url_id'];

        if (!$this->urlCheckService->checkUrl($urlId)) {
            $params = $this->urlService->getUrl($urlId);
            $params['errors'] = ['messages' => ['message' => self::MESSAGE_CHECK_NOT_SAVED]];
            $params['alertType'] = self::ALERT_DANGER;
            return $this->renderer->render($response->withStatus(500), 'urls/show.phtml', $params);
        }

        $this->messages->addMessage(self::ALERT_SUCCESS, self::MESSAGE_CHECK_SAVED);
        return $response->withHeader(
            'Location',
            $this->routeParser->urlFor('urls.show', ['id' => (string) $urlId])
        )->withStatus(302);
    }
}
