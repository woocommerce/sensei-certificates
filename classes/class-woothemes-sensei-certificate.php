<?php
/**
 * Sensei Certificate Object Class
 *
 * All functionality pertaining to the idividual Certificate.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Extension
 * @author WooThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooCommerce voucher
 *
 * The WooCommerce PDF Product Vouchers class gets voucher data from storage.  This class
 * represents two different concepts:  a "voucher template" and a "product voucher".
 * The voucher template can be thought of as the blueprint for a voucher, it
 * contains everything needed to create a voucher (one or more images, the
 * coordinates for a number of fields, expiry days, etc).  The "product voucher"
 * is an instantiation of a voucher template, it also contains the voucher data.
 *
 * @since 1.0
 */
class WooThemes_Sensei_Certificate {

	/**
	 * @var int certificate post_id
	 */
	public $id;
	/**
	 * @var string certificate name (post title)
	 */
	private $name;
	/**
	 * @var string Default certificate font color
	 */
	private $voucher_font_color;
	/**
	 * @var int Default certificate font size
	 */
	private $voucher_font_size;
	/**
	 * @var string Default certificate font style (one of 'B', 'I' or 'BI')
	 */
	private $voucher_font_style;
	/**
	 * @var string Default certificate font family
	 */
	private $voucher_font_family;
	/**
	 * @var array certificate fields (text which is written on top of the voucher image to create the final pdf)
	 */
	public $voucher_fields;
	/**
	 * @var array of certificate image ids (attachment ids)
	 */
	private $image_ids;
	/**
	 * @var int certificate main image id (attachment id)
	 */
	public $image_id;


	/**
	 * Construct voucher with $id
	 *
	 * @since 1.0
	 * @param int $id Voucher id
	 * @param int $order_id optional order id when this is a product voucher
	 * @param array $item optional item data when this is a product voucher
	 */
	function __construct( $id, $order_id = null, $item = array() ) {

		$this->id       = (int) $id;
		$this->order_id = $order_id;
		$this->item     = $item;

		// load data from the item if this is a product voucher
		if ( $this->item ) {
			$this->voucher_number = $item['voucher_number'];
			if ( isset( $item['voucher_expiration'] ) ) $this->expiration_date = strtotime( $item['voucher_expiration'] );
		}

		$this->voucher_custom_fields = get_post_custom( $this->id );

		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'image_ids'            => array(),
			'additional_image_ids' => array(),
			'voucher_font_color'   => '',
			'voucher_font_size'    => '',
			'voucher_font_style'   => '',
			'voucher_font_family'  => '',
			'voucher_fields'       => array(),
		);

		// Load the data from the custom fields
		foreach ( $load_data as $key => $default ) {
			// set value from db (unserialized if needed) or use default
			$this->$key = ( isset( $this->voucher_custom_fields[ '_' . $key ][0] ) && '' !== $this->voucher_custom_fields[ '_' . $key ][0] ) ? ( is_array( $default ) ? maybe_unserialize( $this->voucher_custom_fields[ '_' . $key ][0] ) : $this->voucher_custom_fields[ '_' . $key ][0] ) : $default;
		}

		// set the voucher main template image, if any
		if ( count( $this->image_ids ) > 0 ) {
			$this->image_id = $this->image_ids[0];
		}

