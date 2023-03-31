<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Exception\HttpNotFoundException;
use Slim\Middleware\FlashMiddleware;
use Slim\Flash\Messages;


require_once 'init.php';

/** Homepage */
// Get home page
$app->get('/', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  return $this->get('view')->render($response, 'home.html.twig',['session' => ['user' => $userData]]);
});


/**Register */
// STATE 1: first display of the form
$app->get('/register', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  return $this->get('view')->render($response, 'register.html.twig',['session' => ['user' => $userData]]);
});

// *Check if username is taken using AJAX*
$app->post('/checkUsername', function ($request, $response, $args) {
  $username = $request->getParam('username');
  $result = DB::queryFirstRow('SELECT * FROM users WHERE username = %s', $username);

  if ($result) {
      $response->getBody()->write(json_encode(array('taken' => true)));
  } else {
      $response->getBody()->write(json_encode(array('taken' => false)));
  }
  return $response->withHeader('Content-Type', 'application/json');
});

// SATE 2&3: receiving a submission
$app->post('/register', function ($request, $response, $args) {
  // $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
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
  // validate username and check if it's taken
  if (preg_match('/^[a-z][a-z0-9_]{3,19}$/', $username) !== 1) {
      $errorList[] = "Username must be made up of 4-20 letters, digits, or underscore. The first character must be a letter";
      $username = "";
  } else {
      $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $username);
      if ($userRecord) {
          $errorList[] = "This username is already registered";
          $username = "";
        }
  }
  // validate password
  if (
      strlen($password) < 6 || strlen($password) > 100
      || (preg_match("/[A-Z]/", $password) !== 1)
      || (preg_match("/[a-z]/", $password) !== 1)
      || (preg_match("/[0-9]/", $password) !== 1)
  ) {
      $errorList[] = "Password must be 6-100 characters long and contain at least one uppercase letter, one lowercase, and one digit.";
      $password ="";
  }
  // validate phone
  if (preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $phoneNumber) !== 1) {
      $errorList[] ="Phone number format is 000-000-0000";
      $phoneNumber = "";
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
  } else { // STATE 3: sucess - add new user to the DB
      DB::insert('users', ['userId' => NULL, 'username' => $username, 'firstName' => $firstName, 'lastName' => $lastName, 
      'password' => $password, 'phoneNumber' => $phoneNumber, 'email' => $email, 'role' => "parent"]);
      return $response->withHeader('Location', '/login')->withStatus(302);
  }
});


/**Log In */
// STATE 1: first display of the form
$app->get('/login', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  return $this->get('view')->render($response, 'login.html.twig',['session' => ['user' => $userData]]);
});
   
// SATE 2&3: receiving a submission
$app->post('/login', function (Request $request, Response $response, $args) {
  $data = $request->getParsedBody();
  $username = $data['username'];
  $password = $data['password'];

  $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $username);
  $loginSuccessful = ($userRecord != null) && ($userRecord['password'] == $password);

  if ($loginSuccessful && $userRecord['role'] == "admin") { // logged in as Admin
      unset($userRecord['password']);
      $_SESSION['user'] = $userRecord;
      return $response->withHeader('Location', '/admin')->withStatus(302);
  } elseif ($loginSuccessful) { // logged in as a customer
      unset($userRecord['password']);
      $_SESSION['user'] = $userRecord;
      return $response->withHeader('Location', '/')->withStatus(302);
  } else {
      $response->getBody()->write("Invalid username or password");
      return $response;
  }
});


/**Log Out */
$app->get('/logout', function ($request, $response, $args) {
  unset($_SESSION['user']);
  session_destroy();
  setFlashMessage("You've been logged out.");
  return $response->withHeader('Location', '/')->withStatus(302);
})->setName('logout');


/**Reset Password */
$app->get('/resetpassword', function ($request, $response, $args) {
  // validate if the user is logged in already
  if (!isset($_SESSION['user'])) {
    return $this->get('view')->render($response, 'resetPassword.html.twig');
  } else {
    setFlashMessage("You're already logged in");
    return $response->withHeader('Location', '/')->withStatus(302);
  }
});

