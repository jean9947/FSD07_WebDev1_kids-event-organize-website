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


// Admin dashboard
$app->get('/admin', function($request, $response, $args) {
    // Check if user is authenticated
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        setFlashMessage("Admin must log in to edit.");
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    $userRecord = $_SESSION['user']['username'];
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
    $users = DB::query("SELECT userId, role, username, password, firstName, lastName, phoneNumber, email FROM users WHERE role='admin'");
    return $this->get('view')->render($response, 'admin.html.twig', ['user' => $userRecord, 'isAdmin' => $isAdmin, 'users' => $users]);
});


/************************************** Users - CRUD ************************************************** */

/** VIEW all users */
$app->get('/admin/users', function($request, $response) {
    // Check if user is authenticated
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        setFlashMessage("Admin must log in to edit.");
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    $userRecord = $_SESSION['user']['username'];
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
    $users = DB::query("SELECT userId, role, username, password, firstName, lastName, phoneNumber, email FROM users WHERE role='parent'");
    return $this->get('view')->render($response, 'admin_users.html.twig', ['user' => $userRecord, 'isAdmin' => $isAdmin, 'users' => $users]);
});


/*************************************************************** */

/** ADD users */
// STATE 1: first display of the form
$app->get('/admin/adduser', function ($request, $response, $args) {
    $users = DB::query("SELECT * FROM users");
    $html = $this->get('view')->fetch('admin_adduser.html.twig', [
        'users' => $users]);
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// SATE 2&3: receiving a submission
$app->post('/admin/adduser', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $firstName = $data['firstName'];
    $lastName = $data['lastName'];
    $username = $data['username'];
    $password = $data['password'];
    $phoneNumber = $data['phoneNumber'];
    $role = $data['role'];
    $email = $data['email'];
    
    $errorList = [];

    if(isset($_SESSION['user'])) {
        $userId = $_SESSION['user']['userId'];
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
            $password = "";
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
        $valuesList = ['firstName' => $firstName, 'lastName' => $lastName, 'username' => $username, 'password' => $password, 
                        'phoneNumber' => $phoneNumber, 'email' => $email, 'role' => $role];
        return $this->get('view')->render($response, 'admin_adduser.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
        } else { // STATE 3: sucess - add new user to the DB
            DB::insert('users', ['userId' => NULL, 'username' => $username, 'firstName' => $firstName, 'lastName' => $lastName, 
            'password' => $password, 'phoneNumber' => $phoneNumber, 'email' => $email, 'role' => $role]);
            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }
    } else {
        return $response->withHeader('Location', '/login')->withStatus(302);
      }
});

/*************************************************************** */

/** DELETE user */
$app->delete('/admin/users/{userId}', function ($request, $response, $args) {
    // $userId = $request->getParam('userId');
    $userId = $args['userId'];
    DB::delete('users', 'userId=%d', $userId);
    return $this->get('view')->render($response, 'admin_users.html.twig');
});

/*************************************************************** */

/** UPDATE user */
$app->get('/admin/updateuser/{userId}', function ($request, $response, $args) {
    // Check if user is authenticated
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        setFlashMessage("Admin must log in to edit.");
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    $userRecord = $_SESSION['user']['username'];
    $isAdmin = ($_SESSION['user']['role'] === 'admin');

    $userId = $args['userId'];
    // Get the user record based on the provided id
    $userResult = DB::queryFirstRow("SELECT * FROM users WHERE userId=%d", $userId);
    if (!$userRecord) {
        $response->getBody()->write("Error: user not found");
    }
    return $this->get('view')->render($response, 'admin_updateuser.html.twig', ['user' => $userRecord, 'isAdmin' => $isAdmin, 'userResult' => $userResult]);
});

$app->post('/admin/updateuser/{userId}', function ($request, $response, $args) {
    $userId = $args['userId'];
    // Get the user record based on the provided id
    $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE userId=%i", $userId);
    if (!$userRecord) {
        $response->getBody()->write("Error: user not found");
    }

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
    // validate username
    if (preg_match('/^[a-z][a-z0-9_]{3,19}$/', $username) !== 1) {
        $errorList[] = "Username must be made up of 4-20 letters, digits, or underscore. The first character must be a letter";
        $username = "";
    } 
    // validate password
    if (
        strlen($password) < 6 || strlen($password) > 100
        || (preg_match("/[A-Z]/", $password) !== 1)
        || (preg_match("/[a-z]/", $password) !== 1)
        || (preg_match("/[0-9]/", $password) !== 1)
    ) {
        $errorList[] = "Password must be 6-100 characters long and contain at least one uppercase letter, one lowercase, and one digit.";
        $password = "";
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
        $valuesList = ['username' => $username, 'firstName' => $firstName, 'lastName' => $lastName, 
        'password' => $password, 'phoneNumber' => $phoneNumber, 'email' => $email];
        return $this->get('view')->render($response, 'admin_updateuser.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: sucess - update the user from the database
    DB::update('users', ['username' => $username, 'firstName' => $firstName, 'lastName' => $lastName, 
    'password' => $password, 'phoneNumber' => $phoneNumber, 'email' => $email], "userId=%i", $userId);
    return $response->withHeader('Location', '/admin/users')->withStatus(302);
    }
});



/************************************** Bookings - CRUD ************************************************** */

/** VIEW all bookings */
$app->get('/admin/bookings', function($request, $response) {
    // Check if user is authenticated
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        setFlashMessage("Admin must log in to edit.");
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    $userRecord = $_SESSION['user']['username'];
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
    $booking = DB::query("SELECT bookingId, eventId , userId, childId, bookingTimeStamp FROM bookings");
    return $this->get('view')->render($response, 'admin_bookings.html.twig', ['user' => $userRecord, 'isAdmin' => $isAdmin, 'bookings' => $booking]);
});


/** ADD booking */
$app->get('/admin/addbooking', function ($request, $response, $args) {
    $bookings = DB::query("SELECT * FROM bookings");
    $html = $this->get('view')->fetch('admin_addbooking.html.twig', [
        'bookings' => $bookings]);
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->post('/admin/addbooking', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $eventId = $data['eventId'];
    $userId = $data['userId'];
    $childId = $data['childId'];

    $errorList = [];

    if(isset($_SESSION['user'])) {
        if (!$eventId || !$userId || !$childId) {
            $errorList []= "Please fill in all";
          }
        if ($errorList) { // STATE 2: errors
        $valuesList = ['eventId' => $eventId, 'userId' => $userId, 'childId' => $childId];
        return $this->get('view')->render($response, 'admin_addbooking.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
        } else { // STATE 3: sucess - add new user to the DB
            DB::insert('bookings', ['bookingId' => NULL, 'eventId' => $eventId, 'userId' => $userId, 'childId' => $childId]);
            return $response->withHeader('Location', '/admin/bookings')->withStatus(302);
        }
    } else {
        return $response->withHeader('Location', '/login')->withStatus(302);
      }
});


/** DELETE booking */
$app->delete('/admin/bookings/{bookingId}', function ($request, $response, $args) {
    $bookingId = $args['bookingId'];
    DB::delete('bookings', 'bookingId=%d', $bookingId);
    return $this->get('view')->render($response, 'admin_bookings.html.twig');
});


/** UPDATE booking */
$app->get('/admin/updatebooking/{bookingId}', function ($request, $response, $args) {
    // Check if user is authenticated
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        setFlashMessage("Admin must log in to edit.");
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    $userRecord = $_SESSION['user']['username'];
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
  
    $bookingId = $args['bookingId'];
    // Get the user record based on the provided id
    $bookingRecord = DB::queryFirstRow("SELECT * FROM bookings WHERE bookingId=%i", $bookingId);
    if (!$bookingRecord) {
        $response->getBody()->write("Error: booking not found");
    }
    return $this->get('view')->render($response, 'admin_updatebooking.html.twig', ['user' => $userRecord, 'isAdmin' => $isAdmin, 
    'bookingRecord' => $bookingRecord]);
});

$app->post('/admin/updatebooking/{bookingId}', function ($request, $response, $args) {
    $bookingId = $args['bookingId'];
    // Get the user record based on the provided id
    $bookingRecord = DB::queryFirstRow("SELECT * FROM bookings WHERE bookingId=%i", $bookingId);
    if (!$bookingRecord) {
        $response->getBody()->write("Error: booking not found");
    }

    $data = $request->getParsedBody();
    $eventId = $data['eventId'];
    $userId = $data['userId'];
    $childId = $data['childId'];
    
    $errorList = [];

    if (!$eventId || !$userId || !$childId) {
        $errorList []= "Please fill in all";
      }
    if ($errorList) { // STATE 2: errors
    $valuesList = ['eventId' => $eventId, 'userId' => $userId, 'childId' => $childId];
    return $this->get('view')->render($response, 'admin_updatebooking.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: sucess - add new user to the DB
        DB::update('bookings', ['eventId' => $eventId, 'userId' => $userId, 'childId' => $childId], "bookingId=%i", $bookingId);
        return $response->withHeader('Location', '/admin/bookings')->withStatus(302);
    }
});



/************************************** Events ************************************************** */

/** VIEW all events */
$app->get('/admin/events', function($request, $response) {
    // Check if user is authenticated
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        setFlashMessage("Admin must log in to edit.");
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    $userRecord = $_SESSION['user']['username'];
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
    $events = DB::query("SELECT eventId, eventName, date, price, capacity, attendeesCount FROM events");
    return $this->get('view')->render($response, 'admin_events.html.twig', ['user' => $userRecord, 'isAdmin' => $isAdmin, 'events' => $events]);
});

/** UPDATE event */
$app->get('/admin/updateevent/{eventId}', function ($request, $response, $args) {
    // Check if user is authenticated
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        setFlashMessage("Admin must log in to edit.");
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    $userRecord = $_SESSION['user']['username'];
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
  
    $eventId = $args['eventId'];
    // Get the user record based on the provided id
    $eventRecord = DB::queryFirstRow("SELECT * FROM events WHERE eventId=%i", $eventId);
    if (!$eventRecord) {
        $response->getBody()->write("Error: event not found");
    }
    return $this->get('view')->render($response, 'admin_updateevent.html.twig', ['user' => $userRecord, 'isAdmin' => $isAdmin, 'eventRecord' => $eventRecord]);
});

$app->post('/admin/updateevent/{eventgId}', function ($request, $response, $args) {
    $eventId = $args['eventId'];
    // Get the user record based on the provided id
    $eventRecord = DB::queryFirstRow("SELECT * FROM events WHERE eventId=%d", $eventId);
    if (!$eventRecord) {
        $response->getBody()->write("Error: event not found");
    }

    $data = $request->getParsedBody();
    $eventName = $data['eventName'];
    $smallPhotoPath = $data['smallPhotoPath'];
    $largePhotoPath = $data['largePhotoPath'];
    $date = $data['date'];
    $startTime = $data['startTime'];
    $endTime = $data['endTime'];
    $eventDescription = $data['eventDescription']; 
    $price = $data['price'];
    $organizer = $data['organizer'];
    $venue = $data['venue'];
    $capacity = $data['capacity'];
    $attendeesCount = $data['attendeesCount'];
    
    $errorList = [];

    if (!$eventName || !$smallPhotoPath || !$largePhotoPath || !$date || !$startTime || !$endTime || 
    !$eventDescription || !$price || !$organizer || !$venue || !$capacity || !$attendeesCount) {
        $errorList []= "Please fill in all";
      }
    if ($errorList) { // STATE 2: errors
    $valuesList = ['eventName' => $eventName, 'smallPhotoPath' => $smallPhotoPath, 'largePhotoPath' => $largePhotoPath, 
    'date' => $date, 'startTime' => $startTime, 'endTime' => $endTime, 'eventDescription' => $eventDescription, 'price' => $price, 
    'organizer' => $organizer, 'venue' => $venue, 'capacity' => $capacity, 'attendeesCount' => $attendeesCount];
    return $this->get('view')->render($response, 'admin_addevent.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: sucess - add new user to the DB
        DB::update('event', ['eventName' => $eventName, 'smallPhotoPath' => $smallPhotoPath, 'largePhotoPath' => $largePhotoPath, 
        'date' => $date, 'startTime' => $startTime, 'endTime' => $endTime, 'eventDescription' => $eventDescription, 'price' => $price, 
        'organizer' => $organizer, 'venue' => $venue, 'capacity' => $capacity, 'attendeesCount' => $attendeesCount], "eventId=%d", $eventId);
        return $response->withHeader('Location', '/admin/events')->withStatus(302);
    }
});

/** DELETE event */
$app->delete('/admin/events/{eventId}', function ($request, $response, $args) {
    $bookingId = $args['eventId'];
    DB::delete('events', 'eventId=%d', $bookingId);
    return $this->get('view')->render($response, 'admin_events.html.twig');
});