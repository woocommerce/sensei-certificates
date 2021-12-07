<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei LMS Certificates Main Class
 *
 * All functionality pertaining to the Certificates functionality in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Extension
 * @author Automattic
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - plugin_path()
 * - certificates_settings_tabs()
 * - certificates_settings_fields()
 * - setup_certificates_post_type()
 * - post_type_custom_column_headings()
 * - post_type_custom_column_content()
 * - generate_certificate_number()
 * - can_view_certificate()
 * - download_certificate()
 * - replace_data_field_template_tags()
 * - certificate_text()
 * - certificate_backgroudn()
 * - get_certificate_font_settings()
 * - certificate_link()
 * - create_columns()
 * - populate_columns()
 * - add_inline_js()
 * - output_inline_js()
 * - include_sensei_scripts()
 * - reset_course_certificate()
 * - certificates_user_settings_form()
 * - certificcates_user_settings_save()
 * - certificates_user_settings_messages()
 */

class WooThemes_Sensei_Certificates {

	/**
	 * The single instance of WooThemes_Sensei_Certificates.
	 *
	 * @var    object
	 * @access private
	 * @static
	 * @since  2.0.0
	 */
	private static $_instance = null;

	/**
	 * @var string url link to plugin files
	 */
	public $plugin_url;

	/**
	 * @var string path to the plugin files
	 */
	public $plugin_path;

