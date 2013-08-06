<?php
/**
 * Sensei Certificate Object Class
 *
 * All functionality pertaining to the idividual Certificate.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Extension
 * @author WooThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooCommerce voucher
 *
 * The WooCommerce PDF Product Vouchers class gets voucher data from storage.  This class
 * represents two different concepts:  a "voucher template" and a "product voucher".
 * The voucher template can be thought of as the blueprint for a voucher, it
 * contains everything needed to create a voucher (one or more images, the
 * coordinates for a number of fields, expiry days, etc).  The "product voucher"
 * is an instantiation of a voucher template, it also contains the voucher data.
 *
 * @since 1.0
 */
class WooThemes_Sensei_Certificate {

	/**
	 * @var int certificate hash
	 */
	public $hash;


	/**
	 * Construct certificate with $hash
	 *
	 * @since 1.0
	 * @param int $certificate_hash Certificate hash
	 */
	function __construct( $certificate_hash ) {
		$this->hash  = $certificate_hash;
	}


	/**
	 * Get the expiration date (if any) in the user-defined WordPress format,
	 * or the empty string.  Product voucher method.
	 *
	 * @since 1.0
	 * @return string formatted expiration date, if any, otherwise the empty string
	 */
	public function get_formatted_expiration_date() {
		if ( $this->expiration_date ) {
			if ( is_int( $this->expiration_date ) ) return date_i18n( get_option( 'date_format' ), $this->expiration_date );
			else return $this->expiration_date;
		}
		return '';
	}

	/**
	 * Get the student name for the certificate
	 *
	 * @since 1.0
	 * @return string student name, surname
	 */
	public function get_student_name() {
		if ( ! isset( $this->student_name ) ) {
			global $woothemes_sensei;
			$this->student_name = '';
		}

		return $this->student_name;
	}

	/**
	 * Get the course name
	 *
	 * @since 1.0
	 * @return string course name
	 */
	public function get_course_name() {
		if ( ! isset( $this->course_name ) ) {
			$this->course_name = '';
		}

		return $this->course_name;
	}

	/**
	 * Returns the file name for this certificate
	 *
	 * @since 1.0
	 * @return string certificate pdf file name
	 */
	public function get_certificate_filename() {
		return 'certificate-' . $this->hash . '.pdf';
	}

	/**
	 * Generate and save or stream a PDF file for this certificate
	 *
	 * @since 1.0
	 * @param string $path optional absolute path to the certificate directory, if
	 *        not supplied the PDF will be streamed as a downloadable file
	 *
	 * @return mixed nothing if a $path is supplied, otherwise a PDF download
	 */
	public function generate_pdf( $path = '' ) {

		// include the pdf library
		$root_dir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
		require_once( $root_dir . '/../lib/fpdf/fpdf.php' );

		// determine orientation: landscape or portrait
		$orientation = 'L';

		// Create the pdf
		// TODO: we're assuming a standard DPI here of where 1 point = 1/72 inch = 1 pixel
		// When writing text to a Cell, the text is vertically-aligned in the middle
		$fpdf = new FPDF( $orientation, 'pt', 100, 50 );
		$fpdf->AddPage();
		$fpdf->SetAutoPageBreak( false );

		// this is useful for displaying the text cell borders when debugging the PDF layout,
		//  though keep in mind that we translate the box position to align the text to bottom
		//  edge of what the user selected, so if you want to see the originally selected box,
		//  display that prior to the translation
		$show_border = 0;

		// voucher message text, this is multi-line, so it's handled specially
		//$this->textarea_field( $fpdf, 'message', $this->get_message(), $show_border );

		// product name
		$this->text_field( $fpdf, 'product_name', 'Course Name', $show_border );

		// recepient name
		$this->text_field( $fpdf, 'recipient_name', 'This is to certify that Gerhard', $show_border );

		if ( $path ) {
			// save the pdf as a file
			$fpdf->Output( $path . '/' . $this->get_voucher_path() . '/' . $this->get_voucher_filename(), 'F' );
		} else {
			// download file
			$fpdf->Output( 'certificate-preview-' . $this->hash . '.pdf', 'D' );
		}
	}


