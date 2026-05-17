<?php
/**
 * Advanced PDF Invoice Builder - GDPR User Data Helper
 * Gère l'export, la portabilité et la suppression des données utilisateur.
 *
 * @package PDF_Builder_Pro
 * @since   1.6.12
 */

namespace PDFIB\Utilities;

use WP_Error;
use SimpleXMLElement;

defined( 'ABSPATH' ) || exit;

/**
 * Gestion des données utilisateur pour la conformité RGPD.
 */
class GdprUserDataHelper {

	/**
	 * HTML renderer instance.
	 *
	 * @var GdprHtmlRenderer
	 */
	private GdprHtmlRenderer $renderer;

	/**
	 * Initialise le helper avec le renderer HTML.
	 *
	 * @param GdprHtmlRenderer $renderer Renderer HTML RGPD.
	 */
	public function __construct( GdprHtmlRenderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Récupère toutes les données d un utilisateur.
	 *
	 * @param int $user_id ID de l utilisateur.
	 */
	public function get_user_data( int $user_id ): array {
		$user = \get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return array();
		}
		$consent_data = array();

		foreach ( array( 'analytics', 'templates', 'marketing' ) as $type ) {
			$consent_key           = 'pdfib_consent_' . $type;
			$consent_data[ $type ] = get_user_meta( $user_id, $consent_key, true );
		}

		$posts_table = pdfib_db()->posts;
		$templates   = pdfib_db()->get_results(
			pdfib_db()->prepare(
				"SELECT ID, post_title, post_modified, post_content FROM {$posts_table} WHERE post_author = %d AND post_type = 'pdfib_template'",
				$user_id
			),
			ARRAY_A
		);

		$template_meta = array();
		if ( ! empty( $templates ) ) {
			update_meta_cache( 'post', array_column( $templates, 'ID' ) );
		}
		foreach ( $templates as $template ) {
			$meta                             = \get_post_meta( $template['ID'] );
			$template_meta[ $template['ID'] ] = $meta;
		}

		$table_audit = pdfib_db()->prefix . 'pdfib_audit_log';

		$audit_logs = pdfib_db()->get_results(
			pdfib_db()->prepare(
				"
            SELECT action, data_type, created_at
            FROM {$table_audit}
            WHERE user_id = %d
            ORDER BY created_at DESC
        ",
				$user_id
			),
			ARRAY_A
		);

		$user_preferences = get_user_meta( $user_id, 'pdfib_user_preferences', true );
		$last_activity    = get_user_meta( $user_id, 'pdfib_last_activity', true );

		return array(
			'user_info'               => array(
				'id'           => $user->ID,
				'login'        => $user->user_login,
				'email'        => $user->user_email,
				'display_name' => $user->display_name,
				'registered'   => $user->user_registered,
				'roles'        => $user->roles,
			),
			'consents'                => $consent_data,
			'templates'               => $templates,
			'template_metadata'       => $template_meta,
			'audit_logs'              => $audit_logs,
			'user_preferences'        => $user_preferences,
			'last_activity'           => $last_activity,
			'export_date'             => current_time( 'mysql' ),
			'data_portability_notice' => 'Ces données sont fournies au format RGPD pour portabilité.',
		);
	}

	/**
	 * Crée un fichier d export des données utilisateur dans le format spécifié.
	 *
	 * @param array  $user_data Les donnees utilisateur.
	 * @param int    $user_id   ID de l utilisateur.
	 * @param string $format    Format d export (html, json...).
	 * @return array|WP_Error
	 */
	public function create_user_data_export( array $user_data, int $user_id, string $format = 'json' ) {
		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/pdf-builder-exports';

		wp_mkdir_p( $export_dir );

		$timestamp = gmdate( 'Y-m-d-H-i-s' );
		$filename  = "pdf-builder-user-data-{$user_id}-{$timestamp}.{$format}";
		$file_path = $export_dir . '/' . $filename;

		if ( 'html' === $format ) {
			$content   = $this->renderer->render( $user_data );
			$mime_type = 'text/html';
		} else {
			return new WP_Error( 'invalid_format', __( 'Format d\'export non supporté.', 'advanced-pdf-invoice-builder' ) );
		}

		if ( pdfib_filesystem()->put_contents( $file_path, $content, FS_CHMOD_FILE ) === false ) {
			return new WP_Error( 'file_write_error', __( 'Erreur lors de l\'écriture du fichier d\'export.', 'advanced-pdf-invoice-builder' ) );
		}

		return array(
			'filename'     => $filename,
			'file_path'    => $file_path,
			'download_url' => $upload_dir['baseurl'] . '/pdf-builder-exports/' . $filename,
			'mime_type'    => $mime_type,
			'format'       => $format,
		);
	}