	/**
	 * @var string inline js code
	 */
	public $_inline_js;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {

		// Defaults
		$this->plugin_url  = trailingslashit( plugins_url( '', SENSEI_CERTIFICATES_PLUGIN_FILE ) );
		$this->plugin_path = plugin_dir_path( SENSEI_CERTIFICATES_PLUGIN_FILE );

		register_activation_hook( SENSEI_CERTIFICATES_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
	} // End __construct()

	/**
	 * Set up all hooks and filters.
	 */
	public static function init() {
		add_action( 'sensei_certificates_check_run_installer', array( __CLASS__, 'check_run_installer' ) );

		$version = get_option( 'sensei_certificates_version', false );
		if ( ! $version || SENSEI_CERTIFICATES_VERSION !== $version ) {
			// If we haven't been installed, schedule another installer check on another plugin's activation.
			add_action( 'activated_plugin', array( __CLASS__, 'schedule_installer_check' ) );
		}

		if ( ! Woothemes_Sensei_Certificates_Dependency_Checker::are_plugin_dependencies_met() ) {
			return;
		}

		$instance = self::instance();

		self::load_files();

		if ( class_exists( 'Sensei_Assets' ) ) {
			$instance->assets = new \Sensei_Assets( $instance->plugin_url, dirname( __DIR__ ), SENSEI_CERTIFICATES_VERSION );
		}

		$GLOBALS['woothemes_sensei_certificates']          = self::instance();
		$GLOBALS['woothemes_sensei_certificate_templates'] = new WooThemes_Sensei_Certificate_Templates();

		add_action( 'sensei_certificates_run_installer', array( $instance, 'install' ) );

		// Hook onto Sensei settings and load a new tab with settings for extension
		add_filter( 'sensei_settings_tabs', array( $instance, 'certificates_settings_tabs' ) );
		add_filter( 'sensei_settings_fields', array( $instance, 'certificates_settings_fields' ) );

		// Setup post type
		add_action( 'init', array( $instance, 'setup_certificates_post_type' ), 110 );
		add_filter( 'manage_edit-certificate_columns', array( $instance, 'post_type_custom_column_headings' ) );
		add_action( 'manage_certificate_posts_custom_column', array( $instance, 'post_type_custom_column_content' ), 10, 2 );

		/**
		 * FRONTEND
		 */

		// Filters
		add_filter( 'sensei_user_course_status_passed', array( $instance, 'certificate_link' ), 10, 1 );
		add_filter( 'sensei_results_links', array( $instance, 'certificate_link' ), 10, 2 );

		// Actions
		add_action( 'wp_enqueue_scripts', array( $instance, 'enqueue_styles' ) );
		add_action( 'sensei_user_lesson_reset', array( $instance, 'reset_lesson_course_certificate' ), 10, 2 );
		add_action( 'sensei_user_course_reset', array( $instance, 'reset_course_certificate' ), 10, 2 );
		// Create certificate endpoint and handle generation of pdf certificate
		add_action( 'template_redirect', array( $instance, 'download_certificate' ) );
		// User settings output and save handling
		add_action( 'sensei_learner_profile_info', array( $instance, 'certificates_user_settings_form' ), 10, 1 );
		add_action( 'sensei_complete_course', array( $instance, 'certificates_user_settings_save' ), 10 );
		add_action( 'sensei_frontend_messages', array( $instance, 'certificates_user_settings_messages' ), 10 );

		/**
		 * Emails
		 */
		add_action( 'sensei_after_email_content', array( $instance, 'email_certificate_link' ) );

		/**
		 * BACKEND
		 */
		if ( is_admin() ) {
			// Add Certificates Menu
			add_action( 'sensei_analysis_course_columns', array( $instance, 'create_columns' ), 10, 2 );
			add_action( 'sensei_analysis_course_column_data', array( $instance, 'populate_columns' ), 10, 3 );
			add_filter( 'sensei_scripts_allowed_post_types', array( $instance, 'include_sensei_scripts' ), 10, 1 );
			add_filter( 'sensei_upgrade_functions', 'sensei_certificates_updates_list', 10, 1 );
			add_filter( 'sensei_updates_function_whitelist', 'sensei_certificates_add_update_functions_to_whitelist', 1 );

			// We don't need a WordPress SEO meta box for certificates and certificate templates. Hide it.
			add_filter( 'option_wpseo_titles', array( $instance, 'force_hide_wpseo_meta_box' ) );

			// Reorder the admin menus to display Certificates below Lessons.
			add_filter( 'custom_menu_order', '__return_true', 20 );
			add_filter( 'menu_order', array( $instance, 'admin_menu_order' ) );

			if ( interface_exists( 'Sensei_Tool_Interface' ) ) {
				self::load_tools();
			}
		}

		if ( interface_exists( 'Sensei_Background_Job_Interface' ) ) {
			self::load_background_jobs();
		}

		// Generate certificate hash when course is completed.
		add_action( 'sensei_course_status_updated', array( $instance, 'handle_course_completed' ), 9, 3 );
		// Background Image to display on certificate
		add_action( 'sensei_certificates_set_background_image', array( $instance, 'certificate_background' ), 10, 1 );
		// Certificate data field tag replacement.
		add_filter( 'sensei_certificate_data_field_value', array( $instance, 'replace_data_field_template_tags' ), 10, 5 );
		// Text to display on certificate
		add_action( 'sensei_certificates_before_pdf_output', array( $instance, 'certificate_text' ), 10, 2 );

		// Blocks
		add_action( 'enqueue_block_editor_assets', [ $instance, 'enqueue_block_editor_assets' ] );
		add_filter( 'render_block', [ $instance, 'update_view_certificate_button_url' ], 10, 2 );
		add_filter( 'sensei_course_completed_page_template', [ $instance, 'add_certificate_button_to_course_completed_template' ] );
		add_action( 'init', [ $instance, 'add_certificate_button_to_current_course_completed_page' ] );
	}

	/**
	 * Load plugin files.
	 */
	private static function load_files() {
		require_once dirname( dirname( __FILE__ ) ) . '/sensei-certificates-functions.php';
		require_once dirname( __FILE__ ) . '/class-woothemes-sensei-certificates-utils.php';
		require_once dirname( __FILE__ ) . '/class-woothemes-sensei-certificates.php';
		require_once dirname( __FILE__ ) . '/class-woothemes-sensei-certificate-templates.php';
		require_once dirname( __FILE__ ) . '/class-woothemes-sensei-certificates-data-store.php';
		require_once dirname( __FILE__ ) . '/class-woothemes-sensei-certificates-tfpdf.php';
	}

	/**
	 * Load background jobs.
	 */
	private static function load_background_jobs() {
		require_once __DIR__ . '/background-jobs/class-sensei-certificates-create-certificates.php';

		add_action( Sensei_Certificates_Create_Certificates::NAME, [ __CLASS__, 'run_create_certificates_job' ] );
	}

	/**
	 * Run the create certificates job.
	 *
	 * @access private
	 */
	public static function run_create_certificates_job() {
		$job = Sensei_Certificates_Create_Certificates::instance();
		Sensei_Scheduler::instance()->run( $job );
	}

	/**
	 * Load the tools and add needed filters.
	 */
	private static function load_tools() {
		require_once __DIR__ . '/tools/class-sensei-certificates-tool-create-certificates.php';
		require_once __DIR__ . '/tools/class-sensei-certificates-tool-create-default-example-template.php';

		add_filter( 'sensei_tools', [ __CLASS__, 'add_sensei_certificates_tools' ] );
	}

	/**
	 * Add Sensei Certificates tools to Sensei LMS.
	 *
	 * @param array $tools Tool objects for Sensei LMS.
	 *
	 * @return array
	 */
	public static function add_sensei_certificates_tools( $tools ) {
		$tools[] = new Sensei_Certificates_Tool_Create_Certificates();
		$tools[] = new Sensei_Certificates_Tool_Create_Default_Example_Template();

		return $tools;
	}

	/**
	 * Check dependencies. If met, run installer.
	 */
	public static function check_run_installer() {
		if ( ! Woothemes_Sensei_Certificates_Dependency_Checker::are_plugin_dependencies_met() ) {
			return;
		}

		do_action( 'sensei_certificates_run_installer' );
	}

	/**
	 * Load the plugin text domain.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'sensei-certificates', false, dirname( SENSEI_CERTIFICATES_PLUGIN_BASENAME ) . '/lang/' );
	}

	/**
	 * Load front-end CSS.
	 *
	 * @access private
	 * @since  1.0.0
	 */
	public function enqueue_styles() {
		global $wp_query;

		$view_link_courses = Sensei()->settings->settings['certificates_view_courses'];
		$view_link_profile = Sensei()->settings->settings['certificates_view_profile'];

		// Certificates are not configured to display on any pages.
		if ( ! $view_link_courses && ! $view_link_profile ) {
			return;
		}

		$should_enqueue = false;

		// My Courses or single course page.
		if ( $view_link_courses
			&& ( is_page( intval( Sensei()->settings->get( 'my_course_page' ) ) )
			|| ( is_single() && 'course' === get_post_type() ) )
		) {
			$should_enqueue = true;
		} elseif ( $view_link_profile && isset( $wp_query->query_vars['learner_profile'] )
		) {
			$should_enqueue = true;
		}

		if ( $should_enqueue ) {
			wp_enqueue_style( 'sensei-certificates-frontend', $this->plugin_url . 'assets/dist/css/frontend.css', array(), SENSEI_CERTIFICATES_VERSION, 'screen' );
		}
	}

	/**
	 * Function that runs on activation.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public static function activate() {
		self::schedule_installer_check();
	}

	/**
	 * Schedules event to check if we can run the installer.
	 */
	public static function schedule_installer_check() {
		wp_clear_scheduled_hook( 'sensei_certificates_check_run_installer' );
		wp_schedule_single_event( time(), 'sensei_certificates_check_run_installer' );
	}

	/**
	 * Performs actions to on activation once dependencies are met.
	 *
	 * @since  2.0.0
	 * @return string
	 */
	public function install() {
		// Register post types, so we can flush the rewrite rules.
		$this->setup_certificates_post_type();
		$GLOBALS['woothemes_sensei_certificate_templates']->setup_certificate_templates_post_type();

		update_option( 'sensei_certificates_version', SENSEI_CERTIFICATES_VERSION );

		// Check if the installer has already been run
		$sensei_certificates_user_data_installed = get_option( 'sensei_certificate_user_data_installer', false );
		$sensei_certificate_templates_installed  = get_option( 'sensei_certificate_templates_installer', false );
		$user_count                              = count_users();
		$total_users                             = intval( $user_count['total_users'] );

		if ( ! $sensei_certificates_user_data_installed && 1000 >= $total_users ) {

			// Add certificates for courses that have been completed.
			$user_data_installed = sensei_update_users_certificate_data( $total_users, 0 );
			update_option( 'sensei_certificate_user_data_installer', $user_data_installed );

		}

		if ( ! $sensei_certificate_templates_installed ) {

			// Create the example Certificate Template.
			$template_installed = sensei_create_master_certificate_template();
			update_option( 'sensei_certificate_templates_installer', $template_installed );

		}

		flush_rewrite_rules();
	}

	/**
	 * [admin_menu_order description]
	 *
	 * @since  1.4.0
	 * @param  array $menu_order Existing menu order
	 * @return array             Modified menu order for Sensei
	 */
	public function admin_menu_order( $menu_order ) {
		$new_order    = array();
		$item_before  = 'edit.php?post_type=lesson';
		$item_to_move = 'edit.php?post_type=certificate';

		if ( isset( $menu_order[ $item_to_move ] ) ) {
			unset( $menu_order[ $item_to_move ] );
		}

		// Loop through menu order and do some rearranging
		foreach ( $menu_order as $k => $v ) {
			if ( $v == $item_before ) {
				$new_order[] = $v;
				$new_order[] = $item_to_move;
			} else {
				$new_order[] = $v;
			}
		}

		// Return order
		return $new_order;
	}

	/**
	 * Force the WordPress SEO meta box to be turned off for the "certificate" and "certificate_template" post types.
	 *
	 * @access  public
	 * @since   1.0.1
	 * @param   array $value WordPress SEO wpseo_titles option.
	 * @return  array        Modified array.
	 */
	public function force_hide_wpseo_meta_box( $value ) {
		if ( is_array( $value ) ) {
			$value['hideeditbox-certificate']          = 'on';
			$value['hideeditbox-certificate_template'] = 'on';
		}

		return $value;
	} // End force_hide_wpseo_meta_box()

	/**
	 * plugin_path function
	 *
	 * @access public
	 * @since  1.0.0
	 * @return string
	 */
	public function plugin_path() {

		if ( $this->plugin_path ) {
			return $this->plugin_path;
		}

		return $this->plugin_path = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );

	} // End plugin_path()


