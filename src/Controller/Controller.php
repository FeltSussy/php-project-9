<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;

class Controller
{
    protected ContainerInterface $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
