<?php
/*
Plugin Name:  WP SPP Hosts
Plugin URI:   https://github.com/AbdoPrDZ/wp-spp-hosts
Description:  A simple plugin to manage hosts and tokens for WordPress Smart proxy pass.
Version:      1.0
Author:       AbdoPrDZ
Author URI:   https://github.com/AbdoPrDZ
License:      MIT
License URI:  https://opensource.org/licenses/MIT
Text Domain:  wp-spp-hosts
Domain Path:  /languages
*/

if (!defined('WP_SPP_HOSTS_DIR')) {
  define('WP_SPP_HOSTS_DIR', __DIR__ . '/');
}

// Include the Composer autoloader
require_once WP_SPP_HOSTS_DIR . 'vendor/autoload.php';

// Verify JWT Auth key

if (!defined('WP_SPP_HOSTS_DIR')) {
  die("undefined 'SPP_JWT_AUTH_KEY' constant value");
}

function create_hosts_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'hosts'; // Table name with WordPress prefix

	// SQL to create the table
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE `$table_name` (
		`id` MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
		`name` VARCHAR(255) NOT NULL,
		`host` VARCHAR(255) NOT NULL,
    'headers' TEXT NOT NULL,
		`cookie` VARCHAR(255) NOT NULL,
		`description` TEXT NOT NULL,
		PRIMARY KEY (`id`)
	) $charset_collate;";

	// Include the WordPress upgrade file
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
// register_activation_hook(__FILE__, 'create_hosts_table');

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

function validate_fields($fields, &$errors) {
  $errors = [];
  $valid = true;
  foreach ($fields as $field)
    if (empty($_POST[$field])) {
      $errors[$field] = "Field $field is required";
      $valid = false;
    }
  return $valid;
}

function hosts_page_content() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'hosts';

  // verify if hosts table exists
  $result = $wpdb->get_results("SHOW TABLES LIKE '$table_name'");
  if ( empty($result) ) {
    // create hosts table if not exists
    create_hosts_table();
  }

	// Handle form submission for adding/editing/deleting hosts
	if (isset($_POST['action'])) {
    $errors = [];
    switch ($_POST['action']) {
			case 'add':
        if (!validate_fields(['name', 'host', 'description'], $errors)) break;
				$name = sanitize_text_field($_POST['name']);
				$host = sanitize_text_field($_POST['host']);
				$headers = sanitize_text_field($_POST['headers']);
        if (!empty($headers) && !is_object(json_decode($headers))) {
          $errors['headers'] = "Invalid headers json";
          print_r($errors);
          break;
        }
				$cookie = sanitize_text_field($_POST['cookie']);
				$description = sanitize_textarea_field($_POST['description']);
				$wpdb->insert($table_name, compact('name', 'host', 'headers', 'cookie', 'description'));
				break;

			case 'edit':
        if (!validate_fields(['name', 'host', 'description'], $errors)) break;
				$id = intval($_POST['id']);
				$name = sanitize_text_field($_POST['name']);
				$host = sanitize_text_field($_POST['host']);
				$headers = sanitize_text_field($_POST['headers']);
        if (!empty($headers) && !is_object(json_decode($headers))) {
          $errors['headers'] = "Invalid headers json";
          print_r($errors);
          break;
        }
				$cookie = sanitize_text_field($_POST['cookie']);
				$description = sanitize_textarea_field($_POST['description']);
				$wpdb->update($table_name, compact('name', 'host', 'headers', 'cookie', 'description'), ['id' => $id]);
				break;

			case 'delete':
				$id = intval($_POST['id']);
				$wpdb->delete($table_name, ['id' => $id]);
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
		`id` MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
		`token` VARCHAR(255) NOT NULL,
		`host_id` MEDIUMINT(9) NOT NULL,
		`user_id` MEDIUMINT(9) NOT NULL,
		`expired_at` DATETIME NOT NULL,
		`status` ENUM('active', 'expired', 'canceled') NOT NULL DEFAULT 'active',
		PRIMARY KEY (`id`),
    FOREIGN KEY (`host_id`) REFERENCES `$hosts_table`(`id`) ON DELETE CASCADE
	) $charset_collate;";

	// Include the WordPress upgrade file
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);
}
// register_activation_hook(__FILE__, 'create_tokens_table');

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

  // verify if tokens table exists
  $result = $wpdb->get_results("SHOW TABLES LIKE '$table_name'");
  if ( empty($result) ) {
    // create tokens table if not exists
    create_tokens_table();
  }

	// Handle form submission for adding/editing/deleting tokens
	if (isset($_POST['action'])) {
    $errors = [];
		switch ($_POST['action']) {
			case 'add':
        if (!validate_fields(['host_id', 'user_id', 'status', 'expired_at'], $errors)) break;
				$host_id = intval($_POST['host_id']);
				$user_id = intval($_POST['user_id']);
				$token = md5("$host_id-$user_id-" . time());
				$status = sanitize_textarea_field($_POST['status']);
				$expired_at = sanitize_text_field($_POST['expired_at']);
				$wpdb->insert($table_name, compact('token', 'host_id', 'user_id', 'expired_at', 'status'));
				break;

			case 'edit':
        if (!validate_fields(['id', 'host_id', 'user_id', 'status', 'expired_at'], $errors)) break;
				$id = intval($_POST['id']);
				$host_id = intval($_POST['host_id']);
				$user_id = intval($_POST['user_id']);
				$status = sanitize_textarea_field($_POST['status']);
				$expired_at = sanitize_text_field($_POST['expired_at']);
				$wpdb->update($table_name, compact('host_id', 'user_id', 'expired_at', 'status'), ['id' => $id]);
				break;

			case 'delete':
				$id = intval($_POST['id']);
				$wpdb->delete($table_name, ['id' => $id]);
				break;
		}
	}

	// Display the form and the list of tokens
	include 'tokens-admin-page.php'; // Create this file for the admin page content
}

