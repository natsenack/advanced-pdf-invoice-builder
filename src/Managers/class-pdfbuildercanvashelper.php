<?php
/**
 * Helper pour nettoyer et résoudre les données canvas.
 *
 * @package PDFIB\Managers
 */

namespace PDFIB\Managers;

defined( 'ABSPATH' ) || exit;

/**
 * Outils de nettoyage et de résolution des données canvas.
 */
class PdfBuilderCanvasHelper {

	/**
	 * Sanitise les éléments du canvas.
	 *
	 * @param mixed $elements Éléments bruts du canvas.
	 * @return array
	 */
	public function sanitize_canvas_elements( mixed $elements ): array {
		if ( ! is_array( $elements ) ) {
			return array();
		}
		$sanitized = array();
		foreach ( $elements as $element ) {
			if ( is_array( $element ) ) {
				$sanitized[] = $this->sanitize_one_canvas_element( $element );
			}
		}
		return $sanitized;
	}

	/**
	 * Sanitise un seul élément canvas.
	 *
	 * @param array $element Élément canvas brut.
	 * @return array
	 */
	private function sanitize_one_canvas_element( array $element ): array {
		$s = array(
			'id'     => isset( $element['id'] ) ? \sanitize_text_field( $element['id'] ) : '',
			'type'   => isset( $element['type'] ) && is_string( $element['type'] ) ? $element['type'] : '',
			'x'      => isset( $element['x'] ) ? floatval( $element['x'] ) : 0,
			'y'      => isset( $element['y'] ) ? floatval( $element['y'] ) : 0,
			'width'  => isset( $element['width'] ) ? floatval( $element['width'] ) : 0,
			'height' => isset( $element['height'] ) ? floatval( $element['height'] ) : 0,
		);
		if ( isset( $element['content'] ) ) {
			$s['content'] = $this->sanitize_element_content( $element['content'], isset( $element['type'] ) ? $element['type'] : '' );
		}
		if ( isset( $element['style'] ) && is_array( $element['style'] ) ) {
			$s['style'] = $this->sanitize_element_styles( $element['style'] );
		}
		return $s;
	}

	/**
	 * Sanitise le contenu d'un élément selon son type.
	 *
	 * @param mixed  $content Contenu brut.
	 * @param string $type Type d'élément.
	 * @return string
	 */
	private function sanitize_element_content( mixed $content, string $type ): string {
		switch ( $type ) {
			case 'text':
			case 'dynamic_text':
				return \wp_kses(
					$content,
					array(
						'br'     => array(),
						'strong' => array(),
						'em'     => array(),
						'u'      => array(),
					)
				);
			case 'image':
				return \esc_url_raw( $content );
			default:
				return \sanitize_text_field( $content );
		}
	}

	/**
	 * Sanitise les styles d'un élément.
	 *
	 * @param array $styles Styles bruts.
	 * @return array
	 */
	private function sanitize_element_styles( array $styles ): array {
		$allowed_styles = array(
			'fontSize',
			'fontFamily',
			'color',
			'backgroundColor',
			'textAlign',
			'fontWeight',
			'fontStyle',
			'textDecoration',
			'borderWidth',
			'borderColor',
			'borderStyle',
		);
		$sanitized      = array();
		foreach ( $styles as $key => $value ) {
			if ( in_array( $key, $allowed_styles, true ) ) {
				if ( strpos( $key, 'color' ) !== false ) {
					$sanitized[ $key ] = sanitize_hex_color( $value ) !== null ? sanitize_hex_color( $value ) : '#000000';
				} elseif ( strpos( $key, 'fontSize' ) !== false || strpos( $key, 'borderWidth' ) !== false ) {
					$sanitized[ $key ] = floatval( $value );
				} else {
					$sanitized[ $key ] = \sanitize_text_field( $value );
				}
			}
		}
		return $sanitized;
	}

