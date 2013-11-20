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
 * Sets up the write panels used by vouchers (custom post types)
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once( 'writepanel-certificate_image.php' );
include_once( 'writepanel-certificate_data.php' );
include_once( 'writepanel-course_data.php' );


add_action( 'add_meta_boxes', 'certificate_templates_meta_boxes' );


/**
 * Add and remove meta boxes from the Voucher edit page and Order edit page
 *
 * @since 1.0
 * @see woocommerce_woocommerce-order-vouchers()
 */
function certificate_templates_meta_boxes() {

	// Voucher Primary Image box
	add_meta_box(
		'sensei-certificate-image',
		__( 'Certificate Background Image <small>&ndash; Used to lay out the certificate fields found in the Certificate Data box.</small>', 'woothemes-sensei' ),
		'certificate_template_image_meta_box',
		'certificate_template',
		'normal',
		'high'
	);

	// Voucher Data box
	add_meta_box(
		'sensei-certificate-data',
		__( 'Certificate Data', 'woothemes-sensei' ),
		'certificate_template_data_meta_box',
		'certificate_template',
		'normal',
		'high'
	);

	// Voucher Data box
	add_meta_box(
		'sensei-course-certificate-data',
		__( 'Certificate Template', 'woothemes-sensei' ),
		'course_certificate_template_data_meta_box',
		'course',
		'side',
		'core'
	);

	// remove unnecessary meta boxes
	remove_meta_box( 'wpseo_meta', 'certificate_template', 'normal' );
	remove_meta_box( 'woothemes-settings', 'certificate_template', 'normal' );
	remove_meta_box( 'commentstatusdiv',   'certificate_template', 'normal' );
	remove_meta_box( 'slugdiv',            'certificate_template', 'normal' );
}


add_filter( 'enter_title_here', 'certificate_templates_enter_title_here', 1, 2 );

/**
 * Set a more appropriate placeholder text for the New Voucher title field
 *
 * @since 1.0
 * @param string $text "Enter Title Here" string
 * @param object $post post object
 *
 * @return string "Certificate Template Name" when the post type is certificate_template
 */
function certificate_templates_enter_title_here( $text, $post ) {
	if ( 'certificate_template' == $post->post_type ) return __( 'Certificate Template', 'woothemes-sensei' );
	return $text;
}


add_action( 'save_post', 'certificate_templates_meta_boxes_save', 1, 2 );
add_action( 'save_post', 'course_certificate_templates_meta_boxes_save', 1, 2 );

/**
 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
 *
 * @since 1.0
 * @param int $post_id post identifier
 * @param object $post post object
 */
