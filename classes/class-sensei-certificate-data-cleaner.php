<?php
/**
 * Defines a class with methods for cleaning up plugin data. To be used when
 * the plugin is deleted.
 *
 * @package Extension
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

/**
 * Class for cleaning up all plugin data.
 *
 * @author Automattic
 * @since  2.3.0
 */
class Sensei_Certificate_Data_Cleaner {

	/**
	 * Custom post types to be deleted.
	 *
	 * @var string[]
	 */
	private static $custom_post_types = array(
		'certificate',
		'certificate_template',
	);

	/**
	 * Options to be deleted.
	 *
	 * @var string[]
	 */
	private static $options = array(
		'sensei_certificates_version',
		'sensei_certificate_user_data_installer',
		'sensei_certificate_templates_installer',
		'sensei_certificates_view_certificate_button_added',
	);

	/**
	 * Attachment GUIDs (as MySQL regexes) to be deleted.
	 *
	 * @var string[]
	 */
	private static $attachment_guids = array(
		'.+sensei_certificate_nograde.png$',
	);

	/**
	 * Capabilities to be deleted.
	 *
	 * @var string[]
	 */
	private static $caps = array(
		// Certificates.
		'edit_certificate',
		'read_certificate',
		'delete_certificate',
		'create_certificates',
		'edit_certificates',
		'edit_others_certificates',
		'publish_certificates',
		'read_private_certificates',
		'delete_certificates',
		'delete_private_certificates',
		'delete_published_certificates',
		'delete_others_certificates',
		'edit_private_certificates',
		'edit_published_certificates',

		// Certificate Templates.
		'edit_certificate_template',
		'read_certificate_template',
		'delete_certificate_template',
		'create_certificate_templates',
		'edit_certificate_templates',
		'edit_others_certificate_templates',
		'publish_certificate_templates',
		'read_private_certificate_templates',
		'delete_certificate_templates',
		'delete_private_certificate_templates',
		'delete_published_certificate_templates',
		'delete_others_certificate_templates',
		'edit_private_certificate_templates',
		'edit_published_certificate_templates',
	);

	/**
	 * Transient names (as MySQL regexes) to be deleted. The prefixes
	 * "_transient_" and "_transient_timeout_" will be prepended.
	 *
	 * @var string[]
	 */
	private static $transients = array(
		'sensei_certificates_job_create_certificates',
	);

	/**
	 * User meta key names (as MySQL regexes) to be deleted.
	 *
	 * @var string[]
	 */
	private static $user_meta_keys = array(
		'^%BLOG_PREFIX%sensei_certificates_view_by_public$',
	);

	/**
	 * Post meta to be deleted.
	 *
	 * @var string[]
	 */
	private static $post_meta = array(
		'_course_certificate_template',
	);

	/**
	 * Cleanup all data.
	 */
	public static function cleanup_all() {
		self::cleanup_custom_post_types();
		self::cleanup_attachments();
		self::cleanup_post_meta();
		self::cleanup_capabilities();
		self::cleanup_transients();
		self::cleanup_settings();
		self::cleanup_options();
		self::cleanup_user_meta();

		// Todo: Maybe cleanup the 'view-certificate' buttons.
	}

	/**
	 * Checks if the uninstall option is enabled.
	 *
	 * @return bool
	 */
	public static function should_do_cleanup(): bool {
		$settings = get_option( 'sensei-settings' );

		return (bool) ( $settings['certificates_delete_data_on_uninstall'] ?? false );
	}

	/**
	 * Cleanup data for custom post types.
	 */
	private static function cleanup_custom_post_types() {
		foreach ( self::$custom_post_types as $post_type ) {
			$items = get_posts(
				array(
					'post_type'   => $post_type,
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);

			foreach ( $items as $item ) {
				wp_trash_post( $item );
			}
		}
	}

	/**
	 * Cleanup data for attachments.
	 */
	private static function cleanup_attachments() {
		global $wpdb;

		foreach ( self::$attachment_guids as $attachment_guid ) {
			$attachments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts
					WHERE post_type = 'attachment'
					AND guid RLIKE %s",
					$attachment_guid
				)
			);

			foreach ( $attachments as $attachment ) {
				wp_delete_attachment( $attachment->ID );
			}
		}
	}

	/**
	 * Cleanup Sensei settings.
	 */
	private static function cleanup_settings() {
		$settings = get_option( 'sensei-settings' );

		if ( ! $settings ) {
			return;
		}

		$settings_fields = WooThemes_Sensei_Certificates::instance()->certificates_settings_fields( [] );
		foreach ( $settings_fields as $field_key => $field_data ) {
			unset( $settings[ $field_key ] );
		}

		delete_option( 'sensei-settings' );
		update_option( 'sensei-settings', $settings );
	}

	/**
	 * Cleanup data for options.
	 */
	private static function cleanup_options() {
		foreach ( self::$options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Cleanup data for user/role capabilities.
	 */
	private static function cleanup_capabilities() {
		global $wp_roles;

		// Remove caps from roles.
		$role_names = array_keys( $wp_roles->roles );
		foreach ( $role_names as $role_name ) {
			$role = get_role( $role_name );
			self::remove_capabilities( $role );
		}

		// Remove caps from users.
		$users = get_users( array() );
		foreach ( $users as $user ) {
			self::remove_capabilities( $user );
		}
	}

	/**
	 * Helper method to remove capabilities from a user or role object.
	 *
	 * @param (WP_User|WP_Role) $object the user or role object.
	 */
	private static function remove_capabilities( $object ) {
		foreach ( self::$caps as $cap ) {
			$object->remove_cap( $cap );
		}
	}

	/**
	 * Cleanup transients from the database.
	 */
	private static function cleanup_transients() {
		global $wpdb;

		foreach ( array( '_transient_', '_transient_timeout_' ) as $prefix ) {
			foreach ( self::$transients as $transient ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $wpdb->options WHERE option_name RLIKE %s",
						$prefix . $transient
					)
				);
			}
		}
	}

	/**
	 * Cleanup Sensei user meta from the database.
	 */
	private static function cleanup_user_meta() {
		global $wpdb;

		foreach ( self::$user_meta_keys as $meta_key ) {
			$meta_key = str_replace( '%BLOG_PREFIX%', preg_quote( $wpdb->get_blog_prefix(), null ), $meta_key );

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->usermeta WHERE meta_key RLIKE %s",
					$meta_key
				)
			);
		}
	}

	/**
	 * Cleanup post meta that doesn't get deleted automatically.
	 */
	private static function cleanup_post_meta() {
		global $wpdb;

		foreach ( self::$post_meta as $post_meta ) {
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $post_meta ) );
		}
	}
}
