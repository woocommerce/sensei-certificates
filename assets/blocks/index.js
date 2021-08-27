/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { registerBlockVariation } from '@wordpress/blocks';

const attributes = {
	className: 'view-certificate',
	text: __( 'View Certificate', 'sensei-certificates' ),
};

registerBlockVariation( 'core/button', {
	name: 'sensei-certificates/view-certificate-button',
	title: __( 'View Certificate', 'sensei-certificates' ),
	description: __( 'Enable a learner to view their course certificate.', 'sensei-certificates' ),
	keywords: [
		__( 'Certificates', 'sensei-lms' ),
	],
	category: 'sensei-lms',
	attributes,
	isActive: ( blockAttributes, variationAttributes ) =>
		blockAttributes.className?.match( variationAttributes.className ),
} );

const addBlockToTemplate = ( blocks ) => ( [
	...blocks,
	[ 'core/button', attributes ]
] );

// Add this block to the Course Completed Actions block.
addFilter(
	'sensei-lms.Course.completedActions',
	'sensei-certificates',
	addBlockToTemplate
);