function certificate_templates_meta_boxes_save( $post_id, $post ) {
	if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( is_int( wp_is_post_revision( $post ) ) ) return;
	if ( is_int( wp_is_post_autosave( $post ) ) ) return;
	if ( empty($_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( 'certificate_template' != $post->post_type ) return;

	do_action( 'sensei_process_certificate_template_meta', $post_id, $post );

	woocommerce_meta_boxes_save_errors();
}

/**
 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
 *
 * @since 1.0
 * @param int $post_id post identifier
 * @param object $post post object
 */
function course_certificate_templates_meta_boxes_save( $post_id, $post ) {
	if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( is_int( wp_is_post_revision( $post ) ) ) return;
	if ( is_int( wp_is_post_autosave( $post ) ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( 'course' != $post->post_type ) return;
	do_action( 'sensei_process_course_certificate_template_meta', $post_id, $post );
}


add_action( 'publish_certificate_template', 'certificate_template_private', 10, 2 );

/**
 * Automatically make the voucher posts private when they are published.
 * That way we can have them be publicly_queryable for the purposes of
 * generating a preview pdf for the admin user, while having them always
 * hidden on the frontend (draft posts are not visible by definition)
 *
 * @since 1.0
 * @param int $post_id the voucher identifier
 * @param object $post the voucher object
 */
function certificate_template_private( $post_id, $post ) {
	global $wpdb;

	$wpdb->update( $wpdb->posts, array( 'post_status' => 'private' ), array( 'ID' => $post_id ) );
}


/**
 * Rendres a custom admin input field to select a font which includes font
 * family, size and style (bold/italic)
 *
 * @since 1.0
 */
function certificate_templates_wp_font_select( $field ) {
	global $thepostid, $post, $woocommerce;

	if ( ! $thepostid ) $thepostid = $post->ID;

	// values
	$font_family_value = $font_size_value = $font_style_value = '';

	if ( '_certificate' == $field['id'] ) {
		// voucher defaults
		$font_family_value = get_post_meta( $thepostid, $field['id'] . '_font_family', true );
		$font_size_value   = get_post_meta( $thepostid, $field['id'] . '_font_size',   true );
		$font_style_value  = get_post_meta( $thepostid, $field['id'] . '_font_style',  true );
	} else {
		// field-specific overrides
		$certificate_fields = get_post_meta( $thepostid, '_certificate_fields', true );
		$field_name = ltrim( $field['id'], '_' );

		if ( is_array( $certificate_fields ) ) {
			if ( isset( $certificate_fields[ $field_name ]['font']['family'] ) ) $font_family_value = $certificate_fields[ $field_name ]['font']['family'];
			if ( isset( $certificate_fields[ $field_name ]['font']['size'] ) )   $font_size_value   = $certificate_fields[ $field_name ]['font']['size'];
			if ( isset( $certificate_fields[ $field_name ]['font']['style'] ) )  $font_style_value  = $certificate_fields[ $field_name ]['font']['style'];
		}
	}

	// defaults
	if ( ! $font_size_value && isset( $field['font_size_default'] ) ) $font_size_value = $field['font_size_default'];

	echo '<p class="form-field ' . $field['id'] . '_font_family_field"><label for="' . $field['id'] . '_font_family">' . $field['label'] . '</label><select id="' . $field['id'] . '_font_family" name="' . $field['id'] . '_font_family" class="select short">';

	foreach ( $field['options'] as $key => $value ) {
		echo '<option value="' . $key . '" ';
		selected( $font_family_value, $key );
		echo '>' . $value . '</option>';
	}

	echo '</select> ';

	echo '<input type="text" style="width:auto;margin-left:10px;" size="2" name="' . $field['id'] . '_font_size" id="' . $field['id'] . '_font_size" value="' . esc_attr( $font_size_value ) . '" placeholder="' . __( 'Size', 'woothemes-sensei' ) . '" /> ';

	echo '<label for="' . $field['id'] . '_font_style_b" style="width:auto;margin:0 5px 0 10px;">' . __( 'Bold', 'woothemes-sensei' ) . '</label><input type="checkbox" class="checkbox" style="margin-top:4px;" name="' . $field['id'] . '_font_style_b" id="' . $field['id'] . '_font_style_b" value="yes" ';
	checked( false !== strpos( $font_style_value, 'B' ), true );
	echo ' /> ';

	echo '<label for="' . $field['id'] . '_font_style_i" style="width:auto;margin:0 5px 0 10px;">' . __( 'Italic', 'woothemes-sensei' ) . '</label><input type="checkbox" class="checkbox" style="margin-top:4px;" name="' . $field['id'] . '_font_style_i" id="' . $field['id'] . '_font_style_i" value="yes" ';
	checked( false !== strpos( $font_style_value, 'I' ), true );
	echo ' /> ';

	echo '</p>';
}


/**
 * Add inline javascript to activate the farbtastic color picker element.
 * Must be called in order to use the certificate_templates_wp_color_picker() method
 *
 * @since 1.0
 */
function certificate_templates_wp_color_picker_js() {
	global $woocommerce;

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
	$woocommerce->add_inline_js( $javascript );
}


/**
 * Renders a custom admin control used on the voucher edit page to Set/Remove
 * the position via two buttons
 *
 * @since 1.0
 */
function certificate_templates_wp_position_picker( $field ) {
	global $woocommerce;

	if ( ! isset( $field['value'] ) ) $field['value'] = '';

	echo '<p class="form-field"><label>' . $field['label'] . '</label><input type="button" id="' . $field['id'] . '" class="set_position button" value="' . esc_attr__( 'Set Position', 'woothemes-sensei' ) . '" style="width:auto;" /> <input type="button" id="remove_' . $field['id'] . '" class="remove_position button" value="' . esc_attr__( 'Remove Position', 'woothemes-sensei' ) . '" style="width:auto;' . ( $field['value'] ? '' : 'display:none' ) . ';margin-left:7px;" />';

	if ( isset( $field['description'] ) && $field['description'] ) {

		if ( isset( $field['desc_tip'] ) ) {
			echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />';
		} else {
			echo '<span class="description">' . $field['description'] . '</span>';
		}
	}
	echo '</p>';
}