	/**
	 * Génère le contenu d'export des données utilisateur sans créer de fichier.
	 * Retourne une chaîne HTML ou le tableau de données brut selon le format.
	 *
	 * @param array  $user_data Données utilisateur.
	 * @param string $format    Format d'export ('html' ou 'json').
	 * @return string|array|WP_Error
	 */
	public function get_export_content( array $user_data, string $format ) {
		if ( 'html' === $format ) {
			return $this->renderer->render( $user_data );
		}
		if ( 'json' === $format ) {
			return $user_data;
		}
		return new WP_Error( 'invalid_format', __( 'Format d\'export non supporté.', 'advanced-pdf-invoice-builder' ) );
	}

	/**
	 * Récupère les données utilisateur au format de portabilité.
	 *
	 * @param int    $user_id ID de l utilisateur.
	 * @param string $format  Format de portabilite.
	 * @return array|string|WP_Error
	 */
	public function get_user_data_portable( int $user_id, string $format = 'json' ) {
		$data = $this->get_user_data( $user_id );

		if ( 'xml' === $format ) {
			return $this->array_to_xml( $data );
		}

		return $data;
	}

	/**
	 * Supprime toutes les données d un utilisateur.
	 *
	 * @param int $user_id ID de l utilisateur.
	 */
	public function delete_user_data( int $user_id ): void {
		foreach ( array( 'analytics', 'templates', 'marketing' ) as $type ) {
			delete_user_meta( $user_id, 'pdfib_consent_' . $type );
		}

		pdfib_db()->delete(
			pdfib_db()->posts,
			array(
				'post_author' => $user_id,
				'post_type'   => 'pdfib_template',
			)
		);

		delete_metadata( 'post', 0, '_pdfib_template_author', $user_id, true );

		$table_audit = pdfib_db()->prefix . 'pdfib_audit_log';
		pdfib_db()->delete( $table_audit, array( 'user_id' => $user_id ) );

		$backup_dir = wp_upload_dir()['basedir'] . '/pdf-builder-pro/backups/' . $user_id;
		if ( is_dir( $backup_dir ) ) {
			$this->delete_directory_recursive( $backup_dir );
		}

		$temp_files = glob( wp_upload_dir()['basedir'] . '/pdf-builder-pro/temp/*' . $user_id . '*' );
		if ( $temp_files ) {
			foreach ( $temp_files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}

		delete_user_meta( $user_id, 'pdfib_user_preferences' );
		delete_user_meta( $user_id, 'pdfib_last_activity' );
		delete_user_meta( $user_id, 'pdfib_session_data' );
	}

	/**
	 * Supprime un repertoire de facon recursive.
	 *
	 * @param string $dir Chemin du repertoire a supprimer.
	 */
	private function delete_directory_recursive( string $dir ): bool {
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			$path = $dir . DIRECTORY_SEPARATOR . $file;
			if ( is_dir( $path ) ) {
				$this->delete_directory_recursive( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		return (bool) pdfib_filesystem()->rmdir( $dir );
	}

	/**
	 * Convertit un tableau en XML.
	 *
	 * @param array  $data        Les donnees a convertir.
	 * @param string $root_element Element racine XML.
	 */
	private function array_to_xml( array $data, string $root_element = 'data' ): string {
		// XXE protection: LIBXML_NONET prevents network-based external entity loading.
		$xml = new SimpleXMLElement( "<?xml version=\"1.0\" encoding=\"UTF-8\"?><$root_element></$root_element>", LIBXML_NONET );
		$this->array_to_xml_recursive( $data, $xml );
		return (string) $xml->asXML();
	}

	/**
	 * Convertit recursivement un tableau en noeuds XML.
	 *
	 * @param array $data Les donnees a convertir.
	 * @param mixed $xml  Noeud XML courant (reference).
	 */
	private function array_to_xml_recursive( array $data, mixed &$xml ): void {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( is_numeric( $key ) ) {
					$key = 'item' . $key;
				}
				$subnode = $xml->addChild( $key );
				$this->array_to_xml_recursive( $value, $subnode );
			} else {
				$xml->addChild( $key, (string) $value );
			}
		}
	}
}
