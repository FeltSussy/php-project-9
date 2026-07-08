<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Controller\ {
    SiteController
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
    [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
});

/**
 * Slim App
 */
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$container->set(RouteParser::class, function () use ($app) {
    return $app->getRouteCollector()->getRouteParser();
});

$app->get('/', [SiteController::class, 'home'])
    ->setName('root');

$app->get('/sites/new', [SiteController::class, 'create'])
    ->setName('sites.new');

$app->post('/sites', [SiteController::class, 'store'])
    ->setName('sites');

$app->run();


// index() — список;

// create() — показать форму создания;

// store() — сохранить;

// show() — показать один объект;

// edit() — показать форму редактирования;

// update() — обновить;

// destroy() — удалить
