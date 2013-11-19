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
		require_once( 'classes/class-woothemes-sensei-certificate-templates.php' );
		$GLOBALS['woothemes_sensei_certificate_templates'] = new WooThemes_Sensei_Certificate_Templates( __FILE__ );
	}

} // End init_sensei_extension()
add_action( 'plugins_loaded', 'init_sensei_certificates', 0 );

/**
 * install function to generate cert hashes
 * @since  1.0.0
 * @return string
 */
function sensei_certificates_install() {
	global $woothemes_sensei;
	$users = get_users();
	foreach ( $users as $user_key => $user_item ) {
		$course_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'user_id' => $user_item->ID, 'type' => 'sensei_course_start' ) );
		$posts_array = array();
		if ( 0 < intval( count( $course_ids ) ) ) {
			$posts_array = $woothemes_sensei->post_types->course->course_query( -1, 'usercourses', $course_ids );
		} // End If Statement
		foreach ( $posts_array as $course_item ) {
			$course_end_date = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_item->ID, 'user_id' => $user_item->ID, 'type' => 'sensei_course_end', 'field' => 'comment_date' ) );
			if ( isset( $course_end_date ) && '' != $course_end_date ) {
				$args = array(
					'post_type' => 'certificate',
					'author' => $user_item->ID,
					'meta_key' => 'course_id',
					'meta_value' => $course_item->ID
				);
				$query = new WP_Query( $args );
				if ( ! $query->have_posts() ) {
					// Insert custom post type
					$cert_args = array(
						'post_author' => intval( $user_item->ID ),
						'post_title' => esc_html( substr( md5( $course_item->ID . $user_item->ID ), -8 ) ),
						'post_name' => esc_html( substr( md5( $course_item->ID . $user_item->ID ), -8 ) ),
						'post_type' => 'certificate',
						'post_status'   => 'publish'
					);
					$post_id = wp_insert_post( $cert_args, $wp_error = false );
					if ( ! is_wp_error( $post_id ) ) {
						add_post_meta( $post_id, 'course_id', intval( $course_item->ID ) );
						add_post_meta( $post_id, 'learner_id', intval( $user_item->ID ) );
						add_post_meta( $post_id, 'certificate_hash',esc_html( substr( md5( $course_item->ID . $user_item->ID ), -8 ) ) );
					}
				}
				wp_reset_query();
			}
		}
	}
} // End sensei_certificates_install()
register_activation_hook( __FILE__, 'sensei_certificates_install' );

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