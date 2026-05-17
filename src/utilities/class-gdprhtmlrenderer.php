<?php
/**
 * Advanced PDF Invoice Builder - GDPR HTML Renderer
 * Génère le document HTML d'export RGPD.
 *
 * @package PDF_Builder_Pro
 * @since   1.6.12
 */

namespace PDFIB\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * Rendu HTML du document d'export RGPD utilisateur.
 */
class GdprHtmlRenderer {


	/**
	 * Génère le document HTML complet à partir des données utilisateur.
	 *
	 * @param array $user_data Les donnees utilisateur.
	 */
	public function render( array $user_data ): string {
		$user_info   = wp_get_current_user();
		$export_date = date_i18n( 'd/m/Y H:i:s', time() );

		$html  = $this->build_document_head();
		$html .= $this->build_body_header( $user_info, $export_date );
		$html .= $this->build_data_sections( $user_data );
		$html .= $this->build_footer();
		return $html;
	}

	/**
	 * Genere l en-tete HTML du document d export.
	 */
	private function build_document_head(): string {
		$css_file = plugin_dir_path( __FILE__ ) . '../../assets/css/gdpr-export.css';
		$css      = is_readable( $css_file ) ? (string) pdfib_filesystem()->get_contents( $css_file ) : '';
		return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html__( 'Mes Données Personnelles - Export RGPD', 'advanced-pdf-invoice-builder' ) . '</title>
    <style>' . $css . '</style>
</head>';
	}

	/**
	 * Genere l en-tete du corps HTML avec les informations utilisateur.
	 *
	 * @param \WP_User $user_info  Informations de l utilisateur.
	 * @param string   $export_date Date d export formatee.
	 */
	private function build_body_header( \WP_User $user_info, string $export_date ): string {
		return '
<body>
        <div class="pdfb-container">
        <div class="header">
            <h1>' . esc_html__( 'Mes Données Personnelles', 'advanced-pdf-invoice-builder' ) . '</h1>
            <div class="subtitle">' . esc_html__( 'Export RGPD - Document officiel', 'advanced-pdf-invoice-builder' ) . '</div>
            <div class="date">' . esc_html__( 'Généré le', 'advanced-pdf-invoice-builder' ) . ' ' . $export_date . '</div>
            <div class="actions">
                <button onclick="window.print()" class="print-button">
                    🖨️ ' . esc_html__( 'Imprimer le document', 'advanced-pdf-invoice-builder' ) . '
                </button>
            </div>
        </div>            <div class="user-info">
                <div class="user-info-item">
                    <div class="user-info-label">' . esc_html__( 'Utilisateur', 'advanced-pdf-invoice-builder' ) . '</div>
                    <div class="user-info-value">' . esc_html( $user_info->display_name ) . '</div>
                </div>
                <div class="user-info-item">
                    <div class="user-info-label">' . esc_html__( 'Email', 'advanced-pdf-invoice-builder' ) . '</div>
                    <div class="user-info-value">' . esc_html( $user_info->user_email ) . '</div>
                </div>
                <div class="user-info-item">
                    <div class="user-info-label">' . esc_html__( 'ID Utilisateur', 'advanced-pdf-invoice-builder' ) . '</div>
                    <div class="user-info-value">#' . esc_html( (string) $user_info->ID ) . '</div>
                </div>
            </div>

            <div class="privacy-notice">
                <h3>' . esc_html__( 'Protection de vos données', 'advanced-pdf-invoice-builder' ) . '</h3>
                <p>' . esc_html__( 'Ce document contient toutes les données personnelles que nous détenons à votre sujet, conformément au Règlement Général sur la Protection des Données (RGPD). Vous avez le droit de consulter, rectifier ou supprimer ces données à tout moment.', 'advanced-pdf-invoice-builder' ) . '</p>
            </div>

            <div class="content">';
	}

	/**
	 * Genere les sections de donnees HTML.
	 *
	 * @param array $user_data Les donnees utilisateur.
	 */
	private function build_data_sections( array $user_data ): string {
		$sections = $this->classify_user_data_sections( $user_data );
		$content  = '';
		foreach ( $sections as $section_title => $section_data ) {
			if ( ! empty( $section_data ) ) {
				$content .= '<div class="section"><h2>' . esc_html( $section_title ) . '</h2><div class="pdfb-section-content"><div class="data-grid">' . $this->format_data_items( $section_data ) . '</div></div></div>';
			}
		}
		return $content;
	}