	/**
	 * certificates_settings_tabs function for settings tabs
	 *
	 * @access public
	 * @param  $sections array
	 * @since  1.0.0
	 * @return $sections array
	 */
	public function certificates_settings_tabs( $sections ) {

		$sections['certificate-settings'] = array(
			'name'        => __( 'Certificate Settings', 'sensei-certificates' ),
			'description' => __( 'Options for the Certificate Extension.', 'sensei-certificates' ),
		);

		return $sections;

	} // End certificates_settings_tabs()

	/**
	 * certificates_settings_fields function for settings fields
	 *
	 * @access public
	 * @param  $fields array
	 * @since  1.0.0
	 * @return $fields array
	 */
	public function certificates_settings_fields( $fields ) {

		$fields['certificates_view_courses'] = array(
			'name'        => __( 'View in Courses', 'sensei-certificates' ),
			'description' => __( 'Show a View Certificate link in the single Course page and the My Courses page.', 'sensei-certificates' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'certificate-settings',
		);

		$fields['certificates_view_profile'] = array(
			'name'        => __( 'View in Learner Profile', 'sensei-certificates' ),
			'description' => __( 'Show a View Certificate link in the Learner Profile page.', 'sensei-certificates' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'certificate-settings',
		);

		$fields['certificates_public_viewable'] = array(
			'name'        => __( 'Public Certificate', 'sensei-certificates' ),
			'description' => __( 'Allow the Learner to share their Certificate with the public. (The learner will have to enable this in their profile by going to mysite.com/learner/{learner_username})', 'sensei-certificates' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'certificate-settings',
		);

		$fields['certificates_delete_data_on_uninstall'] = array(
			'name'        => __( 'Delete data on uninstall', 'sensei-certificates' ),
			'description' => __( 'Delete Sensei Certificates data when the plugin is deleted. Once removed, this data cannot be restored.', 'sensei-certificates' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'certificate-settings',
		);

		return $fields;

	} // End certificates_settings_fields()

	/**
	 * Setup the certificate post type, it's admin menu item and the appropriate labels and permissions.
	 *
	 * @access public
	 * @since  1.0.0
	 * @uses  global $woothemes_sensei
	 * @return void
	 */
	public function setup_certificates_post_type() {

		$args = array(
			'labels'              => array(
				'name'               => _x( 'Certificates', 'post type general name', 'sensei-certificates' ),
				'singular_name'      => _x( 'Certificate', 'post type singular name', 'sensei-certificates' ),
				'add_new'            => _x( 'Add New Certificate', 'post type add_new', 'sensei-certificates' ),
				'add_new_item'       => __( 'Add New Certificate', 'sensei-certificates' ),
				'edit_item'          => __( 'Edit Certificate', 'sensei-certificates' ),
				'new_item'           => __( 'New Certificate', 'sensei-certificates' ),
				'all_items'          => __( 'Certificates', 'sensei-certificates' ),
				'view_item'          => __( 'View Certificate', 'sensei-certificates' ),
				'search_items'       => __( 'Search Certificates', 'sensei-certificates' ),
				'not_found'          => __( 'No certificates found', 'sensei-certificates' ),
				'not_found_in_trash' => __( 'No certificates found in Trash', 'sensei-certificates' ),
				'parent_item_colon'  => '',
				'menu_name'          => __( 'Certificates', 'sensei-certificates' ),
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'query_var'           => true,
			'rewrite'             => array(
				'slug'       => esc_attr( apply_filters( 'sensei_certificates_slug', 'certificate' ) ),
				'with_front' => true,
				'feeds'      => true,
				'pages'      => true,
			),
			'capability_type'     => 'certificate',
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_icon'           => 'dashicons-awards',
			'menu-position'       => 21,
			'supports'            => array( 'title', 'custom-fields' ),
		);

		register_post_type( 'certificate', $args );

	} // End setup_certificates_post_type()


	/**
	 * post_type_custom_column_headings function.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $defaults default values
	 * @return array $defaults modified values
	 */
	public function post_type_custom_column_headings( $defaults ) {

		unset( $defaults['date'] );
		$defaults['learner']        = __( 'Learner', 'sensei-certificates' );
		$defaults['course']         = __( 'Course', 'sensei-certificates' );
		$defaults['date_completed'] = __( 'Date Completed', 'sensei-certificates' );
		$defaults['actions']        = __( 'Actions', 'sensei-certificates' );

		return $defaults;

	} // End post_type_custom_column_headings()


	/**
	 * post_type_custom_column_content function.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  string $column_name
	 * @param  int    $post_ID post id
	 * @return void
	 */
	public function post_type_custom_column_content( $column_name, $post_ID ) {
		$user_id   = get_post_meta( $post_ID, 'learner_id', true );
		$course_id = get_post_meta( $post_ID, 'course_id', true );
		if ( empty( $user_id ) || empty( $course_id ) ) {
			echo '-';
			return;
		}
		$user            = get_userdata( $user_id );
		$course          = get_post( $course_id );
		$course_end_date = WooThemes_Sensei_Utils::sensei_get_activity_value(
			array(
				'post_id' => $course_id,
				'user_id' => $user_id,
				'type'    => 'sensei_course_status',
				'field'   => 'comment_date',
			)
		);

		switch ( $column_name ) {
			case 'learner':
				echo '<a href="' . esc_url(
					add_query_arg(
						array(
							'page'      => 'sensei_analysis',
							'user_id'   => intval( $user_id ),
							'course_id' => intval( $course_id ),
						),
						admin_url( 'admin.php' )
					)
				) . '">' . esc_html( "{$user->display_name} ({$user->user_login})" ) . '</a>';
				break;
			case 'course':
				echo '<a href="' . esc_url(
					add_query_arg(
						array(
							'page'      => 'sensei_analysis',
							'course_id' => intval( $course_id ),
						),
						admin_url( 'admin.php' )
					)
				) . '">' . esc_html( $course->post_title ) . '</a>';
				break;
			case 'date_completed':
				echo wp_kses_post( $course_end_date );
				break;
			case 'actions':
				$template_id = get_post_meta( $course_id, '_course_certificate_template', true );
				if ( $template_id ) {
					echo '<a href="' . esc_url( get_permalink( $post_ID ) ) . '" target="_blank">' . esc_html__( 'View Certificate', 'sensei-certificates' ) . '</a>';
				} else {
					echo wp_kses_post(
						sprintf(
							// translators: %1$s is the URL for editing the Course.
							__( 'Set a certificate template on the <a href="%1$s">course</a> in order to view this certificate', 'sensei-certificates' ),
							esc_url( get_edit_post_link( $course_id ) )
						)
					);
				}
				break;
		} // End Switch Statement

	} // End post_type_custom_column_content()

	/**
	 * Ensure certificate is generated on course completion.
	 *
	 * @access private
	 * @since 2.0.0
	 *
	 * @param string $status    The new course status.
	 * @param int    $user_id   The ID of the learner.
	 * @param int    $course_id The ID of the course.
	 * @return void
	 */
	public function handle_course_completed( $status, $user_id, $course_id ) {
		if ( 'complete' !== $status ) {
			return;
		}

		$this->generate_certificate_number( $user_id, $course_id );
	}

	/**
	 * Generate unique certificate hash and save as comment.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  int $user_id arguments for queries
	 * @param  int $course_id data to post
	 * @return void
	 */
	public function generate_certificate_number( $user_id = 0, $course_id = 0 ) {

		if ( ! $user_id || ! $course_id || ! is_numeric( $user_id ) || ! is_numeric( $course_id ) ) {
			return;
		}
		$user_id   = absint( $user_id );
		$course_id = absint( $course_id );
		if ( false === get_user_by( 'id', $user_id ) ) {
			return;
		}

		if ( null === get_post( $course_id ) ) {
			return;
		}
		$data_store = new Woothemes_Sensei_Certificate_Data_Store();

		$certificate_id = $data_store->insert( $user_id, $course_id );

		if ( ! is_wp_error( $certificate_id ) ) {

			$data = array(
				'post_id' => absint( $certificate_id ),
				'data'    => Woothemes_Sensei_Certificates_Utils::get_certificate_hash( $course_id, $user_id ),
				'type'    => 'sensei_certificate',
				'user_id' => $user_id,
			);

			WooThemes_Sensei_Utils::sensei_log_activity( $data );
		}
	} // End generate_certificate_number()


	/**
	 * Check if certificate is viewable
	 *
	 * @access public
	 * @since  1.0.0
	 * @return boolean
	 */
	public function can_view_certificate( $certificate_id = 0 ) {

		global $post, $current_user;

		$response = false;

		if ( 0 >= intval( $certificate_id ) ) {
			return false; // We require a certificate ID value.
		}

		$learner_id = get_post_meta( intval( $certificate_id ), 'learner_id', true );

		// Check if student can only view certificate
		$grant_access      = Sensei()->settings->settings['certificates_public_viewable'];
		$grant_access_user = get_user_option( 'sensei_certificates_view_by_public', $learner_id );

		/**
		 * Filter to force all certificates to be public.
		 *
		 * @since 1.9.0
		 * @param bool $force_public_access default false
		 */

		$force_public_access = apply_filters( 'sensei_certificates_force_public_certs', false );

		// If we can view certificates, get out.
		if ( true == (bool) $grant_access_user && true == (bool) $grant_access || $force_public_access || current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( isset( $current_user->ID ) && ( intval( $current_user->ID ) === intval( $learner_id ) ) ) {
			$response = true;
		}

		return $response;

	} // End can_view_certificate()


	/**
	 * Download the certificate
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function download_certificate() {

		global $post;

		if ( ! is_singular() || 'certificate' != get_post_type() ) {
			return;
		}

		if ( $this->can_view_certificate( get_the_ID() ) ) {

			$hash      = $post->post_slug;
			$hash_meta = get_post_meta( get_the_ID(), 'certificate_hash', true );
			if ( ! empty( $hash_meta ) && 8 >= strlen( $hash_meta ) ) {
				$hash = $hash_meta;
			}

			// Generate the certificate here
			require_once 'class-woothemes-sensei-pdf-certificate.php';
			$pdf = new WooThemes_Sensei_PDF_Certificate( $hash );
			$pdf->generate_pdf();
			exit;

		} elseif ( is_user_logged_in() ) {

			wp_die( esc_html__( 'You are not allowed to view this Certificate.', 'sensei-certificates' ), esc_html__( 'Certificate Error', 'sensei-certificates' ) );

		} else {

			// Redirect to the login page.
			wp_safe_redirect( wp_login_url( get_permalink() ) );
			exit;

		} // End If Statement

	} // End generate_certificate()

	/**
	 * Replace template tags on certificate data fields.
	 *
	 * @access public
	 * @since  2.2.2
	 *
	 * @param string       $field_value The data field value.
	 * @param string       $field_key   The data field key.
	 * @param WP_User      $student     The student user.
	 * @param WP_Post|null $course      The course post.
	 *
	 * @return string
	 */
	public function replace_data_field_template_tags( $field_value, $field_key, $student, $course = null ) {
		// Prepare data.
		if ( $course ) {
			$course_title    = $course->post_title;
			$course_end      = Sensei_Utils::sensei_check_for_activity(
				array(
					'post_id' => $course->ID,
					'user_id' => $student->ID,
					'type'    => 'sensei_course_status',
				),
				true
			);
			$course_end_date = $course_end->comment_date;
		} else {
			// Most likely this is for preview. Use placeholder data.
			$course_title    = __( 'Course Title', 'sensei-certificates' );
			$course_end_date = gmdate( 'Y-m-d' );
		}

		// Get student name.
		$student_name = $student->display_name;
		if ( $student->first_name && $student->last_name ) {
			$student_name = $student->first_name . ' ' . $student->last_name;
		}

		// Get end date.
		$completion_date = Woothemes_Sensei_Certificates_Utils::get_certificate_formatted_date( $course_end_date );

		$replacement_values = array(
			'{{learner}}'         => $student_name,
			'{{course_title}}'    => $course_title,
			'{{completion_date}}' => $completion_date,
			'{{course_place}}'    => get_bloginfo( 'name' ),
		);

		return str_replace(
			array_keys( $replacement_values ),
			array_values( $replacement_values ),
			$field_value
		);
	}

	/**
	 * Add text to the certificate
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function certificate_text( $pdf_certificate, $fpdf ) {

		$show_border    = apply_filters( 'woothemes_sensei_certificates_show_border', 0 );
		$start_position = 200;

		// Find certificate based on hash
		$args = array(
			'post_type'  => 'certificate',
			'meta_key'   => 'certificate_hash',
			'meta_value' => $pdf_certificate->hash,
		);

		$query          = new WP_Query( $args );
		$certificate_id = 0;

		if ( $query->have_posts() ) {

			$query->the_post();
			$certificate_id = $query->posts[0]->ID;

		} // End If Statement

		wp_reset_query();

		if ( 0 < intval( $certificate_id ) ) {

			// Get Student Data
			$user_id = get_post_meta( $certificate_id, 'learner_id', true );
			$student = get_userdata( $user_id );

			// Get Course Data
			$course_id       = get_post_meta( $certificate_id, 'course_id', true );
			$course          = get_post( $course_id );
			$course_end      = Sensei_Utils::sensei_check_for_activity(
				array(
					'post_id' => intval( $course_id ),
					'user_id' => intval( $user_id ),
					'type'    => 'sensei_course_status',
				),
				true
			);
			$course_end_date = $course_end->comment_date;

			// Get the certificate template
			$certificate_template_id = get_post_meta( $course_id, '_course_certificate_template', true );

			$certificate_template_custom_fields = get_post_custom( $certificate_template_id );

			// Define the data we're going to load: Key => Default value
			$load_data = array(
				'certificate_font_style'      => '',
				'certificate_font_color'      => '',
				'certificate_font_size'       => '',
				'certificate_font_family'     => '',
				'image_ids'                   => array(),
				'certificate_template_fields' => array(),
			);

			// Load the data from the custom fields
			foreach ( $load_data as $key => $default ) {

				// set value from db (unserialized if needed) or use default
				$this->$key = ( isset( $certificate_template_custom_fields[ '_' . $key ][0] ) && '' !== $certificate_template_custom_fields[ '_' . $key ][0] ) ? ( is_array( $default ) ? maybe_unserialize( $certificate_template_custom_fields[ '_' . $key ][0] ) : $certificate_template_custom_fields[ '_' . $key ][0] ) : $default;

			} // End For Loop

			// Set default fonts
			if ( isset( $this->certificate_font_color ) && '' != $this->certificate_font_color ) {
				$pdf_certificate->certificate_pdf_data['font_color'] = $this->certificate_font_color; }
			if ( isset( $this->certificate_font_size ) && '' != $this->certificate_font_size ) {
				$pdf_certificate->certificate_pdf_data['font_size'] = $this->certificate_font_size; }
			if ( isset( $this->certificate_font_family ) && '' != $this->certificate_font_family ) {
				$pdf_certificate->certificate_pdf_data['font_family'] = $this->certificate_font_family; }
			if ( isset( $this->certificate_font_style ) && '' != $this->certificate_font_style ) {
				$pdf_certificate->certificate_pdf_data['font_style'] = $this->certificate_font_style; }

			// Data fields.
			$data_fields = sensei_get_certificate_data_fields();
			foreach ( $data_fields as $field_key => $field_info ) {

				$meta_key = 'certificate_' . $field_key;

				// Get the default field value.
				$field_value = $field_info['text_placeholder'];
				if ( isset( $this->certificate_template_fields[ $meta_key ]['text'] ) && '' !== $this->certificate_template_fields[ $meta_key ]['text'] ) {
					$field_value = $this->certificate_template_fields[ $meta_key ]['text'];
				}

				// Replace the template tags.
				$field_value = apply_filters( 'sensei_certificate_data_field_value', $field_value, $field_key, $student, $course );

				// Check if the field has a set position.
				if ( isset( $this->certificate_template_fields[ $meta_key ]['position']['x1'] ) ) {

					// Write the value to the PDF.
					$function_name = ( 'textarea' === $field_info['type'] ) ? 'textarea_field' : 'text_field';

					$font_settings = $this->get_certificate_font_settings( $meta_key );

					call_user_func_array( array( $pdf_certificate, $function_name ), array( $fpdf, $field_value, $show_border, array( $this->certificate_template_fields[ $meta_key ]['position']['x1'], $this->certificate_template_fields[ $meta_key ]['position']['y1'], $this->certificate_template_fields[ $meta_key ]['position']['width'], $this->certificate_template_fields[ $meta_key ]['position']['height'] ), $font_settings ) );

				} // End If Statement
			} // End For Loop
		} else {

			wp_die( esc_html__( 'The certificate you are searching for does not exist.', 'sensei-certificates' ), esc_html__( 'Certificate Error', 'sensei-certificates' ) );

		} // End If Statement

	} // End certificate_text()


	/**
	 * Add background to the certificate
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function certificate_background( $pdf_certificate ) {

		$start_position = 200;

		// Find certificate based on hash
		$args = array(
			'post_type'  => 'certificate',
			'meta_key'   => 'certificate_hash',
			'meta_value' => $pdf_certificate->hash,
		);

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {

			$query->the_post();
			$certificate_id = $query->posts[0]->ID;

		} // End If Statement

		wp_reset_query();

		// Get Course Data
		$course_id = get_post_meta( $certificate_id, 'course_id', true );

		// Get the certificate template
		$certificate_template_id = get_post_meta( $course_id, '_course_certificate_template', true );

		$certificate_template_custom_fields = get_post_custom( $certificate_template_id );

		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'image_ids' => array(),
		);

		// Load the data from the custom fields
		foreach ( $load_data as $key => $default ) {

			// set value from db (unserialized if needed) or use default
			$this->$key = ( isset( $certificate_template_custom_fields[ '_' . $key ][0] ) && '' !== $certificate_template_custom_fields[ '_' . $key ][0] ) ? ( is_array( $default ) ? maybe_unserialize( $certificate_template_custom_fields[ '_' . $key ][0] ) : $certificate_template_custom_fields[ '_' . $key ][0] ) : $default;

		} // End For Loop

		// set the certificate main template image, if any
		if ( count( $this->image_ids ) > 0 ) {
			$this->image_id = $this->image_ids[0];
		} // End If Statement

		// Logo image
		if ( isset( $this->image_id ) && is_numeric( $this->image_id ) && 0 < intval( $this->image_id ) ) {
			$pdf_certificate->bg_image_src = get_attached_file( $this->image_id );
		}

	} // End certificate_background()


	/**
	 * Returns font settings for the certificate template
	 *
	 * @access public
	 * @since  1.0.0
	 * @param string $field_key
	 * @return array $return_array
	 */
	public function get_certificate_font_settings( $field_key = '' ) {

		$return_array = array();

		if ( isset( $this->certificate_template_fields[ $field_key ]['font']['color'] ) && '' != $this->certificate_template_fields[ $field_key ]['font']['color'] ) {
			$return_array['font_color'] = $this->certificate_template_fields[ $field_key ]['font']['color'];
		} // End If Statement

		if ( isset( $this->certificate_template_fields[ $field_key ]['font']['family'] ) && '' != $this->certificate_template_fields[ $field_key ]['font']['family'] ) {
			$return_array['font_family'] = $this->certificate_template_fields[ $field_key ]['font']['family'];
		} // End If Statement

		if ( isset( $this->certificate_template_fields[ $field_key ]['font']['style'] ) && '' != $this->certificate_template_fields[ $field_key ]['font']['style'] ) {
			$return_array['font_style'] = $this->certificate_template_fields[ $field_key ]['font']['style'];
		} // End If Statement

		if ( isset( $this->certificate_template_fields[ $field_key ]['font']['size'] ) && '' != $this->certificate_template_fields[ $field_key ]['font']['size'] ) {
			$return_array['font_size'] = $this->certificate_template_fields[ $field_key ]['font']['size'];
		} // End If Statement

		return $return_array;

	} // End get_certificate_font_settings()

	/**
	 * certificate_link frontend output function for certificate link
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  string  $message html
	 * @param integer $course_id
	 * @return string $message html
	 */
	public function certificate_link( $message, $course_id = 0 ) {
		global $wp_query, $post;

		if ( empty( $course_id ) ) {

			global $course;

			if ( isset( $course->ID ) ) {

				$course_id = $course->ID;

			} else {

				$course_id = $post->ID;

			}
		}

		$certificate_template_id = get_post_meta( $course_id, '_course_certificate_template', true );

		if ( ! $certificate_template_id ) {
			return $message;
		}

		$my_account_page_id = intval( Sensei()->settings->settings['my_course_page'] );
		$view_link_courses  = Sensei()->settings->settings['certificates_view_courses'];
		$view_link_profile  = Sensei()->settings->settings['certificates_view_profile'];
		$is_viewable        = false;

		if ( ( 'page' == get_post_type( $my_account_page_id )
				|| is_singular( 'course' )
				|| isset( $wp_query->query_vars['course_results'] ) ) && $view_link_courses
				|| isset( $wp_query->query_vars['learner_profile'] ) && $view_link_profile ) {

			$is_viewable = true;

		} // End If Statement

		if ( ! $is_viewable ) {

			return $message;

		}

		if ( is_singular( 'course' ) ) {

			$certificate_url = $this->get_certificate_url( $post->ID, get_current_user_id() );

		} else {

			$certificate_url = $this->get_certificate_url( $course_id, get_current_user_id() );

		} // End If Statement

		if ( '' != $certificate_url ) {

			$classes = '';

			if ( 'page' == get_post_type( $my_account_page_id ) || isset( $wp_query->query_vars['learner_profile'] ) ) {

				$classes = 'button ';

			} // End If Statement

			$message = $message . '<a href="' . $certificate_url . '" class="' . $classes . 'sensei-certificate-link" title="' . esc_attr( __( 'View Certificate', 'sensei-certificates' ) ) . '">' . __( 'View Certificate', 'sensei-certificates' ) . '</a>';

		} // End If Statement

		return $message;

	} // End certificate_link()


	/**
	 * get_certificate_url gets url for certificate
	 *
	 * @access private
	 * @since  1.0.0
	 * @param  int $course_id course post id
	 * @param  int $user_id   course learner user id
	 * @return string $certificate_url certificate link
	 */
	private function get_certificate_url( $course_id, $user_id ) {

		$certificate_url = '';

		$args = array(
			'post_type'  => 'certificate',
			'author'     => $user_id,
			'meta_key'   => 'course_id',
			'meta_value' => $course_id,
		);

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {

			$count = 0;
			while ( $query->have_posts() ) {

				$query->the_post();
				$certificate_url = get_permalink();

			} // End While Loop
		} // End If Statement

		wp_reset_postdata();

		return $certificate_url;

	} // End get_certificate_url()


	/**
	 * create_columns adds columns for certificates
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $columns existing columns
	 * @return array $columns existing and new columns
	 */
	public function create_columns( $columns, $analysis ) {

		if ( 'user' == $analysis->view ) {
			$columns['certificates_link'] = __( 'Certificate', 'sensei-certificates' );
		}

		return $columns;

	} // End create_columns()


	/**
	 * populate_columns outputs column data
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $content output
	 * @param  int   $course_id course post id
	 * @param  int   $user_id  course learner user id
	 * @return array $content modified output
	 */
	public function populate_columns( $content, $item, $analysis ) {

		if ( 'user' == $analysis->view ) {
			$certificate_url = $this->get_certificate_url( $analysis->course_id, $item->user_id );
			$output          = '';

			if ( '' != $certificate_url ) {

				$output = '<a href="' . $certificate_url . '" class="sensei-certificate-link" title="' . esc_attr( __( 'View Certificate', 'sensei-certificates' ) ) . '">' . __( 'View Certificate', 'sensei-certificates' ) . '</a>';

			} // End If Statement

			$content['certificates_link'] = $output;
		}
		return $content;

	} // End populate_columns()


	/**
	 * Add some JavaScript inline to be output in the footer.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param string $code
	 * @return void
	 *
	 * @deprecated 2.0.4
	 */
	public function add_inline_js( $code ) {
		_deprecated_function( __METHOD__, '2.0.4' );

		$this->_inline_js .= "\n" . $code . "\n";

	} // End add_inline_js()


	/**
	 * Output any queued inline JS.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 *
	 * @deprecated 2.0.4
	 */
	public function output_inline_js() {
		_deprecated_function( __METHOD__, '2.0.4' );

		if ( $this->_inline_js ) {

			echo "<!-- Sensei LMS Certificates JavaScript-->\n<script type=\"text/javascript\">\njQuery(document).ready(function($) {";

			// Sanitize
			$this->_inline_js = wp_check_invalid_utf8( $this->_inline_js );
			$this->_inline_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $this->_inline_js );
			$this->_inline_js = str_replace( "\r", '', $this->_inline_js );

			// Output
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Custom sanitization above.
			echo $this->_inline_js;

			echo "});\n</script>\n";

			$this->_inline_js = '';

		} // End If Statement

	} // End output_inline_js()


	/**
	 * include_sensei_scripts includes Sensei scripts and styles on Certificates pages
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $allowed_post_types array of existing post types
	 * @return array $allowed_post_types array of additional post types
	 */
	public function include_sensei_scripts( $allowed_post_types ) {

		array_push( $allowed_post_types, 'certificate' );
		array_push( $allowed_post_types, 'certificate_template' );

		return $allowed_post_types;

	} // End include_sensei_scripts()


	/**
	 * reset_course_certificate deletes existing course certificate when the user resets a lesson
	 *
	 * @access public
	 * @since  1.0.7
	 * @param  int $user_id   User ID
	 * @param  int $lesson_id Lesson Post ID
	 * @return void
	 */
	public function reset_lesson_course_certificate( $user_id = 0, $lesson_id = 0 ) {

		if ( 0 < $user_id && 0 < $lesson_id ) {
			$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
			if ( $course_id ) {
				return $this->reset_course_certificate( $user_id, $course_id );
			}
		}
	}

	/**
	 * reset_course_certificate deletes existing course certificate when the user resets the course
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  int $user_id   User ID
	 * @param  int $course_id Course Post ID
	 * @return void
	 */
	public function reset_course_certificate( $user_id = 0, $course_id = 0 ) {

		if ( 0 < $user_id && 0 < $course_id ) {

			// Get a list of all Certificates for the Course for the User
			$certificates_array = array();

			$certificate_args   = array(
				'post_type'        => 'certificate',
				'numberposts'      => -1,
				'meta_query'       => array(
					'relation' => 'AND',
					array(
						'key'     => 'course_id',
						'value'   => $course_id,
						'compare' => '=',
					),
					array(
						'key'     => 'learner_id',
						'value'   => $user_id,
						'compare' => '=',
					),
				),
				'post_status'      => 'any',
				'suppress_filters' => true,
				'fields'           => 'ids',
			);
			$certificates_array = get_posts( $certificate_args );

			if ( is_array( $certificates_array ) && ! empty( $certificates_array ) ) {

				// Loop and delete all existing certificates
				foreach ( $certificates_array as $key => $certificate_id ) {

					$dataset_changes = wp_delete_post( $certificate_id, true );

				} // End For Loop
			} // End If Statement
		} // End If Statement

	} // End reset_course_certificate()


	/**
	 * certificates_user_settings_form form output
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  Object $user WordPress User object
	 * @return html
	 */
	public function certificates_user_settings_form( $user ) {

		// Check if certificates can be made public on this site
		$grant_access = Sensei()->settings->settings['certificates_public_viewable'];

		// Restrict to current logged in user only
		$current_user_id = get_current_user_id();
		if ( $user->ID == $current_user_id && is_user_logged_in() && true == (bool) $grant_access ) {

			$view_setting = get_user_option( 'sensei_certificates_view_by_public', $user->ID );
			?>
			<div id="certificates_user_settings">
				<form class="certificates_user_meta" method="POST" action="">
					<input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_certificates_user_meta_save_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_certificates_user_meta_save_noonce' ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_certificates_user_meta_save_noonce' ) ); ?>" />
								<p>
									<input type="checkbox" value="yes" name="certificates_user_public_view" <?php checked( $view_setting, true ); ?>/> <?php esc_html_e( 'Allow my Certificates to be publicly viewed', 'sensei-certificates' ); ?> <input type="submit" name="certificates_user_meta_save" class="certificates-submit complete" value="<?php echo esc_attr( apply_filters( 'sensei_certificates_save_meta_button', __( 'Save', 'sensei-certificates' ) ) ); ?>"/>
					</p>
				</form>
			</div>
			<?php
		} // End If Statement

	} // End certificates_user_settings_form()


	/**
	 * certificates_user_settings_save handles the save from the user meta form
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function certificates_user_settings_save() {
		global $current_user;
		// phpcs:ignore WordPress.VIP.ValidatedSanitizedInput.InputNotSanitized
		if (
			is_user_logged_in()
			&& isset( $_POST['certificates_user_meta_save'] )
			&& isset( $_POST['woothemes_sensei_certificates_user_meta_save_noonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Leave nonce value unmodified.
			&& wp_verify_nonce( wp_unslash( $_POST['woothemes_sensei_certificates_user_meta_save_noonce'] ), 'woothemes_sensei_certificates_user_meta_save_noonce' )
		) {

			// Update the user meta with the setting
			$current_user    = wp_get_current_user();
			$current_user_id = intval( $current_user->ID );

			if ( 0 < $current_user_id ) {

				$view_setting = false;
				if ( isset( $_POST['certificates_user_public_view'] ) && 'yes' === sanitize_key( $_POST['certificates_user_public_view'] ) ) {
					$view_setting = true;
				} // End If Statement

				$update_success = update_user_option( $current_user_id, 'sensei_certificates_view_by_public', $view_setting );

				$this->messages = '<div class="sensei-message tick">' . esc_html( apply_filters( 'sensei_certificates_user_settings_save', __( 'Your Certificates Public View Settings Saved Successfully.', 'sensei-certificates' ) ) ) . '</div>';

			} // End If Statement
		} // End If Statement

	} // End certificates_user_settings_save()


	/**
	 * certificates_user_settings_messages frontend notification messages
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function certificates_user_settings_messages() {
		$allowed_html = [
			'div'      => [
				'class'  => [],
			],
		];

		if ( isset( $this->messages ) && '' != $this->messages ) {
			echo wp_kses( $this->messages, $allowed_html );
		} // End If Statement

	} // End certificates_user_settings_message()

	/**
	 * Output the "View certificate" link for emails.
	 *
	 * @access private
	 * @since 2.0.0
	 *
	 * @param string $template The email template being rendered.
	 */
	public function email_certificate_link( $template ) {
		global $sensei_email_data;

		// Only handle emails for course completion.
		if ( ! ( 'learner-completed-course' === $template || 'teacher-completed-course' === $template ) ) {
			return;
		}

		// Get ID of learner who completed the course.
		$user_id = null;
		if ( 'learner-completed-course' === $template ) {
			$user_id = $sensei_email_data['user_id'];
		} else {
			$user_id = $sensei_email_data['learner_id'];
		}

		$course_id   = $sensei_email_data['course_id'];
		$template_id = get_post_meta( $course_id, '_course_certificate_template', true );

		// Only include the link if the certificate has a template.
		if ( $template_id ) {
			$certificate_url = $this->get_certificate_url( $course_id, $user_id );
			?>
			<p style="text-align: center !important">
				<a href="<?php echo esc_url( $certificate_url ); ?>" target="_blank">
					<?php echo esc_html__( 'View certificate', 'sensei-certificates' ); ?>
				</a>
			</p>
			<?php
		}
	}

	/**
	 * Enqueue block assets for the editing interface.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		$screen = get_current_screen();

		if ( $screen && 'page' === $screen->post_type ) {
			WooThemes_Sensei_Certificates::instance()->assets->enqueue(
				'sensei-certificates-block',
				'blocks/index.js'
			);
		}
	}

	/**
	 * Update the URL of the "View Certificate" button.
	 *
	 * @access private
	 *
	 * @param string $block_content The block content about to be appended.
	 * @param array  $block         The full block, including name and attributes.
	 *
	 * @return string Block HTML.
	 */
	public function update_view_certificate_button_url( $block_content, $block ): string {
		$class_name = 'view-certificate';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used if the learner completed the course.
		$course_id = isset( $_GET['course_id'] ) ? (int) $_GET['course_id'] : false;

		if (
			// Check that the course ID exists and that the user has completed the course.
			! $course_id
			|| ! get_current_user_id()
			|| 'course' !== get_post_type( $course_id )
			|| ! Sensei_Utils::user_completed_course( $course_id, get_current_user_id() )

			// Check that the block is a core/button and it contains the respective class name.
			|| ! isset( $block['blockName'] )
			|| 'core/button' !== $block['blockName']
			|| ! isset( $block['attrs']['className'] )
			|| false === strpos( $block['attrs']['className'], $class_name )
		) {
			return $block_content;
		}

		// Check if course has template and core method exists.
		if (
			! get_post_meta( $course_id, '_course_certificate_template', true )
			|| ! method_exists( 'Sensei_Blocks', 'update_button_block_url' )
		) {
			return '';
		}

		return Sensei_Blocks::update_button_block_url( $block_content, $block, $class_name,
			WooThemes_Sensei_Certificates::instance()->get_certificate_url( $course_id, get_current_user_id() ) );
	}

	/**
	 * Add certificate button to course completed template.
	 * This template is used when creating the page through Sensei Setup Wizard.
	 *
	 * @since 2.2.1
	 *
	 * @access private
	 *
	 * @param {array} $blocks Blocks array.
	 *
	 * @return {array} Blocks array.
	 */
	public function add_certificate_button_to_course_completed_template( $blocks ) {
		return $this->add_view_certificate_block_to_course_completed_actions( $blocks );
	}

	/**
	 * Add certificate button to Course Completed page, when already created.
	 * It's useful for cases where the user already created the Course Completed
	 * page, and then they activate this plugin.
	 *
	 * @since 2.2.1
	 *
	 * @access private
	 */
	public function add_certificate_button_to_current_course_completed_page() {
		$option_name = 'sensei_certificates_view_certificate_button_added';

		if ( get_option( $option_name ) ) {
			return;
		}

		update_option( $option_name, 1 );

		$page_id = isset( Sensei()->settings->settings['course_completed_page'] ) ? intval( Sensei()->settings->settings['course_completed_page'] ) : 0;
		if ( ! $page_id ) {
			return;
		}

		$page = get_post( $page_id );
		if ( ! $page ) {
			return;
		}

		$blocks = parse_blocks( $page->post_content );

		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => serialize_blocks(
					$this->add_view_certificate_block_to_course_completed_actions( $blocks )
				),
			]
		);
	}

	/**
	 * It adds the View Certificate button as inner block to the course completed actions.
	 * If it's not found or if the button is already added, it's not changed.
	 *
	 * @param array $blocks Parsed blocks.
	 *
	 * @return array Parsed blocks with the view certificate button.
	 */
	private function add_view_certificate_block_to_course_completed_actions( $blocks ) {
		$class_name = 'view-certificate';

		$blocks = array_map(
			function( $block ) use ( $class_name ) {
				/**
				 * Notice that we check the block through the innerContent and not through
				 * the anchor attribute directly, which is what we use to check the block
				 * variation. The reason is that the back-end doesn't contain this attribute
				 * when created through the block editor.
				 */
				if (
					'core/buttons' !== $block['blockName']
					|| ! isset( $block['innerContent'] )
					|| ! isset( $block['innerContent'][0] )
					|| false === strpos( $block['innerContent'][0], 'id="course-completed-actions"' )
				) {
					return $block;
				}

				// Check if action buttons contains the View Certificate button.
				foreach ( $block['innerBlocks'] as $inner_block ) {
					if (
						isset( $inner_block['attrs'] )
						&& isset( $inner_block['attrs']['className'] )
						&& false !== strpos( $inner_block['attrs']['className'], $class_name )
					) {
						return $block;
					}
				}

				// Add space for the button in the second to last item in the innerContent.
				array_splice( $block['innerContent'], count( $block['innerContent'] ) - 1, 0, [ null ] );

				// Add button to the innerBlocks.
				array_push(
					$block['innerBlocks'],
					[
						'blockName'    => 'core/button',
						'innerContent' => [ '<div class="wp-block-button ' . $class_name . '"><a class="wp-block-button__link">' . __( 'View Certificate', 'sensei-certificates' ) . '</a></div>' ],
						'attrs'        => [ 'className' => $class_name ],
					]
				);

				return $block;
			},
			$blocks
		);

		return $blocks;
	}

	/**
	 * Main Woothemes_Sensei_Certificates Instance
	 *
	 * Ensures only one instance of Woothemes_Sensei_Certificates is loaded or can be loaded.
	 *
	 * @since  2.0.0
	 * @return Woothemes_Sensei_Certificates
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
} // End Class
