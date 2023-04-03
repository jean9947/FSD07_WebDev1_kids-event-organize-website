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
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
// use Slim\Routing\RouteCollectorProxy;

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

// // *Check if username is taken using AJAX*
// $app->post('/checkUsername', function ($request, $response, $args) {
//   $username = $request->getParam('username');
//   $result = DB::queryFirstRow('SELECT * FROM users WHERE username = %s', $username);

//   if ($result) {
//       $response->getBody()->write(json_encode(array('taken' => true)));
//   } else {
//       $response->getBody()->write(json_encode(array('taken' => false)));
//   }
//   return $response->withHeader('Content-Type', 'application/json');
// });

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
  // validate password, password_hash()
  if (
      strlen($password) < 6 || strlen($password) > 100
      || (preg_match("/[A-Z]/", $password) !== 1)
      || (preg_match("/[a-z]/", $password) !== 1)
      || (preg_match("/[0-9]/", $password) !== 1)
  ) {
      $errorList[] = "Password must be 6-100 characters long and contain at least one uppercase letter, one lowercase, and one digit.";
      $password ="";
  }
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
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
      'password' => $hashedPassword, 'phoneNumber' => $phoneNumber, 'email' => $email, 'role' => "parent"]);
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
  $errorList = [];

  $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE username=%s", $username);
  $loginSuccessful = ($userRecord != null) && ($userRecord['password'] == $password);

  if (!$userRecord) {
    $errorList[] = "Invalid username";
    $username = "";
  }

  if (!($userRecord['password'] == $password)) {
    $errorList[] = "Wrong password";
    $password = "";
  }

  if ($errorList) { // STATE 2: errors
    $valuesList = ['usernamed' => $username, 'password' => $password];
    return $this->get('view')->render($response, 'login.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
  } 

  if ($loginSuccessful && $userRecord['role'] == "admin") { // logged in as Admin
    unset($userRecord['password']);
    $_SESSION['user'] = $userRecord;
    setFlashMessage("Welcome back admin " . $userRecord['username']);
    return $response->withHeader('Location', '/admin')->withStatus(302);
  } elseif ($loginSuccessful) { // logged in as a customer
    unset($userRecord['password']);
    $_SESSION['user'] = $userRecord;
    setFlashMessage("Welcome back " . $userRecord['username']);
    return $response->withHeader('Location', '/')->withStatus(302);
  } 
});


/**Log Out */
$app->get('/logout', function ($request, $response, $args) {
  unset($_SESSION['user']);
  session_destroy();
  setFlashMessage("You've been logged out.");
  return $response->withHeader('Location', '/')->withStatus(302);
})->setName('logout');


/**Password Reset Request */
$app->get('/passwordresetrequest', function ($request, $response, $args) {
  // validate if the user is logged in already
  if (!isset($_SESSION['user'])) {
    return $this->get('view')->render($response, 'passwordResetRequest.html.twig');
  } else {
    setFlashMessage("You're already logged in");
    return $response->withHeader('Location', '/')->withStatus(302);
  }
});

$app->post('/passwordresetrequest', function ($request, $response, $args) {
  $data = $request->getParsedBody();
  $email = $data['email'];
  $email2 = $data['email2'];
  $errorList = [];

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errorList[] = "Invalid email address format";
    $emai = "";
    $emai2 = "";
  }
  // Check if email is registered in the database
  $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
  if (!$user) {
    $errorList[] = "Email address not found";
    $emai = "";
    $emai2 = "";
  }
  if ($email !== $email2) {
    $errorList[] = "Email address mismatch";
    $emai = "";
    $emai2 = "";
  }
  if ($errorList) {
    $valuesList = ['email' => $email, 'email2' => $email2];
    return $this->get('view')->render($response, 'passwordResetRequest.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
  } else {
    $token = bin2hex(random_bytes(32));
    try {
      $mail = new PHPMailer(true);
      $mail->isSMTP(); 
      // $mail->Host       = 'smtp.gmail.com'; 
      // $mail->SMTPAuth   = true;
      // $mail->Username   = 'playroomfsd07@gmail.com'; 
      // $mail->Password   = 'Playroom@fsd07'; 
      // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
      // $mail->Port       = 587; 
      $mail->Host = 'smtp.mailtrap.io';
      $mail->SMTPAuth = true;
      $mail->Port = 587;
      $mail->Username = '9d148a19b6e434';
      $mail->Password = 'a2667148ccd6e6';
      $mail->SMTPDebug = SMTP::DEBUG_SERVER;
      $mail->setFrom('info@mailtrap.io', 'Mailtrap');
      $mail->addAddress($email); 
      $mail->isHTML(true);
      $mail->Subject = 'Password Reset Request From Playroom';
      $mail->Body    = 'Please click on the following link to reset your password: ' . 'http://' . $_SERVER['HTTP_HOST'] . '/passwordreset/' . $token;
      if (!$mail->send()) {
          setFlashMessage("Failed to send password reset email: " . $mail->ErrorInfo);
      } else {
          DB::update('users', ['token' => $token], "email=%s", $email);
          setFlashMessage("Password reset email has been sent to $email");
      }
    } catch (Exception $e) {
        setFlashMessage("Failed to send password reset email");
    }
    return $response->withHeader('Location', '/')->withStatus(302);
   }
});


