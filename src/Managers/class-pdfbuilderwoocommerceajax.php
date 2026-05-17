<?php
/**
 * Advanced PDF Invoice Builder WooCommerce AJAX handlers.
 *
 * @package PDFIB
 */

namespace PDFIB\Managers;

use PDFIB\Exception\PdfBuilderException;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce canvas/order AJAX handlers.
 * Extracted from PdfBuilderWooCommerceIntegration to satisfy Sonar S1448.
 */
class PdfBuilderWooCommerceAjax {

	/**
	 * Helper canvas pour les opérations sur les éléments.
	 *
	 * @var PdfBuilderCanvasHelper
	 */
	private PdfBuilderCanvasHelper $canvas_helper;

	/**
	 * Helper order data pour les données de commande.
	 *
	 * @var PdfBuilderOrderDataHelper
	 */
	private PdfBuilderOrderDataHelper $order_helper;

	/**
	 * Constructeur.
	 *
	 * @param PdfBuilderCanvasHelper    $canvas_helper Helper canvas.
	 * @param PdfBuilderOrderDataHelper $order_helper  Helper order data.
	 */
	public function __construct( PdfBuilderCanvasHelper $canvas_helper, PdfBuilderOrderDataHelper $order_helper ) {
		$this->canvas_helper = $canvas_helper;
		$this->order_helper  = $order_helper;
	}

	/**
	 * Vérifie les capacités WooCommerce minimales pour les endpoints AJAX.
	 *
	 * @param string $error_message Message d'erreur en cas de refus.
	 * @throws PdfBuilderException Si l'utilisateur n'a pas les capacités requises.
	 */
	private function assert_woo_commerce_capabilities( string $error_message ): void {
		if ( ! \current_user_can( 'manage_woocommerce' ) && ! \current_user_can( 'edit_shop_orders' ) ) {
			throw new PdfBuilderException( esc_html( $error_message ), 403 );
		}
	}

	/**
	 * Vérifie le nonce AJAX commun.
	 *
	 * @param string $nonce_action Action du nonce.
	 * @param string $nonce_field  Nom du champ nonce.
	 * @throws PdfBuilderException Si le nonce est invalide.
	 */
	private function assert_ajax_nonce( string $nonce_action = 'pdfib_ajax', string $nonce_field = 'nonce' ): void {
		if ( ! check_ajax_referer( $nonce_action, $nonce_field, false ) ) {
			throw new PdfBuilderException( esc_html__( 'Sécurité: Nonce invalide', 'advanced-pdf-invoice-builder' ), 403 );
		}
	}

	/**
	 * Vérifie capacités + nonce pour les endpoints WooCommerce protégés.
	 *
	 * @param string $error_message Message d'erreur en cas de refus.
	 * @throws PdfBuilderException Si les permissions ou le nonce sont invalides.
	 */
	private function assert_woo_commerce_access_with_nonce( string $error_message ): void {
		$this->assert_woo_commerce_capabilities( $error_message );
		$this->assert_ajax_nonce();
	}

	/**
	 * AJAX handler pour sauvegarder le canvas d'une commande.
	 *
	 * @throws PdfBuilderException Si la sauvegarde échoue.
	 */
	public function ajax_save_order_canvas(): void {
		try {
			check_ajax_referer( 'pdfib_ajax', 'nonce' );
			[$order_id, $elements] = $this->parse_save_canvas_request();
			$save_result           = \update_post_meta( $order_id, '_pdfib_canvas_data', $elements );
			if ( false === $save_result ) {
				throw new PdfBuilderException( esc_html__( 'Erreur lors de la sauvegarde des données canvas', 'advanced-pdf-invoice-builder' ), 500 );
			}
			wp_send_json_success(
				array(
					'message'        => __( 'Canvas sauvegardé avec succès', 'advanced-pdf-invoice-builder' ),
					'order_id'       => $order_id,
					'elements_count' => count( $elements ),
				)
			);
		} catch ( PdfBuilderException $e ) {
			\wp_send_json_error( array( 'message' => $e->getMessage() ), 0 !== (int) $e->getCode() ? (int) $e->getCode() : 400 );
		} catch ( \Exception $e ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur interne lors de la sauvegarde', 'advanced-pdf-invoice-builder' ) ) );
		}
	}

