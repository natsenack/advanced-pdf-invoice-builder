<?php
/**
 * Advanced PDF Invoice Builder - Order Product Table Renderer
 *
 * Handles building of order product table HTML, extracted from HTMLRenderer.
 *
 * @package PDFIB\Admin\Renderers
 */

namespace PDFIB\Admin\Renderers;

use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Fee;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds HTML for order product tables.
 */
class OrderProductTableRenderer {

	const STYLE_ALIGN_CENTER = ' text-align: center;">';
	const COLOR_WHITE        = '#ffffff';
	const COLOR_DARK         = '#1e293b';

	/**
	 * Builds the product table header row.
	 *
	 * @param bool   $show_headers Whether to show the header row.
	 * @param array  $columns      Column visibility settings.
	 * @param string $header_css   CSS styles for header cells.
	 * @return string
	 */
	public function build_product_table_header( bool $show_headers, array $columns, string $header_css ): string {
		if ( ! $show_headers ) {
			return '';
		}
		$html = '<thead><tr>';
		if ( $columns['image'] ?? false ) {
			$html .= '<th style="' . $header_css . '">Image</th>';
		}
		if ( $columns['name'] ?? true ) {
			$html .= '<th style="' . $header_css . '">Produit</th>';
		}
		if ( $columns['sku'] ?? false ) {
			$html .= '<th style="' . $header_css . '">SKU</th>';
		}
		if ( $columns['quantity'] ?? true ) {
			$html .= '<th style="' . $header_css . '">Qté</th>';
		}
		if ( $columns['price'] ?? true ) {
			$html .= '<th style="' . $header_css . '">Prix</th>';
		}
		if ( $columns['total'] ?? true ) {
			$html .= '<th style="' . $header_css . '">Total</th>';
		}
		return $html . '</tr></thead>';
	}

	/**
	 * Builds product rows for the table body.
	 *
	 * @param object $order    The WooCommerce order.
	 * @param array  $columns  Column visibility settings.
	 * @param string $cell_css CSS for regular cells.
	 * @param string $alt_css  CSS for alternating row cells.
	 * @return array{0: string, 1: int}
	 */
	public function build_product_item_rows( object $order, array $columns, string $cell_css, string $alt_css ): array {
		$html  = '';
		$count = 0;
		foreach ( $order->get_items() as $item ) {
			$product   = $item->get_product();
			$row_style = ( 1 === $count % 2 ) ? $alt_css : $cell_css;
			$html     .= $this->build_single_product_row( $item, $product, $columns, $row_style );
			++$count;
		}
		return array( $html, $count );
	}

	/**
	 * Builds a single product row HTML.
	 *
	 * @param object $item      The order item.
	 * @param mixed  $product   The product object or false.
	 * @param array  $columns   Column visibility settings.
	 * @param string $row_style CSS styles for the row.
	 * @return string
	 */
	public function build_single_product_row( object $item, mixed $product, array $columns, string $row_style ): string {
		$html = '<tr>';
		if ( $columns['image'] ?? false ) {
			$img_url = $product ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';
			$html   .= '<td style="' . $row_style . self::STYLE_ALIGN_CENTER . ( $img_url ? '<img src="' . esc_url( $img_url ) . '" style="max-width: 40px; max-height: 40px; object-fit: contain;" />' : '' ) . '</td>';
		}
		if ( $columns['name'] ?? true ) {
			$html .= '<td style="' . $row_style . '">' . esc_html( $item->get_name() ) . '</td>';
		}
		if ( $columns['sku'] ?? false ) {
			$html .= '<td style="' . $row_style . self::STYLE_ALIGN_CENTER . esc_html( $product ? $product->get_sku() : '' ) . '</td>';
		}
		if ( $columns['quantity'] ?? true ) {
			$html .= '<td style="' . $row_style . self::STYLE_ALIGN_CENTER . absint( $item->get_quantity() ) . '</td>';
		}
		if ( $columns['price'] ?? true ) {
			$quantity = max( 1, (int) $item->get_quantity() );
			$p        = $this->format_cell_price( (float) ( $item->get_total() / $quantity ) );
			$html    .= '<td style="' . $row_style . ' text-align: right;">' . wp_kses_post( $p ) . '</td>';
		}
		if ( $columns['total'] ?? true ) {
			$t     = $this->format_cell_price( (float) $item->get_total() );
			$html .= '<td style="' . $row_style . ' text-align: right;">' . wp_kses_post( $t ) . '</td>';
		}
		return $html . '</tr>';
	}

