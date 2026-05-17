<?php
/**
 * Advanced PDF Invoice Builder - Variable Mapper
 *
 * Maps dynamic variables to WooCommerce order data
 *
 * @package PDF_Builder
 * @version 1.0
 * @since   5.4
 */

namespace PDFIB\Managers;

// Importer les classes nécessaires.
use DateTime;
use WC_Order;
use WC_DateTime;


defined( 'ABSPATH' ) || exit;

/**
 * Class PDFBuilderVariableMapper
 *
 * Handles mapping of dynamic variables to WooCommerce order data
 */
class PDFBuilderVariableMapper {


	// Configuration du formatage des nombres.
	const DEFAULT_DECIMALS           = 2;
	const DEFAULT_DECIMAL_SEPARATOR  = ',';
	const DEFAULT_THOUSAND_SEPARATOR = ' ';
	const ZERO_AMOUNT                = '$0.00';

	/**
	 * WooCommerce order object
	 *
	 * @var object|null
	 */
	private $order;

	/**
	 * Constructor
	 *
	 * @param object|null $order WooCommerce order object.
	 */
	public function __construct( $order ) {
		$this->order = $order;
	}

	/**
	 * Get all available variables with their values
	 *
	 * @return array Array of variable => value mappings
	 */
	public function get_all_variables() {
		return array_merge(
			$this->get_order_variables(),
			$this->get_customer_variables(),
			$this->get_address_variables(),
			$this->get_financial_variables(),
			$this->get_payment_variables(),
			$this->get_product_variables(),
			$this->get_company_variables()
		);
	}

	/**
	 * Get order-related variables
	 *
	 * @return array
	 */
	private function get_order_variables() {
		if ( ! $this->order ) {
			return array(
				'order_number'        => '',
				'order_date'          => '',
				'order_date_time'     => '',
				'order_date_modified' => '',
				'order_total'         => '',
				'order_status'        => '',
				'currency'            => '',
			);
		}

		return array(
			'order_number'        => $this->order->get_order_number(),
			'order_date'          => $this->format_date( $this->order->get_date_created() ),
			'order_date_time'     => $this->format_datetime( $this->order->get_date_created() ),
			'order_date_modified' => $this->format_date( $this->order->get_date_modified() ),
			'order_total'         => $this->format_currency( (float) $this->order->get_total() ),
			'order_status'        => $this->get_order_status_label( $this->order->get_status() ),
			'currency'            => $this->order->get_currency(),
		);
	}

	/**
	 * Get customer-related variables
	 *
	 * @return array
	 */
	private function get_customer_variables() {
		if ( ! $this->order ) {
			return array(
				'customer_name'       => '',
				'customer_first_name' => '',
				'customer_last_name'  => '',
				'customer_email'      => '',
				'customer_phone'      => '',
				'customer_note'       => '',
			);
		}

		return array(
			'customer_name'       => trim( $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name() ),
			'customer_first_name' => $this->order->get_billing_first_name(),
			'customer_last_name'  => $this->order->get_billing_last_name(),
			'customer_email'      => $this->order->get_billing_email(),
			'customer_phone'      => $this->order->get_billing_phone(),
			'customer_note'       => $this->order->get_customer_note(),
		);
	}

	/**
	 * Get address-related variables
	 *
	 * @return array
	 */
	private function get_address_variables() {
		if ( ! $this->order ) {
			return array(
				'billing_address'    => '',
				'shipping_address'   => '',
				'billing_first_name' => '',
				'billing_last_name'  => '',
				'billing_company'    => '',
				'billing_address_1'  => '',
				'billing_address_2'  => '',
				'billing_city'       => '',
				'billing_postcode'   => '',
				'billing_country'    => '',
				'billing_state'      => '',
			);
		}

		return array(
			'billing_address'    => $this->format_address( $this->order, 'billing' ),
			'shipping_address'   => $this->format_address( $this->order, 'shipping' ),
			'billing_first_name' => $this->order->get_billing_first_name(),
			'billing_last_name'  => $this->order->get_billing_last_name(),
			'billing_company'    => $this->order->get_billing_company(),
			'billing_address_1'  => $this->order->get_billing_address_1(),
			'billing_address_2'  => $this->order->get_billing_address_2(),
			'billing_city'       => $this->order->get_billing_city(),
			'billing_postcode'   => $this->order->get_billing_postcode(),
			'billing_country'    => $this->get_country_name( $this->order->get_billing_country() ),
			'billing_state'      => $this->order->get_billing_state(),
		);
	}

