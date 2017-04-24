<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * The data store
 * Class Woothemes_Sensei_Certificate_Data_Store
 */
class Woothemes_Sensei_Certificate_Data_Store {
    /**
     * @param int $user_id
     * @param int $course_id
     * @return int|WP_Error
     */
    function insert( $user_id, $course_id ) {
        $certificate_hash = Woothemes_Sensei_Certificates_Utils::get_certificate_hash( $course_id, $user_id );
        // check if user certificate already exists
        $exists = get_page_by_title( $certificate_hash, OBJECT, 'certificate' );
        if ( ! empty( $exists ) ) {
            return new WP_Error( 'sensei_certificates_duplicate' );
        }

        // Insert custom post type
        $cert_args = array(
            'post_author' => intval( $user_id ),
            'post_title' => $certificate_hash,
            'post_name' => $certificate_hash,
            'post_type' => 'certificate',
            'post_status'   => 'publish'
        );
        $post_id = wp_insert_post( $cert_args, $wp_error = false );

        if ( ! is_wp_error( $post_id ) ) {
            add_post_meta( $post_id, 'course_id', absint( $course_id ) );
            add_post_meta( $post_id, 'learner_id', absint( $user_id ) );
            add_post_meta( $post_id, 'certificate_hash', $certificate_hash );
        } // End If Statement
        return $post_id;
    }
}