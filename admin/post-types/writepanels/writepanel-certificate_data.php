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
 * Functions for displaying the voucher data meta box
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Voucher data meta box
 *
 * Displays the meta box
 *
 * @since 1.0
 */
function certificate_template_data_meta_box( $post ) {
	global $woocommerce, $woothemes_sensei_certificate_templates;

	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

	$woothemes_sensei_certificate_templates->populate_object( $post->ID );

	$default_fonts = array(
		'Helvetica' => 'Helvetica',
		'Courier'   => 'Courier',
		'Times'     => 'Times',
	);
	$available_fonts = $default_fonts;

	// since this little snippet of css applies only to the voucher post page, it's easier to have inline here
	?>
	<style type="text/css">
		#misc-publishing-actions { display:none; }
		#edit-slug-box { display:none }
		.imgareaselect-outer { cursor: crosshair; }
	</style>
	<div id="certificate_options" class="panel woocommerce_options_panel">
		<div class="options_group">
			<?php

				// Defaults
				echo '<div class="options_group">';
					certificate_templates_wp_font_select( array(
						'id'                => '_certificate',
						'label'             => __( 'Default Font', 'woothemes-sensei' ),
						'options'           => $default_fonts,
						'font_size_default' => 11,
					) );
					woocommerce_wp_text_input( array(
						'id'          => '_certificate_font_color',
						'label'       => __( 'Default Font color', 'woothemes-sensei' ),
						'default'     => '#000000',
						'description' => __( 'The default text color for the certificate.', 'woothemes-sensei' ),
						'class'       => 'colorpick',
					) );
				echo '</div>';

				// Heading
				echo '<div class="options_group">';
					certificate_templates_wp_position_picker( array(
						'id'          => 'certificate_heading_pos',
						'label'       => __( 'Heading Position', 'woothemes-sensei' ),
						'value'       => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_heading' ) ),
						'description' => __( 'Optional position of the Certificate Heading', 'woothemes-sensei' ),
					) );
					woocommerce_wp_hidden_input( array(
						'id'    => '_certificate_heading_pos',
						'class' => 'field_pos',
						'value' => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_heading' ) ),
					) );
					certificate_templates_wp_font_select( array(
						'id'      => '_certificate_heading',
						'label'   => __( 'Font', 'woothemes-sensei' ),
						'options' => $available_fonts,
					) );
					woocommerce_wp_text_input( array(
						'id'    => '_certificate_heading_font_color',
						'label' => __( 'Font color', 'woothemes-sensei' ),
						'value' => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_heading']['font']['color'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_heading']['font']['color'] : '',
						'class' => 'colorpick',
					) );
					woocommerce_wp_text_input( array(
						'class'       => '',
						'id'          => '_certificate_heading_text',
						'label'       => __( 'Heading Text', 'woothemes-sensei' ),
						'description' => __( 'Text to display in the heading.', 'woothemes-sensei' ),
						'placeholder' => __( 'Certificate of Completion', 'woothemes-sensei' ),
						'value'       => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_heading']['text'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_heading']['text'] : '',
					) );
				echo '</div>';

				// Message
				echo '<div class="options_group">';
					certificate_templates_wp_position_picker( array(
						'id'          => 'certificate_message_pos',
						'label'       => __( 'Message Position', 'woothemes-sensei' ),
						'value'       => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_message' ) ),
						'description' => __( 'Optional position of the Certificate Message', 'woothemes-sensei' ),
					) );
					woocommerce_wp_hidden_input( array(
						'id'    => '_certificate_message_pos',
						'class' => 'field_pos',
						'value' => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_message' ) )
					) );
					certificate_templates_wp_font_select( array(
						'id'      => '_certificate_message',
						'label'   => __( 'Font', 'woothemes-sensei' ),
						'options' => $available_fonts,
					) );
					woocommerce_wp_text_input( array(
						'id'    => '_certificate_message_font_color',
						'label' => __( 'Font color', 'woothemes-sensei' ),
						'value' => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_message']['font']['color'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_message']['font']['color'] : '',
						'class' => 'colorpick',
					) );
					woocommerce_wp_textarea_input( array(
						'class'       => '',
						'id'          => '_certificate_message_text',
						'label'       => __( 'Message Text', 'woothemes-sensei' ),
						'description' => __( 'Text to display in the message area.', 'woothemes-sensei' ),
						'placeholder' => __( 'This is to certify that {{learner}} has completed the course', 'woothemes-sensei' ),
						'value'       => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_message']['text'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_message']['text'] : '',
					) );
				echo '</div>';

				// Voucher number position
				echo '<div class="options_group">';
					certificate_templates_wp_position_picker( array(
						'id'          => 'certificate_course_pos',
						'label'       => __( 'Course Position', 'woothemes-sensei' ),
						'value'       => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_course' ) ),
						'description' => __( 'Optional position of the Course Name', 'woothemes-sensei' ),
					) );
					woocommerce_wp_hidden_input( array(
						'id'    => '_certificate_course_pos',
						'class' => 'field_pos',
						'value' => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_course' ) ),
					) );
					certificate_templates_wp_font_select( array(
						'id'      => '_certificate_course',
						'label'   => __( 'Font', 'woothemes-sensei' ),
						'options' => $available_fonts,
					) );
					woocommerce_wp_text_input( array(
						'id'    => '_certificate_course_font_color',
						'label' => __( 'Font color', 'woothemes-sensei' ),
						'value' => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_course']['font']['color'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_course']['font']['color'] : '',
						'class' => 'colorpick',
					) );
					woocommerce_wp_text_input( array(
						'class'       => '',
						'id'          => '_certificate_course_text',
						'label'       => __( 'Course Text', 'woothemes-sensei' ),
						'description' => __( 'Text to display in the course area.', 'woothemes-sensei' ),
						'placeholder' => __( '{{course_title}}', 'woothemes-sensei' ),
						'value'       => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_course']['text'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_course']['text'] : '',
					) );
				echo '</div>';

				// Days to expiration
				echo '<div class="options_group">';
					certificate_templates_wp_position_picker( array(
						'id'          => 'certificate_completion_pos',
						'label'       => __( 'Completion Date Position', 'woothemes-sensei' ),
						'value'       => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_completion' ) ),
						'description' => __( 'Optional position of the Course Completion date', 'woothemes-sensei' ),
					) );
					woocommerce_wp_hidden_input( array(
						'id' => '_certificate_completion_pos',
						'class' => 'field_pos',
						'value' => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_completion' ) ),
					) );
					certificate_templates_wp_font_select( array(
						'id'      => '_certificate_completion',
						'label'   => __( 'Font', 'woothemes-sensei' ),
						'options' => $available_fonts,
					) );
					woocommerce_wp_text_input( array(
						'id'    => '_certificate_completion_font_color',
						'label' => __( 'Font color', 'woothemes-sensei' ),
						'value' => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_completion']['font']['color'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_completion']['font']['color'] : '',
						'class' => 'colorpick',
					) );
					woocommerce_wp_text_input( array(
						'class'       => '',
						'id'          => '_certificate_completion_text',
						'label'       => __( 'Completion Date Text', 'woothemes-sensei' ),
						'description' => __( 'Text to display in the course completion date area.', 'woothemes-sensei' ),
						'placeholder' => __( '{{completion_date}}', 'woothemes-sensei' ),
						'value'       => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_completion']['text'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_completion']['text'] : '',
					) );
				echo '</div>';

				// Voucher recipient position
				echo '<div class="options_group">';
					certificate_templates_wp_position_picker( array(
						'id'          => 'certificate_place_pos',
						'label'       => __( 'Place Position', 'woothemes-sensei' ),
						'value'       => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_place' ) ),
						'description' => __( 'Optional position of the place of Certification.', 'woothemes-sensei' ),
					) );
					woocommerce_wp_hidden_input( array(
						'id'    => '_certificate_place_pos',
						'class' => 'field_pos',
						'value' => implode( ',', $woothemes_sensei_certificate_templates->get_field_position( 'certificate_place' ) ),
					) );
					certificate_templates_wp_font_select( array(
						'id'      => '_certificate_place',
						'label'   => __( 'Font', 'woothemes-sensei' ),
						'options' => $available_fonts,
					) );
					woocommerce_wp_text_input( array(
						'id'    => '_certificate_place_font_color',
						'label' => __( 'Font color', 'woothemes-sensei' ),
						'value' => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_place']['font']['color'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_place']['font']['color'] : '',
						'class' => 'colorpick',
					) );
					woocommerce_wp_text_input( array(
						'class'       => '',
						'id'          => '_certificate_place_text',
						'label'       => __( 'Course Place Text', 'woothemes-sensei' ),
						'description' => __( 'Text to display in the course place area.', 'woothemes-sensei' ),
						'placeholder' => __( '{{course_place}}', 'woothemes-sensei' ),
						'value'       => isset( $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_place']['text'] ) ? $woothemes_sensei_certificate_templates->certificate_template_fields['certificate_place']['text'] : '',
					) );
				echo '</div>';

			?>
		</div>
	</div>
	<?php

	certificate_templates_wp_color_picker_js();
}


add_action( 'sensei_process_certificate_template_meta', 'certificate_templates_process_meta', 10, 2 );

/**
 * Voucher Data Save
 *
 * Function for processing and storing all voucher data.
 *
 * @since 1.0
 * @param int $post_id the voucher id
 * @param object $post the voucher post object
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
	foreach ( array( '_certificate_heading', '_certificate_message', '_certificate_course', '_certificate_completion', '_certificate_place' ) as $i => $field_name ) {
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
		}

		if ( $_POST[ $field_name . '_text' ] ) {
			$field['text'] = $_POST[ $field_name . '_text' ] ? $_POST[ $field_name . '_text' ] : '';
		}

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
	}

	update_post_meta( $post_id, '_certificate_template_fields', $fields );
}
