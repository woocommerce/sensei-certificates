<?php
/**
 * File containing the WooThemes_Sensei_Certificates_View_Certificate_Link_Block class.
 *
 * @package sensei-certificates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooThemes_Sensei_Certificates_View_Certificate_Link_Block
 */
class WooThemes_Sensei_Certificates_View_Certificate_Link_Block {

	/**
	 * Sensei_Course_Overview_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-certificates/view-certificate-link',
			[
				'render_callback' => [ $this, 'render' ],
			],
			WooThemes_Sensei_Certificates::instance()->assets->src_path( 'blocks/view-certificate-link' )
		);
	}

	/**
	 * Renders View Certificate Link block on the frontend.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner block content.
	 *
	 * @return string HTML of the block.
	 */
	public function render( array $attributes, string $content ): string {
		$course_id = \Sensei_Utils::get_current_course();

		// Check that the user has completed the course and it has a certificate
		// template.
		if (
			! $course_id
			|| ! get_current_user_id()
			|| 'course' !== get_post_type( $course_id )
			|| ! get_post_meta( $course_id, '_course_certificate_template', true )
			|| ! Sensei_Utils::user_completed_course( $course_id, get_current_user_id() )
		) {
			return '';
		}

		$certificate_url = WooThemes_Sensei_Certificates::instance()->get_certificate_url(
			$course_id,
			get_current_user_id()
		);

		// Ensure the certificate URL exists.
		if ( ! $certificate_url ) {
			return '';
		}

		$wrapper_attributes = get_block_wrapper_attributes();

		return sprintf(
			'<div %1$s><a href="%2$s">%3$s</a></div>',
			$wrapper_attributes,
			$certificate_url,
			__( 'View Certificate', 'sensei-certificates' )
		);
	}
}
