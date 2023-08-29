<?php

// don't call directly.
if (!defined('WP_UPDATE_ROOT_PATH')){
	die();
}

if ( file_exists( WP_UPDATE_ROOT_PATH . '/engr-wp-config.php' ) ) {
	require_once WP_UPDATE_ROOT_PATH . '/engr-wp-config.php';
}

if ( ! class_exists( 'Engr_UpdateServer' ) ) {
	require_once WP_UPDATE_ROOT_PATH . '/includes/Iastate/class-engr-update-server.php';
}

if ( ! defined( 'WP_UPDATE_SECRET_ID' ) ) {
	define( 'WP_UPDATE_SECRET_ID', '998' );
}
if ( ! defined( 'WP_UPDATE_SECRET_STRING' ) ) {
	define( 'WP_UPDATE_SECRET_STRING', '123openup' );
}

if ( ! defined( 'WP_UPDATE_HASH_ALGO' ) ) {
	define( 'WP_UPDATE_HASH_ALGO', 'tiger128,3' );
}

if ( ! defined( 'WP_UPDATE_PACKAGE_PATH' ) ) {
	define( 'WP_UPDATE_PACKAGE_PATH', WP_UPDATE_ROOT_PATH );
}

if ( ! defined( 'WP_UPDATE_HOME_URL' ) ) {
	define( 'WP_UPDATE_HOME_URL', null );
}

if ( ! defined( 'GLOB_BRACE' ) ) {
	define( 'GLOB_BRACE', 0 );
}
