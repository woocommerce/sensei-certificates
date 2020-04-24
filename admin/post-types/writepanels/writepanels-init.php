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
 * - certificate_templates_meta_boxes()
 * - certificate_templates_enter_title_here()
 * - certificate_templates_meta_boxes_save()
 * - course_certificate_templates_meta_boxes_save()
 * - certificate_template_private()
 * - certificate_template_wp_font_select()
 * - certificate_template_wp_color_picker_js()
 * - certificate_template_wp_position_picker()
 * - certificates_wp_text_input()
 * - certificates_wp_hidden_input()
 * - certificates_wp_checkbox()
 * - certificates_wp_select()
 * - certificates_wp_radio()
 */

/**
 * Sets up the write panels used by certificates (custom post types).
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Requires.
 */
require_once 'writepanel-certificate_image.php';
require_once 'writepanel-certificate_data.php';
require_once 'writepanel-course_data.php';


/**
 * Actions and Filters.
 */
add_action( 'add_meta_boxes', 'certificate_templates_meta_boxes' );
add_filter( 'enter_title_here', 'certificate_templates_enter_title_here', 1, 2 );
add_action( 'save_post', 'certificate_templates_meta_boxes_save', 1, 2 );
add_action( 'save_post', 'course_certificate_templates_meta_boxes_save', 1, 2 );
add_action( 'publish_certificate_template', 'certificate_template_private', 10, 2 );


/**
 * Add and remove meta boxes from the certificates edit page and Order edit page.
 *
 * @since 1.0.0
 */
function certificate_templates_meta_boxes() {

	// Certificate Primary Image box.
	add_meta_box(
		'sensei-certificate-image',
		__( 'Certificate Background Image <small>&ndash; Used to lay out the certificate fields found in the Certificate Data box.</small>', 'sensei-certificates' ),
		'certificate_template_image_meta_box',
		'certificate_template',
		'normal',
		'high'
	);

	// Certificate Data box.
	add_meta_box(
		'sensei-certificate-data',
		__( 'Certificate Data', 'sensei-certificates' ),
		'certificate_template_data_meta_box',
		'certificate_template',
		'normal',
		'high'
	);

	// Certificate Data box.
	add_meta_box(
		'sensei-course-certificate-data',
		__( 'Certificate Template', 'sensei-certificates' ),
		'course_certificate_template_data_meta_box',
		'course',
		'side',
		'core'
	);

	// Remove unnecessary meta boxes.
	remove_meta_box( 'wpseo_meta', 'certificate_template', 'normal' );
	remove_meta_box( 'woothemes-settings', 'certificate_template', 'normal' );
	remove_meta_box( 'commentstatusdiv', 'certificate_template', 'normal' );
	remove_meta_box( 'slugdiv', 'certificate_template', 'normal' );
}


/**
 * Set a more appropriate placeholder text for the New Certificate title field.
 *
 * @since 1.0.0
 * @param string $text "Enter Title Here" string.
 * @param object $post Post object.
 *
 * @return string "Certificate Template Name" when the post type is certificate_template.
 */
function certificate_templates_enter_title_here( $text, $post ) {

	if ( 'certificate_template' == $post->post_type ) {
		return __( 'Certificate Template', 'sensei-certificates' );
	}

	return $text;
}


/**
 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
 *
 * @since 1.0.0
 * @param int    $post_id Post identifier.
 * @param object $post    Post object.
 */
function certificate_templates_meta_boxes_save( $post_id, $post ) {

	if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( is_int( wp_is_post_revision( $post ) ) ) {
		return;
	}
	if ( is_int( wp_is_post_autosave( $post ) ) ) {
		return;
	}
	if (
		empty( $_POST['certificates_meta_nonce'] )
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Leave nonce value unmodified.
		|| ! wp_verify_nonce( wp_unslash( $_POST['certificates_meta_nonce'] ), 'certificates_save_data' )
	) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'certificate_template' != $post->post_type ) {
		return;
	}

	do_action( 'sensei_process_certificate_template_meta', $post_id, $post );
}


/**
 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
 *
 * @since 1.0.0
 * @param int    $post_id Post identifier.
 * @param object $post    Post object.
 */
