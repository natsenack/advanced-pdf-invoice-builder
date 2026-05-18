<?php
/**
 * Mentions element generator.
 *
 * @package PDFIB\HTMLGenerators\Generators
 */

namespace PDFIB\HTMLGenerators\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PDFIB\HTMLGenerators\ElementGeneratorBase;

/**
 * Generates HTML for mentions elements.
 */
class MentionsElementGenerator extends ElementGeneratorBase {
	/**
	 * Generate HTML for this element.
	 *
	 * @return string
	 */
	public function generate_html() {
		$original_element = $this->element;
		$this->element    = $this->apply_mentions_theme_defaults( $this->element );

		$styles     = $this->get_element_styles();
		$style_attr = $this->create_style_string( $styles );
		$content    = $this->resolve_mentions_content();

		$html = '<div class="pdf-element pdf-mentions" ' . $style_attr . '>';

		if ( $this->get_property( 'showSeparator', true ) !== false ) {
			$separator_style  = $this->get_property( 'separatorStyle', 'solid' );
			$separator_color  = $this->normalize_color( $this->get_property( 'separatorColor', '#e5e7eb' ) );
			$separator_width  = max( 1, (int) $this->get_property( 'separatorWidth', 1 ) );
			$separator_margin = (int) $this->get_property( 'margin', 0 );

			$html .= '<div style="margin:' . $separator_margin . 'px 0 8px 0; border-top:' . $separator_width . 'px ' . $separator_style . ' ' . $separator_color . '; width:100%;"></div>';
		}

		$html .= '<div class="pdf-mentions__content" style="white-space:pre-wrap;">' . nl2br( htmlspecialchars( $content ) ) . '</div>';
		$html .= '</div>';

		$this->element = $original_element;

		return $html;
	}

	/**
	 * Apply theme defaults to mentions elements when only the theme is stored.
	 *
	 * @param array $element Element data.
	 * @return array
	 */
	private function apply_mentions_theme_defaults( array $element ): array {
		$theme = (string) ( $element['theme'] ?? '' );
		if ( '' === $theme ) {
			return $element;
		}

		$defaults = $this->get_mentions_theme_defaults( $theme );
		if ( empty( $defaults ) ) {
			return $element;
		}

		foreach ( $defaults as $key => $value ) {
			if ( ! array_key_exists( $key, $element ) || '' === $element[ $key ] || null === $element[ $key ] ) {
				$element[ $key ] = $value;
			}
		}

		return $element;
	}

	/**
	 * Get theme defaults for mentions presets.
	 *
	 * @param string $theme Theme identifier.
	 * @return array<string,mixed>
	 */
	private function get_mentions_theme_defaults( string $theme ): array {
		switch ( $theme ) {
			case 'subtle':
				return array(
					'backgroundColor' => '#f9fafb',
					'borderColor'     => '#e5e7eb',
					'borderWidth'     => 1,
					'borderStyle'     => 'solid',
					'borderRadius'    => 4,
					'textColor'       => '#6b7280',
					'separatorColor'  => '#cbd5e1',
					'showBackground'  => true,
				);
			case 'highlighted':
				return array(
					'backgroundColor' => '#eff6ff',
					'borderColor'     => '#bfdbfe',
					'borderWidth'     => 1,
					'borderStyle'     => 'solid',
					'borderRadius'    => 4,
					'textColor'       => '#1d4ed8',
					'separatorColor'  => '#60a5fa',
					'showBackground'  => true,
				);
			case 'elegant':
				return array(
					'backgroundColor' => '#ffffff',
					'borderColor'     => '#ddd6fe',
					'borderWidth'     => 1,
					'borderStyle'     => 'solid',
					'borderRadius'    => 4,
					'textColor'       => '#6d28d9',
					'separatorColor'  => '#8b5cf6',
					'showBackground'  => true,
				);
			case 'clean':
			default:
				return array(
					'backgroundColor' => '#ffffff',
					'borderColor'     => '#e5e7eb',
					'borderWidth'     => 1,
					'borderStyle'     => 'solid',
					'borderRadius'    => 4,
					'textColor'       => '#374151',
					'separatorColor'  => '#e5e7eb',
					'showBackground'  => true,
				);
		}
	}

	/**
	 * Resolve the content to render for mentions.
	 *
	 * The editor persists the final text in `text`; older templates may use
	 * `content`, so we support both fields.
	 */
	private function resolve_mentions_content(): string {
		$content = (string) $this->get_property( 'text', '' );
		if ( '' === trim( $content ) ) {
			$content = (string) $this->get_property( 'content', '' );
		}

		if ( '' === trim( $content ) ) {
			$content = $this->build_company_mentions_text();
		}

		foreach ( $this->order_data as $key => $value ) {
			if ( is_string( $value ) || is_numeric( $value ) ) {
				$content = str_replace( '{{' . $key . '}}', (string) $value, $content );
			}
		}

		return $content;
	}

	/**
	 * Build a fallback company-info mention block when no free text exists.
	 *
	 * This keeps legacy templates readable even if they only toggle company fields.
	 */
	private function build_company_mentions_text(): string {
		$parts   = array();
		$company = $this->company_data;

		if ( $this->get_property( 'showEmail', true ) !== false ) {
			$email = $this->resolve_company_value( 'email', 'email' );
			if ( '' !== trim( $email ) ) {
				$parts[] = $email;
			}
		}

		if ( $this->get_property( 'showPhone', true ) !== false ) {
			$phone = $this->resolve_company_value( 'phone', 'phone' );
			if ( '' !== trim( $phone ) ) {
				$parts[] = $phone;
			}
		}

		if ( $this->get_property( 'showAddress', false ) === true ) {
			$address = $this->resolve_company_value( 'address', 'address' );
			if ( '' !== trim( $address ) ) {
				$parts[] = $address;
			}
		}

		if ( $this->get_property( 'showSiret', true ) !== false ) {
			$siret = $this->resolve_company_value( 'siret', 'siret' );
			if ( '' !== trim( $siret ) ) {
				$parts[] = 'SIRET: ' . $siret;
			}
		}

		if ( $this->get_property( 'showVat', true ) !== false ) {
			$vat = $this->resolve_company_value( 'tva', 'vat' );
			if ( '' !== trim( $vat ) ) {
				$parts[] = 'TVA: ' . $vat;
			}
		}

		if ( $this->get_property( 'showRcs', false ) === true ) {
			$rcs = $this->resolve_company_value( 'rcs', 'rcs' );
			if ( '' !== trim( $rcs ) ) {
				$parts[] = 'RCS: ' . $rcs;
			}
		}

		if ( $this->get_property( 'showCapital', false ) === true ) {
			$capital = $this->resolve_company_value( 'capital', 'capital' );
			if ( '' !== trim( $capital ) ) {
				$parts[] = 'Capital: ' . $capital;
			}
		}

		$separator = (string) $this->get_property( 'separator', ' • ' );
		if ( empty( $parts ) ) {
			return '';
		}

		return implode( $separator, $parts );
	}

	/**
	 * Resolve a company value from the element properties or the company data.
	 *
	 * @param string $property_name Element property name.
	 * @param string $company_key Company data key.
	 * @return string
	 */
	private function resolve_company_value( string $property_name, string $company_key ): string {
		$element_value = trim( (string) $this->get_property( $property_name, '' ) );
		if ( '' !== $element_value && 'Non indiqué' !== $element_value ) {
			return $element_value;
		}

		$company_value = trim( (string) ( $this->company_data[ $company_key ] ?? '' ) );
		if ( '' !== $company_value && 'Non indiqué' !== $company_value ) {
			return $company_value;
		}

		return '';
	}
}
