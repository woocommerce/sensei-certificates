<?php
/**
 * WooThemes Sensei Certificates Templates Admin
 *
 * @package   woothemes-sensei-certificates/Admin
 * @author    WooThemes
 * @copyright Copyright (c) 2012-2013, WooThemes, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * Main admin file which loads all Template panels
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


add_action( 'admin_head', 'sensei_certificate_template_admin_menu_highlight' );

/**
 * Highlight the correct top level admin menu item for the voucher post type add screen
 *
 * @since 1.0
 */
function sensei_certificate_template_admin_menu_highlight() {

	global $menu, $submenu, $parent_file, $submenu_file, $self, $post_type, $taxonomy;

	if ( isset( $post_type ) && 'certificate_template' == $post_type ) {
		$submenu_file = 'edit.php?post_type=' . $post_type;
		$parent_file  = 'sensei';
	}
}


add_action( 'admin_init', 'sensei_certificate_template_admin_init' );

/**
 * Initialize the admin, adding actions to properly display and handle
 * the Voucher custom post type add/edit page
 *
 * @since 1.0
 */
function sensei_certificate_template_admin_init() {
	global $pagenow;

	if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow || 'edit.php' == $pagenow ) {

		include_once( 'post-types/writepanels/writepanels-init.php' );

		// add voucher list/edit pages contextual help
		add_action( 'admin_print_styles', 'sensei_certificate_template_admin_help_tab' );
	}
}


/**
 * Adds the Vouchers Admin Help tab to the Vouchers admin screens
 *
 * @since 1.0
 */
function sensei_certificate_template_admin_help_tab() {
	$screen = get_current_screen();

	if ( 'edit-certificate_template' != $screen->id && 'certificate_template' != $screen->id ) return;

	$screen->add_help_tab( array(
		'id'      => 'sensei_certificate_template_overview_help_tab',
		'title'   => __( 'Overview', 'woothemes-sensei' ),
		'content' => '<p>' . __( 'The WooCommerce PDF Product Vouchers plugin allows you to create and configure customizable vouchers which can be attached to simple/variable downloadable products and purchased by your customers.  You can give your customers the ability to set a recipient name/message and purchase these vouchers as a gift, and allow them to choose from among a set of voucher images.  Once the purchase is made the customer will have access to a custom-generated PDF voucher in the same manner as a standard downloadable product.', 'woothemes-sensei' ) . '</p>',
	) );

	$screen->add_help_tab( array(
		'id'       => 'sensei_certificate_template_voucher_help_tab',
		'title'    => __( 'Editing a Voucher', 'woothemes-sensei' ),
		'callback' => 'sensei_certificate_template_voucher_help_tab_content',
	) );

	$screen->add_help_tab( array(
		'id'      => 'sensei_certificate_template_list_help_tab',
		'title'   => __( 'Vouchers List', 'woothemes-sensei' ),
		'content' => '<p>' . __( 'From the list view you can review all your voucher templates, quickly see the name, primary default image and optional expiry days, and trash a voucher template.', 'woothemes-sensei' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'       => 'sensei_certificate_template_how_to_help_tab',
		'title'    => __( 'How To', 'woothemes-sensei' ),
		'callback' => 'sensei_certificate_template_how_to_help_tab_content',
	) );

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'woothemes-sensei' ) . '</strong></p>' .
		'<p><a href="http://docs.woothemes.com/document/pdf-product-vouchers/" target="_blank">' . __( 'Vouchers Docs', 'woothemes-sensei' ) . '</a></p>'
	);
}


/**
 * Renders the Voucher help tab content for the contextual help menu
 *
 * @since 1.0
 */
