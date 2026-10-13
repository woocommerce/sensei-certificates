<?php
/**
 * Bootstrap the WordPress integration tests with Sensei LMS and Certificates.
 *
 * @package Sensei_Certificates
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$sensei_certificates_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';
$sensei_certificates_lms_dir   = getenv( 'SENSEI_PLUGIN_DIR' ) ?: dirname( __DIR__, 3 ) . '/sensei';

if ( ! file_exists( $sensei_certificates_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "Start the WordPress test environment first; see tests/README.md.\n" );
	exit( 1 );
}

if ( ! file_exists( $sensei_certificates_lms_dir . '/vendor/autoload.php' ) ) {
	fwrite( STDERR, "Set SENSEI_PLUGIN_DIR to a Sensei LMS checkout with Composer dependencies installed.\n" );
	exit( 1 );
}

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );
require_once $sensei_certificates_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $sensei_certificates_lms_dir ) {
		require_once $sensei_certificates_lms_dir . '/sensei-lms.php';
		update_option( 'sensei-version', SENSEI_LMS_VERSION );
		add_action( 'init', array( Sensei(), 'activate' ) );
		require_once dirname( __DIR__, 2 ) . '/woothemes-sensei-certificates.php';
	}
);

require_once $sensei_certificates_tests_dir . '/includes/bootstrap.php';
