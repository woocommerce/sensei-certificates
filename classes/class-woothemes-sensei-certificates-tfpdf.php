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
	 * @return tFPDF\PDF
	 */
	public static function get_tfpdf_object( $orientation, $units, $size ) {
		// Include the pdf library if needed.
		require_once dirname( __DIR__ ) . '/lib/tfpdf/tFPDF/PDF.php';
		require_once dirname( __DIR__ ) . '/lib/tfpdf/tFPDF/TTFontFile.php';

		if ( defined( 'WPCOM_IS_VIP_ENV' ) && true === WPCOM_IS_VIP_ENV ) {
			require_once dirname( __FILE__ ) . '/class-vip-tfpdf.php';

			return new VIP_tFPDF( $orientation, $units, $size );
		}

		return new tFPDF\PDF( $orientation, $units, $size );
	}

	/**
	 * Get the PDF from the tFPDF object and send it to the HTTP client. Note
	 * that this will set headers and echo to stdout.
	 *
	 * @param \tFPDF\PDF $tfpdf    The tFPDF object.
	 * @param string     $filename The filename to send in the HTTP headers.
	 */
	public static function output_to_http( $tfpdf, $filename ) {
		header( 'Content-Type: application/pdf' );
		header( "Content-Disposition: inline; filename=\"$filename\"" );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );

		echo $tfpdf->output();
	}
}
