<?php
$API = array(
		'Mashape' => 'X-Mashape-Authorization: UJwV8Yw9nzmshuNjBFVutMGNPJk3p1B3nrljsnYKe3dHcBi5mW',
);
/**
 * [post description]
 * We use this script to send secure API request using JavaScript
 * @param  [type] $url  [description]
 * @param  [type] $data [description]
 * @return [type]       [description]
 */
function get( $url, $data, $apis ) {
	global $MashapeAPI;

	$curl   = curl_init();
	$url   .= '?' . $data;
	$header = array(
			'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'],
			'Content-Type: application/x-www-form-urlencoded',
		);

	// if we are requesting to use some API key, inject them inside the request header
	if ( isset ( $apis ) ) {
		foreach ( $apis as $api ) {
			$header[] = $api;
		}
	}

	curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
	curl_setopt( $curl, CURLOPT_URL, $url );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );

	$response = curl_exec( $curl );
	curl_close( $curl );

	return $response;
}

function post( $url, $data, $apis ) {
	$curl   = curl_init();
	$header = array(
			'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'],
			'Content-Type: application/x-www-form-urlencoded',
		);

	// if we are requesting to use some API key, inject them inside the request header
	if ( isset ( $apis ) ) {
		foreach ( $apis as $api ) {
			$header[] = $api;
		}
	}

	curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
	curl_setopt( $curl, CURLOPT_URL, $url );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt( $curl, CURLOPT_POST, TRUE );
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );

	$response = curl_exec( $curl );
	curl_close( $curl );

	return $response;
}

$url     = $_POST['url'];
$request = $_POST['request'];
$data    = $_POST['data'];
$apis    = array();

if ( isset ( $_POST['api'] ) ) {
	$post_apis = explode( ',', $_POST['api'] );

	foreach ( $post_apis as $api ) {
		$api = trim( $api, ' ' );

		// if the API key exists
		if ( isset ( $API[$api] ) ) {
			$apis[] = $API[$api];
		}
	}
}

if ( $request === 'POST' ) {
	echo ( post( $url, $data, $apis ) );
}
else if ( $request === 'GET' ) {
	echo ( get( $url, $data, $apis ) );
}

?>