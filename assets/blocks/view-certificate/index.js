/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockVariation } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './hooks';

export const registerViewCertificateBlock = () =>
	registerBlockVariation( 'core/button', {
		name: 'sensei-certificates/view-certificate-button',
		title: __( 'View Certificate', 'sensei-certificates' ),
		description: __(
			'Enable learners to view their course certificate.',
			'sensei-certificates'
		),
		category: 'sensei-lms',
		keywords: [
			__( 'Course', 'sensei-certificates' ),
			__( 'Certificate', 'sensei-certificates' ),
		],
		attributes: {
			className: 'view-certificate',
			text: __( 'View Certificate', 'sensei-certificates' ),
		},
	} );