$app->post('/resetpassword', function ($request, $response, $args) {
  $data = $request->getParsedBody();
  $username = $data['username'];
  $password1 = $data['password1'];
  $password2 = $data['password2'];
  $errorList = [];

  // validate if username is in the db 
  $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $username);
  if (!$userRecord['username'] == $username) {
    $errorList[] = "Username not found";
    $username = "";
  }
  // validate password
  if (
      strlen($password1) < 6 || strlen($password1) > 100
      || (preg_match("/[A-Z]/", $password1) !== 1)
      || (preg_match("/[a-z]/", $password1) !== 1)
      || (preg_match("/[0-9]/", $password1) !== 1)
  ) {
      $errorList[] = "Password must be 6-100 characters long and contain at least one uppercase letter, one lowercase, and one digit.";
      $password1 = "";
      $password2 = "";
  }
  if (!($password1 == $password2)) {
      $errorList[] = "Passwords don't match";
      $password1 = "";
      $password2 = "";
  }

  if ($errorList) { // STATE 2: errors
    $valuesList = ['username' => $username, 'password1' => $password1, 'password2' => $password2];
    return $this->get('view')->render($response, 'resetPassword.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
  } else { // STATE 3: sucess - reset password and update data to the DB
      DB::update('users', ['password' => $password2], "username=%s", $username);
      setFlashMessage("Password reset successfully.");
      return $response->withHeader('Location', '/login')->withStatus(302);
  }
});


/**************************************************************************************** */
// Get event page
$app->get('/event', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
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
  return $this->get('view')->render($response, 'event.html.twig', ['events' => $events,'session' => ['user' => $userData]]);
});

// Get event information pages
$app->get('/event/{id}', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
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
  $event,'session' => ['user' => $userData]]); 
});

// Get about us page
$app->get('/aboutus', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  return $this->get('view')->render($response, 'aboutus.html.twig',['session' => ['user' => $userData]]);
});

// Get gallery page
$app->get('/gallery', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  return $this->get('view')->render($response, 'gallery.html.twig',['session' => ['user' => $userData]]);
});

// Get my bookings page
$app->get('/mybookings', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  return $this->get('view')->render($response, 'mybookings.html.twig',['session' => ['user' => $userData]]);
});

// Use AJAX to display event detail page and form on the same page
$app->get('/booking-form', function( $request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  $events = DB::query("SELECT * FROM events");
  $html = $this->get('view')->fetch('booking_form.html.twig', [
    'events' => $events,'session' => ['user' => $userData]]);
  $response->getBody()->write($html);
  return $response->withHeader('Content-Type', 'text/html');
});

// Post data from the form
$app->post('/booking-form', function ($request, $response, $args) {
  $data = $request->getParsedBody();
  $KfirstName = $data['firstName'];
  $KlastName =  $data['lastName'];
  $birthday = $data['birthday'];
  $eventId = $data['eventId'];
  $gender = $data['gender'];
  
  $errorList = [];

  
  $userId = $_SESSION['user']['userId'];

  if (strlen($KfirstName) < 2 || strlen($KfirstName) > 100) {
    $errorList []= "First name must be 2-100 characters long";
    $KfirstName = "";
  }
  
  if (strlen($KlastName) < 2 || strlen($KlastName) > 100) {
    $errorList []= "Last name must be 2-100 characters long";
    $KlastName = "";
  }

  $age = date_diff(date_create($birthday), date_create('now'))->y;
  if ($age < 2) {
    $errorList []= "Your child must be over 2 years old";
    $birthday = "";
  }

  if ($errorList) {
    $valuesList = ['firstName' => $KfirstName, 
                    'lastName' => $KlastName, 
                    'birthday' => $birthday, 
                  ];
    return $this->get('view')->render($response, 'eventinformation.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
  } else {
    DB::insert('children', [
      'userId' => $userId,
      'firstName' => $KfirstName,
      'lastName' => $KlastName,
      'DOB' => $birthday,
      'gender' => $gender
    ]);
    $childId = DB::queryFirstField("SELECT LAST_INSERT_ID() FROM children");
    DB::insert('bookings', [
      'eventId' => $eventId,
      'userId' => $userId,
      'childId' => $childId
    ]);
    return $response->withHeader('Location', '/mybookings')->withStatus(302);
  }
});


// $app->run();