	/**
	 * Sanitise un champ d'élément selon son type.
	 *
	 * @param string $field Nom du champ.
	 * @param mixed  $value Valeur brute.
	 * @return mixed
	 */
	private function sanitize_element_field( string $field, mixed $value ): mixed {
		$numeric_fields = array( 'x', 'y', 'width', 'height', 'rotation', 'opacity', 'zIndex' );
		if ( in_array( $field, $numeric_fields, true ) ) {
			return floatval( $value );
		}
		if ( 'content' === $field ) {
			return $this->sanitize_element_content( $value, 'text' );
		}
		if ( 'style' === $field ) {
			if ( is_array( $value ) ) {
				return $this->sanitize_element_styles( $value );
			}
			return array();
		}
		return \sanitize_text_field( $value );
	}

	/**
	 * Nettoie un élément canvas individuel.
	 *
	 * @param array $element Élément canvas brut.
	 * @return array|false
	 */
	private function clean_canvas_element( array $element ): array|false {
		$cleaned         = array();
		$required_fields = array( 'id', 'type', 'x', 'y', 'width', 'height' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $element[ $field ] ) ) {
				return false;
			}
			$cleaned[ $field ] = $this->sanitize_element_field( $field, $element[ $field ] );
		}
		$optional_fields = array( 'content', 'style', 'rotation', 'opacity', 'zIndex' );
		foreach ( $optional_fields as $field ) {
			if ( isset( $element[ $field ] ) ) {
				$cleaned[ $field ] = $this->sanitize_element_field( $field, $element[ $field ] );
			}
		}
		return $cleaned;
	}

	/**
	 * Valide et nettoie les éléments canvas récupérés.
	 *
	 * @param mixed $elements Éléments bruts.
	 * @return array
	 */
	public function validate_and_clean_canvas_elements( mixed $elements ): array {
		if ( ! is_array( $elements ) ) {
			if ( is_string( $elements ) ) {
				$decoded = json_decode( $elements, true );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					$elements = $decoded;
				} else {
					return array();
				}
			} else {
				return array();
			}
		}
		$cleaned_elements = array();
		foreach ( $elements as $element ) {
			if ( is_array( $element ) && isset( $element['type'] ) ) {
				$cleaned_element = $this->clean_canvas_element( $element );
				if ( $cleaned_element ) {
					$cleaned_elements[] = $cleaned_element;
				}
			}
		}
		return $cleaned_elements;
	}

	/**
	 * Vérifie si un template existe et retourne son contenu.
	 *
	 * @param int $template_id ID du template.
	 * @return array{0: bool, 1: ?string}
	 */
	public function resolve_template_existence( int $template_id ): array {
		if ( \get_post( $template_id ) ) {
			return array( true, null );
		}
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		$template        = pdfib_db()->get_row(
			pdfib_db()->prepare( 'SELECT id, name, template_data FROM %i WHERE id = %d', $table_templates, $template_id ),
			ARRAY_A
		);
		if ( $template && ! empty( $template['template_data'] ) ) {
			return array( true, $template['template_data'] );
		}
		return array( false, null );
	}

	/**
	 * Extrait les éléments canvas depuis un template.
	 *
	 * @param int         $template_id ID du template.
	 * @param string|null $template_data JSON du template.
	 * @return mixed
	 */
	public function resolve_canvas_elements_from_template( int $template_id, ?string $template_data ): mixed {
		if ( null !== $template_data ) {
			return $this->decode_template_json( $template_data );
		}
		return \get_post_meta( $template_id, 'pdfib_elements', true );
	}

	/**
	 * Décode le JSON d'un template et extrait les éléments canvas.
	 *
	 * @param string $template_data JSON brut.
	 * @return array
	 */
	private function decode_template_json( string $template_data ): array {
		$decoded = json_decode( $template_data, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
			return array();
		}
		if ( isset( $decoded['elements'] ) && is_array( $decoded['elements'] ) ) {
			return $decoded['elements'];
		}
		if ( ! empty( $decoded['pages'] ) && is_array( $decoded['pages'] ) ) {
			$first = $decoded['pages'][0];
			return ( isset( $first['elements'] ) && is_array( $first['elements'] ) ) ? $first['elements'] : array();
		}
		return array();
	}
}
