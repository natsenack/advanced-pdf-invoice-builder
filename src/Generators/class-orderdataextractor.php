<?php
/**
 * Extraction des données de commande WooCommerce pour les templates PDF.
 *
 * @package PDFIB\Generators
 */

namespace PDFIB\Generators;

use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extrait les données d'une commande WooCommerce et les formate pour les templates PDF.
 *
 * @package PDFIB\Generators
 */
class OrderDataExtractor {

	/**
	 * Commande source.
	 *
	 * @var object
	 */
	private WC_Order $order;

	/**
	 * Données extraites et normalisées.
	 *
	 * @var array
	 */
	private array $extracted_data = array();

	/**
	 * Initialise l'extracteur pour une commande donnée.
	 *
	 * @param object $order Commande WooCommerce.
	 */
	public function __construct( object $order ) {
		$this->order = $order;
		$this->extract_all_data();
	}

	/**
	 * Extrait toutes les données de la commande.
	 */
	private function extract_all_data(): void {
		// Données client.
		$this->extracted_data['customer'] = $this->extract_customer_data();

		// Données commande.
		$this->extracted_data['order'] = $this->extract_order_data();

		// Produits.
		$this->extracted_data['products'] = $this->extract_products_data();

		// Frais supplémentaires.
		$this->extracted_data['fees'] = $this->extract_fees_data();

		// Adresses.
		$this->extracted_data['billing']  = $this->extract_billing_address();
		$this->extracted_data['shipping'] = $this->extract_shipping_address();

		// Totaux.
		$this->extracted_data['totals'] = $this->extract_totals();
	}

	/**
	 * Extrait les données client.
	 *
	 * @return array
	 */
	private function extract_customer_data(): array {
		return array(
			'id'         => $this->order->get_customer_id(),
			'first_name' => $this->order->get_billing_first_name(),
			'last_name'  => $this->order->get_billing_last_name(),
			'email'      => $this->order->get_billing_email(),
			'phone'      => $this->order->get_billing_phone(),
			'full_name'  => trim( $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name() ),
		);
	}

	/**
	 * Extrait les données de commande.
	 *
	 * @return array
	 */
	private function extract_order_data(): array {
		$order_created = $this->order->get_date_created();

		return array(
			'id'              => $this->order->get_id(),
			'order_number'    => $this->order->get_order_number(),
			'date'            => $order_created ? $order_created->format( 'Y-m-d H:i:s' ) : '',
			'date_formatted'  => $order_created ? wc_format_datetime( $order_created, get_option( 'date_format' ) ) : '',
			'status'          => $this->order->get_status(),
			'status_label'    => wc_get_order_status_name( $this->order->get_status() ),
			'payment_method'  => $this->order->get_payment_method_title(),
			'transaction_id'  => $this->order->get_transaction_id(),
			'shipping_method' => $this->order->get_shipping_method(),
			'currency'        => $this->order->get_currency(),
			'notes'           => $this->order->get_customer_note(),
		);
	}

	/**
	 * Extrait les données des produits.
	 *
	 * @return array
	 */
	private function extract_products_data(): array {
		$products = array();

		foreach ( $this->order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$unit_price = $item->get_quantity() > 0 ? $item->get_total() / $item->get_quantity() : 0;

			// Récupérer l'image du produit en base64 pour PDF.
			$image_id     = $product->get_image_id();
			$image_base64 = '';
			if ( $image_id ) {
				$image_path = get_attached_file( $image_id );
				if ( $image_path && file_exists( $image_path ) ) {
					$image_data = pdfib_filesystem()->get_contents( $image_path );
					$image_type = wp_check_filetype( $image_path );
					if ( false !== $image_data ) {
						$image_base64 = 'data:' . $image_type['type'] . ';base64,' . sodium_bin2base64( $image_data, SODIUM_BASE64_VARIANT_ORIGINAL );
					}
				}
			}

			// Récupérer la description.
			$description = $product->get_short_description();
			if ( empty( $description ) ) {
				$description = $product->get_description();
			}

			$products[] = array(
				'id'             => $product->get_id(),
				'sku'            => $product->get_sku(),
				'name'           => $item->get_name(),
				'description'    => wp_strip_all_tags( $description ),
				'image'          => $image_base64,
				'quantity'       => $item->get_quantity(),
				'price'          => wc_price( $unit_price ),
				'price_raw'      => (float) $unit_price,
				'total'          => wc_price( $item->get_total() ),
				'total_raw'      => (float) $item->get_total(),
				'subtotal'       => wc_price( $item->get_subtotal() ),
				'subtotal_raw'   => (float) $item->get_subtotal(),
				'tax'            => wc_price( $item->get_total_tax() ),
				'tax_raw'        => (float) $item->get_total_tax(),
				'variation_data' => $this->extract_variation_data( $item ),
			);
		}

		return $products;
	}

