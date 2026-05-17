<?php
/**
 * Text element generator.
 *
 * @package PDFIB\HTMLGenerators\Generators
 */

namespace PDFIB\HTMLGenerators\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PDFIB\HTMLGenerators\ElementGeneratorBase;

/**
 * Generates HTML for text elements.
 */
class TextElementGenerator extends ElementGeneratorBase {


	/**
	 * Generate HTML for this element.
	 *
	 * @return string
	 */
	public function generate_html() {
		$content    = $this->get_property( 'content', '' );
		$styles     = $this->get_element_styles();
		$style_attr = $this->create_style_string( $styles );

		// Résoudre les variables {{var_name}} depuis le flat order_data.
		foreach ( $this->order_data as $key => $value ) {
			if ( is_string( $value ) || is_numeric( $value ) ) {
				$content = str_replace( '{{' . $key . '}}', (string) $value, $content );
			}
		}

		$html  = '<div class="pdf-element pdf-text" ' . $style_attr . '>';
		$html .= '<p style="margin:0; padding:0;">' . nl2br( htmlspecialchars( $content ) ) . '</p>';
		$html .= '</div>';

		return $html;
	}
}
