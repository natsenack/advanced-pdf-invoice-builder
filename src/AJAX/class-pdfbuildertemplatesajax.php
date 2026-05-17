<?php
/**
 * Advanced PDF Invoice Builder - Templates AJAX Handler
 *
 * Gestion des actions AJAX pour les templates prédéfinis.
 *
 * @package PDFIB\AJAX
 */

namespace PDFIB\AJAX;

use Exception;
use PDFIB\HTMLGenerators\DocumentHTMLGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * Gère les actions AJAX des templates PDF.
 *
 * @package PDFIB\AJAX
 */
class PdfBuilderTemplatesAjax {

	/**
	 * Requête SQL de chargement d'un template par identifiant.
	 *
	 * @var string
	 */
	private const SQL_SELECT_TEMPLATE_BY_ID = 'SELECT * FROM %i WHERE id = %d';

	/**
	 * Instance singleton du handler.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Enregistre l'instance du handler AJAX.
	 *
	 * @return void
	 */
	public static function register(): void {
		self::$instance ??= new self();
	}

	/**
	 * Enregistre les hooks AJAX du gestionnaire.
	 */
	public function __construct() {
		// Actions pour les templates prédéfinis - géré dans predefined-templates-manager.php.
		\add_action( 'wp_ajax_pdfib_create_from_predefined', array( $this, 'create_from_predefined' ) );
		\add_action( 'wp_ajax_pdfib_load_predefined_into_editor', array( $this, 'load_predefined_into_editor' ) );
		// Actions pour les templates personnalisés.
		\add_action( 'wp_ajax_pdfib_load_template_settings', array( $this, 'load_template_settings' ) );
		\add_action( 'wp_ajax_pdfib_save_template_settings', array( $this, 'save_template_settings' ) );
		\add_action( 'wp_ajax_pdfib_set_default_template', array( $this, 'set_default_template' ) );
		\add_action( 'wp_ajax_pdfib_toggle_default_template', array( $this, 'toggle_default_template' ) );
		\add_action( 'wp_ajax_pdfib_delete_template', array( $this, 'delete_template' ) );
		\add_action( 'wp_ajax_pdfib_duplicate_template', array( $this, 'duplicate_template' ) );
		\add_action( 'wp_ajax_pdfib_save_order_status_templates', array( $this, 'save_order_status_templates' ) );
		\add_action( 'wp_ajax_pdfib_render_template_html', array( $this, 'render_template_html' ) );
	}

	/**
	 * Vérifie si un nouveau template personnalisé peut encore être créé.
	 *
	 * @return bool
	 */
	private function can_create_custom_template(): bool {
		if ( class_exists( '\PDFIB\Admin\PdfBuilderAdmin' ) ) {
			return \PDFIB\Admin\PdfBuilderAdmin::can_create_template();
		}

		$pdfib_license_manager = apply_filters( 'pdfib_license_manager_instance', null );
		if ( is_object( $pdfib_license_manager )
			&& method_exists( $pdfib_license_manager, 'is_premium' )
			&& $pdfib_license_manager->is_premium() ) {
			return true;
		}

		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		$template_count  = (int) pdfib_db()->get_var(
			pdfib_db()->prepare(
				'SELECT COUNT(*) FROM %i WHERE user_id = %d AND is_default = %d',
				$table_templates,
				get_current_user_id(),
				0
			)
		);

		return $template_count < 1;
	}

	/**
	 * Envoie une erreur standard quand le quota gratuit est atteint.
	 *
	 * @return void
	 */
	private function deny_custom_template_creation(): void {
		wp_send_json_error(
			array(
				'message' => esc_html__(
					'La version gratuite permet un seul template personnalisé. Supprimez-en un pour en créer un nouveau.',
					'advanced-pdf-invoice-builder'
				),
			),
			403
		);
	}

