<?php

if ( ! defined( 'WP_SPP_HOSTS_DIR' ) ) {
  define( 'WP_SPP_HOSTS_DIR', __DIR__ . '/' );
}

// Include the Composer autoloader
require_once WP_SPP_HOSTS_DIR . 'vendor/autoload.php';
// JWT Auth key
define( 'JWT_AUTH_KEY',     'FAA200F027797FD16C7A134D150F2E60C4A0C68FAAF65B03A3B892DC9DCAE0C6' );
