/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { button as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const viewCertificateBlock = {
	...metadata,
	title: __( 'View Certificate Button', 'sensei-certificates' ),
	icon,
	description: __(
		'Enable a learner to view their course certificate.',
		'sensei-certificates'
	),
	keywords: [ __( 'Certificates', 'sensei-lms' ) ],
	category: 'sensei-lms',
	edit: () => {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				<InnerBlocks
					template={ [
						[
							'core/button',
							{
								className: 'view-certificate',
								text: __(
									'View Certificate',
									'sensei-certificates'
								),
							},
						],
					] }
					templateLock="all"
				/>
			</div>
		);
	},
	save: () => {
		const blockProps = useBlockProps.save();

		return (
			<div { ...blockProps }>
				<InnerBlocks.Content />
			</div>
		);
	},
};

export default viewCertificateBlock;
