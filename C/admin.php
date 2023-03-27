<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Exception\HttpNotFoundException;


require_once 'init.php';


// dashboard
$app->get('/admin', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'admin.html.twig');
});



/************************************** Users ************************************************** */

/** VIEW all users */
$app->get('/admin/users', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'admin_adduser.html.twig');
});


/** ADD users */
// STATE 1: first display of the form
$app->get('/admin/adduser', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'admin_adduser.html.twig');
});

// // *Check if username is taken using AJAX*
// $app->post('/checkUsername', function (Request $request, Response $response) {
//     $username = $request->getParam('username');
//     $result = DB::queryFirstRow('SELECT * FROM users WHERE username = %s', $username);

//     if ($result) {
//         $response->getBody()->write(json_encode(array('taken' => true)));
//     } else {
//         $response->getBody()->write(json_encode(array('taken' => false)));
//     }
//     return $response->withHeader('Content-Type', 'application/json');
// });

// SATE 2&3: receiving a submission
$app->post('/admin/adduser', function ($request, $response, $args) {
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
        return $this->get('view')->render($response, 'admin_adduser.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: sucess - add new user to the DB
        DB::insert('users', ['userId' => NULL, 'username' => $username, 'firstName' => $firstName, 'lastName' => $lastName, 
        'password' => $password, 'phoneNumber' => $phoneNumber, 'email' => $email, 'role' => "parent"]);
        // return $this->get('view')->render($response, 'registered.html.twig');
        setFlashMessage("user added, redirecting to the admin page...");
        return $response->withRedirect("/admin");   
    }
});


/** DELETE user */
$app->get('/admin/deleteuser/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    // Get the user record based on the provided id
    $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE userId=%i", $id);
    if (!$userRecord) {
        $response->getBody()->write("Error: User not found");
    }
    // Display the delete confirmation form
    return $this->get('view')->render($response, 'admin_deleteuser.html.twig', ['userRecord' => $userRecord]);
});

$app->post('/admin/deleteuser/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    // Get the user record based on the provided id
    $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE userId=%i", $id);
    if (!$userRecord) {
        $response->getBody()->write("Error: user not found");
    }
    // Delete the user from the database
    DB::delete('users', 'userId=%d', $id);
    // Display a success message
    // return $this->get('view')->render($response, 'admin_deleteduser.html.twig');
    setFlashMessage("user deleted, redirecting to the admin page...");
    return $response->withRedirect("/admin"); 
});


/** UPDATE user */
$app->get('/admin/updateuser/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    // Get the user record based on the provided id
    $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE userId=%i", $id);
    if (!$userRecord) {
        $response->getBody()->write("Error: user not found");
    }
    return $this->get('view')->render($response, 'admin_updateuser.html.twig', ['userRecord' => $userRecord]);
});

$app->post('/admin/updateuser/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    // Get the user record based on the provided id
    $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE userId=%i", $id);
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
        return $this->get('view')->render($response, 'admin_updateuser.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: sucess - update the user from the database
    DB::update('users', ['username' => $username, 'firstName' => $firstName, 'lastName' => $lastName, 
    'password' => $password, 'phoneNumber' => $phoneNumber, 'email' => $email]);
    // Display a success message
    // return $this->get('view')->render($response, 'admin_updateduser.html.twig');
    setFlashMessage("user updated, redirecting to the admin page...");
    return $response->withRedirect("/admin");   
    }
});



/************************************** Bookings ************************************************** */

/** VIEW all bookings */

/** ADD booking */

/** DELETE booking */

/** UPDATE booking */