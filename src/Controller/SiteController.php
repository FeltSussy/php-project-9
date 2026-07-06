<?php

namespace App\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Interfaces\ResponseInterface;

class SiteController extends Controller
{
    public function create (ServerRequestInterface $request, ResponseInterface $response)
    {
        $messages = $this->container->get('flash')->getMessages();
        $content = $this->container->get('renderer')->fetch('main.phtml', []);
        $params = [
            'flash' => $messages,
            'content' => $content,
        ];
        return $this->container->get('renderer')->render($response, 'layout.phtml', $params);
    }

    public function store (ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->container->get('flash')->addMessage('success', 'Добавленный flash');
        return $response->withRedirect("/sites/new", 302);
    }
}
