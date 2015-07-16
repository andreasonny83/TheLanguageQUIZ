<?php
/**
 * Database login details
 */
define( 'DB_HOST', 'localhost' );
define( 'DB_USERNAME', 'root' ); // Dev
// define( 'DB_USERNAME', 'languageQUIZ' ); // sonnywebdesign.net
define( 'DB_PASSWORD', 'root' ); // Dev
// define( 'DB_PASSWORD', 'Nq8Gc%yrvvIl' ); // sonnywebdesign.net
define( 'DB_NAME', 'jerry_lee' ); // Dev
// define( 'DB_NAME', 'language_quiz' ); // sonnywebdesign.net

define( 'SECURE', FALSE ); // FOR DEVELOPMENT ONLY

define( 'CAN_REGISTER', 'any' );
define( 'DEFAULT_ROLE', 'member' );

/**
 * API Key for using the Mashape service
 * https://www.mashape.com
 */
define( 'MASHAPE_API_DEMO', 'UJwV8Yw9nzmshuNjBFVutMGNPJk3p1B3nrljsnYKe3dHcBi5mW' );
define( 'MASHAPE_API_PROD', 'wJtEBqFzlgmsh4ZPUdP6Y7PKLDMdp1N1qixjsnOshPPBiTsNFg' );

/**
 * The project url
 * Set with your own adress to make everithing working correctly
 * @var array
 */
define( 'PATH', 'http://localhost:8888/language-quiz/dist' );
define( 'API_PATH', 'http://localhost:8888/language-quiz/dist/api' );
// define( 'PATH', 'http://sonnywebdesign.net/languagequiz' ); // sonnywebdesign.net
// define( 'API_PATH', 'http://sonnywebdesign.net/languagequiz/api' ); // sonnywebdesign.net

// Make sure to display any PHP error and warning
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( -1 );
?>
