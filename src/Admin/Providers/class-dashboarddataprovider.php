<?php
/**
 * DashboardDataProvider - Fournit les donnees du tableau de bord.
 * Responsabilites : statistiques, comptage, informations version.
 *
 * @package PDFIB\Admin\Providers
 */

namespace PDFIB\Admin\Providers;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Fournit les statistiques et metadonnees du dashboard admin.
 */
class DashboardDataProvider {


	/**
	 * Obtient les statistiques du tableau de bord.
	 *
	 * @return array
	 */
	public function get_dashboard_stats(): array {
		// Nombre de templates.
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		$templates_count = pdfib_db()->get_var( pdfib_db()->prepare( 'SELECT COUNT(*) FROM %i', $table_templates ) );

		// Nombre total de documents generes (logs).
		$table_logs = pdfib_db()->prefix . 'pdfib_logs';
		$logs_stats = $this->get_logs_documents_stats( $table_logs );

		return array(
			'templates' => (int) $templates_count,
			'documents' => (int) $logs_stats['documents'],
			'today'     => (int) $logs_stats['today'],
		);
	}

	/**
	 * Agrege les statistiques de logs/documents.
	 *
	 * @param string $table_logs Nom table des logs.
	 *
	 * @return array
	 */
	private function get_logs_documents_stats( string $table_logs ): array {
		$documents_count = 0;
		$today_count     = 0;

		if ( pdfib_db()->get_var( pdfib_db()->prepare( 'SHOW TABLES LIKE %s', $table_logs ) ) === $table_logs ) {
			$columns         = pdfib_db()->get_results( pdfib_db()->prepare( 'DESCRIBE %i', $table_logs ), ARRAY_A );
			$has_log_message = false;
			foreach ( $columns as $column ) {
				if ( isset( $column['Field'] ) && 'log_message' === $column['Field'] ) {
					$has_log_message = true;
					break;
				}
			}

			if ( $has_log_message ) {
				$pdf_like = '%' . pdfib_db()->esc_like( 'PDF généré' ) . '%';
				$doc_like = '%' . pdfib_db()->esc_like( 'Document créé' ) . '%';

				$documents_count = pdfib_db()->get_var(
					pdfib_db()->prepare(
						"SELECT COUNT(*) FROM `{$table_logs}` WHERE log_message LIKE %s OR log_message LIKE %s",
						$pdf_like,
						$doc_like
					)
				);
				$today           = gmdate( 'Y-m-d' );

				$today_count = pdfib_db()->get_var(
					pdfib_db()->prepare(
						"SELECT COUNT(*) FROM `{$table_logs}` WHERE DATE(created_at) = %s AND (log_message LIKE %s OR log_message LIKE %s)",
						$today,
						$pdf_like,
						$doc_like
					)
				);
			} else {
				$documents_count = pdfib_db()->get_var( pdfib_db()->prepare( 'SELECT COUNT(*) FROM %i', $table_logs ) );
				$today           = gmdate( 'Y-m-d' );

				$today_count = pdfib_db()->get_var(
					pdfib_db()->prepare(
						'SELECT COUNT(*) FROM %i WHERE DATE(created_at) = %s',
						$table_logs,
						$today
					)
				);
			}
		}

		return array(
			'documents' => $documents_count,
			'today'     => $today_count,
		);
	}

	/**
	 * Obtient le nombre de templates.
	 *
	 * @return int
	 */
	public function get_template_count(): int {
		$templates = pdfib_get_option( 'pdfib_templates', array() );
		return is_array( $templates ) ? count( $templates ) : 0;
	}

	/**
	 * Recupere la version du plugin.
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		static $version = null;

		if ( null === $version ) {
			if ( defined( 'PDFIB_PLUGIN_FILE' ) && file_exists( PDFIB_PLUGIN_FILE ) ) {
				$plugin_data = get_file_data( PDFIB_PLUGIN_FILE, array( 'Version' => 'Version' ) );
				$version     = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '1.1.0';
			} else {
				$version = '1.1.0';
			}
		}

		return $version;
	}

	/**
	 * Cree un template par defaut.
	 *
	 * @param int $template_id ID template.
	 *
	 * @return array
	 */
	public function create_default_template( int $template_id ): array {
		return array(
			'id'          => $template_id,
			'name'        => 'Template par défaut',
			'description' => 'Template créé automatiquement',
			'elements'    => $this->get_default_template_elements(),
			'pages'       => $this->get_default_template_pages(),
			'created_at'  => \current_time( 'mysql' ),
		);
	}

	/**
	 * Elements par defaut du template.
	 *
	 * @return array
	 */
	private function get_default_template_elements() {
		return array(
			array(
				'id'       => 'title',
				'type'     => 'text',
				'content'  => 'Advanced PDF Invoice Builder',
				'position' => array(
					'x' => 50,
					'y' => 50,
				),
				'size'     => array(
					'width'  => 200,
					'height' => 40,
				),
				'style'    => array(
					'fontSize'   => 24,
					'fontWeight' => 'bold',
					'color'      => '#000000',
					'textAlign'  => 'center',
				),
			),
			array(
				'id'       => 'subtitle',
				'type'     => 'text',
				'content'  => 'Éditeur de PDF professionnel',
				'position' => array(
					'x' => 50,
					'y' => 100,
				),
				'size'     => array(
					'width'  => 200,
					'height' => 30,
				),
				'style'    => array(
					'fontSize'  => 16,
					'color'     => '#666666',
					'textAlign' => 'center',
				),
			),
		);
	}

	/**
	 * Pages par defaut du template.
	 *
	 * @return array
	 */
	private function get_default_template_pages() {
		return array(
			array(
				'id'              => 1,
				'name'            => 'Page 1',
				'width'           => 595, // A4 width in points.
				'height'          => 842, // A4 height in points.
				'orientation'     => 'portrait',
				'margins'         => array(
					'top'    => 28,
					'right'  => 28,
					'bottom' => 28,
					'left'   => 28,
				),
				'backgroundColor' => '#ffffff',
			),
		);
	}
}
