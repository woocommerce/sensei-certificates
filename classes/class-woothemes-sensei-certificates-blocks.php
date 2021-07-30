<?php
/**
 * File containing the class Woothemes_Sensei_Certificates_Blocks.
 *
 * @package sensei-certificates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Woothemes_Sensei_Certificates_Blocks
 */
class Woothemes_Sensei_Certificates_Blocks extends Sensei_Blocks_Initializer {
	/**
	 * Woothemes_Sensei_Certificates_Blocks constructor.
	 */
	public function __construct() {
		parent::__construct( [ 'page' ] );
	}

	/**
	 * Initialize blocks.
	 */
	public function initialize_blocks() {}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
		WooThemes_Sensei_Certificates::instance()->assets->enqueue(
			'sensei-certificates-block',
			'blocks/view-certificate/index.js'
		);

		register_block_type(
			'sensei-certificates/button-view-certificate',
			[
				'render_callback' => [ $this, 'render_block' ],
			]
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block HTML.
	 *
	 * @return string Block HTML.
	 */
	public function render_block( $attributes, $content ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used safely if the learner completed the course.
		$course_id = isset( $_GET['course_id'] ) ? (int) $_GET['course_id'] : false;

		// Check that the course ID exists and that the user has completed the course.
		if (
			! $course_id
			|| ! get_current_user_id()
			|| 'course' !== get_post_type( $course_id )
			|| ! Sensei_Utils::user_completed_course( $course_id, get_current_user_id() )
		) {
			return '';
		}

		// Extract the anchor element for manipulation.
		$dom = new DomDocument();
		$dom->loadHTML( $content );
		$parent_div = $dom->getElementsByTagName( 'div' )->length > 0 ? $dom->getElementsByTagName( 'div' )[0] : '';
		$anchor     = $parent_div && $parent_div->getElementsByTagName( 'a' )->length > 0 ? $parent_div->getElementsByTagName( 'a' )[0] : '';

		// Update the anchor to open the certificate when clicked.
		if ( $anchor ) {
			$certificate_url = WooThemes_Sensei_Certificates::instance()->get_certificate_url( $course_id, get_current_user_id() );
			$certificate_url && $anchor->setAttribute( 'href', $certificate_url );
			$content = $dom->saveHTML( $parent_div );
		}

		return $content;
	}
}
