<?php
/**
 * File containing Sensei_Certificates_Tool_Create_Certificates class.
 *
 * @package sensei-certificates
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Certificates_Tool_Create_Certificates class.
 *
 * @since 2.1.0
 */
class Sensei_Certificates_Tool_Create_Certificates implements Sensei_Tool_Interface {
	/**
	 * Job object.
	 *
	 * @var Sensei_Certificates_Create_Certificates
	 */
	private $job;

	/**
	 * Sensei_Certificates_Tool_Create_Certificates constructor.
	 */
	public function __construct() {
		$this->job = Sensei_Certificates_Create_Certificates::instance();

		add_action( 'sensei_tools_listing_after_' . $this->get_id(), array( $this, 'add_status_notice' ) );
	}

	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'sensei-certificates-create-certificates';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Create certificates', 'sensei-certificates' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Creates certificates for learners who have already completed Courses.', 'sensei-certificates' );
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		$this->job->setup();

		Sensei_Scheduler::instance()->schedule_job( $this->job );
		Sensei_Tools::instance()->add_user_message( __( 'Certificate records will be created in the background.', 'sensei-certificates' ) );
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available() {
		return ! $this->job->is_running();
	}

	/**
	 * Add the status notice when running.
	 */
	public function add_status_notice() {
		if ( ! $this->job->is_running() ) {
			return;
		}

		echo '<div class="notice inline notice-info"><p>';
		// translators: Placeholder %d is the percentage complete.
		echo esc_html( sprintf( __( 'This task is running in the background and is %d%% complete.', 'sensei-certificates' ), $this->job->get_progress() ) );
		echo '</p></div>';
	}
}
