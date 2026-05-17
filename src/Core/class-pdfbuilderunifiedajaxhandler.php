<?php
/**
 * Gestionnaire AJAX unifié pour Advanced PDF Invoice Builder.
 *
 * @package AdvancedPdfInvoiceBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced PDF Invoice Builder - Handler AJAX unifié.
 * Point d'entrée unique pour toutes les actions AJAX avec gestion centralisée des nonces.
 * Version: 2.1.3 - Correction erreurs PHP et cron (05/12/2025).
 */
class PdfBuilderUnifiedAjaxHandler {

	/**
	 * Instance singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Gestionnaire de nonces.
	 *
	 * @var PdfBuilderNonceManager|null
	 */
	private ?PdfBuilderNonceManager $nonce_manager = null;

	/**
	 * Moteur utilisé pour la génération en cours.
	 *
	 * @var string
	 */
	private string $current_engine_name = 'puppeteer';

	/**
	 * Cache de commandes scope-requête pour éviter les appels redondants.
	 *
	 * @var array<int|string, mixed>
	 */
	private array $order_cache = array();

	/**
	 * Cache de templates scope-requête.
	 *
	 * @var array<int|string, mixed>
	 */
	private array $template_cache = array();

	/**
	 * Cache HTML scope-requête.
	 *
	 * @var array<int|string, mixed>
	 */
	private array $html_cache = array();

