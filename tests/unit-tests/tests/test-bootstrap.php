<?php
/**
 * Verify the extension test environment loads Certificates with Sensei LMS.
 *
 * @package Sensei_Certificates
 */

/**
 * Tests plugin initialization in the WordPress test environment.
 */
class WooThemes_Sensei_Certificates_Bootstrap_Test extends WP_UnitTestCase {

	/**
	 * Loading the extension with Sensei registers its certificate post type.
	 *
	 * @covers WooThemes_Sensei_Certificates::init
	 * @covers WooThemes_Sensei_Certificates::setup_certificates_post_type
	 */
	public function testInit_SenseiLoaded_RegistersCertificatePostType() {
		self::assertTrue( post_type_exists( 'certificate' ) );
	}
}
