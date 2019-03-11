<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Certificates Extension Dependencies Check
 *
 * @since 2.0.0
 */
class Woothemes_Sensei_Certificates_Dependency_Checker {
	const MINIMUM_PHP_VERSION    = '5.6';
	const MINIMUM_SENSEI_VERSION = '1.11.0';

	/**
	 * The list of active plugins.
	 *
	 * @var array
	 */
	private static $active_plugins;

	/**
	 * Get active plugins.
	 *
	 * @return string[]
	 */
	private static function get_active_plugins() {
		if ( ! isset( self::$active_plugins ) ) {
			self::$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
		}
		return self::$active_plugins;
	}

	/**
	 * Checks if all dependencies are met.
	 *
	 * @return bool
	 */
	public static function are_dependencies_met() {
		$are_met = true;
		if ( ! self::check_php() ) {
			add_action( 'admin_notices', array( __CLASS__, 'add_php_notice' ) );
			$are_met = false;
		}
		if ( ! self::check_sensei() ) {
			add_action( 'admin_notices', array( __CLASS__, 'add_sensei_notice' ) );
			$are_met = false;
		}
		return $are_met;
	}

	/**
	 * Checks for our PHP version requirement.
	 *
	 * @return bool
	 */
	private static function check_php() {
		return version_compare( phpversion(), self::MINIMUM_PHP_VERSION, '>=' );
	}

	/**
	 * Checks for our Sensei dependency.
	 *
	 * @return bool
	 */
	private static function check_sensei() {
		$active_plugins = self::get_active_plugins();

		$search_sensei = array(
			'sensei/sensei.php',                     // Sensei 2.x from WordPress.org.
			'sensei/woothemes-sensei.php',           // Sensei 1.x or Sensei 2.x Compatibility Plugin.
			'woothemes-sensei/woothemes-sensei.php', // Sensei 1.x or Sensei 2.x Compatibility Plugin.
		);

		$found_sensei = false;
		foreach ( $search_sensei as $basename ) {
			if ( in_array( $basename, $active_plugins, true ) || array_key_exists( $basename, $active_plugins ) ) {
				$found_sensei = true;
				break;
			}
		}

		if ( ! $found_sensei && ! class_exists( 'Sensei_Main' ) ) {
			return false;
		}

		// As long as we support 1.x, we need to also check this option.
		$legacy_version = get_option( 'woothemes-sensei-version' );
		return version_compare( self::MINIMUM_SENSEI_VERSION, get_option( 'sensei-version', $legacy_version ), '<=' );
	}

	/**
	 * Adds notice in WP Admin that minimum version of PHP is not met.
	 *
	 * @access private
	 */
	public static function add_php_notice() {
		$screen        = get_current_screen();
		$valid_screens = array( 'dashboard', 'plugins' );

		if ( ! current_user_can( 'activate_plugins' ) || ! in_array( $screen->id, $valid_screens, true ) ) {
			return;
		}

		// translators: %1$s is version of PHP that this plugin requires; %2$s is the version of PHP WordPress is running on.
		$message = sprintf( __( '<strong>Sensei Certificates</strong> requires PHP version %1$s but you are running %2$s.', 'sensei-certificates' ), self::MINIMUM_PHP_VERSION, phpversion() );
		echo '<div class="error"><p>';
		echo wp_kses( $message, array( 'strong' => array() ) );
		$php_update_url = 'https://wordpress.org/support/update-php/';
		if ( function_exists( 'wp_get_update_php_url' ) ) {
			$php_update_url = wp_get_update_php_url();
		}
		printf(
			'<p><a class="button button-primary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>',
			esc_url( $php_update_url ),
			esc_html__( 'Learn more about updating PHP', 'sensei-certificates' ),
			/* translators: accessibility text */
			esc_html__( '(opens in a new tab)', 'sensei-certificates' )
		);
		echo '</p></div>';
	}

	/**
	 * Adds the notice in WP Admin that Sensei is required.
	 *
	 * @access private
	 */
	public static function add_sensei_notice() {
		$screen        = get_current_screen();
		$valid_screens = array( 'dashboard', 'plugins' );

		if ( ! current_user_can( 'activate_plugins' ) || ! in_array( $screen->id, $valid_screens, true ) ) {
			return;
		}

		// translators: %1$s is the minimum version number of Sensei that is required.
		$message = sprintf( __( '<strong>Sensei Certificates</strong> requires the plugin <strong>Sensei</strong> (minimum version: <strong>%1$s</strong>) to be installed and activated.', 'sensei-certificates' ), self::MINIMUM_SENSEI_VERSION );
		echo '<div class="error"><p>';
		echo wp_kses( $message, array( 'strong' => array() ) );
		echo '</p></div>';
	}
}