	/**
	 * Charge un template prédéfini depuis le fichier JSON.
	 */
	public function load_predefined_template() {
		try {
			// Vérification des permissions.
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Vérification du nonce.
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$template_slug = \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_slug'] ?? '' ) );
			if ( empty( $template_slug ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Slug du template manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Chemin vers le dossier des templates prédéfinis (peut être surchargé par le plugin PRO).
			$predefined_dir = apply_filters( 'pdfib_predefined_templates_dir', plugin_dir_path( dirname( __DIR__ ) ) . 'templates/predefined/' );
			$template_file  = $predefined_dir . $template_slug . '.json';
			if ( ! file_exists( $template_file ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Template prédéfini non trouvé', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Charger le contenu du template.
			$content       = pdfib_filesystem()->get_contents( $template_file );
			$template_data = json_decode( $content, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors du décodage du JSON du template', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			\wp_send_json_success(
				array(
					'template' => $template_data,
					'slug'     => $template_slug,
				)
			);
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors du chargement du template: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Crée un nouveau template personnalisé à partir d'un template prédéfini
	 */
	public function create_from_predefined() {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_slug = \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_slug'] ?? '' ) );
			$template_name = \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_name'] ?? '' ) );
			if ( empty( $template_slug ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Slug du template manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( empty( $template_name ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nom du template manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! $this->can_create_custom_template() ) {
				$this->deny_custom_template_creation();
				return;
			}
			$template_data   = $this->load_predefined_json_file( $template_slug );
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';
			$result          = pdfib_db()->insert(
				$table_templates,
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
				\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de la création du template dans la base de données', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$template_id = (int) pdfib_db()->insert_id;
			\wp_send_json_success(
				array(
					'template_id'  => $template_id,
					'message'      => 'Redirection vers l\'éditeur unique',
					'redirect_url' => admin_url( 'admin.php?page=pdf-builder-react-editor&template_id=' . $template_id ),
				)
			);
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors de la création du template: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Charge et parse un fichier JSON de template prédéfini.
	 *
	 * Appelle wp_send_json_error si le fichier est absent ou invalide.
	 *
	 * @param string $slug Slug du template prédéfini.
	 * @return array
	 */
	private function load_predefined_json_file( string $slug ): array {
		$predefined_dir = apply_filters( 'pdfib_predefined_templates_dir', plugin_dir_path( dirname( __DIR__ ) ) . 'templates/predefined/' );
		$template_file  = $predefined_dir . $slug . '.json';
		if ( ! file_exists( $template_file ) ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Template prédéfini non trouvé', 'advanced-pdf-invoice-builder' ) ) );
			return array();
		}
		$content = pdfib_filesystem()->get_contents( $template_file );
		$data    = json_decode( $content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors du décodage du JSON du template', 'advanced-pdf-invoice-builder' ) ) );
			return array();
		}
		return $data;
	}

	/**
	 * Charge un template prédéfini dans l'éditeur React.
	 *
	 * @return void
	 */
	public function load_predefined_into_editor() {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_slug = \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_slug'] ?? '' ) );
			if ( empty( $template_slug ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Slug du modèle prédéfini manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! $this->can_create_custom_template() ) {
				$this->deny_custom_template_creation();
				return;
			}
			$predefined_data = $this->load_predefined_json_file( $template_slug );
			if ( ! isset( $predefined_data['elements'] ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Format du modèle prédéfini invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_data   = $this->build_predefined_template_data( $predefined_data, $template_slug );
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';
			$result          = pdfib_db()->insert(
				$table_templates,
				array(
					'name'          => $template_data['name'],
					'template_data' => wp_json_encode( $template_data ),
					'user_id'       => get_current_user_id(),
					'created_at'    => current_time( 'mysql' ),
					'updated_at'    => current_time( 'mysql' ),
					'is_default'    => 0,
				)
			);
			if ( false === $result ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de la création du template', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_id = pdfib_db()->insert_id;
			\wp_send_json_success(
				array(
					'message'      => 'Modèle prédéfini chargé avec succès',
					'template_id'  => $template_id,
					'redirect_url' => admin_url( 'admin.php?page=pdf-builder-react-editor&template_id=' . $template_id ),
				)
			);
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors du chargement du modèle prédéfini: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Construit les données d'un template depuis un modèle prédéfini.
	 *
	 * @param array  $predefined_data Données brutes du template prédéfini.
	 * @param string $slug            Slug du template prédéfini.
	 * @return array<string,mixed>
	 */
	private function build_predefined_template_data( array $predefined_data, string $slug ): array {
		return array(
			'name'               => $predefined_data['name'] ?? 'Template depuis modèle prédéfini',
			'elements'           => $predefined_data['elements'],
			'canvasWidth'        => $predefined_data['canvasWidth'] ?? 794,
			'canvasHeight'       => $predefined_data['canvasHeight'] ?? 1123,
			'canvas_settings'    => array(
				'width'            => $predefined_data['canvasWidth'] ?? 794,
				'height'           => $predefined_data['canvasHeight'] ?? 1123,
				'background_color' => $predefined_data['canvas_settings']['background_color'] ?? '#ffffff',
			),
			'version'            => $predefined_data['version'] ?? '1.0',
			'last_modified'      => current_time( 'mysql' ),
			'is_from_predefined' => true,
			'predefined_slug'    => $slug,
		);
	}

	/**
	 * Charge les paramètres d'un template enregistré.
	 *
	 * @return void
	 */
	public function load_template_settings() {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_id = intval( wp_unslash( $GLOBALS['_POST']['template_id'] ?? 0 ) );
			if ( empty( $template_id ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'ID du template manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';
			$template        = pdfib_db()->get_row( pdfib_db()->prepare( self::SQL_SELECT_TEMPLATE_BY_ID, $table_templates, $template_id ), ARRAY_A );
			if ( ! $template ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Template non trouvé', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_data = json_decode( $template['template_data'] ?? '{}', true );
			if ( ! is_array( $template_data ) ) {
				$template_data = array();
			}
			$meta     = $this->guess_template_meta( $template['name'], $template_data );
			$settings = $this->build_load_template_settings_data( $template, $template_data, $meta );
			\wp_send_json_success( array( 'template' => $settings ) );
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors du chargement: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Déduit la description et la catégorie d'un template.
	 *
	 * @param string $template_name Nom du template.
	 * @param array  $template_data Données du template.
	 * @return array{description:string,category:string}
	 */
	private function guess_template_meta( string $template_name, array $template_data ): array {
		$has_stored_category = isset( $template_data['category'] ) && ! empty( $template_data['category'] );
		$category            = $has_stored_category ? $template_data['category'] : 'autre';
		$description         = $template_data['description'] ?? '';
		if ( ! empty( $description ) ) {
			return compact( 'description', 'category' );
		}
		$name_lower = strtolower( $template_name );
		$map        = array(
			array( array( 'facture', 'invoice' ), 'Template de facture personnalisé', 'facture' ),
			array( array( 'devis', 'quote' ), 'Template de devis personnalisé', 'devis' ),
			array( array( 'commande', 'order' ), 'Template de commande personnalisé', 'commande' ),
			array( array( 'contrat', 'contract' ), 'Template de contrat personnalisé', 'contrat' ),
			array( array( 'newsletter' ), 'Template de newsletter personnalisé', 'newsletter' ),
		);
		foreach ( $map as list($keywords, $desc, $cat) ) {
			foreach ( $keywords as $kw ) {
				if ( false !== strpos( $name_lower, $kw ) ) {
					$description = $desc;
					if ( ! $has_stored_category ) {
						$category = $cat;
					}
					return compact( 'description', 'category' );
				}
			}
		}
		$description = 'Template personnalisé';
		return compact( 'description', 'category' );
	}

	/**
	 * Construit les données de réponse pour le chargement des paramètres du template.
	 *
	 * @param array $template      Ligne du template en base.
	 * @param array $template_data Données JSON du template.
	 * @param array $meta          Métadonnées calculées.
	 * @return array<string,mixed>
	 */
	private function build_load_template_settings_data( array $template, array $template_data, array $meta ): array {
		$canvas_manager  = \PDFIB\Canvas\CanvasManager::get_instance();
		$canvas_settings = $canvas_manager->get_all_settings();
		$settings        = pdfib_get_option( 'pdfib_settings', array() );
		if ( empty( $settings ) ) {
			$settings = array(
				'pdfib_available_formats'      => array( 'A3', 'A4', 'A5', 'Letter', 'Legal' ),
				'pdfib_available_orientations' => array( 'portrait', 'landscape' ),
				'pdfib_available_dpi'          => array( 72, 96, 150, 300, 600 ),
			);
			pdfib_update_option( 'pdfib_settings', $settings );
		}
		$available_formats      = $settings['pdfib_available_formats'] ?? array( 'A4' );
		$available_orientations = $settings['pdfib_available_orientations'] ?? array( 'portrait' );
		$available_dpi          = $this->resolve_available_dpi_from_settings( $settings );
		return array(
			'id'              => $template['id'],
			'name'            => $template['name'],
			'description'     => $meta['description'],
			'category'        => $meta['category'],
			'is_default'      => $template['is_default'],
			'created_at'      => $template['created_at'],
			'updated_at'      => $template['updated_at'],
			'template_data'   => $template_data,
			'canvas_settings' => array(
				'default_canvas_format'      => $canvas_settings['default_canvas_format'] ?? 'A4',
				'default_canvas_orientation' => $canvas_settings['default_canvas_orientation'] ?? 'portrait',
				'default_canvas_dpi'         => $canvas_settings['default_canvas_dpi'] ?? 96,
				'available_formats'          => $available_formats,
				'available_orientations'     => $available_orientations,
				'available_dpi'              => $available_dpi,
			),
		);
	}

	/**
	 * Retourne la liste des DPI disponibles d'après les paramètres.
	 *
	 * @param array $settings Paramètres du plugin.
	 * @return int[]
	 */
	private function resolve_available_dpi_from_settings( array $settings ): array {
		$raw           = $settings['pdfib_canvas_dpi'] ?? null;
		$available_dpi = $settings['pdfib_available_dpi'] ?? array( 72, 96, 150 );

		if ( is_string( $raw ) && strpos( $raw, ',' ) !== false ) {
			$available_dpi = array_map( 'intval', explode( ',', $raw ) );
		} elseif ( is_array( $raw ) ) {
			$available_dpi = array_map( 'intval', $raw );
		} elseif ( null !== $raw && '' !== $raw && '0' !== $raw ) {
			$available_dpi = array( intval( $raw ) );
		}

		return $available_dpi;
	}

	/**
	 * Définit un template comme template par défaut.
	 */
	public function set_default_template() {
		try {
			// Vérification des permissions.
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Vérification du nonce.
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$template_id = intval( wp_unslash( $GLOBALS['_POST']['template_id'] ?? 0 ) );
			$is_default  = intval( wp_unslash( $GLOBALS['_POST']['is_default'] ?? 0 ) );
			if ( empty( $template_id ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'ID du template manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';
			// Vérifier que le template existe.
			$existing = pdfib_db()->get_var( pdfib_db()->prepare( 'SELECT id FROM %i WHERE id = %d', $table_templates, $template_id ) );
			if ( ! $existing ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Template non trouvé', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Si on définit comme défaut, retirer le statut par défaut des autres templates.
			if ( $is_default ) {
				pdfib_db()->update( $table_templates, array( 'is_default' => 0 ), array( 'is_default' => 1 ), array( '%d' ), array( '%d' ) );
			}

			// Mettre à jour le statut du template.
			$result = pdfib_db()->update( $table_templates, array( 'is_default' => $is_default ), array( 'id' => $template_id ), array( '%d' ), array( '%d' ) );
			if ( false === $result ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de la mise à jour du statut par défaut', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$message = $is_default ? 'Template défini comme par défaut' : 'Statut par défaut retiré';
			\wp_send_json_success(
				array(
					'message' => $message,
				)
			);
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors de la modification du statut: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Bascule le statut par défaut d'un template.
	 */
	public function toggle_default_template() {
		try {
			// Vérification des permissions.
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Vérification du nonce.
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$template_id = intval( wp_unslash( $GLOBALS['_POST']['template_id'] ?? 0 ) );
			if ( empty( $template_id ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'ID du template manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';

			// Récupérer le statut actuel du template.
			$current_status = pdfib_db()->get_var( pdfib_db()->prepare( 'SELECT is_default FROM %i WHERE id = %d', $table_templates, $template_id ) );
			if ( null === $current_status ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Template non trouvé', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$new_status = $current_status ? 0 : 1;

			// Si on définit comme défaut, retirer le statut par défaut des autres templates.
			if ( $new_status ) {
				pdfib_db()->update( $table_templates, array( 'is_default' => 0 ), array( 'is_default' => 1 ), array( '%d' ), array( '%d' ) );
			}

			// Mettre à jour le statut du template.
			$result = pdfib_db()->update( $table_templates, array( 'is_default' => $new_status ), array( 'id' => $template_id ), array( '%d' ), array( '%d' ) );
			if ( false === $result ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de la mise à jour du statut par défaut', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$message = $new_status ? 'Template défini comme par défaut' : 'Statut par défaut retiré';
			\wp_send_json_success(
				array(
					'message'    => $message,
					'is_default' => $new_status,
				)
			);
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors de la modification du statut: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Sauvegarde les paramètres d'un template.
	 *
	 * @return void
	 */
	public function save_template_settings() {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$post_data = $this->extract_save_template_post_data();
			if ( empty( $post_data['template_id'] ) || empty( $post_data['template_name'] ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'ID du template ou nom manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';
			$existing        = pdfib_db()->get_row( pdfib_db()->prepare( self::SQL_SELECT_TEMPLATE_BY_ID, $table_templates, $post_data['template_id'] ) );
			if ( ! $existing ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Template non trouvé', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_json = $this->build_updated_template_json( $existing, $post_data['template_description'], $post_data['template_category'], $post_data['canvas_format'], $post_data['canvas_orientation'], $post_data['canvas_dpi'] );
			if ( $post_data['is_default'] ) {
				pdfib_db()->update( $table_templates, array( 'is_default' => 0 ), array( 'is_default' => 1 ), array( '%d' ), array( '%d' ) );
			}
			$result = pdfib_db()->update(
				$table_templates,
				array(
					'name'          => $post_data['template_name'],
					'template_data' => $template_json,
					'is_default'    => $post_data['is_default'],
					'updated_at'    => current_time( 'mysql' ),
				),
				array( 'id' => $post_data['template_id'] ),
				array( '%s', '%s', '%d', '%s' ),
				array( '%d' )
			);
			if ( false === $result ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de la sauvegarde des paramètres du template', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			\wp_send_json_success(
				array(
					'message'     => 'Paramètres du template sauvegardés avec succès',
					'template_id' => $post_data['template_id'],
				)
			);
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors de la sauvegarde: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Retourne les données POST sanitisées pour save_template_settings.
	 *
	 * @return array<string,mixed>
	 */
	private function extract_save_template_post_data(): array {
		return array(
			'template_id'          => intval( wp_unslash( $GLOBALS['_POST']['template_id'] ?? 0 ) ),
			'template_name'        => \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_name'] ?? '' ) ),
			'template_description' => \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_description'] ?? '' ) ),
			'template_category'    => \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_category'] ?? 'autre' ) ),
			'is_default'           => intval( wp_unslash( $GLOBALS['_POST']['is_default'] ?? 0 ) ),
			'canvas_format'        => \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['canvas_format'] ?? 'A4' ) ),
			'canvas_orientation'   => \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['canvas_orientation'] ?? 'portrait' ) ),
			'canvas_dpi'           => intval( wp_unslash( $GLOBALS['_POST']['canvas_dpi'] ?? 96 ) ),
		);
	}

	/**
	 * Construit le JSON mis à jour d'un template.
	 *
	 * @param object $existing           Template existant.
	 * @param string $description        Description du template.
	 * @param string $category           Catégorie du template.
	 * @param string $canvas_format      Format du canvas.
	 * @param string $canvas_orientation Orientation du canvas.
	 * @param int    $canvas_dpi         Résolution du canvas.
	 * @return string
	 */
	private function build_updated_template_json( object $existing, string $description, string $category, string $canvas_format, string $canvas_orientation, int $canvas_dpi ): string {
		$template_data = json_decode( $existing->template_data ?? '{}', true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$template_data = array();
		}
		$template_data['description']        = $description;
		$template_data['category']           = $category;
		$template_data['canvas_format']      = $canvas_format;
		$template_data['canvas_orientation'] = $canvas_orientation;
		$template_data['canvas_dpi']         = $canvas_dpi;
		return wp_json_encode( $template_data );
	}

	/**
	 * Supprime un template.
	 */
	public function delete_template() {
		try {
			// Vérification des permissions - permettre aux utilisateurs connectés de supprimer leurs templates.
			if ( ! is_user_logged_in() ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Utilisateur non connecté', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Vérification du nonce.
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$template_id = intval( wp_unslash( $GLOBALS['_POST']['template_id'] ?? 0 ) );
			if ( empty( $template_id ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'ID du template manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';

			// Vérifier que le template existe et appartient à l'utilisateur actuel.
			$template = pdfib_db()->get_row( pdfib_db()->prepare( 'SELECT id, name, user_id FROM %i WHERE id = %d', $table_templates, $template_id ), ARRAY_A );
			if ( ! $template ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Template non trouvé', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Vérifier que l'utilisateur est propriétaire du template ou admin.
			$current_user_id  = get_current_user_id();
			$template_user_id = isset( $template['user_id'] ) ? absint( $template['user_id'] ) : 0;
			if ( $template_user_id !== $current_user_id && ! current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			$template_name = $template['name'];

			// Supprimer le template.
			$result = pdfib_db()->delete( $table_templates, array( 'id' => $template_id ), array( '%d' ) );
			if ( false === $result ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de la suppression du template', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Déclencher le hook de suppression de template.
			\do_action( 'pdfib_template_deleted', $template_id, ! empty( $template_name ) ? $template_name : 'Template #' . $template_id );
			\wp_send_json_success(
				array(
					'message' => 'Template supprimé avec succès',
				)
			);
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors de la suppression: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Duplique un template existant.
	 *
	 * @return void
	 */
	public function duplicate_template() {
		try {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$template_id   = intval( wp_unslash( $GLOBALS['_POST']['template_id'] ?? 0 ) );
			$template_name = \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_name'] ?? '' ) );
			if ( empty( $template_id ) || empty( $template_name ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'ID du template ou nom manquant', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! $this->can_create_custom_template() ) {
				$this->deny_custom_template_creation();
				return;
			}
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';
			$existing        = pdfib_db()->get_row( pdfib_db()->prepare( self::SQL_SELECT_TEMPLATE_BY_ID, $table_templates, $template_id ) );
			if ( ! $existing ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Template non trouvé', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$new_template_id = $this->insert_duplicated_template( $template_name, $existing );
			if ( $new_template_id <= 0 ) {
				return;
			}
			\wp_send_json_success(
				array(
					'message'     => 'Template dupliqué avec succès',
					'template_id' => $new_template_id,
				)
			);
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors de la duplication: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Insère une copie d'un template et retourne son nouvel ID.
	 *
	 * @param string $name     Nom du nouveau template.
	 * @param object $existing Template existant.
	 * @return int
	 */
	private function insert_duplicated_template( string $name, object $existing ): int {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		$result          = pdfib_db()->insert(
			$table_templates,
			array(
				'name'          => $name,
				'template_data' => $existing->template_data,
				'user_id'       => get_current_user_id(),
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
				'is_default'    => 0,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%d' )
		);
		if ( false === $result ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors de la duplication du template', 'advanced-pdf-invoice-builder' ) ) );
			return 0;
		}
		return pdfib_db()->insert_id;
	}

	/**
	 * Sauvegarde les mappings des templates par statut de commande.
	 */
	public function save_order_status_templates() {
		try {
			// Vérification des permissions.
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Vérification du nonce.
			if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Récupérer les données JSON.
			$templates_data_json = sanitize_textarea_field( wp_unslash( $GLOBALS['_POST']['templates_data'] ?? '' ) );
			if ( empty( $templates_data_json ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Données des templates manquantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Décoder les données JSON.
			$templates_data = json_decode( $templates_data_json, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors du décodage des données JSON', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! is_array( $templates_data ) ) {
				\wp_send_json_error( array( 'message' => esc_html__( 'Le format des données des templates est invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Valider et nettoyer les données.
			$clean_data = array();
			foreach ( $templates_data as $status => $template_id ) {
				if ( ! empty( $template_id ) && is_numeric( $template_id ) ) {
					$clean_data[ $status ] = intval( $template_id );
				}
			}

			// Sauvegarder dans les options WordPress.
			pdfib_update_option( 'pdfib_order_status_templates', $clean_data );

			\wp_send_json_success(
				array(
					'message'    => 'Mappings des templates sauvegardés avec succès',
					'saved_data' => $clean_data,
				)
			);
		} catch ( Exception $e ) {
			\wp_send_json_error( array( 'message' => 'Erreur lors de la sauvegarde: ' . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Handler AJAX: génère le rendu HTML d'un template à partir des données JSON envoyées par le React editor.
	 */
	public function render_template_html(): void {
		if ( ! \check_ajax_referer( 'pdfib_ajax', 'nonce', false ) ) {
			\wp_send_json_error( array( 'message' => pdfib_err_nonce() ), 403 );
			return;
		}
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( array( 'message' => pdfib_err_perms() ), 403 );
			return;
		}

		$raw_template = sanitize_textarea_field( wp_unslash( $GLOBALS['_POST']['template_data'] ?? '' ) );
		$raw_order    = sanitize_textarea_field( wp_unslash( $GLOBALS['_POST']['order_data'] ?? '{}' ) );

		$template_data = json_decode( $raw_template, true );
		$order_data    = json_decode( $raw_order, true );

		if ( ! is_array( $template_data ) ) {
			\wp_send_json_error( array( 'message' => 'Données template invalides' ), 400 );
			return;
		}

		$company_data = pdfib_get_option( 'pdfib_company_data', array() );
		$generator    = new DocumentHTMLGenerator( $template_data, is_array( $order_data ) ? $order_data : array(), is_array( $company_data ) ? $company_data : array() );
		$html         = $generator->generate_content();

		\wp_send_json_success( array( 'html' => $html ) );
	}
}

// Initialiser le handler AJAX.
PdfBuilderTemplatesAjax::register();
