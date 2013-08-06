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
		// Load the View Certificate template via shortcode
		add_shortcode( 'certificate', array( $this, 'shortcode_certificate' ) );

		/**
		 * BACKEND
		 */
		if ( is_admin() ) {
			// Add Certificates Menu
			add_action( 'admin_menu', array( $this, 'certificates_admin_menu' ) );
			// Show install certificate page admin notice
			add_action( 'admin_print_styles', array( $this, 'admin_notices_styles' ) );
			add_action( 'settings_before_form', array( $this, 'install_pages_output' ) );
			register_activation_hook( $file, array( $this, 'activate_sensei_certificates' ) );
			//add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
			add_action( 'certificates_wrapper_container', array( $this, 'wrapper_container'  ) );
		}

		// Generate certificate hash when course is completed.
		add_action( 'sensei_log_activity_after', array( $this, 'generate_certificate_number' ), 10, 2 );
		// Generate the certificate
		add_action( 'sensei_certificate', array( $this, 'generate_certificate' ) );

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
		$pages_array = array();
		$pages_array = $this->pages_array();
		$fields['certificates_enabled'] = array(
			'name' 			=> __( 'Enable Certificates', 'woothemes-sensei-certificates' ),
			'description' 	=> __( 'A description for the extension setting.', 'woothemes-sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);
		$fields['certificates_page'] = array(
			'name' => __( 'View Certificate Page', 'woothemes-sensei' ),
			'description' => __( 'The page to use to display certificates.', 'woothemes-sensei-certificates' ),
			'type' => 'select',
			'default' => '',
			'required' => 1,
			'section' => 'certificate-settings',
			'options' => $pages_array
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
	 * Return an array of pages.
	 * @access private
	 * @since  1.0.0
	 * @return void
	 */
	private function pages_array() {
		// REFACTOR - Transform this into a field type instead.
		// Setup an array of portfolio gallery terms for a dropdown.
		$args = array( 'echo' => 0, 'hierarchical' => 1, 'sort_column' => 'post_title', 'sort_order' => 'ASC' );
		$pages_dropdown = wp_dropdown_pages( $args );
		$page_items = array();

		// Quick string hack to make sure we get the pages with the indents.
		$pages_dropdown = str_replace( "<select name='page_id' id='page_id'>", '', $pages_dropdown );
		$pages_dropdown = str_replace( '</select>', '', $pages_dropdown );
		$pages_split = explode( '</option>', $pages_dropdown );

		$page_items[] = __( 'Select a Page:', 'woothemes-sensei-certificates' );

		foreach ( $pages_split as $k => $v ) {
		    $id = '';
		    // Get the ID value.
		    preg_match( '/value="(.*?)"/i', $v, $matches );

		    if ( isset( $matches[1] ) ) {
		        $id = $matches[1];
		        $page_items[$id] = trim( strip_tags( $v ) );
		    } // End If Statement
		} // End For Loop

		$pages_array = $page_items;

		return $pages_array;
	} // End pages_array()

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
	 * admin_notices_styles function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_notices_styles() {
		global $woothemes_sensei;
		// Installed notices
	    if ( get_option( 'sensei_certificates_installed', 1 ) == 1 ) {
	    			print "Im HERE";
	    	wp_enqueue_style( 'sensei-activation' );

	    	if ( get_option( 'skip_install_sensei_certficate_pages' ) != 1 && $woothemes_sensei->settings->settings['certificates_page'] < 1 && ! isset( $_GET['install_sensei_certificate_pages'] ) && ! isset( $_GET[ 'skip_install_sensei_certificate_pages' ] ) ) {
	    		add_action( 'admin_notices', array( $this, 'admin_install_notice' ) );
	    	} elseif ( ! isset( $_GET['page'] ) || $_GET['page'] != 'woothemes-sensei-settings' ) {
	    		add_action( 'admin_notices', array( &$this, 'admin_installed_notice' ) );
	    	} // End If Statement

	    } // End If Statement
	} // End admin_notices_styles()

	/**
	 * admin_install_notice function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_install_notice() {
	    ?>
	    <div id="message" class="updated sensei-message sensei-connect">
	    	<div class="squeezer">
	    		<h4><?php _e( '<strong>Welcome to Sensei Certificates</strong> &#8211; You\'re almost ready view some certificates :)', 'woothemes-sensei-certificates' ); ?></h4>
	    		<p class="submit"><a href="<?php echo add_query_arg('install_sensei_certificate_pages', 'true', admin_url('edit.php?post_type=lesson&page=woothemes-sensei-settings')); ?>" class="button-primary"><?php _e( 'Install Sensei Certificates Pages', 'woothemes-sensei-certificates' ); ?></a> <a class="skip button" href="<?php echo add_query_arg('skip_install_sensei_certificate_pages', 'true', admin_url('edit.php?post_type=lesson&page=woothemes-sensei-settings')); ?>"><?php _e('Skip Certificates setup', 'woothemes-sensei-certificates'); ?></a></p>
	    	</div>
	    </div>
	    <?php
	} // End admin_install_notice()


	/**
	 * admin_installed_notice function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_installed_notice() {
	    ?>
	    <div id="message" class="updated sensei-message sensei-connect">
	    	<div class="squeezer">
	    		<h4><?php _e( '<strong>Sensei Certificates has been installed</strong> &#8211; You\'re ready to viewing certificates :)', 'woothemes-sensei-certificates' ); ?></h4>

	    		<p class="submit"><a href="<?php echo admin_url('edit.php?post_type=lesson&page=woothemes-sensei-settings'); ?>" class="button-primary"><?php _e( 'Settings', 'woothemes-sensei' ); ?></a> <a class="docs button-primary" href="http://www.woothemes.com/sensei-docs/"><?php _e('Documentation', 'woothemes-sensei-certificates'); ?></a></p>

	    		<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/sensei/" data-text="A premium Learning Management plugin for #WordPress that helps you teach courses online. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="Sensei">Tweet</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
	    	</div>
	    </div>
	    <?php

	    // Set installed option
	    update_option('sensei_certificates_installed', 0);
	} // End admin_installed_notice()

	/**
	 * install_pages_output function.
	 *
	 * Handles installation of the 2 pages needs for courses and my courses
	 *
	 * @access public
	 * @return void
	 */
	function install_pages_output() {
		global $woothemes_sensei;

		// Install/page installer
	    $install_complete = false;

	    // Add pages button
	    if (isset($_GET['install_sensei_certificate_pages']) && $_GET['install_sensei_certificate_pages']) {

			$woothemes_sensei->admin->create_page( esc_sql( _x('certificate', 'page_slug', 'woothemes-sensei-certificates') ), $woothemes_sensei->admin->token . '_view_certificate_page_id', __('Certificate', 'woothemes-sensei-certificates'), '[certificate]' );
	    	update_option('skip_install_sensei_certificate_pages', 1);
	    	$install_complete = true;

		// Skip button
	    } elseif (isset($_GET['skip_install_sensei_certificate_pages']) && $_GET['skip_install_sensei_certificate_pages']) {

	    	update_option('skip_install_sensei_certificate_pages', 1);
	    	$install_complete = true;

	    }

		if ($install_complete) {
			?>
	    	<div id="message" class="updated sensei-message sensei-connect">
				<div class="squeezer">
					<h4><?php _e( '<strong>Congratulations!</strong> &#8211; Sensei Certificates has been installed and setup. Enjoy :)', 'woothemes-sensei' ); ?></h4>
					<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/sensei/" data-text="A premium Learning Management plugin for #WordPress that helps you create courses. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="Sensei">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
				</div>
			</div>
			<?php

			// Flush rules after install
			flush_rewrite_rules( false );

			// Set installed option
			update_option('sensei_certificates_installed', 0);
		}

	} // End install_pages_output()

	/**
	 * Run on activation of the plugin.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function activate_sensei_certificates () {
		update_option( 'skip_install_sensei_certificate_pages', 0 );
		update_option( 'sensei_certificates_installed', 1 );
	} // End activate_sensei()

	/**
	 * Load the view certificate template.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function shortcode_certificate() {
		global $woothemes_sensei;
		ob_start();
		$woothemes_sensei->frontend->sensei_get_template( 'certificate.php', array(), '', $this->plugin_path . 'templates/' );
		$content = ob_get_clean();
		return $content;
	} // End shortcode_view_certificate()

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
		global $woothemes_sensei;

		// Check if student can only view certificate
		$grant_access = $woothemes_sensei->settings->settings['certificates_public_viewable'];
		if ( ! $grant_access ) {
			$grant_access = current_user_can( 'manage_options' ) ? true : false;
		}

		if ( ! $grant_access )
			return false;

		if ( ! isset( $_GET['certificate'] ) )
			return false;

		if( isset( $_GET['certificate'] ) && strlen( $_GET['certificate'] ) <> 8 )
			return false;

		return true;
	} // End can_view_certificate

	/**
	 * Generate the certificate
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function generate_certificate() {
		global $woothemes_sensei;
		if ( $this->can_view_certificate() ) {
			// Generate the certificate here
			require_once( 'class-woothemes-sensei-certificate.php' );
			$pdf = new WooThemes_Sensei_Certificate( $_GET['certificate'] );
			$pdf->generate_pdf();
		}
	} // End generate_certificate



} // End Class