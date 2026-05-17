<?php
/**
 * Templates Page - Advanced PDF Invoice Builder
 *
 * PHP version 8.2
 *
 * @category Plugin
 * @package  PDFIB
 * @author   Natsenack <threeaxe.france@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/natsenack/wp-pdf-builder-pro
 */

// Empêcher l'accès direct.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

// Compatibilité: certaines installations chargent cette vue avant le loader.
if ( ! function_exists( 'pdfib_db' ) ) {
	/**
	 * Retourne l'instance $wpdb globale.
	 *
	 * @return \wpdb Instance de la base de données WordPress.
	 */
	function pdfib_db() {
		global $wpdb;
		return $wpdb;
	}
}

// Inclure TemplateDefaults si pas déjà chargé.
if ( ! class_exists( 'PDFIB\TemplateDefaults' ) ) {
	// Chemin absolu vers TemplateDefaults.php.
	$pdfib_template_defaults_path = dirname( __DIR__, 2 )
		. '/src/Core/TemplateDefaults.php';
	if ( file_exists( $pdfib_template_defaults_path ) ) {
		include_once $pdfib_template_defaults_path;
	} elseif ( defined( 'PDFIB_PLUGIN_DIR' ) ) {
		// Fallback: essayer avec plugin_dir_path si disponible.
		$pdfib_template_defaults_path = PDFIB_PLUGIN_DIR
			. 'src/Core/TemplateDefaults.php';
		if ( file_exists( $pdfib_template_defaults_path ) ) {
			include_once $pdfib_template_defaults_path;
		}
	}
}

// Nonce aligné avec les handlers AJAX des templates (action: pdfib_ajax).
$pdfib_templates_nonce = wp_create_nonce( 'pdfib_ajax' );

if ( ! function_exists( 'pdfib_apply_canvas_setting_type' ) ) {
	/**
	 * Filtre et convertit les valeurs d'un tableau de paramètres canvas.
	 *
	 * @param array  $parsed Valeurs à traiter.
	 * @param string $type   Type de conversion ('int' ou 'string').
	 *
	 * @return array Tableau filtré et converti.
	 */
	function pdfib_apply_canvas_setting_type( array $parsed, string $type ): array {
		$parsed = array_values(
			array_filter(
				$parsed,
				static function ( $v ) {
					return '' !== $v && null !== $v;
				}
			)
		);
		if ( 'int' === $type ) {
			return array_values( array_unique( array_map( 'intval', $parsed ) ) );
		}
		return array_values( array_unique( array_map( 'strval', $parsed ) ) );
	}
}
if ( ! function_exists( 'pdfib_parse_canvas_setting' ) ) {
	/**
	 * Lit et analyse un paramètre de configuration canvas.
	 *
	 * @param string $setting_key   Clé du paramètre.
	 * @param mixed  $default_value Valeur par défaut.
	 * @param string $type         Type attendu ('string' ou 'int').
	 *
	 * @return array Valeurs analysées.
	 */
	function pdfib_parse_canvas_setting(
		string $setting_key,
		mixed $default_value,
		string $type = 'string'
	): array {
		$settings          = pdfib_get_option( 'pdfib_settings', array() );
		$canvas_settings   = pdfib_get_option(
			'pdfib_canvas_settings',
			array()
		);
		$canvas_key        = strpos( $setting_key, 'pdfib_' ) === 0
			? substr( $setting_key, 6 )
			: $setting_key;
		$legacy_canvas_key = strpos( $canvas_key, 'canvas_' ) === 0
			? substr( $canvas_key, 7 )
			: $canvas_key;
		if ( isset( $canvas_settings[ $canvas_key ] ) ) {
			$val = $canvas_settings[ $canvas_key ];
		} elseif ( isset( $canvas_settings[ $legacy_canvas_key ] ) ) {
			$val = $canvas_settings[ $legacy_canvas_key ];
		} elseif ( isset( $settings[ $setting_key ] ) ) {
			$val = $settings[ $setting_key ];
		} else {
			$legacy = get_option( $setting_key, null );
			$val    = null !== $legacy ? $legacy : $default_value;
		}
		if ( is_string( $val ) && strpos( $val, ',' ) !== false ) {
			$parsed = array_map( 'trim', explode( ',', $val ) );
		} elseif ( is_array( $val ) ) {
			$parsed = $val;
		} else {
			$parsed = array( $val );
		}
		return pdfib_apply_canvas_setting_type( $parsed, $type );
	}
}

