<?php
/**
 * Sensei PDF Certificate Object Class
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
 * Sensei PDF Certificate
 *
 * The Sensei PDF PCertificate class acts as a blueprint for all certificates.
 *
 * @since 1.0
 */
class WooThemes_Sensei_PDF_Certificate {

	/**
	 * @var int preview post id
	 */
	public $preview_id;

	/**
	 * @var int certificate hash
	 */
	public $hash;

	/**
	 * @var mixed certificate pdf data
	 */
	public $certificate_pdf_data;


	/**
	 * Construct certificate with $hash
	 *
	 * @since 1.0
	 * @param int $certificate_hash Certificate hash
	 */
	public function __construct( $certificate_hash ) {
		$this->hash  = $certificate_hash;
		$this->certificate_pdf_data = apply_filters( 'woothemes_sensei_certificates_pdf_data', array(
			'font_color'   => '#000000',
			'font_size'    => '50',
			'font_style'   => 'B',
			'font_family'  => 'Helvetica'
		) );
		$this->certificate_pdf_data_userdata = apply_filters( 'woothemes_sensei_certificates_pdf_data_userdata', array(
			'font_color'   => '#666666',
			'font_size'    => '50',
			'font_style'   => 'I',
			'font_family'  => 'Times'
		) );
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
		require_once( $root_dir . '../lib/fpdf/fpdf.php' );

		do_action( 'sensei_certificates_set_background_image', $this );

		if (isset( $this->bg_image_src ) && '' != $this->bg_image_src ) {
			$image = $this->bg_image_src;
		} else {
			$image = apply_filters( 'woothemes_sensei_certificates_background', $GLOBALS['woothemes_sensei_certificates']->plugin_path . 'assets/images/certificate_template.png' );
		} // End If Statement
		$image_attr = getimagesize( $image );
		if ( $image_attr[0] > $image[1] ) {
			$orientation = 'L';
		} else {
			$orientation = 'P';
		}

		// Create the pdf
		// TODO: we're assuming a standard DPI here of where 1 point = 1/72 inch = 1 pixel
		// When writing text to a Cell, the text is vertically-aligned in the middle
		$fpdf = new FPDF( $orientation, 'pt', array( $image_attr[0], $image_attr[1] ) );

		$fpdf->AddPage();
		$fpdf->SetAutoPageBreak( false );

		// Set the border image as the background
		$fpdf->Image( $image, 0, 0, $image_attr[0], $image_attr[1] );

		do_action( 'sensei_certificates_before_pdf_output', $this, $fpdf );

		if ( $path ) {
			// save the pdf as a file
			$fpdf->Output( $path . '/' . $this->get_voucher_path() . '/' . $this->get_voucher_filename(), 'F' );
		} else {
			// download file
			$fpdf->Output( 'certificate-preview-' . $this->hash . '.pdf', 'I' );
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
	public function textarea_field( $fpdf, $value, $show_border, $position, $font = array() ) {
		if ( $value ) {

			if ( empty( $font ) ) {
				$font = array(
					'font_color' => $this->certificate_pdf_data['font_color'],
					'font_family' => $this->certificate_pdf_data['font_family'],
					'font_style' => $this->certificate_pdf_data['font_style'],
					'font_size' => $this->certificate_pdf_data['font_size']
				);
			}

			// Test each font element
			if ( empty( $font['font_color'] ) ) { $font['font_color'] = $this->certificate_pdf_data['font_color']; }
			if ( empty( $font['font_family'] ) ) { $font['font_family'] = $this->certificate_pdf_data['font_family']; }
			if ( empty( $font['font_style'] ) ) { $font['font_style'] = $this->certificate_pdf_data['font_style']; }
			if ( empty( $font['font_size'] ) ) { $font['font_size'] = $this->certificate_pdf_data['font_size']; }

			// get the field position
			list( $x, $y, $w, $h ) = $position;

			// font color
			$font_color = $this->hex2rgb( $font['font_color'] );
			$fpdf->SetTextColor( $font_color[0], $font_color[1], $font_color[2] );

			// Check for Border and Center align
			$border = 0;
			$center = 'J';
			if ( isset( $font['font_style'] ) && !empty( $font['font_style'] ) && false !== strpos( $font['font_style'], 'C' ) ) {
				$center = 'C';
				$font['font_style'] = str_replace( 'C', '', $font['font_style']);
			} // End If Statement
			if ( isset( $font['font_style'] ) && !empty( $font['font_style'] ) && false !== strpos( $font['font_style'], 'O' ) ) {
				$border = 1;
				$font['font_style'] = str_replace( 'O', '', $font['font_style']);
			} // End If Statement

			// set the field text styling
			$fpdf->SetFont( $font['font_family'], $font['font_style'], $font['font_size'] );

			$fpdf->setXY( $x, $y );

			if ( 0 < $border ) {
				$show_border = 1;
				$fpdf->SetDrawColor( $font_color[0], $font_color[1], $font_color[2] );
			}

			// and write out the value
			$fpdf->Multicell( $w, $font['font_size'], utf8_decode( $value ), $show_border, $center );
		}
	}

	/**
	 * Render an image field to the PDF
	 *
	 * @since 1.0
	 * @param FPDF $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed $value string or int value to display
	 * @param int $show_border a debugging/helper option to display a border
	 *        around the position for this field
	 */
	public function image_field( $fpdf, $value, $show_border, $position ) {
		if ( $value ) {
			// get the field position
			list( $x, $y, $w, $h ) = $position;

			$fpdf->setXY( $x, $y );

			// and write out the value
			$fpdf->Image( esc_url( utf8_decode( $value ) ), $x, $y, $w, $h );
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
	public function text_field( $fpdf, $value, $show_border, $position, $font = array() ) {
		if ( $value ) {

			if ( empty( $font ) ) {
				$font = array(
					'font_color' => $this->certificate_pdf_data['font_color'],
					'font_family' => $this->certificate_pdf_data['font_family'],
					'font_style' => $this->certificate_pdf_data['font_style'],
					'font_size' => $this->certificate_pdf_data['font_size']
				);
			}

			// Test each font element
			if ( empty( $font['font_color'] ) ) { $font['font_color'] = $this->certificate_pdf_data['font_color']; }
			if ( empty( $font['font_family'] ) ) { $font['font_family'] = $this->certificate_pdf_data['font_family']; }
			if ( empty( $font['font_style'] ) ) { $font['font_style'] = $this->certificate_pdf_data['font_style']; }
			if ( empty( $font['font_size'] ) ) { $font['font_size'] = $this->certificate_pdf_data['font_size']; }

			// get the field position
			list( $x, $y, $w, $h ) = $position;

			// font color
			$font_color = $this->hex2rgb( $font['font_color'] );
			$fpdf->SetTextColor( $font_color[0], $font_color[1], $font_color[2] );

			// Check for Border and Center align
			$border = 0;
			$center = 'J';
			if ( isset( $font['font_style'] ) && !empty( $font['font_style'] ) && false !== strpos( $font['font_style'], 'C' ) ) {
				$center = 'C';
				$font['font_style'] = str_replace( 'C', '', $font['font_style']);
			} // End If Statement
			if ( isset( $font['font_style'] ) && !empty( $font['font_style'] ) && false !== strpos( $font['font_style'], 'O' ) ) {
				$border = 1;
				$font['font_style'] = str_replace( 'O', '', $font['font_style']);
			} // End If Statement

			// set the field text styling
			$fpdf->SetFont( $font['font_family'], $font['font_style'], $font['font_size'] );

			// show a border for debugging purposes
			if ( $show_border ) {
				$fpdf->setXY( $x, $y );
				$fpdf->Cell( $w, $h, '', 1 );
			}

			if ( 0 < $border ) {
				$show_border = 1;
				$fpdf->SetDrawColor( $font_color[0], $font_color[1], $font_color[2] );
			}

			// align the text to the bottom edge of the cell by translating as needed
			$y =$font['font_size'] > $h ? $y - ( $font['font_size'] - $h ) / 2 : $y + ( $h - $font['font_size'] ) / 2;
			$fpdf->setXY( $x, $y );

			// and write out the value
			$fpdf->Cell( $w, $h, utf8_decode( $value ), $show_border, $position, $center  );  // can try iconv('UTF-8', 'windows-1252', $content); if this doesn't work correctly for accents
		}
	}

	/**
	 * Render a single-line text field to the PDF, with custom styling for the user data
	 *
	 * @since 1.0
	 * @param FPDF $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed $value string or int value to display
	 * @param int $show_border a debugging/helper option to display a border
	 *        around the position for this field
	 */
	public function text_field_userdata( $fpdf, $value, $show_border, $position, $font = array() ) {
		if ( $value ) {

			if ( empty( $font ) ) {
				$font = array(
					'font_color' => $this->certificate_pdf_data_userdata['font_color'],
					'font_family' => $this->certificate_pdf_data_userdata['font_family'],
					'font_style' => $this->certificate_pdf_data_userdata['font_style'],
					'font_size' => $this->certificate_pdf_data_userdata['font_size']
				);
			}
			// get the field position
			list( $x, $y, $w, $h ) = $position;

			// font color
			$font_color = $this->hex2rgb( $font['font_color'] );
			$fpdf->SetTextColor( $font_color[0], $font_color[1], $font_color[2] );

			// set the field text styling
			$fpdf->SetFont( $font['font_family'], $font['font_style'], $font['font_size'] );

			// show a border for debugging purposes
			if ( $show_border ) {
				$fpdf->setXY( $x, $y );
				$fpdf->Cell( $w, $h, '', 1 );
			}

			// align the text to the bottom edge of the cell by translating as needed
			$y =$font['font_size'] > $h ? $y - ( $font['font_size'] - $h ) / 2 : $y + ( $h - $font['font_size'] ) / 2;
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

}