function sensei_certificate_template_voucher_help_tab_content() {
	?>
	<p><strong><?php _e( 'Voucher Name', 'woothemes-sensei' ) ?></strong> - <?php _e( 'All voucher templates must be given a name.  This will be used to identify the voucher within the admin; from the frontend the voucher will be identified to the customer by a unique voucher number.', 'woothemes-sensei' ) ?></p>
	<p><strong><?php _e( 'Primary Voucher Image', 'woothemes-sensei' ) ?></strong> - <?php _e( 'This is the main image for your voucher, and will be used to configure the layout of the various text fields defined in the Voucher Data panel.', 'woothemes-sensei' ) ?></p>
	<p><strong><?php _e( 'Voucher Data', 'woothemes-sensei' ) ?></strong> - <?php _e( 'These configuration options allow you to specify exactly where various text fields will be displayed on your voucher, as well as the font used.  For instance, if you want the product name displayed on your voucher, click the "Set Position" button next to "Product Name Position".  Then select the area of the Voucher Image where you want the product name to be displayed.', 'woothemes-sensei' ) ?></p>
	<p><?php _e( 'You can define a default font, size, style and color to be used for the voucher text fields.  For each individual text field, you can override these defaults by setting a specific font/style, size or color.  Note that the default font style (Italic/Bold) will only be used if a font is not selected at the field level.', 'woothemes-sensei' ) ?></p>
	<p><strong><?php _e( 'Alternative Images', 'woothemes-sensei' ) ?></strong> - <?php _e( 'You can add alternative voucher images so your customers can choose from multiple backgrounds when purchasing a voucher.  Just make sure all the images have the same layout so the voucher text fields are put in the correct position.', 'woothemes-sensei' ) ?></p>
	<p><strong><?php _e( 'Additional Image', 'woothemes-sensei' ) ?></strong> - <?php _e( 'You can add a second page to the voucher with this option, containing for instance voucher instructions or policies.  As with the alternative images, ensure that this additional image has the same dimensions as the primary voucher image.', 'woothemes-sensei' ) ?></p>
	<p><strong><?php _e( 'Previewing', 'woothemes-sensei' ) ?></strong> - <?php _e( 'You must update the voucher to see any changes in the voucher Preview.', 'woothemes-sensei' ) ?></p>
	<?php
}


/**
 * Renders the "How To" help tab content for the contextual help menu
 *
 * @since 1.0
 */
function sensei_certificate_template_how_to_help_tab_content() {
	?>
	<p><strong><?php _e( 'How to Create Your First Voucher Product', 'woothemes-sensei' ) ?></strong></p>
	<ol>
		<li><?php _e( 'First go to WooCommerce &gt; Vouchers and click "Add Voucher" to add a voucher template', 'woothemes-sensei' ); ?></li>
		<li><?php _e( 'Set a Voucher Name, and Primary Voucher Image.  Optionally configure and add some Voucher Data fields (see the "Editing a Voucher" section for more details)', 'woothemes-sensei' ); ?></li>
		<li><?php _e( 'Next click "Publish" to save your voucher template.  You can also optionally "Preview" the voucher to check your work and field layout.', 'woothemes-sensei' ); ?></li>
		<li><?php _e( 'Next go to WooCommerce &gt; Products and either create a new product or edit an existing one, being sure to make it either Simple or Variable, and checking the "Downloadable" option (probably also checking the "Virtual" option, unless you plan on mailing hard copies of the product Vouchers).', 'woothemes-sensei' ); ?></li>
		<li><?php _e( 'With the "Downloadable" option checked on a Simple type product you should see a field named "Voucher" in the General Product Data tab with a select box containing your newly created voucher template.  With a "Variable" type product the field will appear in the Variations area.  Select your voucher and save the product.', 'woothemes-sensei' ); ?></li>
		<li><?php _e( 'Your product voucher is now available for purchase!  Run a test transaction from the frontend, you should see the voucher primary image(s) displayed on the product page, along with the optional Recipient Name/Messag fields if you added them.  The voucher PDF will be available via downloadable link the same as any standard downloadable produt.', 'woothemes-sensei' ); ?></li>
	</ol>
	<?php
}


include_once( 'post-types/certificate_templates.php' );


add_action( 'admin_enqueue_scripts', 'sensei_certificate_template_admin_enqueue_scripts' );

/**
 * Enqueue the vouchers admin scripts
 *
 * @since 1.0
 */
