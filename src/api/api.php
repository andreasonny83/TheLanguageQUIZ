<?php
require_once dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php';
use \Slim\Slim;
use \Config\Database\DbHandler;
use Config\SecureSessionHandler;

// For debug only
// ini_set( 'display_errors', 1 );
// error_reporting( E_ALL );

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->config( 'debug', true ); // Dev
// $app->config( 'debug', false );

/**
 * Perform an API get status
 * This is not much than a ping on the API server
 */
$app->get(
	'/status', function() {
		$response['status'] = 'ok';
		echoRespnse( 200, $response );
		exit;
	}
);

/**
 * Perform a POST test
 * and reply some test string if the API are alive
 */
$app->post(
	'/test', function() {
		$app = \Slim\Slim::getInstance();
		$response = '{"Hello"}';

		$app->contentType( 'application/json' );

		echoRespnse( 200, $response );
		exit;
	}
);

/**
 * Try to perform a user log in
 * If the user is authenticated, then start the session cookie
 */
$app->post(
	'/login', function() use ( $app ) {
		// Dev only
		// Sleep 3 seconds before processing the request
		// to display the loader
		sleep(1);

		// check for required params
		verify_required_params( array( 'email', 'password' ) );

		// reading post params
		$email    = $app->request()->post( 'email' );
		$password = $app->request()->post( 'password' );
		$response = array(
			'request' => 'login'
		);
		// Sanitize data
		$email    = filter_var(  $email, FILTER_SANITIZE_EMAIL );
		$password = filter_var( $password, FILTER_SANITIZE_STRING );
		// Validate data
		if ( ! ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) ) {
			$response['error']   = true;
			$response['message'] = 'Input data not valid';
			echoRespnse( 401, $response );
			$app->stop();
		}

		$db = new DbHandler();

		// check for correct email and password
		if ( $db->checkLogin( $email, $password ) ) {
			$response['error'] = false;
			$response['login'] = true;
			echoRespnse( 200, $response );
			$app->stop();
		}
		else {
			$response['error']   = true;
			$response['message'] = 'Forbidden';
			echoRespnse( 401, $response );
			$app->stop();
		}
	}
);

/**
 * Register a new user
 */
$app->post(
	'/register', function() use ( $app ) {
		// Dev only
		// Sleep 3 seconds before processing the request
		// to display the loader
		sleep(1);

		// check for required params
		verify_required_params( array(
			'name',
			'email',
			'email_confirm',
			'password',
			'password_confirm',
			'language'
		));

		// reading post params
		$user = array(
			'name'             => $app->request()->post( 'name' ),
			'email'            => $app->request()->post( 'email' ),
			'email_confirm'    => $app->request()->post( 'email_confirm' ),
			'password'         => $app->request()->post( 'password' ),
			'password_confirm' => $app->request()->post( 'password_confirm' ),
			'language'         => $app->request()->post( 'language' ),
		);

		// prepare the answer
		$response = array(
			'request' => 'register'
		);

		// Sanitize data
		$user['name']             = filter_var(  $user['name'], FILTER_SANITIZE_STRING );
		$user['email']            = filter_var(  $user['email'], FILTER_SANITIZE_EMAIL );
		$user['email_confirm']    = filter_var(  $user['email_confirm'], FILTER_SANITIZE_EMAIL );
		$user['password']         = filter_var( $user['password'], FILTER_SANITIZE_STRING );
		$user['password_confirm'] = filter_var( $user['password_confirm'], FILTER_SANITIZE_STRING );
		$user['language']         = filter_var( $user['language'], FILTER_SANITIZE_STRING );

		//Make sure the 2 emails are the same
		if ( $user['email'] !== $user['email_confirm'] ) {
			$response['error']   = true;
			$response['message'] = 'Email verification failed.';
			echoRespnse( 401, $response );
			$app->stop();
		}
		//Make sure the 2 paswwords are the same
		if ( $user['password'] !== $user['password_confirm'] ) {
			$response['error']   = true;
			$response['message'] = 'Password verification failed.';
			echoRespnse( 401, $response );
			$app->stop();
		}

		// Validate data
		if ( ! ( filter_var( $user['email'], FILTER_VALIDATE_EMAIL ) ) ) {
			$response['error']   = true;
			$response['message'] = 'Email is not a not valid one';
			echoRespnse( 401, $response );
			$app->stop();
		}

		$db = new DbHandler();

		// Try to register a new user
		if ( $db->checkRegister( $user ) ) {
			$response['error']    = false;
			$response['register'] = true;

			// Log the user in
			if ( $db->checkLogin( $user['email'], $user['password'] ) ) {
				$response['error'] = false;
				$response['login'] = true;
				echoRespnse( 200, $response );
				$app->stop();
			}
			else {
				$response['error']   = true;
				$response['message'] = 'Forbidden';
				echoRespnse( 401, $response );
				$app->stop();
			}
		}
		else {
			$response['error']   = true;
			$response['message'] = 'Another user with the same email already exists in the database.';
			echoRespnse( 401, $response );
			$app->stop();
		}
	}
);

