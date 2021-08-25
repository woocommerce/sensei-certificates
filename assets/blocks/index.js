/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';

const addBlockToTemplate = ( blocks ) => ( [
	...blocks,
	[
		'core/button',
		{
			className: 'view-certificate',
			text: __( 'View Certificate', 'sensei-certificates' ),
		},
	]
] );

// Add this block to the Course Completed Actions block.
addFilter(
	'sensei-lms.Course.completedActions',
	'sensei-certificates',
	addBlockToTemplate
);
