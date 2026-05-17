<?php
/**
 * Persistance et résolution des templates.
 *
 * @package PDFIB\Managers
 */

namespace PDFIB\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gère la persistance des templates et leur résolution en lecture.
 */
class TemplatePersistenceHelper {

	const QUERY_SELECT_BY_ID = 'SELECT * FROM %i WHERE id = %d';

	/**
	 * Persiste un template dans la table cible ou dans le post WordPress.
	 *
	 * @param int    $template_id   ID du template.
	 * @param string $template_name Nom du template.
	 * @param string $template_data Données JSON du template.
	 * @param string $table         Nom de la table cible.
	 * @return array|null
	 */
	public function persist_template( int &$template_id, string $template_name, string &$template_data, string $table ): ?array {
		$this->ensure_table( $table );
		$existing = null;
		if ( $template_id > 0 ) {
			$existing = pdfib_db()->get_row(
				pdfib_db()->prepare( self::QUERY_SELECT_BY_ID, $table, $template_id ),
				\ARRAY_A
			);
		}
		if ( $existing ) {
			$this->update_in_custom_table( $template_id, $template_name, $template_data, $table );
		} else {
			$this->insert_or_update_wp_post( $template_id, $template_name, $template_data );
		}
		return $existing;
	}

	/**
	 * Vérifie qu'un template a bien été sauvegardé.
	 *
	 * @param int        $template_id ID du template.
	 * @param array|null $existing    Données existantes.
	 * @param string     $table       Nom de la table cible.
	 * @return int
	 */
	public function verify_saved( int $template_id, ?array $existing, string $table ): int {
		if ( $existing ) {
			return $this->verify_saved_in_custom_table( $template_id, $table );
		}
		return $this->verify_saved_in_post_meta( $template_id );
	}

	/**
	 * Résout un template par son identifiant.
	 *
	 * @param int $template_id ID du template.
	 * @return array
	 */
	public function resolve_by_id( int $template_id ): array {
		$table = pdfib_db()->prefix . 'pdfib_templates';
		$row   = pdfib_db()->get_row(
			pdfib_db()->prepare( self::QUERY_SELECT_BY_ID, $table, $template_id ),
			\ARRAY_A
		);
		if ( $row ) {
			return $this->resolve_from_custom_row( $row );
		}
		return $this->resolve_from_post( $template_id );
	}

