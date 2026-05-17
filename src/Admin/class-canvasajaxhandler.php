<?php
/**
 * Advanced PDF Invoice Builder - Canvas AJAX Handler.
 *
 * @package PDFIB\Admin
 */

namespace PDFIB\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PDFIB\Canvas\CanvasManager;

/**
 * Canvas AJAX Handlers.
 * Gere les requetes AJAX pour les parametres du canvas.
 *
 * @package PDF_Builder
 * @since 1.1.0
 */
class CanvasAJAXHandler {

	/**
	 * Enregistre les handlers AJAX.
	 */
	public static function register_hooks(): void {
		add_action( 'wp_ajax_pdfib_get_canvas_settings', array( self::class, 'get_canvas_settings' ) );
		add_action( 'wp_ajax_pdfib_reset_canvas_settings', array( self::class, 'reset_canvas_settings' ) );
	}

	/**
	 * Recupere les parametres du canvas.
	 */
	public static function get_canvas_settings(): void {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Nonce de securite invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$canvas_manager = CanvasManager::get_instance();
			$settings       = $canvas_manager->get_all_settings();
			\wp_send_json_success(
				array(
					'settings' => $settings,
					'message'  => \__( 'Parametres du canvas recuperes avec succes', 'advanced-pdf-invoice-builder' ),
				)
			);
		} catch ( \Exception $e ) {
			\wp_send_json_error(
				array(
					// translators: %s: exception error message.
					'message' => sprintf( \__( 'Erreur: %s', 'advanced-pdf-invoice-builder' ), $e->getMessage() ),
				)
			);
		}
	}

	/**
	 * Sauvegarde les parametres du canvas.
	 */
	public static function save_canvas_settings(): void {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Nonce de securite invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$settings = isset( $GLOBALS['_POST']['settings'] ) && is_array( $GLOBALS['_POST']['settings'] ) ? wp_unslash( $GLOBALS['_POST']['settings'] ) : array();
			if ( empty( $settings ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Aucun parametre a sauvegarder', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$canvas_manager = CanvasManager::get_instance();
			// @phpstan-ignore-next-line CanvasManager::save_settings() defined in stub.
			$saved = $canvas_manager->save_settings( $settings );
			if ( $saved ) {
				\wp_send_json_success(
					array(
						'message'  => \__( 'Parametres du canvas sauvegardes avec succes', 'advanced-pdf-invoice-builder' ),
						'settings' => $canvas_manager->get_all_settings(),
					)
				);
			} else {
				\wp_send_json_error(
					array(
						'message' => \__( 'Erreur lors de la sauvegarde des parametres', 'advanced-pdf-invoice-builder' ),
					)
				);
			}
		} catch ( \Exception $e ) {
			\wp_send_json_error(
				array(
					// translators: %s: exception error message.
					'message' => sprintf( \__( 'Erreur: %s', 'advanced-pdf-invoice-builder' ), $e->getMessage() ),
				)
			);
		}
	}

	/**
	 * Reinitialise les parametres du canvas aux valeurs par defaut.
	 */
	public static function reset_canvas_settings(): void {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Nonce de securite invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! isset( $GLOBALS['_POST']['confirm'] ) || sanitize_text_field( wp_unslash( $GLOBALS['_POST']['confirm'] ) ) !== 'yes' ) {
				\wp_send_json_error(
					array(
						'message' => \__( 'Action non confirmee', 'advanced-pdf-invoice-builder' ),
					)
				);
				return;
			}
			$canvas_manager = CanvasManager::get_instance();
			// @phpstan-ignore-next-line CanvasManager::reset_to_defaults() defined in stub.
			$canvas_manager->reset_to_defaults();
			\wp_send_json_success(
				array(
					'message'  => \__( 'Parametres du canvas reinitialises aux valeurs par defaut', 'advanced-pdf-invoice-builder' ),
					'settings' => $canvas_manager->get_all_settings(),
				)
			);
		} catch ( \Exception $e ) {
			\wp_send_json_error(
				array(
					// translators: %s: exception error message.
					'message' => sprintf( \__( 'Erreur: %s', 'advanced-pdf-invoice-builder' ), $e->getMessage() ),
				)
			);
		}
	}
}