	/**
	 * Formats a price value as an HTML string.
	 *
	 * @param float $amount The price amount to format.
	 * @return string
	 */
	public function format_cell_price( float $amount ): string {
		return function_exists( 'wc_price' ) ? wc_price( $amount ) : '$' . number_format( $amount, 2 );
	}

	/**
	 * Builds fee rows HTML for the product table.
	 *
	 * @param object $order     The WooCommerce order.
	 * @param array  $columns   Column visibility settings.
	 * @param int    $row_count Current row count for alternating styles.
	 * @param string $cell_css  CSS for regular cells.
	 * @param string $alt_css   CSS for alternating row cells.
	 * @return string
	 */
	public function build_fee_rows_html( object $order, array $columns, int $row_count, string $cell_css, string $alt_css ): string {
		$html = '';
		foreach ( $order->get_fees() as $fee ) {
			$row_style = ( 1 === $row_count % 2 ) ? $alt_css : $cell_css;
			$html     .= $this->build_single_fee_row( $fee, $columns, $row_style );
			++$row_count;
		}
		return $html;
	}

	/**
	 * Builds a single fee row HTML.
	 *
	 * @param object $fee       The fee order item.
	 * @param array  $columns   Column visibility settings.
	 * @param string $row_style CSS styles for the row.
	 * @return string
	 */
	public function build_single_fee_row( object $fee, array $columns, string $row_style ): string {
		$html = '<tr>';
		if ( $columns['image'] ?? false ) {
			$html .= '<td style="' . $row_style . '"></td>';
		}
		if ( $columns['name'] ?? true ) {
			$html .= '<td style="' . $row_style . ' font-weight: bold;">' . esc_html( $fee->get_name() ) . '</td>';
		}
		if ( $columns['sku'] ?? false ) {
			$html .= '<td style="' . $row_style . '"></td>';
		}
		if ( $columns['quantity'] ?? true ) {
			$html .= '<td style="' . $row_style . ' text-align: center;">-</td>';
		}
		if ( $columns['price'] ?? true ) {
			$html .= '<td style="' . $row_style . ' text-align: right;">-</td>';
		}
		if ( $columns['total'] ?? true ) {
			$fp    = $this->format_cell_price( (float) $fee->get_total() );
			$html .= '<td style="' . $row_style . ' text-align: right; font-weight: bold;">' . wp_kses_post( $fp ) . '</td>';
		}
		return $html . '</tr>';
	}

