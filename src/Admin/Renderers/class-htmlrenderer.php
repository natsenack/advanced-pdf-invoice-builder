<?php
/**
 * Advanced PDF Invoice Builder - HTML Renderer.
 *
 * @package PDFIB\Admin\Renderers
 */

namespace PDFIB\Admin\Renderers;

use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Classe responsable du rendu HTML des templates PDF.
 */
class HTMLRenderer {

	const STYLE_ALIGN_CENTER = ' text-align: center;">';
	const COLOR_WHITE        = '#ffffff';
	const COLOR_DARK         = '#1e293b';

	/**
	 * Instance de la classe principale.
	 *
	 * @var mixed
	 */
	private mixed $admin;

	/**
	 * Delegue pour la construction du tableau des produits.
	 *
	 * @var OrderProductTableRenderer
	 */
	private OrderProductTableRenderer $table_renderer;

	/**
	 * Constructeur.
	 *
	 * @param mixed $admin Classe principale du plugin.
	 */
	public function __construct( mixed $admin ) {
		$this->admin          = $admin;
		$this->table_renderer = new OrderProductTableRenderer();
	}

	/**
	 * Remplace les variables WooCommerce dans le contenu.
	 *
	 * @param string $content Contenu HTML brut.
	 * @param object $order Commande WooCommerce.
	 * @return string
	 */
	public function replace_order_variables( string $content, object $order ): string {
		$billing  = $this->format_address( $order, 'billing' );
		$shipping = $this->format_address( $order, 'shipping' );

		$order_status = $order->get_status();
		$doc_type     = $this->detect_document_type( $order_status );
		$doc_label    = $this->get_document_type_label( $doc_type );

		$vars = $this->build_order_variable_values( $order, $billing, $shipping, $doc_type, $doc_label );

		foreach ( array( array( '{', '}' ), array( '{{', '}}' ), array( '[', ']' ) ) as list( $pre, $suf ) ) {
			$keys    = array_map( fn( $k ) => $pre . $k . $suf, array_keys( $vars ) );
			$content = str_replace( $keys, array_values( $vars ), $content );
		}

		// Balise speciale uniquement en accolades simples.
		$content = str_replace( '{order_items_table}', $this->generate_order_products_table( $order, 'default' ), $content );

		return $content;
	}

	/**
	 * Detecte le type de document selon le statut de commande.
	 *
	 * @param string $order_status Statut WooCommerce.
	 * @return string
	 */
	private function detect_document_type( string $order_status ): string {
		$normalized = strtolower( $order_status );
		if ( in_array( $normalized, array( 'pending', 'processing', 'on-hold' ), true ) ) {
			return 'devis';
		}

		return 'facture';
	}

