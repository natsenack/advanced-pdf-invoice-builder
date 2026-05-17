<?php
/**
 * Advanced PDF Invoice Builder - AJAX Base Handler.
 *
 * @package PDFIB\AJAX
 */

namespace PDFIB\AJAX;

defined( 'ABSPATH' ) || exit;

/**
 * Classe de base pour les handlers AJAX.
 *
 * Centralise la validation commune et la gestion d'erreurs.
 */
abstract class PdfBuilderAjaxBase {

	/**
	 * Capacite requise pour l'acces.
	 *
	 * @var string
	 */
	protected string $required_capability = 'manage_options';

	/**
	 * Action nonce utilisee pour la validation.
	 *
	 * @var string
	 */
	protected string $nonce_action = 'pdfib_ajax';

	/**
	 * Valide et nettoie un parametre requis.
	 *
	 * @param string $param_name Nom du parametre a valider.
	 * @param string $type       Type de validation a appliquer.
	 * @return mixed
	 */
	protected function validate_required_param( string $param_name, string $type = 'string' ) {
		check_ajax_referer( $this->nonce_action, 'nonce', false );

		switch ( $type ) {
			case 'int':
				if ( ! isset( $_POST[ $param_name ] ) ) {
					$this->send_error( "Parametre manquant: {$param_name}", 400 );
				}
				$value = absint( wp_unslash( $_POST[ $param_name ] ) );
				if ( $value <= 0 ) {
					$this->send_error( "Parametre invalide: {$param_name}", 400 );
				}
				return $value;

			case 'string':
				if ( ! isset( $_POST[ $param_name ] ) ) {
					$this->send_error( "Parametre manquant: {$param_name}", 400 );
				}
				$value = sanitize_text_field( wp_unslash( $_POST[ $param_name ] ) );
				if ( empty( $value ) ) {
					$this->send_error( "Parametre vide: {$param_name}", 400 );
				}
				return $value;

			case 'json':
				// filter_input lit directement l'entrée PHP sans les magic quotes WP.
				$json_raw = filter_input( INPUT_POST, $param_name );
				if ( null === $json_raw || false === $json_raw ) {
					$this->send_error( "Parametre manquant: {$param_name}", 400 );
				}
				$decoded = json_decode( $json_raw, true );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					$this->send_error( "JSON invalide pour: {$param_name} - " . json_last_error_msg(), 400 );
				}
				return $decoded;

			default:
				if ( ! isset( $_POST[ $param_name ] ) ) {
					$this->send_error( "Parametre manquant: {$param_name}", 400 );
				}
				return sanitize_text_field( wp_unslash( $_POST[ $param_name ] ) );
		}
	}

	/**
	 * Envoie une reponse d'erreur standardisee.
	 *
	 * @param string $message Message d'erreur.
	 * @param int    $code    Code HTTP.
	 */
	protected function send_error( string $message, int $code = 400 ) {
		wp_send_json_error(
			array(
				'message'   => $message,
				'code'      => $code,
				'timestamp' => time(),
			),
			$code
		);
		exit;
	}

	/**
	 * Envoie une reponse de succes standardisee.
	 *
	 * @param array  $data    Donnees a retourner.
	 * @param string $message Message de succes.
	 */
	protected function send_success( array $data = array(), string $message = 'Operation reussie' ) {
		wp_send_json_success(
			array_merge(
				array(
					'message'   => $message,
					'timestamp' => time(),
				),
				$data
			)
		);
		exit;
	}

	/**
	 * Logue une erreur pour le debugging.
	 *
	 * @param string $message Message d'erreur.
	 * @param array  $context Contexte additionnel.
	 */
	protected function log_error( string $message, array $context = array() ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wp_trigger_error' ) ) {
			$context_str = ! empty( $context ) ? ' | Context: ' . wp_json_encode( $context ) : '';
			wp_trigger_error( 'PDFIB', $message . $context_str, E_USER_NOTICE );
		}
	}

	/**
	 * Valide le nonce de la requete.
	 *
	 * @return bool
	 */
	protected function validate_nonce(): bool {
		return isset( $GLOBALS['_POST']['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) ), $this->nonce_action );
	}

	/**
	 * Verifie que la requete est de type POST.
	 *
	 * @return bool
	 */
	protected function is_post_request(): bool {
		return sanitize_text_field( wp_unslash( $GLOBALS['_SERVER']['REQUEST_METHOD'] ?? '' ) ) === 'POST';
	}

	/**
	 * Valide la requete AJAX (nonce + permissions + methode).
	 */
	protected function validate_request(): void {
		if ( ! $this->validate_nonce() ) {
			$this->send_error( pdfib_err_nonce(), 403 );
		}

		if ( ! current_user_can( $this->required_capability ) ) {
			$this->send_error( pdfib_err_perms(), 403 );
		}

		if ( sanitize_text_field( wp_unslash( $GLOBALS['_SERVER']['REQUEST_METHOD'] ?? '' ) ) !== 'POST' ) {
			$this->send_error( 'Methode HTTP non autorisee', 405 );
		}
	}

	/**
	 * Methode abstraite que les classes enfants doivent implementer.
	 */
	abstract public function handle();
}