	/**
	 * Valide et extrait les données de la requête save-canvas.
	 *
	 * @throws PdfBuilderException Si les données requises sont manquantes ou invalides.
	 */
	private function parse_save_canvas_request(): array {
		$this->assert_woo_commerce_capabilities( esc_html__( 'Permissions insuffisantes pour sauvegarder le canvas', 'advanced-pdf-invoice-builder' ) );
		$order_id    = isset( $GLOBALS['_POST']['order_id'] ) ? intval( wp_unslash( $GLOBALS['_POST']['order_id'] ) ) : 0;
		$canvas_data = isset( $GLOBALS['_POST']['canvas_data'] ) ? sanitize_textarea_field( \wp_unslash( $GLOBALS['_POST']['canvas_data'] ) ) : '';
		if ( ! $order_id || empty( $canvas_data ) ) {
			throw new PdfBuilderException( esc_html__( 'Données manquantes: order_id et canvas_data requis', 'advanced-pdf-invoice-builder' ), 422 );
		}
		if ( ! function_exists( 'wc_get_order' ) || ! \wc_get_order( $order_id ) ) {
			throw new PdfBuilderException( esc_html__( 'Commande introuvable', 'advanced-pdf-invoice-builder' ), 404 );
		}
		$elements = json_decode( $canvas_data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new PdfBuilderException( esc_html__( 'Format JSON invalide pour les données canvas', 'advanced-pdf-invoice-builder' ), 422 );
		}
		return array( $order_id, $this->canvas_helper->sanitize_canvas_elements( $elements ) );
	}

	/** AJAX handler pour charger le canvas d'une commande. */
	public function ajax_load_order_canvas(): void {
		try {
			[$order_id, $canvas_data] = $this->parse_load_canvas_request();
			if ( null === $canvas_data ) {
				wp_send_json_success(
					array(
						'canvas_data' => array(),
						'message'     => __( 'Aucune donnée canvas trouvée pour cette commande', 'advanced-pdf-invoice-builder' ),
					)
				);
				return;
			}
			wp_send_json_success(
				array(
					'canvas_data'    => $canvas_data,
					'order_id'       => $order_id,
					'elements_count' => count( $canvas_data ),
				)
			);
		} catch ( PdfBuilderException $e ) {
			\wp_send_json_error( array( 'message' => $e->getMessage() ), 0 !== (int) $e->getCode() ? (int) $e->getCode() : 400 );
		} catch ( \Exception $e ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur interne lors du chargement', 'advanced-pdf-invoice-builder' ) ) );
		}
	}

	/**
	 * Valide et extrait les données de la requête load-canvas.
	 *
	 * @throws PdfBuilderException Si la commande est introuvable ou invalide.
	 */
	private function parse_load_canvas_request(): array {
		$this->assert_woo_commerce_access_with_nonce( esc_html__( 'Permissions insuffisantes pour charger le canvas', 'advanced-pdf-invoice-builder' ) );
		$order_id = isset( $GLOBALS['_POST']['order_id'] ) ? intval( wp_unslash( $GLOBALS['_POST']['order_id'] ) ) : 0;
		if ( ! $order_id || ! ( function_exists( 'wc_get_order' ) && \wc_get_order( $order_id ) ) ) {
			$err_msg = $order_id ? esc_html__( 'Commande introuvable', 'advanced-pdf-invoice-builder' ) : esc_html__( 'ID commande manquant', 'advanced-pdf-invoice-builder' );
			throw new PdfBuilderException( esc_html( $err_msg ), 404 );
		}
		$canvas_data = \get_post_meta( $order_id, '_pdfib_canvas_data', true );
		if ( ! empty( $canvas_data ) && ! is_array( $canvas_data ) ) {
			throw new PdfBuilderException( esc_html__( 'Format de données canvas invalide', 'advanced-pdf-invoice-builder' ), 422 );
		}
		return array( $order_id, empty( $canvas_data ) ? null : $canvas_data );
	}

