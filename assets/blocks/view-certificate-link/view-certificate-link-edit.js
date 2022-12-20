/**
 * WordPress dependencies
 */
import { useBlockProps, Warning } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * View Certificate Link block edit component.
 *
 * @param {Object} props
 * @param {Object} props.context          Block context.
 * @param {Object} props.context.postType Post type.
 */
const ViewCertificateLinkEdit = ( { context: { postType } } ) => {
	const blockProps = useBlockProps();

	if ( ! [ 'course', 'lesson' ].includes( postType ) ) {
		return (
			<div { ...blockProps }>
				<Warning>
					{ __(
						'The View Certificate Link block can only be used inside the Course List block.',
						'sensei-certificates'
					) }
				</Warning>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
			<a href="#">{ __( 'View Certificate', 'sensei-certificates' ) }</a>
		</div>
	);
};

export default ViewCertificateLinkEdit;
