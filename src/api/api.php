<?php
require_once dirname( __FILE__ ) . '/vendor/autoload.php';
use \Slim\Slim;
use \Slim\LogWriter;
use \Config\Database\DbHandler;
use \Config\SecureSessionHandler;

\Slim\Slim::registerAutoloader();

$_ENV['SLIM_MODE'] = APP_ENV;

$app = new \Slim\Slim();
$app->setName( 'LQ_API' );

// Only invoked if mode is "production"
$app->configureMode( 'production', function () use ( $app ) {
	$app->config( array(
		'log.enable' => true,
		'debug' => false
	));
	$handle = fopen( 'debug.log', 'w' );
	$app->log->setWriter( new \Slim\LogWriter( $handle ) );
});

// Only invoked if mode is "development"
$app->configureMode( 'development', function () use ( $app ) {
	$app->config( array(
		'log.enable' => false,
		'debug' => true
	));
});

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
			$response['error'] = true;
			$response['msg']   = 'Input data not valid.';
			echoRespnse( 401, $response );
			$app->stop();
		}

		$db = new DbHandler();

		// check for correct email and password
		if ( $db_uid = $db->checkUserExisits( $email ) ) {
			// Check the brute force
			if ( $db->checkBrute( $db_uid ) ) {
				// Account is locked
				$response['error'] = true;
				$response['msg']   = 'Accout locked.';
				echoRespnse( 401, $response );
				$app->stop();
			}

			if ( $db->userLogin( $email, $password ) ) {
				$response['error'] = false;
				$response['login'] = true;
				$response['msg']   = 'User logged in.';
				echoRespnse( 200, $response );
				$app->stop();
			}
			else {
				$response['error'] = true;
				$response['msg']   = 'Password wrong.';
				echoRespnse( 401, $response );
				$app->stop();
			}
		}
		else {
			$response['error'] = true;
			$response['msg']   = 'User not found.';
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
		sleep(2);

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
			$response['error'] = true;
			$response['msg']   = 'Email verification failed.';
			echoRespnse( 401, $response );
			$app->stop();
		}

		//Make sure the 2 passwords are the same
		if ( $user['password'] !== $user['password_confirm'] ) {
			$response['error']   = true;
			$response['msg']     = 'Password verification failed.';
			echoRespnse( 401, $response );
			$app->stop();
		}

		//Make sure the user has selected a language
		if ( $user['language'] === 'undefined' || $user['language'] === '' ) {
			$response['error']   = true;
			$response['msg']     = 'The language is missing.';
			echoRespnse( 401, $response );
			$app->stop();
		}

		// Validate data
		if ( ! ( filter_var( $user['email'], FILTER_VALIDATE_EMAIL ) ) ) {
			$response['error'] = true;
			$response['msg']   = 'Email not valid.';
			echoRespnse( 401, $response );
			$app->stop();
		}

		$db = new DbHandler();

		// Try to register a new user
		if ( $db->userRegister( $user ) ) {
			$response['error']    = false;
			$response['register'] = true;

			// Log the user in
			if ( $db->userLogin( $user['email'], $user['password'] ) ) {
				$response['error'] = false;
				$response['login'] = true;
				$response['msg']   = 'User correctly registered.';
				echoRespnse( 200, $response );
				$app->stop();
			}
			else {
				$response['error'] = true;
				$response['msg']   = 'Username or passeord wrong.';
				echoRespnse( 401, $response );
				$app->stop();
			}
		}
		else {
			$response['error'] = true;
			$response['msg']   = 'Another user with the same email already exists in the database.';
			echoRespnse( 401, $response );
			$app->stop();
		}
	}
);

$app->get(
	'/user/:userid', 'authenticate', function( $userUID ) use ( $app ) {
		sleep(2);
		$response = array(
			'request' => 'user'
		);

		$user_cookie = $app->getCookie( 'lq_user_id' );

		// The userid provided by the app url must be the same as the one stored inside the user's cookie
		$db = new DbHandler();

		if ( $userUID !== $user_cookie ) {
			$user_session = isset( $_COOKIE["LQ_session"] ) ? $_COOKIE["LQ_session"] : '';
			// $db->logOut( $user_session );

			$response['error'] = true;
			$response['msg']   = 'Cannot verify the user identity. Please log in.';
			echoRespnse( 400, $response );
			$app->stop();
		}

		$user = array();
		$user = $db->getUser( $userUID );

		if ( !empty( $user ) ) {
			$response['error'] = false;
			$response['user']  = $user;
			$response['msg']   = 'User found.';
			echoRespnse( 200, $response );
			$app->stop();
		}

		$user_session = isset( $_COOKIE["LQ_session"] ) ? $_COOKIE["LQ_session"] : '';
		// $db->logOut( $user_session );

		$response['error'] = true;
		$response['msg']   = 'The user is not present in the database.';
		echoRespnse( 400, $response );
		$app->stop();
});

