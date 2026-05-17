<?php
/**
 * Handlers AJAX liés au chargement de template.
 *
 * @package PDFIB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handler AJAX pour récupérer un template par son ID.
 * Extrait du fichier d'initialisation pour respecter la limite de complexité des fonctions.
 */
function pdfib_ajax_get_template() {
	if ( ! isset( $GLOBALS['_GET']['nonce'] ) || ! \pdfib_verify_nonce( sanitize_text_field( wp_unslash( $GLOBALS['_GET']['nonce'] ) ), 'pdfib_ajax' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Erreur de sécurité : nonce invalide.', 'advanced-pdf-invoice-builder' ) ) );
		return;
	}
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Permission refusée.', 'advanced-pdf-invoice-builder' ) ) );
		return;
	}
	$template_id = isset( $GLOBALS['_GET']['template_id'] ) ? intval( wp_unslash( $GLOBALS['_GET']['template_id'] ) ) : 0;
	if ( ! $template_id || $template_id < 1 ) {
		wp_send_json_error( array( 'message' => esc_html__( 'ID du template manquant ou invalide.', 'advanced-pdf-invoice-builder' ) ) );
		return;
	}
	$template = pdfib_fetch_template_from_db( $template_id );
	if ( ! $template ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Template non trouvé.', 'advanced-pdf-invoice-builder' ) ) );
		return;
	}
	$template_data = json_decode( $template['template_data'], true );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Erreur lors du décodage des données du template.', 'advanced-pdf-invoice-builder' ) ) );
		return;
	}
	$normalized = pdfib_normalize_template_elements( $template_data );
	if ( null === $normalized ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Format de données du template invalide.', 'advanced-pdf-invoice-builder' ) ) );
		return;
	}
	[ $elements, $canvas ] = $normalized;
	$elements              = pdfib_transform_elements_for_ajax( $elements );
	pdfib_enrich_logo_elements( $elements );
	wp_send_json_success(
		array(
			'id'       => $template['id'],
			'name'     => $template['name'],
			'elements' => $elements,
			'canvas'   => $canvas,
		)
	); }

/**
 * Récupère un template depuis la table pdfib_templates, avec fallback sur wp_posts.
 *
 * @param int $template_id Identifiant du template.
 * @return array|null
 */
function pdfib_fetch_template_from_db( int $template_id ): ?array {
	$table_templates = pdfib_db()->prefix . 'pdfib_templates';

	$template = pdfib_db()->get_row( pdfib_db()->prepare( 'SELECT * FROM %i WHERE id = %d', $table_templates, $template_id ), ARRAY_A );

	if ( $template ) {
		return $template;
	}

	// Fallback : chercher dans wp_posts.
	$post = \get_post( $template_id );
	if ( $post && 'pdfib_template' === $post->post_type ) {
		$template_data_raw = \get_post_meta( $post->ID, '_pdfib_template_data', true );
		if ( ! empty( $template_data_raw ) ) {
			return array(
				'id'            => $post->ID,
				'name'          => $post->post_title,
				'template_data' => $template_data_raw,
				'created_at'    => $post->post_date,
				'updated_at'    => $post->post_modified,
			);
		}
	}

	return null;
}

/**
 * Normalise les éléments et le canvas depuis les données JSON décodées.
 * Gère les 3 formats : {elements, canvas}, {pages[0].elements, canvas}, tableau plat.
 *
 * @param mixed $template_data Données du template décodées.
 * @return array|null [elements, canvas] ou null si format invalide
 */
function pdfib_normalize_template_elements( $template_data ): ?array {
	if ( ! is_array( $template_data ) ) {
		return null;
	}

	if ( isset( $template_data['elements'] ) ) {
		$elements = $template_data['elements'];
		$canvas   = $template_data['canvas'] ?? null;
	} elseif ( isset( $template_data['pages'] ) && is_array( $template_data['pages'] ) && ! empty( $template_data['pages'] ) ) {
		$elements = $template_data['pages'][0]['elements'] ?? array();
		$canvas   = $template_data['canvas'] ?? null;
	} else {
		$elements = $template_data;
		$canvas   = null;
	}

	// Décoder elements si string JSON.
	if ( is_string( $elements ) ) {
		$decoded  = json_decode( stripslashes( $elements ), true );
		$elements = ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : array();
	} elseif ( ! is_array( $elements ) ) {
		$elements = array();
	}

	// Décoder canvas si string JSON.
	if ( is_string( $canvas ) ) {
		$decoded = json_decode( stripslashes( $canvas ), true );
		$canvas  = ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : null;
	} elseif ( null !== $canvas && ! is_array( $canvas ) ) {
		$canvas = null;
	}

	return array( $elements, $canvas );
}

