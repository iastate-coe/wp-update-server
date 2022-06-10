<?php
define( 'WP_UPDATE_ROOT', dirname( __FILE__, 2) );

require_once WP_UPDATE_ROOT . '/includes/Wpup/Package.php';
require_once WP_UPDATE_ROOT . '/includes/Wpup/ZipMetadataParser.php';
require_once WP_UPDATE_ROOT . '/includes/Wpup/InvalidPackageException.php';
require_once WP_UPDATE_ROOT . '/includes/Wpup/Request.php';
require_once WP_UPDATE_ROOT . '/includes/Wpup/Headers.php';
require_once WP_UPDATE_ROOT . '/includes/Wpup/Cache.php';
require_once WP_UPDATE_ROOT . '/includes/Wpup/FileCache.php';
require_once WP_UPDATE_ROOT . '/includes/Wpup/UpdateServer.php';

if ( ! class_exists( 'WshWordPressPackageParser' ) ) {
	require_once WP_UPDATE_ROOT . '/includes/extension-meta/extension-meta.php';
}
