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
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once 'init.php';


// Admin dashboard
$app->get('/admin', function($request, $response, $args) use ($log) {
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
$app->post('/admin/adduser', function ($request, $response, $args) use ($log) {
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
        $log->info("User added by admin with errors", ['errors' => $errorList, 'values' => $valuesList]);
        return $this->get('view')->render($response, 'admin_adduser.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
        } else { // STATE 3: sucess - add new user to the DB
            global $passwordPepper;
            $passwordPepper = hash_hmac('sha256', $password, $passwordPepper);
            $hashedPassword = password_hash($passwordPepper, PASSWORD_DEFAULT);
            DB::insert('users', ['userId' => NULL, 'username' => $username, 'firstName' => $firstName, 'lastName' => $lastName, 
            'password' => $hashedPassword, 'phoneNumber' => $phoneNumber, 'email' => $email, 'role' => $role]);
            $log->info("New user added by admin ", ['username' => $username]);
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
        $response->getBody()->write("Error: user id not found");
    }

    $data = $request->getParsedBody();
    $firstName = $data['firstName'];
    $lastName = $data['lastName'];
    $username = $data['username'];
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
        'phoneNumber' => $phoneNumber, 'email' => $email];
        return $this->get('view')->render($response, 'admin_updateuser.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: sucess - update the user from the database
    DB::update('users', ['username' => $username, 'firstName' => $firstName, 'lastName' => $lastName, 
    'phoneNumber' => $phoneNumber, 'email' => $email], "userId=%i", $userId);
    setFlashMessage("UserID " . $userId . " updated");
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
            DB::query("UPDATE events SET capacity = capacity - 1, attendeesCount = attendeesCount + 1 WHERE eventId = %i", $eventId);
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
        $response->getBody()->write("Error: booking id not found");
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
        setFlashMessage("BookingID " . $bookingId . " updated");
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
    $events = DB::query("SELECT eventId, eventName, date, startTime, endTime, price, organizer, capacity, attendeesCount FROM events");
    return $this->get('view')->render($response, 'admin_events.html.twig', ['user' => $userRecord, 'isAdmin' => $isAdmin, 'events' => $events]);
});

// photo upload verification
function verifyUploadedPhoto(&$newFilePath, $name) {
    $photo = $_FILES['photo'];
    // is there a photo being uploaded and is it okay?
    if ($photo['error'] != UPLOAD_ERR_OK) {
        return "Error uploading photo " . $photo['error'];
    }
    $info = getimagesize($photo['tmp_name']);
    // make sure it is an image (jpeg, gif, png or bmp)
    $ext = "";
    switch ($info['mime']) {
        case 'image/jpeg':
        $ext = "jpg";
        break;
        case 'image/gif':
        $ext = "gif";
        break;
        case 'image/png':
        $ext = "png";
        break;
        case 'image/bmp':
        $ext = "bmp";
        break;
        default:
        return "Only JPG, GIF, PNG, and BMP file types are accepted";
    }
    // Check if the file already exists
    if (file_exists($newFilePath)) {
    // Generate a random suffix of 10 characters made of letters and digits
    $suffix = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 10);
    // Append the suffix to the file name
    $file_extension = pathinfo($newFilePath, PATHINFO_EXTENSION);
    // Sanitize the file name which are not uppercase/lowecase letter, digit, underscore, minus will be replaced with underscore '_'.
    $sanitized_file_name = preg_replace("/[^A-Za-z0-9_\-]/", "_", $newFilePath);
    $newFilePath = $sanitized_file_name . '_' . $suffix . '.' . $file_extension;
    } else {
    $newFilePath = "uploads/" . $name . "." . $ext;
    }
    return true;
}

/** ADD event */
$app->get('/admin/addevent', function ($request, $response, $args) {
    $events = DB::query("SELECT * FROM events");
    $html = $this->get('view')->fetch('admin_addevent.html.twig', ['events' => $events]);
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->post('/admin/addevent', function ($request, $response, $args) {
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

    // Verify uploaded photo
    $largePhotoPath = $_FILES['largePhoto']['tmp_name'];
    $verifyLResult = verifyUploadedPhoto($largePhotoPath, basename($_FILES['largePhoto']['name']));
    if ($verifyLResult !== true) {
        $errorList []= $verifyLResult;
        $largePhotoPath = "";
    }

    $smallPhotoPath = $_FILES['smallPhoto']['tmp_name'];
    $verifySResult = verifyUploadedPhoto($smallPhotoPath, basename($_FILES['smallPhoto']['name']));
    if ($verifySResult !== true) {
        $errorList []= $verifySResult;
        $smallPhotoPath = "";
    }

    if(isset($_SESSION['user'])) {
        $smallPhotoPath = verifyUploadedPhoto($_FILES['smallPhoto']['tmp_name'], $_FILES['smallPhoto']['name']);
        $largePhotoPath = verifyUploadedPhoto($_FILES['largePhoto']['tmp_name'], $_FILES['largePhoto']['name']);
        if (!$eventName || !$smallPhotoPath || !$largePhotoPath || !$date || !$startTime || !$endTime || 
        !$eventDescription || !$price || !$organizer || !$venue || !$capacity || !$attendeesCount) {
            $errorList []= "Please fill in all";
    }
    if ($errorList) { // STATE 2: errors
        $valuesList = ['eventName' => $eventName, 'smallPhotoPath' => $smallPhotoPath, 'largePhotoPath' => $largePhotoPath, 
        'date' => $date, 'startTime' => $startTime, 'endTime' => $endTime, 'eventDescription' => $eventDescription, 'price' => $price, 
        'organizer' => $organizer, 'venue' => $venue, 'capacity' => $capacity, 'attendeesCount' => $attendeesCount];
        } else { // STATE 3: sucess - add new event to the DB
            DB::insert('event', ['eventId' => NULL, 'eventName' => $eventName, 'smallPhotoPath' => $smallPhotoPath, 'largePhotoPath' => $largePhotoPath, 
            'date' => $date, 'startTime' => $startTime, 'endTime' => $endTime, 'eventDescription' => $eventDescription, 'price' => $price, 
            'organizer' => $organizer, 'venue' => $venue, 'capacity' => $capacity, 'attendeesCount' => $attendeesCount]);
            return $response->withHeader('Location', '/admin/events')->withStatus(302);
        } 
    } else {
        return $response->withHeader('Location', '/login')->withStatus(302);
    } 
});

/** UPDATE event */
$app->get('/admin/events/{eventId}', function ($request, $response, $args) {
    // Check if user is authenticated
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        setFlashMessage("Admin must log in to edit.");
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    $userRecord = $_SESSION['user']['username'];
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
    $eventId = $args['eventId'];
    // Get the user record based on the provided id
    $eventRecord = DB::queryFirstRow("SELECT * FROM events WHERE eventId=%d", $eventId);
    if (!$eventRecord) {
        $response->getBody()->write("Error: event not found");
    }
    return $this->get('view')->render($response, 'admin_events.html.twig', ['user' => $userRecord, 'isAdmin' => $isAdmin, 'eventRecord' => $eventRecord]);
});

$app->post('/admin/events/{eventgId}', function ($request, $response, $args) {
    $eventId = $args['eventId'];
    // Get the user record based on the provided id
    $eventRecord = DB::queryFirstRow("SELECT * FROM events WHERE eventId=%d", $eventId);
    if (!$eventRecord) {
        $response->getBody()->write("Error: event id not found");
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

    // Verify uploaded photo
    $largePhotoPath = $_FILES['largePhoto']['tmp_name'];
    $verifyLResult = verifyUploadedPhoto($largePhotoPath, basename($_FILES['largePhoto']['name']));
    if ($verifyLResult !== true) {
        $errorList []= $verifyLResult;
        $largePhotoPath = "";
    }

    $smallPhotoPath = $_FILES['smallPhoto']['tmp_name'];
    $verifySResult = verifyUploadedPhoto($smallPhotoPath, basename($_FILES['smallPhoto']['name']));
    if ($verifySResult !== true) {
        $errorList []= $verifySResult;
        $smallPhotoPath = "";
    }

    if (!$eventName || !$smallPhotoPath || !$largePhotoPath || !$date || !$startTime || !$endTime || 
    !$eventDescription || !$price || !$organizer || !$venue || !$capacity || !$attendeesCount) {
        $errorList []= "Please fill in all";
      }
    if ($errorList) { // STATE 2: errors
    $valuesList = ['eventName' => $eventName, 'smallPhotoPath' => $smallPhotoPath, 'largePhotoPath' => $largePhotoPath, 
    'date' => $date, 'startTime' => $startTime, 'endTime' => $endTime, 'eventDescription' => $eventDescription, 'price' => $price, 
    'organizer' => $organizer, 'venue' => $venue, 'capacity' => $capacity, 'attendeesCount' => $attendeesCount];
    return $this->get('view')->render($response, 'admin_events.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: sucess - add new user to the DB
        DB::update('event', ['eventName' => $eventName, 'smallPhotoPath' => $smallPhotoPath, 'largePhotoPath' => $largePhotoPath, 
        'date' => $date, 'startTime' => $startTime, 'endTime' => $endTime, 'eventDescription' => $eventDescription, 'price' => $price, 
        'organizer' => $organizer, 'venue' => $venue, 'capacity' => $capacity, 'attendeesCount' => $attendeesCount], "eventId=%d", $eventId);
        setFlashMessage("EventID " . $eventId . " updated");
        return $response->withHeader('Location', '/admin/events')->withStatus(302);
    }
});

/** DELETE event */
$app->delete('/admin/events/{eventId}', function ($request, $response, $args) {
    $eventId = $args['eventId'];
    DB::delete('events', 'eventId=%d', $eventId);
    return $this->get('view')->render($response, 'admin_events.html.twig');
});
