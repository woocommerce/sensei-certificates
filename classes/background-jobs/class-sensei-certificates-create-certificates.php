<?php
/**
 * File containing the class Sensei_Certificates_Create_Certificates.
 *
 * @package sensei-certificates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Sensei_Certificates_Create_Certificates handles creating missing certificates for learners who have completed
 * courses.
 */
class Sensei_Certificates_Create_Certificates implements Sensei_Background_Job_Interface {
	const NAME            = 'sensei_certificates_create_certificates';
	const BATCH_SIZE      = 5;
	const STATE_TRANSIENT = 'sensei_certificates_job_create_certificates';

	/**
	 * Whether the job is complete.
	 *
	 * @var bool
	 */
	private $is_complete = false;

	/**
	 * Singleton instance.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Certificates_Create_Certificates constructor.
	 */
	private function __construct() {}

	/**
	 * Get singleton instance of class.
	 *
	 * @return Sensei_Certificates_Create_Certificates
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the action name for the scheduled job.
	 *
	 * @return string
	 */
	public function get_name() {
		return self::NAME;
	}

	/**
	 * Get the arguments to run with the job.
	 *
	 * @return array
	 */
	public function get_args() {
		return array();
	}

	/**
	 * Run the job.
	 */
	public function run() {
		$offset = $this->get_offset();

		if ( ! sensei_update_users_certificate_data( self::BATCH_SIZE, $offset ) ) {
			$this->set_offset( $offset + self::BATCH_SIZE );
		} else {
			$this->end();
		}
	}

	/**
	 * After the job runs, check to see if it needs to be re-queued for the next batch.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return $this->is_complete;
	}

	/**
	 * Set up the job.
	 */
	public function setup() {
		$this->set_offset( 0 );
	}

	/**
	 * Clean up when a job is finished or has been cancelled.
	 */
	public function end() {
		$this->delete_state();
		$this->is_complete = true;
	}

	/**
	 * Get the progress of the job.
	 *
	 * @return false|float
	 */
	public function get_progress() {
		if ( ! $this->has_state() ) {
			return false;
		}

		$user_count  = count_users();
		$total_users = $user_count['total_users'];
		$done        = min( $this->get_offset(), $user_count );

		return round( ( $done / $total_users ) * 100 );
	}

	/**
	 * Is the job currently running?
	 *
	 * @return bool True if job is running.
	 */
	public function is_running() {
		return false !== $this->has_state();
	}

	/**
	 * Set the offset.
	 *
	 * @param int $offset Offset for job.
	 */
	private function set_offset( $offset ) {
		$state           = $this->get_state();
		$state['offset'] = (int) $offset;

		$this->save_state( $state );
	}

	/**
	 * Get the offset.
	 *
	 * @return int
	 */
	private function get_offset() {
		$state = $this->get_state();

		return (int) $state['offset'];
	}

	/**
	 * Get the state of the job.
	 */
	private function get_state() {
		$state = get_transient( self::STATE_TRANSIENT );

		$default_state = array(
			'offset' => 0,
		);

		if ( $state ) {
			return array_merge( $default_state, (array) $state );
		}

		return $default_state;
	}

	/**
	 * Set the state for the job.
	 *
	 * @param array $state State to set.
	 */
	private function save_state( $state ) {
		set_transient( self::STATE_TRANSIENT, $state, HOUR_IN_SECONDS );
	}

	/**
	 * Delete the state and end the job.
	 */
	private function delete_state() {
		delete_transient( self::STATE_TRANSIENT );
	}

	/**
	 * Check if the job has been started and state is set.
	 */
	private function has_state() {
		$state = get_transient( self::STATE_TRANSIENT );

		return false !== $state;
	}
}
