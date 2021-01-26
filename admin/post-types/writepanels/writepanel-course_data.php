<?php
/**
 * Sensei LMS Certificates Templates.
 *
 * All functionality pertaining to the Certificate Templates functionality in Sensei.
 *
 * @package    WordPress
 * @subpackage Sensei
 * @category   Extension
 * @author     Automattic
 * @since      1.0.0
 */

/**
 * TABLE OF CONTENTS
 *
 * - Requires
 * - Actions and Filters
 * - course_certificate_template_data_meta_box()
 * - course_certificate_templates_process_meta()
 */

/**
 * Functions for displaying the course certificates templates data meta box.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Actions and Filters.
 */
add_action( 'sensei_process_course_certificate_template_meta', 'course_certificate_templates_process_meta', 10, 2 );

/**
 * Certificates data meta box.
 *
 * Displays the meta box.
 *
 * @since 1.0.0
 */
function course_certificate_template_data_meta_box( $post ) {

	global $post;

	wp_nonce_field( 'course_certificates_save_data', 'course_certificates_meta_nonce' );

	$select_certificate_template = get_post_meta( $post->ID, '_course_certificate_template', true );

	$post_args   = array(
		'post_type'        => 'certificate_template',
		'post_status'      => 'private',
		'numberposts'      => -1,
		'orderby'          => 'title',
		'order'            => 'DESC',
		'exclude'          => $post->ID,
		'suppress_filters' => 0,
	);
	$posts_array = get_posts( $post_args );

	$html = '';

	$html .= '<input type="hidden" name="' . esc_attr( 'woo_course_noonce' ) . '" id="' . esc_attr( 'woo_course_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename( __FILE__ ) ) ) . '" />';

	if ( count( $posts_array ) > 0 ) {
		$html .= '<select id="course-certificate-template-options" name="course_certificate_template" class="widefat">' . "\n";
		$html .= '<option value="">' . __( 'None', 'sensei-certificates' ) . '</option>';
		foreach ( $posts_array as $post_item ) {
			$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '"' . selected( $post_item->ID, $select_certificate_template, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
		}
		$html .= '</select>' . "\n";
	} else {
		if ( ! empty( $select_certificate_template ) ) {
			$html .= '<input type="hidden" name="course_certificate_template" value="' . absint( $select_certificate_template ) . '">';
		}
		$html .= '<p>' . esc_html( __( 'No certificate template exist yet. Please add some first.', 'sensei-certificates' ) ) . '</p>';
	}

	$allowed_html = [
		'input'  => [
			'type'  => [],
			'name'  => [],
			'id'    => [],
			'value' => [],
		],
		'select' => [
			'id'    => [],
			'name'  => [],
			'class' => [],
		],
		'option' => [
			'value'    => [],
			'selected' => [],
		],
		'p'      => [],
	];

	echo wp_kses( $html, $allowed_html );

}


/**
 * Course Certificate Template Data Save.
 *
 * Function for processing and storing all course certificate data.
 *
 * @since 1.0.0
 * @param int    $post_id The certificate id.
 * @param object $post    The certificate post object.
 */
function course_certificate_templates_process_meta( $post_id ) {

	global $woothemes_sensei_certificate_templates;

	if ( ( get_post_type() != 'course' ) ) {
		return $post_id;
	}

	$woothemes_sensei_certificate_templates->save_post_meta( 'course_certificate_template', $post_id );

}