$app->post(
	'/user/:userid', function( $userid ) use ( $app ) {
		sleep(1);
		$response = array(
			'request' => 'user'
		);
		$user = array();
		$db   = new DbHandler();


		$userSession = $app->getCookie('LQ_session');
		$user = $db->getUser( $userid, $userSession );

		if ( !empty( $user ) ) {
			$response['error'] = false;
			$response['user']  = $user;
			echoRespnse( 200, $response );
			$app->stop();
		}

		$response['error'] = true;
		echoRespnse( 400, $response );
		$app->stop();
});

/**
 * Log out
 * terminate the current session
 */
$app->get(
	'/logout', function() use ( $app ) {
		// Dev only
		sleep(1);

		$user_session = isset( $_COOKIE["LQ_session"] ) ? $_COOKIE["LQ_session"] : '';
		$db           = new DbHandler();

		$response = array(
			'request' => 'logout'
		);

		// If the session cookie is not set
		// terminate the request
		if ( $user_session === '' ) {
			$response['error']      = true;
			$response['logged_out'] = true;
			$response['message']    = 'Impossible to get the required user information to perform a log out';

			echoRespnse( 200, $response );
			$app->stop();
		}

		$db->logOut( $user_session );
		$response['error']      = false;
		$response['logged_out'] = true;
		echoRespnse( 200, $response );
		$app->stop();

	}
);

/**
 * Verifying required params posted or not
 */
function verify_required_params( $required_fields ) {
	$error          = false;
	$error_fields   = '';
	$request_params = array();
	$request_params = $_REQUEST;

	// Handling PUT request params
	if ( $_SERVER['REQUEST_METHOD'] === 'PUT' ) {
		$app = \Slim\Slim::getInstance();
		parse_str( $app->request()->getBody(), $request_params );
	}
	foreach ( $required_fields as $field ) {
		if ( !isset( $request_params[$field] ) || strlen( trim( $request_params[$field] ) ) <= 0 ) {
			$error         = true;
			$error_fields .= $field . ', ';
		}
	}

	if ( $error ) {
		// Required field(s) are missing or empty
		// echo error json and stop the app
		$response            = array();
		$app                 = \Slim\Slim::getInstance();
		$response['error']   = true;
		$response['message'] = 'Required field(s) ' . substr( $error_fields, 0, -2 ) . ' is missing or empty';

		echoRespnse( 401, $response );
		$app->stop();
	}
}

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate( \Slim\Route $route ) {
	// Getting request headers
	$headers  = apache_request_headers();
	$response = array();
	$app = \Slim\Slim::getInstance();

	if ( isset( $headers['X-API-KEY'] ) ) {

		// get the api key
		$api_key = $headers['X-API-KEY'];
		// validating api key
		if ( $api_key !== '612e648bf9594adb50844cad6895f2cf' ) {
			// api key is not present in users table
			$response['error']   = true;
			$response['message'] = 'Access Denied. Invalid Api key';
			echoRespnse( 401, $response );
			$app->stop();
		}
	}
	else {
		// api key is missing in header
		$response['error']   = true;
		$response['message'] = 'Api key is misssing';
		echoRespnse( 401, $response );
		$app->stop();
	}
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse( $status_code, $response ) {
	$app = \Slim\Slim::getInstance();

	// Http response code
	$app->status( $status_code );

	// setting response content type to json
	$app->contentType( 'application/json' );

	echo json_encode( $response );
}


//--- OLD LANGUAGE QUIZ
// Functins to be replaced according to the new app


/**
 * Used to get the username when the user is logging in
 * using Google+
*/
$app->post(
	'/getuseruid', 'authenticate', function() use ( $app ) {
		$email    = $app->request()->post( 'email' );

		$response = array();
		$db       = new DbHandler();

		$response['error']   = false;
		$response['user'] = $db->getUserUIDByEmail( $email );
		echoRespnse( 200, $response );
		$app->stop();
	}
);

$app->post(
	'/user/:useruid/collection/new', function( $useruid ) use ( $app ) {
		$collection    = $app->request()->post('collection');

		$response = array();
		$db       = new DbHandler();

		$response['error']   = false;
		$response['collection_uid'] = $db->newCollection( $collection );

		echoRespnse( 200, $response );
		$app->stop();
	}
);

$app->get(
	'/user/:useruid/collections/globals', function( $useruid ) use ( $app ) {
		$page = $app->request()->get( 'page' );

		$response = array();
		$db       = new DbHandler();

		$response['collections'] = $db->fetchGlobalCollections( $useruid, $page );

		$response['error']   = false;
		// $response['collections'][$page] = $response;

		echoRespnse( 200, $response );
		$app->stop();
	}
);

$app->get(
	'/user/:useruid/collections/', function( $useruid ) use ( $app ) {
		$page = $app->request()->get( 'page' );

		$response = array();
		$db       = new DbHandler();

		$response['collections'] = $db->fetchPrivateCollections( $useruid );

		$response['error']   = false;

		echoRespnse( 200, $response );
		$app->stop();
	}
);

$app->run();
?>