function course_certificate_templates_meta_boxes_save( $post_id, $post ) {

	if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( is_int( wp_is_post_revision( $post ) ) ) {
		return;
	}
	if ( is_int( wp_is_post_autosave( $post ) ) ) {
		return;
	}
	if (
		empty( $_POST['course_certificates_meta_nonce'] )
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Leave nonce value unmodified.
		|| ! wp_verify_nonce( wp_unslash( $_POST['course_certificates_meta_nonce'] ), 'course_certificates_save_data' )
	) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'course' != $post->post_type ) {
		return;
	}

	do_action( 'sensei_process_course_certificate_template_meta', $post_id, $post );
}


/**
 * Automatically make the certificate posts private when they are published.
 * That way we can have them be publicly_queryable for the purposes of
 * generating a preview pdf for the admin user, while having them always
 * hidden on the frontend (draft posts are not visible by definition).
 *
 * @since 1.0.0
 * @param int    $post_id The certificate identifier.
 * @param object $post    The certificate object.
 */
function certificate_template_private( $post_id, $post ) {

	global $wpdb;

	$wpdb->update( $wpdb->posts, array( 'post_status' => 'private' ), array( 'ID' => (int) $post_id ) );
}


/**
 * Rendres a custom admin input field to select a font which includes font
 * family, size and style (bold/italic).
 *
 * @since 1.0.0
 */
function certificate_templates_wp_font_select( $field ) {

	global $thepostid, $post, $woothemes_sensei_certificates;

	if ( ! $thepostid ) {
		$thepostid = $post->ID;
	}

	// Values.
	$font_family_value = $font_size_value = $font_style_value = '';

	if ( '_certificate' == $field['id'] ) {

		// Certificate defaults.
		$font_family_value = get_post_meta( $thepostid, $field['id'] . '_font_family', true );
		$font_size_value   = get_post_meta( $thepostid, $field['id'] . '_font_size', true );
		$font_style_value  = get_post_meta( $thepostid, $field['id'] . '_font_style', true );

	} else {

		// Field-specific overrides.
		$certificate_fields = get_post_meta( $thepostid, '_certificate_template_fields', true );

		$field_name = ltrim( $field['id'], '_' );

		if ( is_array( $certificate_fields ) ) {
			if ( isset( $certificate_fields[ $field_name ]['font']['family'] ) ) {
				$font_family_value = $certificate_fields[ $field_name ]['font']['family'];
			}
			if ( isset( $certificate_fields[ $field_name ]['font']['size'] ) ) {
				$font_size_value = $certificate_fields[ $field_name ]['font']['size'];
			}
			if ( isset( $certificate_fields[ $field_name ]['font']['style'] ) ) {
				$font_style_value = $certificate_fields[ $field_name ]['font']['style'];
			}
		}
	}

	// Defaults.
	if ( ! $font_size_value && isset( $field['font_size_default'] ) ) {
		$font_size_value = $field['font_size_default'];
	}

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_font_family_field"><label for="' . esc_attr( $field['id'] ) . '_font_family">' . esc_html( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '_font_family" name="' . esc_attr( $field['id'] ) . '_font_family" class="select short">';

	foreach ( $field['options'] as $key => $value ) {

		echo '<option value="' . esc_attr( $key ) . '" ';
		selected( $font_family_value, $key );
		echo '>' . esc_html( $value ) . '</option>';

	}

	echo '</select> ';

	echo '<input type="text" style="width:auto;margin-left:10px;" size="2" name="' . esc_attr( $field['id'] ) . '_font_size" id="' . esc_attr( $field['id'] ) . '_font_size" value="' . esc_attr( $font_size_value ) . '" placeholder="' . esc_attr__( 'Size', 'sensei-certificates' ) . '" /> ';

	echo '<label for="' . esc_attr( $field['id'] ) . '_font_style_b" style="width:auto;margin:0 5px 0 10px;">' . esc_html__( 'Bold', 'sensei-certificates' ) . '</label><input type="checkbox" class="checkbox" style="margin-top:4px;" name="' . esc_attr( $field['id'] ) . '_font_style_b" id="' . esc_attr( $field['id'] ) . '_font_style_b" value="yes" ';
	checked( false !== strpos( $font_style_value, 'B' ), true );
	echo ' /> ';

	echo '<label for="' . esc_attr( $field['id'] ) . '_font_style_i" style="width:auto;margin:0 5px 0 10px;">' . esc_html__( 'Italic', 'sensei-certificates' ) . '</label><input type="checkbox" class="checkbox" style="margin-top:4px;" name="' . esc_attr( $field['id'] ) . '_font_style_i" id="' . esc_attr( $field['id'] ) . '_font_style_i" value="yes" ';
	checked( false !== strpos( $font_style_value, 'I' ), true );
	echo ' /> ';

	if ( '_certificate' != $field['id'] ) {

		echo '<label for="' . esc_attr( $field['id'] ) . '_font_style_c" style="width:auto;margin:0 5px 0 10px;">' . esc_html__( 'Center Align', 'sensei-certificates' ) . '</label><input type="checkbox" class="checkbox" style="margin-top:4px;" name="' . esc_attr( $field['id'] ) . '_font_style_c" id="' . esc_attr( $field['id'] ) . '_font_style_c" value="yes" ';
		checked( false !== strpos( $font_style_value, 'C' ), true );
		echo ' /> ';

		echo '<label for="' . esc_attr( $field['id'] ) . '_font_style_o" style="width:auto;margin:0 5px 0 10px;">' . esc_html__( 'Border', 'sensei-certificates' ) . '</label><input type="checkbox" class="checkbox" style="margin-top:4px;" name="' . esc_attr( $field['id'] ) . '_font_style_o" id="' . esc_attr( $field['id'] ) . '_font_style_o" value="yes" ';
		checked( false !== strpos( $font_style_value, 'O' ), true );
		echo ' /> ';

	}

	echo '</p>';
}