	/**
	 * Retourne le label du type de document.
	 *
	 * @param string $doc_type Type de document.
	 * @return string
	 */
	private function get_document_type_label( string $doc_type ): string {
		return 'devis' === $doc_type ? __( 'Devis', 'advanced-pdf-invoice-builder' ) : __( 'Facture', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Construit la map des valeurs de variables de commande.
	 *
	 * @param mixed  $order Commande WooCommerce.
	 * @param string $billing Adresse de facturation formatee.
	 * @param string $shipping Adresse de livraison formatee.
	 * @param string $doc_type Type de document.
	 * @param string $doc_label Label du type de document.
	 * @return array<string,mixed>
	 */
	private function build_order_variable_values( $order, string $billing, string $shipping, string $doc_type, string $doc_label ): array {
		$price_formatter = fn( $value ) => function_exists( 'wc_price' ) ? wc_price( (float) $value ) : '$' . number_format( (float) $value, 2 );
		$order_date      = $order->get_date_created();
		$order_date_only = $order_date ? $order_date->date( 'd/m/Y' ) : gmdate( 'd/m/Y' );
		$order_date_time = $order_date ? $order_date->date( 'd/m/Y H:i:s' ) : gmdate( 'd/m/Y H:i:s' );

		$billing_address_fallback  = empty( $billing ) ? __( 'Adresse de facturation non disponible', 'advanced-pdf-invoice-builder' ) : $billing;
		$shipping_address_fallback = empty( $shipping ) ? __( 'Adresse de livraison non disponible', 'advanced-pdf-invoice-builder' ) : $shipping;

		return array(
			'order_id'                 => $order->get_id(),
			'order_number'             => $order->get_order_number(),
			'order_date'               => $order_date_only,
			'order_date_time'          => $order_date_time,
			'customer_name'            => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'customer_first_name'      => $order->get_billing_first_name(),
			'customer_last_name'       => $order->get_billing_last_name(),
			'customer_email'           => $order->get_billing_email(),
			'customer_phone'           => $order->get_billing_phone(),
			'billing_company'          => $order->get_billing_company(),
			'billing_first_name'       => $order->get_billing_first_name(),
			'billing_last_name'        => $order->get_billing_last_name(),
			'billing_address_1'        => $order->get_billing_address_1(),
			'billing_address_2'        => $order->get_billing_address_2(),
			'billing_city'             => $order->get_billing_city(),
			'billing_state'            => $order->get_billing_state(),
			'billing_postcode'         => $order->get_billing_postcode(),
			'billing_country'          => $order->get_billing_country(),
			'billing_address'          => $billing_address_fallback,
			'complete_customer_info'   => $this->format_complete_customer_info( $order ),
			'complete_billing_address' => $billing_address_fallback,
			'shipping_first_name'      => $order->get_shipping_first_name(),
			'shipping_last_name'       => $order->get_shipping_last_name(),
			'shipping_company'         => $order->get_shipping_company(),
			'shipping_address_1'       => $order->get_shipping_address_1(),
			'shipping_address_2'       => $order->get_shipping_address_2(),
			'shipping_city'            => $order->get_shipping_city(),
			'shipping_state'           => $order->get_shipping_state(),
			'shipping_postcode'        => $order->get_shipping_postcode(),
			'shipping_country'         => $order->get_shipping_country(),
			'shipping_address'         => $shipping_address_fallback,
			'total'                    => $price_formatter( $order->get_total() ),
			'subtotal'                 => $price_formatter( $order->get_subtotal() ),
			'tax'                      => $price_formatter( $order->get_total_tax() ),
			'shipping_total'           => $price_formatter( $order->get_shipping_total() ),
			'discount_total'           => $price_formatter( $order->get_discount_total() ),
			'payment_method'           => $order->get_payment_method_title(),
			'order_status'             => function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $order->get_status() ) : $order->get_status(),
			'currency'                 => $order->get_currency(),
			'document_type'            => $doc_type,
			'document_type_label'      => $doc_label,
		);
	}

	/**
	 * Formate les informations completes du client.
	 *
	 * @param object $order Commande WooCommerce.
	 * @return string
	 */
	public function format_complete_customer_info( object $order ): string {
		$info = array();

		// Nom complet.
		$full_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		if ( ! empty( $full_name ) ) {
			$info[] = $full_name;
		}

		// Societe.
		$company = $order->get_billing_company();
		if ( ! empty( $company ) ) {
			$info[] = $company;
		}

		// Adresse complete.
		$billing_address = $this->format_address( $order, 'billing' );
		if ( ! empty( $billing_address ) ) {
			$info[] = $billing_address;
		}

		// Email.
		$email = $order->get_billing_email();
		if ( ! empty( $email ) ) {
			$info[] = 'Email: ' . $email;
		}

		// Telephone.
		$phone = $order->get_billing_phone();
		if ( ! empty( $phone ) ) {
			$info[] = __( 'Telephone: ', 'advanced-pdf-invoice-builder' ) . $phone;
		}

		return implode( "\n", $info );
	}

	/**
	 * Formate les informations de societe.
	 *
	 * @return string
	 */
	public function format_complete_company_info(): string {
		$company_info = pdfib_get_option( 'pdfib_company_info', '' );
		if ( ! empty( $company_info ) ) {
			return $company_info;
		}

		$parts = array();

		$company_name = \get_bloginfo( 'name' );
		if ( ! empty( $company_name ) ) {
			$parts[] = $company_name;
		}

		$address_parts = $this->build_store_address_parts();
		if ( ! empty( $address_parts ) ) {
			$parts = array_merge( $parts, $address_parts );
		}

		$email = \get_bloginfo( 'admin_email' );
		if ( ! empty( $email ) ) {
			$parts[] = 'Email: ' . $email;
		}

		if ( ! empty( $parts ) ) {
			return implode( "\n", $parts );
		}

		return __( "Votre Societe SARL\n123 Rue de l'Entreprise\n75001 Paris\nFrance\nTel: 01 23 45 67 89\nEmail: contact@votresociete.com", 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Construit les parties d'adresse du magasin WooCommerce.
	 *
	 * @return string[]
	 */
	private function build_store_address_parts(): array {
		$address_parts = array();
		$address1      = \get_option( 'woocommerce_store_address' );
		$address2      = \get_option( 'woocommerce_store_address_2' );
		$city          = \get_option( 'woocommerce_store_city' );
		$postcode      = \get_option( 'woocommerce_store_postcode' );
		$country       = \get_option( 'woocommerce_store_country' );

		if ( ! empty( $address1 ) ) {
			$address_parts[] = $address1;
		}

		if ( ! empty( $address2 ) ) {
			$address_parts[] = $address2;
		}

		$city_line = array();
		if ( ! empty( $postcode ) ) {
			$city_line[] = $postcode;
		}

		if ( ! empty( $city ) ) {
			$city_line[] = $city;
		}

		if ( ! empty( $city_line ) ) {
			$address_parts[] = implode( ' ', $city_line );
		}

		if ( ! empty( $country ) ) {
			$wc              = function_exists( 'WC' ) ? \WC() : null;
			$countries       = $wc ? $wc->countries->get_countries() : array();
			$address_parts[] = isset( $countries[ $country ] ) ? $countries[ $country ] : $country;
		}

		return $address_parts;
	}

	/**
	 * Genere le HTML pour une commande.
	 *
	 * @param object $order Commande WooCommerce.
	 * @param mixed  $template_data Donnees du template.
	 * @return mixed
	 */
	public function generate_order_html( object $order, mixed $template_data ): mixed {
		return $this->admin->generate_unified_html( $template_data, $order );
	}

	/**
	 * Genere le HTML a partir d'un template sans commande.
	 *
	 * @param mixed $template Donnees du template.
	 * @return mixed
	 */
	public function generate_html_from_template_data( mixed $template ): mixed {
		return $this->admin->generate_unified_html( $template, null );
	}

	/**
	 * Remplace les variables WooCommerce dans une structure imbriquee.
	 *
	 * @param mixed $template_data Structure de template.
	 * @param mixed $woocommerce_data Variables a injecter.
	 * @return mixed
	 */
	public function replace_woocommerce_variables( mixed $template_data, mixed $woocommerce_data ): mixed {
		$processed_data = $template_data;

		// Fonction recursive pour remplacer les variables dans toutes les profondeurs.
		$replace_vars = function ( $data ) use ( $woocommerce_data, &$replace_vars ) {
			if ( is_array( $data ) ) {
				$result = array();
				foreach ( $data as $key => $value ) {
					$result[ $key ] = $replace_vars( $value );
				}

				return $result;
			}

			if ( is_string( $data ) ) {
				// Remplacer les variables du type {order_number}, {customer_name}, etc.
				$replaced = $data;
				foreach ( $woocommerce_data as $var => $value ) {
					$replaced = str_replace( '{' . $var . '}', $value, $replaced );
				}

				return $replaced;
			}

			return $data;
		};

		return $replace_vars( $processed_data );
	}

	/**
	 * Formate une adresse sans declencher des chargements inutiles.
	 *
	 * @param object $order Commande WooCommerce.
	 * @param string $type Type d'adresse: billing ou shipping.
	 * @return string
	 */
	private function format_address( object $order, string $type = 'billing' ): string {
		$is_billing    = 'billing' === $type;
		$address_parts = array();

		$company = $is_billing ? $order->get_billing_company() : $order->get_shipping_company();
		if ( ! empty( $company ) ) {
			$address_parts[] = $company;
		}

		$full_name = trim( ( $is_billing ? $order->get_billing_first_name() : $order->get_shipping_first_name() ) . ' ' . ( $is_billing ? $order->get_billing_last_name() : $order->get_shipping_last_name() ) );
		if ( ! empty( $full_name ) ) {
			$address_parts[] = $full_name;
		}

		$address_1 = $is_billing ? $order->get_billing_address_1() : $order->get_shipping_address_1();
		if ( ! empty( $address_1 ) ) {
			$address_parts[] = $address_1;
		}

		$address_2 = $is_billing ? $order->get_billing_address_2() : $order->get_shipping_address_2();
		if ( ! empty( $address_2 ) ) {
			$address_parts[] = $address_2;
		}

		$city_line = trim( ( $is_billing ? $order->get_billing_city() : $order->get_shipping_city() ) . ' ' . ( $is_billing ? $order->get_billing_postcode() : $order->get_shipping_postcode() ) );
		if ( ! empty( $city_line ) ) {
			$address_parts[] = $city_line;
		}

		$country = $this->get_country_name( $is_billing ? $order->get_billing_country() : $order->get_shipping_country() );
		if ( ! empty( $country ) ) {
			$address_parts[] = $country;
		}

		return implode( "\n", $address_parts );
	}

	/**
	 * Retourne le nom du pays depuis son code.
	 *
	 * @param string $country_code Code pays ISO.
	 * @return string
	 */
	private function get_country_name( string $country_code ): string {
		if ( ! defined( 'WC_VERSION' ) || ! $country_code ) {
			return $country_code;
		}

		// Utiliser get_option au lieu de WC()->countries pour eviter l'autoloading.
		$countries = \get_option( 'woocommerce_countries', array() );
		if ( empty( $countries ) ) {
			$countries = \get_option( 'woocommerce_allowed_countries', array() );
		}

		return isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : $country_code;
	}

	/**
	 * Genere le HTML unifie pour un template.
	 *
	 * @param mixed $template Donnees du template.
	 * @param mixed $order Commande optionnelle.
	 * @return mixed
	 */
	public function generate_unified_html( mixed $template, mixed $order = null ): mixed {
		// Cette methode restera deleguee a la classe principale.
		return $this->admin->generate_unified_html( $template, $order );
	}

	/**
	 * Genere le tableau des produits de la commande.
	 *
	 * @param object $order       Commande WooCommerce.
	 * @param string $table_style Style du tableau.
	 * @param mixed  $element     Element de configuration optionnel.
	 * @return string
	 */
	public function generate_order_products_table( object $order, string $table_style = 'default', mixed $element = null ): string {
		return $this->table_renderer->generate_order_products_table( $order, $table_style, $element );
	}
}
