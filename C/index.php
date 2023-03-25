<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Exception\HttpNotFoundException;


require __DIR__ . '/vendor/autoload.php';


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

// URL HANDLERS GO BELOW

$app->get('/faildb', function (Request $request, Response $response, array $args) {
    DB::query("SELECT *** FROM wrong");
    $response->getBody()->write("This should never be displayed");
    return $response;
});




/**Register */

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
    $phoneNumber = $data['phoneNumber'];
    $email = $data['email'];
    
    $errorList = [];
    // validate firstname
    if (strlen($firstName) < 2 || strlen($firstName) > 100) {
        $errorList []= "First name must be 2-100 characters long";
        $firstName = "";
    }
    // validate lastname
    if (strlen($lastName) < 2 || strlen($lastName) > 100) {
        $errorList []= "Last name must be 2-100 characters long";
        $lastName = "";
    }
    // validate username and check if it's already registered
    if (preg_match('/^[a-z][a-z0-9_]{3,19}$/', $username) !== 1) {
        $errorList[] = "Username must be made up of 4-20 letters, digits, or underscore. The first character must be a letter";
        $username = "";
    } 
    // else { 
    //     $result = mysqli_query($link, sprintf("SELECT userId FROM users WHERE username='%s'",
    //         mysqli_real_escape_string($link, $username)));
    //     if (!$result) {
    //         die("SQL Query failed: " . mysqli_error($link));
    //     }
    //     $userRecord = mysqli_fetch_assoc($result);
    //     if ($userRecord) {
    //         $errorList[] ="This username is already registered";
    //         $username = "";
    //     }
    // }
    // validate password
    if (
        strlen($password) < 6 || strlen($password) > 100
        || (preg_match("/[A-Z]/", $password) !== 1)
        || (preg_match("/[a-z]/", $password) !== 1)
        || (preg_match("/[0-9]/", $password) !== 1)
    ) {
        $errorList[] = "Password must be 6-100 characters long and contain at least one uppercase letter, one lowercase, and one digit.";
    }
    // validate phone
    if (preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $phoneNumber) !== 1) {
        $errorList[] ="Phone number format is 000-000-0000";
    }
    // validate email
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errorList[] = "Email does not look valid";
        $email = "";
    }


    if ($errorList) { // STATE 2: errors
        $valuesList = ['firstName' => $firstName, 
                        'lastName' => $lastName, 
                        'username' => $username, 
                        'password' => $password, 
                        'phoneNumber' => $phoneNumber, 
                        'email' => $email,];
        return $this->get('view')->render($response, 'register.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: sucess
        //add new user to the DB
        DB::insert('users', ['userId' => NULL, 'username' => $username, 'firstName' => $firstName, 'lastName' => $lastName, 
        'password' => $password, 'phoneNumber' => $phoneNumber, 'email' => $email, 'role' => "parent"]);
        return $this->get('view')->render($response, 'registered.html.twig');
    }
});


/**Log In */
// STATE 1: first display of the form
$app->get('/login', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'login.html.twig');
});

// SATE 2&3: receiving a submission
$app->post('/login', function ($request, $response, $args) {
    // extract values submitted
    $data = $request->getParsedBody();
    $username = $data['username'];
    $password = $data['password'];
    
    $errorList = [];


});

// DO NOT FORGET APP->RUN() !
$app->run();