/**
 * Add inline javascript to activate the farbtastic color picker element.
 * Must be called in order to use the certificate_templates_wp_color_picker() method.
 *
 * @since 1.0.0
 *
 * @deprecated 2.0.4
 */
function certificate_templates_wp_color_picker_js() {
	_deprecated_function( __FUNCTION__, '2.0.4' );

	global $woothemes_sensei_certificates;

	ob_start();
	?>
	$(".colorpick").wpColorPicker();

	$(document).mousedown(function(e) {
		if ($(e.target).hasParent(".wp-picker-holder"))
			return;
		if ($( e.target ).hasParent("mark"))
			return;
		$(".wp-picker-holder").each(function() {
			$(this).fadeOut();
		});
	});
	<?php
	$javascript = ob_get_clean();

	$woothemes_sensei_certificates->add_inline_js( $javascript );

}


/**
 * Renders a custom admin control used on the certificate edit page to Set/Remove
 * the position via two buttons.
 *
 * @since 1.0.0
 */
function certificate_templates_wp_position_picker( $field ) {

	global $woothemes_sensei_certificates;

	if ( ! isset( $field['value'] ) ) {
		$field['value'] = '';
	}

	echo '<p class="form-field"><label>' . esc_html( $field['label'] ) . '</label><input type="button" id="' . esc_attr( $field['id'] ) . '" class="set_position button" value="' . esc_attr__( 'Set Position', 'sensei-certificates' ) . '" style="width:auto;" /> <input type="button" id="remove_' . esc_attr( $field['id'] ) . '" class="remove_position button" value="' . esc_attr__( 'Remove Position', 'sensei-certificates' ) . '" style="width:auto;' . ( $field['value'] ? '' : 'display:none' ) . ';margin-left:7px;" />';

	if ( isset( $field['description'] ) && $field['description'] ) {

		if ( isset( $field['desc_tip'] ) ) {

			echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . esc_url( $woothemes_sensei_certificates->plugin_url() . '/assets/images/help.png' ) . '" />';

		} else {

			echo '<span class="description">' . esc_html( $field['description'] ) . '</span>';

		}
	}

	echo '</p>';
}


/**
 * Output a text input box.
 *
 * @access public
 * @since  1.0.0
 * @param  array $field
 * @return void
 */
