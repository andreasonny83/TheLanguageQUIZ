<?php
/**
 * Database login details
 */
define( 'DB_HOST', 'localhost' );

define( 'DB_NAME', 'language_quiz' ); //Dev
define( 'DB_USERNAME', 'root' ); //Dev
define( 'DB_PASSWORD', 'root' ); //Dev

define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATION', 'utf8_unicode_ci' );

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
// define( 'PATH', 'http://localhost:8888/language-quiz/dist' );
?>
