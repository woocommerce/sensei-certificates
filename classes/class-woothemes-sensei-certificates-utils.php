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

	/**
	 * Get the formatted date string to be used on a Certificate.
	 *
	 * @param  string $course_end_date The course end date to use.
	 *
	 * @return string The formatted date string.
	 */
	public static function get_certificate_formatted_date( $course_end_date ) {
		$default_date_format = get_option( 'date_format' );

		/**
		 * Filter the date format to be used for certificates. The date format
		 * syntax should be the format required by PHP's `date()` function.
		 *
		 * @param  string $default_date_format The default date format to be used.
		 * @return string The date format to use.
		 */
		$date_format = apply_filters( 'sensei_certificate_date_format', $default_date_format );

		/*
		 * For backwards compatibility, check if we're using a strftime format
		 * string for a non-English locale.
		 */
		$should_use_strftime = ( false === strpos( get_locale(), 'en' ) )
			&& self::date_format_is_for_strftime( $date_format );

		if ( $should_use_strftime ) {
			setlocale( LC_TIME, get_locale() );
			return strftime( $date_format, strtotime( $course_end_date ) );
		}

		return date_i18n( $date_format, strtotime( $course_end_date ) );
	}

	/**
	 * Checks whether a date format string is meant for `strftime()` instead of
	 * `date()`.
	 *
	 * @param  string $date_format The date format.
	 *
	 * @return boolean true if the date format should be used with `strftime()`.
	 */
	private static function date_format_is_for_strftime( $date_format ) {
		return preg_match(
			'/%(a|A|d|e|j|u|w|U|V|W|b|B|h|m|C|g|G|y|Y|H|k|I|l|M|p|P|r|R|S|T|X|z|Z|c|D|F|s|x|n|t|%)/',
			$date_format
		);
	}
}
