<?php
/**
 * Sensei LMS Certificates functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Function sensei_certificates_updates_list add sensei certificates updates to sensei list.
 *
 * @since  1.0.0
 * @param  array $updates List of existing updates.
 * @return array $updates List of existing and new updates.
 */
function sensei_certificates_updates_list( $updates ) {

	$updates['1.0.0'] = array(
		'auto'   => array(),
		'manual' => array(
			'sensei_update_users_certificate_data'      => array(
				'title'   => 'Create Certificates',
				'desc'    => 'Creates certificates for learners who have already completed Courses.',
				'product' => 'Sensei LMS Certificates',
			),
			'sensei_create_master_certificate_template' => array(
				'title'   => 'Create Master Certificate Template',
				'desc'    => 'Creates the master Certificate Template for all Courses.',
				'product' => 'Sensei LMS Certificates',
			),
		),
	);

	return $updates;

}

/**
 * Function sensei_certificates_add_update_functions_to_whitelist.
 *
 * @param  array $permitted_functions Permitted functions.
 * @return array
 */
function sensei_certificates_add_update_functions_to_whitelist( $permitted_functions ) {
	return array_merge(
		$permitted_functions,
		array(
			'sensei_update_users_certificate_data',
			'sensei_create_master_certificate_template',
		)
	);
}

/**
 * Function sensei_update_users_certificate_data install user certificate data.
 *
 * @since  1.0.0
 * @param  int $n      Number of items to iterate through.
 * @param  int $offset Number to offset iteration by.
 * @return boolean
 */
function sensei_update_users_certificate_data( $n = 5, $offset = 0 ) {
	// Calculate if this is the last page.
	if ( 0 == $offset ) {
		$current_page = 1;
	} else {
		$current_page = intval( $offset / $n );
	}

	$args_array     = array(
		'number'  => $n,
		'offset'  => $offset,
		'orderby' => 'ID',
		'order'   => 'DESC',
		'fields'  => 'all_with_meta',
	);
	$wp_user_update = new WP_User_Query( $args_array );
	$users          = $wp_user_update->get_results();

	$user_count  = count_users();
	$total_items = $user_count['total_users'];

	$total_pages = intval( ceil( $total_items / $n ) );
	if ( ! class_exists( 'Woothemes_Sensei_Certificate_Data_Store' ) ) {
		include_once 'classes/class-woothemes-sensei-certificates-data-store.php';
	}

	$data_store = new Woothemes_Sensei_Certificate_Data_Store();

	foreach ( $users as $user_key => $user_item ) {
		$user_id              = absint( $user_item->ID );
		$user_course_statuses = Sensei_Utils::sensei_check_for_activity(
			array(
				'user_id' => $user_item->ID,
				'type'    => 'sensei_course_status',
				'status'  => 'complete',
			),
			true
		);
		if ( ! is_array( $user_course_statuses ) ) {
			$user_course_statuses = array( $user_course_statuses );
		}

		if ( empty( $user_course_statuses ) ) {
			continue;
		}

		foreach ( $user_course_statuses as $user_course_status ) {
			$course_id                = absint( $user_course_status->comment_post_ID );
			$user_did_complete_course = Sensei_Utils::user_completed_course( $course_id, $user_id );
			if ( true === $user_did_complete_course ) {
				$args  = array(
					'post_type'  => 'certificate',
					'author'     => $user_item->ID,
					'meta_key'   => 'course_id',
					'meta_value' => $course_id,
				);
				$query = new WP_Query( $args );

				if ( ! $query->have_posts() ) {
					$data_store->insert( $user_id, $course_id );
				}

				wp_reset_query();

			}
		}
	}

	return ( $current_page >= $total_pages ) ? true : false;
}

/**
 * Function sensei_create_master_certificate_template Creates the example
 * Certificate Template and assigns to every Course.
 *
 * @since  1.0.0
 * @return boolean
 */
