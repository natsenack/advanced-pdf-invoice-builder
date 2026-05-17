<?php
/**
 * Advanced PDF Invoice Builder - Template Defaults.
 * Fournit 3 templates gratuits de base (Modern, Classic, Corporate).
 *
 * @package PDFIB
 */

namespace PDFIB;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Manages default templates for the freemium system.
 */
class TemplateDefaults {

	/**
	 * Retourne la liste des templates gratuits disponibles.
	 *
	 * @return array
	 */
	public static function get_free_templates() {
		return array(
			'modern'    => array(
				'name'        => 'Facture Moderne',
				'description' => 'Template moderne et épuré pour factures professionnelles',
				'category'    => 'facture',
				'is_free'     => true,
				'elements'    => self::get_modern_template_elements(),
			),
			'classic'   => array(
				'name'        => 'Facture Classique',
				'description' => 'Template traditionnel professionnel et intemporel',
				'category'    => 'facture',
				'is_free'     => true,
				'elements'    => self::get_classic_template_elements(),
			),
			'corporate' => array(
				'name'        => 'Facture Corporate',
				'description' => 'Template entreprise avec branding professionnel',
				'category'    => 'facture',
				'is_free'     => true,
				'elements'    => self::get_corporate_template_elements(),
			),
		);
	}

