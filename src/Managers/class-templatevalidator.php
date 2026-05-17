<?php
/**
 * Validation des données de template.
 *
 * @package PDFIB\Managers
 */

namespace PDFIB\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Valide la structure des données de template et les propriétés des éléments.
 */
class TemplateValidator {

	/**
	 * Valide un template complet.
	 *
	 * @param mixed $template_data Données brutes du template.
	 * @return array
	 */
	public function validate_template_data( mixed $template_data ): array {
		$errors = array();
		if ( ! is_array( $template_data ) ) {
			$errors[] = 'Les données doivent être un objet JSON (array PHP)';
			return $errors;
		}
		$errors = array_merge( $errors, $this->validate_required_keys( $template_data ) );
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		$width  = (float) $template_data['canvasWidth'];
		$height = (float) $template_data['canvasHeight'];
		if ( $width < 50 || $width > 2000 ) {
			$errors[] = "canvasWidth doit être entre 50 et 2000 (reçu: $width)";
		}
		if ( $height < 50 || $height > 2000 ) {
			$errors[] = "canvasHeight doit être entre 50 et 2000 (reçu: $height)";
		}
		$element_count = count( $template_data['elements'] );
		if ( $element_count > 1000 ) {
			$errors[] = "Nombre d'éléments trop élevé: $element_count (max: 1000)";
		}
		foreach ( $template_data['elements'] as $index => $element ) {
			$element_errors = $this->validate_element( $element, $index );
			$errors         = array_merge( $errors, $element_errors );
			if ( count( $errors ) >= 10 ) {
				$errors[] = '... et plus d\'erreurs détectées';
				break;
			}
		}
		return $errors;
	}

	/**
	 * Applique les valeurs de repli minimales à un template.
	 *
	 * @param array $template_data Données du template à compléter.
	 * @return void
	 */
	public function apply_fallbacks( array &$template_data ): void {
		if ( ! isset( $template_data['version'] ) ) {
			$template_data['version'] = '1.0.0';
		}
		if ( ! isset( $template_data['canvasWidth'] ) ) {
			$template_data['canvasWidth'] = 794;
		}
		if ( ! isset( $template_data['canvasHeight'] ) ) {
			$template_data['canvasHeight'] = 1123;
		}
		if ( ! isset( $template_data['elements'] ) ) {
			$template_data['elements'] = array();
		}
	}

	/**
	 * Vérifie les clés obligatoires.
	 *
	 * @param array $template_data Données du template.
	 * @return array
	 */
	private function validate_required_keys( array $template_data ): array {
		$errors   = array();
		$required = array( 'elements', 'canvasWidth', 'canvasHeight', 'version' );
		foreach ( $required as $key ) {
			if ( ! isset( $template_data[ $key ] ) ) {
				$errors[] = "Propriété obligatoire manquante: '$key'";
			}
		}
		if ( ! empty( $errors ) ) {
			return $errors;
		}
		if ( ! is_array( $template_data['elements'] ) ) {
			$errors[] = "'elements' doit être un tableau d'objets";
		}
		if ( ! is_numeric( $template_data['canvasWidth'] ) ) {
			$errors[] = "'canvasWidth' doit être un nombre (reçu: " . gettype( $template_data['canvasWidth'] ) . ')';
		}
		if ( ! is_numeric( $template_data['canvasHeight'] ) ) {
			$errors[] = "'canvasHeight' doit être un nombre (reçu: " . gettype( $template_data['canvasHeight'] ) . ')';
		}
		if ( ! is_string( $template_data['version'] ) ) {
			$errors[] = "'version' doit être une chaîne de caractères";
		}
		return $errors;
	}

	/**
	 * Valide un élément du canvas.
	 *
	 * @param mixed $element Élément brut.
	 * @param int   $index   Index de l'élément.
	 * @return array
	 */
	private function validate_element( mixed $element, int $index ): array {
		$errors = array();
		if ( ! is_array( $element ) ) {
			$errors[] = "Élément $index: doit être un objet JSON (reçu: " . gettype( $element ) . ')';
			return $errors;
		}
		if ( ! isset( $element['id'] ) ) {
			$errors[] = "Élément $index: propriété 'id' manquante";
		}
		if ( ! isset( $element['type'] ) ) {
			$errors[] = "Élément $index: propriété 'type' manquante";
		}
		if ( ! empty( $errors ) ) {
			return $errors;
		}
		$element_id   = $element['id'];
		$element_type = $element['type'];
		if ( ! is_string( $element_id ) || empty( $element_id ) ) {
			$errors[] = "Élément $index: id doit être une chaîne non-vide (reçu: '$element_id')";
		}
		$valid_types = $this->get_valid_element_types();
		if ( ! in_array( $element_type, $valid_types, true ) ) {
			$errors[] = "Élément $index ($element_id): type invalide '$element_type' (types valides: " . implode( ', ', $valid_types ) . ')';
		}
		$errors = array_merge( $errors, $this->validate_numeric_props( $element, $index, $element_id ) );
		$errors = array_merge( $errors, $this->validate_position_props( $element, $index, $element_id ) );
		$errors = array_merge( $errors, $this->validate_color_props( $element, $index, $element_id ) );
		$errors = array_merge( $errors, $this->validate_text_props( $element, $index, $element_id ) );
		return $errors;
	}

