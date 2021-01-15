<?php
/**
 * File containing Sensei_Certificates_Tool_Create_Default_Example_Template class.
 *
 * @package sensei-certificates
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Certificates_Tool_Create_Default_Example_Template class.
 *
 * @since 2.1.0
 */
class Sensei_Certificates_Tool_Create_Default_Example_Template implements Sensei_Tool_Interface {
	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'sensei-certificates-create-default-example-template';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Create default example certificate template', 'sensei-certificates' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Recreates the example certificate template and sets all courses to use it.', 'sensei-certificates' );
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		sensei_create_master_certificate_template();

		Sensei_Tools::instance()->add_user_message( __( 'Example certificate template has been created and all courses have been set to use it.', 'sensei-certificates' ) );
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available() {
		return true;
	}
}
