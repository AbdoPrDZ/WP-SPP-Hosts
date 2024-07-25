<?php

/* START ******************************** Setup Hosts Access Feature *********************************/

function create_host_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'hosts'; // Table name with WordPress prefix

	// SQL to create the table
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE `$table_name` (
		`id` mediumint(9) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`host` varchar(255) NOT NULL,
		`description` text NOT NULL,
		PRIMARY KEY (`id`)
	) $charset_collate;";

	// Include the WordPress upgrade file
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_host_table');

function hosts_menu() {
	add_menu_page(
		'Manage Hosts',          // Page title
		'Hosts',                 // Menu title
		'manage_options',        // Capability
		'manage-hosts',          // Menu slug
		'hosts_page_content',    // Callback function
		'dashicons-networking',  // Dashicon class
		6                        // Position
	);
}
add_action('admin_menu', 'hosts_menu');

function hosts_page_content() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'hosts';

	// Handle form submission for adding/editing/deleting hosts
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'add':
				$name = sanitize_text_field($_POST['name']);
				$host = sanitize_text_field($_POST['host']);
				$description = sanitize_textarea_field($_POST['description']);
				$status = sanitize_textarea_field($_POST['status']);
				$wpdb->insert($table_name, compact('name', 'host', 'description', 'status'));
				break;

			case 'edit':
				$id = intval($_POST['id']);
				$name = sanitize_text_field($_POST['name']);
				$host = sanitize_text_field($_POST['host']);
				$description = sanitize_textarea_field($_POST['description']);
				$status = sanitize_textarea_field($_POST['status']);
				$wpdb->update($table_name, compact('name', 'host', 'description', 'status'), array('id' => $id));
				break;

			case 'delete':
				$id = intval($_POST['id']);
				$wpdb->delete($table_name, array('id' => $id));
				break;
		}
	}

	// Display the form and the list of hosts
	include 'hosts-admin-page.php'; // Create this file for the admin page content
}

function create_tokens_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'tokens'; // Table name with WordPress prefix
  $hosts_table = $wpdb->prefix . 'hosts'; // Assuming the hosts table is named wp_hosts

	// SQL to create the table
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE `$table_name` (
		`id` mediumint(9) NOT NULL AUTO_INCREMENT,
		`token` varchar(255) NOT NULL,
		`host_id` mediumint(9) NOT NULL,
		`user_id` mediumint(9) NOT NULL,
		`expired_at` datetime NOT NULL,
		`status` enum('active', 'expired', 'canceled') NOT NULL DEFAULT('active'),
		PRIMARY KEY (`id`),
    FOREIGN KEY (`host_id`) REFERENCES `$hosts_table`(`id`) ON DELETE CASCADE
	) $charset_collate;";

	// Include the WordPress upgrade file
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_tokens_table');

function tokens_menu() {
	add_submenu_page(
		'manage-hosts',           // Parent slug
		'Manage Tokens',          // Page title
		'Tokens',                 // Menu title
		'manage_options',         // Capability
		'manage-tokens',          // Menu slug
		'tokens_page_content'     // Callback function
	);
}
add_action('admin_menu', 'tokens_menu');

function tokens_page_content() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'tokens';

	// Handle form submission for adding/editing/deleting tokens
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'add':
				$host_id = intval($_POST['host_id']);
				$user_id = intval($_POST['user_id']);
				$token = md5("$host_id-$user_id-${time()}");
				$expired_at = sanitize_text_field($_POST['expired_at']);
				$wpdb->insert($table_name, compact('token', 'host_id', 'user_id', 'expired_at'));
				break;

			case 'edit':
				$id = intval($_POST['id']);
				$host_id = intval($_POST['host_id']);
				$user_id = intval($_POST['user_id']);
				$expired_at = sanitize_text_field($_POST['expired_at']);
				$wpdb->update($table_name, compact('host_id', 'user_id', 'expired_at'), array('id' => $id));
				break;

			case 'delete':
				$id = intval($_POST['id']);
				$wpdb->delete($table_name, array('id' => $id));
				break;
		}
	}

	// Display the form and the list of tokens
	include 'tokens-admin-page.php'; // Create this file for the admin page content
}

require_once ABSPATH . '/vendor/miladrahimi/php-jwt/src/Cryptography/Algorithms/Hmac/HS256.php';
require_once ABSPATH . '/vendor/miladrahimi/php-jwt/src/Cryptography/Keys/HmacKey.php';
require_once ABSPATH . '/vendor/miladrahimi/php-jwt/src/Generator.php';
require_once ABSPATH . '/vendor/miladrahimi/php-jwt/src/Parser.php';

use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Generator;
use MiladRahimi\Jwt\Parser;

add_action('rest_api_init', function () {
	register_rest_route('custom/v2', '/generate-jwt-token', array(
		'methods' => ['POST', 'GET'],
		'callback' => 'generate_jwt_token',
		'permission_callback' => '__return_true',
	));
});

function generate_jwt_token(WP_REST_Request $request) {
	global $wpdb;

	$token_id = sanitize_text_field($request->get_param('token_id'));

	if (empty($token_id)) {
		return rest_ensure_response([
			'success' => false,
			'message' => 'Token ID is required.'
		]);
	}

	$tokens_table = $wpdb->prefix . 'tokens';
	$hosts_table = $wpdb->prefix . 'hosts';

  $query = "SELECT *, `hosts`.`host` AS `host` FROM `$tokens_table` `tokens` LEFT JOIN `$hosts_table` `hosts` ON `tokens`.`host_id` = `hosts`.`id` WHERE `tokens`.`id` = %d";

	$token = $wpdb->get_row($wpdb->prepare($query, $token_id));

	if (empty($token)) {
		return rest_ensure_response([
			'success' => false,
			'message' => 'Token not found.',
		]);
	}

  try {
    $payload = [
      'host' => $token->host,
      'exp' => $token->expired_at,
    ];

    // Use HS256 to generate and parse JWTs
    $key = new HmacKey(JWT_AUTH_KEY);
    $signer = new HS256($key);

    // Generate a JWT
    $generator = new Generator($signer);
    $jwt = $generator->generate($payload);

    return rest_ensure_response([
      'success' => true,
      'token' => $jwt
    ]);
  } catch (\Throwable $th) {
		return rest_ensure_response([
			'success' => false,
			'message' => $th->getMessage(),
		]);
  }
}

function expire_tokens() {
  global $wpdb;

  $table_name = $wpdb->prefix . 'tokens';

  $query = "UPDATE `$table_name` SET `status` = 'expired' WHERE `status` = 'active' AND `expired_at` > CURDATE()";

  $wpdb->query($query);
}

function custom_cron_schedules($schedules) {
  $schedules['hourly'] = array(
    'interval' => 60 * 30,
    'display'  => __('Every 30 min')
  );
  return $schedules;
}
add_filter('cron_schedules', 'custom_cron_schedules');

function schedule_expire_tokens_event() {
  if (!wp_next_scheduled('expire_tokens_event')) {
    wp_schedule_event(time(), 'hourly', 'expire_tokens_event');
  }
}
add_action('wp', 'schedule_expire_tokens_event');

add_action('expire_tokens_event', 'expire_tokens');

function unschedule_expire_tokens_event() {
  $timestamp = wp_next_scheduled('expire_tokens_event');
  wp_unschedule_event($timestamp, 'expire_tokens_event');
}
register_deactivation_hook(__FILE__, 'unschedule_expire_tokens_event');

/* END ******************************** Setup Hosts Access Feature *********************************/