// Vérifier le statut Premium via le plugin PRO.
$pdfib_license_manager = apply_filters( 'pdfib_license_manager_instance', null );
$pdfib_is_premium      = is_object( $pdfib_license_manager )
	&& method_exists( $pdfib_license_manager, 'is_premium' )
	&& $pdfib_license_manager->is_premium();

// Compter les templates utilisateur.
$pdfib_table_templates = \pdfib_db()->prefix . 'pdfib_templates';

$pdfib_template_columns = \pdfib_db()->get_col(
	\pdfib_db()->prepare( 'DESCRIBE %i', $pdfib_table_templates ),
	0
);
$pdfib_template_columns = is_array( $pdfib_template_columns )
	? array_map( 'strval', $pdfib_template_columns ) : array();

$pdfib_template_schema = array(
	'id'            => in_array( 'id', $pdfib_template_columns, true )
		? 'id' : 'template_id',
	'name'          => in_array( 'name', $pdfib_template_columns, true )
		? 'name' : 'template_name',
	'created_at'    => in_array( 'created_at', $pdfib_template_columns, true )
		? 'created_at' : 'template_created',
	'updated_at'    => in_array( 'updated_at', $pdfib_template_columns, true )
		? 'updated_at' : 'template_modified',
	'thumbnail'     => in_array( 'thumbnail_url', $pdfib_template_columns, true )
		? 'thumbnail_url' : '',
	'user_id'       => in_array( 'user_id', $pdfib_template_columns, true )
		? 'user_id' : '',
	'is_default'    => in_array( 'is_default', $pdfib_template_columns, true )
		? 'is_default' : '',
	'template_data' => in_array( 'template_data', $pdfib_template_columns, true )
		? 'template_data' : '',
);

$pdfib_count_sql = \pdfib_db()->prepare(
	'SELECT COUNT(*) FROM %i',
	$pdfib_table_templates
);
if ( '' !== $pdfib_template_schema['user_id'] ) {
	$pdfib_count_sql = \pdfib_db()->prepare(
		'SELECT COUNT(*) FROM %i WHERE %i = %d',
		$pdfib_table_templates,
		$pdfib_template_schema['user_id'],
		get_current_user_id()
	);
}

$pdfib_templates_count = (int) \pdfib_db()->get_var( $pdfib_count_sql );

// Créer templates par défaut si aucun template et utilisateur gratuit.
if ( 0 === $pdfib_templates_count && ! $pdfib_is_premium ) {
	\PDFIB\TemplateDefaults::create_default_templates_for_user( get_current_user_id() );
	if ( '' !== $pdfib_template_schema['user_id'] ) {
		$pdfib_count_sql = \pdfib_db()->prepare(
			'SELECT COUNT(*) FROM %i WHERE %i = %d',
			$pdfib_table_templates,
			$pdfib_template_schema['user_id'],
			get_current_user_id()
		);
	} else {
		$pdfib_count_sql = \pdfib_db()->prepare(
			'SELECT COUNT(*) FROM %i',
			$pdfib_table_templates
		);
	}

	$pdfib_templates_count = (int) \pdfib_db()->get_var( $pdfib_count_sql );
}

// Récupérer les DPI disponibles depuis les paramètres canvas.
$pdfib_available_dpis = pdfib_parse_canvas_setting(
	'pdfib_canvas_dpi',
	'72,96,150',
	'int'
);

// Récupérer les formats disponibles depuis les paramètres canvas.
$pdfib_available_formats = pdfib_parse_canvas_setting(
	'pdfib_canvas_formats',
	'A4',
	'string'
);

// Récupérer les orientations disponibles depuis les paramètres canvas.
$pdfib_available_orientations = pdfib_parse_canvas_setting(
	'pdfib_canvas_orientations',
	'portrait,landscape',
	'string'
);

// Définir les options DPI avec leurs labels.
$pdfib_dpi_options = array(
	72  => __( '72 DPI - Écran (faible qualité)', 'advanced-pdf-invoice-builder' ),
	96  => __( '96 DPI - Web (qualité standard)', 'advanced-pdf-invoice-builder' ),
	150 => __( '150 DPI - Impression moyenne', 'advanced-pdf-invoice-builder' ),
	300 => __( '300 DPI - Haute qualité', 'advanced-pdf-invoice-builder' ),
	600 => __( '600 DPI - Professionnel', 'advanced-pdf-invoice-builder' ),
);

