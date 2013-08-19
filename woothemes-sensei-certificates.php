<?php
/**
 * Plugin Name: Sensei Certificates
 * Plugin URI: http://www.woothemes.com/products/sensei-certifcates
 * Description: Add certificates support to Sensei
 * Version: 1.0.0
 * Author: WooThemes
 * Author URI: http://www.woothemes.com
 * License: GPLv3
 */

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '112372c44b002fea2640bd6bfafbca27', '18740' );

/**
 * Localisation
 **/
load_plugin_textdomain( 'woothemes-sensei-certificates', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );


/**
 * init_sensei_certificates function.
 *
 * @access public
 * @since  1.0.0
 * @return void
 */
function init_sensei_certificates() {

	if ( is_sensei_active() ) {
		require_once( 'classes/class-woothemes-sensei-certificates.php' );
		$GLOBALS['woothemes_sensei_certificates'] = new WooThemes_Sensei_Certificates( __FILE__ );
	}

} // End init_sensei_extension()
add_action( 'plugins_loaded', 'init_sensei_certificates', 0 );


/**
 * activate_sensei_certificates function
 * @since  1.0.0
 * @return void
 */
function activate_sensei_certificates() {

} // End activate_sensei_certificates()
register_activation_hook( __FILE__, 'activate_sensei_certificates' );

/**
 * Functions used by plugins
 */
if ( ! class_exists( 'WooThemes_Sensei_Dependencies' ) )
  require_once 'woo-includes/class-woothemes-sensei-dependencies.php';

/**
 * Sensei Detection
 */
if ( ! function_exists( 'is_sensei_active' ) ) {
  function is_sensei_active() {
    return WooThemes_Sensei_Dependencies::sensei_active_check();
  }
}