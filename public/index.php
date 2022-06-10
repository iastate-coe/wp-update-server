<?php
require __DIR__ . '/loader.php';
$server = new Engr_UpdateServer( WP_UPDATE_HOME_URL, WP_UPDATE_PACKAGE_PATH, base64_encode( WP_UPDATE_SECRET_ID . ':' . WP_UPDATE_SECRET_STRING ) );
$server->handleRequest();
