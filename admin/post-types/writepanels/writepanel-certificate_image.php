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
 * Functions for displaying the certificate primary image meta box
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Display the certificate image meta box
 * Fluid image reference: http://unstoppablerobotninja.com/entry/fluid-images
 *
 * @since 1.0
 */
function certificate_template_image_meta_box() {
	global $post, $woocommerce;

	$image_src = '';
	$image_id  = '';

	$image_ids = get_post_meta( $post->ID, '_image_ids', true );

	if ( is_array( $image_ids ) && count( $image_ids ) > 0 ) {
		$image_id = $image_ids[0];
		$image_src = wp_get_attachment_url( $image_id );
	}

	$attachment = wp_get_attachment_metadata( $image_id );

	?>
	<div id="certificate_image_wrapper" style="position:relative;">
		<img id="certificate_image_0" src="<?php echo $image_src ?>" style="max-width:100%;" />
	</div>
	<input type="hidden" name="upload_image_id[0]" id="upload_image_id_0" value="<?php echo $image_id; ?>" />
	<p>
		<a title="<?php esc_attr_e( 'Set certificate image', 'woothemes-sensei' ) ?>" href="#" id="set-certificate-image"><?php _e( 'Set certificate image', 'woothemes-sensei' ) ?></a>
		<a title="<?php esc_attr_e( 'Remove certificate image', 'woothemes-sensei' ) ?>" href="#" id="remove-certificate-image" style="<?php echo ( ! $image_id ? 'display:none;' : '' ); ?>"><?php _e( 'Remove certificate image', 'woothemes-sensei' ) ?></a>
	</p>
	<?php
}