	/**
	 * AJAX: Valide l'accès à une commande.
	 *
	 * @throws PdfBuilderException Si l'accès à la commande est invalide.
	 */
	public function ajax_validate_order_access(): void {
		try {
			$this->assert_woo_commerce_access_with_nonce( esc_html__( 'Permissions insuffisantes pour accéder à cette commande', 'advanced-pdf-invoice-builder' ) );
			$order_id = isset( $GLOBALS['_POST']['order_id'] ) ? intval( wp_unslash( $GLOBALS['_POST']['order_id'] ) ) : 0;
			if ( ! $order_id ) {
				throw new PdfBuilderException( esc_html__( 'ID commande manquant', 'advanced-pdf-invoice-builder' ), 422 );
			}
			$order = $this->order_helper->get_and_validate_order( $order_id );
			if ( \is_wp_error( $order ) ) {
				throw new PdfBuilderException( esc_html( $order->get_error_message() ), 403 );
			}
			wp_send_json_success(
				array(
					'order_id'   => $order_id,
					'accessible' => true,
				)
			);
		} catch ( PdfBuilderException $e ) {
			\wp_send_json_error( array( 'message' => $e->getMessage() ), 0 !== (int) $e->getCode() ? (int) $e->getCode() : 400 );
		} catch ( \Exception $e ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur interne lors de la validation d\'accès', 'advanced-pdf-invoice-builder' ) ) );
		}
	}

	/** AJAX: Récupère les éléments canvas d'un template. */
	public function ajax_get_canvas_elements(): void {
		try {
			$this->assert_can_access_canvas_elements();
			$template_id                = $this->extract_template_id_from_request();
			$template_data              = $this->resolve_template_data_or_fail( $template_id );
			[$cache_key, $cache_on]     = $this->build_canvas_cache_context( $template_id );
			[$canvas_elements, $cached] = $this->get_canvas_elements_with_cache( $template_id, $template_data, $cache_key, $cache_on );
			wp_send_json_success(
				array(
					'elements'      => $canvas_elements,
					'template_id'   => $template_id,
					'cached'        => $cached,
					'element_count' => is_array( $canvas_elements ) ? count( $canvas_elements ) : 0,
				)
			);
		} catch ( PdfBuilderException $e ) {
			\wp_send_json_error( array( 'message' => $e->getMessage() ), 0 !== (int) $e->getCode() ? (int) $e->getCode() : 400 );
		} catch ( \Exception $e ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur interne lors de la récupération des éléments', 'advanced-pdf-invoice-builder' ) ) );
		}
	}

	/**
	 * Vérifie les droits et la sécurité nonce pour l'accès canvas template.
	 *
	 * @throws PdfBuilderException Si les permissions ou le nonce sont invalides.
	 */
	private function assert_can_access_canvas_elements(): void {
		$this->assert_woo_commerce_access_with_nonce( esc_html__( 'Permissions insuffisantes pour accéder aux éléments canvas', 'advanced-pdf-invoice-builder' ) );
	}

	/**
	 * Extrait et valide l'ID de template de la requête AJAX.
	 *
	 * @throws PdfBuilderException Si l'ID de template est invalide.
	 */
	private function extract_template_id_from_request(): int {
		$template_id = isset( $GLOBALS['_POST']['template_id'] ) ? intval( wp_unslash( $GLOBALS['_POST']['template_id'] ) ) : 0;
		if ( ! $template_id || $template_id <= 0 ) {
			throw new PdfBuilderException( esc_html__( 'ID template invalide ou manquant', 'advanced-pdf-invoice-builder' ), 422 );
		}
		return $template_id;
	}

	/**
	 * Résout les données du template ou déclenche une erreur 404.
	 *
	 * @param int $template_id ID du template.
	 * @throws PdfBuilderException Si le template est introuvable.
	 */
	private function resolve_template_data_or_fail( int $template_id ) {
		list( $template_exists, $template_data ) = $this->canvas_helper->resolve_template_existence( $template_id );
		if ( ! $template_exists ) {
			throw new PdfBuilderException( esc_html__( 'Template introuvable', 'advanced-pdf-invoice-builder' ), 404 );
		}
		return $template_data;
	}

	/**
	 * Construit le contexte de cache pour les éléments canvas.
	 *
	 * @param int $template_id ID du template.
	 * @return array
	 */
	private function build_canvas_cache_context( int $template_id ): array {
		$cache_key = 'pdfib_canvas_elements_' . $template_id;
		$cache_on  = ! empty( pdfib_get_option( 'pdfib_settings', array() )['cache_enabled'] );
		return array( $cache_key, $cache_on );
	}

	/**
	 * Charge les éléments canvas depuis le cache (si activé) ou depuis le template.
	 *
	 * @param int    $template_id   ID du template.
	 * @param mixed  $template_data Données du template.
	 * @param string $cache_key     Clé de cache transient.
	 * @param bool   $cache_on      Activer le cache.
	 * @return array
	 */
	private function get_canvas_elements_with_cache( int $template_id, mixed $template_data, string $cache_key, bool $cache_on ): array {
		$cached          = false;
		$canvas_elements = $cache_on ? get_transient( $cache_key ) : false;
		if ( false !== $canvas_elements ) {
			return array( $canvas_elements, true );
		}

		$canvas_elements = $this->canvas_helper->resolve_canvas_elements_from_template( $template_id, $template_data );
		$canvas_elements = $this->canvas_helper->validate_and_clean_canvas_elements( $canvas_elements );
		if ( $cache_on ) {
			\set_transient( $cache_key, $canvas_elements, 5 * \MINUTE_IN_SECONDS );
		}

		return array( $canvas_elements, $cached );
	}

	/** AJAX: Récupère les données entreprise. */
	public function ajax_get_company_data(): void {
		try {
			$this->assert_woo_commerce_access_with_nonce( esc_html__( 'Permissions insuffisantes pour accéder aux données entreprise', 'advanced-pdf-invoice-builder' ) );
			$company_data = array(
				'name'    => get_bloginfo( 'name' ),
				'address' => trim(
					get_option( 'woocommerce_store_address' ) . ' ' .
						get_option( 'woocommerce_store_address_2' ) . ' ' .
						get_option( 'woocommerce_store_postcode' ) . ' ' .
						get_option( 'woocommerce_store_city' )
				),
				'phone'   => get_option( 'woocommerce_phone' ) ? get_option( 'woocommerce_phone' ) : pdfib_get_option( 'pdfib_company_phone_manual', '' ),
				'email'   => get_option( 'woocommerce_email_from_address' ),
				'website' => get_option( 'siteurl' ),
				'vat'     => pdfib_get_option( 'pdfib_company_vat' ),
				'rcs'     => pdfib_get_option( 'pdfib_company_rcs' ),
				'siret'   => pdfib_get_option( 'pdfib_company_siret' ),
			);
			foreach ( $company_data as $key => $value ) {
				if ( empty( $value ) ) {
					$company_data[ $key ] = '';
				}
			}
			wp_send_json_success( array( 'company' => $company_data ) );
		} catch ( PdfBuilderException $e ) {
			\wp_send_json_error( array( 'message' => $e->getMessage() ), 0 !== (int) $e->getCode() ? (int) $e->getCode() : 400 );
		} catch ( \Exception $e ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur interne lors de la récupération des données entreprise', 'advanced-pdf-invoice-builder' ) ) );
		}
	}

	/**
	 * AJAX: Récupère les données d'une commande.
	 *
	 * @throws PdfBuilderException Si les données de commande sont invalides.
	 */
	public function ajax_get_order_data(): void {
		try {
			$this->assert_woo_commerce_access_with_nonce( esc_html__( 'Permissions insuffisantes pour accéder aux données de commande', 'advanced-pdf-invoice-builder' ) );
			$order_id = isset( $GLOBALS['_POST']['order_id'] ) ? intval( wp_unslash( $GLOBALS['_POST']['order_id'] ) ) : 0;
			if ( ! $order_id ) {
				throw new PdfBuilderException( esc_html__( 'ID commande manquant', 'advanced-pdf-invoice-builder' ), 422 );
			}
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				throw new PdfBuilderException( esc_html__( 'Commande introuvable', 'advanced-pdf-invoice-builder' ), 404 );
			}
			$order_data = array(
				'order' => array(
					'id'     => $order->get_id(),
					'number' => $order->get_order_number(),
					'status' => $order->get_status(),
					'total'  => $order->get_total(),
				),
				'items' => $this->order_helper->get_order_items_complete_data( $order ),
			);
			wp_send_json_success(
				array(
					'order'    => $order_data,
					'order_id' => $order_id,
				)
			);
		} catch ( PdfBuilderException $e ) {
			\wp_send_json_error( array( 'message' => $e->getMessage() ), 0 !== (int) $e->getCode() ? (int) $e->getCode() : 400 );
		} catch ( \Exception $e ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur interne lors de la récupération des données de commande', 'advanced-pdf-invoice-builder' ) ) );
		}
	}
}
