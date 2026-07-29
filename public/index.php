<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Controller\ {
    UrlController
};
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use Slim\Views\PhpRenderer;
use GuzzleHttp\Client;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Http\ServerRequest;

session_start();
/**
 * Environment
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
if (!isset($_ENV['DATABASE_URL'])) {
    throw new \RuntimeException('DATABASE_URL is not defined');
}
$databaseUrl = parse_url($_ENV['DATABASE_URL']);

/**
 * DI Container
 */
$container = new Container();
$container->set(Messages::class, function () {
    return new Messages();
});
$container->set(Client::class, function () {
    return new Client();
});
$container->set(PDO::class, function () use ($databaseUrl) {
    return new PDO(
        sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $databaseUrl['host'],
            $databaseUrl['port'] ?? 5432,
            ltrim($databaseUrl['path'], '/')
        ),
        $databaseUrl['user'],
        $databaseUrl['pass'],
        [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
});

/**
 * Slim App
 */
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true)
    ->setDefaultErrorHandler(function (
        ServerRequest $request,
        Throwable $exception,
    ) use ($app, $container): ResponseInterface
    {
        $response = $app->getResponseFactory()->createResponse();

        $status = $exception instanceof HttpNotFoundException ? 404 : 500;
        $template = $status === 404
            ? 'errors/404.phtml'
            : 'errors/500.phtml';

        return $container->get(PhpRenderer::class)->render(
            $response->withStatus($status),
            $template
        );
    });
$container->set(RouteParser::class, function () use ($app) {
    return $app->getRouteCollector()->getRouteParser();
});
$container->set(PhpRenderer::class, function (Container $container) {
    $renderer = new PhpRenderer(__DIR__ . '/../templates');

    $renderer->setAttributes([
        'routeParser' => $container->get(RouteParser::class),
        'flash'  => $container->get(Messages::class)->getMessages()
    ]);

    return $renderer;
});

$app->get('/', [UrlController::class, 'home'])
    ->setName('root');

$app->get('/urls/new', [UrlController::class, 'create'])
    ->setName('index');

$app->post('/urls', [UrlController::class, 'store'])
    ->setName('urls.store');

$app->get('/urls', [UrlController::class, 'list'])
    ->setName('urls.list');

$app->get('/urls/{id:\d+}', [UrlController::class, 'show'])
    ->setName('urls.show');

$app->post('/urls/{url_id:\d+}/checks', [UrlController::class, 'check'])
    ->setName('urls.show.checks');

$app->run();
