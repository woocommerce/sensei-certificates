<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class to manage the tFPDF library.
 */
class Woothemes_Sensei_Certificates_TFPDF {
	/**
	 * Create and return the tFPDF object.
	 *
	 * @param string $orientation
	 * @param string $units
	 * @param string $size
	 *
	 * @return tFPDF
	 */
	public static function get_tfpdf_object( $orientation, $units, $size ) {
		// Include the pdf library if needed.
		require_once( dirname( __FILE__ ) . '/../lib/tfpdf/tfpdf.php' );

		return new tFPDF( $orientation, $units, $size );
	}

	/**
	 * Get the PDF from the tFPDF object and send it to the HTTP client. Note
	 * that this will set headers and echo to stdout.
	 *
	 * @param string $tfpdf    The tFPDF object.
	 * @param string $filename The filename to send in the HTTP headers.
	 */
	public static function output_to_http( $tfpdf, $filename ) {
		$tfpdf->Output( $filename, 'I' );
	}
}
