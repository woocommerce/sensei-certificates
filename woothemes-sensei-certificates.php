<?php
/**
 * Plugin Name: Sensei LMS Certificates
 * Plugin URI: https://woocommerce.com/products/sensei-certificates/
 * Description: Award your students with a certificate of completion and a sense of accomplishment after finishing a course.
 * Version: 2.0.2
 * Author: Automattic
 * Author URI: https://automattic.com
 * Requires at least: 4.9
 * Requires PHP: 5.6
 * Tested up to: 5.3
 * Woo: 247548:625ee5fe1bf36b4c741ab07507ba2ffd
 * License: GPLv2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SENSEI_CERTIFICATES_VERSION', '2.0.2' );
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
