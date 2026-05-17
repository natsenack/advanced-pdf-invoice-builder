<?php
/**
 * Enregistrement des sections/champs de paramètres admin.
 *
 * @package PDFIB
 */

namespace PDFIB\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Registers WordPress settings sections and field callbacks for PdfBuilderAdmin.
 */
class AdminSettingsRegistrar {

	/**
	 * Enregistre les sections de configuration principales.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		$this->register_general_settings_section();
		$this->register_security_settings_section();
		$this->register_pdf_settings_section();
		$this->register_canvas_settings_section();
		$this->register_template_settings_section();
	}

	/**
	 * Enregistre les champs de la section générale.
	 *
	 * @return void
	 */
	private function register_general_settings_section(): void {
		\add_settings_section( 'pdfib_general', \__( 'Paramètres Généraux', 'advanced-pdf-invoice-builder' ), array( $this, 'general_section_callback' ), 'pdfib_general' );
		\add_settings_field( 'company_name', \__( 'Nom de l\'entreprise', 'advanced-pdf-invoice-builder' ), array( $this, 'company_name_field_callback' ), 'pdfib_general', 'pdfib_general' );
		\add_settings_field( 'company_address', \__( 'Adresse', 'advanced-pdf-invoice-builder' ), array( $this, 'company_address_field_callback' ), 'pdfib_general', 'pdfib_general' );
		\add_settings_section( 'pdfib_licence', \__( 'Paramètres de Licence', 'advanced-pdf-invoice-builder' ), array( $this, 'licence_section_callback' ), 'pdfib_licence' );
	}

	/**
	 * Enregistre les champs de la section sécurité.
	 *
	 * @return void
	 */
	private function register_security_settings_section(): void {
		\add_settings_section( 'pdfib_securite', \__( 'Paramètres de Sécurité', 'advanced-pdf-invoice-builder' ), array( $this, 'securite_section_callback' ), 'pdfib_securite' );
		\add_settings_field( 'security_file_validation', \__( 'Validation des fichiers', 'advanced-pdf-invoice-builder' ), array( $this, 'security_file_validation_field_callback' ), 'pdfib_securite', 'pdfib_securite' );
	}

	/**
	 * Enregistre les champs de la section PDF.
	 *
	 * @return void
	 */
	private function register_pdf_settings_section(): void {
		\add_settings_section( 'pdfib_pdf', \__( 'Configuration PDF', 'advanced-pdf-invoice-builder' ), array( $this, 'pdf_section_callback' ), 'pdfib_pdf' );
		\add_settings_field( 'pdf_quality', \__( 'Qualité PDF', 'advanced-pdf-invoice-builder' ), array( $this, 'pdf_quality_field_callback' ), 'pdfib_pdf', 'pdfib_pdf' );
		\add_settings_field( 'pdf_compression', \__( 'Compression PDF', 'advanced-pdf-invoice-builder' ), array( $this, 'pdf_compression_field_callback' ), 'pdfib_pdf', 'pdfib_pdf' );
	}

	/**
	 * Enregistre les champs de la section canvas.
	 *
	 * @return void
	 */
	private function register_canvas_settings_section(): void {
		\add_settings_section( 'pdfib_contenu', \__( 'Canvas & Design', 'advanced-pdf-invoice-builder' ), array( $this, 'contenu_section_callback' ), 'pdfib_contenu' );
		\add_settings_field( 'canvas_default_width', \__( 'Largeur par défaut du canvas', 'advanced-pdf-invoice-builder' ), array( $this, 'canvas_default_width_field_callback' ), 'pdfib_contenu', 'pdfib_contenu' );
	}

	/**
	 * Enregistre les champs de la section templates.
	 *
	 * @return void
	 */
	private function register_template_settings_section(): void {
		\add_settings_section( 'pdfib_templates', \__( 'Paramètres Templates', 'advanced-pdf-invoice-builder' ), array( $this, 'templates_section_callback' ), 'pdfib_templates' );
		\add_settings_field( 'template_cache_enabled', \__( 'Cache des templates activé', 'advanced-pdf-invoice-builder' ), array( $this, 'template_cache_enabled_field_callback' ), 'pdfib_templates', 'pdfib_templates' );
	}

	/**
	 * Fonction de nettoyage des paramètres.
	 *
	 * @param array $input Paramètres bruts soumis depuis le formulaire.
	 *
	 * @return array
	 */
	public function sanitize_settings( array $input ) {
		$sanitized = array();

		if ( isset( $input['company_name'] ) ) {
			$sanitized['company_name'] = \sanitize_text_field( $input['company_name'] );
		}
		if ( isset( $input['company_address'] ) ) {
			$sanitized['company_address'] = \sanitize_textarea_field( $input['company_address'] );
		}

		if ( isset( $input['security_file_validation'] ) ) {
			$sanitized['security_file_validation'] = $input['security_file_validation'] ? '1' : '0';
		}

		if ( isset( $input['pdf_quality'] ) ) {
			$sanitized['pdf_quality'] = in_array( $input['pdf_quality'], array( 'low', 'medium', 'high' ), true ) ? $input['pdf_quality'] : 'high';
		}
		if ( isset( $input['pdf_compression'] ) ) {
			$sanitized['pdf_compression'] = $input['pdf_compression'] ? '1' : '0';
		}

		if ( isset( $input['canvas_default_width'] ) ) {
			$sanitized['canvas_default_width'] = max( 400, min( 2000, \absint( $input['canvas_default_width'] ) ) );
		}

		if ( isset( $input['template_cache_enabled'] ) ) {
			$sanitized['template_cache_enabled'] = $input['template_cache_enabled'] ? '1' : '0';
		}

		return $sanitized;
	}

