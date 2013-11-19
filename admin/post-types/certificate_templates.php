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
 * Admin functions for the certificate_template post type
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'bulk_actions-edit-certificate_template', 'certificate_template_edit_voucher_bulk_actions' );

/**
 * Remove the bulk edit action for vouchers, it really isn't useful
 *
 * @since 1.0
 * @param array $actions associative array of action identifier to name
 *
 * @return array associative array of action identifier to name
 */
function certificate_template_edit_voucher_bulk_actions( $actions ) {

	unset( $actions['edit'] );

	return $actions;
}


add_filter( 'views_edit-certificate_template', 'certificate_template_edit_voucher_views' );

/**
 * Modify the 'views' links, ie All (3) | Publish (1) | Draft (1) | Private (2) | Trash (3)
 * shown above the vouchers list table, to hide the publish/private states,
 * which are not important and confusing for voucher objects.
 *
 * @since 1.0
 * @param array $views associative-array of view state name to link
 *
 * @return array associative array of view state name to link
 */
function certificate_template_edit_voucher_views( $views ) {

	// publish and private are not important distinctions for vouchers
	unset( $views['publish'], $views['private'] );

	return $views;
}


add_filter( 'manage_edit-certificate_template_columns', 'certificate_template_edit_voucher_columns' );

/**
 * Columns for Vouchers page
 *
 * @since 1.0
 * @param array $columns associative-array of column identifier to header names
 *
 * @return array associative-array of column identifier to header names for the vouchers page
 */
function certificate_template_edit_voucher_columns( $columns ){

	$columns = array();

	$columns['cb']             = '<input type="checkbox" />';
	$columns['name']           = __( 'Name', 'woothemes-sensei' );
	$columns['thumb']          = __( 'Image', 'woothemes-sensei' );

	return $columns;
}


add_action( 'manage_certificate_template_posts_custom_column', 'certificate_template_custom_voucher_columns', 2 );


/**
 * Custom Column values for Vouchers page
 *
 * @since 1.0
 * @param string $column column identifier
 */
function certificate_template_custom_voucher_columns( $column ) {
	global $post, $woocommerce;

	$voucher = new WC_Voucher( $post->ID );

	switch ( $column ) {
		case 'thumb':
			$edit_link = get_edit_post_link( $post->ID );
			echo '<a href="' . $edit_link . '">' . $voucher->get_image() . '</a>';
		break;

		case 'name':
			$edit_link = get_edit_post_link( $post->ID );
			$title = _draft_or_post_title();

			$post_type_object = get_post_type_object( $post->post_type );
			$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $post->ID );

			echo '<strong><a class="row-title" href="' . $edit_link . '">' . $title . '</a>';

			// display post states a little more selectively than _post_states( $post );
			if ( 'draft' == $post->post_status ) {
				echo " - <span class='post-state'>" . __( 'Draft', 'woothemes-sensei' ) . '</span>';
			}

			echo '</strong>';

			// Get actions
			$actions = array();

			$actions['id'] = 'ID: ' . $post->ID;

			if ( current_user_can( $post_type_object->cap->delete_post, $post->ID ) ) {
				if ( 'trash' == $post->post_status )
					$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash', 'woothemes-sensei' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-' . $post->post_type . '_' . $post->ID ) . "'>" . __( 'Restore', 'woothemes-sensei' ) . "</a>";
				elseif ( EMPTY_TRASH_DAYS )
					$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash', 'woothemes-sensei' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash', 'woothemes-sensei' ) . "</a>";
				if ( 'trash' == $post->post_status || ! EMPTY_TRASH_DAYS )
					$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently', 'woothemes-sensei' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently', 'woothemes-sensei' ) . "</a>";
			}

			// TODO: add a duplicate voucher action?

			$actions = apply_filters( 'post_row_actions', $actions, $post );

			echo '<div class="row-actions">';

			$i = 0;
			$action_count = count( $actions );

			foreach ( $actions as $action => $link ) {
				( $action_count - 1 == $i ) ? $sep = '' : $sep = ' | ';
				echo '<span class="' . $action . '">' . $link . $sep . '</span>';
				$i++;
			}
			echo '</div>';
		break;

	}
}
