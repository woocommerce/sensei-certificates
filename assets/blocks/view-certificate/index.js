/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import './hooks';
import icon from './icon';
import metadata from './block.json';
import edit from './edit';
import save from './save';

const { name, ...settings } = metadata;

registerBlockType( name, {
	...settings,
	title: __( 'View Certificate', 'sensei-certificates' ),
	description: __(
		'Enable learners to view their course certificate.',
		'sensei-certificates'
	),
	keywords: [
		__( 'Course', 'sensei-certificates' ),
		__( 'Certificate', 'sensei-certificates' ),
	],
	example: {
		innerBlocks: [
			{
				name: 'core/button',
				attributes: {
					text: __( 'View Certificate', 'sensei-certificates' ),
				},
			},
		],
	},
	icon,
	edit,
	save,
} );