// Définir les options de format avec leurs labels.
$pdfib_format_options = array(
	'A4'     => __( '📄 A4 (210×297mm)', 'advanced-pdf-invoice-builder' ),
	'A3'     => __( '📃 A3 (297×420mm)', 'advanced-pdf-invoice-builder' ),
	'Letter' => __( '🇺🇸 Letter (8.5×11")', 'advanced-pdf-invoice-builder' ),
	'Legal'  => __( '⚖️ Legal (8.5×14")', 'advanced-pdf-invoice-builder' ),
	'Label'  => __( '📦 Étiquette Colis (100×150mm)', 'advanced-pdf-invoice-builder' ),
);

// Définir les options d'orientation avec leurs labels.
	$pdfib_orientation_options = array(
		'portrait'  => __( '📱 Portrait (Vertical)', 'advanced-pdf-invoice-builder' ),
		'landscape' => __( '🖥️ Paysage (Horizontal)', 'advanced-pdf-invoice-builder' ),
	);
	?>

<!-- ✅ FIX: Localiser le nonce immédiatement pour le JavaScript inline -->

<?php
$pdfib_script = ( static function () use (
	$pdfib_templates_nonce,
	$pdfib_available_dpis,
	$pdfib_dpi_options,
	$pdfib_available_formats,
	$pdfib_format_options,
	$pdfib_available_orientations,
	$pdfib_orientation_options
): string {
	$json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
	ob_start();
	?>
	var pdfBuilderTemplatesNonce = '<?php echo esc_js( $pdfib_templates_nonce ); ?>';
	var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	var pdfBuilderAjax = {
	nonce: '<?php echo esc_js( wp_create_nonce( 'pdfib_ajax' ) ); ?>',
	};

	// Variables pour les DPI disponibles
	var availableDpis = <?php echo wp_json_encode( $pdfib_available_dpis, $json_flags ); ?>;
	var dpiOptions = <?php echo wp_json_encode( $pdfib_dpi_options, $json_flags ); ?>;

	// Variables pour les formats disponibles
	var availableFormats = <?php echo wp_json_encode( $pdfib_available_formats, $json_flags ); ?>;
	var formatOptions = <?php echo wp_json_encode( $pdfib_format_options, $json_flags ); ?>;

	// Variables pour les orientations disponibles
	var availableOrientations = <?php echo wp_json_encode( $pdfib_available_orientations, $json_flags ); ?>;
	var orientationOptions = <?php echo wp_json_encode( $pdfib_orientation_options, $json_flags ); ?>;
	<?php
	$pdfib_ob_result = ob_get_clean();
	return false !== $pdfib_ob_result ? $pdfib_ob_result : '';
} )();
wp_print_inline_script_tag( $pdfib_script );
?>

