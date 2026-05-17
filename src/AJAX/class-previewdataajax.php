<?php
/**
 * Advanced PDF Invoice Builder - Preview Data AJAX Handler.
 *
 * Récupère les données réelles WooCommerce pour l'aperçu des templates.
 * S'inspire de OrderValueRetriever du plugin concurrent.
 *
 * @package PDFIB\AJAX
 * @since 1.0.0
 */

namespace PDFIB\AJAX;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Gestionnaire AJAX des données d'aperçu.
 */
class PreviewDataAjax {

	/**
	 * Enregistre les hooks AJAX.
	 *
	 * @return void
	 */
	public static function register(): void {
		$instance = new self();
		// L'add_action est dans le constructeur. Appel implicite.
		unset( $instance );
	}

	/**
	 * Initialise les hooks AJAX.
	 */
	public function __construct() {
		// Action AJAX pour récupérer les données d'une commande pour l'aperçu.
		\add_action( 'wp_ajax_pdfib_get_order_data_for_preview', array( $this, 'get_order_data_for_preview' ) );
	}

	/**
	 * Récupère toutes les données réelles d'une commande WooCommerce.
	 *
	 * Inspiré de OrderValueRetriever du plugin concurrent.
	 * Retourne les données dans le format RealOrderData.
	 *
	 * POST params:
	 * - nonce: Nonce de sécurité.
	 * - orderId: ID de la commande WC.
	 *
	 * @return void JSON response avec RealOrderData.
	 */
	public function get_order_data_for_preview() {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error(
					array(
						'message' => __( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ),
						'code'    => 403,
					)
				);
				return;
			}
			$order_id = $this->extract_and_validate_preview_request();
			$order    = \wc_get_order( $order_id );
			if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
				\wp_send_json_error(
					array(
						// translators: %d = ID de la commande WooCommerce.
						'message' => sprintf( __( 'Commande WooCommerce ID %d non trouvée', 'advanced-pdf-invoice-builder' ), $order_id ),
						'code'    => 404,
					)
				);
				return;
			}
			\wp_send_json_success( $this->build_real_order_data( $order ) );
		} catch ( Exception $e ) {
			\wp_send_json_error(
				array(
					'message'   => __( 'Erreur lors de la récupération des données: ', 'advanced-pdf-invoice-builder' ) . $e->getMessage(),
					'code'      => 500,
					'exception' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Valide les permissions, le nonce et l'orderId depuis GET/JSON body/POST.
	 *
	 * @return int
	 */
	private function extract_and_validate_preview_request(): int {
		$json_body = json_decode( pdfib_get_raw_input(), true );
		if ( is_array( $json_body ) ) {
			$json_body = map_deep( $json_body, 'sanitize_text_field' );
		}
		if ( isset( $GLOBALS['_GET']['nonce'] ) ) {
			$nonce = \sanitize_text_field( wp_unslash( $GLOBALS['_GET']['nonce'] ) );
		} elseif ( isset( $json_body['nonce'] ) ) {
			$nonce = \sanitize_text_field( $json_body['nonce'] );
		} else {
			$nonce = isset( $GLOBALS['_POST']['nonce'] ) ? \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ) ) : '';
		}
		if ( ! wp_verify_nonce( $nonce, 'pdfib_ajax' ) ) {
			\wp_send_json_error(
				array(
					'message' => __( 'Nonce invalide', 'advanced-pdf-invoice-builder' ),
					'code'    => 403,
				)
			);
			return 0;
		}
		if ( isset( $GLOBALS['_GET']['orderId'] ) ) {
			$order_id = \intval( wp_unslash( $GLOBALS['_GET']['orderId'] ) );
		} elseif ( isset( $json_body['orderId'] ) ) {
			$order_id = \intval( $json_body['orderId'] );
		} else {
			$order_id = isset( $GLOBALS['_POST']['orderId'] ) ? \intval( wp_unslash( $GLOBALS['_POST']['orderId'] ) ) : 0;
		}
		if ( empty( $order_id ) ) {
			\wp_send_json_error(
				array(
					'message' => __( 'Paramètre orderId manquant ou invalide', 'advanced-pdf-invoice-builder' ),
					'code'    => 400,
				)
			);
			return 0;
		}
		return $order_id;
	}

	/**
	 * Construit la structure RealOrderData depuis une commande WC.
	 *
	 * @param object $order L'objet commande WooCommerce.
	 * @return array Les données réelles formatées.
	 */
	private function build_real_order_data( object $order ): array {
		$products      = $this->build_order_products( $order );
		$order_created = $order->get_date_created();
		return array_merge(
			array(
				'orderId'     => strval( $order->get_id() ),
				'orderNumber' => $order->get_order_number(),
				'orderDate'   => $order_created ? $order_created->format( 'Y-m-d H:i:s' ) : '',
				'orderStatus' => $order->get_status(),
			),
			$this->build_order_customer_data( $order ),
			array(
				'products'     => $products,
				'productCount' => count( $products ),
			),
			$this->build_order_totals( $order ),
			array(
				'paymentMethod' => $order->get_payment_method_title(),
				'transactionId' => '' !== $order->get_transaction_id() ? $order->get_transaction_id() : 'N/A',
			),
			$this->build_company_info(),
			array(
				'timestamp' => time(),
				'isTest'    => false,
			)
		);
	}

	/**
	 * Construit la liste des produits de la commande.
	 *
	 * @param object $order Objet commande WooCommerce.
	 * @return array[] Liste des produits de la commande.
	 */
	private function build_order_products( object $order ): array {
		$products = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			$image_url  = $product->get_image_id()
				? wp_get_attachment_url( $product->get_image_id() )
				: wc_placeholder_img_src( 'woocommerce_gallery' );
			$products[] = array(
				'name'     => $item->get_name(),
				'sku'      => '' !== $product->get_sku() ? $product->get_sku() : 'N/A',
				'quantity' => $item->get_quantity(),
				'price'    => floatval( $item->get_total() / max( 1, $item->get_quantity() ) ),
				'total'    => floatval( $item->get_total() ),
				'image'    => $image_url,
			);
		}
		return $products;
	}

	/**
	 * Construit les donnees client.
	 *
	 * @param object $order Objet commande WooCommerce.
	 * @return array<string,mixed> Données client (adresse facturation).
	 */
	private function build_order_customer_data( object $order ): array {
		$addr1    = $order->get_billing_address_1();
		$addr2    = $order->get_billing_address_2();
		$city     = $order->get_billing_city();
		$postcode = $order->get_billing_postcode();
		$country  = $order->get_billing_country();
		$fn       = $order->get_billing_first_name();
		$ln       = $order->get_billing_last_name();
		return array(
			'customerName'         => trim( "{$fn} {$ln}" ),
			'customerFirstName'    => $fn,
			'customerLastName'     => $ln,
			'customerCompany'      => $order->get_billing_company(),
			'customerEmail'        => $order->get_billing_email(),
			'customerPhone'        => $order->get_billing_phone(),
			'customerAddress'      => $this->format_address(
				array(
					'address_1' => $addr1,
					'address_2' => $addr2,
					'city'      => $city,
					'postcode'  => $postcode,
					'country'   => $country,
				)
			),
			'customerAddressLine1' => $addr1,
			'customerAddressLine2' => $addr2,
			'customerCity'         => $city,
			'customerPostcode'     => $postcode,
			'customerCountry'      => $country,
		);
	}

	/**
	 * Construit les totaux de commande.
	 *
	 * @param object $order Objet commande WooCommerce.
	 * @return array<string,mixed> Totaux commande (subtotal, frais, remise, taxes).
	 */
	private function build_order_totals( object $order ): array {
		$shipping = floatval( $order->get_shipping_total() );
		$tax      = floatval( $order->get_total_tax() );
		$subtotal = floatval( $order->get_subtotal() );
		$total    = floatval( $order->get_total() );
		$fees     = array();
		foreach ( $order->get_fees() as $fee ) {
			$fees[] = array(
				'name'  => $fee->get_name(),
				'total' => floatval( $fee->get_total() ),
			);
		}
		$total_fees = array_sum( array_column( $fees, 'total' ) );
		$discount   = max( 0, $subtotal + $shipping + $tax + $total_fees - $total );
		return array(
			'subtotal'     => $subtotal,
			'shippingCost' => $shipping,
			'taxCost'      => $tax,
			'taxRate'      => ! empty( $subtotal ) ? ( $tax / $subtotal * 100 ) : 0,
			'discount'     => $discount,
			'total'        => $total,
			'fees'         => $fees,
			'totalFees'    => $total_fees,
		);
	}

	/**
	 * Construit les informations de societe.
	 *
	 * @return array<string,string> Informations société depuis les options WordPress.
	 */
	private function build_company_info(): array {
		return array(
			'companyName'               => pdfib_get_option( 'pdfib_company_name', 'Ma Société' ),
			'companyAddress'            => pdfib_get_option( 'pdfib_company_address', '' ),
			'companyPhone'              => pdfib_get_option( 'pdfib_company_phone_manual', '' ),
			'companyEmail'              => pdfib_get_option( 'pdfib_company_email', '' ),
			'companyWebsite'            => pdfib_get_option( 'pdfib_company_website', '' ),
			'companyTaxId'              => pdfib_get_option( 'pdfib_company_tax_id', '' ),
			'companyRegistrationNumber' => pdfib_get_option( 'pdfib_company_registration_number', '' ),
		);
	}

	/**
	 * Formate une adresse à partir de ses composants.
	 *
	 * @param array $address_parts Les parties de l'adresse.
	 * @return string L'adresse formatée.
	 */
	private function format_address( array $address_parts ): string {
		$address_lines = array();

		if ( ! empty( $address_parts['address_1'] ) ) {
			$address_lines[] = $address_parts['address_1'];
		}

		if ( ! empty( $address_parts['address_2'] ) ) {
			$address_lines[] = $address_parts['address_2'];
		}

		$city_line = array();
		if ( ! empty( $address_parts['postcode'] ) ) {
			$city_line[] = $address_parts['postcode'];
		}
		if ( ! empty( $address_parts['city'] ) ) {
			$city_line[] = $address_parts['city'];
		}
		if ( ! empty( $city_line ) ) {
			$address_lines[] = implode( ' ', $city_line );
		}

		if ( ! empty( $address_parts['country'] ) ) {
			$address_lines[] = $address_parts['country'];
		}

		return implode( "\n", $address_lines );
	}
}

// Initialiser la classe si ce n'est pas déjà fait.
if ( ! class_exists( 'PreviewDataAjax' ) ) {
	$pdfib_preview_data_ajax_register = new PreviewDataAjax();
	unset( $pdfib_preview_data_ajax_register );
}