	/**
	 * Callback pour la section général
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Configuration générale du générateur de PDF.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour le champ nom de l'entreprise
	 */
	public function company_name_field_callback() {
		$settings = pdfib_get_option( 'pdfib_settings', array() );
		$value    = isset( $settings['company_name'] ) ? $settings['company_name'] : '';
		echo '<input type="text" name="pdfib_settings[company_name]" value="' . \esc_attr( $value ) . '" class="regular-text" aria-label="' . esc_attr__( 'Nom de l\'entreprise', 'advanced-pdf-invoice-builder' ) . '" />';
	}

	/**
	 * Callback pour le champ adresse
	 */
	public function company_address_field_callback() {
		$settings = pdfib_get_option( 'pdfib_settings', array() );
		$value    = isset( $settings['company_address'] ) ? $settings['company_address'] : '';
		echo '<textarea name="pdfib_settings[company_address]" rows="3" class="large-text">' . \esc_textarea( $value ) . '</textarea>';
	}

	/**
	 * Callback pour la section licence
	 */
	public function licence_section_callback() {
		echo '<p>' . esc_html__( 'Configuration de la licence du plugin.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour la section sécurité
	 */
	public function securite_section_callback() {
		echo '<p>' . esc_html__( 'Paramètres de sécurité pour protéger vos documents PDF.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour la section PDF
	 */
	public function pdf_section_callback() {
		echo '<p>' . esc_html__( 'Configuration de la génération et de la qualité des fichiers PDF.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour la section contenu
	 */
	public function contenu_section_callback() {
		echo '<p>' . esc_html__( 'Paramètres du canvas et options de design pour vos documents.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour la section templates
	 */
	public function templates_section_callback() {
		echo '<p>' . esc_html__( 'Configuration des templates et options de mise en cache.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour le champ validation des fichiers
	 */
	public function security_file_validation_field_callback() {
		$settings = pdfib_get_option( 'pdfib_settings', array() );
		$value    = isset( $settings['security_file_validation'] ) ? $settings['security_file_validation'] : '1';
		echo '<input type="checkbox" name="pdfib_settings[security_file_validation]" value="1" ' . \checked( $value, '1', false ) . ' />';
		echo '<label>' . esc_html__( 'Activer la validation des fichiers uploadés', 'advanced-pdf-invoice-builder' ) . '</label>';
		echo '<p class="description">' . esc_html__( 'Vérifie les types et tailles des fichiers pour la sécurité.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour le champ qualité PDF
	 */
	public function pdf_quality_field_callback() {
		$settings = pdfib_get_option( 'pdfib_settings', array() );
		$value    = isset( $settings['pdf_quality'] ) ? $settings['pdf_quality'] : 'high';
		echo '<select name="pdfib_settings[pdf_quality]">';
		echo '<option value="low" ' . \selected( $value, 'low', false ) . '>' . esc_html__( 'Faible (72 DPI)', 'advanced-pdf-invoice-builder' ) . '</option>';
		echo '<option value="medium" ' . \selected( $value, 'medium', false ) . '>' . esc_html__( 'Moyenne (150 DPI)', 'advanced-pdf-invoice-builder' ) . '</option>';
		echo '<option value="high" ' . \selected( $value, 'high', false ) . '>' . esc_html__( 'Haute (300 DPI)', 'advanced-pdf-invoice-builder' ) . '</option>';
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Qualité d\'export des images dans le PDF.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour le champ compression PDF
	 */
	public function pdf_compression_field_callback() {
		$settings = pdfib_get_option( 'pdfib_settings', array() );
		$value    = isset( $settings['pdf_compression'] ) ? $settings['pdf_compression'] : '1';
		echo '<input type="checkbox" name="pdfib_settings[pdf_compression]" value="1" ' . \checked( $value, '1', false ) . ' />';
		echo '<label>' . esc_html__( 'Activer la compression des images', 'advanced-pdf-invoice-builder' ) . '</label>';
		echo '<p class="description">' . esc_html__( 'Réduit la taille du fichier PDF en compressant les images.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour le champ largeur par défaut du canvas
	 */
	public function canvas_default_width_field_callback() {
		$settings = pdfib_get_option( 'pdfib_settings', array() );
		$value    = isset( $settings['canvas_default_width'] ) ? $settings['canvas_default_width'] : '800';
		echo '<input type="number" name="pdfib_settings[canvas_default_width]" value="' . esc_attr( $value ) . '" class="small-text" min="400" max="2000" /> ' . esc_html( 'px' );
		echo '<p class="description">' . esc_html__( 'Largeur par défaut du canvas en pixels (400-2000).', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Callback pour le champ cache des templates
	 */
	public function template_cache_enabled_field_callback() {
		$settings = pdfib_get_option( 'pdfib_settings', array() );
		$value    = isset( $settings['template_cache_enabled'] ) ? $settings['template_cache_enabled'] : '1';
		echo '<input type="checkbox" name="pdfib_settings[template_cache_enabled]" value="1" ' . \checked( $value, '1', false ) . ' />';
		echo '<label>' . esc_html__( 'Activer le cache des templates', 'advanced-pdf-invoice-builder' ) . '</label>';
		echo '<p class="description">' . esc_html__( 'Améliore les performances en mettant en cache les templates compilés.', 'advanced-pdf-invoice-builder' ) . '</p>';
	}

	/**
	 * Récupère la version du plugin
	 */
}
