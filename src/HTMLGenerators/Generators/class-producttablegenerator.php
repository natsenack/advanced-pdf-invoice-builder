<?php
/**
 * Product table element generator.
 *
 * @package PDFIB\HTMLGenerators\Generators
 */

namespace PDFIB\HTMLGenerators\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PDFIB\HTMLGenerators\ElementGeneratorBase;

/**
 * Generates HTML for product table elements.
 */
class ProductTableGenerator extends ElementGeneratorBase {


	/**
	 * Generate HTML for this element.
	 *
	 * @return string
	 */
	public function generate_html() {
		$style_attr = $this->create_style_string( $this->get_element_styles() );
		$columns    = $this->get_property(
			'columns',
			array(
				array(
					'label' => 'Produit',
					'key'   => 'name',
				),
				array(
					'label' => 'Quantité',
					'key'   => 'quantity',
				),
				array(
					'label' => 'Prix',
					'key'   => 'price',
				),
				array(
					'label' => 'Total',
					'key'   => 'total',
				),
			)
		);
		$items      = $this->order_data['products'] ?? $this->order_data['items'] ?? array();
		return '<div class="pdf-element pdf-product-table" ' . $style_attr . '>'
			. $this->buildProductTableHtml( $columns, $items )
			. '</div>';
	}

	/**
	 * Map legacy column key aliases to actual OrderDataExtractor keys.
	 */
	private const KEY_ALIASES = array(
		'product_name'  => 'name',
		'product_qty'   => 'quantity',
		'product_price' => 'price',
		'product_total' => 'total',
		'product_sku'   => 'sku',
		'product_image' => 'image',
	);

	/**
	 * Build product table HTML.
	 *
	 * @param array $columns Table columns.
	 * @param array $items   Table items.
	 */
	private function buildProductTableHtml( array $columns, array $items ): string {
		$header = $this->buildTableHeader( $columns );
		$body   = $this->buildTableBody( $columns, $items );
		$tfoot  = $this->buildTableFooter( $columns );
		return '<table style="width:100%; border-collapse:collapse;"><thead>' . $header . '</thead>'
			. $tfoot
			. '<tbody>' . $body . '</tbody></table>';
	}

	/**
	 * En-tête du tableau.
	 *
	 * @param array $columns Table columns.
	 */
	private function buildTableHeader( array $columns ): string {
		$header = '<tr style="border-bottom:1px solid #ddd;">';
		foreach ( $columns as $column ) {
			$header .= '<th style="padding:8px; text-align:left; font-weight:bold;">' . htmlspecialchars( $column['label'] ?? '' ) . '</th>';
		}
		return $header . '</tr>';
	}

	/**
	 * Corps du tableau (lignes produits ou ligne placeholder si vide).
	 *
	 * @param array $columns Table columns.
	 * @param array $items   Table items.
	 */
	private function buildTableBody( array $columns, array $items ): string {
		if ( empty( $items ) ) {
			$row = '<tr style="border-bottom:1px solid #eee;">';
			foreach ( $columns as $column ) {
				$row .= '<td style="padding:8px;">-</td>';
			}
			return $row . '</tr>';
		}
		$body = '';
		foreach ( $items as $item ) {
			$row = '<tr style="border-bottom:1px solid #eee;">';
			foreach ( $columns as $column ) {
				$row .= $this->buildTableCell( $column, $item );
			}
			$body .= $row . '</tr>';
		}
		return $body;
	}

	/**
	 * Cellule de tableau (image ou texte).
	 *
	 * @param array $column Column definition.
	 * @param array $item   Item data.
	 */
	private function buildTableCell( array $column, array $item ): string {
		$raw_key = $column['key'] ?? '';
		$key     = self::KEY_ALIASES[ $raw_key ] ?? $raw_key;
		$value   = $item[ $key ] ?? $item[ $raw_key ] ?? '-';
		if ( 'image' === $key && ! empty( $value ) ) {
			return '<td style="padding:8px;"><img src="' . esc_url( (string) $value ) . '" style="max-width:40px;max-height:40px;" /></td>';
		}
		return '<td style="padding:8px;">' . htmlspecialchars( (string) $value ) . '</td>';
	}

	/**
	 * Pied de tableau (totaux) - retourne '' si pas de totaux.
	 *
	 * @param array $columns Table columns.
	 */
	private function buildTableFooter( array $columns ): string {
		$totals = $this->order_data['totals'] ?? array();
		if ( empty( $totals ) ) {
			return '';
		}
		$col_count = count( $columns );
		$last_two  = $col_count >= 2 ? $col_count - 2 : 0;
		$tfoot     = '<tfoot>';
		if ( ! empty( $totals['subtotal'] ) ) {
			$tfoot .= '<tr><td colspan="' . $last_two . '" style="padding:6px 8px;"></td>'
				. '<td style="padding:6px 8px; font-weight:bold;">Sous-total</td>'
				. '<td style="padding:6px 8px;">' . htmlspecialchars( (string) $totals['subtotal'] ) . '</td></tr>';
		}
		if ( ! empty( $totals['tax'] ) && ( $totals['tax_raw'] ?? 0 ) > 0 ) {
			$tfoot .= '<tr><td colspan="' . $last_two . '"></td>'
				. '<td style="padding:6px 8px; font-weight:bold;">TVA</td>'
				. '<td style="padding:6px 8px;">' . htmlspecialchars( (string) $totals['tax'] ) . '</td></tr>';
		}
		if ( ! empty( $totals['total'] ) ) {
			$tfoot .= '<tr style="border-top:2px solid #333;"><td colspan="' . $last_two . '"></td>'
				. '<td style="padding:8px; font-weight:bold; font-size:1.1em;">TOTAL</td>'
				. '<td style="padding:8px; font-weight:bold; font-size:1.1em;">' . htmlspecialchars( (string) $totals['total'] ) . '</td></tr>';
		}
		return $tfoot . '</tfoot>';
	}
}
