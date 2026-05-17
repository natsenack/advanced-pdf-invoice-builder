<?php
/**
 * Advanced PDF Invoice Builder - GDPR Compliance Manager
 * Gestionnaire de conformité RGPD
 *
 * @package PDF_Builder_Pro
 * @since 1.6.11
 */

namespace PDFIB\Utilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Classe pour gérer la conformité RGPD.
 */
class PdfBuilderGdprManager {


	/**
	 * Instance unique.
	 *
	 * @var ?self
	 */
	private static ?self $instance = null;

	/**
	 * Options RGPD.
	 *
	 * @var array
	 */
	private array $gdpr_options = array();

	/**
	 * Dispatcher AJAX RGPD.
	 *
	 * @var GdprAjaxDispatcher
	 */
	private GdprAjaxDispatcher $ajax_dispatcher;

	/**
	 * Constructeur privé (Singleton)
	 */
	private function __construct() {
		$this->init_hooks();
		$this->load_gdpr_options();
	}

	/**
	 * Obtenir l'instance unique.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialiser les hooks
	 */
	private function init_hooks(): void {
		$html_renderer         = new GdprHtmlRenderer();
		$data_helper           = new GdprUserDataHelper( $html_renderer );
		$this->ajax_dispatcher = new GdprAjaxDispatcher( $this, $data_helper );

		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_gdpr_scripts' ) );

		\add_action( 'wp_ajax_pdfib_save_consent', array( $this->ajax_dispatcher, 'handle_save_consent' ) );
		\add_action( 'wp_ajax_pdfib_revoke_consent', array( $this->ajax_dispatcher, 'handle_revoke_consent' ) );
		\add_action( 'wp_ajax_pdfib_load_gdpr_preferences', array( $this->ajax_dispatcher, 'handle_load_preferences' ) );
		\add_action( 'wp_ajax_pdfib_save_gdpr_preferences', array( $this->ajax_dispatcher, 'handle_save_preferences' ) );
		\add_action( 'wp_ajax_pdfib_export_user_data', array( $this->ajax_dispatcher, 'handle_export_user_data' ) );
		\add_action( 'wp_ajax_pdfib_delete_user_data', array( $this->ajax_dispatcher, 'handle_delete_user_data' ) );
		\add_action( 'wp_ajax_pdfib_request_data_portability', array( $this->ajax_dispatcher, 'handle_request_data_portability' ) );
		\add_action( 'wp_ajax_pdfib_get_consent_status', array( $this->ajax_dispatcher, 'handle_get_consent_status' ) );
		\add_action( 'wp_ajax_pdfib_save_gdpr_settings', array( $this->ajax_dispatcher, 'handle_save_gdpr_settings' ) );
		\add_action( 'wp_ajax_pdfib_view_consent_status', array( $this->ajax_dispatcher, 'handle_view_consent_status' ) );
		\add_action( 'wp_ajax_pdfib_refresh_audit_log', array( $this->ajax_dispatcher, 'handle_refresh_audit_log' ) );
		\add_action( 'wp_ajax_pdfib_export_audit_log', array( $this->ajax_dispatcher, 'handle_export_audit_log' ) );

		// Legacy action name aliases (settings-securite.js uses pdf_builder_* prefix).
		\add_action( 'wp_ajax_pdf_builder_export_gdpr_data', array( $this->ajax_dispatcher, 'handle_export_user_data' ) );
		\add_action( 'wp_ajax_pdf_builder_delete_gdpr_data', array( $this->ajax_dispatcher, 'handle_delete_user_data' ) );
		\add_action( 'wp_ajax_pdf_builder_get_consent_status', array( $this->ajax_dispatcher, 'handle_get_consent_status' ) );
		\add_action( 'wp_ajax_pdf_builder_get_audit_log', array( $this->ajax_dispatcher, 'handle_refresh_audit_log' ) );
		\add_action( 'wp_ajax_pdf_builder_export_audit_log', array( $this->ajax_dispatcher, 'handle_export_audit_log' ) );

		\add_action( 'wp_scheduled_delete', array( $this, 'cleanup_expired_data' ) );
		\add_action( 'init', array( $this, 'create_audit_table' ) );
	}

