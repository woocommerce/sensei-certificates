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
	public $plugin_url;
	public $plugin_path;
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct( $file ) {
		$this->plugin_url = trailingslashit( plugins_url( '', $file ) );
		$this->plugin_path = plugin_dir_path( $file );


		// Hook onto Sensei settings and load a new tab with settings for extension
		add_filter( 'sensei_settings_tabs', array( $this, 'certificates_settings_tabs' ) );
		add_filter( 'sensei_settings_fields', array( $this, 'certificates_settings_fields' ) );

		// Setup post type
		add_action( 'init', array( $this, 'setup_extension_post_type' ), 110 );
		add_filter('manage_edit-certificate_columns', array( $this, 'post_type_custom_column_headings' ) );
		add_action('manage_certificate_posts_custom_column', array( $this, 'post_type_custom_column_content' ), 10, 2 );


		/**
		 * FRONTEND
		 */
		// Add View certificate link on Learner Profile.
		add_action( 'sensei_course_after_profile', array( $this, 'function_to_add' ) );
		// Add View certificate link to My Courses
		add_action( 'sensei_item_after_my_courses_completed', array( $this, 'function_to_add' ) );
		// Add View Ceritificate link to Single Course page
		add_action( 'sensei_after_main_content', array( $this, 'function_to_add' ), 9 );
		// Add View Ceritificate link to Course Completed page
		add_action( 'sensei_after_course_completed', array( $this, 'function_to_add' ) );

		// Create certificate endpoint and handle generation of pdf certificate
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );

		/**
		 * BACKEND
		 */
		if ( is_admin() ) {
			// Add Certificates Menu
			//add_action( 'admin_menu', array( $this, 'certificates_admin_menu' ) );
			//add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			//add_action( 'certificates_wrapper_container', array( $this, 'wrapper_container'  ) );
		}

		// Generate certificate hash when course is completed.
		add_action( 'sensei_log_activity_after', array( $this, 'generate_certificate_number' ), 10, 2 );
		// Text to display on certificate
		add_action( 'sensei_certificates_before_pdf_output', array( $this, 'certificate_text' ), 10, 2 );
		// Generate certificates for past completed courses upon installation
		register_activation_hook( $file, array( $this, 'install' ) );

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
			'description'	=> __( 'Options for the Certificate Extension.', 'woothemes-sensei-certificates' )
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
		$fields['certificates_view_courses'] = array(
			'name' 			=> __( 'View in Courses', 'woothemes-sensei-certificates' ),
			'description' 	=> __( 'Show a view certificate link in the single Course page and the My Courses page.', 'woothemes-sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);
		$fields['certificates_view_profile'] = array(
			'name' 			=> __( 'View in Learner Profile', 'woothemes-sensei-certificates' ),
			'description' 	=> __( 'Show a list of all the Learner Certificates in their Learner Profile page.', 'woothemes-sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);
		$fields['certificates_public_viewable'] = array(
			'name' 			=> __( 'Public Certificate', 'woothemes-sensei-certificates' ),
			'description' 	=> __( 'Allow the Learner to share their Certificate with the public.', 'woothemes-sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);
		$fields['certificates_show_course_grade'] = array(
			'name' 			=> __( 'Course Grade', 'woothemes-sensei-certificates' ),
			'description' 	=> __( 'Calculate and display an average Grade for the Course on the Certificate.', 'woothemes-sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);

		return $fields;

	} // End certificates_settings_fields()

	/**
	 * Setup the certificate post type, it's admin menu item and the appropriate labels and permissions.
	 * @since  1.0.0
	 * @uses  global $woothemes_sensei
	 * @return void
	 */
	public function setup_extension_post_type () {

		global $woothemes_sensei;

		$args = array(
		    'labels' => array(
			    'name' => sprintf( _x( '%s', 'post type general name', 'woothemes-sensei' ), 'Certificates' ),
			    'singular_name' => sprintf( _x( '%s', 'post type singular name', 'woothemes-sensei' ), 'Certificate' ),
			    'add_new' => sprintf( _x( 'Add New %s', 'post type add_new', 'woothemes-sensei' ), 'Certificate' ),
			    'add_new_item' => sprintf( __( 'Add New %s', 'woothemes-sensei' ), 'Certificate' ),
			    'edit_item' => sprintf( __( 'Edit %s', 'woothemes-sensei' ), 'Certificate' ),
			    'new_item' => sprintf( __( 'New %s', 'woothemes-sensei' ), 'Certificate' ),
			    'all_items' => sprintf( __( '%s', 'woothemes-sensei' ), 'Certificates' ),
			    'view_item' => sprintf( __( 'View %s', 'woothemes-sensei' ), 'Certificate' ),
			    'search_items' => sprintf( __( 'Search %s', 'woothemes-sensei' ), 'Certificates' ),
			    'not_found' =>  sprintf( __( 'No %s found', 'woothemes-sensei' ), strtolower( 'Certificates' ) ),
			    'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'woothemes-sensei' ), strtolower( 'Certificates' ) ),
			    'parent_item_colon' => '',
			    'menu_name' => sprintf( __( '%s', 'woothemes-sensei' ), 'Certificates' )
			),
		    'public' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true,
		    'show_in_menu' => 'edit.php?post_type=lesson',
		    'query_var' => true,
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_extension_slug', 'certificate' ) ) , 'with_front' => true, 'feeds' => true, 'pages' => true ),
		    'map_meta_cap' => true,
		    'has_archive' => false,
		    'hierarchical' => false,
		    'menu_icon' => esc_url( $woothemes_sensei->plugin_url . 'assets/images/certificate.png' ),
		    'supports' => array( 'title', 'custom-fields' )
		);

		register_post_type( 'certificate', $args );

	} // End setup_extension_post_type()

	/**
	 * post_type_custom_column_headings function.
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	function post_type_custom_column_headings( $defaults ) {
		unset( $defaults['date'] );
		$defaults['learner'] = __( 'Learner', 'woothemes-sensei-certificates' );
		$defaults['course'] = __( 'Course', 'woothemes-sensei-certificates' );
		$defaults['date_completed'] = __( 'Date Completed', 'woothemes-sensei-certificates' );
		$defaults['actions'] = __( 'Actions', 'woothemes-sensei-certificates' );
    	return $defaults;
	} // End post_type_custom_column_headings()

	/**
	 * post_type_custom_column_content function.
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	function post_type_custom_column_content( $column_name, $post_ID ) {
		$user_id = get_post_meta( $post_ID, $key = 'learner_id', true );
		$course_id = get_post_meta( $post_ID, $key = 'course_id', true );
		$user = get_userdata( $user_id );
		$course = get_post( $course_id );
		$course_end_date = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => intval( $course_id ), 'user_id' => intval( $user_id ), 'type' => 'sensei_course_end', 'field' => 'comment_date' ) );
		$certificate_hash = esc_html( substr( md5( $course_id . $user_id ), -8 ) );

		switch ( $column_name ) {
			case "learner" :
				echo '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'user' => intval( $user_id ), 'course_id' => intval( $course_id ) ), admin_url( 'edit.php?post_type=lesson' ) ) . '">'.$user->user_login.'</a>';
				break;
			case "course" :
				echo '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'course_id' => intval( $course_id ) ), admin_url( 'edit.php?post_type=lesson' ) ) . '">'.$course->post_title.'</a>';
				break;
			case "date_completed" :
				echo $course_end_date;
				break;
			case "actions" :
				echo '<a href="' . add_query_arg( array( 'certificate' => '1', 'hash' => $certificate_hash ), site_url() ) . '" target="_blank">'. __( 'View Certificate', 'woothemes-sensei-certificates' ) . '</a>';
				break;
		}
	} // End post_type_custom_column_content()

	/**
	 * Generate unique certificate hash and save as comment.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function generate_certificate_number( $args, $data ) {
		if ( isset( $args['type'] ) && $args['type'] == 'sensei_course_end' ) {
			$cert_args = array(
				'post_id' => $args['post_id'],
				'username' => $args['username'],
				'user_email' => $args['user_email'],
				'user_url' => $args['user_url'],
				'data' => substr( md5( $args['post_id'] . $args['user_id'] ), -8 ), // Use last 8 chars of hash only
				'type' => 'sensei_certificate', /* FIELD SIZE 20 */
				'parent' => 0,
				'user_id' => $args['user_id'],
				'action' => 'update'
			);
			$time = current_time('mysql');
			$data = array(
				'comment_post_ID' => intval( $args['post_id'] ),
				'comment_author' => sanitize_user( $args['username'] ),
				'comment_author_email' => sanitize_email( $args['user_email'] ),
				'comment_author_url' => esc_url( $args['user_url'] ),
				'comment_content' => esc_html( substr( md5( $args['post_id'] . $args['user_id'] ), -8 ) ),
				'comment_type' => 'sensei_certificate',
				'comment_parent' => 0,
				'user_id' => intval( $args['user_id'] ),
				'comment_date' => $time,
				'comment_approved' => 1,
			);
			//$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $cert_args );

			// custom post type
			$cert_args = array(
				'post_author' => intval( $args['user_id'] ),
				'post_title' => esc_html( substr( md5( $args['post_id'] . $args['user_id'] ), -8 ) ),
				'post_name' => esc_html( substr( md5( $args['post_id'] . $args['user_id'] ), -8 ) ),
				'post_type' => 'certificate',
				'post_status'   => 'publish'
			);
			$post_id = wp_insert_post( $cert_args, $wp_error = false );
			if ( ! is_wp_error( $post_id ) ) {
				add_post_meta( $post_id, 'course_id', intval( $args['post_id'] ) );
				add_post_meta( $post_id, 'learner_id', intval( $args['user_id'] ) );
				add_post_meta( $post_id, 'certificate_hash', esc_html( substr( md5( $args['post_id'] . $args['user_id'] ), -8 ) ) );
			}
		}

	} // End generate_certificate_number()

	/**
	 * Check if certificate is viewable
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function can_view_certificate() {
		global $woothemes_sensei, $wp;

		// Check if student can only view certificate
		$grant_access = $woothemes_sensei->settings->settings['certificates_public_viewable'];
		if ( ! $grant_access ) {
			$grant_access = current_user_can( 'manage_options' ) ? true : false;
		}

		if ( ! $grant_access )
			return false;

		if ( strlen( $wp->query_vars['hash'] ) <> 8 )
			return false;

		return true;
	} // End can_view_certificate

	/**
	 * Download the certificate
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function download_certificate() {
		global $woothemes_sensei, $wp;
		if ( $this->can_view_certificate() ) {
			// Generate the certificate here
			require_once( 'class-woothemes-sensei-pdf-certificate.php' );
			$pdf = new WooThemes_Sensei_PDF_Certificate( $wp->query_vars['hash'] );
			$pdf->generate_pdf();
		}
	} // End generate_certificate

	/**
	 * Add text to the certificate
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function certificate_text( $pdf_certificate, $fpdf ) {
		global $woothemes_sensei;
		$show_border = apply_filters( 'woothemes_sensei_certificates_show_border', 0 );

		$start_position = 200;

		// Logo image
		$pdf_certificate->image_field( $fpdf, esc_url( apply_filters( 'woothemes_sensei_certificates_logo_url', $this->plugin_path . '/assets/images/certificate-logo.png' ) ), $show_border, array( 490, 75, 75, 75 ) );
		// Intro text
		$pdf_certificate->text_field( $fpdf, __( 'Certificate of Completion', 'woothemes-sensei-certificates' ), $show_border, array( 250, $start_position, 100, 20 ) );

		$args = array(
			'post_type' => 'certificate',
			'meta_key' => 'certificate_hash',
			'meta_value' => $pdf_certificate->hash
		);

		// Find certificate based on hash
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$query->the_post();
			$certificate_id = $query->posts[0]->ID;
		}
		wp_reset_query();

		// Get Student Data
		$user_id = get_post_meta( $certificate_id, 'learner_id', true );
		$student = get_userdata( $user_id );
		$student_name = $student->first_name . ' ' . $student->last_name;

		// Get Course Data
		$course_id = get_post_meta( $certificate_id, 'course_id', true );
		$course = $woothemes_sensei->post_types->course->course_query( -1, 'usercourses', $course_id );
		$course = $course[0];
		$course_end_date = $course_end_date = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_id, 'user_id' => $user_id, 'type' => 'sensei_course_end', 'field' => 'comment_date' ) );

		// This is the certify that
		$start_position += 50;
		$pdf_certificate->textarea_field( $fpdf, __( 'This is to certify that', 'woothemes-sensei-certificates' ), $show_border, array( 300, $start_position, 900, 400 ) );
		// Student Name
		$start_position += 100;
		$pdf_certificate->text_field_userdata( $fpdf, $student_name, $show_border, array( 400, $start_position, 100, 20 ) );
		// Has completed the
		$start_position += 50;
		$pdf_certificate->textarea_field( $fpdf, __( 'Has completed the course', 'woothemes-sensei-certificates' ), $show_border, array( 230, $start_position, 900, 400 ) );
		// Course Name
		$start_position += 100;
		$pdf_certificate->text_field_userdata( $fpdf, $course->post_title, $show_border, array( 350, $start_position, 100, 20 ) );
		// Course end date
		$start_position += 75;
		$pdf_certificate->text_field_userdata( $fpdf, date( 'jS F Y', strtotime( $course_end_date ) ), $show_border, array( 300, $start_position, 100, 20 ) );
		// At [WEBSITE NAME]
		$start_position += 50;
		$pdf_certificate->textarea_field( $fpdf, sprintf( __( 'At %s', 'woothemes-sensei-certificates' ), get_bloginfo( 'name' ) ), $show_border, array( 350, $start_position, 900, 400 ) );
	} // End certificate_text

	/**
	 * Add public Query Vars
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'certificate';
		$vars[] = 'hash';
		return apply_filters( 'woothemes_sensei_certificates_query_vars', $vars );
	}

	/**
	 * Add endpoint
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function add_endpoint(){
		add_rewrite_rule('^certificate/([^/]*)/?','index.php?certificate=1&hash=$matches[1]','top');
	}

	/**
	 * Listen for certificate request
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function sniff_requests(){
		global $wp;
		if ( isset( $wp->query_vars['certificate'] ) && isset( $wp->query_vars['hash'] ) ) {
			$this->download_certificate();
			exit;
		}
	}

} // End Class