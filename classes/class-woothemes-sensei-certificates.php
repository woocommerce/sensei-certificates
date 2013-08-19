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
		// Download certificate
		//add_action( 'init', array( $this, 'download_certificate' ) );

		// Create certificate endpoint and handle generation of pdf certificate
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );

		/**
		 * BACKEND
		 */
		if ( is_admin() ) {
			// Add Certificates Menu
			add_action( 'admin_menu', array( $this, 'certificates_admin_menu' ) );
			add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			add_action( 'certificates_wrapper_container', array( $this, 'wrapper_container'  ) );
		}

		// Generate certificate hash when course is completed.
		add_action( 'sensei_log_activity_after', array( $this, 'generate_certificate_number' ), 10, 2 );
		// Text to display on certificate
		add_action( 'sensei_certificates_before_pdf_output', array( $this, 'certificate_text'), 10, 2 );
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
	 * install function to generate cert hashes
	 * @since  1.0.0
	 * @return string
	 */
	public function install() {
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
					$certificate_page_id = intval( $woothemes_sensei->settings->settings['certificates_page'] );
					$certificate_hash = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_item->ID, 'user_id' => $user_item->ID, 'type' => 'sensei_certificate', 'field' => 'comment_content' ) );
					if ( ! $certificate_hash ) {
						$cert_args = array(
							'post_id' => $course_item->ID,
							'username' => $user_item->user_login,
							'user_email' => $user_item->user_email,
							'user_url' => $user_item->user_url,
							'data' => substr( md5( $course_item->ID . $user_item->ID ), -8 ), // Use last 8 chars of hash only
							'type' => 'sensei_certificate', /* FIELD SIZE 20 */
							'parent' => 0,
							'user_id' => $user_item->ID,
							'action' => 'update'
						);
						$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $cert_args );
					}
				}
			}
		}

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
	 * certificates_admin_menu function.
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function certificates_admin_menu() {
	    global $menu, $woocommerce;
	    if ( current_user_can( 'manage_options' ) )
	    	$certificates_page = add_submenu_page('edit.php?post_type=lesson', __( 'Certificates', 'woothemes-sensei-certificates' ),  __( 'Certificates', 'woothemes-sensei-certificates'), 'manage_options', 'sensei_certificates', array( $this, 'certificates_page' ) );
	} // End certificates_admin_menu()

	/**
	 * enqueue_styles function.
	 *
	 * @description Load in CSS styles where necessary.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		global $woothemes_sensei;
		wp_enqueue_style( $woothemes_sensei->token . '-admin' );
		wp_register_style( 'woothemes-sensei-certificates-admin', $this->plugin_url . 'assets/css/admin.css' );
		wp_enqueue_style( 'woothemes-sensei-certificates-admin' );
	} // End enqueue_styles()

	/**
	 * certificates_page function.
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function certificates_page() {
		$this->certificates_default_view();
	} // End certificates_page()

	/**
	 * certificates_default_view default view for analysis page
	 * @since  1.0.0
	 * @return void
	 */
	public function certificates_default_view( $type = '' ) {

		// Wrappers
		do_action( 'certificates_before_container' );
		do_action( 'certificates_wrapper_container', 'top' );

		$this->certificates_default_nav();
		?>
			<div id="poststuff" class="sensei-certificates-wrap">
				<div class="sensei-certificates-main">
					<?php
						require_once "class-woothemes-sensei-certificates-list-table.php";
						$sensei_analysis_overview = new WooThemes_Sensei_Certificates_List_Table();
						$sensei_analysis_overview->display();
					?>
				</div>
			</div>
		<?php

		do_action( 'certificates_wrapper_container', 'bottom' );
		do_action( 'certificates_after_container' );
	} // End certificates_default_view

	/**
	 * wrapper_container wrapper for analysis area
	 * @since  1.0.0
	 * @param $which string
	 * @return void
	 */
	public function wrapper_container( $which ) {
		global $woothemes_sensei;
		if ( 'top' == $which ) {
			?><div id="woothemes-sensei" class="wrap <?php echo esc_attr( $woothemes_sensei->token ); ?>"><?php
		} elseif ( 'bottom' == $which ) {
			?></div><!--/#woothemes-sensei--><?php
		} // End If Statement
	} // End wrapper_container()

	/**
	 * analysis_default_nav default nav area for analysis
	 * @since  1.0.0
	 * @return void
	 */
	public function certificates_default_nav() {
		global $woothemes_sensei;
		?><?php screen_icon( 'woothemes-sensei-certificates' ); ?>
			<h2><?php _e( 'Certificates', 'woothemes-sensei-certificates' ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei-certificates' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
	} // End certificates_default_nav()

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
			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $cert_args );
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
		$show_border = apply_filters( 'woothemes_sensei_certificates_show_border', 0 );
		// Intro text
		$pdf_certificate->text_field( $fpdf, __( 'Certificate of Completion', 'woothemes-sensei-certificates' ), $show_border, array( 170, 150, 100, 20 ) );
		// voucher message text, this is multi-line, so it's handled specially
		$pdf_certificate->textarea_field( $fpdf, sprintf( __( 'This is to certify that %s has completed the %s online course on %s', 'woothemes-sensei-certificates' ), 'Gerhard Potgieter', 'WooThemes 101', '14/08/2013' ), $show_border, array( 100, 300, 900, 400) );
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