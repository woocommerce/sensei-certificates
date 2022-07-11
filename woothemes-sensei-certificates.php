<?php
/**
 * Plugin Name: Sensei LMS Certificates
 * Plugin URI: https://woocommerce.com/products/sensei-certificates/
 * Description: Award your students with a certificate of completion and a sense of accomplishment after finishing a course.
 * Version: 2.3.0
 * Author: Automattic
 * Author URI: https://automattic.com
 * Requires at least: 5.6
 * Tested up to: 6.0
 * Requires PHP: 7.0
 * License: GPLv2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SENSEI_CERTIFICATES_VERSION', '2.3.0' );
define( 'SENSEI_CERTIFICATES_PLUGIN_FILE', __FILE__ );
define( 'SENSEI_CERTIFICATES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once dirname( __FILE__ ) . '/classes/class-woothemes-sensei-certificates-dependency-checker.php';

if ( ! Woothemes_Sensei_Certificates_Dependency_Checker::are_system_dependencies_met() ) {
	return;
}

require_once dirname( __FILE__ ) . '/classes/class-woothemes-sensei-certificates.php';

// Load the plugin after all the other plugins have loaded.
add_action( 'plugins_loaded', array( 'WooThemes_Sensei_Certificates', 'init' ), 5 );

WooThemes_Sensei_Certificates::instance();
