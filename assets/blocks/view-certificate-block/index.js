/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockVariation } from '@wordpress/blocks';

const buttonVariationAttributes = {
	url: '/course/sensei-view-certificate',
	text: __( 'View Certificate', 'sensei-certificates' ),
	linkTarget: '_blank'
};

registerBlockVariation( 'core/button', {
	name: 'sensei-certificates/view-certificate-button',
	title: __( 'View Certificate', 'sensei-certificates' ),
	description: __( 'Allow a user to view the course certificate.', 'sensei-certificates' ),
	attributes: buttonVariationAttributes
} );

registerBlockVariation( 'core/buttons', {
	name: 'sensei-certificates//view-certificate-buttons',
	category: 'sensei-lms',
	description: __( 'Allow a user to view the course certificate. The block is not displayed if the user does not have a certificate.', 'sensei-certificates' ),
	title: __( 'View Certificate Button', 'sensei-certificates' ),
	example: undefined,
	innerBlocks: [
		{
			name: 'core/button',
			attributes: buttonVariationAttributes
		}
	],
} );
