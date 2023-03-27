<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Exception\HttpNotFoundException;


require_once 'init.php';


/**Admin */
$app->get('/admin', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'admin.html.twig');
});