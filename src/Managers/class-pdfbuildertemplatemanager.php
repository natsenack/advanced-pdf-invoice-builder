<?php
/**
 * Advanced PDF Invoice Builder Template Manager.
 *
 * @package PDFIB
 */

namespace PDFIB\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced PDF Invoice Builder - Template Manager
 * Gestion centralisée des templates
 * Version: 1.0.4 - Fixed data handling for camelCase and JSON, cache bypass V3
 */
class PdfBuilderTemplateManager {



	/** SQL query to select a template record by ID */
	const QUERY_SELECT_BY_ID = 'SELECT * FROM %i WHERE id = %d';

	/**
	 * Instance principale du plugin.
	 *
	 * @var mixed
	 */
	private $main;

	/**
	 * Validateur de template.
	 *
	 * @var TemplateValidator
	 */
	private TemplateValidator $validator;

	/**
	 * Helper de persistance des templates.
	 *
	 * @var TemplatePersistenceHelper
	 */
	private TemplatePersistenceHelper $persistence;

	/**
	 * Constructeur.
	 *
	 * @param mixed $main_instance Instance principale.
	 */
	public function __construct( mixed $main_instance = null ) {
		$this->main        = $main_instance;
		$this->validator   = new TemplateValidator();
		$this->persistence = new TemplatePersistenceHelper();
	}

