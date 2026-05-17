<?php
/**
 * Advanced PDF Invoice Builder - Template Processor.
 * Responsable du traitement et de la gestion des templates.
 *
 * @package PDFIB\Admin\Processors
 */

namespace PDFIB\Admin\Processors;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Exception;

/**
 * Classe responsable du traitement des templates PDF.
 */
class TemplateProcessor {


	private const DEFAULT_TEXT_COLOR = '#000000';

	/**
	 * Instance de la classe principale.
	 *
	 * @var mixed
	 */
	private mixed $admin;

	/**
	 * Constructeur.
	 *
	 * @param mixed $admin Instance de la classe principale.
	 */
	public function __construct( mixed $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Transforme les elements pour React.
	 *
	 * @param array $elements Elements a transformer.
	 *
	 * @return array
	 */
	public function transform_elements_for_react( array $elements ): array {
		return $this->admin->transformElementsForReact( $elements );
	}

	/**
	 * Charge un template de maniere robuste.
	 *
	 * @param int $template_id Identifiant template.
	 *
	 * @return array
	 */
	public function load_template_robust( int $template_id ): array {
		$result = $this->get_default_invoice_template();

		try {
			$table_templates = pdfib_db()->prefix . 'pdfib_templates';

			if ( pdfib_db()->get_var( pdfib_db()->prepare( 'SHOW TABLES LIKE %s', $table_templates ) ) === $table_templates ) {
				$template = pdfib_db()->get_row( pdfib_db()->prepare( 'SELECT * FROM %i WHERE id = %d', $table_templates, $template_id ), ARRAY_A );
				if ( is_array( $template ) ) {
					$result = $this->try_decode_template( $template, $template_id );
				}
			}
		} catch ( Exception $e ) {
			unset( $e ); // Keep default result on errors.
		}

		return $result;
	}

	/**
	 * Tente le decodage d'un template SQL.
	 *
	 * @param array $template Donnees SQL du template.
	 * @param int   $template_id Identifiant template.
	 *
	 * @return array
	 */
	private function try_decode_template( array $template, int $template_id ): array {
		$decoded = null;

		foreach ( $this->build_template_json_candidates( $template['template_data'] ) as $json_candidate ) {
			$decoded = $this->try_decode_json( $json_candidate, $template );
			if ( null !== $decoded ) {
				break;
			}
		}

		if ( null === $decoded ) {
			$this->mark_template_corrupted( $template_id );
			$decoded = $this->get_default_invoice_template();
		}

		return $decoded;
	}

	/**
	 * Construit plusieurs candidats JSON a decoder.
	 *
	 * @param string $raw_json JSON brut.
	 *
	 * @return string[]
	 */
	private function build_template_json_candidates( string $raw_json ): array {
		$candidates = array( $raw_json );
		$clean_json = $this->clean_json_data( $raw_json );
		if ( $clean_json !== $raw_json ) {
			$candidates[] = $clean_json;
		}

		$aggressive_clean = $this->aggressive_json_clean( $raw_json );
		if ( $aggressive_clean !== $raw_json && $aggressive_clean !== $clean_json ) {
			$candidates[] = $aggressive_clean;
		}

		return $candidates;
	}

	/**
	 * Nettoie legerement la chaine JSON.
	 *
	 * @param string $raw_json JSON brut.
	 *
	 * @return string
	 */
	private function clean_json_data( string $raw_json ): string {
		$clean = trim( $raw_json );
		$clean = preg_replace( '/^\xEF\xBB\xBF/', '', $clean ) ? preg_replace( '/^\xEF\xBB\xBF/', '', $clean ) : $clean; // BOM UTF-8.
		$clean = html_entity_decode( $clean, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return $clean;
	}

	/**
	 * Nettoie agressivement un JSON potentiellement corrompu.
	 *
	 * @param string $raw_json JSON brut.
	 *
	 * @return string
	 */
	private function aggressive_json_clean( string $raw_json ): string {
		$clean = $this->clean_json_data( $raw_json );
		// Supprimer les caractères de contrôle non imprimables qui cassent json_decode.
		$clean = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean ) ? preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean ) : $clean;
		// Tolérer les virgules terminales fréquentes dans les JSON stockés.
		$clean = preg_replace( '/,\s*([}\]])/', '$1', $clean ) ? preg_replace( '/,\s*([}\]])/', '$1', $clean ) : $clean;
		return $clean;
	}

