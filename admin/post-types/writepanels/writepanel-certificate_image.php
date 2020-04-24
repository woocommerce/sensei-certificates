<?php
/**
 * Sensei LMS Certificates Templates.
 *
 * All functionality pertaining to the Certificate Templates functionality in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Extension
 * @author Automattic
 * @since 1.0.0
 */

/**
 * TABLE OF CONTENTS
 *
 * - Requires
 * - Actions and Filters
 * - certificate_template_image_meta_box()
 * - certificate_template_process_images_meta()
 */

/**
 * Functions for displaying the certificate primary image meta box.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Actions and Filters.
 */
add_action( 'sensei_process_certificate_template_meta', 'certificate_template_process_images_meta', 10, 2 );

/**
 * Display the certificate image meta box.
 * Fluid image reference: http://unstoppablerobotninja.com/entry/fluid-images.
 *
 * @since 1.0.0
 */
function certificate_template_image_meta_box() {

	global $post, $woocommerce;

	$image_src = '';
	$image_id  = '';

	$image_ids = get_post_meta( $post->ID, '_image_ids', true );

	if ( is_array( $image_ids ) && count( $image_ids ) > 0 ) {

		if ( is_numeric( $image_ids[0] ) ) {

			$image_id   = $image_ids[0];
			$image_src  = wp_get_attachment_url( $image_id );
			$attachment = wp_get_attachment_metadata( $image_id );

		}
	}

	?>
	<div id="certificate_image_wrapper" style="position:relative;">
		<img id="certificate_image_0" src="<?php echo esc_attr( $image_src ); ?>" style="max-width:100%;" />
	</div>
	<input type="hidden" name="upload_image_id[0]" id="upload_image_id_0" value="<?php echo esc_attr( $image_id ); ?>" />
	<p>
		<a title="<?php esc_attr_e( 'Set certificate image', 'sensei-certificates' ); ?>" href="#" id="set-certificate-image"><?php esc_html_e( 'Set certificate image', 'sensei-certificates' ); ?></a>
		<a title="<?php esc_attr_e( 'Remove certificate image', 'sensei-certificates' ); ?>" href="#" id="remove-certificate-image" style="<?php echo ( ! $image_id ? 'display:none;' : '' ); ?>"><?php esc_html_e( 'Remove certificate image', 'sensei-certificates' ); ?></a>
	</p>
	<?php
}


/**
 * Certificate Templates Images Data Save.
 *
 * Function for processing and storing certificate template images.
 *
 * @since 1.0.0
 * @param int    $post_id The certificate template id.
 * @param object $post    The certificate template post object.
 */
function certificate_template_process_images_meta( $post_id, $post ) {
	if (
		empty( $_POST['certificates_meta_nonce'] )
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Leave nonce value unmodified.
		|| ! wp_verify_nonce( wp_unslash( $_POST['certificates_meta_nonce'] ), 'certificates_save_data' )
		|| empty( $_POST['upload_image_id'] )
	) {
		return;
	}

	// Handle the image_ids meta, which will always have at least an index 0 for the main template image, even if the value is empty.
	$image_ids       = array();
	$upload_image_id = array_map( 'intval', wp_unslash( $_POST['upload_image_id'] ) );

	foreach ( $upload_image_id as $i => $image_id ) {

		if ( 0 == $i || $image_id ) {
			$image_ids[] = $image_id !== 0 ? $image_id : '';
		}
	}

	update_post_meta( $post_id, '_image_ids', $image_ids );

	if ( $image_ids[0] ) {
		set_post_thumbnail( $post_id, $image_ids[0] );
	} else {
		delete_post_thumbnail( $post_id );
	}
}