/**
 * Transforme un tableau d'éléments au format attendu par l'éditeur.
 * Normalise positions (x/y), dimensions (width/height), styles et propriétés de base.
 *
 * @param array $elements Éléments à transformer.
 * @return array
 */
function pdfib_transform_elements_for_ajax( array $elements ): array {
	$transformed = array();
	foreach ( $elements as $element ) {
		$transformed[] = pdfib_transform_single_element( $element );
	}
	return $transformed;
}

/**
 * Retourne les groupes de propriétés à copier lors de la transformation d'un élément.
 *
 * @return array{style: string[], copy: string[]}
 */
function pdfib_get_element_property_groups(): array {
	return array(
		'style' => array(
			'fontSize',
			'fontWeight',
			'color',
			'textAlign',
			'verticalAlign',
			'backgroundColor',
			'borderColor',
			'borderWidth',
			'borderStyle',
			'rotation',
			'opacity',
		),
		'copy'  => array(
			'visible',
			'locked',
			'zIndex',
			'name',
			'src',
			'logoUrl',
			'defaultSrc',
			'alignment',
			'borderRadius',
		),
	);
}

/**
 * Transforme un élément individuel au format éditeur.
 *
 * @param array $element Élément brut à transformer.
 * @return array
 */
function pdfib_transform_single_element( array $element ): array {
	$groups = pdfib_get_element_property_groups();
	$el     = array();

	// Propriétés de base.
	foreach ( array( 'id', 'type', 'content' ) as $key ) {
		if ( isset( $element[ $key ] ) ) {
			$el[ $key ] = $element[ $key ];
		}
	}

	// Position et dimensions.
	$el['x']      = (int) ( $element['position']['x'] ?? $element['x'] ?? 0 );
	$el['y']      = (int) ( $element['position']['y'] ?? $element['y'] ?? 0 );
	$el['width']  = (int) ( $element['size']['width'] ?? $element['width'] ?? 100 );
	$el['height'] = (int) ( $element['size']['height'] ?? $element['height'] ?? 50 );

	// Styles : lire depuis le sous-tableau style ou directement depuis l'élément.
	$style_src = ( isset( $element['style'] ) && is_array( $element['style'] ) ) ? $element['style'] : $element;
	foreach ( $groups['style'] as $prop ) {
		if ( isset( $style_src[ $prop ] ) ) {
			$el[ $prop ] = $style_src[ $prop ];
		}
	}

	// Propriétés diverses.
	foreach ( $groups['copy'] as $prop ) {
		if ( isset( $element[ $prop ] ) ) {
			$el[ $prop ] = $element[ $prop ];
		}
	}

	// Alias text ← content pour type=text.
	if ( isset( $el['type'] ) && 'text' === $el['type'] && isset( $el['content'] ) ) {
		$el['text'] = $el['content'];
	}

	$el['visible'] = $el['visible'] ?? true;
	$el['locked']  = $el['locked'] ?? false;

	return $el;
}

/**
 * Enrichit les éléments de type company_logo sans src/logoUrl
 * en résolvant le logo du thème ou l'option site_logo.
 *
 * @param array &$elements Éléments passés par référence.
 */
function pdfib_enrich_logo_elements( array &$elements ): void {
	foreach ( $elements as &$el ) {
		if (
			isset( $el['type'] ) && 'company_logo' === $el['type'] &&
			empty( $el['src'] ) && empty( $el['logoUrl'] )
		) {
			$custom_logo_id = \get_theme_mod( 'custom_logo' );
			$logo_url       = $custom_logo_id ? \wp_get_attachment_image_url( $custom_logo_id, 'full' ) : null;

			if ( ! $logo_url ) {
				$site_logo_id = \get_option( 'site_logo' );
				$logo_url     = $site_logo_id ? \wp_get_attachment_image_url( $site_logo_id, 'full' ) : null;
			}

			if ( $logo_url ) {
				$el['src'] = $logo_url;
			}
		}
	}
	unset( $el );
}
