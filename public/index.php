<?php
require __DIR__ . '/loader.php';
require __DIR__ . '/EngrUpdateServer.php';

if ( ! defined( 'WP_UPDATE_SECRET_ID' ) ) {
	define( 'WP_UPDATE_SECRET_ID', '998' );
}
if ( ! defined( 'WP_UPDATE_SECRET_STRING' ) ) {
	define( 'WP_UPDATE_SECRET_STRING', '123openup' );
}

if ( ! defined( 'GLOB_BRACE' ) ) {
	define( 'GLOB_BRACE', 0 );
}

$server = new Engr_UpdateServer( null, WP_UPDATE_ROOT, base64_encode( WP_UPDATE_SECRET_ID . ':' . WP_UPDATE_SECRET_STRING ) );
$server->handleRequest();
