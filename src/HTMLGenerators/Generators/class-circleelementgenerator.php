<?php
/**
 * Circle element generator.
 *
 * @package PDFIB\HTMLGenerators\Generators
 */

namespace PDFIB\HTMLGenerators\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PDFIB\HTMLGenerators\ElementGeneratorBase;

/**
 * Generates HTML for circle elements.
 */
class CircleElementGenerator extends ElementGeneratorBase {

	/**
	 * Generate HTML for this element.
	 *
	 * @return string
	 */
	public function generate_html() {
		$x      = $this->get_property( 'x', 0 );
		$y      = $this->get_property( 'y', 0 );
		$width  = $this->get_property( 'width', 100 );
		$height = $this->get_property( 'height', 100 );

		// Background color (fillColor or backgroundColor).
		$background_color = $this->get_property( 'fillColor', $this->get_property( 'backgroundColor', 'transparent' ) );
		$background_color = $this->normalize_color( $background_color );

		// Border.
		$border_color = $this->get_property( 'strokeColor', $this->get_property( 'borderColor', '#000000' ) );
		$border_color = $this->normalize_color( $border_color );
		$border_width = $this->get_property( 'strokeWidth', $this->get_property( 'borderWidth', 0 ) );

		$html  = '<div class="pdf-element pdf-circle" ';
		$html .= 'style="position: absolute; ';
		$html .= 'left: ' . $x . 'px; ';
		$html .= 'top: ' . $y . 'px; ';
		$html .= 'width: ' . $width . 'px; ';
		$html .= 'height: ' . $height . 'px; ';
		$html .= 'background-color: ' . $background_color . '; ';
		$html .= 'border-radius: 50%; ';

		if ( $border_width > 0 ) {
			$html .= 'border: ' . $border_width . 'px solid ' . $border_color . '; ';
		}

		$html .= 'box-sizing: border-box;">';
		$html .= '</div>';

		return $html;
	}
}