/**Reset Password */
$app->get('/passwordreset/{token}', function ($request, $response, $args) {
  $token = $args['token'];
  $user = DB::queryFirstRow("SELECT * FROM users WHERE token=%s", $token);
  if (!$user) {
    setFlashMessage("Invalid password reset link");
    return $response->withHeader('Location', '/passwordresetrequest')->withStatus(302);
  } else {
    return $this->get('view')->render($response, 'passwordReset.html.twig', ['token' => $token]);
  }
});

$app->post('/passwordreset/{token}', function ($request, $response, $args) {
  $token = $args['token'];
  $data = $request->getParsedBody();
  $email = $data['email'];
  $password1 = $data['password1'];
  $password2 = $data['password2'];
  $errorList = [];

  // Check if email is registered in the database
  $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
  if (!$user) {
    $errorList[] = "Email address not found";
    $emai = "";
    $emai2 = "";
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
  $hashedPassword = password_hash($password2, PASSWORD_DEFAULT);
  if ($errorList) { // STATE 2: errors
    $valuesList = ['email' => $email, 'password1' => $password1, 'password2' => $password2];
    return $this->get('view')->render($response, 'passwordReset.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
  } else { // STATE 3: sucess - reset password and update data to the DB
      DB::update('users', ['password' => $hashedPassword], "email=%s", $email);
      setFlashMessage("Password reset successfully");
      return $response->withHeader('Location', '/login')->withStatus(302);
  }
});


/**************************************************************************************** */
// Get event page
$app->get('/event', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  // Fetch all events from the database
  $events = DB::query('SELECT * FROM events WHERE DATE(date) > CURDATE()');
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
  if ($age < 2 || $age > 12) {
    $errorList []= "Your child must between 2-12 years old";
    $birthday = "";
  } 

  if (empty($errorList)) {
    DB::query("UPDATE events SET capacity = capacity - 1, attendeesCount = attendeesCount + 1 WHERE eventId = %i", $eventId);
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
    $bookingId = DB::queryFirstField("SELECT LAST_INSERT_ID() FROM bookings");
    $price = DB::query("SELECT price FROM events WHERE eventId = %i", $eventId);
    $priceValue = (float) $price[0]['price'];
     return $this->get('view')->render($response, 'checkout.html.twig', ['eventId' =>
  $eventId, 'bookingId' => $bookingId, 'price' => $priceValue]); 
  }
});

// list mybookings page
$app->get('/mybookings', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  $userId = isset($_SESSION['user']['userId']) ? $_SESSION['user']['userId'] : null;
  // Fetch bookings only for the logged-in user from the database
  $bookings = DB::query("SELECT c.firstName, c.lastName, u.userId, e.eventName, e.date, e.startTime, e.endTime, e.price, e.venue, e.smallPhotoPath, b.bookingId, e.eventId,e.capacity,e.attendeesCount
    FROM bookings AS b
    JOIN children AS c ON b.childId = c.childId
    JOIN users AS u ON b.userId = u.userId
    JOIN events AS e ON b.eventId = e.eventId
    WHERE DATE(e.date) > CURDATE() AND u.userId = %d", $userId);
  // Render the events page using the events data
  return $this->get('view')->render($response, 'mybookings.html.twig', ['bookings' => $bookings,'session' => ['user' => $userData]]);
});

//edit mybookings
$app->post('/mybookings', function ($request, $response, $args) {  
  $data = $request->getParsedBody();
  $KfirstName = $data['firstName'];
  $KlastName =  $data['lastName'];
  $birthday = $data['birthday'];
  $gender = $data['gender'];
  $bookingId = $data['bookingId'];
  $errorList = [];

  // $bookingId = $args['bookingId'];
  $childId = DB::queryFirstField("SELECT childId FROM bookings WHERE bookingId = %d", $bookingId);
  // echo $bookingId,$childId;

  // $userId = $_SESSION['user']['userId'];

  if (strlen($KfirstName) < 2 || strlen($KfirstName) > 100) {
    $errorList []= "First name must be 2-100 characters long";
    $KfirstName = "";
  }
  
  if (strlen($KlastName) < 2 || strlen($KlastName) > 100) {
    $errorList []= "Last name must be 2-100 characters long";
    $KlastName = "";
  }

  $age = date_diff(date_create($birthday), date_create('now'))->y;
  if ($age < 2 || $age > 12) {
    $errorList []= "Your child must between 2-12 years old";
    $birthday = "";
  } 

  if (empty($errorList)) {
    DB::update('children', [
      'firstName' => $KfirstName,
      'lastName' => $KlastName,
      'DOB' => $birthday,
      'gender' => $gender
    ], 'childId=%d', $childId);
    return $this->get('view')->render($response, 'mybookings.html.twig');
  }
});

// DELETE mybooking
$app->delete('/mybookings/{bookingId}', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  $bookingId = $args['bookingId'];
  DB::delete('bookings', 'bookingId=%d', $bookingId);
  return $this->get('view')->render($response, 'mybookings.html.twig', ['session' => ['user' => $userData]]);
});