	/**
	 * Crée les templates par défaut pour un utilisateur.
	 *
	 * @param int $user_id User ID.
	 */
	public static function create_default_templates_for_user( $user_id ) {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';

		$free_templates = self::get_free_templates();

		// Pour les utilisateurs gratuits, créer seulement un template par défaut.
		// Sélectionner le premier template disponible (modern).
		$default_template_key = 'modern';
		$template_data        = $free_templates[ $default_template_key ];

		// Vérifier si un template par défaut existe déjà pour cet utilisateur.

		$existing_default = pdfib_db()->get_var(
			pdfib_db()->prepare(
				'SELECT COUNT(*) FROM %i WHERE user_id = %d AND is_default = 1',
				$table_templates,
				$user_id
			)
		);

		if ( 0 === $existing_default ) {
			// Insérer le template par défaut dans la table personnalisée.
			$result = pdfib_db()->insert(
				$table_templates,
				array(
					'name'          => $template_data['name'],
					'template_data' => wp_json_encode( $template_data['elements'] ),
					'user_id'       => $user_id,
					'is_default'    => 1,
					'created_at'    => current_time( 'mysql' ),
					'updated_at'    => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%d', '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Éléments du template Modern.
	 */
	private static function get_modern_template_elements() {
		return array_merge(
			self::get_modern_header_elements(),
			self::get_modern_invoice_elements(),
			self::get_modern_client_elements(),
			self::get_modern_table_elements()
		);
	}

	/**
	 * Modern template header elements.
	 */
	private static function get_modern_header_elements() {
		return array(
			// En-tête avec logo et informations entreprise.
			array(
				'type'     => 'text',
				'id'       => 'company_name',
				'content'  => '{{company_name}}',
				'position' => array(
					'x' => 50,
					'y' => 50,
				),
				'style'    => array(
					'fontSize'   => 24,
					'fontWeight' => 'bold',
					'color'      => '#2c3e50',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'company_address',
				'content'  => '{{company_address}}',
				'position' => array(
					'x' => 50,
					'y' => 80,
				),
				'style'    => array(
					'fontSize' => 12,
					'color'    => '#7f8c8d',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'invoice_title',
				'content'  => 'FACTURE',
				'position' => array(
					'x' => 400,
					'y' => 50,
				),
				'style'    => array(
					'fontSize'   => 36,
					'fontWeight' => 'bold',
					'color'      => '#3498db',
				),
			),
		);
	}

	/**
	 * Modern template invoice elements.
	 */
	private static function get_modern_invoice_elements() {
		return array(
			// Numéro de facture et date.
			array(
				'type'     => 'text',
				'id'       => 'invoice_number',
				'content'  => 'N° {{invoice_number}}',
				'position' => array(
					'x' => 400,
					'y' => 100,
				),
				'style'    => array(
					'fontSize'   => 14,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'invoice_date',
				'content'  => 'Date: {{invoice_date}}',
				'position' => array(
					'x' => 400,
					'y' => 120,
				),
				'style'    => array(
					'fontSize' => 12,
				),
			),
		);
	}

	/**
	 * Modern template client elements.
	 */
	private static function get_modern_client_elements() {
		return array(
			// Informations client.
			array(
				'type'     => 'text',
				'id'       => 'client_info_title',
				'content'  => 'FACTURER À:',
				'position' => array(
					'x' => 50,
					'y' => 150,
				),
				'style'    => array(
					'fontSize'   => 12,
					'fontWeight' => 'bold',
					'color'      => '#3498db',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'client_name',
				'content'  => '{{client_name}}',
				'position' => array(
					'x' => 50,
					'y' => 170,
				),
				'style'    => array(
					'fontSize'   => 14,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'client_address',
				'content'  => '{{client_address}}',
				'position' => array(
					'x' => 50,
					'y' => 190,
				),
				'style'    => array(
					'fontSize' => 12,
				),
			),
		);
	}

	/**
	 * Modern template table elements.
	 */
	private static function get_modern_table_elements() {
		return array(
			// Tableau des articles.
			array(
				'type'     => 'table',
				'id'       => 'items_table',
				'position' => array(
					'x' => 50,
					'y' => 250,
				),
				'style'    => array(
					'width'  => 500,
					'border' => '1px solid #bdc3c7',
				),
				'headers'  => array( 'Description', 'Qté', 'Prix', 'Total' ),
				'data'     => '{{items}}',
			),
			// Totaux.
			array(
				'type'     => 'text',
				'id'       => 'total_label',
				'content'  => 'TOTAL:',
				'position' => array(
					'x' => 400,
					'y' => 400,
				),
				'style'    => array(
					'fontSize'   => 16,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'total_amount',
				'content'  => '{{total}} €',
				'position' => array(
					'x' => 480,
					'y' => 400,
				),
				'style'    => array(
					'fontSize'   => 16,
					'fontWeight' => 'bold',
					'color'      => '#27ae60',
				),
			),
		);
	}

	/**
	 * Éléments du template Classic.
	 */
	private static function get_classic_template_elements() {
		return array_merge(
			self::get_classic_header_elements(),
			self::get_classic_body_elements()
		);
	}

	/**
	 * Classic template header elements.
	 */
	private static function get_classic_header_elements() {
		return array_merge(
			self::get_classic_header_company_elements(),
			self::get_classic_header_date_elements()
		);
	}

	/**
	 * Classic template header company elements.
	 */
	private static function get_classic_header_company_elements(): array {
		return array(
			// En-tête traditionnel.
			array(
				'type'     => 'text',
				'id'       => 'company_name',
				'content'  => '{{company_name}}',
				'position' => array(
					'x' => 50,
					'y' => 50,
				),
				'style'    => array(
					'fontSize'   => 20,
					'fontWeight' => 'bold',
					'color'      => '#000000',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'company_address',
				'content'  => '{{company_address}}',
				'position' => array(
					'x' => 50,
					'y' => 75,
				),
				'style'    => array(
					'fontSize' => 11,
					'color'    => '#333333',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'invoice_title',
				'content'  => 'FACTURE',
				'position' => array(
					'x' => 400,
					'y' => 50,
				),
				'style'    => array(
					'fontSize'   => 28,
					'fontWeight' => 'bold',
					'color'      => '#000000',
				),
			),
		);
	}

	/**
	 * Classic template header date elements.
	 */
	private static function get_classic_header_date_elements(): array {
		return array(
			// Numéro et date.
			array(
				'type'     => 'text',
				'id'       => 'invoice_number',
				'content'  => 'Facture N° {{invoice_number}}',
				'position' => array(
					'x' => 400,
					'y' => 90,
				),
				'style'    => array(
					'fontSize'   => 12,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'invoice_date',
				'content'  => 'Date: {{invoice_date}}',
				'position' => array(
					'x' => 400,
					'y' => 110,
				),
				'style'    => array(
					'fontSize' => 12,
				),
			),
		);
	}

	/**
	 * Classic template body elements.
	 */
	private static function get_classic_body_elements() {
		return array_merge(
			self::get_classic_body_client_elements(),
			self::get_classic_body_totals_elements()
		);
	}

	/**
	 * Classic template body client elements.
	 */
	private static function get_classic_body_client_elements(): array {
		return array(
			array(
				'type'     => 'text',
				'id'       => 'client_label',
				'content'  => 'Client:',
				'position' => array(
					'x' => 50,
					'y' => 140,
				),
				'style'    => array(
					'fontSize'   => 12,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'client_name',
				'content'  => '{{client_name}}',
				'position' => array(
					'x' => 50,
					'y' => 160,
				),
				'style'    => array(
					'fontSize'   => 14,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'client_address',
				'content'  => '{{client_address}}',
				'position' => array(
					'x' => 50,
					'y' => 180,
				),
				'style'    => array( 'fontSize' => 12 ),
			),
			array(
				'type'     => 'table',
				'id'       => 'items_table',
				'position' => array(
					'x' => 50,
					'y' => 220,
				),
				'style'    => array(
					'width'  => 500,
					'border' => '2px solid #000000',
				),
				'headers'  => array( 'Désignation', 'Quantité', 'Prix unitaire', 'Montant' ),
				'data'     => '{{items}}',
			),
		);
	}

	/**
	 * Classic template body totals elements.
	 */
	private static function get_classic_body_totals_elements(): array {
		return array(
			array(
				'type'     => 'text',
				'id'       => 'total_label',
				'content'  => 'NET À PAYER:',
				'position' => array(
					'x' => 350,
					'y' => 380,
				),
				'style'    => array(
					'fontSize'   => 14,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'total_amount',
				'content'  => '{{total}} €',
				'position' => array(
					'x' => 480,
					'y' => 380,
				),
				'style'    => array(
					'fontSize'   => 14,
					'fontWeight' => 'bold',
				),
			),
		);
	}


	/**
	 * Éléments du template Corporate.
	 */
	private static function get_corporate_template_elements() {
		return array_merge(
			self::get_corporate_header_elements(),
			self::get_corporate_body_elements()
		);
	}

	/**
	 * Corporate template header elements.
	 */
	private static function get_corporate_header_elements(): array {
		return array_merge(
			self::get_corporate_header_company_elements(),
			self::get_corporate_header_date_elements(),
			self::get_corporate_header_recipient_elements()
		);
	}

	/**
	 * Corporate template header company elements.
	 */
	private static function get_corporate_header_company_elements(): array {
		return array(
			array(
				'type'        => 'line',
				'id'          => 'header_line',
				'position'    => array(
					'x' => 50,
					'y' => 40,
				),
				'endPosition' => array(
					'x' => 550,
					'y' => 40,
				),
				'style'       => array(
					'stroke'      => '#2c3e50',
					'strokeWidth' => 2,
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'company_name',
				'content'  => '{{company_name}}',
				'position' => array(
					'x' => 50,
					'y' => 60,
				),
				'style'    => array(
					'fontSize'   => 22,
					'fontWeight' => 'bold',
					'color'      => '#2c3e50',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'company_details',
				'content'  => '{{company_address}}\nSIRET: {{company_siret}}',
				'position' => array(
					'x' => 50,
					'y' => 85,
				),
				'style'    => array(
					'fontSize' => 10,
					'color'    => '#7f8c8d',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'invoice_title',
				'content'  => 'FACTURE COMMERCIALE',
				'position' => array(
					'x' => 350,
					'y' => 60,
				),
				'style'    => array(
					'fontSize'   => 18,
					'fontWeight' => 'bold',
					'color'      => '#34495e',
				),
			),
		);
	}

	/**
	 * Corporate template header date elements.
	 */
	private static function get_corporate_header_date_elements(): array {
		return array(
			// Numéro et références.
			array(
				'type'     => 'text',
				'id'       => 'invoice_ref',
				'content'  => 'Référence: {{invoice_number}}',
				'position' => array(
					'x' => 350,
					'y' => 90,
				),
				'style'    => array(
					'fontSize'   => 12,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'invoice_date',
				'content'  => 'Émise le: {{invoice_date}}',
				'position' => array(
					'x' => 350,
					'y' => 110,
				),
				'style'    => array(
					'fontSize' => 12,
				),
			),
		);
	}

	/**
	 * Corporate template header recipient elements.
	 */
	private static function get_corporate_header_recipient_elements(): array {
		return array(
			// Destinataire.
			array(
				'type'     => 'text',
				'id'       => 'recipient_label',
				'content'  => 'DESTINATAIRE',
				'position' => array(
					'x' => 50,
					'y' => 140,
				),
				'style'    => array(
					'fontSize'   => 11,
					'fontWeight' => 'bold',
					'color'      => '#2c3e50',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'client_name',
				'content'  => '{{client_name}}',
				'position' => array(
					'x' => 50,
					'y' => 160,
				),
				'style'    => array(
					'fontSize'   => 14,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'client_address',
				'content'  => '{{client_address}}',
				'position' => array(
					'x' => 50,
					'y' => 180,
				),
				'style'    => array(
					'fontSize' => 12,
				),
			),
		);
	}

	/**
	 * Corporate template body elements.
	 */
	private static function get_corporate_body_elements(): array {
		return array_merge(
			self::get_corporate_body_table_elements(),
			self::get_corporate_body_totals_elements()
		);
	}

	/**
	 * Corporate template body table elements.
	 */
	private static function get_corporate_body_table_elements(): array {
		return array(
			// Tableau corporate.
			array(
				'type'     => 'table',
				'id'       => 'items_table',
				'position' => array(
					'x' => 50,
					'y' => 220,
				),
				'style'    => array(
					'width'           => 500,
					'border'          => '1px solid #bdc3c7',
					'backgroundColor' => '#f8f9fa',
				),
				'headers'  => array( 'Prestation', 'Quantité', 'Prix HT', 'Montant HT' ),
				'data'     => '{{items}}',
			),
			// Ligne de séparation.
			array(
				'type'        => 'line',
				'id'          => 'footer_line',
				'position'    => array(
					'x' => 50,
					'y' => 350,
				),
				'endPosition' => array(
					'x' => 550,
					'y' => 350,
				),
				'style'       => array(
					'stroke'      => '#bdc3c7',
					'strokeWidth' => 1,
				),
			),
		);
	}

	/**
	 * Corporate template body totals elements.
	 */
	private static function get_corporate_body_totals_elements(): array {
		return array(
			array(
				'type'     => 'text',
				'id'       => 'total_ht_label',
				'content'  => 'Total HT:',
				'position' => array(
					'x' => 400,
					'y' => 370,
				),
				'style'    => array(
					'fontSize'   => 12,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'total_tva_label',
				'content'  => 'TVA (20%):',
				'position' => array(
					'x' => 400,
					'y' => 385,
				),
				'style'    => array( 'fontSize' => 12 ),
			),
			array(
				'type'     => 'text',
				'id'       => 'total_ttc_label',
				'content'  => 'NET À PAYER:',
				'position' => array(
					'x' => 400,
					'y' => 405,
				),
				'style'    => array(
					'fontSize'   => 14,
					'fontWeight' => 'bold',
				),
			),
			array(
				'type'     => 'text',
				'id'       => 'total_amount',
				'content'  => '{{total}} € TTC',
				'position' => array(
					'x' => 480,
					'y' => 405,
				),
				'style'    => array(
					'fontSize'   => 14,
					'fontWeight' => 'bold',
					'color'      => '#27ae60',
				),
			),
		);
	}

	/**
	 * Retourne un template par son slug.
	 *
	 * @param string $slug Le slug du template.
	 * @return array|null Les données du template ou null si non trouvé.
	 */
	public static function get_template_by_slug( $slug ) {
		$free_templates    = self::get_free_templates();
		$premium_templates = apply_filters( 'pdfib_premium_templates', array() );
		$premium_templates = is_array( $premium_templates ) ? $premium_templates : array();

		// Chercher dans les templates gratuits.
		if ( isset( $free_templates[ $slug ] ) ) {
			return $free_templates[ $slug ];
		}

		// Chercher dans les templates premium.
		if ( isset( $premium_templates[ $slug ] ) ) {
			return $premium_templates[ $slug ];
		}

		return null;
	}
}