function sensei_certificate_template_admin_enqueue_scripts() {
	global $woocommerce, $post, $woothemes_sensei_certificates, $wp_version;

	// Get admin screen id
	$screen = get_current_screen();

	// TODO: check for $screen->id == 'edit-certificate_template' and enqueue woocommerce_admin_styles?

	// WooCommerce admin pages
	if ( 'certificate_template' == $screen->id ) {

		// color picker script/styles
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_media();

		// image area select, for selecting the voucher fields
		wp_enqueue_script( 'imgareaselect' );
		wp_enqueue_style( 'imgareaselect' );

		// wp_enqueue_script( 'woocommerce_writepanel' );

		// make sure the woocommerce admin styles are available for both the voucher edit page, and list page
		wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
	}

	if ( in_array( $screen->id, array( 'certificate_template' ) ) ) {

		// default javascript params
		$sensei_certificate_templates_params = array( 'primary_image_width' => '', 'primary_image_height' => '' );

		if ( 'certificate_template' == $screen->id ) {
			// get the primary image dimensions (if any) which are needed for the page script
			$attachment = null;
			$image_ids = get_post_meta( $post->ID, '_image_ids', true );

			if ( is_array( $image_ids ) && isset( $image_ids[0] ) && $image_ids[0] ) {
				$attachment = wp_get_attachment_metadata( $image_ids[0] );
			}

			// pass parameters into the javascript file
			$sensei_certificate_templates_params = array(
				'done_label'           => __( 'Done', 'woothemes-sensei' ),
				'set_position_label'   => __( 'Set Position', 'woothemes-sensei' ),
				'post_id'              => $post->ID,
				'primary_image_width'  => isset( $attachment['width']  ) && $attachment['width']  ? $attachment['width']  : '0',
				'primary_image_height' => isset( $attachment['height'] ) && $attachment['height'] ? $attachment['height'] : '0',
			 );
		}

		wp_enqueue_script( 'sensei_certificate_templates_admin', $woothemes_sensei_certificates->plugin_url . 'assets/js/admin.js', array( 'jquery' ) );
		wp_localize_script( 'sensei_certificate_templates_admin', 'sensei_certificate_templates_params', $sensei_certificate_templates_params );

		wp_enqueue_style( 'sensei_certificate_templates_admin_styles', $woothemes_sensei_certificates->plugin_url . '/assets/css/admin.css' );
	} // End If Statement

	if ( in_array( $screen->id, array( 'course' ) ) ) {

		wp_enqueue_script( 'sensei_course_certificate_templates_admin', $woothemes_sensei_certificates->plugin_url . 'assets/js/course.js', array( 'jquery', 'woosensei-lesson-metadata', 'woosensei-lesson-chosen' ) );

	} // End If Statement
}


add_filter( 'post_updated_messages', 'sensei_certificate_template_product_updated_messages' );

/**
 * Set the product updated messages so they're specific to the Vouchers
 *
 * @since 1.0
 */
function sensei_certificate_template_product_updated_messages( $messages ) {
	global $post, $post_ID;

	$messages['certificate_template'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __( 'Certificate Template updated.', 'woothemes-sensei' ),
		2 => __( 'Custom field updated.', 'woothemes-sensei' ),
		3 => __( 'Custom field deleted.', 'woothemes-sensei' ),
		4 => __( 'Certificate Template updated.', 'woothemes-sensei'),
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Certificate Template restored to revision from %s', 'woothemes-sensei' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __( 'Certificate Template updated.', 'woothemes-sensei' ),
		7 => __( 'Certificate Template saved.', 'woothemes-sensei' ),
		8 => __( 'Certificate Template submitted.', 'woothemes-sensei' ),
		9 => sprintf( __( 'Certificate Template scheduled for: <strong>%1$s</strong>.', 'woothemes-sensei' ),
		  date_i18n( __( 'M j, Y @ G:i', 'woothemes-sensei' ), strtotime( $post->post_date ) ) ),
		10 => __( 'Certificate Template draft updated.', 'woothemes-sensei'),
	);

	return $messages;
}
