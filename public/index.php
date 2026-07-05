<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function() {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();
    $params = [
        'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'layout.phtml', $params);
});

$app->post('/test', function ($request, $response) {
    $this->get('flash')->addMessage('success', 'Добавленный flash');
    return $response->withRedirect("/", 302);
});

$app->run();
