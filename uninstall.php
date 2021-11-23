<?php
/**
 * Sensei Uninstall
 *
 * Uninstalls the plugin and associated data.
 *
 * @package Extension
 * @author Automattic
 * @since 2.3.0
 *
 * @var string $plugin Plugin name being passed to `uninstall_plugin()`.
 */

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

if ( class_exists( 'WooThemes_Sensei_Certificates' ) ) {
	// Another instance of Sensei Certificates is installed and activated on the current site or network.
	return;
}

require dirname( __FILE__ ) . '/woothemes-sensei-certificates.php';
require dirname( __FILE__ ) . '/classes/class-sensei-certificate-data-cleaner.php';

// Cleanup all data.
if ( ! is_multisite() ) {
	if ( Sensei_Certificate_Data_Cleaner::should_do_cleanup() ) {
		Sensei_Certificate_Data_Cleaner::cleanup_all();
	}
} else {
	global $wpdb;

	$blog_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_blog_id = get_current_blog_id();

	foreach ( $blog_ids as $_blog_id ) {
		switch_to_blog( $_blog_id );

		if ( Sensei_Certificate_Data_Cleaner::should_do_cleanup() ) {
			Sensei_Certificate_Data_Cleaner::cleanup_all();
		}
	}

	switch_to_blog( $original_blog_id );
}
