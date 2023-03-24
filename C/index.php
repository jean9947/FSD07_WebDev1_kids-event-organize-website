<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/vendor/autoload.php';


session_start();


// DATABASE SETUP
DB::$dbName = 'WebDev1_Playroom';
DB::$user = 'WebDev1_Playroom';
DB::$password = 'GCe!]g[]6fRD5yvy';
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





// STATE 1: first display of the form
$app->get('/register', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'register.html.twig');
});

// SATE 2&3: receiving a submission
$app->post('/register', function ($request, $response, $args) {
    // extract values submitted
    $data = $request->getParsedBody();
    $firstName = $data['firstName'];
    $lastName = $data['lastName'];
    $username = $data['username'];
    $password = $data['password'];
    $phone = $data['phone'];
    $email = $data['email'];
    // validate
    $errorList = [];
    if (strlen($name) < 2 || strlen($name) > 100) {
        $errorList []= "Name must be 2-100 characters long";
        $name = "";
    }
    if (filter_var($age, FILTER_VALIDATE_INT) === false || $age < 0 || $age > 150) {
        $errorList[] = "Age must be an integer number between 0 and 150";
        $age = "";
    }
    //
    if ($errorList) { // STATE 2: errors
        $valuesList = ['firstName' => $firstName, 
                        'lastName' => $lastName, 
                        'username' => $username, 
                        'password' => $password, 
                        'phone' => $phone, 
                        'email' => $email,];
        return $this->get('view')->render($response, 'register.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: sucess
        //add new user to the DB
        DB::insert('users', ['userId' => NULL, 'firstName' => $firstName, 'lastName' => $lastName, 'username' => $username, 
        'password' => $password, 'phone' => $phone, 'email' => $email]);
        return $this->get('view')->render($response, 'registered.html.twig');
    }
});

// DO NOT FORGET APP->RUN() !
$app->run();