$app->get(
	'/user/:userid/profile', 'authenticate', function( $userUID ) use ( $app ) {
		sleep(2);
		$response = array(
			'request' => 'profile'
		);

		$user = array();
		$db   = new DbHandler();

		$profile = $db->getUserProfile( $userUID );

		if ( !empty( $profile ) ) {
			$response['error']   = false;
			$response['profile'] = $profile;
			$response['msg']     = 'Profile information updated.';
			echoRespnse( 200, $response );

			$app->stop();
		}

		$response['error'] = true;
		$response['msg']   = 'Impossible to retrieve the user information.';
		echoRespnse( 400, $response );
		$app->stop();
});

$app->post(
	'/user/:userid/profile', 'authenticate', function() use ( $app ) {
		sleep(2);
		// check for required params
		verify_required_params( array(
			'username',
			'email'
		));

		// reading post params
		$user = array(
			'name'             => $app->request()->post( 'username' ),
			'email'            => $app->request()->post( 'email' ),
			'old_password'     => $app->request()->post( 'old_password' ),
			'password'         => $app->request()->post( 'password' ),
			'password_confirm' => $app->request()->post( 'password_confirm' ),
		);

		// prepare the answer
		$response = array(
			'request' => 'profile'
		);

		// Sanitize data
		$user['name']  = filter_var(  $user['name'], FILTER_SANITIZE_STRING );
		$user['email'] = filter_var(  $user['email'], FILTER_SANITIZE_EMAIL );

		$db      = new DbHandler();
		$userUID = $app->getCookie( 'lq_user_id' );

		// Try to update the user details
		if ( $db->updateUserProfile( $userUID, $user['name'], $user['email'] ) ) {
			$response['error'] = false;
		}
		else {
			$response['error'] = true;
			$response['msg']   = 'Impossible to update the user profile.';
			echoRespnse( 401, $response );
			$app->stop();
		}
		// If the user sent a password, reset that as well
		if ( isset( $user['old_password'] ) || isset( $user['password'] ) || isset( $user['password_confirm'] ) ) {
			verify_required_params( array(
				'old_password',
				'password',
				'password_confirm',
			));

			$user['old_password']     = filter_var( $user['old_password'], FILTER_SANITIZE_STRING );
			$user['password']         = filter_var( $user['password'], FILTER_SANITIZE_STRING );
			$user['password_confirm'] = filter_var( $user['password_confirm'], FILTER_SANITIZE_STRING );

			//Make sure the 2 passwords are the same
			if ( $user['password'] !== $user['password_confirm'] ) {
				$response['error']   = true;
				$response['msg']     = 'Password verification failed.';
				echoRespnse( 401, $response );
				$app->stop();
			}

			if ( $db->updateUserPassword( $userUID, $user['email'], $user['old_password'], $user['password'], $user['password_confirm'] ) ) {
				$response['error'] = false;
				$response['msg']   = 'User information saved.';
				echoRespnse( 200, $response );
				$app->stop();
			}
			else {
				$response['error'] = true;
				$response['msg']   = 'Impossible to update the user password.';
				echoRespnse( 401, $response );
				$app->stop();
			}
		}
		else {
			$response['msg'] = 'User information saved.';
			echoRespnse( 200, $response );
			$app->stop();
		}
});

$app->get(
	'/user/:userid/profile/avatar', 'authenticate', function( $userUID ) use ( $app ) {
		sleep(2);
		$response = array(
			'request' => 'get_avatar'
		);
		$user = array();
		$db   = new DbHandler();

		$profile = $db->getUserAvatar( $userUID );

		if ( ! empty( $profile ) ) {
			$response['error']   = false;
			$response['profile'] = $profile;
			$response['msg']     = 'Avatar updated.';
			echoRespnse( 200, $response );

			$app->stop();
		}

		$response['error'] = true;
		$response['msg']   = 'Impossible to retrieve the user avatar.';
		echoRespnse( 400, $response );
		$app->stop();
});