		return false;
	}


	/** Getter/Setter methods ******************************************************/


	/**
	 * Returns true if this voucher is completely redeemed
	 *
	 * @since 1.0
	 * @return boolean true if the voucher is completely redeemd, false otherwise
	 */
	public function is_redeemed() {
		if ( $this->item && isset( $this->item['voucher_redeem'] ) ) {

			$voucher_redeem = maybe_unserialize( $this->item['voucher_redeem'] );

			foreach ( $voucher_redeem as $date ) {
				if ( ! $date ) return false;
			}
		}

		return true;
	}


	/**
	 * Returns the formatted product voucher number, which consists of the
	 * order number - voucher number
	 *
	 * @since 1.0
	 * @return string if a voucher number has been created, or null otherwise
	 */
	public function get_voucher_number() {
		// normally the order object should be available, but check for it in order to support the voucher preview functionality
		$voucher_number = $this->voucher_number;

		if ( $this->order_id ) {
			$voucher_number = ltrim( $this->get_order()->get_order_number(), '#' ) . '-' . $voucher_number;
		}

		return apply_filters( 'woocommerce_voucher_number', $voucher_number, $this );
	}


	/**
	 * Get the number of days this voucher is valid for
	 *
	 * @since 1.0
	 * @return int expiry days
	 */
	public function get_expiry() {
		if ( isset( $this->voucher_fields['expiration_date']['days_to_expiry'] ) )
			return $this->voucher_fields['expiration_date']['days_to_expiry'];
		return '';
	}


	/**
	 * Set the expiration date for this product voucher
	 *
	 * @since 1.0
	 * @param int $expiration_date expiration date of this product voucher,
	 *        mesured in number of seconds since the Unix Epoch
	 */
	public function set_expiration_date( $expiration_date ) {
		$this->expiration_date = $expiration_date;
	}


	/**
	 * Get the expiration date (if any) in the user-defined WordPress format,
	 * or the empty string.  Product voucher method.
	 *
	 * @since 1.0
	 * @return string formatted expiration date, if any, otherwise the empty string
	 */
	public function get_formatted_expiration_date() {
		if ( $this->expiration_date ) {
			if ( is_int( $this->expiration_date ) ) return date_i18n( get_option( 'date_format' ), $this->expiration_date );
			else return $this->expiration_date;
		}
		return '';
	}


	/**
	 * Get the recipient name if any for this product voucher
	 *
	 * @since 1.0
	 * @return string voucher recipient name or empty string
	 */
	public function get_recipient_name() {
		if ( ! isset( $this->recipient_name ) ) {
			$this->recipient_name = $this->get_item_meta_value( $this->voucher_fields['recipient_name']['display_name'] );
		}

		return $this->recipient_name;
	}


	/**
	 * Get the voucher message if any for this product voucher
	 *
	 * @since 1.0
	 * @return string voucher message or empty string
	 */
	public function get_message() {
		if ( ! isset( $this->message ) ) {
			$this->message = $this->get_item_meta_value( $this->voucher_fields['message']['display_name'] );
		}

		return $this->message;
	}


	/**
	 * Get the product name, if available
	 *
	 * @since 1.0
	 * @return string product name if this is a product voucher, or the empty string
	 */
	public function get_product_name() {
		if ( ! isset( $this->product_name ) ) {
			$this->product_name = isset( $this->item['name'] ) ? $this->item['name'] : '';
		}

		return $this->product_name;
	}


	/**
	 * Get the product sku, if available
	 *
	 * @since 1.0
	 * @return string product sku if this is a product voucher, or the empty string
	 */
	public function get_product_sku() {
		if ( ! isset( $this->product_sku ) ) {
			if ( $this->order_id && $this->item ) {
				// get product (this works for simple and variable products)
				$order = $this->get_order();
				$product = $order->get_product_from_item( $this->item );

				$this->product_sku = $product->get_sku();
			} else {
				$this->product_sku = '';
			}
		}

		return $this->product_sku;
	}


	/**
	 * Gets the main voucher image, or a placeholder
	 *
	 * @since 1.0
	 * @return string voucher primary img tag
	 */
	public function get_image( $size = 'wc-pdf-product-vouchers-voucher-thumb' ) {
		global $woocommerce;

		$image = '';

		if ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size );
		} else {
			$image = '<img src="' . woocommerce_placeholder_img_src() . '" alt="Placeholder" width="' . $woocommerce->get_image_size( 'shop_thumbnail_image_width' ) . '" height="' . $woocommerce->get_image_size( 'shop_thumbnail_image_height' ) . '" />';
		}

		return $image;
	}


	/**
	 * Gets the voucher image id: the selected image id if this is a voucher product
	 * otherwise the voucher template primary image id
	 *
	 * @since 1.0
	 * @return int voucher image id
	 */
	public function get_image_id() {
		// if this is a voucher product, return the selected image id
		if ( isset( $this->item['voucher_image_id'] ) ) return $this->item['voucher_image_id'];

		// otherwise return the template primary image id
		return $this->image_id;
	}


	/**
	 * Get the all available images for this voucher
	 *
	 * @since 1.0
	 * @return array of img tags
	 */
	public function get_image_urls( $size = 'wc-pdf-product-vouchers-voucher-thumb' ) {
		global $woocommerce;

		$images = array();

		foreach ( $this->image_ids as $image_id ) {
			$image_src = wp_get_attachment_url( $image_id );
			$thumb_src = wp_get_attachment_image_src( $image_id, $size );

			if ( $image_src ) {
				$images[ $image_id ]['image'] = $image_src;
				$images[ $image_id ]['thumb'] = $thumb_src[0];
			}
		}

		return $images;
	}


	/**
	 * Returns any user-supplied voucher field data in an associative array of
	 * data display name to value.
	 *
	 * @since 1.0
	 * @param int $cut_textarea the number of characters to limit a returned
	 *        textarea value to.  0 indicates to return the entire value regardless
	 *        of length
	 *
	 * @return array associative array of input field name to value
	 */
	public function get_user_input_data( $limit_textarea = 25 ) {
		$data = array();

		// get any meta data

		foreach ( $this->voucher_fields as $field ) {
			if ( 'user_input' == $field['type'] ) {
				foreach ( $this->item as $meta_name => $meta_value ) {
					if ( __( $field['display_name'], WC_PDF_Product_Vouchers::TEXT_DOMAIN ) == $meta_name ) {

						// limit the textarea value?
						if ( 'textarea' == $field['input_type'] && $limit_textarea && strlen( $meta_value ) > $limit_textarea ) {
							list( $value ) = explode( "\n", wordwrap( $meta_value, $limit_textarea, "\n" ) );
							$meta_value = $value . '...';
						}

						$data[ $field['display_name'] ] = $meta_value;
						break;
					}
				}
			}
		}

		return $data;
	}


	/**
	 * Return an array of user-input voucher fields
	 *
	 * @since 1.0
	 * @return array of user-input voucher fields
	 */
	public function get_user_input_voucher_fields() {
		$fields = array();
		foreach ( $this->voucher_fields as $name => $voucher_field ) {

			if ( 'user_input' == $voucher_field['type'] && ! empty( $voucher_field['position'] ) ) {
				$voucher_field['name'] = $name;
				$fields[ (int) $voucher_field['order'] ] = $voucher_field;
			}
		}
		// make sure they're ordered properly (ie for the frontend)
		ksort( $fields );

		return $fields;
	}


	/**
	 * Get the maximum length for the user input field named $name.  This is
	 * enforced on the frontend so that the voucher text doesn't overrun the
	 * field area
	 *
	 * @since 1.0
	 * @param string $name the field name
	 * @return int the max length of the field, or empty string if there is no
	 *         limit
	 */
	public function get_user_input_field_max_length( $name ) {
		if ( isset( $this->voucher_fields[ $name ]['max_length'] ) ) return $this->voucher_fields[ $name ]['max_length'];
		return '';
	}


	/**
	 * Returns true if the user input field named $name is required, false otherwise
	 *
	 * @since 1.1
	 * @param string $name the field name
	 * @return boolean true if $name is required, false otherwise
	 */
	public function user_input_field_is_required( $name ) {
		if ( isset( $this->voucher_fields[ $name ]['is_required'] ) ) return 'yes' == $this->voucher_fields[ $name ]['is_required'];
		return '';
	}


	/**
	 * Returns true if this voucher has any user input fields that are required
	 *
	 * @since 1.1
	 * @return boolean true if there is a required field
	 */
	public function has_required_input_fields() {
		foreach ( $this->voucher_fields as $field ) {
			if ( isset( $field['is_required'] ) && 'yes' == $field['is_required'] ) return true;
		}

		return false;
	}


	/**
	 * Returns the font definition for the field $field_name, using the voucher
	 * font defaults if not provided
	 *
	 * @since 1.0
	 * @param string $field_name name of the field
	 *
	 * @return array with optional members 'family', 'size', 'style', 'color'
	 */
	public function get_field_font( $field_name ) {
		$default_font = array( 'family' => $this->voucher_font_family, 'size' => $this->voucher_font_size, 'color' => $this->voucher_font_color );

		// only use the default font style if there is no specific font family set
		if ( ! isset( $this->voucher_fields[ $field_name ]['font']['family'] ) || ! $this->voucher_fields[ $field_name ]['font']['family'] ) {
			$default_font['style'] = $this->voucher_font_style;
		}

		// get rid of any empty fields so the defaults can take precedence
		foreach ( $this->voucher_fields[ $field_name ]['font'] as $key => $value ) {
			if ( ! $value ) unset( $this->voucher_fields[ $field_name ]['font'][ $key ] );
		}

		$merged = array_merge( $default_font, $this->voucher_fields[ $field_name ]['font'] );

		// handle style specially
		if ( ! isset( $merged['style'] ) ) $merged['style'] = '';

		return $merged;
	}


	/**
	 * Returns the field position for the field $field_name
	 *
	 * @since 1.0
	 * @return array associative array with position members 'x1', 'y1', 'width'
	 *         and 'height'
	 */
	public function get_field_position( $field_name ) {
		return isset( $this->voucher_fields[ $field_name ]['position'] ) ? $this->voucher_fields[ $field_name ]['position'] : array();
	}


	/**
	 * Returns the file name for this product voucher
	 *
	 * @since 1.0
	 * @return string voucher pdf file name
	 */
	public function get_voucher_filename() {
		return 'voucher-' . $this->get_voucher_number() . '.pdf';
	}


	/**
	 * Returns the relative voucher pdf file path for this product voucher
	 *
	 * @since 1.0
	 * @return string voucher pdf file path
	 */
	public function get_voucher_path() {
		// hash the pdfs by the least 3 sig digits of the order id, this will give us no more than 1000 files per directory until we hit 1 million pdfs generated
		return str_pad( substr( $this->voucher_number, -3 ), 3, 0, STR_PAD_LEFT );
	}


	/**
	 * Get the order that this voucher is attached to, when it is a product voucher.
	 *
	 * @since 1.0
	 * @return WC_Order the order, or null
	 */
	public function get_order() {
		if ( $this->order ) return $this->order;

		if ( $this->order_id ) {
			$this->order = new WC_Order( $this->order_id );
			return $this->order;
		}

		return null;
	}


	/** PDF Generation methods ******************************************************/


	/**
	 * Generate and save or stream a PDF file for this product voucher
	 *
	 * @since 1.0
	 * @param string $path optional absolute path to the voucher directory, if
	 *        not supplied the PDF will be streamed as a downloadable file (used
	 *        for admin previewing of the PDF)
	 *
	 * @return mixed nothing if a $path is supplied, otherwise a PDF download
	 */
	public function generate_pdf( $path = '' ) {

		// include the pdf library
		$root_dir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
		require_once( $root_dir . '/../lib/fpdf/fpdf.php' );

		$image = wp_get_attachment_metadata( $this->get_image_id() );

		// determine orientation: landscape or portrait
		if ( $image['width'] > $image['height'] ) {
			$orientation = 'L';
		} else {
			$orientation = "P";
		}

		// Create the pdf
		// TODO: we're assuming a standard DPI here of where 1 point = 1/72 inch = 1 pixel
		// When writing text to a Cell, the text is vertically-aligned in the middle
		$fpdf = new FPDF( $orientation, 'pt', array( $image['width'], $image['height'] ) );
		$fpdf->AddPage();
		$fpdf->SetAutoPageBreak( false );

		// set the voucher image
		$upload_dir = wp_upload_dir();
		$fpdf->Image( $upload_dir['basedir'] . '/' . $image['file'], 0, 0, $image['width'], $image['height'] );

		// this is useful for displaying the text cell borders when debugging the PDF layout,
		//  though keep in mind that we translate the box position to align the text to bottom
		//  edge of what the user selected, so if you want to see the originally selected box,
		//  display that prior to the translation
		$show_border = 0;

		// voucher message text, this is multi-line, so it's handled specially
		$this->textarea_field( $fpdf, 'message', $this->get_message(), $show_border );

		// product name
		$this->text_field( $fpdf, 'product_name', $this->get_product_name(), $show_border );

		// product sku
		$this->text_field( $fpdf, 'product_sku', $this->get_product_sku(), $show_border );

		// recepient name
		$this->text_field( $fpdf, 'recipient_name', $this->get_recipient_name(), $show_border );

		// expiry date
		$this->text_field( $fpdf, 'expiration_date', $this->get_formatted_expiration_date(), $show_border );

		// voucher number
		$this->text_field( $fpdf, 'voucher_number', $this->get_voucher_number(), $show_border );

		// has additional pages?
		foreach ( $this->additional_image_ids as $additional_image_id ) {
			$fpdf->AddPage();
			$additional_image = wp_get_attachment_metadata( $additional_image_id );
			$fpdf->Image( $upload_dir['basedir'] . '/' . $additional_image['file'],
			              0,
			              0,
			              $additional_image['width']  < $image['width']  ? $additional_image['width']  : $image['width'],
			              $additional_image['height'] < $image['height'] ? $additional_image['height'] : $image['height'] );
		}

		if ( $path ) {
			// save the pdf as a file
			$fpdf->Output( $path . '/' . $this->get_voucher_path() . '/' . $this->get_voucher_filename(), 'F' );
		} else {
			// download file
			$fpdf->Output( 'voucher-preview-' . $this->id . '.pdf', 'D' );
		}
	}


	/**
	 * Render a multi-line text field to the PDF
	 *
	 * @since 1.0
	 * @param FPDF $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed $value string or int value to display
	 * @param int $show_border a debugging/helper option to display a border
	 *        around the position for this field
	 */
	private function textarea_field( $fpdf, $field_name, $value, $show_border ) {
		if ( $this->get_field_position( $field_name ) && $value ) {

			$font = $this->get_field_font( $field_name );

			// get the field position
			list( $x, $y, $w, $h ) = array_values( $this->get_field_position( $field_name ) );

			// font color
			$font['color'] = $this->hex2rgb( $font['color'] );
			$fpdf->SetTextColor( $font['color'][0], $font['color'][1], $font['color'][2] );

			// set the field text styling
			$fpdf->SetFont( $font['family'], $font['style'], $font['size'] );

			$fpdf->setXY( $x, $y );

			// and write out the value
			$fpdf->Multicell( $w, $font['size'], utf8_decode( $value ), $show_border );
		}
	}


	/**
	 * Render a single-line text field to the PDF
	 *
	 * @since 1.0
	 * @param FPDF $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed $value string or int value to display
	 * @param int $show_border a debugging/helper option to display a border
	 *        around the position for this field
	 */
	private function text_field( $fpdf, $field_name, $value, $show_border ) {

		if ( $this->get_field_position( $field_name ) && $value ) {

			$font = $this->get_field_font( $field_name );

			// get the field position
			list( $x, $y, $w, $h ) = array_values( $this->get_field_position( $field_name ) );

			// font color
			$font['color'] = $this->hex2rgb( $font['color'] );
			$fpdf->SetTextColor( $font['color'][0], $font['color'][1], $font['color'][2] );

			// set the field text styling
			$fpdf->SetFont( $font['family'], $font['style'], $font['size'] );

			// show a border for debugging purposes
			if ( $show_border ) {
				$fpdf->setXY( $x, $y );
				$fpdf->Cell( $w, $h, '', 1 );
			}

			// align the text to the bottom edge of the cell by translating as needed
			$y = $font['size'] > $h ? $y - ( $font['size'] - $h ) / 2 : $y + ( $h - $font['size'] ) / 2;
			$fpdf->setXY( $x, $y );

			// and write out the value
			$fpdf->Cell( $w, $h, utf8_decode( $value ) );  // can try iconv('UTF-8', 'windows-1252', $content); if this doesn't work correctly for accents
		}
	}


	/**
	 * Taxes a hex color code and returns the RGB components in an array
	 *
	 * @since 1.0
	 * @param string $hex hex color code, ie #EEEEEE
	 *
	 * @return array rgb components, ie array( 'EE', 'EE', 'EE' )
	 */
	private function hex2rgb( $hex ) {

		if ( ! $hex ) return '';

		$hex = str_replace( "#", "", $hex );

		if ( 3 == strlen( $hex ) ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}

		return array( $r, $g, $b );
	}


	/** Helper methods ******************************************************/


	/**
	 * Returns the value for $meta_name, or empty string
	 *
	 * @since 1.0
	 * @param string $meta_name untranslated meta name
	 *
	 * @return string value for $meta_name or empty string
	 */
	private function get_item_meta_value( $meta_name ) {

		// no item set
		if ( ! $this->item ) return '';

		foreach ( $this->item as $name => $value ) {
			if ( __( $meta_name, WC_PDF_Product_Vouchers::TEXT_DOMAIN ) == $name ) {
				return $value;
			}
		}

		// not found
		return '';
	}
}
