<?php
/**
 * Gestion centralisée des paramètres et configurations.
 *
 * @package PDFIB\Managers
 */

namespace PDFIB\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

/**
 * Gère les paramètres globaux du plugin.
 */
class PDFBuilderSettingsManager {
	/**
	 * Instance du plugin principal.
	 *
	 * @var mixed
	 */
	private $main;

	/**
	 * Constructeur.
	 *
	 * @param mixed $main_instance Instance du plugin principal.
	 */
	public function __construct( mixed $main_instance ) {
		$this->main = $main_instance;
	}

	/**
	 * Page de paramètres généraux.
	 *
	 * @return void
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires.', 'advanced-pdf-invoice-builder' ) );
		}

		if ( ( isset( $GLOBALS['_POST']['save_settings'] ) || isset( $GLOBALS['_POST']['pdfib_floating_save'] ) ) && check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Paramètres sauvegardés avec succès.', 'advanced-pdf-invoice-builder' ) . '</p></div>';
		}

		include_once plugin_dir_path( __DIR__ ) . '../../templates/admin/settings-page.php';
	}
	/**
	 * Récupère un paramètre.
	 *
	 * @param string $option   Clé du paramètre.
	 * @param mixed  $fallback Valeur par défaut.
	 * @return mixed Valeur du paramètre.
	 */
	public function get_setting( $option, $fallback = false ) {
		return get_option( $option, $fallback );
	}

	/**
	 * Sauvegarde les paramètres généraux.
	 *
	 * @return void
	 */
	private function save_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );

		// Rôles autorisés.
		$allowed_roles = isset( $GLOBALS['_POST']['allowed_roles'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $GLOBALS['_POST']['allowed_roles'] ) ) : array( 'administrator' );
		pdfib_update_option( 'pdfib_allowed_roles', $allowed_roles );

		// Informations société, seulement les champs non disponibles dans WooCommerce ou WordPress.
		$company_vat   = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['company_vat'] ?? '' ) );
		$company_rcs   = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['company_rcs'] ?? '' ) );
		$company_siret = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['company_siret'] ?? '' ) );

		pdfib_update_option( 'pdfib_company_vat', $company_vat );
		pdfib_update_option( 'pdfib_company_rcs', $company_rcs );
		pdfib_update_option( 'pdfib_company_siret', $company_siret );

		// Templates par statut de commande.
		$status_templates = array();
		if ( isset( $GLOBALS['_POST']['order_status_templates'] ) && is_array( $GLOBALS['_POST']['order_status_templates'] ) ) {
			$order_status_raw = wp_unslash( $GLOBALS['_POST']['order_status_templates'] );
			foreach ( $order_status_raw as $status => $template_id ) {
				$status_templates[ sanitize_text_field( $status ) ] = intval( $template_id );
			}
		}
		$settings                                 = pdfib_get_option( 'pdfib_settings', array() );
		$settings['pdfib_order_status_templates'] = $status_templates;
		pdfib_update_option( 'pdfib_settings', $settings );
	}
}