	/**
	 * Decode une chaine JSON de template.
	 *
	 * @param string $json_data JSON candidate.
	 * @param array  $template Ligne SQL template.
	 *
	 * @return array|null
	 */
	private function try_decode_json( string $json_data, array $template ): ?array {
		$template_data = json_decode( $json_data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}
		if ( ! isset( $template_data['name'] ) || empty( $template_data['name'] ) || preg_match( '/^Template \d+$/', $template_data['name'] ) ) {
			$template_data['name'] = ! empty( $template['name'] ) ? $template['name'] : 'Template ' . $template['id'];
		}
		$template_data['_db_name'] = $template['name'] ?? '';
		$template_data['_db_id']   = $template['id'];
		return $template_data;
	}

	/**
	 * Crée un template par défaut
	 */
	public function get_default_invoice_template(): array {
		return array(
			'elements'     => array_merge(
				$this->get_default_invoice_header_elements(),
				$this->get_default_invoice_info_elements(),
				$this->get_default_invoice_body_elements()
			),
			'canvasWidth'  => 595,
			'canvasHeight' => 842,
			'version'      => '1.0',
		);
	}

	/**
	 * Éléments d'en-tête du template par défaut (nom société, titre facture)
	 */
	private function get_default_invoice_header_elements(): array {
		return array(
			array(
				'id'       => 'company_name',
				'type'     => 'text',
				'x'        => 50,
				'y'        => 50,
				'width'    => 200,
				'height'   => 30,
				'visible'  => true,
				'locked'   => false,
				'rotation' => 0,
				'opacity'  => 1,
				'style'    => array(
					'fontSize'   => 18,
					'fontWeight' => 'bold',
					'color'      => self::DEFAULT_TEXT_COLOR,
				),
				'content'  => 'Ma Société',
			),
			array(
				'id'       => 'invoice_title',
				'type'     => 'text',
				'x'        => 400,
				'y'        => 50,
				'width'    => 150,
				'height'   => 30,
				'visible'  => true,
				'locked'   => false,
				'rotation' => 0,
				'opacity'  => 1,
				'style'    => array(
					'fontSize'   => 20,
					'fontWeight' => 'bold',
					'color'      => self::DEFAULT_TEXT_COLOR,
				),
				'content'  => __( 'FACTURE', 'advanced-pdf-invoice-builder' ),
			),
		);
	}

	/**
	 * Éléments d'informations du template par défaut (numéro, date, client)
	 */
	private function get_default_invoice_info_elements(): array {
		return array(
			array(
				'id'       => 'invoice_number',
				'type'     => 'invoice_number',
				'x'        => 400,
				'y'        => 90,
				'width'    => 150,
				'height'   => 25,
				'visible'  => true,
				'locked'   => false,
				'rotation' => 0,
				'opacity'  => 1,
				'style'    => array(
					'fontSize' => 14,
					'color'    => self::DEFAULT_TEXT_COLOR,
				),
				'content'  => __( 'N° de facture', 'advanced-pdf-invoice-builder' ),
			),
			array(
				'id'       => 'invoice_date',
				'type'     => 'invoice_date',
				'x'        => 400,
				'y'        => 120,
				'width'    => 150,
				'height'   => 25,
				'visible'  => true,
				'locked'   => false,
				'rotation' => 0,
				'opacity'  => 1,
				'style'    => array(
					'fontSize' => 14,
					'color'    => self::DEFAULT_TEXT_COLOR,
				),
				'content'  => 'Date',
			),
			array(
				'id'       => 'customer_info',
				'type'     => 'customer_info',
				'x'        => 50,
				'y'        => 150,
				'width'    => 250,
				'height'   => 80,
				'visible'  => true,
				'locked'   => false,
				'rotation' => 0,
				'opacity'  => 1,
				'style'    => array(
					'fontSize' => 12,
					'color'    => self::DEFAULT_TEXT_COLOR,
				),
				'content'  => 'Informations client',
			),
		);
	}

