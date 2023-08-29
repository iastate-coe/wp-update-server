<?php

// Secret ID and String that generates the key to download packages.
define( 'WP_UPDATE_SECRET_ID', '999' );
define( 'WP_UPDATE_SECRET_STRING', '1234openup' );

// The directory where package and assets live. No trailing slash.
define( 'WP_UPDATE_PACKAGE_PATH', dirname(__FILE__) );

// Force website url. Default is null and will attempt to guess.
define( 'WP_UPDATE_HOME_URL', null );
