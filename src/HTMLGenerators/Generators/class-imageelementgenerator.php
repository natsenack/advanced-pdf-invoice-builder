<?php
/**
 * Image element generator.
 *
 * @package PDFIB\HTMLGenerators\Generators
 */

namespace PDFIB\HTMLGenerators\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PDFIB\HTMLGenerators\ElementGeneratorBase;

/**
 * Generates HTML for image elements.
 */
class ImageElementGenerator extends ElementGeneratorBase {

	/**
	 * Generate HTML for this element.
	 *
	 * @return string
	 */
	public function generate_html() {
		$styles     = $this->get_element_styles();
		$style_attr = $this->create_style_string( $styles );

		$image_url = $this->get_property( 'imageUrl', '' );
		$alt_text  = $this->get_property( 'altText', 'Image' );

		$html  = '<img class="pdf-element pdf-image" ' . $style_attr . ' ';
		$html .= 'src="' . esc_url( $image_url ) . '" ';
		$html .= 'alt="' . htmlspecialchars( $alt_text ) . '" />';

		return $html;
	}
}
