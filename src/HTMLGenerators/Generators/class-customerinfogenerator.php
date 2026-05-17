<?php
/**
 * Customer info element generator.
 *
 * @package PDFIB\HTMLGenerators\Generators
 */

namespace PDFIB\HTMLGenerators\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PDFIB\HTMLGenerators\ElementGeneratorBase;

/**
 * Generates HTML for customer info elements.
 */
class CustomerInfoGenerator extends ElementGeneratorBase {


	private const CSS_PX_COLOR  = 'px;color:';
	private const CSS_MB2_CLOSE = ';margin-bottom:2px;">';

	/**
	 * Generate HTML for this element.
	 *
	 * @return string
	 */
	public function generate_html() {
		$styles = $this->get_element_styles();
		unset( $styles['background-color'] );
		$style_attr = $this->create_style_string(
			array_merge(
				$styles,
				array(
					'overflow' => 'hidden',
					'position' => $styles['position'] ?? 'absolute',
				)
			)
		);

		$display_config = array(
			'name'    => $this->get_property( 'showName', true ) !== false,
			'email'   => $this->get_property( 'showEmail', true ) !== false,
			'phone'   => $this->get_property( 'showPhone', true ) !== false,
			'address' => $this->get_property( 'showAddress', true ) !== false,
			'company' => $this->get_property( 'showCompany', false ) !== false,
		);
		$customer_data  = $this->buildCustomerDataFromOrder();
		$bg_color       = $this->normalize_color( $this->get_property( 'backgroundColor', '#ffffff' ) );
		$border_color   = $this->normalize_color( $this->get_property( 'borderColor', '#e5e7eb' ) );
		$border_width   = (float) $this->get_property( 'borderWidth', 1 );
		$text_color     = $this->normalize_color( $this->get_property( 'textColor', '#374151' ) );
		$header_color   = $this->normalize_color( $this->get_property( 'headerTextColor', $text_color ) );
		$header_size    = (int) $this->get_property( 'headerFontSize', 13 );
		$body_size      = (int) $this->get_property( 'bodyFontSize', 12 );
		$show_bg        = $this->get_property( 'showBackground', true ) !== false;
		$show_borders   = $this->get_property( 'showBorders', false ) !== false;

		$html = '<div class="pdf-element pdf-customer-info" ' . $style_attr . '>';
		if ( $show_bg ) {
			$html .= '<div style="position:absolute;top:0;left:0;width:100%;height:100%;background-color:' . $bg_color . ';z-index:-1;"></div>';
		}
		$html .= '<div style="padding:10px;height:100%;overflow:hidden;">';
		$html .= $this->buildCustomerContentHTML( $customer_data, $display_config, $header_size, $header_color, $body_size, $text_color );
		$html .= '</div>';
		if ( $show_borders ) {
			$html .= '<div style="position:absolute;top:0;left:0;width:100%;height:100%;border:' . $border_width . 'px solid ' . $border_color . ';box-sizing:border-box;pointer-events:none;"></div>';
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * Build customer data from order.
	 *
	 * @return array<string,string>
	 */
	private function buildCustomerDataFromOrder(): array {
		return array(
			'name'    => $this->order_data['customer_name'] ?? $this->order_data['customerName'] ?? 'Client Example',
			'email'   => $this->order_data['customer_email'] ?? $this->order_data['customerEmail'] ?? 'client@example.com',
			'phone'   => $this->order_data['customer_phone'] ?? $this->order_data['customerPhone'] ?? '',
			'address' => $this->order_data['customer_address'] ?? $this->order_data['billing_address'] ?? $this->order_data['customerAddress'] ?? '',
			'company' => $this->order_data['customer_company'] ?? $this->order_data['billing_company'] ?? $this->order_data['customerCompany'] ?? '',
		);
	}

	/**
	 * Build customer content HTML.
	 *
	 * @param array  $customer_data  Customer data.
	 * @param array  $display_config Display configuration.
	 * @param int    $header_size    Header font size.
	 * @param string $header_color   Header text color.
	 * @param int    $body_size      Body font size.
	 * @param string $text_color     Body text color.
	 */
	private function buildCustomerContentHTML( array $customer_data, array $display_config, int $header_size, string $header_color, int $body_size, string $text_color ): string {
		$html = '';
		if ( $display_config['name'] && ! empty( $customer_data['name'] ) ) {
			$html .= '<div style="font-size:' . $header_size . 'px;font-weight:bold;color:' . $header_color . ';margin-bottom:5px;">';
			$html .= htmlspecialchars( $customer_data['name'] ) . '</div>';
		}
		if ( $display_config['company'] && ! empty( $customer_data['company'] ) ) {
			$html .= '<div style="font-size:' . $body_size . self::CSS_PX_COLOR . $text_color . ';margin-bottom:3px;">';
			$html .= htmlspecialchars( $customer_data['company'] ) . '</div>';
		}
		if ( $display_config['address'] && ! empty( $customer_data['address'] ) ) {
			foreach ( explode( "\n", $customer_data['address'] ) as $line ) {
				$line = trim( $line );
				if ( '' !== $line ) {
					$html .= '<div style="font-size:' . $body_size . self::CSS_PX_COLOR . $text_color . self::CSS_MB2_CLOSE;
					$html .= htmlspecialchars( $line ) . '</div>';
				}
			}
		}
		if ( $display_config['email'] && ! empty( $customer_data['email'] ) ) {
			$html .= '<div style="font-size:' . $body_size . self::CSS_PX_COLOR . $text_color . self::CSS_MB2_CLOSE;
			$html .= htmlspecialchars( $customer_data['email'] ) . '</div>';
		}
		if ( $display_config['phone'] && ! empty( $customer_data['phone'] ) ) {
			$html .= '<div style="font-size:' . $body_size . self::CSS_PX_COLOR . $text_color . self::CSS_MB2_CLOSE;
			$html .= htmlspecialchars( $customer_data['phone'] ) . '</div>';
		}
		return $html;
	}

	/**
	 * Normalize a color value.
	 *
	 * @param string $color Color value.
	 */
	protected function normalize_color( string $color ) {
		if ( empty( $color ) || 'transparent' === $color ) {
			return $color ?? 'transparent';
		}
		// Ensure hex colors have #.
		if ( ctype_xdigit( $color ) && ( strlen( $color ) === 6 || strlen( $color ) === 3 ) ) {
			return '#' . $color;
		}
		return $color;
	}
}
