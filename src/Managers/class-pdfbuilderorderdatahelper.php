<?php
/**
 * Aides de données de commande.
 *
 * @package PDFIB\Managers
 */

namespace PDFIB\Managers;

defined( 'ABSPATH' ) || exit;

/**
 * Construit les remplacements de variables WooCommerce pour les commandes.
 */
class PdfBuilderOrderDataHelper {
	/**
	 * Construit les variables de remplacement produit pour une commande.
	 *
	 * @param object $order Commande WooCommerce.
	 * @return array
	 */
	public function get_product_replacements( object $order ): array {
		$items = $order->get_items();
		if ( empty( $items ) ) {
			return array();
		}

		$first_item          = reset( $items );
		$first_item_quantity = max( 1, (int) $first_item->get_quantity() );
		$replacements        = array(
			'{{product_name}}'  => $first_item->get_name(),
			'{{product_qty}}'   => $first_item->get_quantity(),
			'{{product_price}}' => function_exists( 'wc_price' ) ? \wc_price( $first_item->get_total() / $first_item_quantity ) : number_format( $first_item->get_total() / $first_item_quantity, 2 ),
			'{{product_total}}' => function_exists( 'wc_price' ) ? \wc_price( $first_item->get_total() ) : number_format( $first_item->get_total(), 2 ),
			'{{product_sku}}'   => $first_item->get_product() ? $first_item->get_product()->get_sku() : '',
		);

		if ( count( $items ) > 1 ) {
			$summary = '';
			foreach ( $items as $item ) {
				$summary .= $item->get_name() . ' (x' . $item->get_quantity() . ') - ' . ( function_exists( 'wc_price' ) ? \wc_price( $item->get_total() ) : number_format( $item->get_total(), 2 ) ) . "\n";
			}
			$replacements['{{products_list}}'] = $summary;
		} else {
			$replacements['{{products_list}}'] = $first_item->get_name() . ' (x' . $first_item->get_quantity() . ') - ' . ( function_exists( 'wc_price' ) ? \wc_price( $first_item->get_total() ) : number_format( $first_item->get_total(), 2 ) );
		}

		return $replacements;
	}

	/**
	 * Construit les variables de remplacement financières pour une commande.
	 *
	 * @param object $order Commande WooCommerce.
	 * @return array
	 */
	public function get_financial_replacements( object $order ): array {
		$subtotal       = $order->get_subtotal();
		$total_tax      = $order->get_total_tax();
		$shipping_total = $order->get_shipping_total();
		$discount_total = $order->get_discount_total();

		return array(
			'{{subtotal}}'        => function_exists( 'wc_price' ) ? \wc_price( $subtotal ) : number_format( $subtotal, 2 ),
			'{{tax_amount}}'      => function_exists( 'wc_price' ) ? \wc_price( $total_tax ) : number_format( $total_tax, 2 ),
			'{{shipping_amount}}' => function_exists( 'wc_price' ) ? \wc_price( $shipping_total ) : number_format( $shipping_total, 2 ),
			'{{discount_amount}}' => function_exists( 'wc_price' ) ? \wc_price( $discount_total ) : number_format( $discount_total, 2 ),
			'{{total_excl_tax}}'  => function_exists( 'wc_price' ) ? \wc_price( $order->get_total() - $total_tax ) : number_format( $order->get_total() - $total_tax, 2 ),
		);
	}

	/**
	 * Construit les variables de remplacement d'adresse pour une commande.
	 *
	 * @param object $order Commande WooCommerce.
	 * @return array
	 */
	public function get_address_replacements( object $order ): array {
		return array(
			'{{billing_first_name}}'  => $order->get_billing_first_name(),
			'{{billing_last_name}}'   => $order->get_billing_last_name(),
			'{{billing_company}}'     => $order->get_billing_company(),
			'{{billing_address_1}}'   => $order->get_billing_address_1(),
			'{{billing_address_2}}'   => $order->get_billing_address_2(),
			'{{billing_city}}'        => $order->get_billing_city(),
			'{{billing_state}}'       => $order->get_billing_state(),
			'{{billing_postcode}}'    => $order->get_billing_postcode(),
			'{{billing_country}}'     => $order->get_billing_country(),
			'{{shipping_first_name}}' => $order->get_shipping_first_name(),
			'{{shipping_last_name}}'  => $order->get_shipping_last_name(),
			'{{shipping_company}}'    => $order->get_shipping_company(),
			'{{shipping_address_1}}'  => $order->get_shipping_address_1(),
			'{{shipping_address_2}}'  => $order->get_shipping_address_2(),
			'{{shipping_city}}'       => $order->get_shipping_city(),
			'{{shipping_state}}'      => $order->get_shipping_state(),
			'{{shipping_postcode}}'   => $order->get_shipping_postcode(),
			'{{shipping_country}}'    => $order->get_shipping_country(),
		);
	}

