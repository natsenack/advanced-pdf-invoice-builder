<?php
/**
 * Company info element generator.
 *
 * @package PDFIB\HTMLGenerators\Generators
 */

namespace PDFIB\HTMLGenerators\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PDFIB\HTMLGenerators\ElementGeneratorBase;

/**
 * Generates HTML for company info elements.
 */
class CompanyInfoGenerator extends ElementGeneratorBase {

	/**
	 * Generate HTML for this element.
	 *
	 * @return string
	 */
	public function generate_html() {
		$styles     = $this->get_element_styles();
		$style_attr = $this->create_style_string( $styles );

		$display_config = array(
			'companyName' => $this->get_property( 'showCompanyName', true ) !== false,
			'address'     => $this->get_property( 'showAddress', true ) !== false,
			'phone'       => $this->get_property( 'showPhone', true ) !== false,
			'email'       => $this->get_property( 'showEmail', true ) !== false,
			'siret'       => $this->get_property( 'showSiret', true ) !== false,
			'vat'         => $this->get_property( 'showVat', true ) !== false,
			'rcs'         => $this->get_property( 'showRcs', true ) !== false,
			'capital'     => $this->get_property( 'showCapital', true ) !== false,
		);
		$company_data   = $this->getCompanyData();
		$theme          = $this->getTheme();
		$bg_color       = $this->normalize_color( $this->get_property( 'backgroundColor', $theme['backgroundColor'] ) );
		$border_color   = $this->normalize_color( $this->get_property( 'borderColor', $theme['borderColor'] ) );
		$text_color     = $this->normalize_color( $this->get_property( 'textColor', $theme['textColor'] ) );
		$header_color   = $this->normalize_color( $this->get_property( 'headerTextColor', $theme['headerTextColor'] ) );
		$header_size    = $this->get_property( 'headerFontSize', 14 );
		$body_size      = $this->get_property( 'bodyFontSize', 12 );

		$html = '<div class="pdf-element pdf-company-info" ' . $style_attr . '>';

		if ( $this->get_property( 'showBackground', true ) !== false ) {
			$html .= '<div style="position:absolute; top:0; left:0; width:100%; height:100%; background-color:' . $bg_color . '; z-index:-1;"></div>';
		}

		$html .= '<div style="padding:10px; height:100%; overflow:hidden;">';
		$html .= $this->buildCompanyNameAndAddressHTML( $company_data, $display_config, $header_size, $header_color, $body_size, $text_color );
		$html .= $this->buildCompanyFieldsHTML( $company_data, $display_config, $body_size, $text_color );
		$html .= '</div>';

		if ( $this->get_property( 'showBorders', true ) !== false ) {
			$border_width = $this->get_property( 'borderWidth', 1 );
			$html        .= '<div style="position:absolute; top:0; left:0; width:100%; height:100%; border:' . $border_width . 'px solid ' . $border_color . '; box-sizing:border-box; pointer-events:none;"></div>';
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Build company name and address HTML.
	 *
	 * @param array  $company_data   Company data.
	 * @param array  $display_config Display configuration.
	 * @param int    $header_size    Header font size.
	 * @param string $header_color   Header text color.
	 * @param int    $body_size      Body font size.
	 * @param string $text_color     Body text color.
	 */
	private function buildCompanyNameAndAddressHTML( array $company_data, array $display_config, int $header_size, string $header_color, int $body_size, string $text_color ): string {
		$html = '';
		if ( $display_config['companyName'] && $this->hasValue( $company_data['name'] ) ) {
			$html .= '<div style="font-size:' . $header_size . 'px; font-weight:bold; color:' . $header_color . '; margin-bottom:5px;">';
			$html .= htmlspecialchars( $company_data['name'] );
			$html .= '</div>';
		}
		if ( $display_config['address'] ) {
			$address = '';
			if ( $this->hasValue( $company_data['address'] ) ) {
				$address .= htmlspecialchars( $company_data['address'] );
			}
			if ( $this->hasValue( $company_data['city'] ) ) {
				if ( ! empty( $address ) ) {
					$address .= ', ';
				}
				$address .= htmlspecialchars( $company_data['city'] );
			}
			if ( ! empty( $address ) ) {
				$html .= '<div style="font-size:' . $body_size . 'px; color:' . $text_color . '; margin-bottom:3px;">' . $address . '</div>';
			}
		}
		return $html;
	}

	/**
	 * Build company fields HTML.
	 *
	 * @param array  $company_data   Company data.
	 * @param array  $display_config Display configuration.
	 * @param int    $body_size      Body font size.
	 * @param string $text_color     Body text color.
	 */
	private function buildCompanyFieldsHTML( array $company_data, array $display_config, int $body_size, string $text_color ): string {
		$html   = '';
		$fields = array(
			array(
				'value' => $company_data['siret'],
				'show'  => $display_config['siret'],
				'label' => 'SIRET',
			),
			array(
				'value' => $company_data['tva'],
				'show'  => $display_config['vat'],
				'label' => 'TVA',
			),
			array(
				'value' => $company_data['rcs'],
				'show'  => $display_config['rcs'],
				'label' => 'RCS',
			),
			array(
				'value' => $company_data['capital'],
				'show'  => $display_config['capital'],
				'label' => 'Capital',
			),
			array(
				'value' => $company_data['phone'],
				'show'  => $display_config['phone'],
				'label' => 'Tél',
			),
			array(
				'value' => $company_data['email'],
				'show'  => $display_config['email'],
				'label' => 'Email',
			),
		);
		foreach ( $fields as $field ) {
			if ( $field['show'] && $this->hasValue( $field['value'] ) ) {
				$html .= '<div style="font-size:' . $body_size . 'px; color:' . $text_color . '; margin-bottom:2px;">';
				$html .= htmlspecialchars( $field['value'] );
				$html .= '</div>';
			}
		}
		return $html;
	}

	/**
	 * Get company data from element or global settings.
	 */
	private function getCompanyData() {
		$company_name    = $this->get_property( 'companyName', '' );
		$company_address = $this->get_property( 'companyAddress', '' );
		$company_city    = $this->get_property( 'companyCity', '' );
		$company_siret   = $this->get_property( 'companySiret', '' );
		$company_tva     = $this->get_property( 'companyTva', '' );
		$company_rcs     = $this->get_property( 'companyRcs', '' );
		$company_capital = $this->get_property( 'companyCapital', '' );
		$company_phone   = $this->get_property( 'companyPhone', '' );
		$company_email   = $this->get_property( 'companyEmail', '' );
		return array(
			'name'    => $company_name ? $company_name : pdfib_get_option( 'pdfib_company_name', '' ),
			'address' => $company_address ? $company_address : pdfib_get_option( 'pdfib_company_address', '' ),
			'city'    => $company_city ? $company_city : pdfib_get_option( 'pdfib_company_city', '' ),
			'siret'   => $company_siret ? $company_siret : pdfib_get_option( 'pdfib_company_siret', '' ),
			'tva'     => $company_tva ? $company_tva : pdfib_get_option( 'pdfib_company_tva', '' ),
			'rcs'     => $company_rcs ? $company_rcs : pdfib_get_option( 'pdfib_company_rcs', '' ),
			'capital' => $company_capital ? $company_capital : pdfib_get_option( 'pdfib_company_capital', '' ),
			'phone'   => $company_phone ? $company_phone : pdfib_get_option( 'pdfib_company_phone_manual', '' ),
			'email'   => $company_email ? $company_email : pdfib_get_option( 'pdfib_company_email', '' ),
		);
	}

	/**
	 * Get theme configuration.
	 */
	private function getTheme() {
		$theme_name = $this->get_property( 'theme', 'corporate' );

		$themes = array(
			'corporate' => array(
				'backgroundColor' => '#f5f5f5',
				'borderColor'     => '#333333',
				'textColor'       => '#333333',
				'headerTextColor' => '#000000',
			),
			'minimal'   => array(
				'backgroundColor' => '#ffffff',
				'borderColor'     => '#cccccc',
				'textColor'       => '#666666',
				'headerTextColor' => '#333333',
			),
			'modern'    => array(
				'backgroundColor' => '#f9f9f9',
				'borderColor'     => '#0066cc',
				'textColor'       => '#444444',
				'headerTextColor' => '#0066cc',
			),
		);

		return $themes[ $theme_name ] ?? $themes['corporate'];
	}

	/**
	 * Check if a value is valid/not empty.
	 *
	 * @param mixed $value Value to check.
	 */
	private function hasValue( mixed $value ): bool {
		return ! empty( $value ) && 'Non indiqué' !== $value;
	}
}
