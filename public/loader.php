<?php
define( 'WP_UPDATE_ROOT_PATH', dirname( __FILE__, 2 ) );
define( 'WP_UPDATE_PUBLIC_PATH', dirname( __FILE__, 1 ) );

require_once WP_UPDATE_ROOT_PATH . '/includes/Wpup/Package.php';
require_once WP_UPDATE_ROOT_PATH . '/includes/Wpup/ZipMetadataParser.php';
require_once WP_UPDATE_ROOT_PATH . '/includes/Wpup/InvalidPackageException.php';
require_once WP_UPDATE_ROOT_PATH . '/includes/Wpup/Request.php';
require_once WP_UPDATE_ROOT_PATH . '/includes/Wpup/Headers.php';
require_once WP_UPDATE_ROOT_PATH . '/includes/Wpup/Cache.php';
require_once WP_UPDATE_ROOT_PATH . '/includes/Wpup/FileCache.php';
require_once WP_UPDATE_ROOT_PATH . '/includes/Wpup/UpdateServer.php';

if ( ! class_exists( 'WshWordPressPackageParser' ) ) {
	require_once WP_UPDATE_ROOT_PATH . '/includes/extension-meta/extension-meta.php';
}

if ( file_exists( WP_UPDATE_PUBLIC_PATH . '/engr-wp-config.php' ) ) {
	require_once WP_UPDATE_PUBLIC_PATH . '/engr-wp-config.php';
}

if ( ! class_exists( 'Engr_UpdateServer' ) ) {
	require_once WP_UPDATE_PUBLIC_PATH . '/EngrUpdateServer.php';
}

if ( ! defined( 'WP_UPDATE_SECRET_ID' ) ) {
	define( 'WP_UPDATE_SECRET_ID', '998' );
}
if ( ! defined( 'WP_UPDATE_SECRET_STRING' ) ) {
	define( 'WP_UPDATE_SECRET_STRING', '123openup' );
}

if ( ! defined( 'GLOB_BRACE' ) ) {
	define( 'GLOB_BRACE', 0 );
}
