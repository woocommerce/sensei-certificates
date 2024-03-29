/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { registerBlockVariation, registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import ViewCertificateLinkBlock from './view-certificate-link';

// Register the View Certificate Link block.
registerBlockType( ViewCertificateLinkBlock.name, ViewCertificateLinkBlock );

const attributes = {
	className: 'view-certificate',
	text: __( 'View Certificate', 'sensei-certificates' ),
};

registerBlockVariation( 'core/button', {
	name: 'sensei-certificates/view-certificate-button',
	title: __( 'View Certificate', 'sensei-certificates' ),
	description: __(
		'Enable students to view their course certificate.',
		'sensei-certificates'
	),
	keywords: [ __( 'Certificates', 'sensei-lms' ) ],
	category: 'sensei-lms',
	attributes,
	isActive: ( blockAttributes, variationAttributes ) =>
		blockAttributes.className?.match( variationAttributes.className ),
} );

const addBlockToTemplate = ( blocks ) => [
	...blocks,
	[ 'core/button', attributes ],
];

// Add this block to the Course Completed Actions block.
addFilter(
	'sensei-lms.Course.completedActions',
	'sensei-certificates',
	addBlockToTemplate
);

registerBlockVariation( 'core/buttons', {
	name: 'sensei-certificates/view-certificate-buttons',
	title: __( 'View Certificate', 'sensei-certificates' ),
	description: __(
		'A Buttons block with a View Certificate button.',
		'sensei-certificates'
	),
	category: 'sensei-lms',
	keywords: [
		__( 'Certificate', 'sensei-certificates' ),
		__( 'View Certificate', 'sensei-certificates' ),
	],
	innerBlocks: [ [ 'core/button', attributes ] ],
} );
