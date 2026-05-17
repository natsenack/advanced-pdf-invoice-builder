<?php
/**
 * Advanced PDF Invoice Builder - Security Validator.
 *
 * Validateur de sécurité pour PDF Builder.
 *
 * @package PDFIB\Core
 */

namespace PDFIB\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Valide et assainit les données de sécurité.
 */
class PdfBuilderSecurityValidator {

	/**
	 * Récupère l'instance unique.
	 *
	 * @return self
	 */
	public static function get_instance() {
		static $instance;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Initialise le validateur de sécurité.
	 */
	public function init() {
		// Hooks de sécurité enregistrés dans le chargeur principal.
	}

	/**
	 * Assainit le contenu HTML avec wp_kses_post.
	 *
	 * @param string $content Contenu HTML brut.
	 * @return string
	 */
	public static function sanitize_html_content( string $content ): string {
		if ( '' === $content ) {
			return '';
		}

		return wp_kses_post( $content );
	}

	/**
	 * Valide les données JSON.
	 *
	 * @param string $json Chaîne JSON à valider.
	 * @return mixed
	 */
	public static function validate_json_data( string $json ): mixed {
		if ( '' === $json ) {
			return false;
		}

		$decoded = json_decode( $json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		return $decoded;
	}

	/**
	 * Valide le nonce de sécurité WordPress.
	 *
	 * @return bool
	 */
	public static function validate_nonce(): bool {
		$nonce_received = isset( $GLOBALS['_POST']['nonce'] ) ? sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ) ) : '';
		return wp_verify_nonce( $nonce_received, 'pdfib_ajax' );
	}

	/**
	 * Vérifie les capacités utilisateur WordPress.
	 *
	 * @return bool
	 */
	public static function check_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Valide une requête AJAX.
	 *
	 * @return bool
	 */
	public function validate_ajax_request(): bool {
		return $this->validate_nonce() && $this->check_permissions();
	}

	/**
	 * Assainit les données de template.
	 *
	 * @param array $data Données du template.
	 * @return array
	 */
	public function sanitize_template_data( array $data ): array {
		array_walk_recursive(
			$data,
			function ( &$value ) {
				if ( is_string( $value ) ) {
					$value = sanitize_text_field( $value );
				}
			}
		);

		return $data;
	}

	/**
	 * Assainit les paramètres.
	 *
	 * @param array $settings Paramètres à assainir.
	 * @return array
	 */
	public function sanitize_settings( array $settings ): array {
		return $this->sanitize_template_data( $settings );
	}
}
