<?php
/**
 * Advanced PDF Invoice Builder - GDPR AJAX Dispatcher
 * Dispatche les requêtes AJAX RGPD vers les traitements appropriés.
 *
 * @package PDF_Builder_Pro
 * @since   1.6.12
 */

namespace PDFIB\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Gestionnaire AJAX pour la conformité RGPD.
 */
class GdprAjaxDispatcher {

	/**
	 * RGPD manager instance.
	 *
	 * @var PdfBuilderGdprManager
	 */
	private PdfBuilderGdprManager $manager;
	/**
	 * User data helper instance.
	 *
	 * @var GdprUserDataHelper
	 */
	private GdprUserDataHelper $data_helper;

	/**
	 * Initialise le dispatcher avec le manager et le helper.
	 *
	 * @param PdfBuilderGdprManager $manager     Manager RGPD.
	 * @param GdprUserDataHelper    $data_helper Helper de donnees utilisateur.
	 */
	public function __construct( PdfBuilderGdprManager $manager, GdprUserDataHelper $data_helper ) {
		$this->manager     = $manager;
		$this->data_helper = $data_helper;
	}

	/**
	 * Charge les preferences AJAX RGPD de l utilisateur.
	 */
	public function handle_load_preferences(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		$user_id     = get_current_user_id();
		$preferences = array();

		foreach ( array( 'analytics', 'templates', 'marketing' ) as $consent_type ) {
			$preferences[ $consent_type ] = $this->manager->is_consent_granted( $user_id, $consent_type );
		}

		\wp_send_json_success( array( 'preferences' => $preferences ) );
	}

