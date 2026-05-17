<?php
/**
 * Advanced PDF Invoice Builder - Gestionnaire centralisé des nonces.
 * Système unifié pour la validation et le refresh automatique des nonces.
 *
 * @package PdfBuilderNonceManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized nonce manager for AJAX validation.
 */
class PdfBuilderNonceManager {

	// Configuration des nonces.
	const MAX_RETRIES = 2;

	/**
	 * I18n error message helper — cannot use __() in class const declarations.
	 *
	 * @return string
	 */
	private static function err_permissions(): string {
		return __( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns translated invalid nonce error message.
	 *
	 * @return string
	 */
	private static function err_nonce_invalid(): string {
		return __( 'Nonce invalide', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns translated missing nonce error message.
	 *
	 * @return string
	 */
	private static function err_nonce_missing(): string {
		return __( 'Nonce manquant', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns translated nonce generation error message.
	 *
	 * @return string
	 */
	private static function err_nonce_generate(): string {
		return __( 'Erreur lors de la génération du nonce', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns translated internal error message.
	 *
	 * @return string
	 */
	private static function err_internal(): string {
		return __( 'Erreur interne', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;
	/**
	 * AJAX nonce action name.
	 *
	 * @var string
	 */
	private string $nonce_action = 'pdfib_ajax';
	/**
	 * Nonce TTL in milliseconds.
	 *
	 * @var int
	 */
	private int $nonce_ttl = 20 * 60 * 1000; // 20 minutes.

	/**
	 * Singleton pattern
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructeur privé
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialise les hooks
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_pdfib_get_fresh_nonce', array( $this, 'ajax_get_fresh_nonce' ) );
	}

	/**
	 * Génère un nouveau nonce.
	 */
	public function generate_nonce() {
		return wp_create_nonce( $this->nonce_action );
	}

	/**
	 * Valide un nonce avec gestion d'erreurs détaillée.
	 *
	 * @param string $nonce Nonce to validate.
	 */
	public function validate_nonce( string $nonce ) {
		if ( empty( $nonce ) ) {
			return false;
		}

		return wp_verify_nonce( $nonce, $this->nonce_action );
	}




	/**
	 * Handler AJAX pour obtenir un nonce frais.
	 */
	public function ajax_get_fresh_nonce() {
		if ( false === check_ajax_referer( $this->nonce_action, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => self::err_nonce_invalid() ) );
			return;
		}

		try {
			// Validation légère pour cette action.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => self::err_permissions() ) );
				return;
			}

			$fresh_nonce = $this->generate_nonce();

			if ( $fresh_nonce ) {
				wp_send_json_success(
					array(
						'nonce'        => $fresh_nonce,
						'generated_at' => time(),
						'expires_in'   => $this->nonce_ttl / 1000,
					)
				);
			} else {
				wp_send_json_error( array( 'message' => self::err_nonce_generate() ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Vérifie si un nonce est proche de l'expiration.
	 */
	public function is_nonce_expiring_soon() {
		// WordPress découpe le temps en "ticks" de ~6h. Un nonce est valide sur 2 ticks consécutifs (~12h max).
		// On stocke le tick au dernier rafraîchissement pour comparer.
		$current_tick = wp_nonce_tick();
		// wp_nonce_tick() retourne floor(time / (DAY_IN_SECONDS / 2)).
		// On stocke le tick au dernier rafraîchissement pour comparer.
		$stored_tick = (int) get_transient( 'pdfib_nonce_tick_' . get_current_user_id() );
		if ( 0 === $stored_tick ) {
			set_transient( 'pdfib_nonce_tick_' . get_current_user_id(), $current_tick, 7 * DAY_IN_SECONDS );
			return false;
		}
		// Si le tick courant est supérieur au tick stocké, le nonce approche de son 2e tick (expire bientôt).
		return $current_tick > $stored_tick;
	}


	/**
	 * Valide une requête AJAX complète.
	 *
	 * @param string $context Request context name for debugging.
	 */
	public function validate_ajax_request( string $context = '' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => self::err_permissions() ) );
			return false;
		}

		// Vérifier le nonce (chercher d'abord _wpnonce, puis nonce pour compatibilité).
		$wpnonce        = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['_wpnonce'] ?? '' ) );
		$nonce_fallback = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) );
		$nonce          = ! empty( $wpnonce ) ? $wpnonce : $nonce_fallback;
		$error          = $this->resolve_nonce_validation_error( $nonce, $wpnonce, $nonce_fallback, $context );
		if ( null !== $error ) {
			wp_send_json_error( $error );
			return false;
		}
		return true;
	}

	/**
	 * Retourne null si nonce valide, sinon le payload d'erreur à renvoyer.
	 *
	 * @param string $nonce          Resolved nonce value.
	 * @param string $wpnonce        Value of _wpnonce field.
	 * @param string $nonce_fallback Value of nonce field.
	 * @param string $context        Request context.
	 */
	private function resolve_nonce_validation_error( string $nonce, string $wpnonce, string $nonce_fallback, string $context ): ?array {
		if ( empty( $nonce ) ) {
			return array(
				'message' => self::err_nonce_missing(),
				'debug'   => array(
					'_wpnonce' => $wpnonce ? $wpnonce : 'not set',
					'nonce'    => $nonce_fallback ? $nonce_fallback : 'not set',
				),
			);
		}
		if ( ! $this->validate_nonce( $nonce ) ) {
			return array(
				'message' => self::err_nonce_invalid(),
				'debug'   => array(
					'provided_nonce' => $nonce,
					'context'        => $context,
				),
			);
		}
		return null;
	}

	// -----------------------------------------------------------------------
	// Méthodes de debug (anciennement dans PdfBuilderSecurityManager)
	// -----------------------------------------------------------------------

	/**
	 * Vérifie si le debug PHP est activé.
	 */
	public static function is_php_debug_enabled(): bool {
		return (bool) pdfib_get_option( 'pdfib_debug_php_errors', false );
	}

	/**
	 * Vérifie si le debug base de données est activé.
	 */
	public static function is_database_debug_enabled(): bool {
		return (bool) pdfib_get_option( 'pdfib_debug_database', false );
	}

	/**
	 * Vérifie si le debug performance est activé.
	 */
	public static function is_performance_debug_enabled(): bool {
		return (bool) pdfib_get_option( 'pdfib_debug_performance', false );
	}

	/**
	 * Log conditionnel selon le type de debug.
	 *
	 * @param string $type    'php_errors' | 'database' | 'performance'.
	 * @param mixed  ...$args Arguments à logger.
	 */
	public static function debug_log( string $type, mixed ...$args ): void {
		$enabled = match ( $type ) {
			'php_errors'  => self::is_php_debug_enabled(),
			'database'    => self::is_database_debug_enabled(),
			'performance' => self::is_performance_debug_enabled(),
			default       => false,
		};

		if ( $enabled ) {
			$prefix  = '[PDF Builder Debug ' . ucfirst( $type ) . ']';
			$message = $prefix . ' ' . implode(
				' ',
				array_map(
					static function ( $arg ): string {
						return is_string( $arg ) ? $arg : (string) wp_json_encode( $arg );
					},
					$args
				)
			);
			if ( function_exists( 'pdfib_debug_log' ) ) {
				pdfib_debug_log( $message );
			}
		}
	}
}
