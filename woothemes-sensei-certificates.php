<?php
/**
 * Plugin Name: Sensei LMS Certificates
 * Plugin URI: https://woocommerce.com/products/sensei-certificates/
 * Description: Award your students with a certificate of completion and a sense of accomplishment after finishing a course.
 * Version: 2.6.0
 * Author: Automattic
 * Author URI: https://automattic.com
 * Requires at least: 6.9
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * License: GPLv2 or later
 *
 * @package Sensei_Certificates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SENSEI_CERTIFICATES_VERSION', '2.6.0' );
define( 'SENSEI_CERTIFICATES_PLUGIN_FILE', __FILE__ );
define( 'SENSEI_CERTIFICATES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once __DIR__ . '/classes/class-woothemes-sensei-certificates-dependency-checker.php';

if ( ! Woothemes_Sensei_Certificates_Dependency_Checker::are_system_dependencies_met() ) {
	return;
}

require_once __DIR__ . '/classes/class-woothemes-sensei-certificates.php';

// Load the plugin after all the other plugins have loaded.
add_action( 'plugins_loaded', array( 'WooThemes_Sensei_Certificates', 'init' ), 5 );

WooThemes_Sensei_Certificates::instance();
