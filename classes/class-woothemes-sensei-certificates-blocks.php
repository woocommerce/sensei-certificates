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
class Woothemes_Sensei_Certificates_Blocks {
	/**
	 * Woothemes_Sensei_Certificates_Blocks constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_filter( 'render_block', [ $this, 'render_block' ], 10, 2 );
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		$screen = get_current_screen();

		if ( $screen && $screen->is_block_editor && 'page' === $screen->post_type ) {
			WooThemes_Sensei_Certificates::instance()->assets->enqueue(
			'sensei-certificates-block-style',
			'css/blocks.css'
		);
		}
	}

	/**
	 * Enqueue editor and frontend assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
		WooThemes_Sensei_Certificates::instance()->assets->enqueue(
			'sensei-certificates-block',
			'blocks/index.js'
		);
	}

	/**
	 * Render the block.
	 *
	 * @param string $block_content The block content about to be appended.
	 * @param array  $block         The full block, including name and attributes.
	 *
	 * @return string Block HTML.
	 */
	public function render_block( $block_content, $block ): string {
		if ( ! isset( $block['blockName'] ) || 'core/button' !== $block['blockName'] ) {
			return $block_content;
		}

		return $block_content;
	}
}