	/**
	 * Get financial variables
	 *
	 * @return array
	 */
	private function get_financial_variables() {
		if ( ! $this->order ) {
			return array(
				'subtotal'        => '',
				'tax_amount'      => '',
				'shipping_amount' => '',
				'discount_amount' => '',
				'total_excl_tax'  => '',
			);
		}

		return array(
			'subtotal'        => $this->format_currency( (float) $this->calculate_subtotal_with_fees() ),
			'tax_amount'      => $this->format_currency( (float) $this->order->get_total_tax() ),
			'shipping_amount' => $this->format_currency( (float) $this->order->get_shipping_total() ),
			'discount_amount' => $this->format_currency( (float) $this->order->get_discount_total() ),
			'total_excl_tax'  => $this->format_currency( (float) $this->order->get_total() - (float) $this->order->get_total_tax() ),
		);
	}

	/**
	 * Calculate subtotal including fees (treats fees as products)
	 *
	 * @return float
	 */
	private function calculate_subtotal_with_fees() {
		if ( ! $this->order ) {
			return 0;
		}

		$subtotal = $this->order->get_subtotal();

		// Add fees to subtotal (treating them as products).
		foreach ( $this->order->get_fees() as $fee ) {
			$subtotal += $fee->get_total();
		}

		return $subtotal;
	}

	/**
	 * Get payment-related variables
	 *
	 * @return array
	 */
	private function get_payment_variables() {
		if ( ! $this->order ) {
			return array(
				'payment_method'      => '',
				'payment_method_code' => '',
				'transaction_id'      => '',
			);
		}

		return array(
			'payment_method'      => $this->order->get_payment_method_title(),
			'payment_method_code' => $this->order->get_payment_method(),
			'transaction_id'      => $this->order->get_transaction_id(),
		);
	}




	/**
	 * Get company-related variables
	 *
	 * @return array
	 */
	private function get_company_variables() {
		// Get company info from WooCommerce settings.
		$company_name     = get_option( 'woocommerce_store_name', '' );
		$company_address  = get_option( 'woocommerce_store_address', '' );
		$company_city     = get_option( 'woocommerce_store_city', '' );
		$company_postcode = get_option( 'woocommerce_store_postcode', '' );
		$company_country  = get_option( 'woocommerce_default_country', '' );

		$full_address = array_filter(
			array(
				$company_address,
				$company_city,
				$company_postcode,
				$this->get_country_name( $company_country ),
			)
		);

		return array(
			'company_name'    => $company_name,
			'company_address' => implode( ', ', $full_address ),
			'company_phone'   => pdfib_get_option( 'pdfib_company_phone_manual', '' ),
			'company_email'   => get_option( 'admin_email', '' ),
		);
	}

	/**
	 * Format currency according to WooCommerce settings
	 *
	 * @param  float $amount Amount to format.
	 * @return string Formatted currency
	 */
	private function format_currency( float $amount ) {
		// Use a robust local formatter to avoid relying on environment-specific wc_price.
		$value = floatval( $amount );

		// Default formatting similar to tests helper.
		$decimals           = self::DEFAULT_DECIMALS;
		$decimal_separator  = self::DEFAULT_DECIMAL_SEPARATOR;
		$thousand_separator = self::DEFAULT_THOUSAND_SEPARATOR;

		// Try to obtain currency from order if available.
		$currency = '';
		if ( $this->order && method_exists( $this->order, 'get_currency' ) ) {
			try {
				$currency = $this->order->get_currency();
			} catch ( \Throwable $e ) {
				$currency = '';
			}
		}

		$formatted = number_format( $value, $decimals, $decimal_separator, $thousand_separator );
		if ( $currency ) {
			$formatted .= ' ' . $currency;
		}

		return $formatted;
	}

