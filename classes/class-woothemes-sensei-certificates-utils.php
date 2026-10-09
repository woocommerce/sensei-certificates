<?php
/**
 * Certificate helper utilities.
 *
 * @package Sensei_Certificates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Helper functions
 * Class Woothemes_Sensei_Certificates_Utils
 */
class Woothemes_Sensei_Certificates_Utils {
	/**
	 * Generate the certificate hash for a course and user.
	 *
	 * @param int $course_id Course post ID.
	 * @param int $user_id User ID.
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

	/**
	 * Reorder right-to-left (Hebrew) text into visual order for the PDF renderer.
	 *
	 * The PDF library writes characters left to right in the order they are given and has no
	 * bidirectional text support, so Hebrew text would be printed reversed. This puts each line
	 * into the order it should appear on the page, left to right.
	 *
	 * Lines without Hebrew characters are returned unchanged. Each line is handled on its own,
	 * so a learner name placed on its own line is reordered correctly, but a long Hebrew
	 * paragraph that the PDF library has to wrap is not.
	 *
	 * @since 2.6.1
	 *
	 * @param string $text The text, in logical order.
	 * @return string The text in visual order.
	 */
	public static function reorder_rtl_text( $text ) {
		if ( ! is_string( $text ) || ! preg_match( '/[\x{0590}-\x{05FF}\x{FB1D}-\x{FB4F}]/u', $text ) ) {
			return $text;
		}

		$parts = preg_split( '/(\R)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( false === $parts ) {
			return $text;
		}

		// Odd indexes hold the line breaks.
		foreach ( $parts as $index => $part ) {
			if ( 0 === $index % 2 ) {
				$parts[ $index ] = self::reorder_rtl_line( $part );
			}
		}

		return implode( '', $parts );
	}

	/**
	 * Reorder a single line of text into visual order.
	 *
	 * Simplified take on the Unicode bidirectional algorithm, covering Hebrew mixed with Latin
	 * text and numbers. The paragraph direction comes from the first strong character.
	 *
	 * @since 2.6.1
	 *
	 * @param string $line A line of text, in logical order.
	 * @return string The line in visual order.
	 */
	private static function reorder_rtl_line( $line ) {
		// Grapheme clusters keep combining marks (such as Hebrew points) attached to their letter.
		if ( ! preg_match_all( '/\X/u', $line, $matches ) ) {
			return $line;
		}

		$clusters = $matches[0];
		$types    = array();
		foreach ( $clusters as $cluster ) {
			if ( preg_match( '/^[\x{0590}-\x{05FF}\x{FB1D}-\x{FB4F}]/u', $cluster ) ) {
				$types[] = 'R';
			} elseif ( preg_match( '/^[\p{L}\p{N}]/u', $cluster ) ) {
				$types[] = 'L';
			} else {
				$types[] = 'N';
			}
		}

		$strong = array_values( array_diff( $types, array( 'N' ) ) );
		if ( ! in_array( 'R', $strong, true ) ) {
			return $line;
		}
		$base = $strong[0];

		// Neutral characters (spaces, punctuation) take the direction of the text on both sides, or the base direction.
		$count    = count( $types );
		$resolved = array();
		foreach ( $types as $i => $type ) {
			if ( 'N' !== $type ) {
				continue;
			}
			$before = null;
			for ( $j = $i - 1; $j >= 0; $j-- ) {
				if ( 'N' !== $types[ $j ] ) {
					$before = $types[ $j ];
					break;
				}
			}
			$after = null;
			for ( $j = $i + 1; $j < $count; $j++ ) {
				if ( 'N' !== $types[ $j ] ) {
					$after = $types[ $j ];
					break;
				}
			}
			$resolved[ $i ] = ( $before === $after && null !== $before ) ? $before : $base;
		}

		// Group the clusters into runs of the same direction.
		$runs = array();
		foreach ( $clusters as $i => $cluster ) {
			$type = 'N' === $types[ $i ] ? $resolved[ $i ] : $types[ $i ];
			$last = count( $runs ) - 1;
			if ( $last >= 0 && $runs[ $last ]['type'] === $type ) {
				$runs[ $last ]['clusters'][] = $cluster;
			} else {
				$runs[] = array(
					'type'     => $type,
					'clusters' => array( $cluster ),
				);
			}
		}

		// In a right-to-left paragraph the runs themselves run right to left.
		if ( 'R' === $base ) {
			$runs = array_reverse( $runs );
		}

		$output = '';
		foreach ( $runs as $run ) {
			$run_clusters = 'R' === $run['type'] ? array_reverse( $run['clusters'] ) : $run['clusters'];
			$output      .= implode( '', $run_clusters );
		}

		return $output;
	}

	/**
	 * Convert UTF-8 text to ISO-8859-1 with transliteration.
	 *
	 * Falls back to the original string if conversion fails.
	 *
	 * @param string $value Text to convert.
	 * @return string
	 */
	public static function convert_utf8_to_latin1( $value ) {
		if ( ! $value || ! is_string( $value ) ) {
			return $value;
		}

		$converted = iconv( 'UTF-8', 'ISO-8859-1//TRANSLIT', $value );

		return false === $converted ? $value : $converted;
	}
}
