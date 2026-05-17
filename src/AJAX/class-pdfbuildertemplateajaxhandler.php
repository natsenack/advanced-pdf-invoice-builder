<?php
/**
 * Advanced PDF Invoice Builder - AJAX Template Handler.
 *
 * @package PDFIB\AJAX
 */

namespace PDFIB\AJAX;

defined( 'ABSPATH' ) || exit;

/**
 * Handler AJAX pour les templates.
 */
class PdfBuilderTemplateAjaxHandler extends PdfBuilderAjaxBase {

	/**
	 * Traite la requete AJAX principale.
	 */
	public function handle() {
		try {
			$this->validate_request();

			$action = $this->validate_required_param( 'template_action' );

			switch ( $action ) {
				case 'save':
					$this->handleSaveTemplate();
					break;
				case 'load':
					$this->handleLoadTemplate();
					break;
				case 'delete':
					$this->handleDeleteTemplate();
					break;
				default:
					$this->send_error( __( 'Action template inconnue', 'advanced-pdf-invoice-builder' ), 400 );
			}
		} catch ( \Exception $e ) {
			$this->log_error( 'Erreur template AJAX: ' . $e->getMessage() );
			$this->send_error( __( 'Erreur interne du serveur', 'advanced-pdf-invoice-builder' ), 500 );
		}
	}

	/**
	 * Sauvegarde un template.
	 *
	 * @return void
	 */
	private function handleSaveTemplate() {
		check_ajax_referer( $this->nonce_action, 'nonce', false );
		$template_data = $this->validate_required_param( 'template_data', 'json' );
		$template_id   = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
		$template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';
		if ( empty( $template_name ) && isset( $template_data['name'] ) ) {
			$template_name = sanitize_text_field( (string) $template_data['name'] );
		}
		if ( empty( $template_name ) ) {
			$template_name = 'Nouveau template';
		}

		if ( $template_id <= 0 && class_exists( '\PDFIB\Admin\PdfBuilderAdmin' ) && ! \PDFIB\Admin\PdfBuilderAdmin::can_create_template() ) {
			$this->send_error(
				__( 'La version gratuite permet un seul template personnalisé. Supprimez-en un pour en créer un nouveau.', 'advanced-pdf-invoice-builder' ),
				403
			);
			return;
		}

		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		if ( $template_id <= 0 ) {
			$this->insertNewTemplate( $template_name, $template_data, $table_templates );
		}
		$this->updateExistingTemplate( $template_id, $template_name, $template_data, $table_templates );
	}

	/**
	 * Insere un nouveau template en base de donnees.
	 *
	 * @param string $template_name Nom du template.
	 * @param array  $template_data Donnees du template.
	 * @param string $table Nom de la table.
	 * @return void
	 */
	private function insertNewTemplate( string $template_name, array $template_data, string $table ): void {
		$result = pdfib_db()->insert(
			$table,
			array(
				'name'          => $template_name,
				'template_data' => wp_json_encode( $template_data ),
				'user_id'       => get_current_user_id(),
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
				'is_default'    => 0,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%d' )
		);
		if ( false === $result ) {
			pdfib_debug_log( '[PDFIB_SAVE_TEMPLATE] ❌ Création échouée: ' . pdfib_db()->last_error );
			$this->send_error( __( 'Erreur lors de la création du template: ', 'advanced-pdf-invoice-builder' ) . pdfib_db()->last_error, 500 );
		}
		$template_id = (int) pdfib_db()->insert_id;
		$this->send_success(
			array(
				'template_id'  => $template_id,
				'name'         => $template_name,
				'redirect_url' => admin_url( 'admin.php?page=pdf-builder-react-editor&template_id=' . $template_id ),
			),
			__( 'Template créé avec succès', 'advanced-pdf-invoice-builder' )
		);
	}

	/**
	 * Met a jour un template existant en base de donnees.
	 *
	 * @param int    $template_id Identifiant du template.
	 * @param string $template_name Nom du template.
	 * @param array  $template_data Donnees du template.
	 * @param string $table Nom de la table.
	 * @return void
	 */
	private function updateExistingTemplate( int $template_id, string $template_name, array $template_data, string $table ): void {
		pdfib_preserve_template_settings_fields( $template_id, $template_data, pdfib_db() );
		$result = pdfib_db()->update(
			$table,
			array(
				'template_data' => wp_json_encode( $template_data ),
				'updated_at'    => \current_time( 'mysql' ),
			),
			array( 'id' => $template_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		if ( false === $result ) {
			pdfib_debug_log( '[PDFIB_SAVE_TEMPLATE] ❌ Modification échouée: ' . pdfib_db()->last_error );
			$this->send_error( __( 'Erreur lors de la sauvegarde: ', 'advanced-pdf-invoice-builder' ) . pdfib_db()->last_error, 500 );
		}
		$this->send_success(
			array(
				'template_id' => $template_id,
				'name'        => $template_name,
			),
			__( 'Template sauvegardé avec succès', 'advanced-pdf-invoice-builder' )
		);
	}

	/**
	 * Charge un template.
	 *
	 * @return void
	 */
	private function handleLoadTemplate() {
		check_ajax_referer( $this->nonce_action, 'nonce', false );
		$template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;

		if ( $template_id <= 0 ) {
			$this->send_success(
				array(
					'template' => null,
					'id'       => 0,
					'name'     => '',
				),
				__( 'Aucun template', 'advanced-pdf-invoice-builder' )
			);
		}

		$table_templates = pdfib_db()->prefix . 'pdfib_templates';

		$template = pdfib_db()->get_row(
			pdfib_db()->prepare( 'SELECT * FROM %i WHERE id = %d', $table_templates, $template_id ),
			ARRAY_A
		);

		if ( ! $template ) {
			$this->send_error( __( 'Template non trouvé', 'advanced-pdf-invoice-builder' ), 404 );
		}

		$template_data = json_decode( $template['template_data'], true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->send_error( __( 'Erreur de décodage JSON', 'advanced-pdf-invoice-builder' ), 500 );
		}

		$this->send_success(
			array(
				'template' => $template_data,
				'id'       => $template['id'],
				'name'     => $template['name'],
			)
		);
	}

	/**
	 * Supprime un template.
	 *
	 * @return void
	 */
	private function handleDeleteTemplate() {
		$template_id     = $this->validate_required_param( 'template_id', 'int' );
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';

		$result = pdfib_db()->delete( $table_templates, array( 'id' => $template_id ), array( '%d' ) );

		if ( false === $result ) {
			$this->send_error( __( 'Erreur lors de la suppression', 'advanced-pdf-invoice-builder' ), 500 );
		}

		$this->send_success( array(), __( 'Template supprimé avec succès', 'advanced-pdf-invoice-builder' ) );
	}

	/**
	 * Handlers directs pour les appels depuis React sans template_action.
	 */
	public function handleSaveDirect() {
		try {
			$this->validate_request();
			$this->handleSaveTemplate();
		} catch ( \Exception $e ) {
			pdfib_debug_log( '[PDFIB_AJAX] ❌ Exception: ' . $e->getMessage() );
			$this->log_error( 'Erreur save template: ' . $e->getMessage() );
			$this->send_error( $e->getMessage(), 500 );
		}
	}

	/**
	 * Charge un template directement pour React sans template_action.
	 */
	public function handleLoadDirect() {
		try {
			$this->validate_request();
			$this->handleLoadTemplate();
		} catch ( \Exception $e ) {
			$this->log_error( 'Erreur load template: ' . $e->getMessage() );
			$this->send_error( $e->getMessage(), 500 );
		}
	}
}
