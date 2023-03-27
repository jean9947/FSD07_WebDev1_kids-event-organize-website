<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/vendor/autoload.php';

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

DB::$user = 'playroom';
DB::$password = 'Qh_83o2JKeocBABm';
DB::$dbName = 'playroom';
DB::$host = 'localhost';

// Get home page
$app->get('/', function ($request, $response, $args) {
  return $this->get('view')->render($response, 'home.html.twig');
});

// Get event page
$app->get('/event', function ($request, $response, $args) {
  // // Fetch all events from the database
  // $events = DB::query('SELECT * FROM events');

  // // Render the events page using the events data
  // return $this->get('view')->render($response, 'events.html.twig', ['events' => $events]);
  
  return $this->get('view')->render($response, 'event.html.twig');
});

// Get about us page
$app->get('/aboutus', function ($request, $response, $args) {
  return $this->get('view')->render($response, 'aboutus.html.twig');
});

// Get gallery page
$app->get('/gallery', function ($request, $response, $args) {
  return $this->get('view')->render($response, 'gallery.html.twig');
});

// Get my bookings page
$app->get('/mybookings', function ($request, $response, $args) {
  return $this->get('view')->render($response, 'mybookings.html.twig');
});

$app->run();