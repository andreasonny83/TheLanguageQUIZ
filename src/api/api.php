<?php
require_once dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php';
use \Slim\Slim;
use \Config\Database\DbHandler;

// For debug only
ini_set( 'display_errors', 1 );
error_reporting( E_ALL );

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->post(
	'/test', function() {
		$app = \Slim\Slim::getInstance();
		$response = '[{"name":"sdas"}, {"name":"asdds"}]';

		$app->contentType( 'application/json' );

		echo( $response );
		exit;
	}
);

$app->get(
	'/status', function() {
		$response['status'] = 'ok';
		echoRespnse( 200, $response );
		exit;
	}
);

$app->post(
	'/login', function() use ( $app ) {
		// check for required params
		verify_required_params( array( 'email', 'password' ) );

		// reading post params
		$email    = $app->request()->post( 'email' );
		$password = $app->request()->post( 'password' );
		$response = array();
		$db       = new DbHandler();

		// check for correct email and password
		if ( $db->checkLogin( $email, $password ) ) {
			$response['error']   = false;
			$response['message'] = 'login successed';
			$response['user']    = $db->getUserUIDByEmail( $email );
			echoRespnse( 200, $response );
			$app->stop();
		}
	}
);

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

		// print_r($collection);

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

/**
 * Verifying required params posted or not
 */
function verify_required_params( $required_fields ) {
	$error          = false;
	$error_fields   = '';
	$request_params = array();
	$request_params = $_REQUEST;

	// Handling PUT request params
	if ( $_SERVER['REQUEST_METHOD'] == 'PUT' ) {
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
		$response = array();
		$app      = \Slim\Slim::getInstance();
		$response['error']   = true;
		$response['message'] = 'Required field(s) ' . substr( $error_fields, 0, -2 ) . ' is missing or empty';
		echoRespnse( 400, $response );
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
		echoRespnse( 400, $response );
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