	/**
	 * Sauvegarde le consentement RGPD via AJAX.
	 */
	public function handle_save_consent(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		$user_id      = get_current_user_id();
		$consent_type = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['consent_type'] ?? '' ) );
		$granted      = (bool) sanitize_text_field( wp_unslash( $GLOBALS['_POST']['granted'] ?? '' ) );

		$this->manager->save_user_consent( $user_id, $consent_type, $granted );
		$this->manager->log_audit_action( $user_id, $granted ? 'consent_granted' : 'consent_revoked', 'consent', $consent_type );

		\wp_send_json_success( array( 'message' => __( 'Consentement sauvegardé.', 'advanced-pdf-invoice-builder' ) ) );
	}

	/**
	 * Revoque un consentement RGPD via AJAX.
	 */
	public function handle_revoke_consent(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		$user_id      = get_current_user_id();
		$consent_type = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['consent_type'] ?? '' ) );

		$this->manager->revoke_user_consent( $user_id, $consent_type );
		$this->manager->log_audit_action( $user_id, 'consent_revoked', 'consent', $consent_type );

		\wp_send_json_success( array( 'message' => __( 'Consentement révoqué.', 'advanced-pdf-invoice-builder' ) ) );
	}

	/**
	 * Exporte les donnees utilisateur via AJAX.
	 * Retourne le contenu inline (champ 'content') attendu par settings-securite.js.
	 */
	public function handle_export_user_data(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		$user_id   = get_current_user_id();
		$format    = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['format'] ?? 'json' ) );
		$user_data = $this->data_helper->get_user_data( $user_id );
		$content   = $this->data_helper->get_export_content( $user_data, $format );

		if ( \is_wp_error( $content ) ) {
			/** Export content response. @var \WP_Error $content */
			wp_send_json_error( array( 'message' => $content->get_error_message() ) );
			return;
		}

		$this->manager->log_audit_action( $user_id, 'data_exported', 'user_data', $format );

		\wp_send_json_success( array( 'content' => $content ) );
	}
	/**
	 * Supprime les donnees utilisateur via AJAX.
	 */
	public function handle_delete_user_data(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		$user_id = get_current_user_id();
		$this->data_helper->delete_user_data( $user_id );
		$this->manager->log_audit_action( $user_id, 'data_deleted', 'user_data', 'all' );

		\wp_send_json_success( array( 'message' => __( 'Toutes vos données ont été supprimées.', 'advanced-pdf-invoice-builder' ) ) );
	}

	/**
	 * Traite la demande de portabilite des donnees via AJAX.
	 */
	public function handle_request_data_portability(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		$user_id     = get_current_user_id();
		$format      = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['format'] ?? 'json' ) );
		$export_data = $this->data_helper->get_user_data_portable( $user_id, $format );

		if ( \is_wp_error( $export_data ) ) {
			/** Portable user data response. @var \WP_Error $export_data */
			wp_send_json_error( array( 'message' => $export_data->get_error_message() ) );
			return;
		}

		$this->manager->log_audit_action( $user_id, 'data_portability_requested', 'user_data', $format );

		\wp_send_json_success(
			array(
				'message' => __( 'Demande de portabilité traitée.', 'advanced-pdf-invoice-builder' ),
				'data'    => $export_data,
			)
		);
	}

	/**
	 * Retourne le statut des consentements via AJAX.
	 * Retourne un tableau indexé de {label, value} attendu par settings-securite.js.
	 */
	public function handle_get_consent_status(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		$user_id        = get_current_user_id();
		$consent_labels = array(
			'analytics' => 'Analytics & Suivi',
			'templates' => 'Sauvegarde des Templates',
			'marketing' => 'Communications Marketing',
		);
		$consents       = array();

		foreach ( $consent_labels as $type => $label ) {
			$consents[] = array(
				'label' => $label,
				'value' => $this->manager->get_user_consent_status( $user_id, $type ),
			);
		}

		\wp_send_json_success( array( 'consents' => $consents ) );
	}

	/**
	 * Affiche la table HTML des consentements via AJAX.
	 */
	public function handle_view_consent_status(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );
		$user_id = get_current_user_id();
		$html    = $this->get_consent_status_table_html( $user_id );
		\wp_send_json_success( array( 'consent_html' => $html ) );
	}

	/**
	 * Sauvegarde les parametres RGPD via AJAX.
	 */
	public function handle_save_gdpr_settings(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) );
		}

		$this->manager->update_gdpr_options(
			array(
				'consent_required' => isset( $GLOBALS['_POST']['consent_required'] ),
				'consent_types'    => array(
					'analytics' => isset( $GLOBALS['_POST']['consent_types']['analytics'] ),
					'templates' => isset( $GLOBALS['_POST']['consent_types']['templates'] ),
					'marketing' => isset( $GLOBALS['_POST']['consent_types']['marketing'] ),
				),
			)
		);

		\wp_send_json_success( array( 'message' => esc_html__( 'Paramètres RGPD sauvegardés.', 'advanced-pdf-invoice-builder' ) ) );
	}

	/**
	 * Sauvegarde les parametres de securite RGPD via AJAX.
	 */
	public function handle_save_gdpr_security(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) );
		}

		$this->manager->update_gdpr_options(
			array(
				'encryption_enabled'  => isset( $GLOBALS['_POST']['encryption_enabled'] ),
				'data_retention_days' => \intval( wp_unslash( $GLOBALS['_POST']['data_retention_days'] ?? 2555 ) ),
				'audit_enabled'       => isset( $GLOBALS['_POST']['audit_enabled'] ),
			)
		);

		\wp_send_json_success( array( 'message' => esc_html__( 'Paramètres de sécurité sauvegardés.', 'advanced-pdf-invoice-builder' ) ) );
	}

	/**
	 * Actualise le journal d audit via AJAX.
	 */
	public function handle_refresh_audit_log(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) );
		}

		$table_audit = pdfib_db()->prefix . 'pdfib_audit_log';

		$audit_logs = pdfib_db()->get_results(
			pdfib_db()->prepare(
				'
            SELECT * FROM %i
            ORDER BY created_at DESC
            LIMIT 50
        ',
				$table_audit
			),
			ARRAY_A
		);

		$logs = array();
		foreach ( $audit_logs as $log ) {
			$ud     = $log['user_id'] ? \get_userdata( $log['user_id'] ) : null;
			$logs[] = array(
				'date'    => date_i18n( 'd/m/Y H:i', strtotime( $log['created_at'] ) ),
				'user'    => $ud ? $ud->display_name : 'Système',
				'action'  => $log['action'],
				'details' => $log['data_type'],
			);
		}

		\wp_send_json_success( array( 'logs' => $logs ) );
	}

	/**
	 * Sauvegarde les preferences utilisateur via AJAX.
	 */
	public function handle_save_preferences(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		$user_id     = get_current_user_id();
		$preferences = isset( $GLOBALS['_POST']['preferences'] ) && is_array( $GLOBALS['_POST']['preferences'] )
			? array_map( 'sanitize_text_field', wp_unslash( $GLOBALS['_POST']['preferences'] ) )
			: array();

		foreach ( $preferences as $consent_type => $granted ) {
			$this->manager->save_user_consent( $user_id, sanitize_text_field( $consent_type ), (bool) $granted );
		}

		$this->manager->log_audit_action( $user_id, 'preferences_saved', 'gdpr_preferences', count( $preferences ) . ' preferences' );

		\wp_send_json_success( array( 'message' => __( 'Préférences sauvegardées.', 'advanced-pdf-invoice-builder' ) ) );
	}

	/**
	 * Exporte le journal d audit en CSV via AJAX.
	 */
	public function handle_export_audit_log(): void {
		check_ajax_referer( 'pdfib_gdpr', 'nonce' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		$start_date        = sanitize_text_field( wp_unslash( $GLOBALS['_GET']['start_date'] ?? '' ) );
		$end_date          = sanitize_text_field( wp_unslash( $GLOBALS['_GET']['end_date'] ?? '' ) );
		$pdfib_table_audit = pdfib_db()->prefix . 'pdfib_audit_log';

		$where  = '';
		$params = array();

		if ( $start_date ) {
			$where   .= ' AND created_at >= %s';
			$params[] = $start_date . ' 00:00:00';
		}

		if ( $end_date ) {
			$where   .= ' AND created_at <= %s';
			$params[] = $end_date . ' 23:59:59';
		}

		$audit_logs = pdfib_db()->get_results(
			pdfib_db()->prepare(
				"
            SELECT * FROM %i
            WHERE 1=1 $where
            ORDER BY created_at DESC
        ",
				array_merge( array( $pdfib_table_audit ), $params )
			),
			ARRAY_A
		);

		$csv_lines = array( 'Date,Utilisateur,Action,Données concernées,IP' );
		foreach ( $audit_logs as $log ) {
			$ud           = $log['user_id'] ? \get_userdata( $log['user_id'] ) : null;
			$display_name = $ud ? $ud->display_name : 'Système';
			$csv_lines[]  = sprintf(
				'%s,%s,%s,%s,%s',
				$log['created_at'],
				$display_name,
				$log['action'],
				$log['data_type'],
				$log['ip_address']
			);
		}

		$count    = count( $audit_logs );
		$filename = 'audit-log-' . gmdate( 'Y-m-d' ) . '.csv';

		$this->manager->log_audit_action( get_current_user_id(), 'audit_log_exported', 'audit_logs', 'csv' );

		\wp_send_json_success(
			array(
				'csv'      => implode( "\n", $csv_lines ),
				'count'    => $count,
				'filename' => $filename,
			)
		);
	}

	/**
	 * Genere le HTML de la table de statut des consentements.
	 *
	 * @param int $user_id ID de l utilisateur.
	 */
	private function get_consent_status_table_html( int $user_id ): string {
		$consent_types = array(
			'analytics' => 'Analytics & Suivi',
			'templates' => 'Sauvegarde des Templates',
			'marketing' => 'Communications Marketing',
		);
		$rows          = '';
		foreach ( $consent_types as $type => $label ) {
			$status       = $this->manager->get_user_consent_status( $user_id, $type );
			$consent_data = get_user_meta( $user_id, 'pdfib_consent_' . $type, true );
			$status_text  = $status ? '✅ Accordé' : '❌ Refusé';
			$status_class = $status ? 'text-success' : 'text-danger';
			$date_text    = 'Non défini';
			if ( is_array( $consent_data ) && isset( $consent_data['timestamp'] ) ) {
				$date_text = date_i18n( 'd/m/Y H:i', $consent_data['timestamp'] );
			}
			$btn   = $status
				? '<button type="button" class="button button-small button-secondary revoke-consent" data-consent-type="' . esc_attr( $type ) . '">Révoquer</button>'
				: '<button type="button" class="button button-small button-primary grant-consent" data-consent-type="' . esc_attr( $type ) . '">Accorder</button>';
			$rows .= '<tr>'
				. '<td><strong>' . esc_html( $label ) . '</strong></td>'
				. '<td class="' . esc_attr( $status_class ) . '">' . esc_html( $status_text ) . '</td>'
				. '<td>' . esc_html( $date_text ) . '</td>'
				. '<td>' . $btn . '</td>'
				. '</tr>';
		}
		return '<table class="widefat striped" style="margin-top: 10px;">'
			. '<thead><tr><th>Type de consentement</th><th>Statut</th><th>Date de consentement</th><th>Actions</th></tr></thead>'
			. '<tbody>' . $rows . '</tbody></table>'
			. '<p style="margin-top: 15px; color: #666; font-size: 12px;"><em>💡 Vous pouvez modifier vos consentements à tout moment. Ces informations sont stockées de manière sécurisée et conforme au RGPD.</em></p>';
	}
}
