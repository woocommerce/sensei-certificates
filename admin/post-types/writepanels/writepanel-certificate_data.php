<?php
/**
 * Sensei Certificates Templates
 *
 * All functionality pertaining to the Certificate Templates functionality in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Extension
 * @author WooThemes
 * @since 1.0.0
 *
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
 * Functions for displaying the certificates data meta box
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Actions and Filters
 */
add_action( 'sensei_process_certificate_template_meta', 'certificate_templates_process_meta', 10, 2 );


/**
 * Certificates data meta box
 *
 * Displays the meta box
 *
 * @since 1.0.0
 */
function certificate_template_data_meta_box( $post ) {

	global $woocommerce, $woothemes_sensei_certificate_templates;

	wp_nonce_field( 'certificates_save_data', 'certificates_meta_nonce' );

	$woothemes_sensei_certificate_templates->populate_object( $post->ID );

	$default_fonts = array(
		'Helvetica' => 'Helvetica',
		'Courier'   => 'Courier',
		'Times'     => 'Times',
	);
	$available_fonts = array_merge( array( '' => '' ), $default_fonts );

	// since this little snippet of css applies only to the certificates post page, it's easier to have inline here
	?>
	<style type="text/css">
		#misc-publishing-actions { display:none; }
		#edit-slug-box { display:none }
		.imgareaselect-outer { cursor: crosshair; }
	</style>
	<div id="certificate_options" class="panel certificate_templates_options_panel">
		<div class="options_group">
			<?php

				// Defaults
				echo '<div class="options_group">';
					certificate_templates_wp_font_select( array(
						'id'                => '_certificate',
						'label'             => __( 'Default Font', 'sensei-certificates' ),
						'options'           => $default_fonts,
						'font_size_default' => 12,
					) );
					certificates_wp_text_input( array(
						'id'          => '_certificate_font_color',
						'label'       => __( 'Default Font color', 'sensei-certificates' ),
						'default'     => '#000000',
						'description' => __( 'The default text color for the certificate.', 'sensei-certificates' ),
						'class'       => 'colorpick',
					) );
				echo '</div>';

				// Data fields
				$data_fields = sensei_get_certificate_data_fields();
				foreach ( $data_fields as $field_key => $field_info ) {

					echo '<div class="options_group">';
						certificate_templates_wp_position_picker( array(
							'id'          => "certificate_{$field_key}_pos",
							'label'       => $field_info['position_label'],
							'value'       => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( "certificate_$field_key" ) ),
							'description' => $field_info['position_description'],
						) );
						certificates_wp_hidden_input( array(
							'id'    => "_certificate_{$field_key}_pos",
							'class' => 'field_pos',
							'value' => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( "certificate_$field_key" ) ),
						) );
						certificate_templates_wp_font_select( array(
							'id'      => "_certificate_$field_key",
							'label'   => __( 'Font', 'sensei-certificates' ),
							'options' => $available_fonts,
						) );
						certificates_wp_text_input( array(
							'id'    => "_certificate_{$field_key}_font_color",
							'label' => __( 'Font color', 'sensei-certificates' ),
							'value' => isset( $woothemes_sensei_certificate_templates->certificate_template_fields["certificate_$field_key"]['font']['color'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields["certificate_$field_key"]['font']['color'] : '',
							'class' => 'colorpick',
						) );

						$text_function = ( $field_info['type'] == 'textarea' ) ? 'certificates_wp_textarea_input' : 'certificates_wp_text_input';
						$text_function( array(
							'class'       => 'medium',
							'id'          => "_certificate_{$field_key}_text",
							'label'       => $field_info['text_label'],
							'description' => $field_info['text_description'],
							'placeholder' => $field_info['text_placeholder'],
							'value'       => isset( $woothemes_sensei_certificate_templates->certificate_template_fields["certificate_$field_key"]['text'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields["certificate_$field_key"]['text'] : '',
						) );
					echo '</div>';
				}
			?>
		</div>
	</div>
	<?php

	certificate_templates_wp_color_picker_js();

} // End certificate_template_data_meta_box()


/**
 * Certificate Data Save
 *
 * Function for processing and storing all certificate data.
 *
 * @since 1.0.0
 * @param int $post_id the certificate id
 * @param object $post the certificate post object
 */
function certificate_templates_process_meta( $post_id, $post ) {

	// certificate template font defaults
	update_post_meta( $post_id, '_certificate_font_color',  $_POST['_certificate_font_color'] ? $_POST['_certificate_font_color'] : '#000000' );  // provide a default
	update_post_meta( $post_id, '_certificate_font_size',   $_POST['_certificate_font_size'] ? $_POST['_certificate_font_size'] : 11 );  // provide a default
	update_post_meta( $post_id, '_certificate_font_family', $_POST['_certificate_font_family']  );
	update_post_meta( $post_id, '_certificate_font_style',  ( isset( $_POST['_certificate_font_style_b'] ) && 'yes' == $_POST['_certificate_font_style_b'] ? 'B' : '' ) .
	                                                    ( isset( $_POST['_certificate_font_style_i'] ) && 'yes' == $_POST['_certificate_font_style_i'] ? 'I' : '' ) .
	                                                    ( isset( $_POST['_certificate_font_style_c'] ) && 'yes' == $_POST['_certificate_font_style_c'] ? 'C' : '' ) .
	                                                    ( isset( $_POST['_certificate_font_style_o'] ) && 'yes' == $_POST['_certificate_font_style_o'] ? 'O' : '' ) );

	// original sizes: default 11, product name 16, sku 8
	// create the certificate template fields data structure
	$fields = array();
	$data_fields = sensei_get_certificate_data_fields();
	foreach ( array_keys($data_fields) as $i => $field_key ) {

		$field_name = '_certificate_' . $field_key;

		// set the field defaults
		$field = array(
			'type'      => 'property',
			'font'     => array( 'family' => '', 'size' => '', 'style' => '', 'color' => '' ),
			'position' => array(),
			'order'    => $i,
		);

		// get the field position (if set)
		if ( $_POST[ $field_name . '_pos' ] ) {
			$position = explode( ',', $_POST[ $field_name . '_pos' ] );
			$field['position'] = array( 'x1' => $position[0], 'y1' => $position[1], 'width' => $position[2], 'height' => $position[3] );
		} // End If Statement

		if ( $_POST[ $field_name . '_text' ] ) {
			$field['text'] = $_POST[ $field_name . '_text' ] ? $_POST[ $field_name . '_text' ] : '';
		} // End If Statement

		// get the field font settings (if any)
		if ( $_POST[ $field_name . '_font_family' ] )  $field['font']['family'] = $_POST[ $field_name . '_font_family' ];
		if ( $_POST[ $field_name . '_font_size' ] )    $field['font']['size']   = $_POST[ $field_name . '_font_size' ];
		if ( isset( $_POST[ $field_name . '_font_style_b' ] ) && $_POST[ $field_name . '_font_style_b' ] ) $field['font']['style']  = 'B';
		if ( isset( $_POST[ $field_name . '_font_style_i' ] ) && $_POST[ $field_name . '_font_style_i' ] ) $field['font']['style'] .= 'I';
		if ( isset( $_POST[ $field_name . '_font_style_c' ] ) && $_POST[ $field_name . '_font_style_c' ] ) $field['font']['style'] .= 'C';
		if ( isset( $_POST[ $field_name . '_font_style_o' ] ) && $_POST[ $field_name . '_font_style_o' ] ) $field['font']['style'] .= 'O';
		if ( $_POST[ $field_name . '_font_color' ] )   $field['font']['color']  = $_POST[ $field_name . '_font_color' ];

		// cut off the leading '_' to create the field name
		$fields[ ltrim( $field_name, '_' ) ] = $field;

	} // End For Loop

	update_post_meta( $post_id, '_certificate_template_fields', $fields );

} // End certificate_templates_process_meta()
