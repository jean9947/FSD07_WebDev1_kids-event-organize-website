<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/vendor/autoload.php';

DB::$user = 'playroom';
DB::$password = 'LCjs-BDv_mW8j(0*';
DB::$dbName = 'playroom';
DB::$host = 'localhost';
DB::$port = '3306';

// DB::$user = 'playroom';
// DB::$password = 'Qh_83o2JKeocBABm';

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

// Get home page
$app->get('/', function ($request, $response, $args) {
  return $this->get('view')->render($response, 'home.html.twig');
});

// Get event page
$app->get('/event', function ($request, $response, $args) {
  // Fetch all events from the database
  $events = DB::query('SELECT * FROM events');
  foreach ($events as &$event) {
    $startTime = new DateTime($event['startTime']);
    $event['startTime'] = $startTime->format('g:i A');
  }
  foreach ($events as &$event) {
    $endTime = new DateTime($event['endTime']);
    $event['endTime'] = $endTime->format('g:i A');
  }
  foreach ($events as &$event) {
    $date = new DateTime($event['date']);
    $event['date'] = $date->format('M d');
  }
  // Render the events page using the events data
  return $this->get('view')->render($response, 'event.html.twig', ['events' => $events]);
});

// Get event information pages
$app->get('/event/{id}', function ($request, $response, $args) {
  $eventId = $args['id']; 
  // Fetch the event details from the database using the event ID 
  $event = DB::queryFirstRow('SELECT * FROM events WHERE eventId=%i',
  $eventId); 
  $startTime = new DateTime($event['startTime']);
  $event['startTime'] = $startTime->format('g:i A');
  $endTime = new DateTime($event['endTime']);
  $event['endTime'] = $endTime->format('g:i A');
  $date = new DateTime($event['date']);
  $event['date'] = $date->format('y M d');
  // Render the event detail page using the event data return
  return $this->get('view')->render($response, 'eventinformation.html.twig', ['event' =>
  $event]); 
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