	/**
	 * Render a multi-line text field to the PDF
	 *
	 * @since 1.0
	 * @param FPDF $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed $value string or int value to display
	 * @param int $show_border a debugging/helper option to display a border
	 *        around the position for this field
	 */
	private function textarea_field( $fpdf, $field_name, $value, $show_border ) {
		if ( $this->get_field_position( $field_name ) && $value ) {

			$font = $this->get_field_font( $field_name );

			// get the field position
			list( $x, $y, $w, $h ) = array_values( $this->get_field_position( $field_name ) );

			// font color
			$font['color'] = $this->hex2rgb( $font['color'] );
			$fpdf->SetTextColor( $font['color'][0], $font['color'][1], $font['color'][2] );

			// set the field text styling
			$fpdf->SetFont( $font['family'], $font['style'], $font['size'] );

			$fpdf->setXY( $x, $y );

			// and write out the value
			$fpdf->Multicell( $w, $font['size'], utf8_decode( $value ), $show_border );
		}
	}


	/**
	 * Render a single-line text field to the PDF
	 *
	 * @since 1.0
	 * @param FPDF $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed $value string or int value to display
	 * @param int $show_border a debugging/helper option to display a border
	 *        around the position for this field
	 */
	private function text_field( $fpdf, $field_name, $value, $show_border ) {

		//if ( $this->get_field_position( $field_name ) && $value ) {
		if ( $value ) {

			$font = $this->get_field_font( $field_name );

			// get the field position
			list( $x, $y, $w, $h ) = array( 1,1,100,20 );//array_values( $this->get_field_position( $field_name ) );

			// font color
			$font['color'] = $this->hex2rgb( $font['color'] );
			$fpdf->SetTextColor( $font['color'][0], $font['color'][1], $font['color'][2] );

			// set the field text styling
			$fpdf->SetFont( $font['family'], $font['style'], $font['size'] );

			// show a border for debugging purposes
			if ( $show_border ) {
				$fpdf->setXY( $x, $y );
				$fpdf->Cell( $w, $h, '', 1 );
			}

			// align the text to the bottom edge of the cell by translating as needed
			$y = $font['size'] > $h ? $y - ( $font['size'] - $h ) / 2 : $y + ( $h - $font['size'] ) / 2;
			$fpdf->setXY( $x, $y );

			// and write out the value
			$fpdf->Cell( $w, $h, utf8_decode( $value ) );  // can try iconv('UTF-8', 'windows-1252', $content); if this doesn't work correctly for accents
		}
	}


	/**
	 * Taxes a hex color code and returns the RGB components in an array
	 *
	 * @since 1.0
	 * @param string $hex hex color code, ie #EEEEEE
	 *
	 * @return array rgb components, ie array( 'EE', 'EE', 'EE' )
	 */
	private function hex2rgb( $hex ) {

		if ( ! $hex ) return '';

		$hex = str_replace( "#", "", $hex );

		if ( 3 == strlen( $hex ) ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}

		return array( $r, $g, $b );
	}


	/** Helper methods ******************************************************/


	/**
	 * Returns the value for $meta_name, or empty string
	 *
	 * @since 1.0
	 * @param string $meta_name untranslated meta name
	 *
	 * @return string value for $meta_name or empty string
	 */
	private function get_item_meta_value( $meta_name ) {

		// no item set
		if ( ! $this->item ) return '';

		foreach ( $this->item as $name => $value ) {
			if ( __( $meta_name, WC_PDF_Product_Vouchers::TEXT_DOMAIN ) == $name ) {
				return $value;
			}
		}

		// not found
		return '';
	}
}
