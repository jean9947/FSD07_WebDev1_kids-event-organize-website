<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Exception\HttpNotFoundException;
use \Slim\Routing\RouteContext;
use Slim\Middleware\FlashMiddleware;
use Slim\Flash\Messages;


require_once __DIR__ . '/vendor/autoload.php';

session_start();

// TODO: Add logger here
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler('applogs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('applogs/errors.log', Logger::ERROR));

$log->pushProcessor(function ($record) {
    // $record['extra']['user'] = isset($_SESSION['user']) ? $_SESSION['user']['username'] : '=anonymous=';
    $record['extra']['ip'] = $_SERVER['REMOTE_ADDR'];
    return $record;
});

// DATABASE SETUP
DB::$dbName = 'playroom';
DB::$user = 'playroom';
DB::$password = 'LCjs-BDv_mW8j(0*';
DB::$host = 'localhost';


// Create Container
$container = new Container();
AppFactory::setContainer($container);

// Set view in Container
$container->set('view', function() {
    return Twig::create(__DIR__ . '/templates', ['cache' => __DIR__ . '/tmplcache', 'debug' => true]);
});

// Create App
$app = AppFactory::create();

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));

//see the errors in normal way
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// URL HANDLERS GO BELOW
$app->get('/faildb', function (Request $request, Response $response, array $args) {
    try {
        DB::query("SELECT *** FROM wrong");
    } catch (\Exception $e) {
        $response->getBody()->write("Database error: " . $e->getMessage());
        return $response->withStatus(500);
    }
    $response->getBody()->write("This should never be displayed");
    return $response;
});


// flash message middleware
$app->add(function ($request, $handler) use ($container) {
    $container->get('view')->getEnvironment()->addGlobal('flashMessage', getAndClearFlashMessage());
    return $handler->handle($request);
});

// function to set a flash message
function setFlashMessage($message) {
    $_SESSION['flashMessage'] = $message;
}

// function to get and clear a flash message
function getAndClearFlashMessage() {
    if (isset($_SESSION['flashMessage'])) {
        $message = $_SESSION['flashMessage'];
        unset($_SESSION['flashMessage']);
        return $message;
    }
    return "";
}