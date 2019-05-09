<?php
/**
 * To avoid modifying tFPDF directly we introduce WP_tFPDF,
 * A thin wrapper around the parent class to utilize WP_Filesystem
 */
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fwrite, WordPress.WP.AlternativeFunctions.file_system_read_fread, WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_read_fclose, WordPress.VIP.FileSystemWritesDisallow
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once ABSPATH . '/wp-admin/includes/file.php';

class VIP_tFPDF extends tFPDF {
	function __construct( $orientation = 'P', $unit = 'mm', $size = 'A4' ) {
		parent::__construct( $orientation, $unit, $size );
		$this->init_wp_filesystem();
	}

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

	function Output( $name = '', $dest = '' ) {
		// Output PDF to some destination
		if ( $this->state < 3 ) {
			$this->Close();
		}

		$dest = strtoupper( $dest );
		if ( $dest === '' ) {
			if ( $name === '' ) {
				$name = 'doc.pdf';
				$dest = 'I';
			} else {
				$dest = 'F';
			}
		}

		switch ( $dest ) {
			case 'F':
				$parent_dir = dirname( $name );
				global $wp_filesystem;
				if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base' ) ) {
					$this->Error( 'Unable to access the file system' );
				}

				if ( 0 !== validate_file( $name ) ) {
					$this->Error( 'Filename is invalid' );
				}

				if ( false === stristr( $name, wp_upload_dir()['basedir'] ) ) {
					$this->Error( 'To ensure portability all files must be created in uploads/ folder' );
				}

				if ( ! $wp_filesystem->is_dir( $parent_dir ) && ! $wp_filesystem->mkdir( $parent_dir ) ) {
					$this->Error( 'Unable to access the file system' );
				}

				// Save the file using WP_Filesystem ensuring that different types of transfers are supported.
				if ( ! $wp_filesystem->put_contents( $name, $this->buffer ) ) {
					$this->Error( 'Unable to create output file: ' . basename( $name ) );
				}
				break;
			default:
				parent::Output( $name, $dest );
		}

		return '';
	}

	/**
	 * This is a thin wrapper around tFPDF's Image method.
	 * In certain cases direct access to the uploads folder is prohibited,
	 * or uploaded file might not be physically present (when using WP_Filesystem_SSH2, WP_Filesystem_ftpsockets, etc)
	 * We get around that by creating the in the system's temporary folder, performing the necessary operations on that file, and then deleting it.
	 *
	 * @param string  $file full path to the file
	 * @param [type]  $x
	 * @param [type]  $y
	 * @param integer $w
	 * @param integer $h
	 * @param string  $type
	 * @param string  $link
	 * @return void
	 */
	function Image( $file = '', $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '' ) {
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
	}

}
