<?php
/**
 * Line element generator.
 *
 * @package PDFIB\HTMLGenerators\Generators
 */

namespace PDFIB\HTMLGenerators\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PDFIB\HTMLGenerators\ElementGeneratorBase;

/**
 * Generates HTML for line elements.
 */
class LineElementGenerator extends ElementGeneratorBase {

	/**
	 * Generate HTML for this element.
	 *
	 * @return string
	 */
	public function generate_html() {
		$x          = $this->get_property( 'x', 0 );
		$y          = $this->get_property( 'y', 0 );
		$width      = $this->get_property( 'width', 100 );
		$height     = $this->get_property( 'height', 1 );
		$color      = $this->normalize_color( $this->get_property( 'color', '#000000' ) );
		$line_width = $this->get_property( 'lineWidth', 1 );

		$html  = '<div class="pdf-element pdf-line" ';
		$html .= 'style="position:absolute; left:' . $x . 'px; top:' . $y . 'px; ';
		$html .= 'width:' . $width . 'px; height:' . $height . 'px; ';
		$html .= 'background-color:' . $color . ';">';
		$html .= '</div>';

		return $html;
	}
}