<div class="wrap">
	<div class="pdfb-templates-page">

		<!-- Header avec actions -->
		<div class="pdfb-templates-header">
			<div class="pdfb-templates-header-left">
				<h1>
				<?php
				esc_html_e( 'Templates PDF', 'advanced-pdf-invoice-builder' );
				?>
				</h1>
				<p>
				<?php
				esc_html_e(
					'Créez et gérez vos modèles de documents PDF professionnels',
					'advanced-pdf-invoice-builder'
				);
				?>
				</p>
			</div>
			<div class="pdfb-templates-header-right">
				<?php do_action( 'pdfib_render_templates_page_header_actions' ); ?>
			</div>
		</div>



		<!-- Barre de filtres -->
		<div class="pdfb-templates-filters">
			<strong>Filtrer par type</strong>
			<button
				class="pdfb-filter-btn pdfb-active"
				data-filter="all">📄 Tous</button>
			<button class="pdfb-filter-btn" data-filter="facture">🧾 Factures</button>
			<button class="pdfb-filter-btn" data-filter="devis">📋 Devis</button>
			<button
				class="pdfb-filter-btn"
				data-filter="commande">📦 Commandes</button>
			<button class="pdfb-filter-btn" data-filter="contrat">📑 Contrats</button>
			<button
				class="pdfb-filter-btn"
				data-filter="newsletter">📰 Newsletters</button>
			<button class="pdfb-filter-btn" data-filter="autre">📄 Autres</button>
		</div>

		<div id="templates-list" class="pdfb-templates-list">

			<!-- Section Templates Utilisateur -->
			<h3
				style="margin: 30px 0 15px 0; color: #23282d;
					border-bottom: 2px solid #28a745; padding-bottom: 10px;">
				📝 Mes Templates Personnalisés
			</h3>
			<p style="color: #666; margin-bottom: 20px;">
				Gérez vos templates personnalisés créés et modifiés.
			</p>

			<?php
			// Récupérer tous les templates compatibles schéma legacy / moderne.
			$pdfib_table_templates = \pdfib_db()->prefix . 'pdfib_templates';

			$pdfib_select_fields = array(
				"{$pdfib_template_schema['id']} AS id",
				"{$pdfib_template_schema['name']} AS name",
				"{$pdfib_template_schema['created_at']} AS created_at",
				"{$pdfib_template_schema['updated_at']} AS updated_at",
				'' !== $pdfib_template_schema['template_data']
					? "{$pdfib_template_schema['template_data']} AS template_data"
					: "'' AS template_data",
				'' !== $pdfib_template_schema['thumbnail']
					? "{$pdfib_template_schema['thumbnail']} AS thumbnail_url"
					: "'' AS thumbnail_url",
				'' !== $pdfib_template_schema['is_default']
					? "{$pdfib_template_schema['is_default']} AS is_default"
					: '0 AS is_default',
			);

			$pdfib_select_str = implode( ', ', $pdfib_select_fields );
			$pdfib_query      = 'SELECT ' . $pdfib_select_str . ' FROM %i';
			if ( '' !== $pdfib_template_schema['user_id'] ) {
				$pdfib_query = \pdfib_db()->prepare(
					$pdfib_query . ' WHERE %i = %d ORDER BY %i',
					$pdfib_table_templates,
					$pdfib_template_schema['user_id'],
					get_current_user_id(),
					$pdfib_template_schema['id']
				);
			} else {
				$pdfib_query = \pdfib_db()->prepare(
					$pdfib_query . ' ORDER BY %i',
					$pdfib_table_templates,
					$pdfib_template_schema['id']
				);
			}

			$pdfib_templates = \pdfib_db()->get_results( $pdfib_query, ARRAY_A );

			if ( ! empty( $pdfib_templates ) ) {
				echo '<div style="display: grid;'
					. ' grid-template-columns:'
					. ' repeat(auto-fill, minmax(300px, 1fr));'
					. ' gap: 20px; margin-top: 20px;">';

				$pdfib_template_counter = 0;
				foreach ( $pdfib_templates as $pdfib_template ) {
					++$pdfib_template_counter;
					$pdfib_template_id   = $pdfib_template['id'];
					$pdfib_template_name = $pdfib_template['name'];
					$pdfib_thumbnail_url = isset( $pdfib_template['thumbnail_url'] )
						? $pdfib_template['thumbnail_url']
						: '';

					// Nettoyer la thumbnail_url (rejeter les URLs invalides).
					$pdfib_bad_thumb = ! empty( $pdfib_thumbnail_url )
						&& (
							strpos( $pdfib_thumbnail_url, '0.0.0.1' ) !== false
							|| strpos( $pdfib_thumbnail_url, 'localhost' ) !== false
							|| strpos( $pdfib_thumbnail_url, '127.0.0.1' ) !== false
							|| strlen( trim( $pdfib_thumbnail_url ) ) === 0
						);
					if ( $pdfib_bad_thumb ) {
						$pdfib_thumbnail_url = '';
					}

					$pdfib_created_at = isset( $pdfib_template['created_at'] )
						? $pdfib_template['created_at']
						: null;
					$pdfib_updated_at = isset( $pdfib_template['updated_at'] )
						? $pdfib_template['updated_at']
						: null;
					$pdfib_is_default = isset( $pdfib_template['is_default'] )
						? (bool) $pdfib_template['is_default']
						: false;

					// Extraire les données du template.
					$pdfib_template_data     = json_decode(
						$pdfib_template['template_data'] ?? '{}',
						true
					);
					$pdfib_tpl_cat           = $pdfib_template_data['category'] ?? 'autre';
					$pdfib_template_category = $pdfib_tpl_cat;

					// Utiliser la catégorie stockée pour déterminer le type.
					$pdfib_template_type = $pdfib_template_category;

					$pdfib_button_text   = '⚙️ ' . __( 'Paramètres', 'advanced-pdf-invoice-builder' );
					$pdfib_button_action = 'openTemplateSettings';

					// Catégorie du template depuis les données.
					$pdfib_icon              = '📄'; // Default.
					$pdfib_description       = __( 'Template personnalisé', 'advanced-pdf-invoice-builder' );
					$pdfib_features          = array(
						'✓ ' . __( 'Contenu personnalisable', 'advanced-pdf-invoice-builder' ),
						'✓ ' . __( 'Mise en page flexible', 'advanced-pdf-invoice-builder' ),
						'✓ ' . __( 'Éléments dynamiques', 'advanced-pdf-invoice-builder' ),
						'✓ ' . __( 'Export PDF', 'advanced-pdf-invoice-builder' ),
					);
					$pdfib_desc_professional = __( 'Template professionnel et élégant', 'advanced-pdf-invoice-builder' );

					if ( 'facture' === $pdfib_template_type ) {
						$pdfib_icon        = '🧾';
						$pdfib_description = $pdfib_desc_professional;
						$pdfib_features    = array(
							'✓ ' . __( 'En-tête société', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Informations client', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Tableau des articles', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Totaux & TVA', 'advanced-pdf-invoice-builder' ),
						);
					} elseif ( 'devis' === $pdfib_template_type ) {
						$pdfib_icon        = '📋';
						$pdfib_description = $pdfib_desc_professional;
						$pdfib_features    = array(
							'✓ ' . __( 'Présentation entreprise', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Détails du projet', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Conditions & validité', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Signature numérique', 'advanced-pdf-invoice-builder' ),
						);
					} elseif ( 'commande' === $pdfib_template_type ) {
						$pdfib_icon        = '📦';
						$pdfib_description = $pdfib_desc_professional;
						$pdfib_features    = array(
							'✓ ' . __( 'Numéro de commande', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Liste des produits', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Modalités de paiement', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Conditions générales', 'advanced-pdf-invoice-builder' ),
						);
					} elseif ( 'contrat' === $pdfib_template_type ) {
						$pdfib_icon        = '📑';
						$pdfib_description = $pdfib_desc_professional;
						$pdfib_features    = array(
							'✓ ' . __( 'Parties contractantes', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Objet du contrat', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Conditions & obligations', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Clauses légales', 'advanced-pdf-invoice-builder' ),
						);
					} elseif ( 'newsletter' === $pdfib_template_type ) {
						$pdfib_icon        = '📰';
						$pdfib_description = $pdfib_desc_professional;
						$pdfib_features    = array(
							'✓ ' . __( 'En-tête accrocheur', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( "Sections d'articles", 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Call-to-action', 'advanced-pdf-invoice-builder' ),
							'✓ ' . __( 'Pied de page', 'advanced-pdf-invoice-builder' ),
						);
					}

					$pdfib_card_classes = 'pdfb-template-card'
						. ' template-type-' . $pdfib_template_type;

					echo '<div class="' . esc_attr( $pdfib_card_classes ) . '">';

					// Conteneur pour organiser le contenu de la carte.
					echo '<div class="pdfb-template-card-content">';

					// Badge du type de template en haut à gauche.

					$pdfib_dflt_btn_class = 'default-template-icon'
						. ' pdfb-default-template-icon';
					printf(
						'<button type="button" class="%s" style="opacity: %s;"'
						. ' onclick="toggleDefaultTemplate(%d, \'%s\', \'%s\')"'
						. ' title="%s" aria-label="%s">',
						esc_attr( $pdfib_dflt_btn_class ),
						esc_attr( $pdfib_is_default ? '1' : '0.5' ),
						intval( $pdfib_template_id ),
						esc_js( $pdfib_template_type ),
						esc_js( $pdfib_template_name ),
						esc_attr(
							$pdfib_is_default
								? 'Template par défaut'
								: 'Définir comme template par défaut'
						),
						esc_attr(
							$pdfib_is_default
								? 'Template par défaut'
								: 'Définir comme template par défaut'
						)
					);
					echo $pdfib_is_default ? esc_html( '⭐' ) : esc_html( '☆' );
					echo '</button>';
					// Badge du type de template en haut à gauche.
					$pdfib_type_colors = array(
						'facture'    => '#007cba',
						'devis'      => '#28a745',
						'commande'   => '#ffc107',
						'contrat'    => '#dc3545',
						'newsletter' => '#6f42c1',
						'autre'      => '#6c757d',
					);
					$pdfib_type_color  = isset(
						$pdfib_type_colors[ $pdfib_template_type ]
					)
						? $pdfib_type_colors[ $pdfib_template_type ]
						: $pdfib_type_colors['autre'];
					$pdfib_type_labels = array(
						'facture'    => 'Facture',
						'devis'      => 'Devis',
						'commande'   => 'Commande',
						'contrat'    => 'Contrat',
						'newsletter' => 'Newsletter',
						'autre'      => 'Autre',
					);
					$pdfib_type_label  = isset(
						$pdfib_type_labels[ $pdfib_template_type ]
					)
						? $pdfib_type_labels[ $pdfib_template_type ]
						: $pdfib_type_labels['autre'];

					printf(
						'<div class="pdfb-template-type-badge %s">%s</div>',
						esc_attr( $pdfib_template_type ),
						esc_html( $pdfib_type_label )
					);

					echo '<div class="pdfb-template-card-top">';
					printf(
						'<div id="preview-%d"'
						. ' class="pdfb-template-preview-container"'
						. ' data-template-id="%d">',
						intval( $pdfib_template_id ),
						intval( $pdfib_template_id )
					);
					echo '<div class="pdfb-template-card-preview-wrap">';
					echo '<div class="pdfb-template-card-preview-icon">📄</div>';
					echo '<div class="pdfb-template-card-preview-label">'
						. esc_html__( 'Aperçu', 'advanced-pdf-invoice-builder' ) . '</div>';
					echo '</div>';
					echo '</div>';
					echo '<h3 class="pdfb-template-card-title">'
						. esc_html( $pdfib_template_name ) . '</h3>';
					echo '<p class="pdfb-template-card-description">'
						. esc_html( $pdfib_description ) . '</p>';
					echo '</div>';
					echo '<div class="pdfb-template-card-info">';
					foreach ( $pdfib_features as $pdfib_feature ) {
						echo '<div>' . esc_html( $pdfib_feature ) . '</div>';
					}
					echo '</div>';
					echo '<div class="pdfb-template-card-action-row'
						. ' pdfb-template-actions">';
					do_action(
						'pdfib_render_templates_card_editor_action',
						$pdfib_template_id,
						$pdfib_template_name,
						$pdfib_template
					);
					if ( ! has_action( 'pdfib_render_templates_card_editor_action' ) ) {
						$pdfib_editor_url = admin_url( 'admin.php?page=pdf-builder-react-editor&template_id=' . $pdfib_template_id );
						echo '<a href="' . esc_url( $pdfib_editor_url ) . '" class="button button-secondary" title="'
							. esc_attr__( 'Éditer ce template', 'advanced-pdf-invoice-builder' )
							. '">✏️</a>';
					}
					printf(
						'<button class="button button-secondary"'
						. ' onclick="%s(%d, \'%s\')"'
						. ' title="%s">⚙️</button>',
						esc_js( $pdfib_button_action ),
						intval( $pdfib_template_id ),
						esc_js( $pdfib_template_name ),
						esc_attr( 'Paramètres' )
					);
					// Bouton duplication.
					echo '<button class="button button-primary"'
						. ' disabled'
						. ' title="'
						. esc_attr__( 'Duplication non disponible', 'advanced-pdf-invoice-builder' )
						. '">📋</button>';
					printf(
						'<button class="button button-danger"'
						. ' onclick="handleDeleteClick(%d, \'%s\')"'
						. ' title="%s">🗑️</button>',
						intval( $pdfib_template_id ),
						esc_js( $pdfib_template_name ),
						esc_attr( 'Supprimer' )
					);
					echo '</div>';
					echo '</div>';
					echo '</div>';
				}

				echo '</div>';
			} else {
					echo '<div class="notice notice-info inline"><p>'
						. esc_html__(
							'Aucun template personnalisé trouvé pour le moment.',
							'advanced-pdf-invoice-builder'
						) . '</p></div>';
			}
			?>
		</div>

		<?php do_action( 'pdfib_render_templates_gallery_modal' ); ?>

		<div id="no-templates" class="pdfb-no-templates-placeholder">
			<div class="pdfb-no-templates-placeholder-icon">📄</div>
			<h3 style="color: var(--pdf-text);">
			<?php
			esc_html_e( 'Aucun template trouvé', 'advanced-pdf-invoice-builder' );
			?>
			</h3>
			<p>
			<?php
			esc_html_e(
				'Créez votre premier template pour commencer à concevoir des PDF personnalisés.',
				'advanced-pdf-invoice-builder'
			);
			?>
			</p>
		</div>
	</div>
</div>

<!-- JavaScript déplacé vers settings-main.php (pas de conflits nav) -->

<!-- Modaux d'action injectés dynamiquement -->

<!-- Modal des paramètres du template (dynamique - créée par JavaScript) -->
<div
	id="template-settings-modal"
	class="pdfb-template-modal-overlay"
	style="display: none; position: fixed; top: 0; left: 0; width: 100%;
		height: 100%; background: rgba(0,0,0,0.7); z-index: 10000;
		align-items: center; justify-content: center;">
</div>
