<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Helper functions
 * Class Woothemes_Sensei_Certificates_Utils
 */
class Woothemes_Sensei_Certificates_Utils {
    /**
     * @param int $course_id
     * @param int $user_id
     * @return string
     */
    public static function get_certificate_hash( $course_id, $user_id ) {
        return esc_html( substr( md5( $course_id . $user_id ), -8 ) );
    }
}