	/**
	 * Extrait les frais supplémentaires.
	 *
	 * @return array
	 */
	private function extract_fees_data(): array {
		$fees = array();

		foreach ( $this->order->get_fees() as $fee_id => $fee ) {
			$fees[] = array(
				'id'         => $fee_id,
				'name'       => $fee->get_name(),
				'amount'     => wc_price( $fee->get_amount() ),
				'amount_raw' => (float) $fee->get_amount(),
				'total'      => wc_price( $fee->get_total() ),
				'total_raw'  => (float) $fee->get_total(),
				'tax'        => wc_price( $fee->get_total_tax() ),
				'tax_raw'    => (float) $fee->get_total_tax(),
			);
		}

		return $fees;
	}

	/**
	 * Extrait les données de variation de produit.
	 *
	 * @param mixed $item Élément de commande WooCommerce.
	 * @return array
	 */
	private function extract_variation_data( mixed $item ): array {
		$meta_data  = $item->get_meta_data();
		$variations = array();

		if ( ! empty( $meta_data ) ) {
			foreach ( $meta_data as $meta ) {
				if ( 0 === strpos( $meta->key, 'pa_' ) ) {
					$variations[ $meta->key ] = $meta->value;
				}
			}
		}

		return $variations;
	}

	/**
	 * Extrait l'adresse de facturation.
	 *
	 * @return array
	 */
	private function extract_billing_address(): array {
		$data                 = array(
			'first_name' => $this->order->get_billing_first_name(),
			'last_name'  => $this->order->get_billing_last_name(),
			'company'    => $this->order->get_billing_company(),
			'address_1'  => $this->order->get_billing_address_1(),
			'address_2'  => $this->order->get_billing_address_2(),
			'city'       => $this->order->get_billing_city(),
			'state'      => $this->order->get_billing_state(),
			'postcode'   => $this->order->get_billing_postcode(),
			'country'    => $this->order->get_billing_country(),
		);
		$data['full_address'] = $this->format_address( $data );

		return $data;
	}

	/**
	 * Extrait l'adresse de livraison.
	 *
	 * @return array
	 */
	private function extract_shipping_address(): array {
		$data                 = array(
			'first_name' => $this->order->get_shipping_first_name(),
			'last_name'  => $this->order->get_shipping_last_name(),
			'company'    => $this->order->get_shipping_company(),
			'address_1'  => $this->order->get_shipping_address_1(),
			'address_2'  => $this->order->get_shipping_address_2(),
			'city'       => $this->order->get_shipping_city(),
			'state'      => $this->order->get_shipping_state(),
			'postcode'   => $this->order->get_shipping_postcode(),
			'country'    => $this->order->get_shipping_country(),
		);
		$data['full_address'] = $this->format_address( $data );

		return $data;
	}

	/**
	 * Extrait les totaux.
	 *
	 * @return array
	 */
	private function extract_totals(): array {
		return array(
			'subtotal'     => wc_price( $this->order->get_subtotal() ),
			'subtotal_raw' => $this->order->get_subtotal(),
			'shipping'     => wc_price( $this->order->get_shipping_total() ),
			'shipping_raw' => $this->order->get_shipping_total(),
			'tax'          => wc_price( $this->order->get_total_tax() ),
			'tax_raw'      => $this->order->get_total_tax(),
			'discount'     => wc_price( $this->order->get_discount_total() ),
			'discount_raw' => $this->order->get_discount_total(),
			'total'        => wc_price( $this->order->get_total() ),
			'total_raw'    => $this->order->get_total(),
		);
	}

