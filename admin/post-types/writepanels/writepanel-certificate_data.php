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
 * - certificate_template_data_meta_box()
 * - certificate_templates_process_meta()
 */

/**
 * Functions for displaying the certificates data meta box.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Actions and Filters.
 */
add_action( 'sensei_process_certificate_template_meta', 'certificate_templates_process_meta', 10, 2 );


/**
 * Certificates data meta box.
 *
 * Displays the meta box.
 *
 * @since 1.0.0
 */
function certificate_template_data_meta_box( $post ) {

	global $woocommerce, $woothemes_sensei_certificate_templates;

	wp_nonce_field( 'certificates_save_data', 'certificates_meta_nonce' );

	$woothemes_sensei_certificate_templates->populate_object( $post->ID );

	$default_fonts   = array(
		'Helvetica' => 'Helvetica',
		'Courier'   => 'Courier',
		'Times'     => 'Times',
	);
	$available_fonts = array_merge( array( '' => '' ), $default_fonts );

	// Since this little snippet of css applies only to the certificates post page, it's easier to have inline here.
	?>
	<style type="text/css">
		#misc-publishing-actions { display:none; }
		#edit-slug-box { display:none }
		.imgareaselect-outer { cursor: crosshair; }
	</style>
	<div id="certificate_options" class="panel certificate_templates_options_panel">
		<div class="options_group">
			<?php
			// Defaults.
			echo '<div class="options_group">';
				certificate_templates_wp_font_select(
					array(
						'id'                => '_certificate',
						'label'             => __( 'Default Font', 'sensei-certificates' ),
						'options'           => $default_fonts,
						'font_size_default' => 12,
					)
				);
				certificates_wp_text_input(
					array(
						'id'          => '_certificate_font_color',
						'label'       => __( 'Default Font color', 'sensei-certificates' ),
						'default'     => '#000000',
						'description' => __( 'The default text color for the certificate.', 'sensei-certificates' ),
						'class'       => 'colorpick',
					)
				);
			echo '</div>';

			// Data fields.
			$data_fields = sensei_get_certificate_data_fields();
			foreach ( $data_fields as $field_key => $field_info ) {

				echo '<div class="options_group">';
					certificate_templates_wp_position_picker(
						array(
							'id'          => "certificate_{$field_key}_pos",
							'label'       => $field_info['position_label'],
							'value'       => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( "certificate_{$field_key}" ) ),
							'description' => $field_info['position_description'],
						)
					);
					certificates_wp_hidden_input(
						array(
							'id'    => "_certificate_{$field_key}_pos",
							'class' => 'field_pos',
							'value' => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( "certificate_{$field_key}" ) ),
						)
					);
					certificate_templates_wp_font_select(
						array(
							'id'      => "_certificate_{$field_key}",
							'label'   => __( 'Font', 'sensei-certificates' ),
							'options' => $available_fonts,
						)
					);
					certificates_wp_text_input(
						array(
							'id'    => "_certificate_{$field_key}_font_color",
							'label' => __( 'Font color', 'sensei-certificates' ),
							'value' => isset( $woothemes_sensei_certificate_templates->certificate_template_fields[ "certificate_{$field_key}" ]['font']['color'] )
								? $woothemes_sensei_certificate_templates->certificate_template_fields[ "certificate_{$field_key}" ]['font']['color']
								: '',
							'class' => 'colorpick',
						)
					);

					$text_function = ( 'textarea' === $field_info['type'] ) ? 'certificates_wp_textarea_input' : 'certificates_wp_text_input';
					$text_function(
						array(
							'class'       => 'medium',
							'id'          => "_certificate_{$field_key}_text",
							'label'       => $field_info['text_label'],
							'description' => $field_info['text_description'],
							'placeholder' => $field_info['text_placeholder'],
							'value'       => isset( $woothemes_sensei_certificate_templates->certificate_template_fields[ "certificate_{$field_key}" ]['text'] )
								? $woothemes_sensei_certificate_templates->certificate_template_fields[ "certificate_{$field_key}" ]['text']
								: '',
						)
					);
				echo '</div>';
			}
			?>
		</div>
	</div>
	<?php
}


/**
 * Certificate Data Save.
 *
 * Function for processing and storing all certificate data.
 *
 * @since 1.0.0
 * @param int    $post_id The certificate id.
 * @param object $post    The certificate post object.
 */
