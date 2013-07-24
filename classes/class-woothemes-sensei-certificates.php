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

		/**
		 * BACKEND
		 */
		if ( is_admin() ) {
			// Add Certificates Menu
			add_action( 'admin_menu', array( $this, 'certificates_admin_menu' ) );
			//add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			add_action( 'certificates_wrapper_container', array( $this, 'wrapper_container'  ) );
			// Extend user analasys columns
			//add_filter( 'analysis_user_profile_columns' );
			// Extend user analasys query
			//add_filter( 'analasys_user_profile_data_query' );
			// Extend analasys learners taking course columns
			//add_filter( 'analysis_users_taking_course_columns' );
			// Extend analasys learners taking course query
			//add_filter( 'analasys_users_taking_course_data_query' );
		}

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
			'description' 	=> __( 'Calculate and display an average Grade for the Course.', 'woothemes-sensei-certificates' ),
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
		?><?php screen_icon( 'woothemes-sensei' ); ?>
			<h2><?php _e( 'Certificates', 'woothemes-sensei-certificates' ); ?></h2>
			<p class="powered-by-woo"><?php _e( 'Powered by', 'woothemes-sensei-certificates' ); ?><a href="http://www.woothemes.com/" title="WooThemes"><img src="<?php echo $woothemes_sensei->plugin_url; ?>assets/images/woothemes.png" alt="WooThemes" /></a></p>
			<br class="clear"><?php
	} // End certificates_default_nav()

} // End Class