	/**
	 * Charger les options RGPD
	 */
	private function load_gdpr_options(): void {
		$this->gdpr_options = pdfib_get_option(
			'pdfib_gdpr',
			array(
				'consent_required'    => true,
				'consent_types'       => array(
					'analytics' => true,
					'templates' => true,
					'marketing' => false,
				),
				'data_retention_days' => 2555,
				'audit_enabled'       => true,
				'encryption_enabled'  => true,
			)
		);
	}

	/**
	 * Sauvegarder les options RGPD.
	 */
	private function save_gdpr_options(): void {
		pdfib_update_option( 'pdfib_gdpr', $this->gdpr_options );
	}

	/**
	 * Enqueue les scripts et styles RGPD.
	 *
	 * @param string $hook Nom du hook admin courant.
	 */
	public function enqueue_gdpr_scripts( string $hook ): void {
		if ( 'toplevel_page_pdf-builder-pro' !== $hook && false === strpos( $hook, 'pdf-builder' ) ) {
			return;
		}

		$gdpr_css = plugin_dir_path( dirname( __DIR__ ) ) . 'assets/css/gdpr.css';
		if ( file_exists( $gdpr_css ) ) {
			wp_enqueue_style( 'pdf-builder-gdpr', plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/gdpr.css', array(), PDFIB_PRO_VERSION );
		}
	}

	/**
	 * Sauvegarder le consentement d'un utilisateur.
	 *
	 * @param int    $user_id      ID de l'utilisateur.
	 * @param string $consent_type Type de consentement.
	 * @param bool   $granted      Si le consentement est accorde.
	 */
	public function save_user_consent( int $user_id, string $consent_type, bool $granted ): void {
		$consent_data = array(
			'granted'    => $granted,
			'timestamp'  => time(),
			'ip_address' => $this->get_client_ip(),
		);

		$data_to_store = $this->gdpr_options['encryption_enabled']
			? $this->encrypt_data( wp_json_encode( $consent_data ) )
			: $consent_data;

		update_user_meta( $user_id, 'pdfib_consent_' . $consent_type, $data_to_store );
	}

	/**
	 * Revoquer le consentement d'un utilisateur.
	 *
	 * @param int    $user_id      ID de l'utilisateur.
	 * @param string $consent_type Type de consentement.
	 */
	public function revoke_user_consent( int $user_id, string $consent_type ): void {
		delete_user_meta( $user_id, 'pdfib_consent_' . $consent_type );
	}

	/**
	 * Obtenir le statut d'un consentement pour un utilisateur.
	 *
	 * @param int    $user_id      ID de l'utilisateur.
	 * @param string $consent_type Type de consentement.
	 */
	public function get_user_consent_status( int $user_id, string $consent_type ): bool {
		$stored_data = get_user_meta( $user_id, 'pdfib_consent_' . $consent_type, true );

		if ( empty( $stored_data ) ) {
			return false;
		}

		if ( $this->gdpr_options['encryption_enabled'] && is_string( $stored_data ) ) {
			$decrypted    = $this->decrypt_data( $stored_data );
			$consent_data = $decrypted ? json_decode( $decrypted, true ) : null;
			return (bool) ( $consent_data['granted'] ?? false );
		}

		return (bool) ( $stored_data['granted'] ?? false );
	}

	/**
	 * Obtenir la clé de chiffrement
	 */
	private function get_encryption_key(): string {
		if ( ! defined( 'PDFIB_ENCRYPTION_KEY' ) ) {
			$salt = wp_salt( 'auth' ) . wp_salt( 'secure_auth' ) . wp_salt( 'logged_in' ) . wp_salt( 'nonce' );
			define( 'PDFIB_ENCRYPTION_KEY', substr( hash( 'sha256', $salt ), 0, 32 ) );
		}
		return PDFIB_ENCRYPTION_KEY;
	}

	/**
	 * Dechiffrer des donnees sensibles.
	 *
	 * @param string $encrypted_data Donnees chiffrees.
	 */
	public function decrypt_data( string $encrypted_data ): string|false {
		if ( ! $this->gdpr_options['encryption_enabled'] || empty( $encrypted_data ) ) {
			return $encrypted_data;
		}

		$key = $this->get_encryption_key();
		try {
			$data = sodium_base642bin( $encrypted_data, SODIUM_BASE64_VARIANT_ORIGINAL );
		} catch ( \SodiumException $e ) {
			return $encrypted_data;
		}

		if ( strlen( $data ) < 16 ) {
			return $encrypted_data;
		}

		$iv        = substr( $data, 0, 16 );
		$encrypted = substr( $data, 16 );

		return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
	}

	/**
	 * Vérifier si le chiffrement est disponible
	 */
	public function is_encryption_available(): bool {
		return function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' );
	}

	/**
	 * Logger une action d'audit.
	 *
	 * @param int    $user_id   ID de l'utilisateur.
	 * @param string $action    Action effectuee.
	 * @param string $data_type Type de donnees.
	 * @param mixed  $details   Details supplementaires.
	 */
	public function log_audit_action( int $user_id, string $action, string $data_type, mixed $details = '' ): void {
		if ( ! $this->gdpr_options['audit_enabled'] ) {
			return;
		}
		$table_audit = pdfib_db()->prefix . 'pdfib_audit_log';

		pdfib_db()->insert(
			$table_audit,
			array(
				'user_id'    => $user_id,
				'action'     => $action,
				'data_type'  => $data_type,
				'details'    => $details,
				'ip_address' => $this->get_client_ip(),
				'user_agent' => sanitize_text_field( wp_unslash( $GLOBALS['_SERVER']['HTTP_USER_AGENT'] ?? '' ) ),
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Nettoyer les données expirées
	 */
	public function cleanup_expired_data(): void {
		$retention_days = $this->gdpr_options['data_retention_days'];
		$cutoff_date    = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );
		$table_audit    = pdfib_db()->prefix . 'pdfib_audit_log';

		pdfib_db()->delete(
			$table_audit,
			array(
				'created_at <' => $cutoff_date,
			)
		);
	}

	/**
	 * Programmer le nettoyage automatique
	 */
	public function schedule_data_cleanup(): void {
		if ( ! \wp_next_scheduled( 'pdfib_gdpr_cleanup' ) ) {
			\wp_schedule_event( time(), 'daily', 'pdfib_gdpr_cleanup' );
		}
	}

	/**
	 * Créer la table d'audit (hook WP init)
	 */
	public function create_audit_table(): void {
		$table_name      = pdfib_db()->prefix . 'pdfib_audit_log';
		$charset_collate = pdfib_db()->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            action varchar(100) NOT NULL,
            data_type varchar(100) NOT NULL,
            details text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Verifier si un consentement est requis et accorde.
	 *
	 * @param int    $user_id      ID de l'utilisateur.
	 * @param string $consent_type Type de consentement.
	 */
	public function is_consent_granted( int $user_id, string $consent_type ): bool {
		if ( ! ( $this->gdpr_options['consent_types'][ $consent_type ] ?? false ) ) {
			return true;
		}
		return $this->get_user_consent_status( $user_id, $consent_type );
	}

	/**
	 * Chiffrer des donnees sensibles.
	 *
	 * @param mixed $data Donnees a chiffrer.
	 */
	public function encrypt_data( mixed $data ): mixed {
		if ( ! $this->gdpr_options['encryption_enabled'] || empty( $data ) ) {
			return $data;
		}

		$key       = $this->get_encryption_key();
		$iv        = openssl_random_pseudo_bytes( 16 );
		$encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );

		return sodium_bin2base64( $iv . $encrypted, SODIUM_BASE64_VARIANT_ORIGINAL );
	}

	/**
	 * Obtenir l'adresse IP du client
	 */
	private function get_client_ip(): string {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $GLOBALS['_SERVER'][ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $GLOBALS['_SERVER'][ $header ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return sanitize_text_field( wp_unslash( $GLOBALS['_SERVER']['REMOTE_ADDR'] ?? '127.0.0.1' ) );
	}

	/**
	 * Mettre a jour et sauvegarder les options RGPD.
	 *
	 * @param array $updates Options mises a jour.
	 */
	public function update_gdpr_options( array $updates ): void {
		$this->gdpr_options = array_merge( $this->gdpr_options, $updates );
		$this->save_gdpr_options();
	}
}