function certificates_wp_text_input( $field ) {

	global $thepostid, $post, $woothemes_sensei_certificates;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['type']          = isset( $field['type'] ) ? $field['type'] : 'text';

	// Custom attribute handling.
	$custom_attributes = array();

	if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
		foreach ( $field['custom_attributes'] as $attribute => $value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
		}
	}

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><input type="' . esc_attr( $field['type'] ) . '" class="' . esc_attr( $field['class'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . esc_attr( implode( ' ', $custom_attributes ) ) . ' /> ';

	if ( ! empty( $field['description'] ) ) {

		if ( isset( $field['desc_tip'] ) ) {

			echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . esc_url( $woothemes_sensei_certificates->plugin_url() . '/assets/images/help.png' ) . '" height="16" width="16" />';

		} else {

			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

		}
	}

	echo '</p>';
}


/**
 * Output a hidden input box.
 *
 * @access public
 * @since  1.0.0
 * @param  array $field
 * @return void
 */
function certificates_wp_hidden_input( $field ) {

	global $thepostid, $post;

	$thepostid      = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['value'] = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
	$field['class'] = isset( $field['class'] ) ? $field['class'] : '';

	echo '<input type="hidden" class="' . esc_attr( $field['class'] ) . '" name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" /> ';
}


/**
 * Output a textarea input box.
 *
 * @access public
 * @since  1.0.0
 * @param  array $field
 * @return void
 */
function certificates_wp_textarea_input( $field ) {

	global $thepostid, $post, $woothemes_sensei_certificates;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><textarea class="' . esc_attr( $field['class'] ) . '" name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" rows="6" cols="20">' . esc_textarea( $field['value'] ) . '</textarea> ';

	if ( ! empty( $field['description'] ) ) {

		if ( isset( $field['desc_tip'] ) ) {

			echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . esc_url( $woothemes_sensei_certificates->plugin_url() . '/assets/images/help.png' ) . '" height="16" width="16" />';

		} else {

			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

		}
	}

	echo '</p>';
}


/**
 * Output a checkbox input box.
 *
 * @access public
 * @since  1.0.0
 * @param  array $field
 * @return void
 */
function certificates_wp_checkbox( $field ) {

	global $thepostid, $post;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'checkbox';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
	$field['cbvalue']       = isset( $field['cbvalue'] ) ? $field['cbvalue'] : 'yes';

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><input type="checkbox" class="' . esc_attr( $field['class'] ) . '" name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['cbvalue'] ) . '" ' . checked( $field['value'], $field['cbvalue'], false ) . ' /> ';

	if ( ! empty( $field['description'] ) ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</p>';
}


/**
 * Output a select input box.
 *
 * @access public
 * @since  1.0.0
 * @param  array $field
 * @return void
 */
function certificates_wp_select( $field ) {

	global $thepostid, $post, $woothemes_sensei_certificates;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['id'] ) . '" class="' . esc_attr( $field['class'] ) . '">';

	foreach ( $field['options'] as $key => $value ) {

		echo '<option value="' . esc_attr( $key ) . '" ' . selected( esc_attr( $field['value'] ), esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';

	}

	echo '</select> ';

	if ( ! empty( $field['description'] ) ) {

		if ( isset( $field['desc_tip'] ) ) {

			echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . esc_url( $woothemes_sensei_certificates->plugin_url() . '/assets/images/help.png' ) . '" height="16" width="16" />';

		} else {

			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

		}
	}

	echo '</p>';
}

/**
 * Output a radio input box.
 *
 * @access public
 * @since  1.0.0
 * @param  array $field
 * @return void
 */
function certificates_wp_radio( $field ) {

	global $thepostid, $post, $woothemes_sensei_certificates;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );

	echo '<fieldset class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><legend>' . wp_kses_post( $field['label'] ) . '</legend><ul>';

	if ( ! empty( $field['description'] ) ) {

		echo '<li class="description">' . wp_kses_post( $field['description'] ) . '</li>';

	}

	foreach ( $field['options'] as $key => $value ) {

		echo '<li><label><input
				name="' . esc_attr( $field['id'] ) . '"
				value="' . esc_attr( $key ) . '"
				type="radio"
				class="' . esc_attr( $field['class'] ) . '"
				' . checked( esc_attr( $field['value'] ), esc_attr( $key ), false ) . '
				/> ' . esc_html( $value ) . '</label>
		</li>';

	}

	echo '</ul></fieldset>';
}