	/**
	 * Éléments de corps du template par défaut (tableau produits, total)
	 */
	private function get_default_invoice_body_elements(): array {
		return array(
			array(
				'id'       => 'products_table',
				'type'     => 'product_table',
				'x'        => 50,
				'y'        => 250,
				'width'    => 500,
				'height'   => 200,
				'visible'  => true,
				'locked'   => false,
				'rotation' => 0,
				'opacity'  => 1,
				'style'    => array(
					'fontSize' => 12,
					'color'    => self::DEFAULT_TEXT_COLOR,
				),
				'content'  => 'Tableau produits',
			),
			array(
				'id'       => 'total',
				'type'     => 'total',
				'x'        => 400,
				'y'        => 500,
				'width'    => 150,
				'height'   => 30,
				'visible'  => true,
				'locked'   => false,
				'rotation' => 0,
				'opacity'  => 1,
				'style'    => array(
					'fontSize'   => 16,
					'fontWeight' => 'bold',
					'color'      => self::DEFAULT_TEXT_COLOR,
				),
				'content'  => 'Total',
			),
		);
	}

	/**
	 * Marque un template comme corrompu.
	 *
	 * @param int $template_id Identifiant template.
	 *
	 * @return void
	 */
	public function mark_template_corrupted( int $template_id ): void {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		// Ajouter un flag de corruption (on peut utiliser un champ meta ou modifier le nom).
		$current_name = pdfib_db()->get_var( pdfib_db()->prepare( 'SELECT name FROM %i WHERE id = %d', $table_templates, $template_id ) );
		if ( $current_name && strpos( $current_name, '[CORROMPU]' ) !== 0 ) {
			pdfib_db()->update( $table_templates, array( 'name' => '[CORROMPU] ' . $current_name ), array( 'id' => $template_id ) );
		}
	}

	/**
	 * Repare les templates corrompus.
	 *
	 * @return array
	 */
	public function perform_repair_templates(): array {
		$templates      = pdfib_get_option( 'pdfib_templates', array() );
		$repaired_count = 0;
		if ( is_array( $templates ) ) {
			foreach ( $templates as $key => $template ) {
				// Verifier et reparer la structure des templates.
				if ( ! isset( $template['name'] ) || ! isset( $template['data'] ) ) {
					unset( $templates[ $key ] );
					++$repaired_count;
				}
			}
		}

		pdfib_update_option( 'pdfib_templates', $templates );
		// translators: %d: number of corrupted templates deleted.
		$repair_msg_fmt = \__( 'Templates réparés. %d templates corrompus supprimés.', 'advanced-pdf-invoice-builder' );
		return array(
			'success' => true,
			'message' => sprintf( $repair_msg_fmt, $repaired_count ),
		);
	}

	/**
	 * Charge un template par ID.
	 *
	 * @param int $template_id Identifiant template.
	 *
	 * @return array|null
	 */
	public function load_template_by_id( int $template_id ): ?array {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		$template_data   = pdfib_db()->get_var( pdfib_db()->prepare( 'SELECT template_data FROM %i WHERE id = %d', $table_templates, $template_id ) );
		if ( ! $template_data ) {
			return null;
		}

		$template = json_decode( $template_data, true );
		// MIGRATION: Corriger les valeurs par defaut obsoletes dans les templates.
		// Les templates crees avant cette correction peuvent avoir des valeurs par defaut incorrectes.
		if ( is_array( $template ) && isset( $template['elements'] ) && is_array( $template['elements'] ) ) {
			foreach ( $template['elements'] as &$element ) {
				$this->migrate_product_table_element( $element );
			}
		}

		return $template;
	}

	/**
	 * Migre la structure d'un element tableau produits.
	 *
	 * @param array $element Element du template.
	 *
	 * @return void
	 */
	private function migrate_product_table_element( array &$element ): void {
		if ( ( $element['type'] ?? '' ) !== 'product_table' ) {
			return;
		}

		$columns       = $element['columns'] ?? array();
		$headers       = $element['headers'] ?? array();
		$visible_count = count( array_filter( $columns ) );

		if ( is_array( $headers ) && count( $headers ) !== $visible_count ) {
			$element['headers'] = $this->build_product_table_headers( $columns );
		}
	}

	/**
	 * Construit les en-tetes de colonnes selon la configuration.
	 *
	 * @param array $columns Colonnes affichees.
	 *
	 * @return array
	 */
	private function build_product_table_headers( array $columns ): array {
		$default_headers_map = array(
			'image'    => 'Image',
			'name'     => 'Produit',
			'sku'      => 'Produit',
			'quantity' => 'Qté',
			'price'    => 'Prix',
			'total'    => 'Total',
		);

		$headers = array();
		foreach ( $columns as $col_name => $col_visible ) {
			if ( $col_visible ) {
				$headers[] = $default_headers_map[ $col_name ] ?? ucfirst( $col_name );
			}
		}

		return $headers;
	}
}
