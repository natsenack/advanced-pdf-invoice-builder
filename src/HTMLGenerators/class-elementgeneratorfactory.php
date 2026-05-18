<?php
/**
 * Factory for creating appropriate element generators.
 *
 * Inspired by woo-pdf-invoice-builder FieldFactory.
 *
 * @package PDFIB\HTMLGenerators
 */

namespace PDFIB\HTMLGenerators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Creates element generator instances by type.
 */
class ElementGeneratorFactory {

	/**
	 * Create the appropriate generator for an element type.
	 *
	 * @param array $element      Element data.
	 * @param array $order_data   Order data.
	 * @param array $company_data Company data.
	 */
	public static function create_generator( array $element, $order_data = array(), $company_data = array() ) {
		$type = $element['type'] ?? 'text';

		switch ( $type ) {
			case 'text':
				return new Generators\TextElementGenerator( $element, $order_data, $company_data );

			case 'mentions':
				return new Generators\MentionsElementGenerator( $element, $order_data, $company_data );

			case 'company_info':
				return new Generators\CompanyInfoGenerator( $element, $order_data, $company_data );

			case 'rectangle':
				return new Generators\RectangleElementGenerator( $element, $order_data, $company_data );

			case 'circle':
				return new Generators\CircleElementGenerator( $element, $order_data, $company_data );

			case 'image':
				return new Generators\ImageElementGenerator( $element, $order_data, $company_data );

			case 'product_table':
				return new Generators\ProductTableGenerator( $element, $order_data, $company_data );

			case 'customer_info':
				return new Generators\CustomerInfoGenerator( $element, $order_data, $company_data );

			case 'line':
				return new Generators\LineElementGenerator( $element, $order_data, $company_data );

			default:
				// Return a generic text generator for unknown types.
				return new Generators\TextElementGenerator( $element, $order_data, $company_data );
		}
	}

	/**
	 * Generate HTML for multiple elements.
	 *
	 * @param array $elements     Array of elements.
	 * @param array $order_data   Order data.
	 * @param array $company_data Company data.
	 */
	public static function generate_multiple( array $elements, $order_data = array(), $company_data = array() ) {
		$html = '';

		if ( ! is_array( $elements ) ) {
			return $html;
		}

		foreach ( $elements as $element ) {
			try {
				$generator = self::create_generator( $element, $order_data, $company_data );
				$html     .= $generator->generate_html();
			} catch ( \Exception $e ) {
				continue;
			}
		}

		return $html;
	}
}