	/**
	 * Formate une adresse.
	 *
	 * @param array $address Données d'adresse.
	 * @return string
	 */
	private function format_address( array $address ): string {
		$lines = array();

		// NE PAS inclure le nom (first_name/last_name) car il est affiché séparément dans customer_info.
		// Correction : le nom apparaissait en double dans l'aperçu HTML.

		// Ligne 1 : Company (si présente, sur sa propre ligne).
		if ( ! empty( $address['company'] ) ) {
			$lines[] = $address['company'];
		}

		// Ligne 2 : Adresse complète sur une seule ligne (rue, code postal, ville).
		$address_parts = array();
		if ( ! empty( $address['address_1'] ) ) {
			$address_parts[] = $address['address_1'];
		}
		if ( ! empty( $address['address_2'] ) ) {
			$address_parts[] = $address['address_2'];
		}
		if ( ! empty( $address['postcode'] ) || ! empty( $address['city'] ) ) {
			$address_parts[] = trim( $address['postcode'] . ' ' . $address['city'] );
		}
		if ( ! empty( $address['state'] ) ) {
			$address_parts[] = $address['state'];
		}

		if ( ! empty( $address_parts ) ) {
			$lines[] = implode( ', ', $address_parts );
		}

		// Ligne 3 : Pays sur une ligne séparée.
		if ( ! empty( $address['country'] ) ) {
			$lines[] = WC()->countries->countries[ $address['country'] ] ?? $address['country'];
		}

		return implode( "\n", array_filter( $lines ) );
	}

	/**
	 * Retourne toutes les données extraites.
	 *
	 * @return array
	 */
	public function get_all_data(): array {
		return $this->extracted_data;
	}

	/**
	 * Retourne les données client extraites.
	 *
	 * @return array
	 */
	public function get_customer(): array {
		return $this->extracted_data['customer'];
	}

	/**
	 * Retourne les données de commande extraites.
	 *
	 * @return array
	 */
	public function get_order(): array {
		return $this->extracted_data['order'];
	}

	/**
	 * Retourne les produits extraits.
	 *
	 * @return array
	 */
	public function get_products(): array {
		return $this->extracted_data['products'];
	}

	/**
	 * Retourne l'adresse de facturation extraite.
	 *
	 * @return array
	 */
	public function get_billing(): array {
		return $this->extracted_data['billing'];
	}

	/**
	 * Retourne l'adresse de livraison extraite.
	 *
	 * @return array
	 */
	public function get_shipping(): array {
		return $this->extracted_data['shipping'];
	}

	/**
	 * Retourne les totaux extraits.
	 *
	 * @return array
	 */
	public function get_totals(): array {
		return $this->extracted_data['totals'];
	}

	/**
	 * Remplace les placeholders dans un texte par les vraies données.
	 *
	 * Exemple: "Client: {customer.full_name}" -> "Client: John Doe".
	 *
	 * @param string $text Texte source.
	 * @return string
	 */
	public function replace_placeholders( string $text ): string {
		preg_match_all( '/\{([^}]+)\}/', $text, $matches );

		if ( empty( $matches[1] ) ) {
			return $text;
		}

		foreach ( $matches[1] as $placeholder ) {
			$value = $this->get_value_by_path( $placeholder );

			if ( null !== $value ) {
				$text = str_replace( '{' . $placeholder . '}', $value, $text );
			}
		}

		return $text;
	}

	/**
	 * Récupère une valeur avec une clé pointée.
	 *
	 * Exemple: "customer.full_name" -> $extracted_data['customer']['full_name'].
	 *
	 * @param string $path Chemin pointé dans le tableau extrait.
	 * @return mixed
	 */
	private function get_value_by_path( string $path ) {
		$keys  = explode( '.', $path );
		$value = $this->extracted_data;

		foreach ( $keys as $key ) {
			if ( is_array( $value ) && isset( $value[ $key ] ) ) {
				$value = $value[ $key ];
			} else {
				return null;
			}
		}

		return $value;
	}
}
