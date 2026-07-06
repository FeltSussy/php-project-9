<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Controller\ {
    SiteController
};

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $response->withRedirect("/sites/new", 302);
});

$app->get('/sites/new', [SiteController::class, 'create']);

$app->post('/sites', [SiteController::class, 'store']);

$app->run();


// index() — список;

// create() — показать форму создания;

// store() — сохранить;

// show() — показать один объект;

// edit() — показать форму редактирования;

// update() — обновить;

// destroy() — удалить