	/**
	 * Format date according to WordPress settings
	 *
	 * @param  DateTime|string $date Date to format.
	 * @return string Formatted date
	 */
	private function format_date( $date ) {
		if ( empty( $date ) ) {
			return '';
		}
		if ( did_action( 'plugins_loaded' ) && function_exists( 'pdfib_is_woocommerce_active' ) && pdfib_is_woocommerce_active() && function_exists( 'is_a' ) && is_a( $date, 'WC_DateTime' ) ) {
			return $date->date_i18n( get_option( 'date_format' ) );
		}
		if ( $date instanceof DateTime ) {
			return date_i18n( get_option( 'date_format' ), $date->getTimestamp() );
		}
		return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
	}

	/**
	 * Format datetime according to WordPress settings
	 *
	 * @param  DateTime|string $date DateTime to format.
	 * @return string Formatted datetime
	 */
	private function format_datetime( $date ) {
		if ( empty( $date ) ) {
			return '';
		}
		if ( did_action( 'plugins_loaded' ) && function_exists( 'pdfib_is_woocommerce_active' ) && pdfib_is_woocommerce_active() && function_exists( 'is_a' ) && is_a( $date, 'WC_DateTime' ) ) {
			return $date->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		}
		if ( $date instanceof DateTime ) {
			return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date->getTimestamp() );
		}
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ) );
	}

	/**
	 * Get order status label
	 *
	 * @param  string $status Order status.
	 * @return string Status label
	 */
	private function get_order_status_label( $status ) {
		$statuses = \function_exists( 'wc_get_order_statuses' ) ? \wc_get_order_statuses() : array();
		return $this->find_status_in_list( $status, $statuses )
			?? $this->find_status_case_insensitive( $status, $statuses )
			?? $this->find_status_in_fallbacks( $status )
			?? $status;
	}

	/**
	 * Finds an order status label using the exact status key.
	 *
	 * @param string $status   Order status key.
	 * @param array  $statuses Available status labels.
	 * @return string|null
	 */
	private function find_status_in_list( string $status, array $statuses ): ?string {
		if ( isset( $statuses[ $status ] ) ) {
			return $statuses[ $status ];
		}
		if ( strpos( $status, 'wc-' ) === 0 ) {
			$k = substr( $status, 3 );
			if ( isset( $statuses[ $k ] ) ) {
				return $statuses[ $k ];
			}
		}
		return null;
	}

	/**
	 * Finds an order status label using case-insensitive matching.
	 *
	 * @param string $status   Order status key.
	 * @param array  $statuses Available status labels.
	 * @return string|null
	 */
	private function find_status_case_insensitive( string $status, array $statuses ): ?string {
		foreach ( $statuses as $k => $v ) {
			if ( strcasecmp( $k, $status ) === 0 || strcasecmp( $v, $status ) === 0 ) {
				return $v;
			}
		}
		return null;
	}

	/**
	 * Finds an order status label using local fallback translations.
	 *
	 * @param string $status Order status key.
	 * @return string|null
	 */
	private function find_status_in_fallbacks( string $status ): ?string {
		$fallbacks = array(
			'completed'  => 'Terminée',
			'pending'    => 'En attente',
			'processing' => 'En cours',
			'on-hold'    => 'En attente',
			'cancelled'  => 'Annulée',
			'refunded'   => 'Remboursée',
			'failed'     => 'Échouée',
		);
		$s         = strtolower( $status );
		return $fallbacks[ $s ] ?? null;
	}

	/**
	 * Get country name from country code
	 *
	 * @param  string $country_code Country code.
	 * @return string Country name
	 */
	private function get_country_name( $country_code ) {
		if ( ( ! function_exists( 'pdfib_is_woocommerce_active' ) || ! pdfib_is_woocommerce_active() ) || ! $country_code ) {
			return $country_code;
		}

		// Use get_option instead of WC()->countries to avoid autoloading.
		$countries = get_option( 'woocommerce_countries', array() );
		if ( empty( $countries ) ) {
			$countries = get_option( 'woocommerce_allowed_countries', array() );
		}
		return isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : $country_code;
	}

	/**
	 * Replace variables in text
	 *
	 * @param  string $text Text containing variables.
	 * @return string Text with variables replaced
	 */
	public function replace_variables( $text ) {
		$variables = $this->get_all_variables();

		foreach ( $variables as $key => $value ) {
			$text = str_replace( '{{' . $key . '}}', $value, $text );
		}

		return $text;
	}

	/**
	 * Get fallback values for missing data
	 *
	 * @return array Array of fallback values
	 */
	public static function get_fallbacks() {
		$fallbacks = array_merge(
			self::get_core_fallbacks(),
			self::get_address_product_fallbacks()
		);
		if ( did_action( 'plugins_loaded' ) && function_exists( 'pdfib_is_woocommerce_active' ) && pdfib_is_woocommerce_active() && function_exists( 'get_woocommerce_currency' ) ) {
			$currency                     = get_woocommerce_currency();
			$fallbacks['order_total']     = self::ZERO_AMOUNT;
			$fallbacks['currency']        = $currency;
			$fallbacks['subtotal']        = self::ZERO_AMOUNT;
			$fallbacks['tax_amount']      = self::ZERO_AMOUNT;
			$fallbacks['shipping_amount'] = self::ZERO_AMOUNT;
			$fallbacks['discount_amount'] = self::ZERO_AMOUNT;
			$fallbacks['total_excl_tax']  = self::ZERO_AMOUNT;
		}
		return $fallbacks;
	}

	/**
	 * Fallbacks de base: order + customer + company.
	 *
	 * @return array
	 */
	private static function get_core_fallbacks(): array {
		return array(
			'order_number'        => 'N/A',
			'order_date'          => date_i18n( get_option( 'date_format' ) ),
			'order_date_time'     => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'order_date_modified' => date_i18n( get_option( 'date_format' ) ),
			'order_total'         => '0',
			'order_status'        => __( 'Unknown', 'advanced-pdf-invoice-builder' ),
			'currency'            => 'USD',
			'customer_name'       => __( 'Customer', 'advanced-pdf-invoice-builder' ),
			'customer_first_name' => '',
			'customer_last_name'  => '',
			'customer_email'      => '',
			'customer_phone'      => '',
			'customer_note'       => '',
			'subtotal'            => '0',
			'tax_amount'          => '0',
			'shipping_amount'     => '0',
			'discount_amount'     => '0',
			'total_excl_tax'      => '0',
			'payment_method'      => '',
			'payment_method_code' => '',
			'transaction_id'      => '',
			'company_name'        => get_option( 'woocommerce_store_name', '' ),
			'company_address'     => '',
			'company_phone'       => '',
			'company_email'       => get_option( 'admin_email', '' ),
		);
	}

	/**
	 * Fallbacks: adresses + produits.
	 *
	 * @return array
	 */
	private static function get_address_product_fallbacks(): array {
		return array(
			'billing_address'    => '',
			'shipping_address'   => '',
			'billing_first_name' => '',
			'billing_last_name'  => '',
			'billing_company'    => '',
			'billing_address_1'  => '',
			'billing_address_2'  => '',
			'billing_city'       => '',
			'billing_postcode'   => '',
			'billing_country'    => '',
			'billing_state'      => '',
			'product_name'       => '',
			'product_qty'        => '',
			'product_price'      => '',
			'product_total'      => '',
			'product_sku'        => '',
			'products_list'      => '',
		);
	}

	/**
	 * Formate une adresse manuellement pour éviter l'autoloading WooCommerce.
	 *
	 * @param mixed  $order Commande WooCommerce.
	 * @param string $type  Type d'adresse.
	 * @return string
	 */
	private function format_address( mixed $order, string $type = 'billing' ) {
		$address_parts = array();
		$is_billing    = 'billing' === $type;

		$company = $is_billing ? $order->get_billing_company() : $order->get_shipping_company();
		if ( ! empty( $company ) ) {
			$address_parts[] = $company;
		}

		$first_name = $is_billing ? $order->get_billing_first_name() : $order->get_shipping_first_name();
		$last_name  = $is_billing ? $order->get_billing_last_name() : $order->get_shipping_last_name();
		if ( ! empty( $first_name ) || ! empty( $last_name ) ) {
			$address_parts[] = trim( $first_name . ' ' . $last_name );
		}

		$address_1 = $is_billing ? $order->get_billing_address_1() : $order->get_shipping_address_1();
		if ( ! empty( $address_1 ) ) {
			$address_parts[] = $address_1;
		}

		$address_2 = $is_billing ? $order->get_billing_address_2() : $order->get_shipping_address_2();
		if ( ! empty( $address_2 ) ) {
			$address_parts[] = $address_2;
		}

		$city      = $is_billing ? $order->get_billing_city() : $order->get_shipping_city();
		$postcode  = $is_billing ? $order->get_billing_postcode() : $order->get_shipping_postcode();
		$city_line = trim( $city . ' ' . $postcode );
		if ( ! empty( $city_line ) ) {
			$address_parts[] = $city_line;
		}

		$country = $is_billing ? $this->get_country_name( $order->get_billing_country() ) : $this->get_country_name( $order->get_shipping_country() );
		if ( ! empty( $country ) ) {
			$address_parts[] = $country;
		}

		return implode( "\n", $address_parts );
	}


	/**
	 * Get product-related variables
	 *
	 * @return array
	 */
	private function get_product_variables() {
		if ( ! $this->order ) {
			return array(
				'product_name'  => '',
				'product_qty'   => '',
				'product_price' => '',
				'product_total' => '',
				'product_sku'   => '',
				'products_list' => '',
			);
		}

		$items = $this->order->get_items();
		$fees  = $this->order->get_fees();

		// Combiner les produits et les frais.
		$all_items  = array_merge( $items, $fees );
		$first_item = reset( $items ); // Garder le premier produit pour les variables individuelles.

		$products_list = array();
		foreach ( $all_items as $item ) {
			// Traiter tous les types d'items (produits et frais).
			if ( method_exists( $item, 'get_name' ) && method_exists( $item, 'get_total' ) ) {
				$name  = $item->get_name();
				$total = $item->get_total();

				// Pour les produits, récupérer la quantité, pour les frais utiliser 1.
				$quantity = method_exists( $item, 'get_quantity' ) ? $item->get_quantity() : 1;

				$products_list[] = sprintf(
					'%s (x%d) - %s',
					$name,
					$quantity,
					$this->format_currency( $total )
				);
			}
		}

		return array(
			'product_name'  => $first_item ? $first_item->get_name() : '',
			'product_qty'   => $first_item ? $first_item->get_quantity() : '',
			'product_price' => $first_item && $first_item->get_product() ? $this->format_currency( $first_item->get_product()->get_price() ) : '',
			'product_total' => $first_item ? $this->format_currency( $first_item->get_total() ) : '',
			'product_sku'   => $first_item && $first_item->get_product() ? $first_item->get_product()->get_sku() : '',
			'products_list' => implode( "\n", $products_list ),
		);
	}
}