	/**
	 * Returns all available order table styles.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_order_table_styles(): array {
		return array_merge( $this->get_order_table_styles_base(), $this->get_order_table_styles_extended() );
	}

	/**
	 * Returns base order table styles.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_order_table_styles_base(): array {
		return array(
			'default' => array(
				'header_bg'        => array(
					'r' => 248,
					'g' => 249,
					'b' => 250,
				), // #f8f9fa
				'header_border'    => array(
					'r' => 226,
					'g' => 232,
					'b' => 240,
				), // #e2e8f0
				'row_border'       => array(
					'r' => 241,
					'g' => 245,
					'b' => 249,
				), // #f1f5f9
				'alt_row_bg'       => array(
					'r' => 250,
					'g' => 251,
					'b' => 252,
				), // #fafbfc
				'headerTextColor'  => '#000000',
				'rowTextColor'     => '#000000',
				'border_width'     => 1,
				'headerFontWeight' => 'bold',
				'headerFontSize'   => '12px',
				'rowFontSize'      => '11px',
			),
			'classic' => array(
				'header_bg'        => array(
					'r' => 30,
					'g' => 41,
					'b' => 59,
				),   // #1e293b
				'header_border'    => array(
					'r' => 51,
					'g' => 65,
					'b' => 85,
				),   // #334155
				'row_border'       => array(
					'r' => 51,
					'g' => 65,
					'b' => 85,
				),   // #334155
				'alt_row_bg'       => array(
					'r' => 255,
					'g' => 255,
					'b' => 255,
				), // #ffffff
				'headerTextColor'  => self::COLOR_WHITE,
				'rowTextColor'     => self::COLOR_DARK,
				'border_width'     => 1.5,
				'headerFontWeight' => '700',
				'headerFontSize'   => '11px',
				'rowFontSize'      => '10px',
			),
			'blue'    => array(
				'header_bg'        => array(
					'r' => 59,
					'g' => 130,
					'b' => 246,
				),  // #3b82f6
				'header_border'    => array(
					'r' => 37,
					'g' => 99,
					'b' => 235,
				),   // #2563eb
				'row_border'       => array(
					'r' => 226,
					'g' => 232,
					'b' => 240,
				), // #e2e8f0
				'alt_row_bg'       => array(
					'r' => 248,
					'g' => 249,
					'b' => 250,
				), // #f8fafc
				'headerTextColor'  => self::COLOR_WHITE,
				'rowTextColor'     => self::COLOR_DARK,
				'border_width'     => 1,
				'headerFontWeight' => 'bold',
				'headerFontSize'   => '11px',
				'rowFontSize'      => '10px',
			),
		);
	}

	/**
	 * Returns extended order table styles.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_order_table_styles_extended(): array {
		return array(
			'minimal'        => array(
				'header_bg'        => array(
					'r' => 255,
					'g' => 255,
					'b' => 255,
				), // #ffffff
				'header_border'    => array(
					'r' => 55,
					'g' => 65,
					'b' => 81,
				),    // #374151
				'row_border'       => array(
					'r' => 209,
					'g' => 213,
					'b' => 219,
				), // #d1d5db
				'alt_row_bg'       => array(
					'r' => 255,
					'g' => 255,
					'b' => 255,
				), // #ffffff
				'headerTextColor'  => '#374151',
				'rowTextColor'     => '#374151',
				'border_width'     => 1,
				'headerFontWeight' => '600',
				'headerFontSize'   => '11px',
				'rowFontSize'      => '10px',
			),
			'light'          => array(
				'header_bg'        => array(
					'r' => 255,
					'g' => 255,
					'b' => 255,
				), // #ffffff
				'header_border'    => array(
					'r' => 243,
					'g' => 244,
					'b' => 246,
				), // #f3f4f6
				'row_border'       => array(
					'r' => 249,
					'g' => 250,
					'b' => 251,
				), // #f9fafb
				'alt_row_bg'       => array(
					'r' => 255,
					'g' => 255,
					'b' => 255,
				), // #ffffff
				'headerTextColor'  => self::COLOR_DARK,
				'rowTextColor'     => self::COLOR_DARK,
				'border_width'     => 1,
				'headerFontWeight' => '500',
				'headerFontSize'   => '11px',
				'rowFontSize'      => '10px',
			),
			'emerald_forest' => array(
				'header_bg'        => array(
					'r' => 6,
					'g' => 78,
					'b' => 59,
				),    // #064e3b
				'header_border'    => array(
					'r' => 6,
					'g' => 95,
					'b' => 70,
				),    // #065f46
				'row_border'       => array(
					'r' => 209,
					'g' => 250,
					'b' => 229,
				), // #d1fae5
				'alt_row_bg'       => array(
					'r' => 236,
					'g' => 253,
					'b' => 245,
				), // #ecfdf5
				'headerTextColor'  => self::COLOR_WHITE,
				'rowTextColor'     => '#064e3b',
				'border_width'     => 1.5,
				'headerFontWeight' => '600',
				'headerFontSize'   => '11px',
				'rowFontSize'      => '10px',
			),
		);
	}

	/**
	 * Generates the complete order products table HTML.
	 *
	 * @param object $order       The WooCommerce order.
	 * @param string $table_style Table style identifier.
	 * @param mixed  $element     Element configuration options.
	 * @return string
	 */
	public function generate_order_products_table( object $order, string $table_style = 'default', mixed $element = null ) {
		[$show_headers, $show_borders, $show_subtotal, $columns] = $this->parse_table_options( $element );
		$styles = $this->build_table_styles( $table_style, $show_borders );

		$html                    = '<table style="' . $styles['table'] . '">';
		$html                   .= $this->build_product_table_header( $show_headers, $columns, $styles['header'] );
		$html                   .= '<tbody>';
		[$rows_html, $row_count] = $this->build_product_item_rows( $order, $columns, $styles['cell'], $styles['alt_row'] );
		$html                   .= $rows_html;
		$html                   .= $this->build_fee_rows_html( $order, $columns, $row_count, $styles['cell'], $styles['alt_row'] );

		if ( $show_subtotal ) {
			$colspan = array_sum( array_map( 'intval', array( $columns['image'] ?? false, $columns['name'] ?? true, $columns['sku'] ?? false, $columns['quantity'] ?? true, $columns['price'] ?? true ) ) );
			$html   .= '<tr>';
			if ( $colspan > 0 ) {
				$html .= '<td colspan="' . $colspan . '" style="' . $styles['cell'] . ' text-align: right; font-weight: bold;">Sous-total:</td>';
			}
			if ( $columns['total'] ?? true ) {
				$subtotal = $this->format_cell_price( (float) $order->get_subtotal() );
				$html    .= '<td style="' . $styles['cell'] . ' text-align: right; font-weight: bold;">' . $subtotal . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';
		return $html;
	}

	/**
	 * Parses table display options from element configuration.
	 *
	 * @param mixed $element Element configuration array or null.
	 * @return array
	 */
	public function parse_table_options( mixed $element ): array {
		$show_headers  = isset( $element['showHeaders'] ) ? (bool) $element['showHeaders'] : true;
		$show_borders  = isset( $element['showBorders'] ) ? (bool) $element['showBorders'] : true;
		$show_subtotal = isset( $element['showSubtotal'] ) ? (bool) $element['showSubtotal'] : false;
		$columns       = isset( $element['columns'] ) && is_array( $element['columns'] ) ? $element['columns'] : array(
			'image'    => false,
			'name'     => true,
			'sku'      => false,
			'quantity' => true,
			'price'    => true,
			'total'    => true,
		);
		return array( $show_headers, $show_borders, $show_subtotal, $columns );
	}

	/**
	 * Builds CSS style strings for the order table.
	 *
	 * @param string $table_style  Table style identifier.
	 * @param bool   $show_borders Whether to show borders.
	 * @return array
	 */
	public function build_table_styles( string $table_style, bool $show_borders ): array {
		$table_styles   = $this->get_order_table_styles();
		$style          = $table_styles[ $table_style ] ?? $table_styles['default'];
		$rgb_to_css     = fn( $rgb ) => sprintf( 'rgb(%d, %d, %d)', $rgb['r'], $rgb['g'], $rgb['b'] );
		$border_w       = $show_borders ? $style['border_width'] : 0;
		$row_border_clr = $rgb_to_css( $style['row_border'] );
		$border_css     = $show_borders ? ' border: ' . $border_w . 'px solid ' . $row_border_clr . ';' : '';
		return array(
			'table'   => 'width: 100%; border-collapse: collapse;' . $border_css,
			'header'  => sprintf( 'background-color: %s; color: %s;%s padding: 6px 8px; font-weight: %s; font-size: %s; text-align: left;', $rgb_to_css( $style['header_bg'] ), $style['headerTextColor'], $show_borders ? ' border: ' . $border_w . 'px solid ' . $rgb_to_css( $style['header_border'] ) . ';' : '', $style['headerFontWeight'], $style['headerFontSize'] ),
			'cell'    => sprintf( '%s padding: 6px 8px; font-size: %s; color: %s;', $border_css, $style['rowFontSize'], $style['rowTextColor'] ),
			'alt_row' => sprintf( '%s padding: 6px 8px; font-size: %s; color: %s; background-color: %s;', $border_css, $style['rowFontSize'], $style['rowTextColor'], $rgb_to_css( $style['alt_row_bg'] ) ),
		);
	}
}
