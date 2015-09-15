<?php
ob_start();

// find the correct path, depending on which devbox you are running
$basepath = dirname( dirname( __FILE__ ) );
$path = $basepath . '/src/api/';
define( 'API_PATH', $path );

//***************************************** LOAD COMPOSER

$vendor_path = API_PATH . 'vendor/autoload.php';

if ( file_exists( $vendor_path ) ) {
	require_once $vendor_path;
} else {
	exit( "Could not load " . $vendor_path . ".\n" );
}

require API_PATH . 'v1/some.php';
