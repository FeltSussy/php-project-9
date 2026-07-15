<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Controller\ {
    UrlController
};
use Slim\Flash\Messages;
use Slim\Routing\RouteParser;
use Slim\Views\PhpRenderer;

session_start();
/**
 * Environment
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
if (!isset($_ENV['DATABASE_URL'])) {
    throw new \Exception('DATABASE_URL is not defined');
}
$databaseUrl = parse_url($_ENV['DATABASE_URL']);

/**
 * DI Container
 */
$container = new Container();
$container->set(PhpRenderer::class, function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set(Messages::class, function () {
    return new Messages();
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
$app->addErrorMiddleware(true, true, true);
$container->set(RouteParser::class, function () use ($app) {
    return $app->getRouteCollector()->getRouteParser();
});

$app->get('/', [UrlController::class, 'home'])
    ->setName('root');

$app->get('/urls/new', [UrlController::class, 'create'])
    ->setName('urls.new');

$app->post('/urls', [UrlController::class, 'store'])
    ->setName('urls');

$app->get('/urls', [UrlController::class, 'index'])
    ->setName('urls.index');

$app->get('/urls/{id}', [UrlController::class, 'show'])
    ->setName('urls.id');

$app->post('/urls/{url_id}/checks', [UrlController::class, 'checks'])
    ->setName('urls.id.checks');

$app->run();


// index() — список;

// create() — показать форму создания;

// store() — сохранить;

// show() — показать один объект;

// edit() — показать форму редактирования;

// update() — обновить;

// destroy() — удалить
