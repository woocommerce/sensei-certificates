<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Certificates Main Class
 *
 * All functionality pertaining to the Certificates functionality in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Extension
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - plugin_path()
 * - certificates_settings_tabs()
 * - certificates_settings_fields()
 * - setup_certificates_post_type()
 * - create_post_type_labels()
 * - setup_post_type_labels_base()
 */
class WooThemes_Sensei_Certificates {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {

		// Hook onto Sensei settings and load a new tab with settings for extension
		add_filter( 'sensei_settings_tabs', array( &$this, 'certificates_settings_tabs' ) );
		add_filter( 'sensei_settings_fields', array( &$this, 'certificates_settings_fields' ) );
		// Hook onto load post types in Sensei and load the certificates post type
		$this->labels = array();
		$this->setup_post_type_labels_base();
		add_action( 'init', array( &$this, 'setup_certificates_post_type' ), 110 );

	} // End __construct()

	/**
	 * plugin_path function
	 * @since  1.0.0
	 * @return string
	 */
	public function plugin_path() {

		if ( $this->plugin_path ) return $this->plugin_path;

		return $this->plugin_path = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );

	} // End plugin_path()

	/**
	 * certificates_settings_tabs function for settings tabs
	 * @param  $sections array
	 * @since  1.0.0
	 * @return $sections array
	 */
	public function certificates_settings_tabs( $sections ) {

		$sections['certificate-settings'] = array(
						'name' 			=> __( 'Certificate Settings', 'woothemes-sensei-certificates' ),
						'description'	=> __( 'Optional settings for the Certificates functions.', 'woothemes-sensei-certificates' )
					);

		return $sections;

	} // End certificates_settings_tabs()

	/**
	 * certificates_settings_fields function for settings fields
	 * @param  $fields array
	 * @since  1.0.0
	 * @return $fields array
	 */
	public function certificates_settings_fields( $fields ) {

		$fields['certificates_enabled'] = array(
			'name' 			=> __( 'Enable Certificates', 'woothemes-sensei-certificates' ),
			'description' 	=> __( 'A description for the extension setting.', 'woothemes-sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);
		$fields['certificates_public'] = array(
			'name' 			=> __( 'Publicly Viewable', 'woothemes-sensei-certificates' ),
			'description' 	=> __( 'Allow certificates to be publickly viewable by anyone.', 'woothemes-sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);

		return $fields;

	} // End certificates_settings_fields()

	/**
	 * Setup the "extension" post type, it's admin menu item and the appropriate labels and permissions.
	 * @since  1.0.0
	 * @uses  global $woothemes_sensei
	 * @return void
	 */
	public function setup_certificates_post_type () {

		global $woothemes_sensei;

		$args = array(
		    'labels' => $this->create_post_type_labels( 'extension', $this->labels['extension']['singular'], $this->labels['extension']['plural'], $this->labels['extension']['menu'] ),
		    'public' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true,
		    'show_in_menu' => 'edit.php?post_type=lesson',
		    'query_var' => true,
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_certificate_slug', 'certificate' ) ) , 'with_front' => true, 'feeds' => true, 'pages' => true ),
		    'map_meta_cap' => true,
		    // 'capability_type' => 'extension',
		    // 'capabilities' => array(
						// 				// meta caps (don't assign these to roles)
						// 				'edit_post'              => 'edit_extension',
						// 				'read_post'              => 'read_extension',
						// 				'delete_post'            => 'delete_extension',

						// 				// primitive/meta caps
						// 				'create_posts'           => 'create_extensions',

						// 				// primitive caps used outside of map_meta_cap()
						// 				'edit_posts'             => 'edit_extensions',
						// 				'edit_others_posts'      => 'edit_others_extensions',
						// 				'publish_posts'          => 'publish_extensions',
						// 				'read_private_posts'     => 'read_private_extensions',

						// 				// primitive caps used inside of map_meta_cap()
						// 				'read'                   => 'read',
						// 				'delete_posts'           => 'delete_extensions',
						// 				'delete_private_posts'   => 'delete_private_extensions',
						// 				'delete_published_posts' => 'delete_published_extensions',
						// 				'delete_others_posts'    => 'delete_others_extensions',
						// 				'edit_private_posts'     => 'edit_private_extensions',
						// 				'edit_published_posts'   => 'edit_published_extensions'
						// 			),
		    'has_archive' => true,
		    'hierarchical' => false,
		    'menu_position' => 20, // Below "Pages"
		    'menu_icon' => esc_url( $woothemes_sensei->plugin_url . 'assets/images/icon_course_16.png' ),
		    'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' )
		);

		register_post_type( 'certificate', $args );

	} // End setup_certificates_post_type()

	/**
	 * Create the labels for a specified post type.
	 * @since  1.0.0
	 * @param  string $token    The post type for which to setup labels (used to provide context)
	 * @param  string $singular The label for a singular instance of the post type
	 * @param  string $plural   The label for a plural instance of the post type
	 * @param  string $menu     The menu item label
	 * @return array            An array of the labels to be used
	 */
	private function create_post_type_labels ( $token, $singular, $plural, $menu ) {

		$labels = array(
		    'name' => sprintf( _x( '%s', 'post type general name', 'woothemes-sensei-certificates' ), $plural ),
		    'singular_name' => sprintf( _x( '%s', 'post type singular name', 'woothemes-sensei-certificates' ), $singular ),
		    'add_new' => sprintf( _x( 'Add New %s', $token, 'woothemes-sensei-certificates' ), $singular ),
		    'add_new_item' => sprintf( __( 'Add New %s', 'woothemes-sensei-certificates' ), $singular ),
		    'edit_item' => sprintf( __( 'Edit %s', 'woothemes-sensei-certificates' ), $singular ),
		    'new_item' => sprintf( __( 'New %s', 'woothemes-sensei-certificates' ), $singular ),
		    'all_items' => sprintf( __( 'All %s', 'woothemes-sensei-certificates' ), $plural ),
		    'view_item' => sprintf( __( 'View %s', 'woothemes-sensei-certificates' ), $singular ),
		    'search_items' => sprintf( __( 'Search %s', 'woothemes-sensei-certificates' ), $plural ),
		    'not_found' =>  sprintf( __( 'No %s found', 'woothemes-sensei-certificates' ), strtolower( $plural ) ),
		    'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'woothemes-sensei-certificates' ), strtolower( $plural ) ),
		    'parent_item_colon' => '',
		    'menu_name' => sprintf( __( '%s', 'woothemes-sensei-certificates' ), $menu )
		  );

		return $labels;

	} // End create_post_type_labels()

	/**
	 * Setup the singular, plural and menu label names for the post types.
	 * @since  1.0.0
	 * @return void
	 */
	private function setup_post_type_labels_base () {

		$this->labels = array( 'extension' => array() );

		$this->labels['certificate'] = array( 'singular' => __( 'Certificate', 'woothemes-sensei-certificates' ), 'plural' => __( 'Certificates', 'woothemes-sensei-certificates' ), 'menu' => __( 'Certificates', 'woothemes-sensei-certificates' ) );

	} // End setup_post_type_labels_base()

} // End Class