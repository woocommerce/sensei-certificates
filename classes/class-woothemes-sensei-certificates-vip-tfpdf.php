<?php
/**
 * To avoid modifying tFPDF directly we introduce a thin wrapper around the
 * parent class to utilize WP_Filesystem.
 *
 * @package Sensei_Certificates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once ABSPATH . '/wp-admin/includes/file.php';

/**
 * WP_Filesystem-aware wrapper around tFPDF's PDF class.
 */
class Woothemes_Sensei_Certificates_VIP_TFPDF extends tFPDF\PDF {
	/**
	 * Set up the PDF object and initialize the WP Filesystem.
	 *
	 * @param string $orientation Page orientation.
	 * @param string $unit        Measurement unit.
	 * @param string $size        Page size.
	 */
	public function __construct( $orientation = 'P', $unit = 'mm', $size = 'A4' ) {
		parent::__construct( $orientation, $unit, $size );
		$this->init_wp_filesystem();
	}

	/**
	 * Initialize the WP_Filesystem global.
	 *
	 * @return WP_Error|bool WP_Error on failure, otherwise true or the wp_filesystem() result.
	 */
	private function init_wp_filesystem() {
		global $wp_filesystem;

		if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base' ) ) {
			ob_start();
			$creds = request_filesystem_credentials( site_url() );
			ob_end_clean();

			if ( false === $creds ) {
				return new WP_Error( 'fs-init-error', "Couldn't initialize Filesystem" );
			} else {
				return wp_filesystem( $creds );
			}
		}

		return true;
	}

	/**
	 * This is a thin wrapper around tFPDF's Image method.
	 * In certain cases direct access to the uploads folder is prohibited,
	 * or uploaded file might not be physically present (when using WP_Filesystem_SSH2, WP_Filesystem_ftpsockets, etc)
	 * We get around that by creating the in the system's temporary folder, performing the necessary operations on that file, and then deleting it.
	 *
	 * @param string  $file full path to the file.
	 * @param [type]  $x X position.
	 * @param [type]  $y Y position.
	 * @param integer $w Image width.
	 * @param integer $h Image height.
	 * @param string  $type Image type/format.
	 * @param string  $link Link the image points to.
	 * @return void
	 */
	public function Image( $file = '', $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '' ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_is_writable, WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.WP.AlternativeFunctions.unlink_unlink -- Writes to the system temp dir so tFPDF can read a real file path; WP_Filesystem cannot provide one.
		global $wp_filesystem;
		if ( ! is_writable( sys_get_temp_dir() ) ) {
			$this->Error( 'Unable to access the file system' );
		}

		$filestring = $wp_filesystem->get_contents( $file );

		$file    = sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename( $file );
		$fhandle = fopen( $file, 'wb' );
		fwrite( $fhandle, $filestring );
		fclose( $fhandle );
		parent::Image( $file, $x, $y, $w, $h, $type, $link );
		unlink( $file );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_is_writable, WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.WP.AlternativeFunctions.unlink_unlink
	}

	/**
	 * Throws error.
	 *
	 * @param string $str_message The error message.
	 *
	 * @throws \RuntimeException On error.
	 */
	private function Error( $str_message ) {
		// Fatal error.
		throw new \RuntimeException( esc_html( 'FPDF Error: ' . $str_message ) );
	}
}
