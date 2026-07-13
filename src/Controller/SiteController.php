<?php

namespace App\Controller;

use App\Repository\SiteRepository;
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
    private SiteRepository $repo;

    public function __construct(
        PhpRenderer $renderer,
        Messages $messages,
        RouteParser $routeParser,
        SiteRegistrationService $service,
        SiteRepository $repo,
    ) {
        $this->renderer = $renderer;
        $this->messages = $messages;
        $this->routeParser = $routeParser;
        $this->service = $service;
        $this->repo = $repo;
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withHeader('Location', $this->routeParser->urlFor('sites.new'))->withStatus(302);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = [
            'flash' => $this->messages->getMessages(),
            'content' => $this->renderer->fetch('sites/new.phtml', []),
        ];
        return $this->renderer->render($response, 'layout.phtml', $params);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        $allSites = $this->repo->getAll();
        $params = [
            'content' => $this->renderer->fetch('sites/index.phtml', ['sites' => $allSites]),
        ];
        return $this->renderer->render($response, 'layout.phtml', $params);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response)
    {
        $site = $request->getParsedBody();
        $result = $this->service->add($site['url']);
        $key = $result['key'];
        $message = $result['message'];
        $siteId = $result['siteId'];
        $this->messages->addMessage($key, $message);
        if ($key === 'success') {
            return $response->withHeader(
                'Location',
                $this->routeParser->urlFor('sites.id', ['id' => $siteId])
            )->withStatus(302);
        }
        if ($key === 'warning') {
            return $response->withHeader(
                'Location',
                $this->routeParser->urlFor('sites.new')
            )->withStatus(302);
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $siteId = (int) $args['id'];
        if ($site = $this->repo->findById($siteId)) {
            $params = [
                'content' => $this->renderer->fetch('sites/show.phtml', ['site' => $site]),
            ];
            return $this->renderer->render($response, 'layout.phtml', $params);
        }
        return $response->withStatus(404);
    }
}
