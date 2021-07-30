/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Filter the block template.
 *
 * @param {Array} template Block template.
 */
const TEMPLATE = [
	[
		'core/button',
		{
			text: __( 'View Certificate', 'sensei-certificates' ),
		},
	],
];

/**
 * Edit View Certificate block.
 */
const ViewCertificateEdit = ( { className } ) =>
	<div className={ className }>
		<InnerBlocks template={ TEMPLATE } templateLock="all" />
	</div>;

export default ViewCertificateEdit;