function sensei_create_master_certificate_template() {

	// Register Post Data.
	$post                 = array();
	$post['post_status']  = 'private';
	$post['post_type']    = 'certificate_template';
	$post['post_title']   = __( 'Example Template', 'sensei-certificates' );
	$post['post_content'] = '';

	// Create Post.
	$post_id = wp_insert_post( $post );

	$url = trailingslashit( plugins_url( '', __FILE__ ) ) . 'assets/images/sensei_certificate_nograde.png';
	if ( ! function_exists( 'download_url' ) ) {
		include_once ABSPATH . '/wp-admin/includes/file.php';
	}
	$tmp     = download_url( $url );
	$post_id = $post_id;
	$desc    = __( 'Sensei LMS Certificate Template Example', 'sensei-certificates' );

	// Set variables for storage.
	// fix file filename for query strings.
	preg_match( '/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches );
	$file_array['name']     = basename( $matches[0] );
	$file_array['tmp_name'] = $tmp;

	// If error storing temporarily, unlink.
	if ( is_wp_error( $tmp ) ) {
		@unlink( $file_array['tmp_name'] );
		$file_array['tmp_name'] = '';
		error_log( 'An error occurred while uploading the image' );
	}

	if ( ! function_exists( 'media_handle_sideload' ) ) {
		include_once ABSPATH . '/wp-admin/includes/image.php';
		include_once ABSPATH . '/wp-admin/includes/media.php';
	}

	// Do the validation and storage stuff.
	$image_id = media_handle_sideload( $file_array, $post_id, $desc );

	// If error storing permanently, unlink.
	if ( is_wp_error( $image_id ) ) {
		@unlink( $file_array['tmp_name'] );
		error_log( 'An error occurred while uploading the image' );
	}

	$src = wp_get_attachment_url( $image_id );

	$defaults = array(
		'_certificate_font_color'             => '#000000',
		'_certificate_font_size'              => '12',
		'_certificate_font_family'            => 'Helvetica',
		'_certificate_font_style'             => '',
		'_certificate_heading'                => '',
		'_certificate_heading_pos'            => '114,11,989,57',
		'_certificate_heading_font_color'     => '#595959',
		'_certificate_heading_font_size'      => '25',
		'_certificate_heading_font_family'    => 'Helvetica',
		'_certificate_heading_font_style'     => 'C',
		'_certificate_heading_text'           => __( 'Certificate of Completion', 'sensei-certificates' ),
		'_certificate_message'                => '',
		'_certificate_message_pos'            => '110,306,996,167',
		'_certificate_message_font_color'     => '#000000',
		'_certificate_message_font_size'      => '36',
		'_certificate_message_font_family'    => 'Helvetica',
		'_certificate_message_font_style'     => 'BC',
		'_certificate_message_text'           => __( 'This is to certify that', 'sensei-certificates' ) . " \r\n\r\n" . '{{learner}}' . " \r\n\r\n" . __( 'has completed the course', 'sensei-certificates' ),
		'_certificate_course'                 => '',
		'_certificate_course_pos'             => '186,88,838,116',
		'_certificate_course_font_color'      => '#000000',
		'_certificate_course_font_size'       => '48',
		'_certificate_course_font_family'     => 'Helvetica',
		'_certificate_course_font_style'      => 'BCO',
		'_certificate_course_text'            => __( '{{course_title}}', 'sensei-certificates' ),
		'_certificate_completion'             => '',
		'_certificate_completion_pos'         => '108,599,998,48',
		'_certificate_completion_font_color'  => '#9e9e9e',
		'_certificate_completion_font_size'   => '20',
		'_certificate_completion_font_family' => 'Helvetica',
		'_certificate_completion_font_style'  => 'C',
		'_certificate_completion_text'        => __( '{{completion_date}} at {{course_place}}', 'sensei-certificates' ),
		'_certificate_place'                  => '',
		'_certificate_place_pos'              => '',
		'_certificate_place_font_color'       => '#9e9e9e',
		'_certificate_place_font_size'        => '20',
		'_certificate_place_font_family'      => 'Helvetica',
		'_certificate_place_font_style'       => '',
		'_certificate_place_text'             => __( '{{course_place}}', 'sensei-certificates' ),
	);

	// Certificate template font defaults.
	update_post_meta( $post_id, '_certificate_font_color', $defaults['_certificate_font_color'] );
	update_post_meta( $post_id, '_certificate_font_size', $defaults['_certificate_font_size'] );
	update_post_meta( $post_id, '_certificate_font_family', $defaults['_certificate_font_family'] );
	update_post_meta( $post_id, '_certificate_font_style', $defaults['_certificate_font_style'] );

	// Create the certificate template fields data structure.
	$fields = array();
	foreach ( array( '_certificate_heading', '_certificate_message', '_certificate_course', '_certificate_completion', '_certificate_place' ) as $i => $field_name ) {
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
		if ( $defaults[ $field_name . '_pos' ] ) {
			$position          = explode( ',', $defaults[ $field_name . '_pos' ] );
			$field['position'] = array(
				'x1'     => $position[0],
				'y1'     => $position[1],
				'width'  => $position[2],
				'height' => $position[3],
			);
		}

		if ( $defaults[ $field_name . '_text' ] ) {
			$field['text'] = $defaults[ $field_name . '_text' ] ? $defaults[ $field_name . '_text' ] : '';
		}

		// Get the field font settings (if any).
		if ( $defaults[ $field_name . '_font_family' ] ) {
			$field['font']['family'] = $defaults[ $field_name . '_font_family' ];
		}
		if ( $defaults[ $field_name . '_font_size' ] ) {
			$field['font']['size'] = $defaults[ $field_name . '_font_size' ];
		}
		if ( $defaults[ $field_name . '_font_style' ] ) {
			$field['font']['style'] = $defaults[ $field_name . '_font_style' ];
		}
		if ( $defaults[ $field_name . '_font_color' ] ) {
			$field['font']['color'] = $defaults[ $field_name . '_font_color' ];
		}

		// Cut off the leading '_' to create the field name.
		$fields[ ltrim( $field_name, '_' ) ] = $field;
	}

	update_post_meta( $post_id, '_certificate_template_fields', $fields );

	// Test attachment upload.
	$image_ids   = array();
	$image_ids[] = $image_id;
	update_post_meta( $post_id, '_image_ids', $image_ids );

	if ( $image_ids[0] ) {
		set_post_thumbnail( $post_id, $image_ids[0] );
	}

	// Set all courses to the default template.
	$query_args['posts_per_page'] = -1;
	$query_args['post_status']    = 'any';
	$query_args['post_type']      = 'course';
	$the_query                    = new WP_Query( $query_args );

	if ( $the_query->have_posts() ) {

		$count = 0;

		while ( $the_query->have_posts() ) {

			$the_query->the_post();

			update_post_meta( get_the_id(), '_course_certificate_template', $post_id );

		}
	}

	wp_reset_postdata();

	if ( 0 < $post_id ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Gets the data fields that are applied to each certificate.
 *
 * @since 2.2.2
 *
 * @return array
 */
function sensei_get_certificate_data_fields() {
	$data_fields = array(
		'heading'    => array(
			'type'                 => 'text',
			'name'                 => __( 'Heading', 'sensei-certificates' ),
			'position_label'       => __( 'Heading Position', 'sensei-certificates' ),
			'position_description' => __( 'Optional position of the Certificate Heading', 'sensei-certificates' ),
			'text_label'           => __( 'Heading Text', 'sensei-certificates' ),
			'text_description'     => __( 'Text to display in the heading.', 'sensei-certificates' ),
			'text_placeholder'     => __( 'Certificate of Completion', 'sensei-certificates' ),
		),
		'message'    => array(
			'type'                 => 'textarea',
			'name'                 => __( 'Message', 'sensei-certificates' ),
			'position_label'       => __( 'Message Position', 'sensei-certificates' ),
			'position_description' => __( 'Optional position of the Certificate Message', 'sensei-certificates' ),
			'text_label'           => __( 'Message Text', 'sensei-certificates' ),
			'text_description'     => __( 'Text to display in the message area.', 'sensei-certificates' ),
			'text_placeholder'     => __( 'This is to certify that', 'sensei-certificates' ) . "\r\n\r\n{{learner}} \r\n\r\n" . __( 'has completed the course', 'sensei-certificates' ),
		),
		'course'     => array(
			'type'                 => 'text',
			'name'                 => __( 'Course', 'sensei-certificates' ),
			'position_label'       => __( 'Course Position', 'sensei-certificates' ),
			'position_description' => __( 'Optional position of the Course Name', 'sensei-certificates' ),
			'text_label'           => __( 'Course Text', 'sensei-certificates' ),
			'text_description'     => __( 'Text to display in the course area.', 'sensei-certificates' ),
			'text_placeholder'     => __( '{{course_title}}', 'sensei-certificates' ),
		),
		'completion' => array(
			'type'                 => 'text',
			'name'                 => __( 'Completion Date', 'sensei-certificates' ),
			'position_label'       => __( 'Completion Date Position', 'sensei-certificates' ),
			'position_description' => __( 'Optional position of the Course Completion date', 'sensei-certificates' ),
			'text_label'           => __( 'Completion Date Text', 'sensei-certificates' ),
			'text_description'     => __( 'Text to display in the course completion date area.', 'sensei-certificates' ),
			'text_placeholder'     => __( '{{completion_date}}', 'sensei-certificates' ),
		),
		'place'      => array(
			'type'                 => 'text',
			'name'                 => __( 'Place', 'sensei-certificates' ),
			'position_label'       => __( 'Place Position', 'sensei-certificates' ),
			'position_description' => __( 'Optional position of the place of Certification.', 'sensei-certificates' ),
			'text_label'           => __( 'Course Place Text', 'sensei-certificates' ),
			'text_description'     => __( 'Text to display in the course place area.', 'sensei-certificates' ),
			'text_placeholder'     => __( '{{course_place}}', 'sensei-certificates' ),
		),
	);

	return apply_filters( 'sensei_certificate_data_fields', $data_fields );
}