function certificate_templates_process_meta( $post_id, $post ) {
	if (
		empty( $_POST['certificates_meta_nonce'] )
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Leave nonce value unmodified.
		|| ! wp_verify_nonce( wp_unslash( $_POST['certificates_meta_nonce'] ), 'certificates_save_data' )
	) {
		return;
	}

	$font_color  = ! empty( $_POST['_certificate_font_color'] ) ? sanitize_text_field( wp_unslash( $_POST['_certificate_font_color'] ) ) : '#000000'; // Provide a default.
	$font_size   = ! empty( $_POST['_certificate_font_size'] ) ? intval( $_POST['_certificate_font_size'] ) : 11; // Provide a default.
	$font_family = ! empty( $_POST['_certificate_font_family'] ) ? sanitize_text_field( wp_unslash( $_POST['_certificate_font_family'] ) ) : '';

	// Certificate template font defaults.
	update_post_meta( $post_id, '_certificate_font_color', $font_color );
	update_post_meta( $post_id, '_certificate_font_size', $font_size );
	update_post_meta( $post_id, '_certificate_font_family', $font_family );
	update_post_meta(
		$post_id,
		'_certificate_font_style',
		( isset( $_POST['_certificate_font_style_b'] ) && 'yes' == $_POST['_certificate_font_style_b'] ? 'B' : '' ) .
														( isset( $_POST['_certificate_font_style_i'] ) && 'yes' == $_POST['_certificate_font_style_i'] ? 'I' : '' ) .
														( isset( $_POST['_certificate_font_style_c'] ) && 'yes' == $_POST['_certificate_font_style_c'] ? 'C' : '' ) .
														( isset( $_POST['_certificate_font_style_o'] ) && 'yes' == $_POST['_certificate_font_style_o'] ? 'O' : '' )
	);

	// Original sizes: default 11, product name 16, sku 8.
	// Create the certificate template fields data structure.
	$fields      = array();
	$data_fields = sensei_get_certificate_data_fields();
	foreach ( array_keys( $data_fields ) as $i => $field_key ) {

		$field_name = '_certificate_' . $field_key;

		// Set the field defaults.
		$field = array(
			'type'     => 'property',
			'font'     => array(
				'family' => '',
				'size'   => '',
				'style'  => '',
				'color'  => '',
			),
			'position' => array(),
			'order'    => $i,
		);

		// Get the field position (if set).
		if ( ! empty( $_POST[ $field_name . '_pos' ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized after the explode in map with intval.
			$position = explode( ',', wp_unslash( $_POST[ $field_name . '_pos' ] ) );
			$position = array_map( 'intval', $position );

			$field['position'] = array(
				'x1'     => $position[0],
				'y1'     => $position[1],
				'width'  => $position[2],
				'height' => $position[3],
			);
		}

		if ( ! empty( $_POST[ $field_name . '_text' ] ) ) {
			$field['text'] = sanitize_textarea_field( wp_unslash( $_POST[ $field_name . '_text' ] ) );
		}

		// Get the field font settings (if any).
		if ( ! empty( $_POST[ $field_name . '_font_family' ] ) ) {
			$field['font']['family'] = sanitize_text_field( wp_unslash( $_POST[ $field_name . '_font_family' ] ) );
		}
		if ( ! empty( $_POST[ $field_name . '_font_size' ] ) ) {
			$field['font']['size'] = intval( $_POST[ $field_name . '_font_size' ] );
		}
		if ( isset( $_POST[ $field_name . '_font_style_b' ] ) ) {
			$field['font']['style'] = 'B';
		}
		if ( isset( $_POST[ $field_name . '_font_style_i' ] ) ) {
			$field['font']['style'] .= 'I';
		}
		if ( isset( $_POST[ $field_name . '_font_style_c' ] ) ) {
			$field['font']['style'] .= 'C';
		}
		if ( isset( $_POST[ $field_name . '_font_style_o' ] ) ) {
			$field['font']['style'] .= 'O';
		}
		if ( isset( $_POST[ $field_name . '_font_color' ] ) ) {
			$field['font']['color'] = sanitize_text_field( wp_unslash( $_POST[ $field_name . '_font_color' ] ) );
		}

		// Cut off the leading '_' to create the field name.
		$fields[ ltrim( $field_name, '_' ) ] = $field;

	}

	update_post_meta( $post_id, '_certificate_template_fields', $fields );
}