require_once WP_SPP_HOSTS_DIR . 'vendor/miladrahimi/php-jwt/src/Cryptography/Algorithms/Hmac/HS256.php';
require_once WP_SPP_HOSTS_DIR . 'vendor/miladrahimi/php-jwt/src/Cryptography/Keys/HmacKey.php';
require_once WP_SPP_HOSTS_DIR . 'vendor/miladrahimi/php-jwt/src/Generator.php';
require_once WP_SPP_HOSTS_DIR . 'vendor/miladrahimi/php-jwt/src/Parser.php';

use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Generator;
use MiladRahimi\Jwt\Parser;

function generate_jwt($payload) {
  // Use HS256 to generate and parse JWTs
  $key = new HmacKey(SPP_JWT_AUTH_KEY);
  $signer = new HS256($key);

  // Generate a JWT
  $generator = new Generator($signer);
  return $generator->generate($payload);
}

add_action('rest_api_init', function () {
	register_rest_route('custom/v2', '/generate-jwt-token', [
		'methods' => ['GET'],
		'callback' => 'generate_jwt_token',
    'permission_callback' => function () {
      return is_user_logged_in();
    }
	]);
});

function generate_jwt_token(WP_REST_Request $request) {
  $user = wp_get_current_user();

  return rest_ensure_response([
    'success' => true,
    'token' => generate_jwt([
      'uid' => $user->ID,
      'uip' => $_SERVER['REMOTE_ADDR'],
      'time' => time(),
    ]),
  ]);
}

add_action('rest_api_init', function () {
	register_rest_route('custom/v2', '/generate-host-jwt-token', [
		'methods' => ['POST', 'GET'],
		'callback' => 'generate_host_jwt_token',
    'permission_callback' => function () {
      return is_user_logged_in();
    }
	]);
});

function generate_host_jwt_token(WP_REST_Request $request) {
	global $wpdb;

	$token = sanitize_text_field($request->get_param('token'));

	if (empty($token)) {
		return rest_ensure_response([
			'success' => false,
			'message' => 'Token ID is required.'
		]);
	}

	$tokens_table = $wpdb->prefix . 'tokens';
	$hosts_table = $wpdb->prefix . 'hosts';

  $query = "SELECT *, `hosts`.`host` AS `host` FROM `$tokens_table` `tokens` LEFT JOIN `$hosts_table` `hosts` ON `tokens`.`host_id` = `hosts`.`id` WHERE `tokens`.`token` = %d";

	$row = $wpdb->get_row($wpdb->prepare($query, $token));

	if (empty($row)) {
		return rest_ensure_response([
			'success' => false,
			'message' => 'Token not found.',
		]);
	}

  try {
    $payload = [
      'host' => $row->host,
      'cookie' => $row->cookie,
      'token' => $row->token,
      'time' => time(),
      'exp' => strtotime($row->expired_at),
    ];

    return rest_ensure_response([
      'success' => true,
      'token' => generate_jwt($payload),
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

  $query = "UPDATE `$table_name` SET `status` = 'expired' WHERE `status` = 'active' AND `expired_at` <= NOW()";

  $wpdb->query($query);
}

function custom_cron_schedules($schedules) {
  $schedules['hourly'] = [
    'interval' => 60 * 30,
    'display'  => __('Every 30 min')
  ];
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

function my_plugin_register_page_templates($templates) {
  $templates['page-user-tokens.php'] = 'User Tokens Page';
  $templates['page-user-tokens-jwt.php'] = 'User Tokens Page Using JWT';
  $templates['page-user-tokens-socket.php'] = 'User Tokens Page Using Socket IO';
  return $templates;
}
add_filter('theme_page_templates', 'my_plugin_register_page_templates');

function my_plugin_load_page_template($template) {
  global $post;

  if ($post) {
      $custom_template = get_post_meta($post->ID, '_wp_page_template', true);

      if (in_array($custom_template, ['page-user-tokens-jwt.php', 'page-user-tokens-jwt.php', 'page-user-tokens-socket.php'])) {
          $plugin_template = WP_SPP_HOSTS_DIR . "templates/$custom_template";
          if (file_exists($plugin_template)) {
              return $plugin_template;
          }
      }
  }

  return $template;
}
add_filter('template_include', 'my_plugin_load_page_template');

function enqueue_socket_io_script() {
  wp_enqueue_script('socket-io', 'https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.7.5/socket.io.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_socket_io_script');