$app->get('/check-firstname-length', function ($request, $response, $args) {
  return $this->view->render($response, 'booking_form.html.twig');
});

$app->get('/check-lastname-length', function ($request, $response, $args) {
  return $this->view->render($response, 'booking_form.html.twig');
});

$app->get('/check-children-age', function ($request, $response, $args) {
  return $this->view->render($response, 'booking_form.html.twig');
});

$app->get('/edit-booking', function ($request, $response, $args) {
  return $this->view->render($response, 'mybookings.html.twig');
});

//post payment
$app->post('/checkout', function ($request, $response, $args) use ($twig) {    \Stripe\Stripe::setApiKey('sk_test_51MrqZhFIad2TXYCqhlLDrGvki1RAIsJrWSHObLsAwpwQyxMQ5bLfMp8E5pK79LfKLsGezoo9UKbRm2jqnEwt1j7r00xLUtgCgr');
  $eventId = $request->getParsedBody()['eventId'];
  $event = DB::queryFirstRow("SELECT * FROM events WHERE eventId = %i", $eventId);
  $checkout_session = \Stripe\Checkout\Session::create([
      'payment_method_types' => ['card'],
      'line_items' => [[
        'price_data' => [
          'currency' => 'cad',
          'unit_amount' => $event['price']*100,
          'product_data' => [
            'name' => $event['eventName'],
            'description' => $event['eventDescription'],
          ],
        ],
        'quantity' => 1,
      ]],
      'mode' => 'payment',
      'success_url' => 'http://playroom.org/mybookings',
      'cancel_url' => 'http://playroom.org/mybookings',
  ]);

  $response->getBody()->write($twig->render('checkout1.html.twig', [
    'sessionId' => $checkout_session->id
]));

return $response;
});


/**************************************************************************************** */
/**Payment */

$app->get('/checkout', function ($request, $response, $args) {
  $userData = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  return $this->get('view')->render($response, 'checkout.html.twig',['session' => ['user' => $userData]]);
});

// $app->post('/checkout', function ($request, $response, $args) {
//   $amount = $_POST['amount'];
//   $currency = $_POST['currency'];
//   $description = $_POST['description'];
//   $stripeToken = $_POST['stripeToken'];

//   // Set the API key
//   Stripe::setApiKey(STRIPE_SECRET_KEY);

//   // Create the charge
//   try {
//       $charge = Charge::create([
//           'amount' => $amount,
//           'currency' => $currency,
//           'description' => $description,
//           'source' => $stripeToken,
//       ]);
//   } catch (\Exception $e) {
//       setFlashMessage('Error: ' . $e->getMessage());
//       return $response->withHeader('Location', $request->getUri()->getPath())->withStatus(302);
//   }

//   setFlashMessage('Charge successful! Charge ID: ' . $charge->id);
//   return $response->withHeader('Location', $request->getUri()->getPath())->withStatus(302);
// });


// // $app->run();





// try {
//   // Create a new Stripe Checkout session
//   $session = Session::create([
//       'payment_method_types' => ['card'],
//       'line_items' => [
//           [
//               'price_data' => [
//                   'currency' => 'usd',
//                   'unit_amount' => $params['amount'] * 100, // amount in cents
//                   'product_data' => [
//                       'name' => 'My Product',
//                   ],
//               ],
//               'quantity' => 1,
//           ],
//       ],
//       'mode' => 'payment',
//       'success_url' => 'https://example.com/success',
//       'cancel_url' => 'https://example.com/cancel',
//   ]);

//   // Redirect the user to the Stripe Checkout page
//   $response->getBody()->write(json_encode(['sessionId' => $session->id]));
//   return $response->withHeader('Content-Type', 'application/json');
// } catch (ApiErrorException $e) {
//   // Handle Stripe API errors
//   $response->getBody()->write($e->getMessage());
//   return $response->withStatus(500);
// }