	/**
	 * Page de gestion des templates.
	 */
	public function templates_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			\wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires.', 'advanced-pdf-invoice-builder' ) );
		}

		include_once \plugin_dir_path( __DIR__ ) . '../../templates/admin/templates-page.php';
	}

	/**
	 * AJAX - Sauvegarder un template.
	 */
	public function ajax_save_template_v3() {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			check_ajax_referer( 'pdfib_ajax', 'nonce' );
			$json_data     = $this->parse_save_request_json_body();
			$fields        = $this->extract_template_request_fields( $json_data );
			$template_name = $fields['template_name'];
			$template_id   = $fields['template_id'];
			$template_data = $this->build_and_validate_template_data( $json_data, $fields );
			if ( empty( $template_data ) || empty( $template_name ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Données template ou nom manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';
			try {
				$existing_template = $this->persistence->persist_template( $template_id, $template_name, $template_data, $table_templates );
			} catch ( \Exception $e ) {
				\wp_send_json_error( array( 'message' => __( 'Erreur lors de la sauvegarde: ', 'advanced-pdf-invoice-builder' ) . esc_html( $e->getMessage() ) ) );
				return;
			}
			$element_count = $this->persistence->verify_saved( $template_id, $existing_template, $table_templates );
			if ( class_exists( 'PDF_Builder_Cache_Manager' ) && ! empty( $template_id ) ) {
				\PDF_Builder_Cache_Manager::get_instance()->delete( 'template_' . $template_id );
			}
			\wp_send_json_success(
				array(
					'message'       => __( 'Template sauvegardé avec succès', 'advanced-pdf-invoice-builder' ),
					'template_id'   => $template_id,
					'template_name' => $template_name,
					'name'          => $template_name,
					'element_count' => $element_count,
				)
			);
		} catch ( \Throwable $e ) {
			\wp_send_json_error( array( 'message' => __( 'Erreur critique lors de la sauvegarde: ', 'advanced-pdf-invoice-builder' ) . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Construit et valide les données template, retourne la chaîne JSON ou '' en cas d'erreur.
	 *
	 * @param array|null $json_data Données JSON décodées ou null si POST.
	 * @param array      $fields    Champs extraits de la requête.
	 * @return string
	 */
	private function build_and_validate_template_data( ?array $json_data, array $fields ): string {
		try {
			$template_data = $this->build_template_data_payload( $json_data, $fields );
		} catch ( \Exception $build_ex ) {
			\wp_send_json_error( array( 'message' => esc_html( $build_ex->getMessage() ) ) );
			return '';
		}
		return $this->validate_and_return_payload( $template_data );
	}

	/**
	 * Valide la chaîne JSON construite et retourne la chaîne ou '' en cas d'erreur.
	 *
	 * @param string|null $template_data Chaîne JSON à valider.
	 * @return string
	 */
	private function validate_and_return_payload( ?string $template_data ): string {
		if ( ! is_string( $template_data ) || empty( $template_data ) ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Données template manquantes', 'advanced-pdf-invoice-builder' ) ) );
			return '';
		}
		$decoded = \json_decode( $template_data, true );
		$errors  = \json_last_error() !== JSON_ERROR_NONE
			? array( 'Données JSON invalides: ' . \json_last_error_msg() )
			: $this->validator->validate_template_data( $decoded );
		if ( ! empty( $errors ) ) {
			$msg = \json_last_error() !== JSON_ERROR_NONE
				? esc_html( $errors[0] )
				: esc_html( 'Structure invalide: ' . \implode( ', ', $errors ) );
			\wp_send_json_error( array( 'message' => $msg ) );
			return '';
		}
		return $template_data;
	}

	/** Parse le corps JSON de la requête si Content-Type: application/json. */
	private function parse_save_request_json_body(): ?array {
		if ( isset( $GLOBALS['_SERVER']['CONTENT_TYPE'] ) && strpos( sanitize_text_field( wp_unslash( $GLOBALS['_SERVER']['CONTENT_TYPE'] ) ), 'application/json' ) !== false ) {
			$json_input = pdfib_get_raw_input();
			$json_data  = json_decode( $json_input, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				\wp_send_json_error( array( 'message' => esc_html( 'Données JSON invalides dans le corps de la requête: ' . json_last_error_msg() ) ) );
				return null;
			}
			return $json_data;
		}
		return null;
	}

	/**
	 * Dispatcher: bâtit le payload pour un nouveau template ou enrichit un template existant.
	 *
	 * @param array|null $json_data Données JSON décodées ou null si POST.
	 * @param array      $fields    Champs extraits de la requête.
	 * @return string
	 */
	private function build_template_data_payload( ?array $json_data, array $fields ): string {
		if ( empty( $fields['template_data'] ) ) {
			return $this->build_new_template_payload( $json_data, $fields );
		}
		$decoded = \json_decode( $fields['template_data'], true );
		if ( \json_last_error() !== JSON_ERROR_NONE ) {
			\wp_send_json_error( array( 'message' => esc_html( 'Données template JSON invalides: ' . \json_last_error_msg() ) ) );
			return '';
		}
		return $this->enrich_existing_payload( $decoded, $fields );
	}

	/**
	 * Assemble un nouveau template depuis les éléments et le canvas bruts.
	 *
	 * @param array|null $json_data Données JSON décodées ou null si POST.
	 * @param array      $fields    Champs extraits de la requête.
	 * @return string
	 */
	private function build_new_template_payload( ?array $json_data, array $fields ): string {
		if ( $json_data ) {
			$el_raw = $json_data['elements'] ?? $json_data['elementsData'] ?? null;
			$cv_raw = $json_data['canvas'] ?? $json_data['canvasData'] ?? null;
			$el_str = null !== $el_raw ? \wp_json_encode( $el_raw ) : '[]';
			$cv_str = null !== $cv_raw ? \wp_json_encode( $cv_raw ) : '{}';
		} else {
			$el_raw = $GLOBALS['_POST']['elements'] ?? $GLOBALS['_POST']['elementsData'] ?? null;
			$cv_raw = $GLOBALS['_POST']['canvas'] ?? $GLOBALS['_POST']['canvasData'] ?? null;
			$el_str = null !== $el_raw ? sanitize_textarea_field( \wp_unslash( $el_raw ) ) : '[]';
			$cv_str = null !== $cv_raw ? sanitize_textarea_field( \wp_unslash( $cv_raw ) ) : '{}';
		}
		$elements_data = \json_decode( $el_str, true );
		if ( \json_last_error() !== JSON_ERROR_NONE ) {
			\wp_send_json_error( array( 'message' => esc_html( 'Données elements JSON invalides: ' . \json_last_error_msg() ) ) );
			return '';
		}
		if ( is_array( $elements_data ) ) {
			$elements_data = map_deep( $elements_data, 'wp_kses_post' );
		}
		$canvas_data = \json_decode( $cv_str, true );
		if ( \json_last_error() !== JSON_ERROR_NONE ) {
			\wp_send_json_error( array( 'message' => esc_html( 'Données canvas JSON invalides: ' . \json_last_error_msg() ) ) );
			return '';
		}
		if ( is_array( $canvas_data ) ) {
			$canvas_data = map_deep( $canvas_data, 'wp_kses_post' );
		}
		$this->enrich_company_logo_elements( $elements_data );
		$structure = array(
			'elements'     => $elements_data,
			'canvasWidth'  => $canvas_data['width'] ?? $canvas_data['canvasWidth'] ?? 794,
			'canvasHeight' => $canvas_data['height'] ?? $canvas_data['canvasHeight'] ?? 1123,
			'version'      => '1.0',
			'name'         => $fields['template_name'],
			'description'  => $fields['template_description'],
			'showGuides'   => $fields['show_guides'],
			'snapToGrid'   => $fields['snap_to_grid'],
			'marginTop'    => $fields['margin_top'],
			'marginBottom' => $fields['margin_bottom'],
			'marginLeft'   => $fields['margin_left'],
			'marginRight'  => $fields['margin_right'],
		);
		$result    = \wp_json_encode( $structure );
		if ( false === $result ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de l\'encodage des données template', 'advanced-pdf-invoice-builder' ) ) );
		}
		return (string) $result;
	}

	/**
	 * Enrichit un template existant décodé avec les champs de la requête et l'encode.
	 *
	 * @param array $decoded Décodage JSON du template existant.
	 * @param array $fields  Champs extraits de la requête.
	 * @return string
	 */
	private function enrich_existing_payload( array $decoded, array $fields ): string {
		$decoded['name']         = $fields['template_name'];
		$decoded['description']  = $fields['template_description'];
		$decoded['showGuides']   = $fields['show_guides'];
		$decoded['snapToGrid']   = $fields['snap_to_grid'];
		$decoded['marginTop']    = $fields['margin_top'];
		$decoded['marginBottom'] = $fields['margin_bottom'];
		$decoded['marginLeft']   = $fields['margin_left'];
		$decoded['marginRight']  = $fields['margin_right'];
		$result                  = \wp_json_encode( $decoded );
		if ( false === $result ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de l\'encodage des données template enrichies', 'advanced-pdf-invoice-builder' ) ) );
		}
		return (string) $result;
	}

	/**
	 * Extrait les champs template depuis une requête JSON ou FormData.
	 *
	 * @param array|null $json_data Données JSON décodées, ou null si FormData.
	 * @return array{template_data: string, template_name: string, template_description: string, show_guides: bool, snap_to_grid: bool, margin_top: int, margin_bottom: int, margin_left: int, margin_right: int, canvas_width: int, canvas_height: int, template_id: int}
	 */
	private function extract_template_request_fields( ?array $json_data ): array {
		if ( $json_data ) {
			return array(
				'template_data'        => isset( $json_data['templateData'] ) ? \wp_json_encode( $json_data['templateData'] ) : ( $json_data['template_data'] ?? '' ),
				'template_name'        => \sanitize_text_field( $json_data['templateName'] ?? $json_data['template_name'] ?? '' ),
				'template_description' => \sanitize_text_field( $json_data['templateDescription'] ?? $json_data['template_description'] ?? '' ),
				'show_guides'          => (bool) ( $json_data['showGuides'] ?? $json_data['show_guides'] ?? false ),
				'snap_to_grid'         => (bool) ( $json_data['snapToGrid'] ?? $json_data['snap_to_grid'] ?? false ),
				'margin_top'           => \intval( $json_data['marginTop'] ?? $json_data['margin_top'] ?? 0 ),
				'margin_bottom'        => \intval( $json_data['marginBottom'] ?? $json_data['margin_bottom'] ?? 0 ),
				'margin_left'          => \intval( $json_data['marginLeft'] ?? $json_data['margin_left'] ?? 0 ),
				'margin_right'         => \intval( $json_data['marginRight'] ?? $json_data['margin_right'] ?? 0 ),
				'canvas_width'         => \intval( $json_data['canvasWidth'] ?? $json_data['canvas_width'] ?? 0 ),
				'canvas_height'        => \intval( $json_data['canvasHeight'] ?? $json_data['canvas_height'] ?? 0 ),
				'template_id'          => \intval( $json_data['templateId'] ?? $json_data['template_id'] ?? 0 ),
			);
		}
		// FormData (POST).
		$post              = $GLOBALS['_POST'];
		$template_data_raw = $post['template_data'] ?? $post['templateData'] ?? null;
		$template_name_raw = $post['template_name'] ?? $post['templateName'] ?? null;
		$template_desc_raw = $post['template_description'] ?? $post['templateDescription'] ?? null;
		$template_id_raw   = $post['template_id'] ?? $post['templateId'] ?? null;
		return array(
			'template_data'        => null !== $template_data_raw ? sanitize_textarea_field( wp_unslash( $template_data_raw ) ) : '',
			'template_name'        => null !== $template_name_raw ? \sanitize_text_field( wp_unslash( $template_name_raw ) ) : '',
			'template_description' => null !== $template_desc_raw ? \sanitize_text_field( wp_unslash( $template_desc_raw ) ) : '',
			'show_guides'          => isset( $post['show_guides'] ) ? (bool) sanitize_text_field( wp_unslash( $post['show_guides'] ) ) : false,
			'snap_to_grid'         => isset( $post['snap_to_grid'] ) ? (bool) sanitize_text_field( wp_unslash( $post['snap_to_grid'] ) ) : false,
			'margin_top'           => isset( $post['margin_top'] ) ? \intval( wp_unslash( $post['margin_top'] ) ) : 0,
			'margin_bottom'        => isset( $post['margin_bottom'] ) ? \intval( wp_unslash( $post['margin_bottom'] ) ) : 0,
			'margin_left'          => isset( $post['margin_left'] ) ? \intval( wp_unslash( $post['margin_left'] ) ) : 0,
			'margin_right'         => isset( $post['margin_right'] ) ? \intval( wp_unslash( $post['margin_right'] ) ) : 0,
			'canvas_width'         => isset( $post['canvas_width'] ) ? \intval( wp_unslash( $post['canvas_width'] ) ) : 0,
			'canvas_height'        => isset( $post['canvas_height'] ) ? \intval( wp_unslash( $post['canvas_height'] ) ) : 0,
			'template_id'          => null !== $template_id_raw ? \intval( wp_unslash( $template_id_raw ) ) : 0,
		);
	}

	/**
	 * AJAX - Auto-sauvegarde d'un template (version simplifiée).
	 */
	public function ajax_auto_save_template() {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! isset( $GLOBALS['_REQUEST']['nonce'] ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Sécurité: Nonce manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$nonce = sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['nonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'pdfib_ajax' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Sécurité: Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_id  = isset( $GLOBALS['_REQUEST']['template_id'] ) ? \intval( wp_unslash( $GLOBALS['_REQUEST']['template_id'] ) ) : 0;
			$elements_raw = isset( $GLOBALS['_REQUEST']['template_data'] ) ? sanitize_textarea_field( \wp_unslash( $GLOBALS['_REQUEST']['template_data'] ) ) : '[]';
			if ( empty( $template_id ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'ID template invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$elements = \json_decode( $elements_raw, true );
			if ( null === $elements && \json_last_error() !== JSON_ERROR_NONE ) {
				\wp_send_json_error( array( 'message' => esc_html( 'Données des éléments corrompues - Erreur JSON: ' . \json_last_error_msg() ) ) );
				return;
			}
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';
			$template_row    = pdfib_db()->get_row(
				pdfib_db()->prepare( self::QUERY_SELECT_BY_ID, $table_templates, $template_id ),
				\ARRAY_A
			);
			if ( ! $template_row ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Template non trouvé pour auto-save', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$existing_data = \json_decode( $template_row['template_data'], true ) ?? array(
				'elements' => array(),
				'canvas'   => array(),
			);
			$this->enrich_company_logo_elements( $elements );
			$json_data = $this->build_auto_save_template_payload( $elements, $existing_data );
			$this->update_auto_save_template_record( $template_id, $json_data, $table_templates );
			if ( class_exists( 'PDF_Builder_Cache_Manager' ) && ! empty( $template_id ) ) {
				\PDF_Builder_Cache_Manager::get_instance()->delete( 'template_' . $template_id );
			}
			\wp_send_json_success(
				array(
					'message'        => __( 'Auto-save réussi', 'advanced-pdf-invoice-builder' ),
					'template_id'    => $template_id,
					'saved_at'       => \current_time( 'mysql' ),
					'element_count'  => count( $elements ),
					'elements_saved' => $elements,
				)
			);
		} catch ( \Throwable $e ) {
			\wp_send_json_error( array( 'message' => __( 'Erreur critique: ', 'advanced-pdf-invoice-builder' ) . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Enrichit les éléments company_logo avec le logo WP si src est absent.
	 *
	 * @param array $elements Éléments canvas passés par référence.
	 * @return void
	 */
	private function enrich_company_logo_elements( array &$elements ): void {
		foreach ( $elements as &$el ) {
			if ( isset( $el['type'] ) && 'company_logo' === $el['type'] && empty( $el['src'] ) && empty( $el['logoUrl'] ) ) {
				$custom_logo_id = \get_theme_mod( 'custom_logo' );
				if ( $custom_logo_id ) {
					$logo_url = \wp_get_attachment_image_url( $custom_logo_id, 'full' );
					if ( $logo_url ) {
						$el['src'] = $logo_url;
					}
				}
			}
		}
		unset( $el );
	}

	/**
	 * Assemble et encode la payload JSON pour l'auto-save.
	 *
	 * @param array $elements      Éléments canvas sérialisés.
	 * @param array $existing_data Données du template existant.
	 * @return string
	 */
	private function build_auto_save_template_payload( array $elements, array $existing_data ): string {
		$template_data = array(
			'elements'     => $elements,
			'canvas'       => $existing_data['canvas'] ?? array(),
			'canvasWidth'  => $existing_data['canvasWidth'] ?? 210,
			'canvasHeight' => $existing_data['canvasHeight'] ?? 297,
			'version'      => '1.0',
		);
		$json_data     = \wp_json_encode( $template_data );
		if ( false === $json_data ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de l\'encodage des données JSON', 'advanced-pdf-invoice-builder' ) ) );
		}
		return $json_data;
	}

	/**
	 * Met à jour l'enregistrement en base pour l'auto-save.
	 *
	 * @param int    $template_id ID du template.
	 * @param string $json_data   Données JSON encodées.
	 * @param string $table       Nom de la table.
	 * @return void
	 */
	private function update_auto_save_template_record( int $template_id, string $json_data, string $table ): void {
		$updated = pdfib_db()->update(
			$table,
			array(
				'template_data' => $json_data,
				'updated_at'    => \current_time( 'mysql' ),
			),
			array( 'id' => $template_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		if ( false === $updated ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de la mise à jour du template', 'advanced-pdf-invoice-builder' ) ) );
		}
	}

	/**
	 * AJAX - Charger un template.
	 */
	public function ajax_load_template() {
		if ( headers_sent() ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Impossible d\'envoyer les headers - sortie déjà commencée', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}
		header( 'Cache-Control: no-cache, no-store, must-revalidate, private' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		check_ajax_referer( 'pdfib_ajax', 'nonce' );
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_id = isset( $GLOBALS['_REQUEST']['template_id'] ) ? \intval( wp_unslash( $GLOBALS['_REQUEST']['template_id'] ) ) : 0;
			if ( empty( $template_id ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'ID template invalide', 'advanced-pdf-invoice-builder' ) ) );
			}
			$cache_key = 'template_' . $template_id;
			if ( class_exists( 'PDF_Builder_Cache_Manager' ) ) {
				$cached = \PDF_Builder_Cache_Manager::get_instance()->get( $cache_key );
				if ( false !== $cached && is_array( $cached ) ) {
					\wp_send_json_success( $cached );
					return;
				}
			}
			$resolved          = $this->persistence->resolve_by_id( $template_id );
			$template_data     = $resolved['data'];
			$template_name     = $resolved['name'];
			$validation_errors = $this->validator->validate_template_data( $template_data );
			if ( ! empty( $validation_errors ) ) {
				$this->validator->apply_fallbacks( $template_data );
			}
			$response = array(
				'template'      => $template_data,
				'template_name' => $template_name,
			);
			if ( class_exists( 'PDF_Builder_Cache_Manager' ) ) {
				\PDF_Builder_Cache_Manager::get_instance()->set( $cache_key, $response );
			}
			\wp_send_json_success( $response );
		} catch ( \Throwable $e ) {
			\wp_send_json_error( array( 'message' => __( 'Erreur critique: ', 'advanced-pdf-invoice-builder' ) . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * AJAX - Vider le cache REST.
	 */
	public function ajax_flush_rest_cache() {
		check_ajax_referer( 'pdfib_ajax', 'nonce' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		// Vider le cache des transients.
		$db = pdfib_db();
		$db->query( $db->prepare( 'DELETE FROM %i WHERE option_name LIKE %s', $db->options, $db->esc_like( '_transient_pdfib_' ) . '%' ) );
		$db->query( $db->prepare( 'DELETE FROM %i WHERE option_name LIKE %s', $db->options, $db->esc_like( '_transient_timeout_pdfib_' ) . '%' ) );

		\wp_send_json_success( 'Cache REST vidé avec succès' );
	}

	/**
	 * Charger un template de manière robuste.
	 *
	 * @param int $template_id ID du template.
	 * @return mixed
	 */
	public function load_template_robust( int $template_id ) {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		if ( self::is_debug_mode() ) {
			return $this->persistence->load_robust_debug( $template_id, $table_templates );
		}
		return array(
			'name' => null,
			'data' => null,
		);
	}

	/**
	 * Vérifier si le mode debug est activé (WP_DEBUG ou debug PHP activé).
	 *
	 * @return bool
	 */
	private static function is_debug_mode() {
		$settings          = pdfib_get_option( 'pdfib_settings', array() );
		$php_debug_enabled = isset( $settings['pdfib_debug_php_errors'] ) && $settings['pdfib_debug_php_errors'];
		if ( $php_debug_enabled ) {
			return true;
		}
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}
}
