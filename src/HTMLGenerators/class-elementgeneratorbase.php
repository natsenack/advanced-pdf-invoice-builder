<?php
/**
 * Base class for all element generators.
 *
 * Inspired by woo-pdf-invoice-builder architecture.
 *
 * @package PDFIB\HTMLGenerators
 */

namespace PDFIB\HTMLGenerators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract base for HTML element generators.
 */
abstract class ElementGeneratorBase {

	/**
	 * Element data.
	 *
	 * @var array
	 */
	protected array $element;

	/**
	 * Order data.
	 *
	 * @var array
	 */
	protected array $order_data;

	/**
	 * Company data.
	 *
	 * @var array
	 */
	protected array $company_data;

	/**
	 * Constructor.
	 *
	 * @param array $element      Element data.
	 * @param array $order_data   Order data.
	 * @param array $company_data Company data.
	 */
	public function __construct( array $element, array $order_data = array(), array $company_data = array() ) {
		$this->element      = $element;
		$this->order_data   = $order_data;
		$this->company_data = $company_data;
	}

	/**
	 * Generate HTML for this element.
	 * Must be implemented by subclasses.
	 */
	abstract public function generate_html();

	/**
	 * Utility: Create style attribute from array.
	 *
	 * @param array $style_array Style properties.
	 */
	protected function create_style_string( array $style_array = array() ) {
		if ( empty( $style_array ) ) {
			return '';
		}

		$styles = 'style="';
		foreach ( $style_array as $name => $value ) {
			$styles .= htmlspecialchars( $name ) . ':' . $value . ';';
		}
		$styles .= '"';

		return $styles;
	}

	/**
	 * Utility: Get element property value.
	 *
	 * @param string $property_name Property name.
	 * @param mixed  $fallback      Fallback value.
	 */
	protected function get_property( string $property_name, mixed $fallback = '' ) {
		return $this->element[ $property_name ] ?? $fallback;
	}

	/**
	 * Utility: Get element styling.
	 */
	protected function get_element_styles() {
		$styles = array();
		$this->apply_position_styles( $styles );
		$this->apply_color_and_font_styles( $styles );
		$this->apply_border_and_padding_styles( $styles );
		$this->apply_visual_styles( $styles );
		return $styles;
	}

	/**
	 * Position et taille (x/y/width/height).
	 *
	 * @param array $styles Style array (passed by reference).
	 */
	private function apply_position_styles( array &$styles ): void {
		if ( isset( $this->element['x'] ) ) {
			$styles['position'] = 'absolute';
			$styles['left']     = $this->element['x'] . 'px';
		}
		if ( isset( $this->element['y'] ) ) {
			$styles['top'] = $this->element['y'] . 'px';
		}
		if ( isset( $this->element['width'] ) ) {
			$styles['width'] = $this->element['width'] . 'px';
		}
		if ( isset( $this->element['height'] ) ) {
			$styles['height'] = $this->element['height'] . 'px';
		}
	}

	/**
	 * Couleurs et police.
	 *
	 * @param array $styles Style array (passed by reference).
	 */
	private function apply_color_and_font_styles( array &$styles ): void {
		$mappings = array(
			'backgroundColor' => 'background-color',
			'textColor'       => 'color',
			'fontFamily'      => 'font-family',
			'fontWeight'      => 'font-weight',
			'textAlign'       => 'text-align',
			'fontStyle'       => 'font-style',
			'textDecoration'  => 'text-decoration',
		);
		foreach ( $mappings as $prop => $css_prop ) {
			if ( isset( $this->element[ $prop ] ) ) {
				$styles[ $css_prop ] = $this->get_property( $prop );
			}
		}
		if ( isset( $this->element['fontSize'] ) ) {
			$styles['font-size'] = $this->get_property( 'fontSize' ) . 'px';
		}
	}

	/**
	 * Bordures et padding.
	 *
	 * @param array $styles Style array (passed by reference).
	 */
	private function apply_border_and_padding_styles( array &$styles ): void {
		if ( isset( $this->element['border'] ) && is_array( $this->element['border'] ) ) {
			$border = $this->element['border'];
			if ( ! empty( $border['width'] ) && ! empty( $border['color'] ) ) {
				$style            = $border['style'] ?? 'solid';
				$styles['border'] = $border['width'] . 'px ' . $style . ' ' . $border['color'];
			}
		}
		if ( isset( $this->element['padding'] ) && is_array( $this->element['padding'] ) ) {
			$padding           = $this->element['padding'];
			$styles['padding'] = ( $padding['top'] ?? 0 ) . 'px '
				. ( $padding['right'] ?? 0 ) . 'px '
				. ( $padding['bottom'] ?? 0 ) . 'px '
				. ( $padding['left'] ?? 0 ) . 'px';
		}
		if ( isset( $this->element['borderRadius'] ) ) {
			$styles['border-radius'] = intval( $this->element['borderRadius'] ) . 'px';
		}
	}

	/**
	 * Effets visuels: opacité, rotation, espacement, overflow, z-index.
	 *
	 * @param array $styles Style array (passed by reference).
	 */
	private function apply_visual_styles( array &$styles ): void {
		if ( isset( $this->element['opacity'] ) ) {
			$styles['opacity'] = (float) $this->element['opacity'];
		}
		if ( isset( $this->element['rotation'] ) && 0.0 !== (float) $this->element['rotation'] ) {
			$styles['transform'] = 'rotate(' . (float) $this->element['rotation'] . 'deg)';
		}
		if ( isset( $this->element['zIndex'] ) ) {
			$styles['z-index'] = intval( $this->element['zIndex'] );
		}
		if ( isset( $this->element['letterSpacing'] ) ) {
			$styles['letter-spacing'] = (float) $this->element['letterSpacing'] . 'px';
		}
		if ( isset( $this->element['lineHeight'] ) ) {
			$styles['line-height'] = (float) $this->element['lineHeight'];
		}
		if ( isset( $this->element['overflow'] ) ) {
			$styles['overflow'] = $this->element['overflow'];
		}
	}

	/**
	 * Utility: Normalize color values.
	 *
	 * @param string $color Color value.
	 */
	protected function normalize_color( string $color ) {
		if ( empty( $color ) ) {
			return '#000000';
		}
		// Hex valide ou format rgb/rgba: garder tel quel.
		if ( preg_match( '/^#[0-9A-Fa-f]{6}$/', $color ) || stripos( $color, 'rgb' ) === 0 ) {
			return $color;
		}
		return '#000000';
	}
}