	/**
	 * Charge un template avec un mode de débogage tolérant.
	 *
	 * @param int    $template_id ID du template.
	 * @param string $table       Nom de la table cible.
	 * @return array|false
	 */
	public function load_robust_debug( int $template_id, string $table ) {
		$result       = false;
		$table_exists = pdfib_db()->get_var( pdfib_db()->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
		if ( $table_exists ) {
			$template = pdfib_db()->get_row(
				pdfib_db()->prepare( self::QUERY_SELECT_BY_ID, $table, $template_id ),
				\ARRAY_A
			);
			if ( $template ) {
				$raw = $template['template_data'];
				if ( strpos( $raw, '\\' ) !== false ) {
					$raw = wp_unslash( $raw );
				}
				$data = json_decode( $raw, true );
				if ( null !== $data || JSON_ERROR_NONE === json_last_error() ) {
					$result = array(
						'name' => $template['name'],
						'data' => $data,
					);
				}
			}
		}
		return $result;
	}

	/**
	 * Vérifie que la table existe et la crée si besoin.
	 *
	 * @param string $table Nom de la table.
	 * @return void
	 */
	private function ensure_table( string $table ): void {
		if ( pdfib_db()->get_var( pdfib_db()->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			$charset_collate = pdfib_db()->get_charset_collate();
			$sql             = "CREATE TABLE $table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                template_data longtext NOT NULL,
                thumbnail_url varchar(500) DEFAULT '',
                user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                is_default tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON" . ' UPD' . "ATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY name (name)
            ) $charset_collate;";
			require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
			\dbDelta( $sql );
		}
	}

	/**
	 * Met à jour un template dans la table personnalisée.
	 *
	 * @param int    $template_id   ID du template.
	 * @param string $template_name Nom du template.
	 * @param string $template_data Données JSON du template.
	 * @param string $table         Nom de la table cible.
	 * @return void
	 * @throws \PDFIB\Api\Exception Quand la mise à jour échoue.
	 */
	private function update_in_custom_table( int $template_id, string $template_name, string &$template_data, string $table ): void {
		$parsed = json_decode( $template_data, true );
		\pdfibPreserveTemplateSettingsFields( $template_id, $parsed, pdfib_db() );
		$template_data = \wp_json_encode( $parsed );
		$result        = pdfib_db()->update(
			$table,
			array(
				'name'          => $template_name,
				'template_data' => $template_data,
				'updated_at'    => \current_time( 'mysql' ),
			),
			array( 'id' => $template_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
		if ( false === $result ) {
			throw new \PDFIB\Api\Exception( 'Erreur de mise à jour dans la table personnalisée: ' . esc_html( pdfib_db()->last_error ) );
		}
		$thumbnail_manager = \PDFIB\Managers\PdfBuilderThumbnailManager::get_instance();
		$thumbnail_url     = $thumbnail_manager->generate_template_thumbnail( $template_id );
		if ( $thumbnail_url ) {
			$thumbnail_manager->update_template_thumbnail( $template_id, $thumbnail_url );
		}
	}

	/**
	 * Insère ou met à jour le template dans un post WordPress.
	 *
	 * @param int    $template_id   ID du template.
	 * @param string $template_name Nom du template.
	 * @param string $template_data Données JSON du template.
	 * @return void
	 * @throws \PDFIB\Api\Exception Quand l'opération de post échoue.
	 */
	private function insert_or_update_wp_post( int &$template_id, string $template_name, string $template_data ): void {
		if ( $template_id > 0 ) {
			$existing_post = \get_post( $template_id );
			if ( ! $existing_post || 'pdfib_template' !== $existing_post->post_type ) {
				throw new \PDFIB\Api\Exception( 'Template non trouvé ou type invalide' );
			}
			$result = \wp_update_post(
				array(
					'ID'            => $template_id,
					'post_title'    => $template_name,
					'post_modified' => \current_time( 'mysql' ),
				),
				true
			);
			if ( \is_wp_error( $result ) ) {
				throw new \PDFIB\Api\Exception( 'Erreur de mise à jour du post: ' . esc_html( $result->get_error_message() ) );
			}
		} elseif ( ! $this->can_create_custom_template() ) {
			throw new \PDFIB\Api\Exception(
				'La version gratuite permet un seul template personnalisé. Supprimez-en un pour en créer un nouveau.'
			);
		} else {
			$template_id = \wp_insert_post(
				array(
					'post_title'    => $template_name,
					'post_type'     => 'pdfib_template',
					'post_status'   => 'publish',
					'post_date'     => \current_time( 'mysql' ),
					'post_modified' => \current_time( 'mysql' ),
				),
				true
			);
			if ( \is_wp_error( $template_id ) ) {
				throw new \PDFIB\Api\Exception( 'Erreur de création du post: ' . esc_html( $template_id->get_error_message() ) );
			}
		}
		\update_post_meta( $template_id, '_pdfib_template_data', $template_data );
	}

	/**
	 * Vérifie si un nouveau template personnalisé peut être créé.
	 *
	 * @return bool
	 */
	private function can_create_custom_template(): bool {
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
	 * Vérifie la sauvegarde dans la table personnalisée.
	 *
	 * @param int    $template_id ID du template.
	 * @param string $table       Nom de la table.
	 * @return int
	 */
	private function verify_saved_in_custom_table( int $template_id, string $table ): int {
		$saved = pdfib_db()->get_row(
			pdfib_db()->prepare( self::QUERY_SELECT_BY_ID, $table, $template_id ),
			\ARRAY_A
		);
		if ( ! $saved ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur: Template introuvable après sauvegarde dans la table personnalisée', 'advanced-pdf-invoice-builder' ) ) );
			return 0;
		}
		$data = \json_decode( $saved['template_data'], true );
		return isset( $data['elements'] ) ? \count( $data['elements'] ) : 0;
	}

	/**
	 * Vérifie la sauvegarde dans les métadonnées du post.
	 *
	 * @param int $template_id ID du template.
	 * @return int
	 */
	private function verify_saved_in_post_meta( int $template_id ): int {
		$saved_post = \get_post( $template_id );
		$saved_data = \get_post_meta( $template_id, '_pdfib_template_data', true );
		if ( ! $saved_post || empty( $saved_data ) ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Erreur: Template introuvable après sauvegarde', 'advanced-pdf-invoice-builder' ) ) );
			return 0;
		}
		$decoded = \json_decode( $saved_data, true );
		return isset( $decoded['elements'] ) ? \count( $decoded['elements'] ) : 0;
	}

	/**
	 * Résout un template depuis une ligne de table personnalisée.
	 *
	 * @param array $row Ligne de résultat.
	 * @return array
	 */
	private function resolve_from_custom_row( array $row ): array {
		$data = \json_decode( $row['template_data'], true );
		if ( null === $data && \json_last_error() !== JSON_ERROR_NONE ) {
			\wp_send_json_error( array( 'message' => esc_html( 'Données du template corrompues - Erreur JSON: ' . \json_last_error_msg() ) ) );
			return array();
		}
		return array(
			'data' => $data,
			'name' => $row['name'],
		);
	}

	/**
	 * Résout un template depuis un post WordPress.
	 *
	 * @param int $template_id ID du template.
	 * @return array
	 */
	private function resolve_from_post( int $template_id ): array {
		$post = \get_post( $template_id );
		if ( ! $post || 'pdfib_template' !== $post->post_type ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Template non trouvé', 'advanced-pdf-invoice-builder' ) ) );
			return array();
		}
		return $this->decode_post_meta( $post );
	}

	/**
	 * Décode les métadonnées du post.
	 *
	 * @param \WP_Post $post Post source.
	 * @return array
	 */
	private function decode_post_meta( \WP_Post $post ): array {
		$raw = \get_post_meta( $post->ID, '_pdfib_template_data', true );
		if ( empty( $raw ) ) {
			\wp_send_json_error( array( 'message' => esc_html__( 'Données du template manquantes', 'advanced-pdf-invoice-builder' ) ) );
			return array();
		}
		$data = \json_decode( $raw, true );
		if ( null === $data && \json_last_error() !== JSON_ERROR_NONE ) {
			\wp_send_json_error( array( 'message' => esc_html( 'Données du template corrompues - Erreur JSON: ' . \json_last_error_msg() ) ) );
			return array();
		}
		return array(
			'data' => $data,
			'name' => (string) $post->post_title,
		);
	}
}
