<?php
require __DIR__ . '/loader.php';
$server = new Engr_UpdateServer( null, WP_UPDATE_ROOT_PATH, base64_encode( WP_UPDATE_SECRET_ID . ':' . WP_UPDATE_SECRET_STRING ) );
$server->handleRequest();
