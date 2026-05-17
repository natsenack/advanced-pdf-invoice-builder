<?php
/**
 * Gestion centralisée des thumbnails de templates.
 *
 * @package PDFIB\Managers
 */

namespace PDFIB\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gère les thumbnails des templates.
 */
class PdfBuilderThumbnailManager {

	/**
	 * Largeur du thumbnail.
	 *
	 * @var int
	 */
	const THUMBNAIL_WIDTH = 300;

	/**
	 * Hauteur du thumbnail.
	 *
	 * @var int
	 */
	const THUMBNAIL_HEIGHT = 200;

	/**
	 * Position de départ en Y.
	 *
	 * @var int
	 */
	const THUMBNAIL_Y_START = 20;

	/**
	 * Incrément vertical.
	 *
	 * @var int
	 */
	const THUMBNAIL_Y_INCREMENT = 25;

	/**
	 * Instance unique du singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Retourne l'instance unique.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructeur privé du singleton.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialisation des hooks.
	 */
	private function init_hooks() {
		\add_action( 'admin_init', array( $this, 'run_database_migrations' ) );
	}

	/**
	 * Met à jour l'URL du thumbnail dans la base de données.
	 *
	 * @param int    $template_id   ID du template.
	 * @param string $thumbnail_url URL du thumbnail.
	 * @return bool
	 */
	public function update_template_thumbnail( int $template_id, string $thumbnail_url ): bool {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';

		$result = pdfib_db()->update(
			$table_templates,
			array( 'thumbnail_url' => $thumbnail_url ),
			array( 'id' => $template_id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Récupère l'URL du thumbnail d'un template.
	 *
	 * @param int $template_id ID du template.
	 * @return string
	 */
	public function get_template_thumbnail( int $template_id ): string {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';

		$template = pdfib_db()->get_row(
			pdfib_db()->prepare( 'SELECT thumbnail_url FROM %i WHERE id = %d', $table_templates, $template_id ),
			ARRAY_A
		);

		return $template ? $template['thumbnail_url'] : '';
	}

	/**
	 * Supprime le thumbnail d'un template.
	 *
	 * @param int $template_id ID du template.
	 * @return void
	 */
	public function delete_template_thumbnail( int $template_id ): void {
		$thumbnail_url = $this->get_template_thumbnail( $template_id );
		if ( ! empty( $thumbnail_url ) ) {
			// Supprimer le fichier physique.
			$upload_dir    = \wp_upload_dir();
			$relative_path = str_replace( $upload_dir['baseurl'], '', $thumbnail_url );
			$file_path     = $upload_dir['basedir'] . $relative_path;

			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}

			// Supprimer l'URL de la DB.
			$this->update_template_thumbnail( $template_id, '' );
		}
	}

	/**
	 * Applique les migrations de base de données.
	 *
	 * @return void
	 */
	public function run_database_migrations() {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';

		// Vérifier et ajouter la colonne thumbnail_url.
		$columns          = pdfib_db()->get_results( pdfib_db()->prepare( 'DESCRIBE %i', $table_templates ), ARRAY_A );
		$thumbnail_exists = false;
		if ( $columns ) {
			foreach ( $columns as $column ) {
				if ( isset( $column['Field'] ) && 'thumbnail_url' === $column['Field'] ) {
					$thumbnail_exists = true;
					break;
				}
			}
		}

		if ( ! $thumbnail_exists ) {
			$sql    = "ALTER TABLE $table_templates ADD COLUMN thumbnail_url VARCHAR(500) DEFAULT '' AFTER template_data";
			$result = pdfib_db()->query( $sql );
			if ( false !== $result ) {
				$this->log_info( 'Colonne thumbnail_url ajoutée avec succès' );
			} else {
				$this->log_error( 'Erreur lors de l\'ajout de la colonne thumbnail_url: ' . pdfib_db()->last_error );
			}
		}
	}



	/**
	 * Journalise une erreur.
	 *
	 * @param string $message Message à journaliser.
	 * @return void
	 */
	private function log_error( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				if ( $logger ) {
					$logger->error( 'PDFIB Thumbnail: ' . $message, array( 'source' => 'pdfib-thumbnail' ) );
				}
			}
		}
	}

	/**
	 * Journalise une information.
	 *
	 * @param string $message Message à journaliser.
	 * @return void
	 */
	private function log_info( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				if ( $logger ) {
					$logger->info( 'PDFIB Thumbnail: ' . $message, array( 'source' => 'pdfib-thumbnail' ) );
				}
			}
		}
	}
















	/**
	 * Génère un thumbnail pour un template.
	 *
	 * @param int $template_id ID du template.
	 * @return bool
	 */
	public function generate_template_thumbnail( int $template_id ) {
		try {
			// Thumbnail generation requires the PdfHtmlGenerator.
			// which would need extensive setup. For now, return false.
			// to use default placeholder.
			return false;
		} catch ( \Exception $e ) {
			// Log l'erreur mais ne pas échouer la sauvegarde.
			$this->log_error( 'Erreur génération thumbnail template ' . $template_id . ': ' . $e->getMessage() );
		}

		return false;
	}

	/**
	 * Supprime les thumbnails devenus orphelins.
	 *
	 * @return void
	 */
	public function cleanup_orphaned_thumbnails() {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';

		$upload_dir    = \wp_upload_dir();
		$thumbnail_dir = $upload_dir['basedir'] . '/pdf-builder-thumbnails/';

		if ( ! file_exists( $thumbnail_dir ) ) {
			return;
		}

		// Récupérer tous les templates avec thumbnails.
		$templates = pdfib_db()->get_results( pdfib_db()->prepare( "SELECT id, thumbnail_url FROM %i WHERE thumbnail_url != ''", $table_templates ), ARRAY_A );

		$existing_template_ids = array_column( $templates, 'id' );

		// Scanner le répertoire des thumbnails.
		$thumbnail_files = glob( $thumbnail_dir . 'template-*-thumb.png' );

		foreach ( $thumbnail_files as $file_path ) {
			$filename = basename( $file_path );
			// Extraire l'ID du template du nom de fichier.
			if ( preg_match( '/template-(\d+)-thumb\.png/', $filename, $matches ) ) {
				$template_id = (int) $matches[1];

				// Si le template n'existe plus ou n'a plus de thumbnail, supprimer le fichier.
				if ( ! in_array( $template_id, $existing_template_ids, true ) ) {
					wp_delete_file( $file_path );
					$this->log_info( "Thumbnail orphelin supprimé: $filename" );
				}
			}
		}
	}
}