	/**
	 * Error message helpers — i18n strings cannot be used in class const declarations.
	 *
	 * @return string
	 */
	private static function err_internal(): string {
		return __( 'Erreur interne du serveur', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns i18n nonce error message.
	 *
	 * @return string
	 */
	private static function err_nonce(): string {
		return __( 'Nonce invalide', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns i18n access denied error message.
	 *
	 * @return string
	 */
	private static function err_access(): string {
		return __( 'Accès refusé.', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns i18n insufficient permissions error message.
	 *
	 * @return string
	 */
	private static function err_perms(): string {
		return __( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns i18n error prefix string.
	 *
	 * @return string
	 */
	private static function err_prefix(): string {
		return __( 'Erreur: ', 'advanced-pdf-invoice-builder' );
	}
	private const COLOR_WHITE           = '#ffffff';
	private const COLOR_BLACK           = '#000000';
	private const COLOR_DARK_GRAY       = '#374151';
	private const COLOR_NEAR_BLACK      = '#111827';
	private const COLOR_LIGHT_GRAY      = '#e5e7eb';
	private const DATE_FORMAT_FR        = 'd/m/Y';
	private const DATE_FORMAT_DB        = 'Y-m-d H:i:s';
	private const FONT_DEFAULT          = 'DejaVu Sans';
	private const HTML_NBSP             = '&nbsp;';
	private const HEADER_CONTENT_LENGTH = 'Content-Length: ';
	private const STR_PADDING_REGEX     = '/padding(-top|-bottom|-left|-right)?:\s*[^;]+;/i';
	private const CSS_IMPORTANT         = '!important';
	private const CSS_BOX_SIZING        = ' box-sizing: border-box;';
	private const CSS_BG_COLOR          = ' background-color: ';
	private const CSS_BORDER            = ' border: ';
	private const CSS_TD_RIGHT          = ' text-align: right; width: 80px; max-width: 80px;">';
	private const CSS_FLEX_DISPLAY      = ' display: flex !important; line-height: 1 !important;';
	private const CSS_FLEX_COL          = ' flex-direction: column !important;';
	private const CSS_JC_CENTER         = ' justify-content: center !important;';
	private const CSS_JC_END            = ' justify-content: flex-end !important;';
	private const CSS_JC_START          = ' justify-content: flex-start !important;';
	private const CSS_AI_CENTER         = ' align-items: center !important;';
	private const CSS_AI_END            = ' align-items: flex-end !important;';
	private const CSS_AI_START          = ' align-items: flex-start !important;';
	private const LABEL_PAYMENT         = 'Paiement: ';
	private const LABEL_CARD            = 'Carte bancaire';
	private const LABEL_SIRET           = 'SIRET: ';
	private const LABEL_TVA             = 'TVA: ';
	private const LABEL_RCS             = 'RCS: ';

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
		$this->nonce_manager = PdfBuilderNonceManager::get_instance();
		$this->init_hooks();
	}

	/**
	 * Initialise les hooks AJAX (sauvegarde, canvas, maintenance, backup, RGPD, licence)
	 */
	private function init_hooks() {
		$p = 'wp_ajax_';

		\pdfib_add_hook( $p . 'pdfib_save_settings', array( $this, 'handle_save_settings' ) );
		\pdfib_add_hook( $p . 'pdfib_save_all_settings', array( $this, 'handle_save_all_settings' ) );
		\pdfib_add_hook( $p . 'pdfib_get_canvas_orientations', array( $this, 'handle_get_canvas_orientations' ) );
		\pdfib_add_hook( $p . 'pdfib_optimize_database', array( $this, 'handle_optimize_database' ) );
		\pdfib_add_hook( $p . 'pdfib_remove_temp_files', array( $this, 'handle_remove_temp_files' ) );
		\pdfib_add_hook( $p . 'pdfib_repair_templates', array( $this, 'handle_repair_templates' ) );
		\pdfib_add_hook( $p . 'pdfib_clear_temp', array( $this, 'handle_clear_temp_files' ) );
		\pdfib_add_hook( $p . 'pdfib_toggle_auto_maintenance', array( $this, 'handle_toggle_auto_maintenance' ) );
		\pdfib_add_hook( $p . 'pdfib_schedule_maintenance', array( $this, 'handle_schedule_maintenance' ) );
		\pdfib_add_hook( $p . 'pdfib_create_backup', array( $this, 'handle_create_backup' ) );
		\pdfib_add_hook( $p . 'pdfib_list_backups', array( $this, 'handle_list_backups' ) );
		\pdfib_add_hook( $p . 'pdfib_restore_backup', array( $this, 'handle_restore_backup' ) );
		\pdfib_add_hook( $p . 'pdfib_delete_backup', array( $this, 'handle_delete_backup' ) );
		\pdfib_add_hook( $p . 'pdfib_download_backup', array( $this, 'handle_download_backup' ) );
		\pdfib_add_hook( $p . 'pdfib_export_gdpr_data', array( $this, 'handle_export_gdpr_data' ) );
		\pdfib_add_hook( $p . 'pdfib_delete_gdpr_data', array( $this, 'handle_delete_gdpr_data' ) );
		\pdfib_add_hook( $p . 'pdfib_get_consent_status', array( $this, 'handle_get_consent_status' ) );
		\pdfib_add_hook( $p . 'pdfib_get_audit_log', array( $this, 'handle_get_audit_log' ) );
		\pdfib_add_hook( $p . 'pdfib_export_audit_log', array( $this, 'handle_export_audit_log' ) );
		$this->register_hooks_secondary( $p );
	}

	/**
	 * Enregistre les hooks secondaires (diagnostic, test, dev, canvas, PDF).
	 *
	 * @param string $p Préfixe d'action AJAX WordPress.
	 * @return void
	 */
	private function register_hooks_secondary( string $p ) {
		\pdfib_add_hook( $p . 'pdfib_export_diagnostic', array( $this, 'handle_export_diagnostic' ) );
		\pdfib_add_hook( $p . 'pdfib_view_logs', array( $this, 'handle_view_logs' ) );
		\pdfib_add_hook( $p . 'pdfib_refresh_logs', array( $this, 'handle_refresh_logs' ) );
		\pdfib_add_hook( $p . 'pdfib_clear_logs', array( $this, 'handle_clear_logs' ) );
		\pdfib_add_hook( $p . 'pdfib_test_ajax', array( $this, 'handle_test_ajax' ) );
		\pdfib_add_hook( $p . 'pdfib_test_routes', array( $this, 'handle_test_routes' ) );
		\pdfib_add_hook( $p . 'pdfib_test_hook', array( $this, 'handle_test_hook' ) );
		\pdfib_add_hook( $p . 'pdfib_get_fresh_nonce', array( $this, 'handle_get_fresh_nonce' ) );
		\pdfib_add_hook( $p . 'pdfib_system_info', array( $this, 'handle_system_info' ) );
		\pdfib_add_hook( $p . 'pdfib_save_canvas_settings', array( $this, 'handle_save_canvas_settings' ) );
		\pdfib_add_hook( $p . 'pdfib_generate_pdf', array( $this, 'handle_generate_pdf' ) );
		\pdfib_add_hook( $p . 'pdfib_queue_status', array( $this, 'handle_queue_status' ) );
		\pdfib_add_hook( $p . 'pdfib_test_puppeteer', array( $this, 'handle_test_puppeteer' ) );
		\pdfib_add_hook( $p . 'pdfib_test_all_engines', array( $this, 'handle_test_all_engines' ) );
		\pdfib_add_hook( $p . 'pdfib_get_active_engine', array( $this, 'handle_get_active_engine' ) );
		\pdfib_add_hook( $p . 'pdfib_debug_html', array( $this, 'handle_debug_html' ) );
		\pdfib_add_hook( $p . 'pdfib_get_preview_html', array( $this, 'handle_get_preview_html' ) );
		\pdfib_add_hook( $p . 'pdfib_get_orders_list', array( $this, 'handle_get_orders_list' ) );
	}

	/**
	 * Handler principal pour la sauvegarde des paramètres
	 */
	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		$nonce       = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? $GLOBALS['_POST']['_wpnonce'] ?? '' ) );
		$nonce_valid = wp_verify_nonce( $nonce, 'pdfib_ajax' )
			|| wp_verify_nonce( $nonce, 'pdfib_settings' )
			|| wp_verify_nonce( $nonce, 'pdfib_save_settings' );

		if ( ! $nonce_valid ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: nonce invalide', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		try {
			$current_tab                   = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['tab'] ?? 'all' ) );
			$result                        = $this->dispatch_settings_save( $current_tab );
			[$saved_count, $saved_options] = $result;

			if ( $saved_count > 0 ) {
				wp_send_json_success(
					array(
						'message'        => 'Paramètres sauvegardés avec succès',
						'saved_count'    => $saved_count,
						'saved_settings' => $saved_options,
						'new_nonce'      => $this->nonce_manager->generate_nonce(),
					)
				);
			} else {
				wp_send_json_error( array( 'message' => 'Erreur lors de la sauvegarde en base de données' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Délègue la sauvegarde selon l'onglet actif.
	 *
	 * @param string $current_tab Onglet actif (all, general, etc.).
	 * @return array{int, array<string,mixed>}
	 */
	private function dispatch_settings_save( string $current_tab ) {
		$tab_handlers = array(
			'all'         => 'save_all_settings',
			'general'     => 'save_general_settings',
			'performance' => 'save_performance_settings',
			'systeme'     => 'save_system_settings',
			'maintenance' => 'save_maintenance_settings',
			'sauvegarde'  => 'save_backup_settings',
			'securite'    => 'save_security_settings',
			'pdf'         => 'save_pdf_settings',
			'contenu'     => 'save_content_settings',
			'licence'     => 'save_license_settings',
			'templates'   => 'save_templates_settings',
		);

		if ( ! array_key_exists( $current_tab, $tab_handlers ) ) {
			wp_send_json_error( array( 'message' => 'Onglet inconnu: ' . $current_tab ) );
			return array( 0, array() );
		}

		$method = $tab_handlers[ $current_tab ];
		$count  = $this->{$method}();
		$opts   = $this->get_saved_options_for_tab( $current_tab );
		return array( $count, $opts );
	}

	/**
	 * Handler pour la sauvegarde des paramètres Canvas
	 */
	public function handle_save_canvas_settings() {
		if ( ! $this->nonce_manager->validate_ajax_request( 'pdfib_canvas_settings' ) ) {
			wp_send_json_error( array( 'message' => self::err_nonce() ) );
			return;
		}

		try {
			$saved_count   = 0;
			$saved_options = array();

			foreach ( $this->get_canvas_setting_keys() as $setting_key ) {
				if ( ! isset( $GLOBALS['_POST'][ $setting_key ] ) ) {
					continue;
				}

				$raw_value = wp_unslash( $GLOBALS['_POST'][ $setting_key ] );
				$value     = $this->sanitize_canvas_setting_value( $setting_key, $raw_value );

				pdfib_update_option( $setting_key, $value );
				$settings                 = pdfib_get_option( 'pdfib_settings', array() );
				$settings[ $setting_key ] = $value;
				pdfib_update_option( 'pdfib_settings', $settings );

				$saved_options[ $setting_key ] = $value;
				++$saved_count;
			}

			if ( $saved_count > 0 ) {
				wp_send_json_success(
					array(
						'message'        => 'Paramètres Canvas sauvegardés avec succès',
						'saved_count'    => $saved_count,
						'saved_settings' => $saved_options,
						'new_nonce'      => $this->nonce_manager->generate_nonce(),
					)
				);
			} else {
				wp_send_json_error( array( 'message' => 'Aucun paramètre Canvas sauvegardé' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Sanitize et valide la valeur d'un paramètre Canvas.
	 *
	 * @param string          $key Clé du paramètre.
	 * @param string|string[] $raw Valeur brute POST (déjà wp_unslash).
	 * @return int|string Valeur nettoyée.
	 */
	private function sanitize_canvas_setting_value( string $key, $raw ) {
		$array_fields = array( 'pdfib_canvas_dpi', 'pdfib_canvas_formats', 'pdfib_canvas_orientations' );
		if ( in_array( $key, $array_fields, true ) ) {
			if ( 'pdfib_canvas_formats' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'extended_formats' ) ) {
				return 'A4';
			}

			if ( 'pdfib_canvas_orientations' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'extended_formats' ) ) {
				return 'portrait';
			}

			if ( 'pdfib_canvas_dpi' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'high_dpi' ) ) {
				$raw_values = is_array( $raw ) ? $raw : array_map( 'trim', explode( ',', (string) $raw ) );
				$raw_values = array_filter(
					array_map( 'sanitize_text_field', $raw_values ),
					static function ( string $value ): bool {
						return intval( $value ) <= 150;
					}
				);

				return ! empty( $raw_values ) ? implode( ',', $raw_values ) : '96';
			}

			return is_array( $raw )
				? implode( ',', array_map( 'sanitize_text_field', $raw ) )
				: sanitize_text_field( (string) $raw );
		}

		if ( 'pdfib_canvas_format' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'extended_formats' ) ) {
			return 'A4';
		}

		if ( 'pdfib_canvas_orientation' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'extended_formats' ) ) {
			return 'portrait';
		}

		if ( 'pdfib_canvas_allow_landscape' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'extended_formats' ) ) {
			return '0';
		}

		if ( 'pdfib_canvas_multi_select' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'advanced_selection' ) ) {
			return '0';
		}

		if ( 'pdfib_canvas_selection_mode' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'advanced_selection' ) ) {
			return 'single';
		}

		if ( 'pdfib_canvas_keyboard_shortcuts' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'keyboard_shortcuts' ) ) {
			return '0';
		}

		if ( 'pdfib_canvas_export_format' === $key && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'multi_format_export' ) ) {
			return 'pdf';
		}

		$value     = sanitize_text_field( (string) $raw );
		$grid_keys = array( 'pdfib_canvas_grid_enabled', 'pdfib_canvas_guides_enabled', 'pdfib_canvas_snap_to_grid' );
		if ( in_array( $key, $grid_keys, true ) && ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'grid_navigation' ) ) {
			return '0';
		}

		return $this->sanitize_canvas_value_by_suffix( $key, $value );
	}

	/**
	 * Applique la règle de nettoyage selon le suffixe de la clé canvas.
	 *
	 * @param string $key   Clé de l'option canvas.
	 * @param string $value Valeur déjà sanitisée (sanitize_text_field).
	 * @return int|string   Valeur nettoyée selon le type déduit du suffixe.
	 */
	private function sanitize_canvas_value_by_suffix( string $key, string $value ) {
		if ( strpos( $key, '_border_width' ) !== false ) {
			$result = max( 0, min( 10, intval( $value ) ) );
		} elseif ( strpos( $key, '_width' ) !== false || strpos( $key, '_height' ) !== false ) {
			$result = max( 100, min( 5000, intval( $value ) ) );
		} elseif ( strpos( $key, '_dpi' ) !== false ) {
			$result = max( 72, min( 600, intval( $value ) ) );
		} elseif ( strpos( $key, '_size' ) !== false || strpos( $key, '_limit' ) !== false ) {
			$result = intval( $value );
		} elseif ( strpos( $key, '_enabled' ) !== false || strpos( $key, '_transparent' ) !== false || strpos( $key, '_show_' ) !== false ) {
			$result = '1' === $value ? '1' : '0';
		} elseif ( strpos( $key, '_margin_' ) !== false ) {
			$result = max( 0, min( 500, intval( $value ) ) );
		} elseif ( strpos( $key, '_color' ) !== false ) {
			$result = preg_match( '/^#[a-fA-F0-9]{6}$/', $value ) ? $value : self::COLOR_WHITE;
		} else {
			$result = $value;
		}
		return $result;
	}



	/**
	 * Collecte les options sauvegardées pour un onglet spécifique.
	 *
	 * @param string $tab Nom de l'onglet.
	 * @return array<string,mixed>
	 */
	private function get_saved_options_for_tab( string $tab ) {
		$settings = pdfib_get_option( 'pdfib_settings', array() );

		switch ( $tab ) {
			case 'all':
				$result = $this->get_all_saved_options( $settings );
				break;
			case 'general':
				$result = $this->get_saved_options_general();
				break;
			case 'systeme':
				$result = $this->get_saved_options_systeme( $settings );
				break;
			case 'securite':
				$result = $this->get_saved_options_securite();
				break;
			case 'contenu':
				$result = $this->get_saved_options_contenu( $settings );
				break;
			case 'templates':
				$result = array( 'order_status_templates' => $settings['pdfib_order_status_templates'] ?? array() );
				break;
			case 'pdf':
				$result = array(
					'pdf_quality'         => pdfib_get_option( 'pdfib_pdf_quality', 'high' ),
					'default_format'      => pdfib_get_option( 'pdfib_default_format', 'A4' ),
					'default_orientation' => pdfib_get_option( 'pdfib_default_orientation', 'portrait' ),
				);
				break;
			case 'licence':
				$result = $this->get_saved_options_licence();
				break;
			default:
				$result = array();
				break;
		}
		return $result;
	}

	/**
	 * Retourne les options générales sauvegardées.
	 *
	 * @return array<string,mixed>
	 */
	private function get_saved_options_general(): array {
		return array(
			'company_phone_manual' => pdfib_get_option( 'pdfib_company_phone_manual', '' ),
			'company_siret'        => pdfib_get_option( 'pdfib_company_siret', '' ),
			'company_vat'          => pdfib_get_option( 'pdfib_company_vat', '' ),
			'company_rcs'          => pdfib_get_option( 'pdfib_company_rcs', '' ),
			'company_capital'      => pdfib_get_option( 'pdfib_company_capital', '' ),
			'cache_enabled'        => pdfib_get_option( 'pdfib_cache_enabled', '0' ),
			'cache_ttl'            => pdfib_get_option( 'pdfib_cache_ttl', 3600 ),
			'cache_compression'    => pdfib_get_option( 'pdfib_cache_compression', '1' ),
			'cache_auto_cleanup'   => pdfib_get_option( 'pdfib_cache_auto_cleanup', '1' ),
			'cache_max_size'       => pdfib_get_option( 'pdfib_cache_max_size', 100 ),
			'pdf_quality'          => pdfib_get_option( 'pdfib_pdf_quality', 'high' ),
			'default_format'       => pdfib_get_option( 'pdfib_default_format', 'A4' ),
			'default_orientation'  => pdfib_get_option( 'pdfib_default_orientation', 'portrait' ),
		);
	}

	/**
	 * Retourne les options système sauvegardées.
	 *
	 * @param array<string,mixed> $settings Tableau de settings courant.
	 * @return array<string,mixed>
	 */
	private function get_saved_options_systeme( array $settings ): array {
		return array(
			'cache_enabled'         => pdfib_get_option( 'pdfib_cache_enabled', '0' ),
			'cache_compression'     => pdfib_get_option( 'pdfib_cache_compression', '1' ),
			'cache_auto_cleanup'    => pdfib_get_option( 'pdfib_cache_auto_cleanup', '1' ),
			'cache_max_size'        => pdfib_get_option( 'pdfib_cache_max_size', 100 ),
			'cache_ttl'             => pdfib_get_option( 'pdfib_cache_ttl', 3600 ),
			'auto_maintenance'      => pdfib_get_option( 'pdfib_auto_maintenance', '1' ),
			'delete_on_uninstall'   => $settings['pdfib_delete_on_uninstall'] ?? '0',
			'auto_backup'           => pdfib_get_option( 'pdfib_auto_backup', '1' ),
			'auto_backup_frequency' => pdfib_get_option( 'pdfib_auto_backup_frequency', 'daily' ),
			'backup_retention'      => pdfib_get_option( 'pdfib_backup_retention', 30 ),
		);
	}

	/**
	 * Retourne les options de sécurité sauvegardées.
	 *
	 * @return array<string,mixed>
	 */
	private function get_saved_options_securite(): array {
		return array(
			'security_level'          => pdfib_get_option( 'pdfib_security_level', 'medium' ),
			'enable_logging'          => pdfib_get_option( 'pdfib_enable_logging', '1' ),
			'gdpr_enabled'            => pdfib_get_option( 'pdfib_gdpr_enabled', '0' ),
			'gdpr_consent_required'   => pdfib_get_option( 'pdfib_gdpr_consent_required', '0' ),
			'gdpr_data_retention'     => pdfib_get_option( 'pdfib_gdpr_data_retention', 365 ),
			'gdpr_audit_enabled'      => pdfib_get_option( 'pdfib_gdpr_audit_enabled', '0' ),
			'gdpr_encryption_enabled' => pdfib_get_option( 'pdfib_gdpr_encryption_enabled', '0' ),
			'gdpr_consent_analytics'  => pdfib_get_option( 'pdfib_gdpr_consent_analytics', '0' ),
			'gdpr_consent_templates'  => pdfib_get_option( 'pdfib_gdpr_consent_templates', '0' ),
			'gdpr_consent_marketing'  => pdfib_get_option( 'pdfib_gdpr_consent_marketing', '0' ),
		);
	}

	/**
	 * Retourne les options de licence sauvegardées.
	 *
	 * @return array<string,mixed>
	 */
	private function get_saved_options_licence(): array {
		return array(
			'license_key'              => pdfib_get_option( 'pdfib_license_key', '' ),
			'license_status'           => pdfib_get_option( 'pdfib_license_status', 'free' ),
			'license_data'             => pdfib_get_option( 'pdfib_license_data', array() ),
			'license_test_key'         => pdfib_get_option( 'pdfib_license_test_key', '' ),
			'license_test_key_expires' => pdfib_get_option( 'pdfib_license_test_key_expires', '' ),
			'license_email_reminders'  => pdfib_get_option( 'pdfib_license_email_reminders', '0' ),
			'license_test_mode'        => pdfib_get_option( 'pdfib_license_test_mode_enabled', '0' ),
		);
	}

	/**
	 * Retourne les options Canvas sauvegardées pour getAllSavedOptions.
	 *
	 * @return array
	 */
	private function get_all_saved_canvas_options(): array {
		return array(
			'canvas_width'                  => pdfib_get_option( 'pdfib_canvas_width', 794 ),
			'canvas_height'                 => pdfib_get_option( 'pdfib_canvas_height', 1123 ),
			'canvas_dpi'                    => pdfib_get_option( 'pdfib_canvas_dpi', 96 ),
			'canvas_format'                 => pdfib_get_option( 'pdfib_canvas_format', 'A4' ),
			'canvas_bg_color'               => pdfib_get_option( 'pdfib_canvas_bg_color', self::COLOR_WHITE ),
			'canvas_border_color'           => pdfib_get_option( 'pdfib_canvas_border_color', '#cccccc' ),
			'canvas_border_width'           => pdfib_get_option( 'pdfib_canvas_border_width', 1 ),
			'canvas_shadow_enabled'         => pdfib_get_option( 'pdfib_canvas_shadow_enabled', '0' ),
			'canvas_container_bg_color'     => pdfib_get_option( 'pdfib_canvas_container_bg_color', '#f8f9fa' ),
			'canvas_grid_enabled'           => pdfib_get_option( 'pdfib_canvas_grid_enabled', '1' ),
			'canvas_grid_size'              => pdfib_get_option( 'pdfib_canvas_grid_size', 20 ),
			'canvas_guides_enabled'         => pdfib_get_option( 'pdfib_canvas_guides_enabled', '1' ),
			'canvas_snap_to_grid'           => pdfib_get_option( 'pdfib_canvas_snap_to_grid', '1' ),
			'canvas_zoom_min'               => pdfib_get_option( 'pdfib_canvas_zoom_min', 25 ),
			'canvas_zoom_max'               => pdfib_get_option( 'pdfib_canvas_zoom_max', 500 ),
			'canvas_zoom_default'           => pdfib_get_option( 'pdfib_canvas_zoom_default', 100 ),
			'canvas_zoom_step'              => pdfib_get_option( 'pdfib_canvas_zoom_step', 25 ),
			'canvas_drag_enabled'           => pdfib_get_option( 'pdfib_canvas_drag_enabled', '1' ),
			'canvas_resize_enabled'         => pdfib_get_option( 'pdfib_canvas_resize_enabled', '1' ),
			'canvas_rotate_enabled'         => pdfib_get_option( 'pdfib_canvas_rotate_enabled', '1' ),
			'canvas_multi_select'           => pdfib_get_option( 'pdfib_canvas_multi_select', '1' ),
			'canvas_selection_mode'         => pdfib_get_option( 'pdfib_canvas_selection_mode', 'single' ),
			'canvas_keyboard_shortcuts'     => pdfib_get_option( 'pdfib_canvas_keyboard_shortcuts', '1' ),
			'canvas_export_quality'         => pdfib_get_option( 'pdfib_canvas_export_quality', 90 ),
			'canvas_export_format'          => pdfib_get_option( 'pdfib_canvas_export_format', 'png' ),
			'canvas_export_transparent'     => pdfib_get_option( 'pdfib_canvas_export_transparent', '0' ),
			'canvas_fps_target'             => pdfib_get_option( 'pdfib_canvas_fps_target', 60 ),
			'canvas_memory_limit_js'        => pdfib_get_option( 'pdfib_canvas_memory_limit_js', 50 ),
			'canvas_memory_limit_php'       => pdfib_get_option( 'pdfib_canvas_memory_limit_php', 256 ),
			'canvas_lazy_loading_editor'    => pdfib_get_option( 'pdfib_canvas_lazy_loading_editor', '1' ),
			'canvas_performance_monitoring' => pdfib_get_option( 'pdfib_canvas_performance_monitoring', '0' ),
			'canvas_error_reporting'        => pdfib_get_option( 'pdfib_canvas_error_reporting', '0' ),
		);
	}

	/**
	 * Retourne les options sauvegardées pour l'onglet "contenu".
	 *
	 * @param array $settings Tableau global pdfib_settings.
	 * @return array
	 */
	private function get_saved_options_contenu( array $settings ): array {
		return array_merge(
			$this->get_all_saved_canvas_options(),
			array(
				'canvas_max_size'          => pdfib_get_option( 'pdfib_canvas_max_size', 10000 ),
				'canvas_quality'           => pdfib_get_option( 'pdfib_canvas_quality', 90 ),
				'template_library_enabled' => $settings['pdfib_template_library_enabled'] ?? '1',
				'default_template'         => $settings['pdfib_default_template'] ?? 'blank',
			)
		);
	}

	/**
	 * Handler pour sauvegarder tous les paramètres
	 */
	public function handle_save_all_settings() {
		if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		try {
			$saved_count = $this->save_all_settings_from_flattened_data();

			$saved_options = $this->get_saved_options_for_tab( 'all' );

			wp_send_json_success(
				array(
					'message'        => 'Tous les paramètres sauvegardés avec succès',
					'saved_count'    => $saved_count,
					'saved_settings' => $saved_options,
					'new_nonce'      => $this->nonce_manager->generate_nonce(),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Erreur interne du serveur: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Sauvegarde tous les paramètres depuis les données POST aplaties
	 */
	private function save_all_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		return $this->save_all_settings_from_flattened_data();
	}

	/**
	 * Sauvegarde tous les paramètres depuis les données POST aplaties
	 */
	private function save_all_settings_from_flattened_data() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		$saved_count = 0;
		$settings    = pdfib_get_option( 'pdfib_settings', array() );

		$saved_count += $this->process_pdfib_settings_post_array( $settings );

		$field_rules  = $this->get_settings_field_rules();
		$saved_count += $this->process_bool_fields( $settings, $field_rules['bool_fields'] );
		$saved_count += $this->process_bool_fields( $settings, $field_rules['license_bool_fields'], 'pdfib_' );
		$saved_count += $this->process_scalar_fields( $settings, $field_rules );
		$saved_count += $this->extract_separate_option_keys( $settings );

		pdfib_update_option( 'pdfib_settings', $settings );
		return $saved_count;
	}

	/**
	 * Traite le tableau POST['pdfib_settings'] et le fusionne dans $settings.
	 *
	 * @param array $settings Tableau de settings passé par référence.
	 * @return int Nombre de champs traités.
	 */
	private function process_pdfib_settings_post_array( array &$settings ): int {
		$saved_count = 0;
		if ( ! isset( $GLOBALS['_POST']['pdfib_settings'] ) || ! is_array( $GLOBALS['_POST']['pdfib_settings'] ) ) {
			return 0;
		}

		$general_fields = array(
			'pdfib_company_phone_manual',
			'pdfib_company_siret',
			'pdfib_company_vat',
			'pdfib_company_rcs',
			'pdfib_company_capital',
		);
		foreach ( $general_fields as $f ) {
			if ( isset( $GLOBALS['_POST']['pdfib_settings'][ $f ] ) ) {
				pdfib_update_option( $f, sanitize_text_field( wp_unslash( $GLOBALS['_POST']['pdfib_settings'][ $f ] ) ) );
				unset( $GLOBALS['_POST']['pdfib_settings'][ sanitize_key( $f ) ] );
				++$saved_count;
			}
		}

		foreach ( wp_unslash( $GLOBALS['_POST']['pdfib_settings'] ) as $key => $val ) {
			if ( is_array( $val ) ) {
				$settings[ $key ] = ( 'pdfib_order_status_templates' === $key )
					? array_map( 'sanitize_text_field', $val )
					: $val;
			} elseif ( is_numeric( $val ) ) {
				$settings[ $key ] = intval( $val );
			} elseif ( '1' === $val || '0' === $val ) {
				$settings[ $key ] = $val;
			} else {
				$settings[ $key ] = sanitize_text_field( (string) $val );
			}
			++$saved_count;
		}
		unset( $GLOBALS['_POST'][ sanitize_key( 'pdfib_settings' ) ] );
		return $saved_count;
	}

	/**
	 * Traite un groupe de champs booléens POST et les fusionne dans $settings.
	 *
	 * @param array  $settings    Tableau de settings passé par référence.
	 * @param array  $bool_fields Liste des noms de champs.
	 * @param string $prefix      Préfixe optionnel à ajouter quand la clé ne commence pas par 'pdfib_'.
	 * @return int Nombre de champs traités.
	 */
	private function process_bool_fields( array &$settings, array $bool_fields, string $prefix = '' ): int {
		$count = 0;
		foreach ( $bool_fields as $field ) {
			if ( isset( $GLOBALS['_POST'][ $field ] ) && sanitize_text_field( wp_unslash( $GLOBALS['_POST'][ $field ] ) ) === '1' ) {
				$value = $prefix ? '1' : 1;
			} else {
				$value = $prefix ? '0' : 0;
			}

			$key              = ( $prefix && strpos( $field, 'pdfib_' ) !== 0 ) ? $prefix . $field : $field;
			$settings[ $key ] = $value;
			++$count;
		}
		return $count;
	}

	/**
	 * Traite les champs texte, entier et tableau depuis POST.
	 *
	 * @param array $settings   Tableau de settings passé par référence.
	 * @param array $field_rules Règles retournées par getSettingsFieldRules().
	 * @return int Nombre de champs traités.
	 */
	private function process_scalar_fields( array &$settings, array $field_rules ): int {
		return $this->process_post_text_fields( $settings, $field_rules['text_fields'] )
			+ $this->process_post_int_fields( $settings, $field_rules['int_fields'] )
			+ $this->process_post_array_fields( $settings, $field_rules['array_fields'] );
	}

	/**
	 * Traite les champs texte depuis POST et les insère dans le tableau de settings.
	 *
	 * @param array $settings Tableau de settings passé par référence.
	 * @param array $fields   Liste des clés de champs texte.
	 * @return int Nombre de champs traités.
	 */
	private function process_post_text_fields( array &$settings, array $fields ): int {
		$count = 0;
		foreach ( $fields as $key ) {
			if ( isset( $GLOBALS['_POST'][ $key ] ) ) {
				$option_key              = strpos( $key, 'pdfib_' ) === 0 ? $key : 'pdfib_' . $key;
				$settings[ $option_key ] = sanitize_text_field( wp_unslash( $GLOBALS['_POST'][ $key ] ) );
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Traite les champs entiers depuis POST et les insère dans le tableau de settings.
	 *
	 * @param array $settings Tableau de settings passé par référence.
	 * @param array $fields   Liste des clés de champs entiers.
	 * @return int Nombre de champs traités.
	 */
	private function process_post_int_fields( array &$settings, array $fields ): int {
		$count = 0;
		foreach ( $fields as $key ) {
			if ( isset( $GLOBALS['_POST'][ $key ] ) ) {
				$option_key              = strpos( $key, 'pdfib_' ) === 0 ? $key : 'pdfib_' . $key;
				$settings[ $option_key ] = intval( wp_unslash( $GLOBALS['_POST'][ $key ] ) );
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Traite les champs tableau depuis POST et les insère dans le tableau de settings.
	 *
	 * @param array $settings Tableau de settings passé par référence.
	 * @param array $fields   Liste des clés de champs tableau.
	 * @return int Nombre de champs traités.
	 */
	private function process_post_array_fields( array &$settings, array $fields ): int {
		$count = 0;
		foreach ( $fields as $key ) {
			if ( isset( $GLOBALS['_POST'][ $key ] ) ) {
				$option_key              = strpos( $key, 'pdfib_' ) === 0 ? $key : 'pdfib_' . $key;
				$raw                     = wp_unslash( $GLOBALS['_POST'][ $key ] );
				$settings[ $option_key ] = is_array( $raw ) ? array_map( 'sanitize_text_field', $raw ) : array();
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Extrait les clés de licence et de moteur PDF vers des options séparées.
	 *
	 * @param array $settings Tableau de settings passé par référence.
	 * @return int Nombre de clés extraites.
	 */
	private function extract_separate_option_keys( array &$settings ): int {
		$count        = 0;
		$license_keys = array(
			'pdfib_license_key',
			'pdfib_license_status',
			'pdfib_license_data',
			'pdfib_license_test_key',
			'pdfib_license_test_key_expires',
			'pdfib_license_email_reminders',
			'pdfib_license_test_mode_enabled',
		);
		foreach ( $license_keys as $k ) {
			if ( isset( $settings[ $k ] ) ) {
				pdfib_update_option( $k, $settings[ $k ] );
				unset( $settings[ $k ] );
				++$count;
			}
		}
		$engine_keys = array(
			'pdfib_engine',
			'pdfib_puppeteer_url',
			'pdfib_puppeteer_token',
			'pdfib_puppeteer_timeout',
			'pdfib_puppeteer_fallback',
		);
		foreach ( $engine_keys as $k ) {
			if ( isset( $settings[ $k ] ) ) {
				pdfib_update_option( $k, $settings[ $k ] );
				unset( $settings[ $k ] );
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Retourne les règles de champs pour la sauvegarde des paramètres.
	 *
	 * @return array<string,array<string>> Tableau associatif avec clés 'text_fields', 'int_fields', 'array_fields'.
	 */
	private function get_settings_field_rules(): array {
		return array(
			'text_fields'         => array(
				'pdfib_company_phone_manual',
				'pdfib_company_siret',
				'pdfib_company_vat',
				'pdfib_company_rcs',
				'pdfib_company_capital',
				'pdfib_pdf_quality',
				'pdfib_default_format',
				'pdfib_default_orientation',
				'pdfib_default_template',
				'pdfib_license_status',
				'pdfib_license_key',
				'pdfib_license_expires',
				'pdfib_license_activated_at',
				'pdfib_license_test_key',
				'pdfib_license_test_key_expires',
				'pdfib_license_reminder_email',
				'pdfib_last_maintenance',
				'pdfib_next_maintenance',
				'pdfib_last_backup',
				'pdfib_cache_last_cleanup',
				'pdfib_canvas_bg_color',
				'pdfib_canvas_border_color',
				'pdfib_canvas_container_bg_color',
				'pdfib_canvas_selection_mode',
				'pdfib_canvas_export_format',
				'pdfib_default_canvas_format',
				'pdfib_default_canvas_orientation',
				'pdfib_canvas_unit',
				'pdfib_canvas_format',
				'pdfib_engine',
				'pdfib_puppeteer_url',
				'pdfib_puppeteer_token',
			),
			'int_fields'          => array(
				'pdfib_cache_max_size',
				'pdfib_cache_ttl',
				'pdfib_zoom_min',
				'pdfib_zoom_max',
				'pdfib_zoom_default',
				'pdfib_zoom_step',
				'pdfib_canvas_grid_size',
				'pdfib_canvas_export_quality',
				'pdfib_canvas_fps_target',
				'pdfib_canvas_memory_limit_js',
				'pdfib_canvas_memory_limit_php',
				'pdfib_canvas_dpi',
				'pdfib_canvas_width',
				'pdfib_canvas_height',
				'pdfib_canvas_border_width',
				'pdfib_canvas_max_size',
				'pdfib_canvas_quality',
				'pdfib_puppeteer_timeout',
			),
			'bool_fields'         => $this->get_settings_bool_fields(),
			'array_fields'        => array( 'order_status_templates' ),
			'license_bool_fields' => array( 'license_email_reminders' ),
		);
	}

	/**
	 * Sauvegarde des paramètres généraux
	 */
	private function save_general_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );

		$raw = array(
			'company_phone_manual' => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['company_phone_manual'] ?? '' ) ),
			'company_siret'        => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['company_siret'] ?? '' ) ),
			'company_vat'          => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['company_vat'] ?? '' ) ),
			'company_rcs'          => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['company_rcs'] ?? '' ) ),
			'company_capital'      => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['company_capital'] ?? '' ) ),
		);

		$errors = $this->validate_general_settings( $raw );
		if ( ! empty( $errors ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Certains champs contiennent des valeurs invalides.', 'advanced-pdf-invoice-builder' ),
					'fields'  => $errors,
				),
				422
			);
			return 0;
		}

		foreach ( $raw as $key => $value ) {
			pdfib_update_option( 'pdfib_' . $key, $value );
		}

		return count( $raw );
	}

	/**
	 * Valide les champs de l'onglet général.
	 * Retourne un tableau associatif field => message pour chaque champ invalide.
	 * Tous les champs sont optionnels — seul un format incorrect est rejeté.
	 *
	 * @param array $data Données brutes sanitizées.
	 * @return array
	 */
	private function validate_general_settings( array $data ): array {
		$errors = array();

		// Téléphone (optionnel).
		$phone = $data['company_phone_manual'];
		if ( '' !== $phone && ! preg_match( '/^\+?[\d\s\-().]{7,20}$/', $phone ) ) {
			$errors['company_phone_manual'] = __( 'Numéro de téléphone invalide. Exemple : +33 1 23 45 67 89', 'advanced-pdf-invoice-builder' );
		}

		// SIRET : 14 chiffres (espaces ignorés).
		$siret = preg_replace( '/\s/', '', $data['company_siret'] );
		if ( '' !== $siret && ! preg_match( '/^\d{14}$/', $siret ) ) {
			$errors['company_siret'] = __( 'SIRET invalide : 14 chiffres requis.', 'advanced-pdf-invoice-builder' );
		}

		// TVA intracommunautaire.
		$vat = strtoupper( preg_replace( '/\s/', '', $data['company_vat'] ) );
		if ( '' !== $vat ) {
			$prefix       = substr( $vat, 0, 2 );
			$vat_patterns = array(
				'AT' => '/^ATU\d{8}$/',
				'BE' => '/^BE0?\d{9}$/',
				'BG' => '/^BG\d{9,10}$/',
				'CY' => '/^CY\d{8}[A-Z]$/',
				'CZ' => '/^CZ\d{8,10}$/',
				'DE' => '/^DE\d{9}$/',
				'DK' => '/^DK\d{8}$/',
				'EE' => '/^EE\d{9}$/',
				'EL' => '/^EL\d{9}$/',
				'ES' => '/^ES[0-9A-Z]\d{7}[0-9A-Z]$/',
				'FI' => '/^FI\d{8}$/',
				'FR' => '/^FR[0-9A-Z]{2}\d{9}$/',
				'GB' => '/^GB(\d{9}|\d{12}|GD\d{3}|HA\d{3})$/',
				'HR' => '/^HR\d{11}$/',
				'HU' => '/^HU\d{8}$/',
				'IE' => '/^IE\d[0-9A-Z+*]\d{5}[A-Z]{1,2}$/',
				'IT' => '/^IT\d{11}$/',
				'LT' => '/^LT(\d{9}|\d{12})$/',
				'LU' => '/^LU\d{8}$/',
				'LV' => '/^LV\d{11}$/',
				'MT' => '/^MT\d{8}$/',
				'NL' => '/^NL\d{9}B\d{2}$/',
				'PL' => '/^PL\d{10}$/',
				'PT' => '/^PT\d{9}$/',
				'RO' => '/^RO\d{2,10}$/',
				'SE' => '/^SE\d{12}$/',
				'SI' => '/^SI\d{8}$/',
				'SK' => '/^SK\d{10}$/',
			);
			$pattern      = $vat_patterns[ $prefix ] ?? '/^[A-Z]{2}[0-9A-Z]{4,13}$/';
			if ( ! preg_match( $pattern, $vat ) ) {
				$errors['company_vat'] = __( 'Numéro de TVA intracommunautaire invalide. Exemple : FR12345678901', 'advanced-pdf-invoice-builder' );
			}
		}

		// RCS : "Ville [A|B] 123456789".
		$rcs = $data['company_rcs'];
		if ( '' !== $rcs && ! preg_match( '/^[A-ZÀ-Ÿa-zà-ÿ][A-ZÀ-Ÿa-zà-ÿ\s\-]+ [AB] \d{9}$/u', $rcs ) ) {
			$errors['company_rcs'] = __( 'Format RCS invalide. Exemple : Lyon B 123456789', 'advanced-pdf-invoice-builder' );
		}

		// Capital social : nombre décimal optionnel suivi de €.
		$capital = $data['company_capital'];
		if ( '' !== $capital && ! preg_match( '/^\d[\d\s]*([,.]?\d{1,2})?\s*€?$/', $capital ) ) {
			$errors['company_capital'] = __( 'Capital social invalide. Exemple : 10 000 € ou 10000.00', 'advanced-pdf-invoice-builder' );
		}

		return $errors;
	}

	/**
	 * Sauvegarde des paramètres performance
	 */
	private function save_performance_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		$settings = array(
			'cache_enabled'       => isset( $GLOBALS['_POST']['cache_enabled'] ) ? '1' : '0',
			'cache_expiry'        => intval( wp_unslash( $GLOBALS['_POST']['cache_expiry'] ?? 0 ) ),
			'compression_enabled' => isset( $GLOBALS['_POST']['compression_enabled'] ) ? '1' : '0',
			'lazy_loading'        => isset( $GLOBALS['_POST']['lazy_loading'] ) ? '1' : '0',
			'preload_resources'   => isset( $GLOBALS['_POST']['preload_resources'] ) ? '1' : '0',
		);

		foreach ( $settings as $key => $value ) {
			pdfib_update_option( 'pdfib_' . $key, $value );
		}

		return count( $settings );
	}

	/**
	 * Sauvegarde des paramètres système
	 */
	private function save_system_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		$saved_count = 0;

		// Lire les champs depuis le tableau pdfib_settings envoyé par le formulaire.
		$post_settings = wp_unslash( $GLOBALS['_POST']['pdfib_settings'] ?? array() );         // Paramètres cache/performance/maintenance.
		$cache_fields  = array(
			'pdfib_cache_enabled'                 => sanitize_text_field( $post_settings['pdfib_cache_enabled'] ?? '0' ),
			'pdfib_cache_compression'             => sanitize_text_field( $post_settings['pdfib_cache_compression'] ?? '0' ),
			'pdfib_cache_auto_cleanup'            => sanitize_text_field( $post_settings['pdfib_cache_auto_cleanup'] ?? '0' ),
			'pdfib_cache_max_size'                => intval( $post_settings['pdfib_cache_max_size'] ?? 100 ),
			'pdfib_cache_ttl'                     => intval( $post_settings['pdfib_cache_ttl'] ?? 3600 ),
			'pdfib_performance_auto_optimization' => sanitize_text_field( $post_settings['pdfib_performance_auto_optimization'] ?? '0' ),
			'pdfib_systeme_auto_maintenance'      => sanitize_text_field( $post_settings['pdfib_systeme_auto_maintenance'] ?? '0' ),
			'pdfib_delete_on_uninstall'           => sanitize_text_field( $post_settings['pdfib_delete_on_uninstall'] ?? '0' ),
		);

		// Sauvegarder dans le tableau unifié pdfib_settings.
		$existing_settings = pdfib_get_option( 'pdfib_settings', array() );
		foreach ( $cache_fields as $key => $value ) {
			$existing_settings[ $key ] = $value;
		}
		pdfib_update_option( 'pdfib_settings', $existing_settings );
		$saved_count += count( $cache_fields );

		// Paramètres Puppeteer (top-level POST).
		$puppeteer_settings = array(
			'pdfib_engine'             => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['pdfib_engine'] ?? 'puppeteer' ) ),
			'pdfib_puppeteer_url'      => esc_url_raw( wp_unslash( $GLOBALS['_POST']['pdfib_puppeteer_url'] ?? '' ) ),
			'pdfib_puppeteer_token'    => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['pdfib_puppeteer_token'] ?? '' ) ),
			'pdfib_puppeteer_timeout'  => intval( wp_unslash( $GLOBALS['_POST']['pdfib_puppeteer_timeout'] ?? 30 ) ),
			'pdfib_puppeteer_fallback' => isset( $GLOBALS['_POST']['pdfib_puppeteer_fallback'] ) ? '1' : '0',
		);

		foreach ( $puppeteer_settings as $key => $value ) {
			$result = pdfib_update_option( $key, $value );
			if ( $result ) {
				++$saved_count;
			}
		}

		return $saved_count;
	}

	/**
	 * Sauvegarde des paramètres maintenance
	 */
	private function save_maintenance_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		$settings = array(
			'auto_cleanup'     => isset( $GLOBALS['_POST']['auto_cleanup'] ) ? '1' : '0',
			'cleanup_interval' => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['cleanup_interval'] ?? '' ) ),
			'log_retention'    => intval( wp_unslash( $GLOBALS['_POST']['log_retention'] ?? 0 ) ),
			'backup_enabled'   => isset( $GLOBALS['_POST']['backup_enabled'] ) ? '1' : '0',
		);

		foreach ( $settings as $key => $value ) {
			pdfib_update_option( 'pdfib_' . $key, $value );
		}

		return count( $settings );
	}

	/**
	 * Sauvegarde des paramètres de sauvegarde
	 */
	private function save_backup_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		$settings = array(
			'auto_backup'      => isset( $GLOBALS['_POST']['auto_backup'] ) ? '1' : '0',
			'backup_frequency' => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['backup_frequency'] ?? '' ) ),
			'backup_retention' => intval( wp_unslash( $GLOBALS['_POST']['backup_retention'] ?? 0 ) ),
			'cloud_backup'     => isset( $GLOBALS['_POST']['cloud_backup'] ) ? '1' : '0',
		);

		foreach ( $settings as $key => $value ) {
			pdfib_update_option( 'pdfib_' . $key, $value );
		}

		return count( $settings );
	}

	/**
	 * Sauvegarde des paramètres sécurité
	 */
	private function save_security_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		$post   = wp_unslash( $GLOBALS['_POST']['pdfib_settings'] ?? array() );
		$fields = array(
			'pdfib_security_level'          => sanitize_text_field( $post['pdfib_security_level'] ?? 'medium' ),
			'pdfib_enable_logging'          => sanitize_text_field( $post['pdfib_enable_logging'] ?? '0' ),
			'pdfib_gdpr_enabled'            => sanitize_text_field( $post['pdfib_gdpr_enabled'] ?? '0' ),
			'pdfib_gdpr_consent_required'   => sanitize_text_field( $post['pdfib_gdpr_consent_required'] ?? '0' ),
			'pdfib_gdpr_data_retention'     => intval( $post['pdfib_gdpr_data_retention'] ?? 2555 ),
			'pdfib_gdpr_audit_enabled'      => sanitize_text_field( $post['pdfib_gdpr_audit_enabled'] ?? '0' ),
			'pdfib_gdpr_encryption_enabled' => sanitize_text_field( $post['pdfib_gdpr_encryption_enabled'] ?? '0' ),
			'pdfib_gdpr_consent_analytics'  => sanitize_text_field( $post['pdfib_gdpr_consent_analytics'] ?? '0' ),
			'pdfib_gdpr_consent_templates'  => sanitize_text_field( $post['pdfib_gdpr_consent_templates'] ?? '0' ),
			'pdfib_gdpr_consent_marketing'  => sanitize_text_field( $post['pdfib_gdpr_consent_marketing'] ?? '0' ),
		);

		$existing = pdfib_get_option( 'pdfib_settings', array() );
		foreach ( $fields as $key => $value ) {
			$existing[ $key ] = $value;
		}
		pdfib_update_option( 'pdfib_settings', $existing );

		return count( $fields );
	}

	/**
	 * Sauvegarde des paramètres PDF
	 */
	private function save_pdf_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		$settings = array(
			'pdf_quality'          => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['pdf_quality'] ?? 'high' ) ),
			'pdf_page_size'        => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['pdf_page_size'] ?? 'A4' ) ),
			'pdf_orientation'      => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['pdf_orientation'] ?? 'portrait' ) ),
			'pdf_cache_enabled'    => isset( $GLOBALS['_POST']['pdf_cache_enabled'] ) ? '1' : '0',
			'pdf_compression'      => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['pdf_compression'] ?? 'medium' ) ),
			'pdf_metadata_enabled' => isset( $GLOBALS['_POST']['pdf_metadata_enabled'] ) ? '1' : '0',
			'pdf_print_optimized'  => isset( $GLOBALS['_POST']['pdf_print_optimized'] ) ? '1' : '0',
		);

		foreach ( $settings as $key => $value ) {
			pdfib_update_option( 'pdfib_' . $key, $value );
		}

		return count( $settings );
	}

	/**
	 * Sauvegarde des paramètres contenu
	 */
	private function save_content_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		return $this->save_canvas_content_settings() + $this->save_general_content_settings();
	}

	/**
	 * Sauvegarde les options canvas postées sous le préfixe pdfib_canvas_.
	 *
	 * @return int Nombre d'options sauvegardées.
	 */
	private function save_canvas_content_settings(): int {
		$saved_count = 0;
		foreach ( $GLOBALS['_POST'] as $key => $value ) {
			if ( strpos( $key, 'pdfib_canvas_' ) !== 0 || $this->is_modal_canvas_array_key( $key ) ) {
				continue;
			}

			pdfib_update_option( $key, $this->sanitize_content_canvas_setting_value( $key, $value ) );
			\wp_cache_delete( 'alloptions', 'options' ); // Invalider le cache des options.
			++$saved_count;
		}

		return $saved_count;
	}

	/**
	 * Vérifie si la clé canvas est gérée uniquement par le modal AJAX.
	 *
	 * @param string $key Clé d'option.
	 * @return bool
	 */
	private function is_modal_canvas_array_key( string $key ): bool {
		return in_array( $key, array( 'pdfib_canvas_dpi', 'pdfib_canvas_formats', 'pdfib_canvas_orientations' ), true );
	}

	/**
	 * Sanitise une valeur canvas selon la nature de la clé.
	 *
	 * @param string $key   Clé d'option.
	 * @param mixed  $value Valeur brute.
	 * @return mixed
	 */
	private function sanitize_content_canvas_setting_value( string $key, $value ) {
		if ( strpos( $key, '_color' ) !== false || strpos( $key, '_bg_color' ) !== false || strpos( $key, '_border_color' ) !== false ) {
			$result = sanitize_hex_color( $value );
		} elseif (
			strpos( $key, '_enabled' ) !== false || strpos( $key, '_activated' ) !== false ||
			strpos( $key, '_visible' ) !== false || strpos( $key, '_active' ) !== false
		) {
			$normalized = strtolower( (string) $value );
			$result     = in_array( $normalized, array( '1', 'on', 'true', 'yes' ), true ) ? '1' : '0';
		} elseif ( is_numeric( $value ) && strpos( $key, '_size' ) !== false ) {
			$result = intval( $value );
		} elseif ( is_numeric( $value ) && strpos( $key, '_zoom' ) !== false ) {
			$result = floatval( $value );
		} else {
			$result = sanitize_text_field( $value );
		}
		return $result;
	}

	/**
	 * Sauvegarde les paramètres de contenu généraux.
	 *
	 * @return int Nombre d'options sauvegardées.
	 */
	private function save_general_content_settings(): int {
		$settings = array(
			'canvas_max_size'          => intval( wp_unslash( $GLOBALS['_POST']['canvas_max_size'] ?? 0 ) ),
			'canvas_format'            => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['canvas_format'] ?? '' ) ),
			'canvas_quality'           => intval( wp_unslash( $GLOBALS['_POST']['canvas_quality'] ?? 0 ) ),
			'template_library_enabled' => isset( $GLOBALS['_POST']['template_library_enabled'] ) ? '1' : '0',
			'default_template'         => sanitize_text_field( wp_unslash( $GLOBALS['_POST']['default_template'] ?? 'blank' ) ),
		);

		$saved_count = 0;
		foreach ( $settings as $key => $value ) {
			if ( empty( $value ) && 0 !== $value && '0' !== $value ) {
				continue;
			}

			pdfib_update_option( 'pdfib_' . $key, $value );
			++$saved_count;
		}

		return $saved_count;
	}



	/**
	 * Sauvegarde les clés de licence de test en options dédiées et les retire du tableau unifié.
	 *
	 * @param array<string,mixed> $settings Settings unifiés.
	 * @return array<string,mixed>
	 */
	private function persist_and_strip_license_test_settings( array $settings ): array {
		foreach ( array( 'pdfib_license_test_key', 'pdfib_license_test_mode_enabled' ) as $license_key ) {
			if ( ! isset( $settings[ $license_key ] ) ) {
				continue;
			}

			pdfib_update_option( $license_key, $settings[ $license_key ] );
			unset( $settings[ $license_key ] );
		}

		return $settings;
	}

	/**
	 * Sauvegarde des paramètres licence
	 */
	private function save_license_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		// Notifications removed from the license settings — ensure any old option is deleted.
		pdfib_delete_option( 'pdfib_license_enable_notifications' );

		$saved_count = 0;

		// Récupérer les paramètres de licence actuels depuis le tableau unifié.
		$license_settings = pdfib_get_option( 'pdfib_settings', array() );

		// Paramètres de rappel par email - sauvegarder dans le tableau unifié pdfib_settings.
		if ( isset( $GLOBALS['_POST']['pdfib_settings']['pdfib_license_email_reminders'] ) ) {
			$email_reminders                                   = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['pdfib_settings']['pdfib_license_email_reminders'] ) );
			$license_settings['pdfib_license_email_reminders'] = $email_reminders;
			++$saved_count;
		}

		if ( isset( $GLOBALS['_POST']['pdfib_settings']['pdfib_license_reminder_email'] ) ) {
			$reminder_email                                   = sanitize_email( wp_unslash( $GLOBALS['_POST']['pdfib_settings']['pdfib_license_reminder_email'] ) );
			$license_settings['pdfib_license_reminder_email'] = $reminder_email;
			++$saved_count;
		}

		// Sauvegarder le tableau unifié.
		if ( $saved_count > 0 ) {
			if ( pdfib_update_option( 'pdfib_settings', $license_settings ) ) {
				return $saved_count;
			} else {
				return 0;
			}
		}

		return 0;
	}

	/**
	 * Handler pour supprimer les fichiers temporaires
	 */
	public function handle_remove_temp_files() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		try {
			$stats           = $this->remove_expired_plugin_temp_files();
			$transient_count = $this->delete_temp_cleanup_transients();
			$this->update_last_maintenance_setting();

			wp_send_json_success(
				array(
					'message' => $this->build_remove_temp_files_message( $stats['files'], $stats['size'], $transient_count ),
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => '❌ Erreur lors du nettoyage: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Supprime les fichiers temporaires plugin expirés (>24h).
	 *
	 * @return array{files:int,size:int}
	 */
	private function remove_expired_plugin_temp_files(): array {
		$temp_dir = wp_upload_dir()['basedir'] . '/pdf-builder-temp';
		$files    = 0;
		$size     = 0;

		if ( ! is_dir( $temp_dir ) ) {
			return array(
				'files' => 0,
				'size'  => 0,
			);
		}

		foreach ( glob( $temp_dir . '/*' ) as $file ) {
			if ( ! is_file( $file ) || ( time() - filemtime( $file ) ) <= 86400 ) {
				continue;
			}
			$file_size = filesize( $file );
			if ( wp_delete_file( $file ) ) {
				++$files;
				$size += $file_size;
			}
		}

		return array(
			'files' => $files,
			'size'  => $size,
		);
	}

	/**
	 * Nettoie les transients temporaires du plugin.
	 *
	 * @return int
	 */
	private function delete_temp_cleanup_transients(): int {
		return (int) pdfib_db()->query(
			pdfib_db()->prepare(
				"DELETE FROM %i WHERE option_name LIKE %s AND option_value = '1'",
				pdfib_db()->options,
				'_transient_pdfib_temp_%'
			)
		);
	}

	/**
	 * Construit le message de retour du nettoyage des fichiers temporaires.
	 *
	 * @param int $deleted_files   Nombre de fichiers supprimés.
	 * @param int $deleted_size    Taille totale libérée en octets.
	 * @param int $transient_count Nombre de transients supprimés.
	 * @return string
	 */
	private function build_remove_temp_files_message( int $deleted_files, int $deleted_size, int $transient_count ): string {
		$message  = "✅ Fichiers temporaires nettoyés\n";
		$message .= "• Fichiers supprimés: $deleted_files\n";
		$message .= '• Espace libéré: ' . number_format( $deleted_size / 1024, 1 ) . " KB\n";
		$message .= '• Transients nettoyés: ' . $transient_count;
		return $message;
	}

	/**
	 * Met à jour la date de dernière maintenance.
	 */
	private function update_last_maintenance_setting() {
		$settings                           = pdfib_get_option( 'pdfib_settings', array() );
		$settings['pdfib_last_maintenance'] = \current_time( 'mysql' );
		pdfib_update_option( 'pdfib_settings', $settings );
	}
	/**
	 * Handler pour programmer la prochaine maintenance
	 */
	public function handle_schedule_maintenance() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		try {
			// Programmer la prochaine maintenance pour dimanche prochain à 02:00.
			$next_sunday = strtotime( 'next Sunday 02:00' );
			if ( $next_sunday < time() ) {
				$next_sunday = strtotime( 'next Sunday 02:00', strtotime( '+1 week' ) );
			}

			pdfib_update_option( 'pdfib_next_maintenance', $next_sunday );

			// Mettre à jour dans le tableau unifié des paramètres.
			$settings                           = pdfib_get_option( 'pdfib_settings', array() );
			$settings['pdfib_next_maintenance'] = gmdate( self::DATE_FORMAT_DB, $next_sunday );
			pdfib_update_option( 'pdfib_settings', $settings );

			$message        = '📅 Prochaine maintenance programmée pour le ' . gmdate( 'd/m/Y à H:i', $next_sunday );
			$formatted_date = gmdate( 'd/m/Y à H:i', $next_sunday );

			wp_send_json_success(
				array(
					'message'          => $message,
					'next_maintenance' => $formatted_date,
					'timestamp'        => $next_sunday,
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => '❌ Erreur lors de la programmation: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Handler pour exporter le diagnostic
	 */
	public function handle_export_diagnostic() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		$lines = array();

		// Récupérer les erreurs PHP loggées dans wp-content/debug.log si disponible.
		// WP_DEBUG_LOG peut être un chemin personnalisé (string) ou true (emplacement par défaut).
		// On dérive wp-content/ depuis PDFIB_PLUGIN_DIR plutôt que d'utiliser WP_CONTENT_DIR.
		if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && WP_DEBUG_LOG ) {
			$wp_debug_log = WP_DEBUG_LOG;
		} else {
			$wp_debug_log = trailingslashit( dirname( dirname( untrailingslashit( PDFIB_PLUGIN_DIR ) ) ) ) . 'debug.log';
		}
		if ( file_exists( $wp_debug_log ) && is_readable( $wp_debug_log ) ) {
			$raw_lines = file( $wp_debug_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			$all_lines = array_slice( ( false !== $raw_lines ? $raw_lines : array() ), -200 );
			foreach ( $all_lines as $line ) {
				if ( strpos( $line, 'pdf-builder' ) !== false || strpos( $line, 'pdfib' ) !== false ) {
					$lines[] = esc_html( $line );
				}
			}
		}

		// Récupérer les dernières entrées depuis le tableau de bord (option WordPress).
		$db_logs = pdfib_get_option( 'pdfib_error_log', array() );
		if ( is_array( $db_logs ) ) {
			foreach ( array_slice( $db_logs, -100 ) as $entry ) {
				$lines[] = esc_html( wp_json_encode( $entry ) );
			}
		}

		// Infos système.
		$sysinfo = array(
			'wp_version'   => get_bloginfo( 'version' ),
			'php_version'  => PHP_VERSION,
			'plugin_ver'   => defined( 'PDFIB_VERSION' ) ? PDFIB_VERSION : 'unknown',
			'memory_limit' => ini_get( 'memory_limit' ),
			'max_exec'     => ini_get( 'max_execution_time' ),
			'upload_max'   => ini_get( 'upload_max_filesize' ),
			'timestamp'    => current_time( 'mysql' ),
		);

		wp_send_json_success(
			array(
				'system_info' => $sysinfo,
				'log_entries' => $lines,
				'log_count'   => count( $lines ),
			)
		);
	}

	/**
	 * Handler pour voir les logs
	 */
	public function handle_view_logs() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		$db_logs = pdfib_get_option( 'pdfib_error_log', array() );
		$entries = array();
		if ( is_array( $db_logs ) ) {
			$entries = array_reverse( array_slice( $db_logs, -50 ) );
		}

		wp_send_json_success(
			array(
				'logs'  => $entries,
				'count' => count( $entries ),
			)
		);
	}

	/**
	 * Handler de test AJAX
	 */
	public function handle_test_ajax() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		wp_send_json_success(
			array(
				'message'   => 'AJAX connection successful',
				'timestamp' => \current_time( 'mysql' ),
				'user_id'   => get_current_user_id(),
			)
		);
	}

	/**
	 * Handler pour créer une sauvegarde
	 */
	public function handle_create_backup() {
		// Debug: Log que le handler est appelé.

		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permissions insuffisantes.', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		try {
			$backup_manager = \PDFIB\Managers\PDF_Builder_Backup_Restore_Manager::get_instance();

			$options = array(
				'compress'          => isset( $GLOBALS['_POST']['compress'] ) && '1' === $GLOBALS['_POST']['compress'],
				'exclude_templates' => isset( $GLOBALS['_POST']['exclude_templates'] ) && '1' === $GLOBALS['_POST']['exclude_templates'],
				'exclude_settings'  => isset( $GLOBALS['_POST']['exclude_settings'] ) && '1' === $GLOBALS['_POST']['exclude_settings'],
				'exclude_user_data' => isset( $GLOBALS['_POST']['exclude_user_data'] ) && '1' === $GLOBALS['_POST']['exclude_user_data'],
			);

			$result = $backup_manager->createBackup( $options );

			if ( $result['success'] ) {
				wp_send_json_success(
					array(
						'message'    => $result['message'],
						'filename'   => $result['filename'],
						'size_human' => \size_format( $result['size'] ),
					)
				);
			} else {
				wp_send_json_error( array( 'message' => $result['message'] ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Erreur interne du serveur: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Handler pour lister les sauvegardes
	 */
	public function handle_list_backups() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permissions insuffisantes.', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		try {
			$backup_manager = \PDFIB\Managers\PDF_Builder_Backup_Restore_Manager::get_instance();
			$backups        = $backup_manager->listBackups();

			wp_send_json_success( array( 'backups' => $backups ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Erreur lors du chargement des sauvegardes.', 'advanced-pdf-invoice-builder' ) ) );
		}
	}

	/**
	 * Handler pour restaurer une sauvegarde
	 */
	public function handle_restore_backup() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permissions insuffisantes.', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		$filename = sanitize_file_name( wp_unslash( $GLOBALS['_POST']['filename'] ?? '' ) );

		if ( empty( $filename ) ) {
			wp_send_json_error( array( 'message' => __( 'Nom de fichier manquant.', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		try {
			$backup_manager = \PDFIB\Managers\PDF_Builder_Backup_Restore_Manager::get_instance();

			$options = array(
				'overwrite'         => isset( $GLOBALS['_POST']['overwrite'] ) && '1' === $GLOBALS['_POST']['overwrite'],
				'exclude_templates' => isset( $GLOBALS['_POST']['exclude_templates'] ) && '1' === $GLOBALS['_POST']['exclude_templates'],
				'exclude_settings'  => isset( $GLOBALS['_POST']['exclude_settings'] ) && '1' === $GLOBALS['_POST']['exclude_settings'],
				'exclude_user_data' => isset( $GLOBALS['_POST']['exclude_user_data'] ) && '1' === $GLOBALS['_POST']['exclude_user_data'],
			);

			$result = $backup_manager->restoreBackup( $filename, $options );

			if ( $result['success'] ) {
				wp_send_json_success(
					array(
						'message' => $result['message'],
						'results' => $result['results'],
					)
				);
			} else {
				wp_send_json_error( array( 'message' => $result['message'] ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Handler pour supprimer une sauvegarde
	 */
	public function handle_delete_backup() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permissions insuffisantes.', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		$filename = sanitize_file_name( sanitize_text_field( wp_unslash( $GLOBALS['_POST']['filename'] ?? '' ) ) );

		if ( empty( $filename ) ) {
			wp_send_json_error( array( 'message' => __( 'Nom de fichier manquant.', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		try {
			$backup_manager = \PDFIB\Managers\PDF_Builder_Backup_Restore_Manager::get_instance();
			$result         = $backup_manager->deleteBackup( $filename );

			if ( $result['success'] ) {
				wp_send_json_success( array( 'message' => $result['message'] ) );
			} else {
				wp_send_json_error( array( 'message' => $result['message'] ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}





	/**
	 * Supprime toutes les options concernées par un reset complet de licence.
	 */
	private function delete_license_options_for_cleanup() {
		foreach ( $this->get_license_cleanup_options() as $option ) {
			pdfib_delete_option( $option );
		}
	}

	/**
	 * Retourne les options supprimées lors d'un nettoyage complet de licence.
	 *
	 * @return string[]
	 */
	private function get_license_cleanup_options(): array {
		return array(
			'pdfib_license_key',
			'pdfib_license_status',
			'pdfib_license_expires',
			'pdfib_license_data',
			'pdfib_license_activated_at',
			'pdfib_license_email_reminders',
			'pdfib_license_reminder_email',
			'pdfib_license_test_key',
			'pdfib_license_test_key_expires',
			'pdfib_license_test_mode_enabled',
		);
	}

	/**
	 * Désactive le mode test dans les paramètres unifiés si présent.
	 */
	private function disable_test_mode_in_unified_settings() {
		$settings = pdfib_get_option( 'pdfib_settings', array() );
		if ( ! isset( $settings['pdfib_license_test_mode_enabled'] ) ) {
			return;
		}

		$settings['pdfib_license_test_mode_enabled'] = '0';
		pdfib_update_option( 'pdfib_settings', $settings );
	}

	/**
	 * Supprime les transients de licence.
	 */
	private function delete_license_transients() {
		$db = pdfib_db();
		$db->query( $db->prepare( 'DELETE FROM %i WHERE option_name LIKE %s', $db->options, $db->esc_like( '_transient_pdfib_license_' ) . '%' ) );
		$db->query( $db->prepare( 'DELETE FROM %i WHERE option_name LIKE %s', $db->options, $db->esc_like( '_transient_timeout_pdfib_license_' ) . '%' ) );
	}

	/**
	 * Handler pour nettoyer les fichiers temporaires
	 */
	public function handle_clear_temp_files() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		try {
			$temp_dirs = array(
				wp_upload_dir()['basedir'] . '/pdf-builder-pro/temp/',
				\get_temp_dir() . '/pdf-builder/',
			);
			$dir_stats = $this->clear_all_files_in_temp_dirs( $temp_dirs );
			$old_stats = $this->clear_old_upload_temp_files();
			$files     = $dir_stats['files'] + $old_stats['files'];
			$size      = $dir_stats['size'] + $old_stats['size'];

			wp_send_json_success(
				array(
					'message' => "Fichiers temporaires nettoyés: $files fichier(s) supprimé(s), " . \size_format( $size ) . ' libéré(s).',
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Vide tous les fichiers présents dans des répertoires temporaires.
	 *
	 * @param string[] $temp_dirs Répertoires à purger.
	 * @return array{files:int,size:int}
	 */
	private function clear_all_files_in_temp_dirs( array $temp_dirs ): array {
		$files = 0;
		$size  = 0;

		foreach ( $temp_dirs as $temp_dir ) {
			if ( ! is_dir( $temp_dir ) ) {
				continue;
			}
			foreach ( glob( $temp_dir . '*' ) as $file ) {
				if ( ! is_file( $file ) ) {
					continue;
				}
				$file_size = filesize( $file );
				if ( wp_delete_file( $file ) ) {
					++$files;
					$size += $file_size;
				}
			}
		}

		return array(
			'files' => $files,
			'size'  => $size,
		);
	}

	/**
	 * Supprime les fichiers temporaires upload de plus de 24h.
	 *
	 * @return array{files:int,size:int}
	 */
	private function clear_old_upload_temp_files(): array {
		$files = 0;
		$size  = 0;
		$all   = glob( wp_upload_dir()['basedir'] . '/pdf-builder-temp-*' );

		foreach ( $all as $temp_file ) {
			if ( ! is_file( $temp_file ) || ( time() - filemtime( $temp_file ) ) <= 86400 ) {
				continue;
			}
			$file_size = filesize( $temp_file );
			if ( wp_delete_file( $temp_file ) ) {
				++$files;
				$size += $file_size;
			}
		}

		return array(
			'files' => $files,
			'size'  => $size,
		);
	}

	/**
	 * Handler pour tester les routes
	 */
	public function handle_test_routes() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		$routes_tested = array();
		$failed_routes = array();

		$admin_routes = array(
			'admin.php?page=pdf-builder-settings' => 'Page principale des paramètres',
			'admin-ajax.php'                      => 'Endpoint AJAX WordPress',
		);

		foreach ( $admin_routes as $route => $description ) {
			$url      = \admin_url( $route );
			$response = \wp_remote_head( $url, array( 'timeout' => 5 ) );

			if ( \is_wp_error( $response ) ) {
				$error_message   = is_object( $response ) && method_exists( $response, 'get_error_message' ) ? $response->get_error_message() : 'Unknown error';
				$failed_routes[] = $route . ' (' . $error_message . ')';
			} else {
				$routes_tested[] = $route . ' (OK)';
			}
		}

		if ( empty( $failed_routes ) ) {
			wp_send_json_success(
				array(
					'message'       => 'Toutes les routes sont accessibles.',
					'routes_tested' => $routes_tested,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message'       => 'Routes inaccessibles détectées.',
					'routes_tested' => $routes_tested,
					'failed_routes' => $failed_routes,
				)
			);
		}
	}

	/**
	 * Handler pour tester les hooks disponibles
	 */
	public function handle_test_hook() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			wp_send_json_error( array( 'message' => esc_html( self::err_nonce() ) ) );
			return;
		}

		$request = $this->validate_hook_test_request();
		if ( ! $request ) {
			return;
		}

		$hook_type = $request['hook_type'];
		$hook_info = $this->collect_hook_test_info( $request['hook_name'] );

		wp_send_json_success(
			array(
				'type'           => $hook_type,
				'is_registered'  => $hook_info['is_registered'],
				'callback_count' => $hook_info['callback_count'],
				'callbacks'      => $hook_info['callbacks'],
			)
		);
	}

	/**
	 * Valide les paramètres d'un test de hook.
	 *
	 * @return array{hook_name: string, hook_type: string}|null
	 */
	private function validate_hook_test_request(): ?array {
		$hook_name = isset( $GLOBALS['_POST']['hookName'] ) ? sanitize_text_field( wp_unslash( $GLOBALS['_POST']['hookName'] ) ) : '';
		$hook_type = isset( $GLOBALS['_POST']['hookType'] ) ? sanitize_text_field( wp_unslash( $GLOBALS['_POST']['hookType'] ) ) : 'action';

		if ( empty( $hook_name ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Hook name is required', 'advanced-pdf-invoice-builder' ) ) );
			return null;
		}

		if ( ! in_array( $hook_name, $this->get_valid_test_hooks(), true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Hook non reconnu', 'advanced-pdf-invoice-builder' ) ) );
			return null;
		}

		return array(
			'hook_name' => $hook_name,
			'hook_type' => $hook_type,
		);
	}

	/**
	 * Retourne la liste des hooks autorisés pour le test.
	 *
	 * @return string[]
	 */
	private function get_valid_test_hooks(): array {
		return array(
			'pdfib_template_data',
			'pdfib_element_render',
			'pdfib_security_check',
			'pdfib_before_save',
			'pdfib_after_save',
			'pdfib_initialize_canvas',
			'pdfib_render_complete',
			'pdfib_pdf_generated',
			'pdfib_admin_page_loaded',
			'pdfib_cache_cleared',
		);
	}

	/**
	 * Convertit une callback WP en libellé lisible.
	 *
	 * @param mixed $callback Callback enregistrée.
	 * @return string
	 */
	private function normalize_hook_callback_name( $callback ): string {
		if ( is_string( $callback ) ) {
			return $callback;
		}

		if ( is_array( $callback ) && count( $callback ) >= 2 ) {
			$class_name  = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
			$method_name = (string) $callback[1];
			return $class_name . '::' . $method_name;
		}

		return ( $callback instanceof \Closure ) ? 'Closure' : 'Unknown';
	}

	/**
	 * Handler pour actualiser les logs
	 */
	public function handle_refresh_logs() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		try {
			$log_dirs = array(
				wp_upload_dir()['basedir'] . '/pdf-builder-pro/logs/',
				wp_upload_dir()['basedir'] . '/pdf-builder-logs/',
			);

			$logs_content = '';
			$max_lines    = 100;

			foreach ( $log_dirs as $log_dir ) {
				if ( is_dir( $log_dir ) ) {
					$files = glob( $log_dir . '*.log' );
					foreach ( $files as $file ) {
						if ( is_file( $file ) && filesize( $file ) > 0 ) {
							$lines         = file( $file );
							$recent_lines  = array_slice( $lines, -$max_lines );
							$logs_content .= '=== ' . basename( $file ) . " ===\n";
							$logs_content .= implode( '', $recent_lines );
							$logs_content .= "\n\n";
						}
					}
				}
			}

			if ( empty( $logs_content ) ) {
				$logs_content = 'Aucun log trouvé ou les logs sont vides.';
			}

			wp_send_json_success(
				array(
					'message'      => 'Logs actualisés avec succès.',
					'logs_content' => $logs_content,
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Handler pour vider les logs
	 */
	public function handle_clear_logs() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		try {
			$log_dirs = array(
				wp_upload_dir()['basedir'] . '/pdf-builder-pro/logs/',
				wp_upload_dir()['basedir'] . '/pdf-builder-logs/',
			);

			$cleared_files = 0;
			foreach ( $log_dirs as $log_dir ) {
				$cleared_files += $this->clear_log_directory( $log_dir );
			}

			wp_send_json_success(
				array(
					'message' => "$cleared_files fichier(s) de log supprimé(s) avec succès.",
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Supprime tous les fichiers *.log dans un répertoire donné.
	 *
	 * @param string $log_dir Chemin absolu du répertoire de logs.
	 * @return int Nombre de fichiers supprimés.
	 */
	private function clear_log_directory( string $log_dir ): int {
		if ( ! is_dir( $log_dir ) ) {
			return 0;
		}
		$files = glob( $log_dir . '*.log' );
		if ( ! is_array( $files ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $files as $file ) {
			if ( is_file( $file ) && wp_delete_file( $file ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Handler pour obtenir un nonce frais
	 */
	public function handle_get_fresh_nonce() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		try {
			$fresh_nonce = $this->nonce_manager->generate_nonce();

			wp_send_json_success(
				array(
					'nonce' => $fresh_nonce,
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Obtenir la taille de la base de données
	 */
	private function get_database_size() {

		$result = pdfib_db()->get_row(
			'
             SELECT
                 ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
         '
		);

		return $result ? $result->size_mb . ' MB' : 'Unknown';
	}

	/**
	 * Retourne le contenu par défaut d'un template.
	 *
	 * @param int $template_id Identifiant du template.
	 * @return string HTML du template par défaut.
	 */
	private function get_default_template_content( int $template_id ) {
		$templates = array(
			'invoice' => '<h1>Facture</h1><p>Template de facture par défaut</p>',
			'quote'   => '<h1>Devis</h1><p>Template de devis par défaut</p>',
			'receipt' => '<h1>Reçu</h1><p>Template de reçu par défaut</p>',
			'blank'   => '<div style="text-align: center; padding: 50px;"><h1>Template Vierge</h1><p>Commencez à créer votre PDF ici</p></div>',
		);

		return $templates[ $template_id ] ?? '<h1>Template</h1><p>Contenu par défaut</p>';
	}

	/**
	 * Validation nonce centralisée pour les endpoints PDF/Image/Preview.
	 * Accepte les clés nonce usuelles et deux actions compatibles.
	 *
	 * @return bool
	 */
	private function validate_pdf_ajax_nonce() {
		$nonce = '';

		if ( isset( $GLOBALS['_REQUEST']['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['nonce'] ) );
		} elseif ( isset( $GLOBALS['_REQUEST']['_ajax_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['_ajax_nonce'] ) );
		} elseif ( isset( $GLOBALS['_REQUEST']['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['_wpnonce'] ) );
		}

		if ( empty( $nonce ) ) {
			return false;
		}

		return wp_verify_nonce( $nonce, 'pdfib_ajax' ) || wp_verify_nonce( $nonce, 'pdfib_order_actions' );
	}

	/**
	 * Génère le PDF et retourne le contenu binaire (pour pièce jointe email, etc.)
	 *
	 * @param string|int $template_id Identifiant du template.
	 * @param int        $order_id    Identifiant de la commande WooCommerce.
	 * @return string|false  Contenu binaire du PDF, ou false en cas d'erreur.
	 */
	public function get_pdf_buffer( $template_id, $order_id ) {
		$result = false;
		try {
			if ( function_exists( 'wc_get_order' ) ) {
				$order    = wc_get_order( $order_id );
				$template = $order ? $this->get_template_by_id( $template_id ) : null;
				if ( $order && $template ) {
					$engine                    = \PDFIB\PDF\Engines\PDFEngineFactory::create();
					$this->current_engine_name = strtolower( $engine->get_name() );

					$html = $this->generate_template_html( $template, $order, 'pdf' );
					$html = $this->optimize_html( $html );

					$template_data = json_decode( $template['template_data'], true );
					$width         = $template_data['canvasWidth'] ?? 794;
					$height        = $template_data['canvasHeight'] ?? 1123;

					$pdf_content = $engine->generate(
						$html,
						array(
							'width'  => $width,
							'height' => $height,
						)
					);
					$result      = ( null !== $pdf_content ? $pdf_content : false );
				}
			}
		} catch ( \Exception $e ) {
			$this->debug_log( 'getPdfBuffer error: ' . $e->getMessage(), 'ERROR' );
		}
		return $result;
	}

	/**
	 * Handler pour obtenir le HTML de prévisualisation avec données de commande
	 */
	public function handle_get_preview_html() {
		$request = $this->validate_preview_html_request();
		if ( ! $request ) {
			return;
		}

		try {
			$payload = $this->build_preview_html_payload( $request['template_id'], $request['order_id'] );

			wp_send_json_success(
				array(
					'html'         => $payload['html'],
					'width'        => $payload['width'],
					'height'       => $payload['height'],
					'order_number' => $payload['order_number'],
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => 'Erreur lors de la génération du HTML',
					'details' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Valide la requête AJAX de prévisualisation HTML.
	 *
	 * @return array{template_id: string, order_id: int}|null
	 */
	private function validate_preview_html_request(): ?array {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission refusée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return null;
		}

		$template_id = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_id'] ?? '' ) );
		$order_id    = intval( wp_unslash( $GLOBALS['_POST']['order_id'] ?? 0 ) );
		$nonce_valid = $this->validate_pdf_ajax_nonce();

		if ( ! $nonce_valid || ! $template_id || ! $order_id ) {
			$code = ! $nonce_valid ? 403 : 400;
			$msg  = 403 === $code ? __( 'Nonce de sécurité invalide', 'advanced-pdf-invoice-builder' ) : 'Paramètres manquants';
			wp_send_json_error( array( 'message' => $msg ), $code );
			return null;
		}

		return array(
			'template_id' => $template_id,
			'order_id'    => $order_id,
		);
	}

	/**
	 * Construit le payload HTML de prévisualisation.
	 *
	 * @param string $template_id ID du template.
	 * @param int    $order_id    ID de la commande.
	 * @return array{html: string, width: int, height: int, order_number: string}
	 * @throws \PDFIB\Api\Exception Si WooCommerce inactif ou commande introuvable.
	 */
	private function build_preview_html_payload( string $template_id, int $order_id ): array {
		if ( ! function_exists( 'wc_get_order' ) ) {
			throw new \PDFIB\Api\Exception( 'WooCommerce n\'est pas actif' );
		}

		$order = $this->resolve_order( $order_id );
		if ( ! $order ) {
			throw new \PDFIB\Api\Exception( 'Commande introuvable' );
		}

		$template = $this->resolve_template( $template_id );
		if ( ! $template ) {
			throw new \PDFIB\Api\Exception( 'Modèle introuvable' );
		}

		$dimensions = $this->extract_preview_canvas_dimensions( $template );
		return array(
			'html'         => $this->resolve_template_html( $template, $order, 'html' ),
			'width'        => $dimensions['width'],
			'height'       => $dimensions['height'],
			'order_number' => $order->get_order_number(),
		);
	}

	/**
	 * Extrait les dimensions canvas à utiliser pour la preview HTML.
	 *
	 * @param array $template Données du template.
	 * @return array{width: int, height: int}
	 */
	private function extract_preview_canvas_dimensions( array $template ): array {
		if ( isset( $template['template_data'] ) ) {
			$template_data = is_string( $template['template_data'] )
				? json_decode( $template['template_data'], true )
				: $template['template_data'];
		} else {
			$template_data = array();
		}

		if ( ! is_array( $template_data ) ) {
			$template_data = array();
		}

		return array(
			'width'  => $template_data['canvasWidth'] ?? $template_data['canvas']['width'] ?? 794,
			'height' => $template_data['canvasHeight'] ?? $template_data['canvas']['height'] ?? 1123,
		);
	}

	/**
	 * Récupère un template spécifique.
	 *
	 * @param int $template_id Identifiant du template.
	 * @return array<string,mixed>|null Données du template ou null si introuvable.
	 */
	private function get_template_by_id( int $template_id ) {
		$table_name = pdfib_db()->prefix . 'pdfib_templates';
		$template   = pdfib_db()->get_row(
			pdfib_db()->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$template_id
			),
			ARRAY_A
		);

		return ( null !== $template ? $template : $this->get_fallback_template( $template_id ) );
	}

	/**
	 * Récupère (et mémoïse) une commande WooCommerce par ID.
	 * Évite les appels `wc_get_order()` redondants dans une même requête.
	 *
	 * @param int $order_id Identifiant de la commande WooCommerce.
	 * @return object|false Objet commande WooCommerce ou false si introuvable.
	 */
	private function resolve_order( $order_id ) {
		$order_id = (int) $order_id;
		if ( ! array_key_exists( $order_id, $this->order_cache ) ) {
			$this->order_cache[ $order_id ] = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
		}
		return $this->order_cache[ $order_id ];
	}

	/**
	 * Récupère (et mémoïse) un template par ID.
	 * Évite les requêtes SQL redondantes dans une même requête.
	 *
	 * @param int|string $template_id Identifiant du template.
	 * @return array<string,mixed>|null Tableau de données du template ou null si introuvable.
	 */
	private function resolve_template( $template_id ) {
		$key = (string) $template_id;
		if ( ! array_key_exists( $key, $this->template_cache ) ) {
			$this->template_cache[ $key ] = $this->get_template_by_id( $template_id );
		}
		return $this->template_cache[ $key ];
	}

	/**
	 * Génère et mémoïse l'HTML d'un template+commande pour un format donné.
	 * Sans ce cache, un cycle preview → PDF → PNG/JPG régénérerait le HTML
	 * à chaque appel (3× extraction OrderData + 3× boucles de rendu).
	 *
	 * @param array  $template Données du template.
	 * @param object $order    Objet commande WooCommerce.
	 * @param string $format   Format cible : 'html', 'pdf' ou 'image'.
	 * @return string HTML généré.
	 */
	private function resolve_template_html( $template, $order, $format = 'html' ): string {
		$tpl_id   = $template['id'] ?? 'inline';
		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : 0;
		$key      = $tpl_id . '|' . $order_id . '|' . $format;
		if ( ! isset( $this->html_cache[ $key ] ) ) {
			$this->html_cache[ $key ] = $this->generate_template_html( $template, $order, $format );
		}
		return $this->html_cache[ $key ];
	}

	/**
	 * Expose le HTML généré pour d'autres composants du plugin.
	 *
	 * @param array  $template Données du template.
	 * @param mixed  $order    Objet commande WooCommerce.
	 * @param string $format   Format cible : 'html', 'pdf' ou 'image'.
	 * @return string HTML généré.
	 */
	public function get_template_html_for_export( array $template, mixed $order, string $format = 'pdf' ): string {
		$html = $this->resolve_template_html( $template, $order, $format );

		return $this->optimize_html( $html );
	}

	/**
	 * Génère l'HTML du template avec les vraies données de commande.
	 *
	 * @param array  $template Données du template.
	 * @param mixed  $order    Objet commande WooCommerce.
	 * @param string $format   Format cible : 'html', 'pdf' ou 'image'.
	 * @return string HTML généré.
	 */
	private function generate_template_html( array $template, mixed $order, string $format = 'html' ) {
		$is_premium = false;

		require_once wp_normalize_path( PDFIB_PLUGIN_DIR . 'src/Generators/class-orderdataextractor.php' );
		// Mémoïse l'extraction (≈25 appels WC/DB) par order_id pour la requête en cours.
		static $order_data_cache = array();
		$order_id                = method_exists( $order, 'get_id' ) ? $order->get_id() : 0;
		if ( ! isset( $order_data_cache[ $order_id ] ) ) {
			$order_data_cache[ $order_id ] = ( new \PDFIB\Generators\OrderDataExtractor( $order ) )->get_all_data();
		}
		$all_data = $order_data_cache[ $order_id ];

		$template_data = null;
		if ( isset( $template['template_data'] ) ) {
			$template_data = is_string( $template['template_data'] )
				? json_decode( $template['template_data'], true )
				: $template['template_data'];
		}

		if ( ! $template_data || ! isset( $template_data['elements'] ) ) {
			return $this->generate_fallback_html( $template, $all_data );
		}

		$elements      = $template_data['elements'];
		$dimensions    = $this->get_pdf_canvas_dimensions( $template_data );
		$width         = $dimensions['width'];
		$height        = $dimensions['height'];
		$elements_html = '';
		foreach ( $elements as $element ) {
			$elements_html .= $this->render_element( $element, $all_data, $is_premium, $format );
		}

		return $this->build_pdf_html_document( $width, $height, $template['name'] ?? 'Document', $elements_html );
	}

	/**
	 * Extrait les dimensions du canvas depuis les données du template.
	 *
	 * @param array $template_data Données du template décodé.
	 * @return array{width: int, height: int}
	 */
	private function get_pdf_canvas_dimensions( array $template_data ): array {
		if ( isset( $template_data['canvas'] ) ) {
			return array(
				'width'  => $template_data['canvas']['width'] ?? 794,
				'height' => $template_data['canvas']['height'] ?? 1123,
			);
		}
		return array(
			'width'  => $template_data['canvasWidth'] ?? 794,
			'height' => $template_data['canvasHeight'] ?? 1123,
		);
	}

	/**
	 * Construit le document HTML complet pour l'export PDF.
	 *
	 * @param int    $width         Largeur du canvas en px.
	 * @param int    $height        Hauteur du canvas en px.
	 * @param string $template_name Titre du document.
	 * @param string $elements_html HTML des éléments déjà rendu.
	 * @return string HTML complet.
	 */
	private function build_pdf_html_document( int $width, int $height, string $template_name, string $elements_html ): string {

		return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html( $template_name ) . '</title>
    <style>@import url("https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=Roboto:wght@400;700&family=Open+Sans:wght@400;700&family=Lora:wght@400;700&family=Merriweather:wght@400;700&display=swap");' . $this->get_pdf_page_base_styles( $width, $height ) . $this->get_pdf_page_print_styles( $width, $height ) . '
    </style>
</head>
<body>
    <div class="pdf-canvas">' . $elements_html . '
    </div>
</body>
</html>';
	}

	/**
	 * Retourne les styles CSS de base du document PDF.
	 *
	 * @param int $w Largeur du canvas.
	 * @param int $h Hauteur du canvas.
	 * @return string Bloc CSS (sans balises <style>).
	 */
	private function get_pdf_page_base_styles( int $w, int $h ): string {
		return "
        @page { margin: 0; size: {$w}px {$h}px; }
        html {
            margin: 0; padding: 0; border: 0; box-sizing: border-box; font-size: 16px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #ffffff;
            font-family: \"DejaVu Sans\", \"Arial Unicode MS\", sans-serif;
            margin: 0; padding: 0; border: 0;
            overflow-y: auto; overflow-x: hidden;
            font-size: 16px; max-height: 100vh;
        }
        .pdf-canvas {
            position: relative; display: block;
            width: {$w}px; height: {$h}px;
            background: #ffffff; margin: 0 auto;
            padding: 0 !important; border: 0 !important;
            overflow: visible; transform: translate(0, 0);
        }
        .element {
            position: absolute !important; overflow: hidden;
            word-wrap: break-word; box-sizing: border-box !important;
        }
        table {
            border-collapse: collapse; width: 100%;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        th, td { padding: 8px; text-align: left; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        th { font-weight: bold; }";
	}

	/**
	 * Retourne les règles @media print pour le document PDF.
	 *
	 * @param int $w Largeur du canvas.
	 * @param int $h Hauteur du canvas.
	 * @return string Bloc @media print CSS.
	 */
	private function get_pdf_page_print_styles( int $w, int $h ): string {
		return "
        @media print {
            @page { margin: 0; size: {$w}px {$h}px; }
            body, html {
                margin: 0 !important; padding: 0 !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;
            }
            .pdf-canvas {
                position: relative !important;
                width: {$w}px !important; height: {$h}px !important;
                overflow: visible !important;
                page-break-after: avoid !important; page-break-before: avoid !important;
            }
            .element {
                position: absolute !important; display: block !important;
                visibility: visible !important; opacity: 1 !important;
                page-break-inside: avoid !important;
            }
            table, th, td, tr {
                -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;
            }
        }";
	}

	/**
	 * Helper: Extrait les propriétés de padding avec fallback.
	 *
	 * @param array $element Données de l'élément.
	 * @return array{horizontal: int, vertical: int}
	 */
	private function extract_padding( array $element ) {
		$default_padding = $element['padding'] ?? 12;
		return array(
			'horizontal' => $element['paddingHorizontal'] ?? $default_padding,
			'vertical'   => $element['paddingVertical'] ?? $default_padding,
		);
	}

	/**
	 * Helper: Extrait les propriétés de police avec fallback.
	 *
	 * @param array  $element  Données de l'élément.
	 * @param string $prefix   Préfixe de clé (ex: 'header', 'body').
	 * @param array  $defaults Valeurs par défaut pour family, size, weight, style.
	 * @return array{family: string, size: int|float, weight: string, style: string}
	 */
	private function extract_font_props( array $element, string $prefix = '', array $defaults = array() ) {
		// Récupérer la police par défaut pour le prefix.
		// ✅ FIX: Utiliser 'Arial' au lieu de self::FONT_DEFAULT pour matcher React/Canvas defaults.
		$default_family = $element['fontFamily'] ?? 'Arial';
		$default_size   = $defaults['size'] ?? 12;
		$default_weight = $defaults['weight'] ?? 'normal';
		$default_style  = $defaults['style'] ?? 'normal';

		// Construire les clés pour le prefix (ex: 'header' → 'headerFontFamily').
		$family_key = $prefix ? "{$prefix}FontFamily" : 'fontFamily';
		$size_key   = $prefix ? "{$prefix}FontSize" : 'fontSize';
		$weight_key = $prefix ? "{$prefix}FontWeight" : 'fontWeight';
		$style_key  = $prefix ? "{$prefix}FontStyle" : 'fontStyle';

		// Pour family, accepter aussi une version avec 'body' ou 'header' prefix du fontFamily.
		$specific_family = $element[ $family_key ] ?? null;
		if ( ! $specific_family && $prefix ) {
			// Fallback: utiliser fontFamily si la version spécifique n'existe pas.
			$specific_family = $element['fontFamily'] ?? $defaults['family'] ?? $default_family;
		}

		// Remplacer les guillemets doubles par des guillemets simples pour éviter de casser l'attribut style="...".
		$family_raw  = $specific_family ?? $default_family;
		$family_safe = str_replace( '"', "'", $family_raw );

		return array(
			'family' => $family_safe,
			'size'   => $element[ $size_key ] ?? $defaults['size'] ?? $default_size,
			'weight' => $element[ $weight_key ] ?? $defaults['weight'] ?? $default_weight,
			'style'  => $element[ $style_key ] ?? $defaults['style'] ?? $default_style,
		);
	}

	/**
	 * Helper: Extrait les propriétés de couleur avec fallback.
	 *
	 * @param array $element  Données de l'élément.
	 * @param array $defaults Couleurs par défaut (text, header, background, border).
	 * @return array{text: string, header: string, background: string, border: string}
	 */
	private function extract_colors( array $element, array $defaults = array() ) {
		return array(
			'text'       => $element['textColor'] ?? ( $defaults['text'] ?? self::COLOR_DARK_GRAY ),
			'header'     => $element['headerTextColor'] ?? ( $defaults['header'] ?? self::COLOR_NEAR_BLACK ),
			'background' => $element['backgroundColor'] ?? ( $defaults['background'] ?? self::COLOR_WHITE ),
			'border'     => $element['borderColor'] ?? ( $defaults['border'] ?? self::COLOR_LIGHT_GRAY ),
		);
	}

	/**
	 * Helper: Extrait les propriétés de layout.
	 *
	 * @param array $element Données de l'élément.
	 * @return array{layout: string, textAlign: string, verticalAlign: string, letterSpacing: float}
	 */
	private function extract_layout_props( array $element ) {
		return array(
			'layout'        => $element['layout'] ?? 'vertical',
			'textAlign'     => $element['textAlign'] ?? 'left',
			'verticalAlign' => $element['verticalAlign'] ?? 'top',
			'letterSpacing' => floatval( $element['letterSpacing'] ?? 0 ),
		);
	}

	/**
	 * Rendu des lignes produits dans le tableau produit.
	 *
	 * @param array  $products      Liste des produits.
	 * @param array  $element       Données de l'élément template.
	 * @param string $row_style_base Styles CSS de base pour les lignes.
	 * @param array  $column_flags  Tableau de flags de colonnes visibles.
	 * @param string $alt_bg        Couleur de fond alternée.
	 * @param string $bg_color      Couleur de fond standard.
	 * @param int    $row_index     Indice de ligne courant (passé par référence).
	 * @return string HTML des lignes produits.
	 */
	private function render_product_table_product_rows( array $products, array $element, string $row_style_base, array $column_flags, string $alt_bg, string $bg_color, int &$row_index ): string {
		$html      = '';
		$alternate = (bool) ( $element['showAlternatingRows'] ?? true );
		foreach ( $products as $product ) {
			$row_bg = $this->compute_row_bg( $alternate, $row_index, $alt_bg, $bg_color );
			$html  .= $this->render_product_row( $product, $row_style_base, $column_flags, $row_bg );
			++$row_index;
		}
		return $html;
	}

	/**
	 * Retourne la couleur de fond d'une ligne selon l'alternance.
	 *
	 * @param bool   $alternate Alternance activée ou non.
	 * @param int    $row_index Indice de ligne courant.
	 * @param string $alt_bg    Couleur de fond alternée.
	 * @param string $bg_color  Couleur de fond standard.
	 * @return string Couleur CSS de fond.
	 */
	private function compute_row_bg( bool $alternate, int $row_index, string $alt_bg, string $bg_color ): string {
		return $alternate && ( 1 === $row_index % 2 ) ? $alt_bg : $bg_color;
	}

	/**
	 * Génère le HTML d'une ligne produit (TR + TDs).
	 *
	 * @param array  $product       Données du produit.
	 * @param string $row_style_base Styles CSS de base pour la ligne.
	 * @param array  $column_flags  Tableau de flags de colonnes visibles.
	 * @param string $row_bg        Couleur de fond de la ligne.
	 * @return string HTML de la ligne produit.
	 */
	private function render_product_row( array $product, string $row_style_base, array $column_flags, string $row_bg ): string {
		$html = '<tr style="background: ' . $row_bg . ';">';
		if ( $column_flags['image'] ) {
			$img_html = $this->build_product_table_image_html( $product['image'] ?? '' );
			$html    .= '<td style="' . $row_style_base . ' text-align: center;">' . $img_html . '</td>';
		}
		if ( $column_flags['name'] ) {
			$html .= '<td style="' . $row_style_base . '">' . esc_html( $product['name'] ) . '</td>';
		}
		if ( $column_flags['sku'] ) {
			$html .= '<td style="' . $row_style_base . '">' . esc_html( $product['sku'] ?? 'N/A' ) . '</td>';
		}
		if ( $column_flags['description'] ) {
			$html .= '<td style="' . $row_style_base . '">' . esc_html( $product['description'] ?? '' ) . '</td>';
		}
		if ( $column_flags['quantity'] ) {
			$html .= '<td style="' . $row_style_base . ' text-align: center; width: 80px; max-width: 80px;">' . esc_html( $product['quantity'] ) . '</td>';
		}
		if ( $column_flags['price'] ) {
			$html .= '<td style="' . $row_style_base . self::CSS_TD_RIGHT . wp_kses_post( (string) $product['price'] ) . '</td>';
		}
		if ( $column_flags['total'] ) {
			$html .= '<td style="' . $row_style_base . self::CSS_TD_RIGHT . wp_kses_post( (string) $product['total'] ) . '</td>';
		}
		return $html . '</tr>';
	}

	/**
	 * Construit la cellule image d'une ligne produit.
	 *
	 * @param string $img_url URL ou data-URI de l'image produit.
	 * @return string HTML de la cellule image ou chaîne vide.
	 */
	private function build_product_table_image_html( string $img_url ): string {
		if ( '' === $img_url ) {
			return '';
		}
		if ( strpos( $img_url, 'data:' ) === 0 ) {
			return '<img src="' . esc_attr( $img_url ) . '" style="max-width: 50px; max-height: 50px; object-fit: contain;" />';
		}
		return '<img src="' . esc_url( $img_url ) . '" style="max-width: 50px; max-height: 50px; object-fit: contain;" />';
	}

	/**
	 * Rendu des lignes de frais dans le tableau produit.
	 *
	 * @param array  $fees          Liste des frais.
	 * @param array  $element       Données de l'élément template.
	 * @param string $row_style_base Styles CSS de base pour les lignes.
	 * @param array  $column_flags  Tableau de flags de colonnes visibles.
	 * @param string $alt_bg        Couleur de fond alternée.
	 * @param string $bg_color      Couleur de fond standard.
	 * @param int    $row_index     Indice de ligne courant (passé par référence).
	 * @return string HTML des lignes de frais.
	 */
	private function render_product_table_fee_rows( array $fees, array $element, string $row_style_base, array $column_flags, string $alt_bg, string $bg_color, int &$row_index ): string {
		$html      = '';
		$alternate = (bool) ( $element['showAlternatingRows'] ?? true );
		foreach ( $fees as $fee ) {
			$row_bg = $this->compute_row_bg( $alternate, $row_index, $alt_bg, $bg_color );
			$html  .= $this->render_fee_row( $fee, $row_style_base, $column_flags, $row_bg );
			++$row_index;
		}
		return $html;
	}

	/**
	 * Génère le HTML d'une ligne de frais (TR + TDs).
	 *
	 * @param array  $fee           Données du frais.
	 * @param string $row_style_base Styles CSS de base pour la ligne.
	 * @param array  $column_flags  Tableau de flags de colonnes visibles.
	 * @param string $row_bg        Couleur de fond de la ligne.
	 * @return string HTML de la ligne de frais.
	 */
	private function render_fee_row( array $fee, string $row_style_base, array $column_flags, string $row_bg ): string {
		$html = '<tr style="background: ' . $row_bg . ';">';
		if ( $column_flags['image'] ) {
			$html .= '<td style="' . $row_style_base . '"></td>';
		}
		if ( $column_flags['name'] ) {
			$html .= '<td style="' . $row_style_base . '">' . esc_html( $fee['name'] ) . '</td>';
		}
		if ( $column_flags['sku'] ) {
			$html .= '<td style="' . $row_style_base . '">FEE</td>';
		}
		if ( $column_flags['description'] ) {
			$html .= '<td style="' . $row_style_base . '"></td>';
		}
		if ( $column_flags['quantity'] ) {
			$html .= '<td style="' . $row_style_base . ' text-align: center; width: 80px; max-width: 80px;">1</td>';
		}
		if ( $column_flags['price'] ) {
			$html .= '<td style="' . $row_style_base . self::CSS_TD_RIGHT . wp_kses_post( (string) $fee['total'] ) . '</td>';
		}
		if ( $column_flags['total'] ) {
			$html .= '<td style="' . $row_style_base . self::CSS_TD_RIGHT . wp_kses_post( (string) $fee['total'] ) . '</td>';
		}
		return $html . '</tr>';
	}

	/**
	 * Rendu des informations client.
	 *
	 * @param array  $element    Données de l'élément template.
	 * @param array  $order_data Données extraites de la commande.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @param bool   $is_premium Indique si la licence est premium.
	 * @return string HTML de l'élément.
	 */
	private function render_customer_info_element( array $element, array $order_data, string $base_styles, bool $is_premium = false ) {
		unset( $is_premium );
		$padding      = $this->extract_padding( $element );
		$layout_props = $this->extract_layout_props( $element );
		$colors       = $this->extract_colors( $element );
		$header_font  = $this->extract_font_props(
			$element,
			'header',
			array(
				'size'   => 14,
				'weight' => 'bold',
			)
		);
		$body_font    = $this->extract_font_props( $element, 'body', array( 'size' => 12 ) );

		$show = array(
			'headers'     => $element['showHeaders'] ?? true,
			'fullName'    => $element['showFullName'] ?? true,
			'address'     => $element['showAddress'] ?? true,
			'email'       => $element['showEmail'] ?? true,
			'phone'       => $element['showPhone'] ?? true,
			'payment'     => $element['showPaymentMethod'] ?? false,
			'transaction' => $element['showTransactionId'] ?? false,
		);

		$lines            = $this->build_customer_info_lines( $layout_props['layout'], $show, $order_data['customer'], $order_data['billing'], $order_data );
		$container_styles = $this->build_element_container_styles( $base_styles, $padding, $layout_props, $colors, $body_font, $element );
		$header_style     = "color: {$colors['header']}; font-family: {$header_font['family']}; font-size: {$header_font['size']}px; font-weight: {$header_font['weight']}; font-style: {$header_font['style']}; margin-bottom: 4px;";
		$line_style       = "font-size: {$body_font['size']}px; font-family: {$body_font['family']}; font-weight: {$body_font['weight']}; font-style: {$body_font['style']}; color: {$colors['text']}; margin: 0; padding: 0;";
		if ( isset( $element['lineHeight'] ) && '' !== $element['lineHeight'] && 'normal' !== $element['lineHeight'] ) {
			$line_style .= " line-height: {$element['lineHeight']};";
		}

		$html = '<div class="element" style="' . $container_styles . '">';
		if ( $show['headers'] ) {
			$html .= '<div style="' . $header_style . '">Informations Client</div>';
		}
		foreach ( $lines as $line ) {
			$html .= '<div style="' . $line_style . '">' . $line . '</div>';
		}
		return $html . '</div>';
	}

	/**
	 * Construit les styles CSS du conteneur pour renderCustomerInfoElement et renderCompanyInfoElement.
	 *
	 * @param string $base_styles  Styles de base de l'élément.
	 * @param array  $padding      Padding extrait.
	 * @param array  $layout_props Props layout (textAlign, verticalAlign, letterSpacing...).
	 * @param array  $colors       Couleurs (text, background...).
	 * @param array  $body_font    Propriétés de police du corps.
	 * @param array  $element      Données de l'élément (pour showBackground, lineHeight).
	 * @return string Chaîne de styles CSS inline.
	 */
	private function build_element_container_styles( string $base_styles, array $padding, array $layout_props, array $colors, array $body_font, array $element ): string {
		$clean  = preg_replace( self::STR_PADDING_REGEX, '', $base_styles );
		$clean  = str_replace( self::CSS_IMPORTANT, '', $clean );
		$lsp    = $layout_props['letterSpacing'] ? " letter-spacing: {$layout_props['letterSpacing']}px;" : '';
		$styles = $clean
			. " padding: {$padding['vertical']}px {$padding['horizontal']}px;"
			. " text-align: {$layout_props['textAlign']};"
			. " color: {$colors['text']};"
			. " font-family: {$body_font['family']};"
			. " font-size: {$body_font['size']}px;"
			. " font-weight: {$body_font['weight']};"
			. " font-style: {$body_font['style']};"
			. self::CSS_BOX_SIZING
			. $lsp;

		if ( ( $element['showBackground'] ?? true ) !== false && ! empty( $colors['background'] ) && 'transparent' !== $colors['background'] ) {
			$styles .= self::CSS_BG_COLOR . $colors['background'] . ';';
		}
		$styles .= ' display: flex; flex-direction: column;';
		if ( 'middle' === $layout_props['verticalAlign'] ) {
			$styles .= ' justify-content: center;';
		} elseif ( 'bottom' === $layout_props['verticalAlign'] ) {
			$styles .= ' justify-content: flex-end;';
		} else {
			$styles .= ' justify-content: flex-start;';
		}
		return $styles;
	}

	/**
	 * Dispatcher : délègue la construction des lignes client au bon layout.
	 *
	 * @param string $layout     Type de layout : 'vertical', 'horizontal', ou autre.
	 * @param array  $show       Tableau de flags de champs visibles.
	 * @param array  $customer   Données client extraites de la commande.
	 * @param array  $billing    Données de facturation extraites de la commande.
	 * @param array  $order_data Données complètes de la commande.
	 * @return string[] Lignes HTML à afficher.
	 */
	private function build_customer_info_lines( string $layout, array $show, array $customer, array $billing, array $order_data ): array {
		if ( 'vertical' === $layout ) {
			return $this->build_customer_lines_vertical( $show, $customer, $billing, $order_data );
		}
		if ( 'horizontal' === $layout ) {
			return $this->build_customer_lines_horizontal( $show, $customer, $billing, $order_data );
		}
		return $this->build_customer_lines_compact( $show, $customer, $billing, $order_data );
	}

	/**
	 * Construit les lignes client en layout vertical.
	 *
	 * @param array $show       Tableau de flags de champs visibles.
	 * @param array $customer   Données client.
	 * @param array $billing    Données de facturation.
	 * @param array $order_data Données complètes de la commande.
	 * @return string[] Lignes HTML à afficher.
	 */
	private function build_customer_lines_vertical( array $show, array $customer, array $billing, array $order_data ): array {
		$lines = array();
		if ( $show['fullName'] ) {
			$lines[] = esc_html( $customer['full_name'] );
		}
		if ( $show['address'] ) {
			foreach ( explode( "\n", $billing['full_address'] ) as $l ) {
				if ( trim( $l ) ) {
					$lines[] = esc_html( $l );
				}
			}
		}
		if ( $show['email'] ) {
			$lines[] = esc_html( $customer['email'] );
		}
		if ( $show['phone'] && ! empty( $customer['phone'] ) ) {
			$lines[] = esc_html( $customer['phone'] );
		}
		if ( $show['payment'] ) {
			$lines[] = self::LABEL_PAYMENT . esc_html( $order_data['order']['payment_method'] ?? self::LABEL_CARD );
		}
		if ( $show['transaction'] ) {
			$lines[] = 'ID: ' . esc_html( $order_data['order']['transaction_id'] ?? 'N/A' );
		}
		return $lines;
	}

	/**
	 * Construit les lignes client en layout horizontal.
	 *
	 * @param array $show       Tableau de flags de champs visibles.
	 * @param array $customer   Données client.
	 * @param array $billing    Données de facturation.
	 * @param array $order_data Données complètes de la commande.
	 * @return string[] Lignes HTML à afficher.
	 */
	private function build_customer_lines_horizontal( array $show, array $customer, array $billing, array $order_data ): array {
		$line1 = $show['fullName'] ? esc_html( $customer['full_name'] ) : '';
		if ( $show['email'] ) {
			$line1 .= ( $line1 ? ' | ' : '' ) . esc_html( $customer['email'] );
		}
		$line2 = $show['address'] ? esc_html( str_replace( "\n", ', ', $billing['full_address'] ) ) : '';
		if ( $show['phone'] && ! empty( $customer['phone'] ) ) {
			$line2 .= ( $line2 ? ' | ' : '' ) . esc_html( $customer['phone'] );
		}
		$line3 = '';
		if ( $show['payment'] ) {
			$line3 .= self::LABEL_PAYMENT . esc_html( $order_data['order']['payment_method'] ?? self::LABEL_CARD );
		}
		if ( $show['transaction'] ) {
			$line3 .= ( $line3 ? ' | ' : '' ) . 'ID: ' . esc_html( $order_data['order']['transaction_id'] ?? 'N/A' );
		}
		return array_values( array_filter( array( $line1, $line2, $line3 ) ) );
	}

	/**
	 * Construit les lignes client en layout compact.
	 *
	 * @param array $show       Tableau de flags de champs visibles.
	 * @param array $customer   Données client.
	 * @param array $billing    Données de facturation.
	 * @param array $order_data Données complètes de la commande.
	 * @return string[] Lignes HTML à afficher.
	 */
	private function build_customer_lines_compact( array $show, array $customer, array $billing, array $order_data ): array {
		$lines = $show['fullName'] ? array( esc_html( $customer['full_name'] ) ) : array();
		$parts = array();
		if ( $show['address'] ) {
			$parts[] = esc_html( str_replace( "\n", ', ', $billing['full_address'] ) );
		}
		if ( $show['email'] ) {
			$parts[] = esc_html( $customer['email'] );
		}
		if ( $show['phone'] && ! empty( $customer['phone'] ) ) {
			$parts[] = esc_html( $customer['phone'] );
		}
		if ( $show['payment'] ) {
			$parts[] = self::LABEL_PAYMENT . esc_html( $order_data['order']['payment_method'] ?? self::LABEL_CARD );
		}
		if ( $show['transaction'] ) {
			$parts[] = 'ID: ' . esc_html( $order_data['order']['transaction_id'] ?? 'N/A' );
		}
		if ( $parts ) {
			$lines[] = implode( ' • ', $parts );
		}
		return $lines;
	}

	/**
	 * Rendu des informations entreprise.
	 *
	 * @param array  $element    Données de l'élément template.
	 * @param array  $order_data Données extraites de la commande (non utilisées ici).
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @param bool   $is_premium Indique si la licence est premium.
	 * @param string $format     Format cible : 'html', 'pdf' ou 'image'.
	 * @return string HTML de l'élément.
	 */
	private function render_company_info_element( array $element, array $order_data, string $base_styles, bool $is_premium = false, string $format = 'html' ) {
		unset( $order_data, $is_premium, $format );
		$layout_props = $this->extract_layout_props( $element );
		$colors       = $this->get_company_colors( $element );
		$header_font  = $this->extract_font_props(
			$element,
			'header',
			array(
				'size'   => 14,
				'weight' => 'bold',
			)
		);
		$body_font    = $this->extract_font_props( $element, 'body', array( 'size' => 12 ) );
		$company      = $this->build_company_data( $element );

		$show = array(
			'name'    => $element['showCompanyName'] ?? true,
			'address' => $element['showAddress'] ?? true,
			'email'   => $element['showEmail'] ?? true,
			'phone'   => $element['showPhone'] ?? true,
			'siret'   => $element['showSiret'] ?? true,
			'vat'     => $element['showVat'] ?? true,
			'rcs'     => $element['showRcs'] ?? true,
			'capital' => $element['showCapital'] ?? true,
		);

		$lines            = $this->build_company_info_lines( $element, $company, $show, $layout_props );
		$container_styles = $this->build_company_container_styles( $base_styles, $element, $layout_props, $colors, $body_font );
		$strong_style     = "color: {$colors['header']}; font-weight: {$header_font['weight']}; font-size: {$header_font['size']}px; font-family: {$header_font['family']}; font-style: {$header_font['style']};";
		$line_style       = 'margin: 0; padding: 0;';
		if ( isset( $element['lineHeight'] ) && '' !== $element['lineHeight'] && 'normal' !== $element['lineHeight'] ) {
			$line_style .= " line-height: {$element['lineHeight']};";
		}

		$html = '<div class="element" style="' . $container_styles . '">';
		foreach ( $lines as $line ) {
			$processed_line = str_replace( '<strong>', '<strong style="' . $strong_style . '">', $line );
			$html          .= '<div style="' . $line_style . '">' . $processed_line . '</div>';
		}
		return $html . '</div>';
	}

	/**
	 * Résout les couleurs de l'élément company_info en appliquant le thème prédéfini.
	 *
	 * @param array $element Données de l'élément.
	 * @return array{text: string, header: string, background: string, border: string}
	 */
	private function get_company_colors( array $element ): array {
		$themes = array(
			'corporate'    => array(
				'backgroundColor' => self::COLOR_WHITE,
				'borderColor'     => '#1f2937',
				'textColor'       => self::COLOR_DARK_GRAY,
				'headerTextColor' => self::COLOR_NEAR_BLACK,
			),
			'modern'       => array(
				'backgroundColor' => self::COLOR_WHITE,
				'borderColor'     => '#3b82f6',
				'textColor'       => '#1e40af',
				'headerTextColor' => '#1e3a8a',
			),
			'elegant'      => array(
				'backgroundColor' => self::COLOR_WHITE,
				'borderColor'     => '#8b5cf6',
				'textColor'       => '#6d28d9',
				'headerTextColor' => '#581c87',
			),
			'minimal'      => array(
				'backgroundColor' => self::COLOR_WHITE,
				'borderColor'     => self::COLOR_LIGHT_GRAY,
				'textColor'       => self::COLOR_DARK_GRAY,
				'headerTextColor' => self::COLOR_NEAR_BLACK,
			),
			'professional' => array(
				'backgroundColor' => self::COLOR_WHITE,
				'borderColor'     => '#059669',
				'textColor'       => '#047857',
				'headerTextColor' => '#064e3b',
			),
		);
		$theme  = $themes[ $element['theme'] ?? 'corporate' ] ?? $themes['corporate'];
		return array(
			'text'       => $element['textColor'] ?? $theme['textColor'],
			'header'     => $element['headerTextColor'] ?? $theme['headerTextColor'],
			'background' => $element['backgroundColor'] ?? $theme['backgroundColor'],
			'border'     => $element['borderColor'] ?? $theme['borderColor'],
		);
	}

	/**
	 * Collecte et normalise les données de l'entreprise depuis l'élément ou les options WP.
	 *
	 * @param array $element Données de l'élément.
	 * @return array{name: string, address: string, city: string, email: string, phone: string, siret: string, rcs: string, tva: string, capital: string}
	 */
	private function build_company_data( array $element ): array {
		$to_string    = static function ( $v ) {
			return is_array( $v ) ? ( $v[0] ?? '' ) : ( $v ?? '' );
		};
		$format_phone = static function ( $p ) {
			if ( empty( $p ) ) {
				return '';
			}
			$cleaned = preg_replace( '/\D/', '', $p );
			return $cleaned ? implode( '.', str_split( $cleaned, 2 ) ) : '';
		};
		$company      = array(
			'name'    => $to_string( $element['companyName'] ?? pdfib_get_option( 'pdfib_company_name', get_bloginfo( 'name' ) ) ),
			'address' => $to_string( $element['companyAddress'] ?? pdfib_get_option( 'pdfib_company_address', get_option( 'woocommerce_store_address', '' ) ) ),
			'city'    => $to_string( $element['companyCity'] ?? get_option( 'woocommerce_store_city', '' ) ),
			'email'   => $to_string( $element['companyEmail'] ?? pdfib_get_option( 'pdfib_company_email', get_option( 'admin_email', '' ) ) ),
			'phone'   => $format_phone( $to_string( $element['companyPhone'] ?? pdfib_get_option( 'pdfib_company_phone_manual', '' ) ) ),
			'siret'   => $to_string( $element['companySiret'] ?? pdfib_get_option( 'pdfib_company_siret', '' ) ),
			'rcs'     => $to_string( $element['companyRcs'] ?? pdfib_get_option( 'pdfib_company_rcs', '' ) ),
			'tva'     => $to_string( $element['companyTva'] ?? pdfib_get_option( 'pdfib_company_vat', '' ) ),
			'capital' => $to_string( $element['companyCapital'] ?? pdfib_get_option( 'pdfib_company_capital', '' ) ),
		);
		if ( $company['capital'] && strpos( $company['capital'], '€' ) === false ) {
			$company['capital'] .= ' €';
		}
		return $company;
	}

	/**
	 * Construit les styles CSS du conteneur company_info (bords, padding, alignement vertical).
	 *
	 * @param string $base_styles  Styles de positionnement de base.
	 * @param array  $element      Données de l'élément.
	 * @param array  $layout_props Props layout.
	 * @param array  $colors       Couleurs résolues.
	 * @param array  $body_font    Propriétés de police du corps.
	 * @return string Styles CSS inline.
	 */
	private function build_company_container_styles( string $base_styles, array $element, array $layout_props, array $colors, array $body_font ): string {
		$clean          = preg_replace( self::STR_PADDING_REGEX, '', $base_styles );
		$letter_spacing = $layout_props['letterSpacing'] ? " letter-spacing: {$layout_props['letterSpacing']}px;" : '';
		$styles         = $clean
			. "; text-align: {$layout_props['textAlign']};"
			. " color: {$colors['text']};"
			. " font-family: {$body_font['family']}; font-size: {$body_font['size']}px;"
			. " font-weight: {$body_font['weight']}; font-style: {$body_font['style']};"
			. ' box-sizing: border-box; width: 100%; height: 100%;'
			. $letter_spacing;

		if ( ( $element['showBackground'] ?? true ) !== false && ! empty( $colors['background'] ) && 'transparent' !== $colors['background'] ) {
			$styles .= self::CSS_BG_COLOR . $colors['background'] . ';';
		}
		$border_w = isset( $element['borderWidth'] ) ? (float) $element['borderWidth'] : 1;
		$border_s = $element['borderStyle'] ?? 'solid';
		if ( ( $element['showBorders'] ?? true ) !== false && $border_w > 0 ) {
			$styles .= self::CSS_BORDER . $border_w . 'px ' . $border_s . ' ' . $colors['border'] . ';';
		} else {
			$styles .= ' border: none;';
		}
		$pt      = isset( $element['paddingTop'] ) ? intval( $element['paddingTop'] ) : 8;
		$ph      = isset( $element['paddingHorizontal'] ) ? intval( $element['paddingHorizontal'] ) : 12;
		$pb      = isset( $element['paddingBottom'] ) ? intval( $element['paddingBottom'] ) : 12;
		$styles .= " padding: {$pt}px {$ph}px {$pb}px {$ph}px;";

		if ( 'middle' === $layout_props['verticalAlign'] ) {
			$styles .= ' display: flex; flex-direction: column; justify-content: center;';
		} elseif ( 'bottom' === $layout_props['verticalAlign'] ) {
			$styles .= ' display: flex; flex-direction: column; justify-content: flex-end;';
		}
		return $styles;
	}

	/**
	 * Dispatcher : délègue la construction des lignes entreprise au bon layout.
	 *
	 * @param array $element      Données de l'élément (non utilisées directement).
	 * @param array $company      Données entreprise extraites des options.
	 * @param array $show         Tableau de flags de champs visibles.
	 * @param array $layout_props Propriétés de layout (layout, textAlign, etc.).
	 * @return string[] Lignes HTML à afficher.
	 */
	private function build_company_info_lines( array $element, array $company, array $show, array $layout_props ): array {
		unset( $element );
		if ( 'vertical' === $layout_props['layout'] ) {
			return $this->build_company_lines_vertical( $company, $show );
		}
		if ( 'horizontal' === $layout_props['layout'] ) {
			return $this->build_company_lines_horizontal( $company, $show );
		}
		return $this->build_company_lines_compact( $company, $show );
	}

	/**
	 * Construit les lignes entreprise en layout vertical.
	 *
	 * @param array $company Données entreprise.
	 * @param array $show    Tableau de flags de champs visibles.
	 * @return string[] Lignes HTML à afficher.
	 */
	private function build_company_lines_vertical( array $company, array $show ): array {
		$lines = array();
		if ( $show['name'] ) {
			$lines[] = '<strong>' . esc_html( $company['name'] ) . '</strong>';
		}
		if ( $show['address'] && $company['address'] ) {
			$lines[] = esc_html( $company['address'] );
			if ( $company['city'] ) {
				$lines[] = esc_html( $company['city'] );
			}
		}
		// Champs with optional prefix label.
		$labeled_fields = array(
			'email'   => array( '', 'email' ),
			'phone'   => array( '', 'phone' ),
			'siret'   => array( self::LABEL_SIRET, 'siret' ),
			'vat'     => array( self::LABEL_TVA, 'tva' ),
			'rcs'     => array( self::LABEL_RCS, 'rcs' ),
			'capital' => array( 'Capital: ', 'capital' ),
		);
		foreach ( $labeled_fields as $flag => list($prefix, $field) ) {
			if ( $show[ $flag ] && ( $company[ $field ] ?? '' ) ) {
				$lines[] = $prefix . esc_html( $company[ $field ] );
			}
		}
		return $lines;
	}

	/**
	 * Construit les lignes entreprise en layout horizontal.
	 *
	 * @param array $company Données entreprise.
	 * @param array $show    Tableau de flags de champs visibles.
	 * @return string[] Lignes HTML à afficher.
	 */
	private function build_company_lines_horizontal( array $company, array $show ): array {
		$lines = array();
		if ( $show['name'] ) {
			$lines[] = '<strong>' . esc_html( $company['name'] ) . '</strong>';
		}
		$addr = $this->build_company_horizontal_address_line( $company, $show );
		if ( null !== $addr ) {
			$lines[] = $addr;
		}
		$contact = $this->build_company_horizontal_contact_line( $company, $show );
		if ( null !== $contact ) {
			$lines[] = $contact;
		}
		$legal = $this->build_company_horizontal_legal_line( $company, $show );
		if ( null !== $legal ) {
			$lines[] = $legal;
		}
		return $lines;
	}

	/**
	 * Construit la ligne d'adresse horizontale (null si vide).
	 *
	 * @param array $company Données entreprise.
	 * @param array $show    Tableau de flags de champs visibles.
	 * @return string|null Ligne HTML ou null si masquée.
	 */
	private function build_company_horizontal_address_line( array $company, array $show ): ?string {
		if ( ! $show['address'] || ! $company['address'] ) {
			return null;
		}
		$addr = esc_html( $company['address'] );
		if ( $company['city'] ) {
			$addr .= ', ' . esc_html( $company['city'] );
		}
		return $addr;
	}

	/**
	 * Construit la ligne de contact horizontale (null si vide).
	 *
	 * @param array $company Données entreprise.
	 * @param array $show    Tableau de flags de champs visibles.
	 * @return string|null Ligne HTML ou null si masquée.
	 */
	private function build_company_horizontal_contact_line( array $company, array $show ): ?string {
		$parts = array();
		foreach (
			array(
				'email' => 'email',
				'phone' => 'phone',
			) as $flag => $field
		) {
			if ( $show[ $flag ] && $company[ $field ] ) {
				$parts[] = esc_html( $company[ $field ] );
			}
		}
		return $parts ? implode( ' | ', $parts ) : null;
	}

	/**
	 * Construit la ligne légale horizontale (null si vide).
	 *
	 * @param array $company Données entreprise.
	 * @param array $show    Tableau de flags de champs visibles.
	 * @return string|null Ligne HTML ou null si masquée.
	 */
	private function build_company_horizontal_legal_line( array $company, array $show ): ?string {
		$parts        = array();
		$legal_fields = array(
			'siret'   => array( self::LABEL_SIRET, 'siret' ),
			'rcs'     => array( self::LABEL_RCS, 'rcs' ),
			'vat'     => array( self::LABEL_TVA, 'tva' ),
			'capital' => array( 'Capital: ', 'capital' ),
		);
		foreach ( $legal_fields as $flag => list($prefix, $field) ) {
			if ( $show[ $flag ] && $company[ $field ] ) {
				$parts[] = $prefix . esc_html( $company[ $field ] );
			}
		}
		return $parts ? implode( ' | ', $parts ) : null;
	}

	/**
	 * Construit les lignes entreprise en layout compact.
	 *
	 * @param array $company Données entreprise.
	 * @param array $show    Tableau de flags de champs visibles.
	 * @return string[] Lignes HTML à afficher.
	 */
	private function build_company_lines_compact( array $company, array $show ): array {
		$lines = $show['name'] ? array( '<strong>' . esc_html( $company['name'] ) . '</strong>' ) : array();
		$parts = array();
		if ( $show['address'] && $company['address'] ) {
			$parts[] = esc_html( $company['address'] );
		}
		if ( $show['email'] && $company['email'] ) {
			$parts[] = esc_html( $company['email'] );
		}
		if ( $show['phone'] && $company['phone'] ) {
			$parts[] = esc_html( $company['phone'] );
		}
		if ( $show['siret'] && $company['siret'] ) {
			$parts[] = self::LABEL_SIRET . esc_html( $company['siret'] );
		}
		if ( $show['vat'] && $company['tva'] ) {
			$parts[] = self::LABEL_TVA . esc_html( $company['tva'] );
		}
		if ( $show['rcs'] && $company['rcs'] ) {
			$parts[] = self::LABEL_RCS . esc_html( $company['rcs'] );
		}
		if ( $parts ) {
			$lines[] = implode( ' • ', $parts );
		}
		return $lines;
	}

	/**
	 * Rendu du logo entreprise.
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément logo ou chaîne vide.
	 */
	private function render_company_logo( array $element, string $base_styles ) {
		$src = $element['src'] ?? $element['logoUrl'] ?? '';
		if ( ! $src ) {
			return '';
		}
		$cw         = $element['width'] ?? 100;
		$ch         = $element['height'] ?? 100;
		$object_fit = $element['objectFit'] ?? 'contain';
		$opacity    = isset( $element['opacity'] ) ? floatval( $element['opacity'] ) : 1;
		$border_r   = isset( $element['borderRadius'] ) ? intval( $element['borderRadius'] ) : 0;
		$bg         = $element['backgroundColor'] ?? 'transparent';
		$image_data = $this->get_image_as_base64( $src );
		if ( $image_data ) {
			$src = $image_data;
		}

		$base_styles = preg_replace( '/\s*border[^;]*;/', '', $base_styles );
		$base_styles = preg_replace( '/\s*border-radius[^;]*;/', '', $base_styles );

		$dims  = $this->calculate_logo_render_dimensions( $cw, $ch, $object_fit );
		$lw    = $dims['width'];
		$lh    = $dims['height'];
		$ix    = ( $cw - $lw ) / 2;
		$iy    = ( $ch - $lh ) / 2;
		$outer = $base_styles . ( 'transparent' !== $bg ? self::CSS_BG_COLOR . esc_attr( $bg ) . ';' : '' ) . ' overflow: hidden; box-sizing: border-box;';
		$img   = 'position: absolute; left: ' . round( $ix, 2 ) . 'px; top: ' . round( $iy, 2 ) . 'px; width: ' . round( $lw, 2 ) . 'px; height: ' . round( $lh, 2 ) . 'px; display: block;';
		if ( $opacity < 1 ) {
			$img .= ' opacity: ' . esc_attr( (string) $opacity ) . ';';
		}
		if ( $border_r > 0 ) {
			$img .= ' border-radius: ' . esc_attr( (string) $border_r ) . 'px;';
		}

		return '<div class="element" style="' . $outer . '"><img src="' . esc_attr( $src ) . '" style="' . $img . '" /></div>';
	}

	/**
	 * Calcule les dimensions de rendu du logo selon le mode objectFit.
	 * Dimensions naturelles fixées à 512×512 (approximation carrée).
	 *
	 * @param float  $cw  Largeur du conteneur en px.
	 * @param float  $ch  Hauteur du conteneur en px.
	 * @param string $fit Valeur CSS object-fit.
	 * @return array{width: float, height: float}
	 */
	private function calculate_logo_render_dimensions( float $cw, float $ch, string $fit ): array {
		$nw = 512;
		$nh = 512;
		$ca = $cw / $ch;
		$ia = $nw / $nh;
		$w  = 0;
		$h  = 0;
		switch ( $fit ) {
			case 'cover':
				if ( $ca > $ia ) {
					$w = $cw;
					$h = $w / $ia;
				} else {
					$h = $ch;
					$w = $h * $ia;
				}
				break;
			case 'fill':
				$w = $cw;
				$h = $ch;
				break;
			case 'none':
				$w = min( $nw, $cw );
				$h = min( $nh, $ch );
				break;
			case 'scale-down':
				if ( $nw <= $cw && $nh <= $ch ) {
					$w = $nw;
					$h = $nh;
				} elseif ( $ca > $ia ) {
					$h = $ch;
					$w = $h * $ia;
				} else {
					$w = $cw;
					$h = $w / $ia;
				}
				break;
			case 'contain':
			default:
				if ( $ca > $ia ) {
					$h = $ch;
					$w = $h * $ia;
				} else {
					$w = $cw;
					$h = $w / $ia;
				}
				break;
		}
		return array(
			'width'  => $w,
			'height' => $h,
		);
	}

	/**
	 * Helper: Map text-align CSS values to flexbox justify-content values
	/**
	 * Rendu d'un rectangle.
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément rectangle.
	 */
	private function render_rectangle( array $element, string $base_styles ) {
		// Couleur de fond (fillColor ou backgroundColor).
		$background_color = $element['fillColor'] ?? $element['backgroundColor'] ?? 'transparent';

		// Bordure (strokeColor/strokeWidth ou borderColor/borderWidth).
		$border_color = $element['strokeColor'] ?? $element['borderColor'] ?? self::COLOR_BLACK;
		$border_width = $element['strokeWidth'] ?? $element['borderWidth'] ?? 0;

		// Border radius.
		$border_radius = $element['borderRadius'] ?? 0;

		$rect_styles = $base_styles . self::CSS_BG_COLOR . $background_color . ';';

		if ( $border_width > 0 ) {
			$rect_styles .= self::CSS_BORDER . $border_width . 'px solid ' . $border_color . ';';
		}

		if ( $border_radius > 0 ) {
			$rect_styles .= ' border-radius: ' . $border_radius . 'px;';
		}

		$rect_styles .= self::CSS_BOX_SIZING;

		return '<div class="element pdf-rectangle" style="' . $rect_styles . '"></div>';
	}

	/**
	 * Rendu d'un cercle.
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément cercle.
	 */
	private function render_circle( array $element, string $base_styles ) {
		// Couleur de fond (fillColor ou backgroundColor).
		$background_color = $element['fillColor'] ?? $element['backgroundColor'] ?? 'transparent';

		// Bordure (strokeColor/strokeWidth ou borderColor/borderWidth).
		$border_color = $element['strokeColor'] ?? $element['borderColor'] ?? self::COLOR_BLACK;
		$border_width = $element['strokeWidth'] ?? $element['borderWidth'] ?? 0;

		$circle_styles  = $base_styles . self::CSS_BG_COLOR . $background_color . ';';
		$circle_styles .= ' border-radius: 50%;'; // Clé pour faire un cercle.

		if ( $border_width > 0 ) {
			$circle_styles .= self::CSS_BORDER . $border_width . 'px solid ' . $border_color . ';';
		}

		$circle_styles .= self::CSS_BOX_SIZING;

		return '<div class="element pdf-circle" style="' . $circle_styles . '"></div>';
	}

	/**
	 * Rendu du type de document.
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément.
	 */
	private function render_document_type( array $element, string $base_styles ) {
		$title = $element['title'] ?? 'FACTURE';
		return '<div class="element" style="' . $base_styles . ' display: flex; align-items: center; justify-content: center;">' .
			'<strong>' . esc_html( $title ) . '</strong></div>';
	}

	/**
	 * Formate une date selon le format PHP spécifié (avec noms français).
	 *
	 * @param \DateTime $date   Objet date.
	 * @param string    $format Format de sortie.
	 * @return string Date formatée.
	 */
	private function format_date_php( $date, $format ) {
		$a  = $this->get_fr_date_arrays();
		$n  = (int) $date->format( 'n' );
		$w  = (int) $date->format( 'w' );
		$m  = $a['months'][ $n ];
		$ms = $a['months_short'][ $n ];
		$d  = $a['days'][ $w ];
		$ds = $a['days_short'][ $w ];
		switch ( $format ) {
			case self::DATE_FORMAT_FR:
				$result = $date->format( self::DATE_FORMAT_FR );
				break;
			case 'm/d/Y':
				$result = $date->format( 'm/d/Y' );
				break;
			case 'Y-m-d':
				$result = $date->format( 'Y-m-d' );
				break;
			case 'd-m-Y':
				$result = $date->format( 'd-m-Y' );
				break;
			case 'd.m.Y':
				$result = $date->format( 'd.m.Y' );
				break;
			case 'j F Y':
				$result = $date->format( 'j' ) . ' ' . $m . ' ' . $date->format( 'Y' );
				break;
			case 'l j F Y':
				$result = $d . ' ' . $date->format( 'j' ) . ' ' . $m . ' ' . $date->format( 'Y' );
				break;
			case 'F j, Y':
				$result = $m . ' ' . $date->format( 'j' ) . ', ' . $date->format( 'Y' );
				break;
			case 'D, M j, Y':
				$result = $ds . ', ' . $ms . ' ' . $date->format( 'j' ) . ', ' . $date->format( 'Y' );
				break;
			default:
				$result = $date->format( self::DATE_FORMAT_FR );
				break;
		}
		return $result;
	}

	/**
	 * Retourne les tableaux de noms de jours et mois en français.
	 *
	 * @return array{months: array<int,string>, months_short: array<int,string>, days: array<int,string>, days_short: array<int,string>}
	 */
	private function get_fr_date_arrays(): array {
		return array(
			'months'       => array(
				1  => 'janvier',
				2  => 'février',
				3  => 'mars',
				4  => 'avril',
				5  => 'mai',
				6  => 'juin',
				7  => 'juillet',
				8  => 'août',
				9  => 'septembre',
				10 => 'octobre',
				11 => 'novembre',
				12 => 'décembre',
			),
			'months_short' => array(
				1  => 'jan',
				2  => 'fév',
				3  => 'mar',
				4  => 'avr',
				5  => 'mai',
				6  => 'juin',
				7  => 'juil',
				8  => 'août',
				9  => 'sep',
				10 => 'oct',
				11 => 'nov',
				12 => 'déc',
			),
			'days'         => array(
				0 => 'dimanche',
				1 => 'lundi',
				2 => 'mardi',
				3 => 'mercredi',
				4 => 'jeudi',
				5 => 'vendredi',
				6 => 'samedi',
			),
			'days_short'   => array(
				0 => 'dim',
				1 => 'lun',
				2 => 'mar',
				3 => 'mer',
				4 => 'jeu',
				5 => 'ven',
				6 => 'sam',
			),
		);
	}

	/**
	 * Extrait les propriétés de police et layout pour renderOrderDate.
	 *
	 * @param array $element Données de l'élément.
	 * @return array Propriétés de présentation.
	 */
	private function extract_date_display_props( array $element ): array {
		$label_ff = $element['labelFontFamily'] ?? ( $element['fontFamily'] ?? self::FONT_DEFAULT );
		$date_ff  = $element['fontFamily'] ?? self::FONT_DEFAULT;
		return array(
			'show_label'               => $element['showLabel'] ?? true,
			'label_text'               => $element['labelText'] ?? 'Date de la facture :',
			'label_position'           => $element['labelPosition'] ?? 'left',
			'label_spacing'            => $element['labelSpacing'] ?? 8,
			'label_font_size'          => $element['labelFontSize'] ?? ( $element['fontSize'] ?? 12 ),
			'label_font_weight'        => $element['labelFontWeight'] ?? 'normal',
			'label_font_style'         => $element['labelFontStyle'] ?? 'normal',
			'label_color'              => $element['labelColor'] ?? ( $element['textColor'] ?? ( $element['color'] ?? self::COLOR_BLACK ) ),
			'label_font_with_fallback' => $this->add_font_fallbacks( $label_ff ),
			'date_font_size'           => $element['fontSize'] ?? 12,
			'date_font_weight'         => $element['fontWeight'] ?? 'normal',
			'date_font_style'          => $element['fontStyle'] ?? 'normal',
			'date_color'               => $element['textColor'] ?? ( $element['color'] ?? self::COLOR_BLACK ),
			'date_font_with_fallback'  => $this->add_font_fallbacks( $date_ff ),
			'text_align'               => $element['textAlign'] ?? 'left',
			'vertical_align'           => $element['verticalAlign'] ?? 'top',
			'padding_top'              => $element['padding']['top'] ?? $element['paddingTop'] ?? 0,
			'padding_right'            => $element['padding']['right'] ?? $element['paddingRight'] ?? 0,
			'padding_bottom'           => $element['padding']['bottom'] ?? $element['paddingBottom'] ?? 0,
			'padding_left'             => $element['padding']['left'] ?? $element['paddingLeft'] ?? 0,
		);
	}

	/**
	 * Rendu de texte dynamique.
	 *
	 * Utilise margin-bottom (= gap React).
	 * Formule IDENTIQUE à React: gap = fontSize × (lineHeight - 1)
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param array  $order_data  Données extraites de la commande (non utilisées ici).
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément.
	 */
	private function render_dynamic_text( array $element, array $order_data, string $base_styles ) {
		unset( $order_data );
		$text  = $element['text'] ?? $element['textTemplate'] ?? 'Signature du client';
		$text  = preg_replace( '/<br\s*\/?>/i', "\n", $text );
		$clean = preg_replace( self::STR_PADDING_REGEX, '', $base_styles );
		$clean = preg_replace( '/line-height:\s*[^;]+;/', '', $clean );
		$clean = str_replace( self::CSS_IMPORTANT, '', $clean );

		$font_size    = isset( $element['fontSize'] ) ? floatval( $element['fontSize'] ) : 12;
		$gap          = 4;
		$is_puppeteer = $this->is_puppeteer_engine();
		$line_height  = $is_puppeteer ? round( ( $font_size + $gap ) / $font_size, 2 ) : 1;

		$styles = $this->extract_position_text_styles( $clean );
		$lines  = preg_split( '/\r\n|\n|\r/', $text );

		$html  = '<div class="element" style="' . $styles['position'] . ' margin: 0; padding: 0; box-sizing: border-box; overflow: hidden;">';
		$html .= $this->render_dynamic_text_lines( $lines, $font_size, $line_height, $gap, $is_puppeteer, $styles['text'] );
		return $html . '</div>';
	}

	/**
	 * Génère les divs de lignes pour renderDynamicText.
	 *
	 * @param array  $lines       Lignes de texte à afficher.
	 * @param float  $font_size   Taille de police en px.
	 * @param float  $line_height Hauteur de ligne.
	 * @param int    $gap         Écart entre lignes en px.
	 * @param bool   $is_puppeteer Rendu Puppeteer ou non.
	 * @param string $text_style  Styles CSS de texte.
	 * @return string HTML des lignes.
	 */
	private function render_dynamic_text_lines( array $lines, float $font_size, float $line_height, int $gap, bool $is_puppeteer, string $text_style ): string {
		$html = '';
		if ( $is_puppeteer ) {
			foreach ( $lines as $line ) {
				$content = trim( $line ) === '' ? self::HTML_NBSP : esc_html( $line );
				$html   .= '<div style="margin: 0; padding: 0; font-size: ' . $font_size . 'px; line-height: ' . $line_height . '; ' . $text_style . '">' . $content . '</div>';
			}
		} else {
			$total = count( $lines );
			foreach ( $lines as $i => $line ) {
				$margin  = $i < $total - 1 ? " margin-bottom: {$gap}px;" : '';
				$content = trim( $line ) === '' ? self::HTML_NBSP : esc_html( $line );
				$html   .= '<div style="margin: 0; padding: 0; font-size: ' . $font_size . 'px; line-height: 1;' . $margin . ' ' . $text_style . '">' . $content . '</div>';
			}
		}
		return $html;
	}

	/**
	 * Retourne true : le moteur PDF actif est désormais toujours Puppeteer.
	 * Conservé pour compatibilité avec d'éventuels appelants externes.
	 */
	private function is_puppeteer_engine(): bool {
		return true;
	}

	/**
	 * Extrait les styles de positionnement et de texte depuis un bloc CSS inline nettoyé.
	 *
	 * @param string $clean Styles CSS inline sans padding ni !important.
	 * @return array{position: string, text: string}
	 */
	private function extract_position_text_styles( string $clean ): array {
		$m = array();
		foreach ( array( 'left', 'top', 'width', 'height' ) as $prop ) {
			preg_match( '/' . $prop . ':\s*[^;]+;/', $clean, $m[ $prop ] );
		}
		$position = 'position: absolute; '
			. ( $m['left'][0] ?? '' ) . ' '
			. ( $m['top'][0] ?? '' ) . ' '
			. ( $m['width'][0] ?? '' ) . ' '
			. ( $m['height'][0] ?? '' );

		$text_props = array( 'font-size', 'font-family', 'font-weight', 'font-style', 'text-align', 'color', 'text-decoration', 'text-transform', 'letter-spacing' );
		$text       = '';
		foreach ( $text_props as $prop ) {
			preg_match( '/' . $prop . ':\s*[^;]+;/', $clean, $tmp );
			if ( ! empty( $tmp[0] ) ) {
				$text .= $tmp[0] . ' ';
			}
		}
		return array(
			'position' => $position,
			'text'     => trim( $text ),
		);
	}

	/**
	 * Rendu des mentions légales.
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément.
	 */
	private function render_mentions( array $element, string $base_styles ) {
		$text  = $this->get_mention_text( $element );
		$text  = preg_replace( '/<br\s*\/?>/i', "\n", $text );
		$clean = preg_replace( self::STR_PADDING_REGEX, '', $base_styles );
		$clean = preg_replace( '/line-height:\s*[^;]+;/', '', $clean );
		$clean = str_replace( self::CSS_IMPORTANT, '', $clean );

		$font_size    = isset( $element['fontSize'] ) ? floatval( $element['fontSize'] ) : 10;
		$lh           = 1.1;
		$is_puppeteer = $this->is_puppeteer_engine();
		$styles       = $this->extract_position_text_styles( $clean );
		$lines        = explode( "\n", $text );

		$html  = '<div class="element" style="' . $styles['position'] . ' margin: 0; padding: 0; box-sizing: border-box; overflow: hidden;">';
		$html .= $this->render_mention_separator_html( $element );
		$html .= $this->render_mention_lines( $lines, $font_size, $lh, $is_puppeteer, $styles['text'] );
		return $html . '</div>';
	}

	/**
	 * Génère les divs de lignes pour renderMentions.
	 *
	 * @param array  $lines        Lignes de texte à afficher.
	 * @param float  $font_size    Taille de police en px.
	 * @param float  $lh          Hauteur de ligne.
	 * @param bool   $is_puppeteer Rendu Puppeteer ou non.
	 * @param string $text_style   Styles CSS de texte.
	 * @return string HTML des lignes.
	 */
	private function render_mention_lines( array $lines, float $font_size, float $lh, bool $is_puppeteer, string $text_style ): string {
		$html = '';
		if ( $is_puppeteer ) {
			foreach ( $lines as $line ) {
				$content = trim( $line ) === '' ? self::HTML_NBSP : esc_html( $line );
				$html   .= '<div style="margin: 0; padding: 0; font-size: ' . $font_size . 'px; line-height: ' . $lh . '; ' . $text_style . '">' . $content . '</div>';
			}
		} else {
			$mb    = round( $font_size * ( $lh - 1 ) );
			$total = count( $lines );
			foreach ( $lines as $i => $line ) {
				$margin  = $i < $total - 1 ? " margin-bottom: {$mb}px;" : '';
				$content = trim( $line ) === '' ? self::HTML_NBSP : esc_html( $line );
				$html   .= '<div style="margin: 0; padding: 0; font-size: ' . $font_size . 'px; line-height: 1;' . $margin . ' ' . $text_style . '">' . $content . '</div>';
			}
		}
		return $html;
	}

	/**
	 * Retourne le texte des mentions (dynamique ou personnalisé).
	 *
	 * @param array $element Données de l'élément mention.
	 * @return string Texte prêt à l'affichage.
	 */
	private function get_mention_text( array $element ): string {
		if ( ( $element['mentionType'] ?? 'custom' ) !== 'dynamic' ) {
			return $element['text'] ?? 'Conditions générales de vente disponibles sur demande.';
		}
		$sep   = $element['separator'] ?? ' • ';
		$parts = array();
		if ( $element['showEmail'] ?? true ) {
			$v = get_option( 'admin_email', '' );
			if ( $v ) {
				$parts[] = 'Email: ' . $v;
			}
		}
		if ( $element['showPhone'] ?? true ) {
			$v = pdfib_get_option( 'pdfib_company_phone_manual', '' );
			if ( $v ) {
				$parts[] = 'Tél: ' . $v;
			}
		}
		if ( $element['showSiret'] ?? true ) {
			$v = pdfib_get_option( 'pdfib_company_siret', '' );
			if ( $v ) {
				$parts[] = self::LABEL_SIRET . $v;
			}
		}
		if ( $element['showVat'] ?? true ) {
			$v = pdfib_get_option( 'pdfib_company_vat', '' );
			if ( $v ) {
				$parts[] = self::LABEL_TVA . $v;
			}
		}
		return $parts ? implode( $sep, $parts ) : 'Conditions générales de vente disponibles sur demande.';
	}

	/**
	 * Retourne le HTML du séparateur horizontal si activé dans l'élément.
	 *
	 * @param array $element Données de l'élément.
	 * @return string HTML du <hr> ou chaîne vide.
	 */
	private function render_mention_separator_html( array $element ): string {
		if ( ! ( $element['showSeparator'] ?? true ) ) {
			return '';
		}
		$sep_style = $element['separatorStyle'] ?? 'solid';
		$sep_color = $element['separatorColor'] ?? self::COLOR_LIGHT_GRAY;
		$sep_width = isset( $element['separatorWidth'] ) && $element['separatorWidth'] > 0 ? $element['separatorWidth'] : 1;
		$hr_style  = sprintf(
			'border: none; border-top: %dpx %s %s; margin: 0 0 10px 0; padding: 0; line-height: 0; height: %dpx; display: block;',
			$sep_width,
			$sep_style,
			$sep_color,
			$sep_width
		);
		return '<hr style="' . $hr_style . '" />';
	}

	/**
	 * ========================================
	 * FONCTIONS HELPER D'OPTIMISATION PDF
	 * ========================================
	 */

	/**
	 * Logger conditionnel — log uniquement si le mode debug est activé.
	 *
	 * @param string $message Message à logger.
	 * @param string $level   Niveau de log (INFO, WARNING, ERROR).
	 * @return void
	 */
	private function debug_log( $message, $level = 'INFO' ) {
		// Vérifier si les logs debug sont activés.
		$debug_enabled = pdfib_get_option( 'pdfib_debug_enabled', false );

		// Logger uniquement si debug activé OU en mode développement WordPress.
		if ( $debug_enabled || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			pdfib_debug_log( '[PDF Builder ' . $level . '] ' . $message );
		}
	}

	/**
	 * Génère un HTML de secours si le template n'est pas valide.
	 *
	 * @param array $template  Données du template (potentiellement invalide).
	 * @param array $all_data  Données complètes de la commande.
	 * @return string HTML de secours.
	 */
	private function generate_fallback_html( array $template, array $all_data ) {

		return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html( $template['name'] ?? 'Facture' ) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: ' . self::COLOR_WHITE . '; }
        h1 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
    </style>
</head>
<body>
    <h1>' . esc_html( $template['name'] ?? 'Facture' ) . '</h1>
    <p>Template canvas invalide - affichage de secours</p>
    <p>Commande: ' . esc_html( $all_data['order']['order_number'] ) . '</p>
    <p>Client: ' . esc_html( $all_data['customer']['full_name'] ) . '</p>
</body>
</html>';
	}

	/**
	 * Récupère la liste des commandes WooCommerce pour le select d'aperçu
	 */
	public function handle_get_orders_list() {
		try {
			if ( ! $this->validate_get_orders_list_request() ) {
				return;
			}

			// Récupérer les commandes récentes (limitées à 50 pour performance).
			$args = array(
				'limit'   => 50,
				'orderby' => 'date',
				'order'   => 'DESC',
				'status'  => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending' ),
			);

			$orders      = wc_get_orders( $args );
			$orders_list = array();

			foreach ( $orders as $order ) {
				$orders_list[] = $this->format_order_for_preview_select( $order );
			}

			wp_send_json_success( $orders_list );
		} catch ( \Exception $e ) {
			$this->debug_log( 'Erreur lors de la récupération des commandes: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => 'Erreur lors de la récupération des commandes' ) );
		}
	}

	/**
	 * Valide la requête de récupération des commandes.
	 *
	 * @return bool
	 */
	private function validate_get_orders_list_request(): bool {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request( 'get_orders_list' ) ) {
			wp_send_json_error( array( 'message' => self::err_nonce() ) );
			return false;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => self::err_perms() ) );
			return false;
		}

		return true;
	}

	/**
	 * Formate une commande pour le select d'aperçu admin.
	 *
	 * @param object $order Commande WooCommerce.
	 * @return array{id:int,number:string,customer:string,date:string,total:string}
	 */
	private function format_order_for_preview_select( $order ): array {
		$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		if ( '' === $customer_name ) {
			$billing_email = $order->get_billing_email();
			$customer_name = ( '' !== $billing_email ? $billing_email : 'Client anonyme' );
		}

		$total    = number_format( $order->get_total(), 2, ',', ' ' );
		$currency = get_woocommerce_currency_symbol( $order->get_currency() );
		$currency = html_entity_decode( $currency, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return array(
			'id'       => $order->get_id(),
			'number'   => $order->get_order_number(),
			'customer' => $customer_name,
			'date'     => $order->get_date_created()->date( self::DATE_FORMAT_FR ),
			'total'    => $total . ' ' . $currency,
		);
	}

	/**
	 * Test de connexion Puppeteer
	 * Handler AJAX pour tester uniquement le moteur Puppeteer
	 */
	public function handle_test_puppeteer() {
		try {
			// Vérifier nonce.
			check_ajax_referer( 'pdfib_test_puppeteer', '_ajax_nonce' );

			// Vérifier permissions.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => self::err_perms() ) );
				return;
			}

			$engine      = new \PDFIB\PDF\Engines\PuppeteerEngine();
			$test_result = $engine->test_connection();

			if ( $test_result['success'] ) {
				wp_send_json_success(
					array(
						'message' => sprintf(
							'Connexion Puppeteer réussie! 🎉<br>Service: %s<br>Temps de réponse: %dms',
							esc_html( \PDFIB\PDF\Engines\PuppeteerClient::SERVICE_BASE_URL ),
							isset( $test_result['response_time'] ) ? $test_result['response_time'] : 0
						),
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => sprintf(
							'Échec de connexion Puppeteer ❌<br>Service: %s<br>Erreur: %s',
							esc_html( \PDFIB\PDF\Engines\PuppeteerClient::SERVICE_BASE_URL ),
							esc_html( $test_result['message'] )
						),
					)
				);
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => 'Erreur lors du test: ' . $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Test de tous les moteurs PDF
	 * Handler AJAX pour tester tous les moteurs disponibles
	 */
	public function handle_test_all_engines() {
		try {
			if ( ! $this->validate_test_all_engines_request() ) {
				return;
			}

			// Charger la factory.
			require_once wp_normalize_path( PDFIB_PLUGIN_DIR . 'src/PDF/Engines/class-pdfenginefactory.php' );

			// Tester tous les moteurs.
			$results = \PDFIB\PDF\Engines\PDFEngineFactory::test_all_engines();

			if ( empty( $results ) ) {
				wp_send_json_error( array( 'message' => 'Aucun moteur n\'a pu être testé' ) );
				return;
			}

			wp_send_json_success( $this->format_engine_test_results( $results ) );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => 'Erreur lors des tests: ' . $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Valide la requête de test global des moteurs.
	 *
	 * @return bool
	 */
	private function validate_test_all_engines_request(): bool {
		check_ajax_referer( 'pdfib_test_engines', '_ajax_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => self::err_perms() ) );
			return false;
		}
		return true;
	}

	/**
	 * Formate les résultats de test des moteurs pour l'interface admin.
	 *
	 * @param array<string,array<string,mixed>> $results Résultats bruts des tests.
	 * @return array<string,array{success:bool,message:string}>
	 */
	private function format_engine_test_results( array $results ): array {
		$formatted = array();
		foreach ( $results as $engine_name => $result ) {
			$status  = ! empty( $result['success'] ) ? 'DISPONIBLE' : 'INDISPONIBLE';
			$icon    = ! empty( $result['success'] ) ? '✅' : '❌';
			$message = sprintf( '%s %s - %s', $icon, strtoupper( $engine_name ), $status );
			if ( isset( $result['response_time'] ) ) {
				$message .= sprintf( ' (Temps: %dms)', $result['response_time'] );
			}
			if ( empty( $result['success'] ) && isset( $result['message'] ) ) {
				$message .= sprintf( '<br>Raison: %s', esc_html( $result['message'] ) );
			}
			$formatted[ $engine_name ] = array(
				'success' => ! empty( $result['success'] ),
				'message' => $message,
			);
		}
		return $formatted;
	}

	/**
	 * Retourne le moteur PDF actuellement actif
	 * Utilisé pour afficher l'indicateur dans l'interface
	 */
	public function handle_get_active_engine() {
		try {
			// Récupérer le moteur configuré.
			$engine_name = pdfib_get_option( 'pdfib_engine', 'puppeteer' );

			// Tester si Puppeteer est disponible.
			$is_puppeteer_available = false;
			$puppeteer_url          = pdfib_get_option( 'pdfib_puppeteer_url', '' );

			if ( 'puppeteer' === $engine_name && ! empty( $puppeteer_url ) ) {
				require_once wp_normalize_path( PDFIB_PLUGIN_DIR . 'src/PDF/Engines/class-puppeteerengine.php' );
				$puppeteer              = new \PDFIB\PDF\Engines\PuppeteerEngine();
				$is_puppeteer_available = $puppeteer->is_available();
			}

			// Déterminer la disponibilité de Puppeteer.
			$effective_engine = 'puppeteer';
			if ( 'puppeteer' === $engine_name && ! $is_puppeteer_available ) {
				$effective_engine = 'puppeteer'; // Puppeteer est le moteur unique.
			}

			wp_send_json_success(
				array(
					'configured'   => $engine_name,
					'effective'    => $effective_engine,
					'available'    => $is_puppeteer_available,
					'display_name' => 'Puppeteer',
					'icon'         => '🚀',
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => self::err_prefix() . $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Ajoute des polices de secours (fallbacks) pour meilleure compatibilité Puppeteer.
	 * Utilise Google Fonts comme polices principales (garantit disponibilité sur tous serveurs).
	 * Exemple: "Courier New" -> "Courier Prime", Courier, monospace.
	 *
	 * @param string $font_name Nom de la police système à mapper.
	 * @return string Chaîne font-family avec fallbacks.
	 */
	private function add_font_fallbacks( string $font_name ) {
		// Mapping des polices système vers Google Fonts équivalentes avec fallbacks.
		$fallback_map = array(
			'Courier New'     => '"Courier Prime", Courier, monospace',
			'Times New Roman' => 'Lora, "Times New Roman", serif',
			'Arial'           => 'Roboto, Arial, sans-serif',
			'Helvetica'       => '"Open Sans", Helvetica, sans-serif',
			'Verdana'         => '"Open Sans", Verdana, sans-serif',
			'Georgia'         => 'Lora, Georgia, serif',
			'Comic Sans MS'   => 'cursive',
			'Trebuchet MS'    => '"Open Sans", sans-serif',
			'Impact'          => 'sans-serif',
			'Tahoma'          => 'sans-serif',
		);

		// Utiliser le mapping si disponible.
		if ( isset( $fallback_map[ $font_name ] ) ) {
			return $fallback_map[ $font_name ];
		}

		// Par défaut: ajouter guillemets et fallback générique.
		if ( strpos( $font_name, ' ' ) === false ) {
			return $font_name;
		}

		// Police avec espaces: détecter la famille générique.
		if ( stripos( $font_name, 'times' ) !== false || stripos( $font_name, 'georgia' ) !== false || stripos( $font_name, 'merriweather' ) !== false ) {
			$generic = 'serif';
		} elseif ( stripos( $font_name, 'courier' ) !== false || stripos( $font_name, 'mono' ) !== false ) {
			$generic = 'monospace';
		} else {
			$generic = 'sans-serif';
		}
		return "\"{$font_name}\", {$generic}";
	}

	// =========================================================================.
	// HANDLERS RGPD.
	// =========================================================================.

	/** Valide le nonce RGPD (action = pdfib_gdpr) */
	private function validate_gdpr_nonce(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => self::err_perms() ) );
			return false;
		}
		$nonce = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? $GLOBALS['_POST']['_wpnonce'] ?? '' ) );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'pdfib_gdpr' ) ) {
			wp_send_json_error( array( 'message' => self::err_nonce() ) );
			return false;
		}
		return true;
	}

	/**
	 * Ajoute une entrée dans le log d'audit.
	 *
	 * @param string $action  Action effectuée.
	 * @param string $details Détails supplémentaires optionnels.
	 * @return void
	 */
	private function append_audit_log( string $action, string $details = '' ): void {
		$logs = (array) get_option( 'pdfib_audit_log', array() );
		array_unshift(
			$logs,
			array(
				'date'    => current_time( self::DATE_FORMAT_DB ),
				'user'    => wp_get_current_user()->user_login ?? 'admin',
				'action'  => $action,
				'details' => $details,
			)
		);
		$logs = array_slice( $logs, 0, 200 ); // garder les 200 dernières entrées.
		update_option( 'pdfib_audit_log', $logs );
	}

	/**
	 * Supprimer les données personnelles stockées dans les options du plugin
	 */
	public function handle_delete_gdpr_data(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );
		if ( ! $this->validate_gdpr_nonce() ) {
			return;
		}

		$user     = wp_get_current_user();
		$settings = pdfib_get_option( 'pdfib_settings', array() );

		$keys_to_clear = array(
			'pdfib_company_name',
			'pdfib_company_address',
			'pdfib_company_phone',
			'pdfib_company_email',
		);
		$cleared       = array();
		foreach ( $keys_to_clear as $key ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$settings[ $key ] = '';
				$cleared[]        = $key;
			}
		}
		pdfib_update_option( 'pdfib_settings', $settings );

		$this->append_audit_log( 'CLEAR_DATA', 'Données effacées par ' . $user->user_login . ' — champs: ' . implode( ', ', ( null !== $cleared ? $cleared : array( '(aucun à effacer)' ) ) ) );

		wp_send_json_success(
			array(
				'message' => empty( $cleared )
					? 'Aucune donnée personnelle stockée à supprimer.'
					: 'Données personnelles supprimées : ' . implode( ', ', $cleared ) . '.',
			)
		);
	}

	/**
	 * Retourner l'état actuel des consentements RGPD
	 */
	public function handle_get_consent_status(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );
		if ( ! $this->validate_gdpr_nonce() ) {
			return;
		}

		$settings = pdfib_get_option( 'pdfib_settings', array() );

		$consents = array(
			array(
				'label' => 'RGPD activé',
				'value' => ( $settings['pdfib_gdpr_enabled'] ?? '0' ) === '1',
			),
			array(
				'label' => 'Consentement requis',
				'value' => ( $settings['pdfib_gdpr_consent_required'] ?? '0' ) === '1',
			),
			array(
				'label' => 'Audit Logging',
				'value' => ( $settings['pdfib_gdpr_audit_enabled'] ?? '0' ) === '1',
			),
			array(
				'label' => 'Chiffrement des données',
				'value' => ( $settings['pdfib_gdpr_encryption_enabled'] ?? '0' ) === '1',
			),
			array(
				'label' => 'Consentement Analytics',
				'value' => ( $settings['pdfib_gdpr_consent_analytics'] ?? '0' ) === '1',
			),
			array(
				'label' => 'Consentement Templates',
				'value' => ( $settings['pdfib_gdpr_consent_templates'] ?? '0' ) === '1',
			),
			array(
				'label' => 'Consentement Marketing',
				'value' => ( $settings['pdfib_gdpr_consent_marketing'] ?? '0' ) === '1',
			),
			array(
				'label' => 'Rétention des données (j)',
				'value' => (int) ( $settings['pdfib_gdpr_data_retention'] ?? 2555 ),
			),
		);

		wp_send_json_success( array( 'consents' => $consents ) );
	}

	/**
	 * Retourner les dernières entrées du log d'audit
	 */
	public function handle_get_audit_log(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );
		if ( ! $this->validate_gdpr_nonce() ) {
			return;
		}

		$logs  = (array) get_option( 'pdfib_audit_log', array() );
		$limit = min( 50, intval( wp_unslash( $GLOBALS['_POST']['limit'] ?? 50 ) ) );
		$logs  = array_slice( $logs, 0, $limit );

		wp_send_json_success(
			array(
				'logs'  => $logs,
				'total' => count( $logs ),
			)
		);
	}

	/**
	 * Exporter le log d'audit en CSV
	 */
	public function handle_export_audit_log(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );
		if ( ! $this->validate_gdpr_nonce() ) {
			return;
		}

		$logs = (array) get_option( 'pdfib_audit_log', array() );

		$csv = "Date,Utilisateur,Action,Détails\n";
		foreach ( $logs as $entry ) {
			$csv .= sprintf(
				"%s,%s,%s,\"%s\"\n",
				$entry['date'] ?? '',
				$entry['user'] ?? '',
				$entry['action'] ?? '',
				str_replace( '"', '""', $entry['details'] ?? '' )
			);
		}

		$this->append_audit_log( 'EXPORT_AUDIT_LOG', "Log d'audit exporté par " . ( wp_get_current_user()->user_login ?? 'admin' ) );

		wp_send_json_success(
			array(
				'csv'      => $csv,
				'filename' => 'audit-log-' . gmdate( 'Y-m-d' ) . '.csv',
				'count'    => count( $logs ),
			)
		);
	}


	/**
	 * Handler pour basculer la maintenance automatique
	 */
	public function handle_toggle_auto_maintenance() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		try {
			$current_state = pdfib_get_option( 'pdfib_auto_maintenance', '1' );
			$new_state     = '1' === $current_state ? '0' : '1';

			// Mettre à jour dans le tableau unifié des paramètres.
			$settings                                   = pdfib_get_option( 'pdfib_settings', array() );
			$settings['pdfib_systeme_auto_maintenance'] = $new_state;
			pdfib_update_option( 'pdfib_settings', $settings );

			// Garder aussi l'option individuelle pour compatibilité.
			pdfib_update_option( 'pdfib_auto_maintenance', $new_state );

			$message = '1' === $new_state ? '✅ Maintenance automatique activée' : '❌ Maintenance automatique désactivée';

			wp_send_json_success( array( 'message' => $message ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => '❌ Erreur lors du basculement: ' . esc_html( $e->getMessage() ) ) );
		}
	}



	/**
	 * Rendu d'une ligne de séparation.
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément.
	 */
	private function render_line( array $element, string $base_styles ) {
		$color = $element['strokeColor'] ?? self::COLOR_BLACK;
		$width = $element['strokeWidth'] ?? 1;
		$style = $element['borderStyle'] ?? $element['style'] ?? 'solid';

		// Version simplifiée sans flex pour meilleure compatibilité impression.
		// Créer une div interne centrée verticalement avec position relative.
		$line_styles = $base_styles . ' overflow: hidden;';

		// Calculer la position verticale pour centrer la ligne dans le conteneur.
		$element_height = $element['height'] ?? 20;
		$top_offset     = ( $element_height - $width ) / 2;

		// Styles pour la div interne (la vraie ligne).
		// IMPORTANT: Utiliser border au lieu de background-color pour compatibilité impression.
		// (les navigateurs désactivent background-color par défaut lors de l'impression).
		$inner_style = "position: relative; top: {$top_offset}px; width: 100%;";

		if ( 'dashed' === $style ) {
			$inner_style .= " border-bottom: {$width}px dashed {$color};";
		} elseif ( 'dotted' === $style ) {
			$inner_style .= " border-bottom: {$width}px dotted {$color};";
		} else {
			// Solid : utiliser border solid au lieu de background-color.
			$inner_style .= " border-bottom: {$width}px solid {$color}; height: 0;";
		}

		return '<div class="element" style="' . $line_styles . '"><div style="' . $inner_style . '"></div></div>';
	}



	/**
	 * Optimise le HTML pour le rendu PDF
	 * - Nettoie les espaces inutiles
	 * - Assure l'encodage UTF-8
	 * - Supprime les commentaires HTML
	 * - Normalise les sauts de ligne
	 *
	 * @param string $html HTML brut.
	 * @return string HTML optimisé.
	 */
	private function optimize_html( $html ) {
		$size_before = strlen( $html );

		// Assurer l'encodage UTF-8 propre.
		$html = mb_convert_encoding( $html, 'UTF-8', 'UTF-8' );

		// Supprimer les commentaires HTML (mais garder les commentaires conditionnels IE).
		$html = preg_replace( '/<!--(?!\[if\s).*?-->/s', '', $html );

		// Supprimer les espaces entre les balises.
		$html = preg_replace( '/>\s+</', '><', $html );

		// Collapse tous les whitespaces (inclut \r\n, \r, \n et espaces multiples).
		// Remplace l'ancien str_replace de normalisation des sauts de ligne (devenu redondant).
		$html = preg_replace( '/\s+/', ' ', $html );

		$this->debug_log( 'HTML optimisé - ' . $size_before . ' → ' . strlen( $html ) . ' caractères' );

		return $html;
	}



	/**
	 * Handler pour récupérer les orientations disponibles du canvas
	 */
	public function handle_get_canvas_orientations() {
		if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		try {
			// Récupérer les orientations disponibles depuis les paramètres canvas.
			$available_orientations_string = pdfib_get_option( 'pdfib_canvas_orientations', 'portrait,landscape' );

			if ( is_string( $available_orientations_string ) && strpos( $available_orientations_string, ',' ) !== false ) {
				$available_orientations = explode( ',', $available_orientations_string );
			} elseif ( is_array( $available_orientations_string ) ) {
				$available_orientations = $available_orientations_string;
			} else {
				$available_orientations = array( $available_orientations_string );
			}

			$available_orientations = array_map( 'strval', $available_orientations );

			// Retourner les permissions d'orientation.
			$orientation_permissions = array(
				'allowPortrait'         => in_array( 'portrait', $available_orientations, true ),
				'allowLandscape'        => in_array( 'landscape', $available_orientations, true ),
				'defaultOrientation'    => pdfib_get_option( 'pdfib_canvas_orientation', 'portrait' ),
				'availableOrientations' => $available_orientations,
			);

			wp_send_json_success( $orientation_permissions );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}




	/**
	 * Sauvegarde des paramètres templates
	 */
	private function save_templates_settings() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		// Chercher les données dans la structure imbriquée.
		$order_status_templates = array();
		if ( isset( $GLOBALS['_POST']['pdfib_settings']['pdfib_order_status_templates'] ) ) {
			$order_status_templates = wp_unslash( $GLOBALS['_POST']['pdfib_settings']['pdfib_order_status_templates'] );
		} elseif ( isset( $GLOBALS['_POST']['order_status_templates'] ) ) {
			// Fallback pour l'ancien format.
			$order_status_templates = wp_unslash( $GLOBALS['_POST']['order_status_templates'] );
		}

		// Nettoyer les valeurs vides.
		$clean_templates = array();
		foreach ( $order_status_templates as $status => $template_id ) {
			if ( ! empty( $template_id ) ) {
				$clean_templates[ sanitize_text_field( $status ) ] = sanitize_text_field( $template_id );
			}
		}

		// Sauvegarder même si vide (permet de désélectionner tous les templates).
		$result = pdfib_update_option( 'pdfib_order_status_templates', $clean_templates );
		// Retourner 1 si sauvegarde réussie, 0 si échec.
		return $result ? 1 : 0;
	}




	/**
	 * Lance la réparation des templates manquants ou corrompus.
	 *
	 * @return mixed Résultat de l'implémentation.
	 */
	public function handle_repair_templates() {
		return $this->handle_repair_templates_impl();
	}

	/**
	 * Implémentation interne de handle_repair_templates().
	 *
	 * @return void
	 */
	public function handle_repair_templates_impl() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		try {
			$repaired_templates = 0;
			$errors             = array();

			// Vérifier et réparer les templates par défaut.
			$default_templates = array(
				'invoice' => 'Template Facture',
				'quote'   => 'Template Devis',
				'receipt' => 'Template Reçu',
				'blank'   => 'Template Vierge',
			);

			foreach ( $default_templates as $template_id => $template_name ) {
				$template_option = pdfib_get_option( "pdfib_template_{$template_id}", '' );

				if ( empty( $template_option ) ) {
					// Template manquant, le recréer avec des valeurs par défaut.
					$default_content = $this->get_default_template_content( $template_id );
					pdfib_update_option( "pdfib_template_{$template_id}", $default_content );
					++$repaired_templates;
				}
			}

			// Vérifier l'intégrité des templates existants.
			$all_templates = pdfib_get_option( 'pdfib_templates', array() );
			if ( ! is_array( $all_templates ) ) {
				pdfib_update_option( 'pdfib_templates', array() );
				$errors[] = 'Liste des templates corrompue, réinitialisée';
			}

			$message  = "✅ Templates vérifiés et réparés\n";
			$message .= "• Templates réparés: $repaired_templates\n";

			if ( ! empty( $errors ) ) {
				$message .= "⚠️ Problèmes détectés:\n" . implode( "\n", $errors );
			} else {
				$message .= '• Aucun problème détecté';
			}

			// Mettre à jour la date de dernière maintenance.
			$current_time                       = \current_time( 'mysql' );
			$settings                           = pdfib_get_option( 'pdfib_settings', array() );
			$settings['pdfib_last_maintenance'] = $current_time;
			pdfib_update_option( 'pdfib_settings', $settings );

			wp_send_json_success( array( 'message' => $message ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => '❌ Erreur lors de la réparation: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Retourne les clés de configuration du canvas.
	 *
	 * @return string[] Liste des clés de paramètres canvas.
	 */
	private function get_canvas_setting_keys(): array {
		return $this->get_canvas_setting_keys_impl();
	}

	/**
	 * Implémentation interne de get_canvas_setting_keys().
	 *
	 * @return string[] Liste des clés de paramètres canvas.
	 */
	private function get_canvas_setting_keys_impl(): array {
		return array(
			'pdfib_canvas_width',
			'pdfib_canvas_height',
			'pdfib_canvas_dpi',
			'pdfib_canvas_format',
			'pdfib_canvas_formats',
			'pdfib_canvas_orientations',
			'pdfib_canvas_bg_color',
			'pdfib_canvas_border_color',
			'pdfib_canvas_border_width',
			'pdfib_canvas_shadow_enabled',
			'pdfib_canvas_container_bg_color',
			'pdfib_canvas_grid_enabled',
			'pdfib_canvas_grid_size',
			'pdfib_canvas_guides_enabled',
			'pdfib_canvas_snap_to_grid',
			'pdfib_canvas_zoom_min',
			'pdfib_canvas_zoom_max',
			'pdfib_canvas_zoom_default',
			'pdfib_canvas_zoom_step',
			'pdfib_canvas_export_quality',
			'pdfib_canvas_export_format',
			'pdfib_canvas_export_transparent',
			'pdfib_canvas_drag_enabled',
			'pdfib_canvas_resize_enabled',
			'pdfib_canvas_rotate_enabled',
			'pdfib_canvas_multi_select',
			'pdfib_canvas_selection_mode',
			'pdfib_canvas_keyboard_shortcuts',
			'pdfib_canvas_fps_target',
			'pdfib_canvas_memory_limit_js',
			'pdfib_canvas_response_timeout',
			'pdfib_canvas_lazy_loading_editor',
			'pdfib_canvas_preload_critical',
			'pdfib_canvas_lazy_loading_plugin',
			'pdfib_canvas_debug_enabled',
			'pdfib_canvas_performance_monitoring',
			'pdfib_canvas_error_reporting',
			'pdfib_canvas_memory_limit_php',
			'pdfib_canvas_backup',
			'pdfib_canvas_margin_top',
			'pdfib_canvas_margin_right',
			'pdfib_canvas_margin_bottom',
			'pdfib_canvas_margin_left',
			'pdfib_canvas_show_margins',
		);
	}

	/**
	 * Retourne toutes les options sauvegardées sous forme de tableau normalisé.
	 *
	 * @param array $settings Tableau des options brutes de la base de données.
	 * @return array Options normalisées.
	 */
	private function get_all_saved_options( array $settings ): array {
		return $this->get_all_saved_options_impl( $settings );
	}

	/**
	 * Implémentation interne de get_all_saved_options().
	 *
	 * @param array $settings Tableau des options brutes de la base de données.
	 * @return array Options normalisées.
	 */
	private function get_all_saved_options_impl( array $settings ): array {
		return array_merge(
			array(
				// Général.
				'company_phone_manual'           => $settings['pdfib_company_phone_manual'] ?? '',
				'company_siret'                  => $settings['pdfib_company_siret'] ?? '',
				'company_vat'                    => $settings['pdfib_company_vat'] ?? '',
				'company_rcs'                    => $settings['pdfib_company_rcs'] ?? '',
				'company_capital'                => $settings['pdfib_company_capital'] ?? '',
				// Cache.
				'cache_enabled'                  => $settings['pdfib_cache_enabled'] ?? '0',
				'cache_ttl'                      => $settings['pdfib_cache_ttl'] ?? 3600,
				'cache_compression'              => $settings['pdfib_cache_compression'] ?? '1',
				'cache_auto_cleanup'             => $settings['pdfib_cache_auto_cleanup'] ?? '1',
				'cache_max_size'                 => $settings['pdfib_cache_max_size'] ?? 100,
				// Système.
				'auto_maintenance'               => $settings['pdfib_auto_maintenance'] ?? '1',
				'backup_retention'               => $settings['pdfib_backup_retention'] ?? 30,
				// Sécurité.
				'security_level'                 => $settings['pdfib_security_level'] ?? 'medium',
				'enable_logging'                 => $settings['pdfib_enable_logging'] ?? '1',
				'gdpr_enabled'                   => $settings['pdfib_gdpr_enabled'] ?? '0',
				'gdpr_consent_required'          => $settings['pdfib_gdpr_consent_required'] ?? '0',
				'gdpr_data_retention'            => $settings['pdfib_gdpr_data_retention'] ?? 365,
				'gdpr_audit_enabled'             => $settings['pdfib_gdpr_audit_enabled'] ?? '0',
				'gdpr_encryption_enabled'        => $settings['pdfib_gdpr_encryption_enabled'] ?? '0',
				'gdpr_consent_analytics'         => $settings['pdfib_gdpr_consent_analytics'] ?? '0',
				'gdpr_consent_templates'         => $settings['pdfib_gdpr_consent_templates'] ?? '0',
				'gdpr_consent_marketing'         => $settings['pdfib_gdpr_consent_marketing'] ?? '0',
				// PDF.
				'pdf_quality'                    => $settings['pdfib_pdf_quality'] ?? 'high',
				'default_format'                 => $settings['pdfib_default_format'] ?? 'A4',
				'default_orientation'            => $settings['pdfib_default_orientation'] ?? 'portrait',
				// Contenu.
				'template_library_enabled'       => pdfib_get_option( 'pdfib_template_library_enabled', '1' ),
				'default_template'               => $settings['pdfib_default_template'] ?? 'blank',
				// Templates.
				'order_status_templates'         => $settings['pdfib_order_status_templates'] ?? array(),
				// Licence.
				'license_test_mode'              => $settings['pdfib_license_test_mode_enabled'] ?? '0',
				'license_email_reminders'        => $settings['pdfib_license_email_reminders'] ?? '0',
				'license_reminder_email'         => $settings['pdfib_license_reminder_email'] ?? get_option( 'admin_email', '' ),
				'pdfib_license_test_key_expires' => $settings['pdfib_license_test_key_expires'] ?? '',
			),
			$this->get_all_saved_canvas_options()
		);
	}

	/**
	 * Retourne les noms des champs booléens des paramètres.
	 *
	 * @return string[] Liste des noms de champs booléens.
	 */
	private function get_settings_bool_fields(): array {
		return $this->get_settings_bool_fields_impl();
	}

	/**
	 * Implémentation interne de get_settings_bool_fields().
	 *
	 * @return string[] Liste des noms de champs booléens.
	 */
	private function get_settings_bool_fields_impl(): array {
		return array(
			'pdfib_cache_enabled',
			'pdfib_cache_compression',
			'pdfib_cache_auto_cleanup',
			'pdfib_performance_auto_optimization',
			'pdfib_systeme_auto_maintenance',
			'pdfib_template_library_enabled',
			'pdfib_license_test_mode_enabled',
			'pdfib_canvas_debug_enabled',
			'pdfib_license_email_reminders',
			'pdfib_debug_javascript',
			'pdfib_debug_javascript_verbose',
			'pdfib_debug_ajax',
			'pdfib_debug_performance',
			'pdfib_debug_database',
			'pdfib_debug_php_errors',
			'pdfib_canvas_grid_enabled',
			'pdfib_canvas_snap_to_grid',
			'pdfib_canvas_guides_enabled',
			'pdfib_canvas_drag_enabled',
			'pdfib_canvas_resize_enabled',
			'pdfib_canvas_rotate_enabled',
			'pdfib_canvas_multi_select',
			'pdfib_canvas_keyboard_shortcuts',
			'pdfib_canvas_export_transparent',
			'pdfib_canvas_lazy_loading_editor',
			'pdfib_canvas_preload_critical',
			'pdfib_canvas_lazy_loading_plugin',
			'pdfib_canvas_debug_enabled',
			'pdfib_canvas_performance_monitoring',
			'pdfib_canvas_error_reporting',
			'pdfib_canvas_shadow_enabled',
			'pdfib_license_test_mode_enabled',
			'pdfib_force_https',
			'pdfib_performance_monitoring',
			'pdfib_enable_logging',
			'pdfib_gdpr_enabled',
			'pdfib_gdpr_consent_required',
			'pdfib_gdpr_audit_enabled',
			'pdfib_gdpr_encryption_enabled',
			'pdfib_gdpr_consent_analytics',
			'pdfib_gdpr_consent_templates',
			'pdfib_gdpr_consent_marketing',
			'pdfib_pdf_metadata_enabled',
			'pdfib_pdf_print_optimized',
			'pdfib_puppeteer_fallback',
		);
	}

	/**
	 * Lance l'optimisation de la base de données.
	 *
	 * @return mixed Résultat de l'implémentation.
	 */
	public function handle_optimize_database() {
		return $this->handle_optimize_database_impl();
	}

	/**
	 * Implémentation interne de handle_optimize_database().
	 *
	 * @return void
	 */
	public function handle_optimize_database_impl() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		try {
			// Obtenir la taille de la base avant optimisation.
			$size_before = $this->get_database_size();

			// Optimiser toutes les tables du plugin.
			$tables           = pdfib_db()->get_results( pdfib_db()->prepare( 'SHOW TABLES LIKE %s', pdfib_db()->esc_like( pdfib_db()->prefix . 'pdf_builder' ) . '%' ), ARRAY_N );
			$optimized_tables = 0;
			$errors           = array();

			foreach ( $tables as $table ) {
				$table_name = $table[0];
				$result     = pdfib_db()->query( pdfib_db()->prepare( 'OPTIMIZE TABLE %i', $table_name ) );

				if ( false === $result ) {
					$errors[] = "Erreur sur la table $table_name: " . pdfib_db()->last_error;
				} else {
					++$optimized_tables;
				}
			}

			// Obtenir la taille après optimisation.
			$size_after = $this->get_database_size();

			$message  = "✅ Base de données optimisée avec succès\n";
			$message .= "• Tables optimisées: $optimized_tables\n";
			$message .= "• Taille avant: {$size_before} MB\n";
			$message .= "• Taille après: {$size_after} MB";

			if ( ! empty( $errors ) ) {
				$message .= "\n⚠️ Erreurs rencontrées:\n" . implode( "\n", $errors );
			}

			// Mettre à jour la date de dernière maintenance.
			$current_time                       = \current_time( 'mysql' );
			$settings                           = pdfib_get_option( 'pdfib_settings', array() );
			$settings['pdfib_last_maintenance'] = $current_time;
			pdfib_update_option( 'pdfib_settings', $settings );

			wp_send_json_success( array( 'message' => $message ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => '❌ Erreur lors de l\'optimisation: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Lance le test de la licence.
	 *
	 * @return mixed Résultat de l'implémentation.
	 */
	/**
	 * Implémentation interne de handle_test_license().
	 *
	 * @return void
	 */
	public function handle_test_license_impl() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité: requête non autorisée', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		try {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => self::err_perms() ) );
				return;
			}

			// Récupérer le statut de licence actuel.
			$license_status = pdfib_get_option( 'pdfib_license_status', 'free' );
			$license_key    = pdfib_get_option( 'pdfib_license_key', '' );
			$test_mode      = pdfib_get_option( 'pdfib_license_test_mode_enabled', '0' );

			// Simuler un test de licence (à implémenter selon vos besoins).
			$test_result = 'valid'; // ou 'invalid', 'expired', etc.

			// Si en mode test, vérifier la clé de test.
			if ( '1' === $test_mode ) {
				$test_key     = pdfib_get_option( 'pdfib_license_test_key', '' );
				$test_expires = pdfib_get_option( 'pdfib_license_test_key_expires', '' );

				if ( empty( $test_key ) ) {
					$test_result = 'no_test_key';
				} elseif ( ! empty( $test_expires ) && strtotime( $test_expires ) < time() ) {
					$test_result = 'test_expired';
				} else {
					$test_result = 'test_valid';
				}
			}

			wp_send_json_success(
				array(
					'license_status' => $license_status,
					'license_key'    => ! empty( $license_key ) ? substr( $license_key, 0, 8 ) . '...' : '',
					'test_mode'      => $test_mode,
					'test_result'    => $test_result,
					'tested_at'      => \current_time( 'mysql' ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Erreur lors du test de licence: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Lance le téléchargement d'un fichier de sauvegarde.
	 *
	 * @return mixed Résultat de l'implémentation.
	 */
	public function handle_download_backup() {
		return $this->handle_download_backup_impl();
	}

	/**
	 * Implémentation interne de handle_download_backup().
	 *
	 * @return void
	 */
	public function handle_download_backup_impl() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		$filename = sanitize_file_name( wp_unslash( $GLOBALS['_POST']['filename'] ?? '' ) );
		if ( ! current_user_can( 'manage_options' ) || empty( $filename ) ) {
			$msg = ! current_user_can( 'manage_options' )
				? __( 'Permissions insuffisantes.', 'advanced-pdf-invoice-builder' )
				: __( 'Nom de fichier manquant.', 'advanced-pdf-invoice-builder' );
			wp_send_json_error( array( 'message' => $msg ) );
			return;
		}

		try {
			$filepath = wp_upload_dir()['basedir'] . '/pdf-builder-pro/backups/' . $filename;

			if ( ! file_exists( $filepath ) || headers_sent() ) {
				$msg = ! file_exists( $filepath )
					? __( 'Fichier de sauvegarde introuvable.', 'advanced-pdf-invoice-builder' )
					: __( 'Impossible d\'envoyer les headers - sortie déjà commencée.', 'advanced-pdf-invoice-builder' );
				wp_send_json_error( array( 'message' => $msg ) );
				return;
			}

			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( basename( $filename ) ) . '"' );
			header( self::HEADER_CONTENT_LENGTH . filesize( $filepath ) );
			header( 'Cache-Control: no-cache, no-store, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$file_content = pdfib_filesystem()->get_contents( $filepath );
			if ( false === $file_content ) {
				wp_send_json_error( array( 'message' => __( 'Impossible de lire le fichier de sauvegarde.', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$pdfib_fs = pdfib_filesystem();
			if ( ! $pdfib_fs->put_contents( 'php://output', $file_content ) ) {
				wp_send_json_error( array( 'message' => __( 'Impossible d\'envoyer le fichier de sauvegarde.', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			exit;
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Collecte les informations de test d'un hook WordPress.
	 *
	 * @param string $hook_name Nom du hook à tester.
	 * @return array Informations sur le hook (enregistrement, callbacks, etc.).
	 */
	private function collect_hook_test_info( string $hook_name ): array {
		return $this->collect_hook_test_info_impl( $hook_name );
	}

	/**
	 * Implémentation interne de collect_hook_test_info().
	 *
	 * @param string $hook_name Nom du hook à tester.
	 * @return array Informations sur le hook (enregistrement, callbacks, etc.).
	 */
	private function collect_hook_test_info_impl( string $hook_name ): array {
		global $wp_filter;

		$is_registered  = isset( $wp_filter[ $hook_name ] );
		$callback_count = 0;
		$callbacks      = array();

		if ( ! $is_registered || ! is_array( $wp_filter[ $hook_name ] ) ) {
			return array(
				'is_registered'  => false,
				'callback_count' => 0,
				'callbacks'      => array(),
			);
		}

		foreach ( $wp_filter[ $hook_name ] as $priority => $hooks_by_priority ) {
			if ( ! is_array( $hooks_by_priority ) ) {
				continue;
			}

			foreach ( $hooks_by_priority as $hook_data ) {
				if ( ! is_array( $hook_data ) || ! isset( $hook_data['function'] ) ) {
					continue;
				}

				++$callback_count;
				$callbacks[] = array(
					'function'      => $this->normalize_hook_callback_name( $hook_data['function'] ),
					'priority'      => (int) $priority,
					'accepted_args' => isset( $hook_data['accepted_args'] ) ? (int) $hook_data['accepted_args'] : 1,
				);
			}
		}

		usort(
			$callbacks,
			function ( $a, $b ) {
				return $a['priority'] - $b['priority'];
			}
		);

		return array(
			'is_registered'  => true,
			'callback_count' => $callback_count,
			'callbacks'      => $callbacks,
		);
	}

	/**
	 * Lance la récupération des informations système.
	 *
	 * @return mixed Résultat de l'implémentation.
	 */
	public function handle_system_info() {
		return $this->handle_system_info_impl();
	}

	/**
	 * Implémentation interne de handle_system_info().
	 *
	 * @return void
	 */
	public function handle_system_info_impl() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		try {
			$wp_version = $GLOBALS['wp_version'];

			$system_info = array(
				'wordpress' => array(
					'version'     => $wp_version,
					'site_url'    => get_site_url(),
					'admin_email' => get_option( 'admin_email' ),
					'debug_mode'  => defined( 'WP_DEBUG' ) && WP_DEBUG,
					'multisite'   => is_multisite(),
				),
				'server'    => array(
					'php_version'         => PHP_VERSION,
					'server_software'     => sanitize_text_field( wp_unslash( $GLOBALS['_SERVER']['SERVER_SOFTWARE'] ?? 'Unknown' ) ),
					'memory_limit'        => ini_get( 'memory_limit' ),
					'max_execution_time'  => ini_get( 'max_execution_time' ),
					'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
				),
				'database'  => array(
					'version'      => pdfib_db()->db_version(),
					'size'         => $this->get_database_size(),
					'tables_count' => count( pdfib_db()->get_results( 'SHOW TABLES' ) ),
				),
				'plugin'    => array(
					'version'        => pdfib_get_option( 'pdfib_version', 'Unknown' ),
					'cache_enabled'  => pdfib_get_option( 'pdfib_cache_enabled', '0' ) === '1',
					'license_status' => pdfib_get_option( 'pdfib_license_status', 'inactive' ),
				),
			);

			wp_send_json_success(
				array(
					'message'     => 'Informations système récupérées avec succès.',
					'system_info' => $system_info,
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Lance le rendu HTML de debug pour un template et une commande.
	 *
	 * @return void
	 */
	public function handle_debug_html() {
		$this->handle_debug_html_impl();
	}

	/**
	 * Implémentation interne de handle_debug_html().
	 *
	 * @return void
	 */
	public function handle_debug_html_impl() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé', 'advanced-pdf-invoice-builder' ), 403 );
		}

		$nonce = isset( $GLOBALS['_REQUEST']['nonce'] ) ? sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'pdfib_admin_nonce' ) ) {
			wp_die( esc_html( self::err_nonce() ), 403 );
		}

		$template_id = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_id'] ?? $GLOBALS['_GET']['template_id'] ?? '' ) );
		$order_id    = intval( wp_unslash( $GLOBALS['_POST']['order_id'] ?? $GLOBALS['_GET']['order_id'] ?? 0 ) );

		if ( ! $template_id || ! $order_id ) {
			/* translators: 1: Template ID, 2: Order ID */
			wp_die( esc_html( sprintf( __( 'Paramètres manquants: template_id=%1$s, order_id=%2$d', 'advanced-pdf-invoice-builder' ), $template_id, $order_id ) ), 400 );
		}

		try {
			if ( ! function_exists( 'wc_get_order' ) ) {
				wp_die( esc_html__( 'WooCommerce n\'est pas actif', 'advanced-pdf-invoice-builder' ), 400 );
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				/* translators: %d: Order ID */
				wp_die( esc_html( sprintf( __( 'Commande introuvable: %d', 'advanced-pdf-invoice-builder' ), $order_id ) ), 404 );
			}

			$template = $this->get_template_by_id( $template_id );
			if ( ! $template ) {
				/* translators: %s: Template ID */
				wp_die( esc_html( sprintf( __( 'Template introuvable: %s', 'advanced-pdf-invoice-builder' ), $template_id ) ), 404 );
			}

			// Générer le HTML.
			$html = $this->generate_template_html( $template, $order );

			// Afficher directement le HTML.
			header( 'Content-Type: text/html; charset=UTF-8' );
			$pdfib_fs = pdfib_filesystem();
			if ( ! $pdfib_fs->put_contents( 'php://output', $html ) ) {
				wp_die( esc_html__( 'Impossible d\'envoyer le flux HTML de debug.', 'advanced-pdf-invoice-builder' ), 500 );
			}
			exit;
		} catch ( Exception $e ) {
			wp_die( esc_html( $e->getMessage() ), 500 );
		}
	}

	/**
	 * Retourne un template de secours pour les tests.
	 *
	 * @param int $template_id Identifiant du template.
	 * @return array Données du template de secours.
	 */
	private function get_fallback_template( int $template_id ) {
		return $this->get_fallback_template_impl( $template_id );
	}

	/**
	 * Implémentation interne de get_fallback_template().
	 *
	 * @param int $template_id Identifiant du template.
	 * @return array Données du template de secours.
	 */
	private function get_fallback_template_impl( int $template_id ) {
		$fallback_template = array(
			'elements' => array(
				array(
					'type'    => 'text',
					'x'       => 50,
					'y'       => 50,
					'width'   => 200,
					'height'  => 40,
					'content' => 'FACTURE',
					'styles'  => array(
						'fontSize'   => 32,
						'fontWeight' => 'bold',
						'color'      => '#0073aa',
					),
				),
				array(
					'type'   => 'customerInfo',
					'x'      => 50,
					'y'      => 120,
					'width'  => 250,
					'height' => 120,
					'styles' => array(
						'fontSize' => 14,
					),
				),
				array(
					'type'   => 'table',
					'x'      => 50,
					'y'      => 280,
					'width'  => 495,
					'height' => 200,
				),
			),
			'canvas'   => array(
				// A4 @ 96 DPI (standard écran) - 794×1123px.
				'width'       => 794,
				'height'      => 1123,
				'dpi'         => 96,  // DPI écran (React Canvas).
				'orientation' => 'portrait',
			),
		);

		return array(
			'id'            => $template_id,
			'name'          => 'Template Test (Fallback)',
			'template_data' => $fallback_template,
		);
	}

	/**
	 * Génère les totaux du tableau produit (sous-total, TVA, remises, total).
	 *
	 * @param array $element    Données de l'élément tableau produit.
	 * @param array $order_data Données extraites de la commande.
	 * @return string HTML des totaux.
	 */
	private function render_product_table_totals( array $element, array $order_data ): string {
		return $this->render_product_table_totals_impl( $element, $order_data );
	}

	/**
	 * Implémentation interne de render_product_table_totals().
	 *
	 * @param array $element    Données de l'élément tableau produit.
	 * @param array $order_data Données extraites de la commande.
	 * @return string HTML des totaux.
	 */
	private function render_product_table_totals_impl( array $element, array $order_data ): string {
		$row_font_size     = $element['rowFontSize'] ?? 11;
		$row_font_family   = trim( str_replace( '"', "'", $element['rowFontFamily'] ?? 'Arial' ) );
		$row_font_weight   = $element['rowFontWeight'] ?? 'normal';
		$row_color         = $element['rowTextColor'] ?? self::COLOR_DARK_GRAY;
		$total_font_size   = $element['totalFontSize'] ?? 12;
		$total_font_family = trim( str_replace( '"', "'", $element['totalFontFamily'] ?? 'Arial' ) );
		$total_font_weight = $element['totalFontWeight'] ?? 'bold';
		$total_font_style  = $element['totalFontStyle'] ?? 'normal';
		$total_color       = $element['totalTextColor'] ?? self::COLOR_NEAR_BLACK;
		$html              = '';
		// Tableau des totaux séparé - aligné à droite (SANS bordure).
		$html .= '<div style="margin-top: 20px; width: 100%; display: table;">';

		// Le tableau des totaux n'a JAMAIS de bordure.
		$html .= '<table style="width: 25%; margin-left: auto; border-collapse: collapse; margin-right: 5px;">';
		$html .= '<tbody>';

		// Ligne de séparation avant les totaux (comme dans React Canvas ligne 1327-1332).
		$html .= '<tr><td colspan="2" style="border-bottom: 1px solid #d1d5db; padding: 0; line-height: 0; height: 1px;"></td></tr>';
		$html .= '<tr><td colspan="2" style="padding: 10px 0 0 0;"></td></tr>'; // Espacement après la ligne.

		// Style pour les lignes de summary (sous-total, remise, livraison, TVA) - SANS bordures de cellules.
		$summary_style = 'border: none; text-align: right; padding: 6px 8px; ' .
			"font-size: {$row_font_size}px; font-family: {$row_font_family}; " .
			"font-weight: {$row_font_weight}; color: {$row_color};";

		$summary_label_style = 'border: none; text-align: left; padding: 6px 8px; ' .
			"font-size: {$row_font_size}px; font-family: {$row_font_family}; " .
			"font-weight: {$row_font_weight}; color: {$row_color};";

		// Sous-total (avant remises et frais).
		if ( $element['showSubtotal'] ?? true ) {
			$html .= '<tr>';
			$html .= '<td style="' . $summary_label_style . '">Sous-total:</td>';
			$html .= '<td style="' . $summary_style . '">' . wp_kses_post( wc_price( $order_data['totals']['subtotal_raw'] ) ) . '</td>';
			$html .= '</tr>';
		}

		// Frais de service (total des fees).
		if ( isset( $order_data['fees'] ) && ! empty( $order_data['fees'] ) ) {
			$fees_total = 0;
			foreach ( $order_data['fees'] as $fee ) {
				$fees_total += floatval( $fee['total_raw'] ?? 0 );
			}
			if ( $fees_total > 0 ) {
				$html .= '<tr>';
				$html .= '<td style="' . $summary_label_style . '">Frais:</td>';
				$html .= '<td style="' . $summary_style . '">' . wp_kses_post( wc_price( $fees_total ) ) . '</td>';
				$html .= '</tr>';
			}
		}

		// Réductions (si présentes).
		if ( ( $element['showDiscount'] ?? true ) && $order_data['totals']['discount_raw'] > 0 ) {
			$discount_style       = str_replace( $row_color, '#dc2626', $summary_style );
			$discount_label_style = str_replace( $row_color, '#dc2626', $summary_label_style );
			$html                .= '<tr>';
			$html                .= '<td style="' . $discount_label_style . '">Remise:</td>';
			$html                .= '<td style="' . $discount_style . '">-' . wp_kses_post( wc_price( $order_data['totals']['discount_raw'] ) ) . '</td>';
			$html                .= '</tr>';
		}

		// Frais de port.
		if ( ( $element['showShipping'] ?? true ) && $order_data['totals']['shipping_raw'] > 0 ) {
			$html .= '<tr>';
			$html .= '<td style="' . $summary_label_style . '">Frais de port:</td>';
			$html .= '<td style="' . $summary_style . '">' . wp_kses_post( wc_price( $order_data['totals']['shipping_raw'] ) ) . '</td>';
			$html .= '</tr>';
		}

		// TVA.
		if ( ( $element['showTax'] ?? true ) && $order_data['totals']['tax_raw'] > 0 ) {
			$html .= '<tr>';
			$html .= '<td style="' . $summary_label_style . '">TVA (5.0%):</td>';
			$html .= '<td style="' . $summary_style . '">' . wp_kses_post( wc_price( $order_data['totals']['tax_raw'] ) ) . '</td>';
			$html .= '</tr>';
		}

		// Total final avec séparateur (ligne de séparation avec border-top uniquement).
		// IMPORTANT: border-left/right/bottom = none pour éviter les bordures de cellules.
		$total_style = 'border-top: 2px solid #333; border-left: none; border-right: none; border-bottom: none; text-align: right; padding: 10px 8px 6px 8px; ' .
			"font-size: {$total_font_size}px; font-family: {$total_font_family}; " .
			"font-weight: {$total_font_weight}; font-style: {$total_font_style}; " .
			"color: {$total_color};";

		$total_label_style = 'border-top: 2px solid #333; border-left: none; border-right: none; border-bottom: none; text-align: left; padding: 10px 8px 6px 8px; ' .
			"font-size: {$total_font_size}px; font-family: {$total_font_family}; " .
			"font-weight: {$total_font_weight}; font-style: {$total_font_style}; " .
			"color: {$total_color};";

		$html .= '<tr>';
		$html .= '<td style="' . $total_label_style . '">TOTAL:</td>';
		$html .= '<td style="' . $total_style . '">' . wp_kses_post( wc_price( $order_data['totals']['total_raw'] ) ) . '</td>';
		$html .= '</tr>';

		$html .= '</tbody></table>';
		$html .= '</div>'; // Fermer le conteneur des totaux.

		return $html;
	}

	/**
	 * Exporte les données personnelles RGPD de l'utilisateur courant.
	 *
	 * @return void
	 */
	public function handle_export_gdpr_data(): void {
		$this->handle_export_gdpr_data_impl();
	}

	/**
	 * Implémentation interne de handle_export_gdpr_data().
	 *
	 * @return void
	 */
	public function handle_export_gdpr_data_impl(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );
		if ( ! $this->validate_gdpr_nonce() ) {
			return;
		}

		$user   = wp_get_current_user();
		$format = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['format'] ?? 'html' ) );
		$table  = pdfib_db()->prefix . 'pdfib_templates';

		$template_count = (int) pdfib_db()->get_var(
			pdfib_db()->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
				$user->ID
			)
		);

		$data = array(
			'user'   => array(
				'id'         => $user->ID,
				'login'      => $user->user_login,
				'email'      => $user->user_email,
				'registered' => $user->user_registered,
				'roles'      => $user->roles,
			),
			'plugin' => array(
				'templates_created' => $template_count,
				'exported_at'       => current_time( self::DATE_FORMAT_DB ),
			),
		);

		$this->append_audit_log( 'EXPORT_DATA', 'Données exportées par ' . $user->user_login );

		if ( 'json' === $format ) {
			wp_send_json_success(
				array(
					'format'  => 'json',
					'content' => $data,
				)
			);
			return;
		}

		// Format HTML.
		$html  = '<div style="font-family:sans-serif;font-size:13px;">';
		$html .= '<h3 style="color:#155724">📄 Données personnelles exportées</h3>';
		$html .= '<table style="border-collapse:collapse;width:100%">';
		foreach (
			array(
				'ID'              => $data['user']['id'],
				'Identifiant'     => $data['user']['login'],
				'Email'           => $data['user']['email'],
				'Inscrit le'      => $data['user']['registered'],
				'Rôle(s)'         => implode( ', ', $data['user']['roles'] ),
				'Templates créés' => $data['plugin']['templates_created'],
				'Exporté le'      => $data['plugin']['exported_at'],
			) as $label => $value
		) {
			$html .= '<tr><th style="text-align:left;padding:6px 12px;background:#e8f5e8;border:1px solid #c3e6cb">' . esc_html( $label ) . '</th>';
			$html .= '<td style="padding:6px 12px;border:1px solid #dee2e6">' . esc_html( (string) $value ) . '</td></tr>';
		}
		$html .= '</table></div>';

		wp_send_json_success(
			array(
				'format'  => 'html',
				'content' => $html,
			)
		);
	}

	/**
	 * Bascule le mode test de la licence.
	 *
	 * @return mixed Résultat de l'implémentation.
	 */
	/**
	 * Implémentation interne de handle_toggle_test_mode().
	 *
	 * @return void
	 */
	public function handle_toggle_test_mode_impl() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		if ( ! $this->nonce_manager->validate_ajax_request() ) {
			return;
		}

		try {
			$current_mode = pdfib_get_option( 'pdfib_license_test_mode_enabled', '0' );
			$new_mode     = '1' === $current_mode ? '0' : '1';

			$response_data = array(
				'message'   => 'Mode test ' . ( '1' === $new_mode ? 'activé' : 'désactivé' ) . ' avec succès.',
				'test_mode' => $new_mode,
			);

			// Sauvegarder le nouveau mode.
			pdfib_update_option( 'pdfib_license_test_mode_enabled', $new_mode );

			// Si on active le mode test, générer automatiquement une clé de test si elle n'existe pas.
			$existing_test_key = pdfib_get_option( 'pdfib_license_test_key', '' );
			if ( '1' === $new_mode ) {
				if ( empty( $existing_test_key ) ) {
					// Générer une nouvelle clé de test.
					$test_key           = 'TEST-' . strtoupper( substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 16 ) );
					$expires_in_30_days = gmdate( 'Y-m-d', strtotime( '+30 days' ) );
					pdfib_update_option( 'pdfib_license_test_key', $test_key );
					pdfib_update_option( 'pdfib_license_test_key_expires', $expires_in_30_days );
					pdfib_update_option( 'pdfib_license_status', 'active' );

					$response_data['test_key'] = $test_key;
					$response_data['expires']  = $expires_in_30_days;
					$response_data['message'] .= ' Clé de test générée automatiquement.';
				} else {
					// Retourner la clé existante.
					$response_data['test_key'] = $existing_test_key;
					$response_data['expires']  = pdfib_get_option( 'pdfib_license_test_key_expires', '' );
				}
			} else {
				// Mode test désactivé : la clé existante est conservée pour réactivation ultérieure.
				$response_data['test_key'] = $existing_test_key;
			}

			wp_send_json_success( $response_data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => self::err_internal() ) );
		}
	}

	/**
	 * Lance la génération d'un PDF pour une commande et un template donnés.
	 *
	 * @return void
	 */
	public function handle_generate_pdf() {
		$this->handle_generate_pdf_impl();
	}

	/**
	 * Retourne la position de file d'attente pour un token de requête donné.
	 * Endpoint : wp_ajax_pdfib_queue_status
	 * Paramètre GET/POST : request_token (string)
	 */
	public function handle_queue_status(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Non autorisé' ), 403 );
			return;
		}

		$token = sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['request_token'] ?? '' ) );
		if ( '' === $token ) {
			wp_send_json_error( array( 'message' => 'Token manquant' ), 400 );
			return;
		}

		$data = get_transient( 'pdfib_queue_' . $token );

		if ( false === $data ) {
			// Transient absent = job non démarré ou déjà terminé.
			wp_send_json_success( array( 'status' => 'unknown' ) );
			return;
		}

		wp_send_json_success(
			array(
				'status'         => 'pending',
				'queue_position' => $data['queue_position'] ?? null,
				'attempt'        => $data['attempt'] ?? null,
				'updated_at'     => $data['updated_at'] ?? null,
			)
		);
	}

	/**
	 * Implémentation interne de handle_generate_pdf().
	 *
	 * @return void
	 */
	public function handle_generate_pdf_impl() {

		// Le service Puppeteer peut prendre jusqu'à 35 s (timeout wp_remote_post).
		$this->debug_log( '========== GÉNÉRATION PDF DÉMARRÉE ==========' );

		// Vérifier les permissions - doit être connecté et avoir les droits de gestion WooCommerce.
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_shop_orders' ) ) {
			$this->debug_log( 'Permission refusée', 'WARNING' );
			wp_die( esc_html__( 'Permission refusée', 'advanced-pdf-invoice-builder' ), '', array( 'response' => 403 ) );
		}

		// Vérifier le nonce (supporte GET et POST).
		if ( ! $this->validate_pdf_ajax_nonce() ) {
			$this->debug_log( self::err_nonce(), 'WARNING' );
			wp_die( esc_html__( 'Nonce de sécurité invalide', 'advanced-pdf-invoice-builder' ), '', array( 'response' => 403 ) );
		}

		$this->debug_log( 'POST params: ' . wp_json_encode( $GLOBALS['_POST'] ) );

		// Supporte GET et POST (ouverture directe dans onglet ou appel AJAX).
		$template_id = sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['template_id'] ?? '' ) );
		$order_id    = intval( wp_unslash( $GLOBALS['_REQUEST']['order_id'] ?? 0 ) );

		$this->debug_log( "Template ID: '{$template_id}', Order ID: {$order_id}" );

		if ( ! $template_id || ! $order_id ) {
			$this->debug_log( 'Paramètres manquants', 'WARNING' );
			wp_die( esc_html__( 'Paramètres manquants', 'advanced-pdf-invoice-builder' ), '', array( 'response' => 400 ) );
		}

		try {
			// Vérifier que WooCommerce est actif.
			if ( ! function_exists( 'wc_get_order' ) ) {
				$this->debug_log( 'WooCommerce non actif', 'ERROR' );
				wp_die( esc_html__( 'WooCommerce n\'est pas actif', 'advanced-pdf-invoice-builder' ), '', array( 'response' => 500 ) );
			}

			$order = $this->resolve_order( $order_id );
			if ( ! $order ) {
				$this->debug_log( "Commande #{$order_id} introuvable", 'WARNING' );
				wp_die( esc_html__( 'Commande introuvable', 'advanced-pdf-invoice-builder' ), '', array( 'response' => 404 ) );
			}
			// @var \WC_Abstract_Order $order Commande WooCommerce résolue.

			$this->debug_log( "Commande #{$order_id} trouvée" );

			// Récupérer le template.
			$template = $this->resolve_template( $template_id );
			if ( ! $template ) {
				$this->debug_log( "Template '{$template_id}' introuvable", 'WARNING' );
				wp_die( esc_html__( 'Modèle introuvable', 'advanced-pdf-invoice-builder' ), '', array( 'response' => 404 ) );
			}

			$this->debug_log( "Template '{$template_id}' trouvé: " . ( $template['name'] ?? 'sans nom' ) );

			// === NOUVEAU : DÉTERMINER LE MOTEUR PDF AVANT GÉNÉRATION HTML ===...
			$engine                    = \PDFIB\PDF\Engines\PDFEngineFactory::create();
			$this->current_engine_name = strtolower( $engine->get_name() );
			$this->debug_log( 'Moteur PDF sélectionné: ' . $engine->get_name() );

			// Générer (ou récupérer du cache requête) l'HTML.
			$this->debug_log( "Début génération HTML pour PDF (moteur: {$this->current_engine_name})" );
			$html = $this->resolve_template_html( $template, $order, 'pdf' );
			$this->debug_log( 'HTML généré - Longueur: ' . strlen( $html ) . ' caractères' );

			// Optimiser l'HTML avant le rendu.
			$html = $this->optimize_html( $html );

			// Configurer le format papier depuis le template.
			$template_data = json_decode( $template['template_data'], true );
			$width         = $template_data['canvasWidth'] ?? 794;
			$height        = $template_data['canvasHeight'] ?? 1123;

			$this->debug_log( 'Génération PDF avec moteur: ' . $engine->get_name() );
			// Générer le PDF avec le moteur sélectionné.
			$request_token = sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['request_token'] ?? '' ) );
			$pdf_content   = $engine->generate(
				$html,
				array(
					'width'         => $width,
					'height'        => $height,
					'request_token' => $request_token,
				)
			);

			if ( false === $pdf_content ) {
				$this->debug_log( 'Échec génération PDF', 'ERROR' );
				wp_die( esc_html__( 'Erreur lors de la génération du PDF', 'advanced-pdf-invoice-builder' ), '', array( 'response' => 500 ) );
			}

			$this->debug_log( 'PDF généré avec succès - Taille: ' . strlen( $pdf_content ) . ' bytes' );
			// Envoyer le PDF au navigateur.
			$this->debug_log( 'Envoi du PDF au navigateur' );
			// Vider tous les output buffers WordPress/PHP avant d'envoyer du binaire.
			while ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			// Supprimer tous les headers existants pour éviter la corruption.
			header_remove();
			header( 'Content-Type: application/pdf' );
			header( 'Content-Disposition: inline; filename="facture-' . sanitize_file_name( $order->get_order_number() ) . '.pdf"' );
			header( self::HEADER_CONTENT_LENGTH . strlen( $pdf_content ) );
			header( 'Cache-Control: private, max-age=0, must-revalidate' );
			header( 'Pragma: public' );
			header( 'X-Content-Type-Options: nosniff' );

			$pdfib_fs = pdfib_filesystem();
			if ( ! $pdfib_fs->put_contents( 'php://output', $pdf_content ) ) {
				wp_die( esc_html__( 'Impossible d\'envoyer le flux PDF.', 'advanced-pdf-invoice-builder' ), '', array( 'response' => 500 ) );
			}
			exit;
		} catch ( Exception $e ) {
			$this->debug_log( 'Erreur génération PDF: ' . $e->getMessage(), 'ERROR' );
			wp_die( esc_html( self::err_prefix() . $e->getMessage() ), '', array( 'response' => 500 ) );
		}
	}

	/**
	 * Effectue le rendu d'un élément du template en HTML.
	 *
	 * @param array  $element    Données de l'élément template.
	 * @param array  $order_data Données extraites de la commande.
	 * @param bool   $is_premium Indique si la licence est premium.
	 * @param string $format     Format cible : 'html', 'pdf' ou 'image'.
	 * @return string HTML de l'élément.
	 */
	private function render_element( array $element, array $order_data, bool $is_premium = false, string $format = 'html' ) {
		return $this->render_element_impl( $element, $order_data, $is_premium, $format );
	}

	/**
	 * Implémentation interne de render_element().
	 *
	 * @param array  $element    Données de l'élément template.
	 * @param array  $order_data Données extraites de la commande.
	 * @param bool   $is_premium Indique si la licence est premium.
	 * @param string $format     Format cible : 'html', 'pdf' ou 'image'.
	 * @return string HTML de l'élément.
	 */
	private function render_element_impl( array $element, array $order_data, bool $is_premium = false, string $format = 'html' ) {
		// Vérifier si l'élément est visible.
		if ( isset( $element['visible'] ) && false === $element['visible'] ) {
			return '';
		}

		$type   = $element['type'] ?? 'text';
		$x      = $element['x'] ?? 0;
		$y      = $element['y'] ?? 0;
		$width  = $element['width'] ?? 100;
		$height = $element['height'] ?? 30;

		// Styles de base avec position absolute et dimensions.
		$styles  = "position: absolute !important; margin: 0 !important; left: {$x}px !important; top: {$y}px !important; width: {$width}px !important; height: {$height}px !important;";
		$styles .= $this->build_element_styles( $element );

		// Rendu spécifique par type.
		$rendered = '';
		switch ( $type ) {
			case 'product_table':
				$rendered = $this->render_product_table( $element, $order_data, $styles );
				break;
			case 'customer_info':
				$rendered = $this->render_customer_info_element( $element, $order_data, $styles, $is_premium );
				break;
			case 'company_info':
				$rendered = $this->render_company_info_element( $element, $order_data, $styles, $is_premium, $format );
				break;
			case 'company_logo':
				$rendered = $this->render_company_logo( $element, $styles );
				break;
			case 'line':
				$rendered = $this->render_line( $element, $styles );
				break;
			case 'rectangle':
				$rendered = $this->render_rectangle( $element, $styles );
				break;
			case 'circle':
				$rendered = $this->render_circle( $element, $styles );
				break;
			case 'document_type':
				$rendered = $this->render_document_type( $element, $styles );
				break;
			case 'woocommerce_order_date':
				$rendered = $this->render_order_date( $element, $order_data, $styles );
				break;
			case 'woocommerce_invoice_number':
				$rendered = $this->render_invoice_number( $element, $order_data, $styles );
				break;
			case 'dynamic_text':
				$rendered = $this->render_dynamic_text( $element, $order_data, $styles );
				break;
			case 'mentions':
				$rendered = $this->render_mentions( $element, $styles );
				break;
			case 'image':
				$img_src = $element['src'] ?? '';
				if ( $img_src ) {
					// Convertir en base64 pour Puppeteer (évite les problèmes SSL avec wp.local).
					$img_base64 = $this->get_image_as_base64( $img_src );
					$img_final  = ( null !== $img_base64 ? $img_base64 : esc_url( $img_src ) );
					// ✅ Support de objectFit (cohérent avec React : cover par défaut).
					$object_fit = $element['fit'] ?? $element['objectFit'] ?? 'cover';
					$rendered   = '<div class="element" style="' . $styles . '"><img src="' . esc_attr( $img_final ) . '" style="width: 100%; height: 100%; object-fit: ' . esc_attr( $object_fit ) . ';" /></div>';
				}
				break;
			case 'text':
			default:
				$content = $element['text'] ?? $element['content'] ?? '';

				// ✅ Support du padding horizontal et vertical (backward compatibility).
				$padding_horizontal = 12;
				$padding_vertical   = 12;
				$vertical_align     = isset( $element['verticalAlign'] ) ? $element['verticalAlign'] : 'top';

				// Styles du conteneur interne avec padding et alignement.
				$text_style = $styles . '; white-space: pre-line; padding: ' . $padding_vertical . 'px ' . $padding_horizontal . 'px;';

				// ✅ Alignement vertical via flexbox (cohérent avec React).
				if ( 'middle' === $vertical_align ) {
					$text_style .= ' display: flex; flex-direction: column; justify-content: center; height: 100%;';
				} elseif ( 'bottom' === $vertical_align ) {
					$text_style .= ' display: flex; flex-direction: column; justify-content: flex-end; height: 100%;';
				}

				$rendered = '<div class="element" style="' . $text_style . '">' . esc_html( $content ) . '</div>';
				break;
		}

		return $rendered;
	}

	/**
	 * Construit les styles CSS d'un élément template (typographie + fond + effets).
	 *
	 * @param array $element Données de l'élément template.
	 * @return string Styles CSS inline.
	 */
	private function build_element_styles( array $element ) {
		return $this->build_element_styles_impl( $element );
	}

	/**
	 * Implémentation interne de build_element_styles().
	 *
	 * @param array $element Données de l'élément template.
	 * @return string Styles CSS inline.
	 */
	private function build_element_styles_impl( array $element ) {
		return $this->build_typography_styles( $element )
			. $this->build_background_border_styles( $element )
			. $this->build_visual_effects_styles( $element );
	}

	/**
	 * Génère les styles de typographie (police, taille, espacement…).
	 *
	 * @param array $element Données de l'élément template.
	 * @return string Styles CSS inline de typographie.
	 */
	private function build_typography_styles( array $element ): string {
		$css = '';

		static $text_styles = array(
			'fontSize'       => 'font-size: %spx;',
			'fontWeight'     => 'font-weight: %s;',
			'fontStyle'      => 'font-style: %s;',
			'textDecoration' => 'text-decoration: %s;',
			'textTransform'  => 'text-transform: %s;',
			'textAlign'      => 'text-align: %s;',
			'textColor'      => 'color: %s;',
		);

		foreach ( $text_styles as $prop => $format ) {
			if ( isset( $element[ $prop ] ) ) {
				$css .= sprintf( $format, $element[ $prop ] ) . ' ';
			}
		}

		// Font family (guillemets simples pour supporter les polices avec espaces).
		if ( isset( $element['fontFamily'] ) ) {
			$type              = $element['type'] ?? '';
			$is_custom_flex    = in_array( $type, array( 'woocommerce_order_date', 'woocommerce_invoice_number' ), true );
			$important         = $is_custom_flex ? ' !important' : '';
			$font_family_clean = trim( str_replace( '"', "'", $element['fontFamily'] ) );
			$css              .= "font-family: {$font_family_clean}{$important}; ";
		}

		// Word spacing (ignorer 'normal').
		if ( ( $element['wordSpacing'] ?? 'normal' ) !== 'normal' ) {
			$css .= 'word-spacing: ' . $element['wordSpacing'] . '; ';
		}

		// Line height.
		if ( isset( $element['lineHeight'] ) && '' !== $element['lineHeight'] && 'normal' !== $element['lineHeight'] ) {
			$css .= 'line-height: ' . $element['lineHeight'] . '; ';
		}

		return $css;
	}

	/**
	 * Génère les styles d'arrière-plan et de bordures.
	 *
	 * @param array $element Données de l'élément template.
	 * @return string Styles CSS inline de fond et bordures.
	 */
	private function build_background_border_styles( array $element ): string {
		$css = '';

		$show_background = $element['showBackground'] ?? true;
		if ( false !== $show_background ) {
			$bg_color = $element['backgroundColor'] ?? '';
			if ( $bg_color && 'transparent' !== $bg_color ) {
				$css .= 'background-color: ' . $bg_color . '; ';
			}
		}

		$type            = $element['type'] ?? '';
		$no_border_types = array( 'line', 'rectangle', 'circle', 'image', 'company_info' );
		$show_borders    = ! in_array( $type, $no_border_types, true ) && ( $element['showBorders'] ?? true ) !== false;
		if ( $show_borders ) {
			$border_width = isset( $element['borderWidth'] ) ? (float) $element['borderWidth'] : 1;
			$border_color = $element['borderColor'] ?? self::COLOR_BLACK;
			$border_style = $element['borderStyle'] ?? 'solid';
			if ( $border_width > 0 ) {
				$css .= "border: {$border_width}px {$border_style} {$border_color}; ";
			}
		}

		$border_radius = $element['borderRadius'] ?? 0;
		if ( $border_radius > 0 ) {
			$css .= "border-radius: {$border_radius}px; ";
		}

		return $css;
	}

	/**
	 * Génère les styles d'effets visuels (opacité, rotation, ombre).
	 *
	 * @param array $element Données de l'élément template.
	 * @return string Styles CSS inline d'effets visuels.
	 */
	private function build_visual_effects_styles( array $element ): string {
		$css = '';

		if ( isset( $element['opacity'] ) ) {
			$opacity = $element['opacity'] > 1 ? $element['opacity'] / 100 : $element['opacity'];
			if ( $opacity < 1 ) {
				$css .= "opacity: {$opacity}; ";
			}
		}

		$rotation = $element['rotation'] ?? 0;
		if ( 0 !== $rotation ) {
			$css .= "transform: rotate({$rotation}deg); ";
		}

		$shadow_x    = $element['shadowOffsetX'] ?? 0;
		$shadow_y    = $element['shadowOffsetY'] ?? 0;
		$shadow_blur = $element['shadowBlur'] ?? 0;
		if ( 0 !== $shadow_x || 0 !== $shadow_y || 0 !== $shadow_blur ) {
			$shadow_color = $element['shadowColor'] ?? self::COLOR_BLACK;
			$css         .= "box-shadow: {$shadow_x}px {$shadow_y}px {$shadow_blur}px {$shadow_color}; ";
		}

		return $css;
	}

	/**
	 * Effectue le rendu du tableau produit.
	 *
	 * @param array  $element     Données de l'élément tableau produit.
	 * @param array  $order_data  Données extraites de la commande.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML du tableau produit.
	 */
	private function render_product_table( array $element, array $order_data, string $base_styles ) {
		return $this->render_product_table_impl( $element, $order_data, $base_styles );
	}

	/**
	 * Implémentation interne de render_product_table().
	 *
	 * @param array  $element     Données de l'élément tableau produit.
	 * @param array  $order_data  Données extraites de la commande.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML du tableau produit.
	 */
	private function render_product_table_impl( array $element, array $order_data, string $base_styles ) {
		$html               = '<div class="element" style="' . $base_styles . '">';
		$header_bg          = $element['headerBackgroundColor'] ?? '#f9fafb';
		$alt_bg             = $element['alternateRowColor'] ?? '#f9fafb';
		$bg_color           = $element['backgroundColor'] ?? self::COLOR_WHITE;
		$header_color       = $element['headerTextColor'] ?? self::COLOR_NEAR_BLACK;
		$row_color          = $element['rowTextColor'] ?? self::COLOR_DARK_GRAY;
		$header_font_size   = $element['headerFontSize'] ?? 12;
		$header_font_family = trim( str_replace( '"', "'", $element['headerFontFamily'] ?? 'Arial' ) );
		$header_font_weight = $element['headerFontWeight'] ?? 'bold';
		$header_font_style  = $element['headerFontStyle'] ?? 'normal';
		$row_font_size      = $element['rowFontSize'] ?? 11;
		$row_font_family    = trim( str_replace( '"', "'", $element['rowFontFamily'] ?? 'Arial' ) );
		$row_font_weight    = $element['rowFontWeight'] ?? 'normal';
		$row_font_style     = $element['rowFontStyle'] ?? 'normal';
		$show_image         = $element['showImage'] ?? true;
		$show_name          = true;
		$show_sku           = $element['showSku'] ?? false;
		$show_description   = $element['showDescription'] ?? false;
		$show_quantity      = $element['showQuantity'] ?? true;
		$show_price         = $element['showPrice'] ?? true;
		$show_total         = $element['showTotal'] ?? true;
		$cell_sep_style     = 'border: none;';

		$html .= '<table style="width:100%; border-collapse: collapse; background-color: ' . $bg_color . ';">';

		// En-têtes.
		if ( $element['showHeaders'] ?? true ) {
			$header_style = $cell_sep_style . " padding: 8px; background: {$header_bg}; color: {$header_color}; " .
				"font-size: {$header_font_size}px; font-family: {$header_font_family}; " .
				"font-weight: {$header_font_weight}; font-style: {$header_font_style};";

			$html .= '<thead><tr>';
			if ( $show_image ) {
				$html .= '<th style="' . $header_style . ' text-align: center; width: 60px;">Img</th>';
			}
			if ( $show_name ) {
				$html .= '<th style="' . $header_style . '">Produit</th>';
			}
			if ( $show_sku ) {
				$html .= '<th style="' . $header_style . '">SKU</th>';
			}
			if ( $show_description ) {
				$html .= '<th style="' . $header_style . '">Description</th>';
			}
			if ( $show_quantity ) {
				$html .= '<th style="' . $header_style . ' text-align: center; width: 80px; max-width: 80px;">Qté</th>';
			}
			if ( $show_price ) {
				$html .= '<th style="' . $header_style . ' text-align: right; width: 80px; max-width: 80px;">Prix</th>';
			}
			if ( $show_total ) {
				$html .= '<th style="' . $header_style . ' text-align: right; width: 80px; max-width: 80px;">Total</th>';
			}
			$html .= '</tr></thead>';
		}

		$html     .= '<tbody>';
		$row_index = 0;

		$row_style_base = $cell_sep_style . " padding: 8px; color: {$row_color}; " .
			"font-size: {$row_font_size}px; font-family: {$row_font_family}; " .
			"font-weight: {$row_font_weight}; font-style: {$row_font_style};";

		$html .= $this->render_product_table_product_rows(
			$order_data['products'],
			$element,
			$row_style_base,
			array(
				'image'       => $show_image,
				'name'        => $show_name,
				'sku'         => $show_sku,
				'description' => $show_description,
				'quantity'    => $show_quantity,
				'price'       => $show_price,
				'total'       => $show_total,
			),
			$alt_bg,
			$bg_color,
			$row_index
		);

		$html .= $this->render_product_table_fee_rows(
			$order_data['fees'] ?? array(),
			$element,
			$row_style_base,
			array(
				'image'       => $show_image,
				'name'        => $show_name,
				'sku'         => $show_sku,
				'description' => $show_description,
				'quantity'    => $show_quantity,
				'price'       => $show_price,
				'total'       => $show_total,
			),
			$alt_bg,
			$bg_color,
			$row_index
		);

		$html .= '</tbody></table>'; // Fermer le tableau des produits.

		$html .= $this->render_product_table_totals( $element, $order_data );
		$html .= '</div>'; // Fermer l'élément principal.
		return $html;
	}

	/**
	 * Résout une URL image en chaîne base64 data-URI via cache statique.
	 *
	 * @param string $url URL absolue ou relative de l'image.
	 * @return string|false Data-URI base64 ou false en cas d'échec.
	 */
	private function get_image_as_base64( string $url ) {
		return $this->get_image_as_base64_impl( $url );
	}

	/**
	 * Implémentation interne de get_image_as_base64().
	 *
	 * @param string $url URL absolue ou relative de l'image.
	 * @return string|false Data-URI base64 ou false en cas d'échec.
	 */
	private function get_image_as_base64_impl( string $url ) {
		if ( strpos( $url, 'data:image' ) === 0 ) {
			return $url;
		}

		static $cache = array();
		if ( isset( $cache[ $url ] ) ) {
			return $cache[ $url ];
		}

		$cache[ $url ] = $this->fetch_and_encode_image_as_base64( $url );
		return $cache[ $url ];
	}

	/**
	 * Résout une URL image en chaîne base64 data-URI (filesystem ou HTTP).
	 *
	 * @param string $url URL absolue ou relative /wp-content/….
	 * @return string|false Data-URI base64 ou false en cas d'échec.
	 */
	private function fetch_and_encode_image_as_base64( string $url ) {
		if ( strpos( $url, '/wp-content/' ) === 0 ) {
			$url = site_url( $url );
		}
		$local_b64 = $this->try_local_file_as_base64( $url );
		return ( null !== $local_b64 ? $local_b64 : $this->try_http_fetch_as_base64( $url ) );
	}

	/**
	 * Tente de lire l'image depuis le système de fichiers local.
	 *
	 * @param string $url URL absolue ou relative de l'image.
	 * @return string|false Data-URI base64 ou false si introuvable.
	 */
	private function try_local_file_as_base64( string $url ) {
		try {
			$upload_dir = wp_upload_dir();
			$local_path = str_replace(
				rtrim( $upload_dir['baseurl'], '/' ),
				rtrim( $upload_dir['basedir'], '/' ),
				$url
			);
			if ( ! file_exists( $local_path ) ) {
				$content_dir_path = dirname( $upload_dir['basedir'] );
				$local_path       = str_replace(
					untrailingslashit( content_url() ),
					untrailingslashit( $content_dir_path ),
					$url
				);
			}
			if ( ! file_exists( $local_path ) ) {
				return false;
			}
			$body         = pdfib_filesystem()->get_contents( $local_path );
			$finfo        = new \finfo( FILEINFO_MIME_TYPE );
			$content_type = $finfo->buffer( $body );
			$result       = false;
			if ( $body && $content_type ) {
				$result = 'data:' . $content_type . ';base64,' . sodium_bin2base64( $body, SODIUM_BASE64_VARIANT_ORIGINAL );
			}
			return $result;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Tente de récupérer l'image via HTTP et encode en base64.
	 *
	 * @param string $url URL de l'image à télécharger.
	 * @return string|false Data-URI base64 ou false si échec HTTP.
	 */
	private function try_http_fetch_as_base64( string $url ) {
		if ( ! wp_http_validate_url( $url ) ) {
			return false;
		}
		try {
			$response = wp_remote_get(
				$url,
				array(
					'timeout'   => 10,
					'sslverify' => ! ( defined( 'PDFIB_DISABLE_SSL_VERIFY' ) && PDFIB_DISABLE_SSL_VERIFY ),
				)
			);
			if ( is_wp_error( $response ) ) {
				return false;
			}
			// @var array $response Réponse HTTP WordPress.
			$body         = wp_remote_retrieve_body( $response );
			$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			if ( empty( $content_type ) ) {
				$finfo        = new \finfo( FILEINFO_MIME_TYPE );
				$content_type = $finfo->buffer( $body );
			}
			return empty( $body ) ? false : 'data:' . $content_type . ';base64,' . sodium_bin2base64( $body, SODIUM_BASE64_VARIANT_ORIGINAL );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Effectue le rendu de la date de commande.
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param array  $order_data  Données extraites de la commande.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément date.
	 */
	private function render_order_date( array $element, array $order_data, string $base_styles ) {
		return $this->render_order_date_impl( $element, $order_data, $base_styles );
	}

	/**
	 * Tente de créer un objet DateTime depuis une chaîne.
	 * Retourne null si la chaîne est vide ou invalide.
	 *
	 * @param string $date_string Chaîne de date à parser.
	 * @return \DateTime|null Objet date ou null si invalide.
	 */
	private function try_parse_date( string $date_string ): ?\DateTime {
		if ( empty( $date_string ) ) {
			return null;
		}
		try {
			return new \DateTime( $date_string );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Implémentation interne de render_order_date().
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param array  $order_data  Données extraites de la commande.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément date.
	 */
	private function render_order_date_impl( array $element, array $order_data, string $base_styles ) {
		$date_string = $order_data['order']['date'] ?? '';
		$date        = $this->try_parse_date( $date_string );
		if ( null === $date ) {
			$fallback = ( '' !== $date_string ? $date_string : 'Date non disponible' );
			return '<div class="element" style="' . $base_styles . '">' . esc_html( $fallback ) . '</div>';
		}

		$format    = $element['dateFormat'] ?? self::DATE_FORMAT_FR;
		$show_time = $element['showTime'] ?? false;

		$formatted_date = $this->format_date_php( $date, $format );
		if ( $show_time ) {
			$formatted_date .= ' ' . $date->format( 'H:i' );
		}

		$p = $this->extract_date_display_props( $element );

		$show_label               = $p['show_label'];
		$label_text               = $p['label_text'];
		$label_position           = $p['label_position'];
		$label_spacing            = $p['label_spacing'];
		$label_font_size          = $p['label_font_size'];
		$label_font_with_fallback = $p['label_font_with_fallback'];
		$date_font_size           = $p['date_font_size'];
		$date_font_weight         = $p['date_font_weight'];
		$date_font_style          = $p['date_font_style'];
		$date_color               = $p['date_color'];
		$date_font_with_fallback  = $p['date_font_with_fallback'];
		$text_align               = $p['text_align'];
		$vertical_align           = $p['vertical_align'];
		$padding_top              = $p['padding_top'];
		$padding_right            = $p['padding_right'];
		$padding_bottom           = $p['padding_bottom'];
		$padding_left             = $p['padding_left'];

		$span_h       = max( $label_font_size, $date_font_size ) + 2;
		$label_styles = "display: inline-flex !important; align-items: center !important; height: {$span_h}px !important; font-family: {$label_font_with_fallback} !important; font-size: {$label_font_size}px !important; font-weight: {$p['label_font_weight']} !important; font-style: {$p['label_font_style']} !important; color: {$p['label_color']} !important; line-height: 1 !important; margin: 0 !important;";
		$date_styles  = "display: inline-flex !important; align-items: center !important; height: {$span_h}px !important; font-family: {$date_font_with_fallback} !important; font-size: {$date_font_size}px !important; font-weight: {$p['date_font_weight']} !important; font-style: {$p['date_font_style']} !important; color: {$p['date_color']} !important; line-height: 1 !important; margin: 0 !important;";

		if ( $show_label ) {
			$container_styles = $this->build_label_value_container_styles(
				$base_styles,
				$label_position,
				$label_spacing,
				$text_align,
				$vertical_align,
				array( $padding_top, $padding_right, $padding_bottom, $padding_left )
			);
			return $this->build_label_value_html( $container_styles, $label_styles, $date_styles, $label_position, $label_text, $formatted_date );
		}

		$container_styles = $base_styles . self::CSS_FLEX_DISPLAY
			. " padding: {$padding_top}px {$padding_right}px {$padding_bottom}px {$padding_left}px !important; box-sizing: border-box !important;"
			. $this->flex_justify_content( $vertical_align )
			. $this->flex_align_items( $text_align )
			. self::CSS_FLEX_COL;

		$date_styles = "display: inline-flex !important; align-items: center !important; font-family: {$date_font_with_fallback} !important; font-size: {$date_font_size}px !important; font-weight: {$date_font_weight} !important; font-style: {$date_font_style} !important; color: {$date_color} !important; line-height: 1 !important; margin: 0 !important;";

		return '<div class="element" style=\'' . $container_styles . '\'><span style=\'' . $date_styles . '\'>' . esc_html( $formatted_date ) . '</span></div>';
	}

	/**
	 * Effectue le rendu du numéro de facture.
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param array  $order_data  Données extraites de la commande.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément numéro de facture.
	 */
	private function render_invoice_number( array $element, array $order_data, string $base_styles ) {
		return $this->render_invoice_number_impl( $element, $order_data, $base_styles );
	}

	/**
	 * Implémentation interne de render_invoice_number().
	 *
	 * @param array  $element     Données de l'élément template.
	 * @param array  $order_data  Données extraites de la commande.
	 * @param string $base_styles Styles CSS de base de l'élément.
	 * @return string HTML de l'élément numéro de facture.
	 */
	private function render_invoice_number_impl( array $element, array $order_data, string $base_styles ) {
		$invoice_number = 'INV-' . $order_data['order']['order_number'];
		$prefix         = $element['prefix'] ?? '';
		$suffix         = $element['suffix'] ?? '';
		$display_number = $prefix . $invoice_number . $suffix;

		$show_label     = $element['showLabel'] ?? true;
		$label_text     = $element['labelText'] ?? 'Numéro de facture :';
		$label_position = $element['labelPosition'] ?? 'left';
		$label_spacing  = $element['labelSpacing'] ?? 8;

		$label_font_family = $element['labelFontFamily'] ?? ( $element['fontFamily'] ?? self::FONT_DEFAULT );
		$label_font_size   = $element['labelFontSize'] ?? ( $element['fontSize'] ?? 12 );
		$label_font_weight = $element['labelFontWeight'] ?? 'normal';
		$label_font_style  = $element['labelFontStyle'] ?? 'normal';
		$label_color       = $element['labelColor'] ?? ( $element['textColor'] ?? ( $element['color'] ?? self::COLOR_BLACK ) );

		$number_font_family = $element['fontFamily'] ?? self::FONT_DEFAULT;
		$number_font_size   = $element['fontSize'] ?? 12;
		$number_font_weight = $element['fontWeight'] ?? 'normal';
		$number_font_style  = $element['fontStyle'] ?? 'normal';
		$number_color       = $element['textColor'] ?? ( $element['color'] ?? self::COLOR_BLACK );
		$text_align         = $element['textAlign'] ?? 'left';
		$vertical_align     = $element['verticalAlign'] ?? 'top';

		$number_font_with_fallback = $this->add_font_fallbacks( $number_font_family );
		$label_font_with_fallback  = $this->add_font_fallbacks( $label_font_family );

		$padding_top    = $element['padding']['top'] ?? $element['paddingTop'] ?? 0;
		$padding_right  = $element['padding']['right'] ?? $element['paddingRight'] ?? 0;
		$padding_bottom = $element['padding']['bottom'] ?? $element['paddingBottom'] ?? 0;
		$padding_left   = $element['padding']['left'] ?? $element['paddingLeft'] ?? 0;

		if ( $show_label ) {
			$max_font_size = max( $label_font_size, $number_font_size );
			$span_height   = $max_font_size + 2;

			$label_styles  = "display: inline-flex !important; align-items: center !important; height: {$span_height}px !important; font-family: {$label_font_with_fallback} !important; font-size: {$label_font_size}px !important; font-weight: {$label_font_weight} !important; font-style: {$label_font_style} !important; color: {$label_color} !important; line-height: 1 !important; margin: 0 !important;";
			$number_styles = "display: inline-flex !important; align-items: center !important; height: {$span_height}px !important; font-family: {$number_font_with_fallback} !important; font-size: {$number_font_size}px !important; font-weight: {$number_font_weight} !important; font-style: {$number_font_style} !important; color: {$number_color} !important; line-height: 1 !important; margin: 0 !important;";

			$container_styles = $this->build_label_value_container_styles(
				$base_styles,
				$label_position,
				$label_spacing,
				$text_align,
				$vertical_align,
				array( $padding_top, $padding_right, $padding_bottom, $padding_left )
			);
			return $this->build_label_value_html( $container_styles, $label_styles, $number_styles, $label_position, $label_text, $display_number );
		}

		$container_styles = $base_styles . self::CSS_FLEX_DISPLAY
			. " padding: {$padding_top}px {$padding_right}px {$padding_bottom}px {$padding_left}px !important; box-sizing: border-box !important;"
			. $this->flex_justify_content( $vertical_align )
			. $this->flex_align_items( $text_align )
			. self::CSS_FLEX_COL;

		$number_styles = "display: inline-flex !important; align-items: center !important; font-family: {$number_font_with_fallback} !important; font-size: {$number_font_size}px !important; font-weight: {$number_font_weight} !important; font-style: {$number_font_style} !important; color: {$number_color} !important; line-height: 1 !important; margin: 0 !important;";

		return '<div class="element" style=\'' . $container_styles . '\'><span style=\'' . $number_styles . '\'>' . esc_html( $display_number ) . '</span></div>';
	}

	/**
	 * Construit les styles CSS du conteneur flexbox pour un élément label+valeur.
	 *
	 * @param string $base_styles    Styles CSS de base de l'élément.
	 * @param string $label_position Position du label : 'top', 'bottom', 'left', 'right'.
	 * @param int    $label_spacing  Espacement entre le label et la valeur en px.
	 * @param string $text_align     Alignement horizontal du texte.
	 * @param string $vertical_align Alignement vertical.
	 * @param array  $padding        Tableau [top, right, bottom, left] (int ou float).
	 * @return string Styles CSS inline du conteneur.
	 */
	private function build_label_value_container_styles(
		string $base_styles,
		string $label_position,
		int $label_spacing,
		string $text_align,
		string $vertical_align,
		array $padding
	): string {
		[$pt, $pr, $pb, $pl] = $padding;
		$is_vertical         = in_array( $label_position, array( 'top', 'bottom' ), true );
		$direction           = $is_vertical ? self::CSS_FLEX_COL : ' flex-direction: row !important;';
		$jc                  = $is_vertical ? $this->flex_justify_content( $vertical_align ) : $this->flex_justify_content( $text_align );
		$ai                  = $is_vertical ? $this->flex_align_items( $text_align ) : $this->flex_align_items( $vertical_align );

		return $base_styles . self::CSS_FLEX_DISPLAY
			. " padding: {$pt}px {$pr}px {$pb}px {$pl}px !important; box-sizing: border-box !important;"
			. $direction
			. " gap: {$label_spacing}px !important;"
			. $jc
			. $ai;
	}

	/**
	 * Construit le HTML d'un élément label+valeur avec flexbox.
	 * L'ordre des spans dépend de la position du label (top/left = label first).
	 *
	 * @param string $container_styles Styles CSS du conteneur flexbox.
	 * @param string $label_styles     Styles CSS du span label.
	 * @param string $value_styles     Styles CSS du span valeur.
	 * @param string $label_position   Position du label : 'top', 'bottom', 'left', 'right'.
	 * @param string $label_text       Texte du label.
	 * @param string $value_text       Texte de la valeur.
	 * @return string HTML de l'élément.
	 */
	private function build_label_value_html(
		string $container_styles,
		string $label_styles,
		string $value_styles,
		string $label_position,
		string $label_text,
		string $value_text
	): string {
		$label_span = '<span style=\'' . $label_styles . '\'>' . esc_html( $label_text ) . '</span>';
		$value_span = '<span style=\'' . $value_styles . '\'>' . esc_html( $value_text ) . '</span>';

		$label_first = in_array( $label_position, array( 'top', 'left' ), true ) || '' === $label_position;
		$inner       = $label_first ? $label_span . $value_span : $value_span . $label_span;

		return '<div class="element" style=\'' . $container_styles . '\'>' . $inner . '</div>';
	}

	/**
	 * Retourne la valeur CSS justify-content en fonction de l'alignement demandé.
	 * Utilisé pour les axes principaux flexbox.
	 *
	 * @param string $align Alignement demandé : 'center', 'middle', 'bottom', 'right', ou autre.
	 * @return string Valeur CSS justify-content avec !important.
	 */
	private function flex_justify_content( string $align ): string {
		if ( 'middle' === $align || 'center' === $align ) {
			return self::CSS_JC_CENTER;
		}
		if ( 'bottom' === $align || 'right' === $align ) {
			return self::CSS_JC_END;
		}
		return self::CSS_JC_START;
	}

	/**
	 * Retourne la valeur CSS align-items en fonction de l'alignement demandé.
	 * Utilisé pour les axes transversaux flexbox.
	 *
	 * @param string $align Alignement demandé : 'center', 'middle', 'bottom', 'right', ou autre.
	 * @return string Valeur CSS align-items avec !important.
	 */
	private function flex_align_items( string $align ): string {
		if ( 'middle' === $align || 'center' === $align ) {
			return self::CSS_AI_CENTER;
		}
		if ( 'bottom' === $align || 'right' === $align ) {
			return self::CSS_AI_END;
		}
		return self::CSS_AI_START;
	}
}
