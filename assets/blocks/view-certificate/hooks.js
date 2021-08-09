/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

const addBlockToTemplate = ( props ) => {
	// props.push( [ 'sensei-certificates/view-certificate-button' ] );

	return props;
};

// Add this block to the Course Completed Actions block.
addFilter(
	'sensei-lms.Course.completedActions',
	'sensei-certificates',
	addBlockToTemplate
);