$app->post(
	'/user/:userid/profile/avatar', 'authenticate', function() use ( $app ) {
		// prepare the answer
		$response = array(
			'request' => 'avatar'
		);

		if ( empty( $_FILES['file'] ) ) {
			$response['error'] = true;
			$response['msg']   = 'File information missing or wrong.';
			echoRespnse( 400, $response );
			$app->stop();
		}

		if ( 0 < $_FILES['file']['error'] ) {
			$response['error'] = true;
			$response['msg']   = 'Impossible to read the image file.';
			echoRespnse( 400, $response );
			$app->stop();
		}

		$mime_types = array(
			'image/png'  => '.png',
			'image/gif'  => '.gif',
			'image/jpeg' => '.jpeg',
			'image/png'  => '.png',
			'image/tiff' => '.tif',
		);

		// $file_ext = array_search( $_FILES['file']['type'], $file_types );
		$userUID   = $app->getCookie( 'lq_user_id' );
		$file      = $_FILES['file'];
		$file_type = $_FILES['file']['type'];

		if ( ! array_key_exists( $file_type, $mime_types ) ) {
			$response['error'] = true;
			$response['msg']   = 'File format not valid.';
			echoRespnse( 400, $response );
			$app->stop();
		}

		$file_ext = $mime_types[$file_type];
		$name     = 'avatar-' . $userUID;
		move_uploaded_file( $file['tmp_name'], '../img/avatars/' . $name );

		// Sanitize data
		$db = new DbHandler();

		// Try to update the user details
		$file_path = APP_DIR . 'img/avatars/' . $name;

		if ( $db->updateUserAvatar( $file_path, $userUID ) ) {
			$response['error'] = false;
			$response['profile']['avatar'] = $file_path;
			$response['msg']   = 'Avatar saved successfully.';
			echoRespnse( 200, $response );
			$app->stop();
		}

		$response['error'] = true;
		$response['msg']   = 'Impossible to save the new avatar.';
		echoRespnse( 400, $response );
		$app->stop();
});

$app->get(
	'/user/:userid/collections/', 'authenticate', function( $userid ) use ( $app ) {
		sleep(2);
		$page     = $app->request()->get( 'page' );
		$page     = isset( $page ) ? $page : 0;
		$db       = new DbHandler();
		$response = array(
			'request' => 'collections'
		);

		$collections = $db->getCollections( 0, $userid, $page );

		if ( ! empty( $collections['collections'] ) ) {
			$response['error']       = false;
			$response['collections'] = $collections['collections'];
			$response['loadMore']    = $collections['loadMore'];
			$response['msg']         = 'Collections correctly fetched.';
			echoRespnse( 200, $response );
			$app->stop();
		}

		$response['error']    = true;
		$response['loadMore'] = $collections['loadMore'];
		$response['msg']      = 'Cannot retrieve new collections.';
		echoRespnse( 200, $response );
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
			$response['msg']        = 'Impossible to get the required user information to perform a log out.';

			echoRespnse( 200, $response );
			$app->stop();
		}

		$db->logOut( $user_session );
		$response['error']      = false;
		$response['logged_out'] = true;
		$response['msg']        = 'User logged out.';
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
		$response['error'] = true;
		$response['msg']   = 'Required field(s) ' . substr( $error_fields, 0, -2 ) . ' is missing or empty.';

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

	$user = array();
	$db   = new DbHandler();

	$userSession = $app->getCookie( 'LQ_session' );
	$userUID     = $app->getCookie( 'lq_user_id' );

	$header  = array_change_key_case( $headers, CASE_UPPER );
	$api_key = isset( $header['LQ-API-KEY'] ) ? $header['LQ-API-KEY'] : '';

	if ( isset( $api_key ) ) {
	// 	// get the api key
	// 	// validating api key
		if ( $api_key !== '406cc6ed2c7471d7593461264c0db966' ) {
	// 		// api key is not present in users table
			$response['error'] = true;
			$response['msg']   = 'Access Denied. Invalid Api key.';
			echoRespnse( 401, $response );
			$app->stop();
		}
		if ( ! $db->authenticate( $userUID, $userSession ) ) {
			// authentication failed
			$response['error'] = true;
			$response['msg']   = 'Authentication failed.';
			echoRespnse( 401, $response );
			$app->stop();
		}
	}
	else {
		// api key is missing in header
		$response['error'] = true;
		$response['msg']   = 'Api key is misssing.';
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

$app->run();
?>