	/**
	 * Retourne les types d'éléments autorisés.
	 *
	 * @return array
	 */
	private function get_valid_element_types(): array {
		return array(
			'text',
			'image',
			'rectangle',
			'line',
			'product_table',
			'customer_info',
			'company_logo',
			'company_info',
			'order_number',
			'document_type',
			'textarea',
			'html',
			'divider',
			'progress_bar',
			'dynamic_text',
			'mentions',
			'woocommerce_order_date',
			'woocommerce_invoice_number',
		);
	}

	/**
	 * Valide les propriétés numériques d'un élément.
	 *
	 * @param array  $element Élément à contrôler.
	 * @param int    $index Index de l'élément.
	 * @param string $id Identifiant de l'élément.
	 * @return array
	 */
	private function validate_numeric_props( array $element, int $index, string $id ): array {
		$errors        = array();
		$numeric_props = array( 'x', 'y', 'width', 'height', 'fontSize', 'opacity', 'zIndex', 'borderWidth', 'borderRadius', 'padding', 'margin', 'rotation' );
		foreach ( $numeric_props as $prop ) {
			if ( isset( $element[ $prop ] ) && ! is_numeric( $element[ $prop ] ) ) {
				$errors[] = "Élément $index ($id): '$prop' doit être numérique (reçu: " . gettype( $element[ $prop ] ) . ')';
			}
		}
		return $errors;
	}

	/**
	 * Valide les propriétés de position d'un élément.
	 *
	 * @param array  $element Élément à contrôler.
	 * @param int    $index Index de l'élément.
	 * @param string $id Identifiant de l'élément.
	 * @return array
	 */
	private function validate_position_props( array $element, int $index, string $id ): array {
		$errors = array();
		foreach ( array( 'x', 'y', 'width', 'height' ) as $prop ) {
			if ( ! isset( $element[ $prop ] ) ) {
				$errors[] = "Élément $index ($id): propriété '$prop' obligatoire manquante";
			} else {
				$value = (float) $element[ $prop ];
				if ( $value < 0 || $value > 3000 ) {
					$errors[] = "Élément $index ($id): '$prop' doit être entre 0 et 3000 (reçu: $value)";
				}
			}
		}
		return $errors;
	}

	/**
	 * Valide les propriétés de couleur d'un élément.
	 *
	 * @param array  $element Élément à contrôler.
	 * @param int    $index Index de l'élément.
	 * @param string $id Identifiant de l'élément.
	 * @return array
	 */
	private function validate_color_props( array $element, int $index, string $id ): array {
		$errors = array();
		foreach ( array( 'color', 'backgroundColor', 'borderColor', 'shadowColor' ) as $prop ) {
			if ( isset( $element[ $prop ] ) && ! empty( $element[ $prop ] ) ) {
				$color = $element[ $prop ];
				if ( 'transparent' !== $color && ! preg_match( '/^#[0-9A-Fa-f]{3,6}$/', $color ) ) {
					$errors[] = "Élément $index ($id): '$prop' format couleur invalide '$color'";
				}
			}
		}
		return $errors;
	}

	/**
	 * Valide les propriétés textuelles d'un élément.
	 *
	 * @param array  $element Élément à contrôler.
	 * @param int    $index Index de l'élément.
	 * @param string $id Identifiant de l'élément.
	 * @return array
	 */
	private function validate_text_props( array $element, int $index, string $id ): array {
		$errors       = array();
		$valid_values = array(
			'fontWeight'     => array( 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900' ),
			'textAlign'      => array( 'left', 'center', 'right', 'justify' ),
			'textDecoration' => array( 'none', 'underline', 'overline', 'line-through' ),
			'fontStyle'      => array( 'normal', 'italic', 'oblique' ),
		);
		foreach ( array( 'fontFamily', 'fontWeight', 'textAlign', 'textDecoration', 'fontStyle' ) as $prop ) {
			if ( isset( $element[ $prop ], $valid_values[ $prop ] ) && ! in_array( $element[ $prop ], $valid_values[ $prop ], true ) ) {
				$errors[] = "Élément $index ($id): '$prop' valeur invalide '" . $element[ $prop ] . "' (valeurs: " . implode( ', ', $valid_values[ $prop ] ) . ')';
			}
		}
		return $errors;
	}
}