	/**
	 * Récupère et valide complètement une commande WooCommerce.
	 *
	 * @param int $order_id Identifiant de la commande.
	 * @return object|\WP_Error
	 */
	public function get_and_validate_order( int $order_id ): object {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return new \WP_Error( 'woocommerce_not_active', __( 'WooCommerce n\'est pas actif', 'advanced-pdf-invoice-builder' ) );
		}
		$order = \wc_get_order( $order_id );
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return new \WP_Error( 'order_not_found', $order ? __( 'Type d\'objet invalide', 'advanced-pdf-invoice-builder' ) : __( 'Commande introuvable', 'advanced-pdf-invoice-builder' ) );
		}
		return $this->validate_order_permissions_and_status( $order );
	}

	/**
	 * Vérifie l'accès utilisateur et le statut d'une commande.
	 *
	 * @param object $order Commande WooCommerce.
	 * @return object|\WP_Error
	 */
	private function validate_order_permissions_and_status( object $order ): object {
		if ( ! \current_user_can( 'manage_woocommerce' ) ) {
			$user_id = get_current_user_id();
			if ( $user_id !== $order->get_customer_id() && ! \current_user_can( 'edit_shop_orders' ) ) {
				return new \WP_Error( 'access_denied', 'Accès non autorisé à cette commande' );
			}
		}
		$status_result = $this->check_order_status_validity( $order );
		return ( $status_result instanceof \WP_Error ) ? $status_result : $order;
	}

	/**
	 * Vérifie que le statut de la commande est valide pour le traitement.
	 *
	 * @param object $order Commande WooCommerce.
	 * @return bool|\WP_Error
	 */
	private function check_order_status_validity( object $order ): bool|\WP_Error {
		$current_status     = $order->get_status();
		$normalized_current = ( strpos( $current_status, 'wc-' ) !== 0 ) ? 'wc-' . $current_status : $current_status;

		$valid_statuses   = function_exists( 'wc_get_order_statuses' ) ? array_keys( \wc_get_order_statuses() ) : array();
		$settings         = pdfib_get_option( 'pdfib_settings', array() );
		$status_templates = $settings['pdfib_order_status_templates'] ?? array();
		$valid_statuses   = array_merge( $valid_statuses, array_keys( $status_templates ) );

		if ( ! in_array( $normalized_current, $valid_statuses, true ) && ! in_array( $current_status, $valid_statuses, true ) ) {
			return new \WP_Error( 'invalid_order_status', 'Statut de commande non valide pour le traitement' );
		}

		return true;
	}

	/**
	 * Récupère les données complètes des articles de commande.
	 *
	 * @param object $order Commande WooCommerce.
	 * @return array
	 */
	public function get_order_items_complete_data( object $order ): array {
		$items_data = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			$product   = $item->get_product();
			$item_data = array(
				'id'            => $item_id,
				'name'          => $item->get_name(),
				'quantity'      => (float) $item->get_quantity(),
				'price'         => (float) ( $item->get_total() / max( 1, $item->get_quantity() ) ),
				'regular_price' => $product ? (float) $product->get_regular_price() : null,
				'sale_price'    => $product ? (float) $product->get_sale_price() : null,
				'total'         => (float) $item->get_total(),
				'total_tax'     => (float) $item->get_total_tax(),
				'subtotal'      => (float) $item->get_subtotal(),
				'subtotal_tax'  => method_exists( $item, 'get_subtotal_tax' ) ? (float) $item->get_subtotal_tax() : 0.0,
				'tax_class'     => $item->get_tax_class(),
				'sku'           => $product ? $product->get_sku() : '',
				'product_id'    => $item->get_product_id(),
				'variation_id'  => $item->get_variation_id(),
				'type'          => $product ? $product->get_type() : 'simple',
			);

			if ( $item->get_variation_id() ) {
				$item_data['variation_attributes'] = $this->get_item_variation_data( $item->get_variation_id() );
			}

			$item_data['meta_data'] = array();
			foreach ( $item->get_meta_data() as $meta ) {
				$item_data['meta_data'][ $meta->key ] = $meta->value;
			}

			$items_data[] = $item_data;
		}

		return $items_data;
	}

	/**
	 * Récupère les attributs de variation d'un article de commande.
	 *
	 * @param int $variation_id Identifiant de variation.
	 * @return array
	 */
	private function get_item_variation_data( int $variation_id ): array {
		$variation = function_exists( 'wc_get_product' ) ? \wc_get_product( $variation_id ) : null;
		if ( ! $variation || ! is_object( $variation ) ) {
			return array();
		}

		$result               = array();
		$variation_attributes = $variation->get_variation_attributes();

		foreach ( $variation_attributes as $attribute_name => $attribute_value ) {
			$attribute_name_clean = str_replace( array( 'attribute_', 'pa_' ), '', $attribute_name );
			$attribute_taxonomy   = 'pa_' . $attribute_name_clean;
			$attribute_terms      = get_terms(
				array(
					'taxonomy'   => $attribute_taxonomy,
					'slug'       => $attribute_value,
					'hide_empty' => false,
				)
			);

			$attribute_label = $attribute_terms && ! \is_wp_error( $attribute_terms ) && ! empty( $attribute_terms )
				? $attribute_terms[0]->name
				: ucfirst( $attribute_name_clean );

			$result[ $attribute_name_clean ] = array(
				'label'    => $attribute_label,
				'value'    => $attribute_value,
				'taxonomy' => $attribute_taxonomy,
			);
		}

		return $result;
	}
}
