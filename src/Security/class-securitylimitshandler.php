<?php
/**
 * Gestionnaire des limites de sécurité.
 *
 * Applique les paramètres max_execution_time, memory_limit et max_template_size.
 *
 * @package PDFIB\Security
 */

namespace PDFIB\Security;

use PDFIB\Exception\TemplateSizeException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gère les limites de sécurité du plugin.
 */
class SecurityLimitsHandler {

	/**
	 * Initialise le gestionnaire des limites.
	 */
	public static function init(): void {
		// Appliquer les limites au chargement du plugin.
		\add_action( 'plugins_loaded', array( __CLASS__, 'apply_security_limits' ), 5 );
		// Valider la taille des fichiers uploadés.
		\add_filter( 'upload_size_limit', array( __CLASS__, 'validate_upload_size' ) );
		// Le système de génération PDF a été retiré, donc la validation du template n'est plus nécessaire.
	}

	/**
	 * Applique les limites de sécurité depuis les settings.
	 */
	public static function apply_security_limits(): void {
		// NOTE: PHP execution-time and memory-limit settings are not changed from this global hook.
		// For WordPress.org compliance, PHP settings must not be changed globally.
		// They are applied only within specific PDF generation functions as needed.
	}

	/**
	 * Valide la taille du fichier uploadé.
	 *
	 * @param int $size Taille maximale courante.
	 */
	public static function validate_upload_size( int $size ): int {
		$settings          = pdfib_get_option( 'pdfib_settings', array() );
		$max_template_size = isset( $settings['max_template_size'] )
			? \intval( $settings['max_template_size'] )
			: 52428800;
		// 50MB par défaut.

		// Retourner la limite la plus petite.
		return min( $size, $max_template_size );
	}

	/**
	 * Valide la taille du template avant génération.
	 *
	 * @param array $template_data Données du template.
	 * @throws TemplateSizeException Si le template est trop gros.
	 */
	public static function validate_template_size( array $template_data ): void {
		$settings          = pdfib_get_option( 'pdfib_settings', array() );
		$max_template_size = isset( $settings['max_template_size'] )
			? \intval( $settings['max_template_size'] )
			: 52428800;
		// 50MB par défaut.

		// Estimer la taille via JSON (sérialization légère, non stockée).
		$serialized = wp_json_encode( $template_data );
		$size       = strlen( false !== $serialized ? $serialized : '' );
		if ( $size > $max_template_size ) {
			$size_mb = round( $size / 1048576, 2 );
			$max_mb  = round( $max_template_size / 1048576, 2 );
			throw new TemplateSizeException(
				sprintf(
					'La taille du template (%s MB) dépasse la limite configurée (%s MB)',
					esc_html( $size_mb ),
					esc_html( $max_mb )
				)
			);
		}
	}

	/**
	 * Retourne les informations des limites actuelles.
	 */
	public static function get_limits_info(): array {
		$settings     = pdfib_get_option( 'pdfib_settings', array() );
		$request_time = (float) sanitize_text_field( wp_unslash( $GLOBALS['_SERVER']['REQUEST_TIME_FLOAT'] ?? '0' ) );
		return array(
			'max_execution_time'     => isset( $settings['max_execution_time'] )
				? \intval( $settings['max_execution_time'] )
				: 300,
			'memory_limit'           => \intval( pdfib_get_option( 'pdfib_canvas_memory_limit_php', 256 ) ) . 'M',
			'max_template_size'      => isset( $settings['max_template_size'] )
				? \intval( $settings['max_template_size'] )
				: 52428800,
			'current_memory_usage'   => memory_get_usage( true ),
			'peak_memory_usage'      => memory_get_peak_usage( true ),
			'current_execution_time' => round( microtime( true ) - $request_time, 2 ),
		);
	}
}

// Initialiser au chargement.
SecurityLimitsHandler::init();