	/**
	 * Classe les donnees utilisateur en sections.
	 *
	 * @param array $user_data Les donnees brutes utilisateur.
	 */
	private function classify_user_data_sections( array $user_data ): array {
		$sections = array(
			/* translators: Section title in GDPR export */ __( 'Informations de base', 'advanced-pdf-invoice-builder' )      => array(),
			/* translators: Section title in GDPR export */ __( 'Métadonnées utilisateur', 'advanced-pdf-invoice-builder' )   => array(),
			/* translators: Section title in GDPR export */ __( 'Préférences et paramètres', 'advanced-pdf-invoice-builder' ) => array(),
			/* translators: Section title in GDPR export */ __( 'Historique et activité', 'advanced-pdf-invoice-builder' )    => array(),
			/* translators: Section title in GDPR export */ __( 'Données RGPD', 'advanced-pdf-invoice-builder' )              => array(),
			/* translators: Section title in GDPR export */ __( 'Autre', 'advanced-pdf-invoice-builder' )                     => array(),
		);
		foreach ( $user_data as $key => $value ) {
			if ( strpos( $key, 'user_' ) === 0 || in_array( $key, array( 'ID', 'user_login', 'user_email', 'display_name' ), true ) ) {
				$sections['Informations de base'][ $key ] = $value;
			} elseif ( strpos( $key, 'meta_' ) === 0 || strpos( $key, 'wp_' ) === 0 ) {
				$sections['Métadonnées utilisateur'][ $key ] = $value;
			} elseif ( strpos( $key, 'pref' ) === 0 || strpos( $key, 'setting' ) === 0 || strpos( $key, 'option' ) === 0 ) {
				$sections['Préférences et paramètres'][ $key ] = $value;
			} elseif ( strpos( $key, 'consent' ) !== false || strpos( $key, 'gdpr' ) !== false ) {
				$sections['Données RGPD'][ $key ] = $value;
			} elseif ( strpos( $key, 'last_' ) === 0 || strpos( $key, 'date' ) !== false || strpos( $key, 'time' ) !== false ) {
				$sections['Historique et activité'][ $key ] = $value;
			} else {
				$sections['Autre'][ $key ] = $value;
			}
		}
		return $sections;
	}

	/**
	 * Génère les éléments HTML data-item pour un tableau de données.
	 * Extracted complex items to format_complex_item() to keep cognitive complexity ≤ 15.
	 *
	 * @param array $data Les donnees a formater.
	 */
	private function format_data_items( array $data ): string {
		$result = '';
		foreach ( $data as $key => $value ) {
			$display_key = ucfirst( str_replace( array( '_', '-' ), ' ', $key ) );
			if ( is_array( $value ) || is_object( $value ) ) {
				$result .= $this->format_complex_item( $display_key, $value );
			} else {
				$result .= '<div class="data-item"><div class="data-label">' . esc_html( $display_key ) . '</div><div class="data-value">' . esc_html( $value ) . '</div></div>';
			}
		}
		return $result;
	}

	/**
	 * Formate un element de donnee complexe (tableau/objet) en HTML.
	 *
	 * @param string $display_key Cle affichee.
	 * @param mixed  $value       Valeur complexe.
	 */
	private function format_complex_item( string $display_key, mixed $value ): string {
		if ( empty( $value ) ) {
			return '<div class="data-item"><div class="data-label">' . esc_html( $display_key ) . '</div><div class="data-value empty-notice">' . esc_html__( '(Aucune donnée)', 'advanced-pdf-invoice-builder' ) . '</div></div>';
		}
		if ( is_object( $value ) ) {
			$value = (array) $value;
		}
		$sub = '';
		foreach ( $value as $sub_key => $sub_value ) {
			$sub_display = ucfirst( str_replace( array( '_', '-' ), ' ', $sub_key ) );
			$sub_val     = ( is_array( $sub_value ) || is_object( $sub_value ) ) ? esc_html__( '(Données complexes)', 'advanced-pdf-invoice-builder' ) : esc_html( $sub_value );
			$sub        .= '<div class="array-item"><span class="array-key">' . esc_html( $sub_display ) . '</span><span class="array-value">' . $sub_val . '</span></div>';
		}
		return '<div class="data-item"><div class="data-label">' . esc_html( $display_key ) . '</div><div class="array-value">' . $sub . '</div></div>';
	}

	/**
	 * Genere le pied de page HTML du document d export.
	 */
	private function build_footer(): string {
		return '</div>

        <div class="footer">
            <h3>' . esc_html__( 'Advanced PDF Invoice Builder - Protection RGPD', 'advanced-pdf-invoice-builder' ) . '</h3>
            <p>' . esc_html__( 'Conformément au Règlement UE 2016/679 - Règlement Général sur la Protection des Données', 'advanced-pdf-invoice-builder' ) . '</p>
            <p>' . esc_html__( 'Pour toute question concernant vos données, contactez l\'administrateur du site', 'advanced-pdf-invoice-builder' ) . '</p>
            <div class="pdfb-footer-links">
                <a href="#" class="pdfb-footer-link">' . esc_html__( 'Demander rectification', 'advanced-pdf-invoice-builder' ) . '</a>
                <a href="#" class="pdfb-footer-link">' . esc_html__( 'Demander suppression', 'advanced-pdf-invoice-builder' ) . '</a>
                <a href="#" class="pdfb-footer-link">' . esc_html__( 'Contacter le DPO', 'advanced-pdf-invoice-builder' ) . '</a>
            </div>
        </div>
    </div>
</body>
</html>';
	}
}
