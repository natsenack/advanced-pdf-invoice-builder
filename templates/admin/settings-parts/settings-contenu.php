<?php
/**
 * Advanced PDF Invoice Builder
 *
 * Content settings tab template.
 *
 * PHP version 8.2
 *
 * @category Plugin
 * @package  PDFIB
 * @author   PDF Invoice Builder <support@threeaxe.fr>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://threeaxe.fr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

if ( ! function_exists( 'pdfib_get_canvas_option_contenu' ) ) {
	/**
	 * Read a canvas option from grouped settings.
	 *
	 * @param string     $key      Field suffix.
	 * @param string|int $fallback Default value.
	 *
	 * @return mixed
	 */
	function pdfib_get_canvas_option_contenu(
		string $key,
		string|int $fallback = ''
	): mixed {
		$pdfib_option_key = 'pdfib_' . $key;
		$pdfib_settings   = pdfib_get_option( 'pdfib_settings', array() );

		if ( isset( $pdfib_settings[ $pdfib_option_key ] ) ) {
			return $pdfib_settings[ $pdfib_option_key ];
		}

		return $fallback;
	}
}

$pdfib_canvas_defaults = array(
	'pdfib_canvas_width'              => '794',
	'pdfib_canvas_height'             => '1123',
	'pdfib_canvas_dpi'                => '96',
	'pdfib_canvas_format'             => 'A4',
	'pdfib_canvas_formats'            => 'A4',
	'pdfib_canvas_orientation'        => 'portrait',
	'pdfib_canvas_orientations'       => 'portrait,landscape',
	'pdfib_canvas_unit'               => 'px',
	'pdfib_canvas_bg_color'           => '#ffffff',
	'pdfib_canvas_border_color'       => '#cccccc',
	'pdfib_canvas_border_width'       => '1',
	'pdfib_canvas_container_bg_color' => '#f8f9fa',
	'pdfib_canvas_shadow_enabled'     => '0',
	'pdfib_canvas_grid_enabled'       => '0',
	'pdfib_canvas_grid_size'          => '10',
	'pdfib_canvas_guides_enabled'     => '0',
	'pdfib_canvas_snap_to_grid'       => '0',
	'pdfib_canvas_zoom_min'           => '10',
	'pdfib_canvas_zoom_max'           => '500',
	'pdfib_canvas_zoom_default'       => '100',
	'pdfib_canvas_zoom_step'          => '25',
	'pdfib_canvas_export_quality'     => 'print',
	'pdfib_canvas_export_format'      => 'pdf',
	'pdfib_canvas_export_transparent' => '0',
	'pdfib_canvas_drag_enabled'       => '1',
	'pdfib_canvas_resize_enabled'     => '1',
	'pdfib_canvas_rotate_enabled'     => '0',
	'pdfib_canvas_multi_select'       => '1',
	'pdfib_canvas_selection_mode'     => 'single',
	'pdfib_canvas_keyboard_shortcuts' => '1',
	'pdfib_canvas_allow_portrait'     => '1',
	'pdfib_canvas_allow_landscape'    => '1',
	'pdfib_canvas_margin_top'         => '28',
	'pdfib_canvas_margin_right'       => '28',
	'pdfib_canvas_margin_bottom'      => '10',
	'pdfib_canvas_margin_left'        => '10',
	'pdfib_canvas_show_margins'       => '0',
);

$pdfib_hidden_fields = array();

foreach ( $pdfib_canvas_defaults as $pdfib_field => $pdfib_default ) {
	$pdfib_hidden_fields[ $pdfib_field ] = (string) pdfib_get_canvas_option_contenu(
		substr( $pdfib_field, 6 ),
		$pdfib_default
	);
}

$pdfib_width          = (int) $pdfib_hidden_fields['pdfib_canvas_width'];
$pdfib_height         = (int) $pdfib_hidden_fields['pdfib_canvas_height'];
$pdfib_dpi            = max( 1, (int) $pdfib_hidden_fields['pdfib_canvas_dpi'] );
$pdfib_format         = (string) $pdfib_hidden_fields['pdfib_canvas_format'];
$pdfib_orientation    = (string) $pdfib_hidden_fields['pdfib_canvas_orientation'];
$pdfib_grid_enabled   = ( '1' === $pdfib_hidden_fields['pdfib_canvas_grid_enabled'] );
$pdfib_guides_enabled = ( '1' === $pdfib_hidden_fields['pdfib_canvas_guides_enabled'] );
$pdfib_multi_select   = ( '1' === $pdfib_hidden_fields['pdfib_canvas_multi_select'] );

$pdfib_available_orientations = array();

if ( '1' === $pdfib_hidden_fields['pdfib_canvas_allow_portrait'] ) {
	$pdfib_available_orientations[] = 'portrait';
}

if ( '1' === $pdfib_hidden_fields['pdfib_canvas_allow_landscape'] ) {
	$pdfib_available_orientations[] = 'landscape';
}

// translators comments moved inside sprintf() calls below.
$pdfib_display_summary = sprintf(
	/* translators: 1: canvas width in px, 2: canvas height in px, 3: DPI value. */
	__( '%1$spx x %2$spx a %3$s DPI.', 'advanced-pdf-invoice-builder' ),
	(string) $pdfib_width,
	(string) $pdfib_height,
	(string) $pdfib_dpi
);
$pdfib_display_format_summary = sprintf(
	/* translators: 1: paper format (e.g. A4), 2: orientation (portrait/landscape). */
	__( 'Format %1$s, orientation %2$s.', 'advanced-pdf-invoice-builder' ),
	$pdfib_format,
	$pdfib_orientation
);
$pdfib_grid_state   = $pdfib_grid_enabled
	? __( 'actifs', 'advanced-pdf-invoice-builder' )
	: __( 'inactifs', 'advanced-pdf-invoice-builder' );
$pdfib_guides_state = $pdfib_guides_enabled
	? __( 'actifs', 'advanced-pdf-invoice-builder' )
	: __( 'inactifs', 'advanced-pdf-invoice-builder' );
// translators: 1: grid state (actifs/inactifs), 2: guides state (actifs/inactifs).
$pdfib_navigation_summary = sprintf(
	/* translators: 1: grid state (actifs/inactifs), 2: guides state (actifs/inactifs). */
	__( 'Grille %1$s, guides %2$s.', 'advanced-pdf-invoice-builder' ),
	$pdfib_grid_state,
	$pdfib_guides_state
);
// translators: 1: minimum zoom level, 2: maximum zoom level.
$pdfib_zoom_summary = sprintf(
	/* translators: 1: minimum zoom level, 2: maximum zoom level. */
	__( 'Zoom de %1$s%% a %2$s%%.', 'advanced-pdf-invoice-builder' ),
	$pdfib_hidden_fields['pdfib_canvas_zoom_min'],
	$pdfib_hidden_fields['pdfib_canvas_zoom_max']
);
$pdfib_selection_state = $pdfib_multi_select
	? __( 'active', 'advanced-pdf-invoice-builder' )
	: __( 'inactive', 'advanced-pdf-invoice-builder' );
// translators: 1: selection state (active/inactive), 2: selection mode.
$pdfib_behavior_summary = sprintf(
	/* translators: 1: selection state (active/inactive), 2: selection mode. */
	__(
		'Selection multiple %1$s, mode %2$s.',
		'advanced-pdf-invoice-builder'
	),
	$pdfib_selection_state,
	$pdfib_hidden_fields['pdfib_canvas_selection_mode']
);
// translators: 1: export format, 2: export quality.
$pdfib_export_summary = sprintf(
	/* translators: 1: export format, 2: export quality. */
	__( 'Export %1$s, qualite %2$s.', 'advanced-pdf-invoice-builder' ),
	$pdfib_hidden_fields['pdfib_canvas_export_format'],
	$pdfib_hidden_fields['pdfib_canvas_export_quality']
);

$pdfib_canvas_js_config = array(
	'default_canvas_width'       => $pdfib_width,
	'default_canvas_height'      => $pdfib_height,
	'default_canvas_dpi'         => $pdfib_dpi,
	'default_canvas_format'      => $pdfib_format,
	'default_canvas_unit'        => $pdfib_hidden_fields['pdfib_canvas_unit'],
	'default_canvas_orientation' => $pdfib_orientation,
	'canvas_background_color'    => $pdfib_hidden_fields['pdfib_canvas_bg_color'],
	'border_color'               => $pdfib_hidden_fields['pdfib_canvas_border_color'],
	'border_width'               => $pdfib_hidden_fields['pdfib_canvas_border_width'],
	'container_background_color' => $pdfib_hidden_fields['pdfib_canvas_container_bg_color'],
	'shadow_enabled'             => ( '1' === $pdfib_hidden_fields['pdfib_canvas_shadow_enabled'] ),
	'margin_top'                 => (int) $pdfib_hidden_fields['pdfib_canvas_margin_top'],
	'margin_right'               => (int) $pdfib_hidden_fields['pdfib_canvas_margin_right'],
	'margin_bottom'              => (int) $pdfib_hidden_fields['pdfib_canvas_margin_bottom'],
	'margin_left'                => (int) $pdfib_hidden_fields['pdfib_canvas_margin_left'],
	'show_margins'               => ( '1' === $pdfib_hidden_fields['pdfib_canvas_show_margins'] ),
	'show_grid'                  => $pdfib_grid_enabled,
	'grid_size'                  => (int) $pdfib_hidden_fields['pdfib_canvas_grid_size'],
	'show_guides'                => $pdfib_guides_enabled,
	'snap_to_grid'               => ( '1' === $pdfib_hidden_fields['pdfib_canvas_snap_to_grid'] ),
	'zoom_min'                   => (int) $pdfib_hidden_fields['pdfib_canvas_zoom_min'],
	'zoom_max'                   => (int) $pdfib_hidden_fields['pdfib_canvas_zoom_max'],
	'zoom_default'               => (int) $pdfib_hidden_fields['pdfib_canvas_zoom_default'],
	'zoom_step'                  => (int) $pdfib_hidden_fields['pdfib_canvas_zoom_step'],
	'export_quality'             => $pdfib_hidden_fields['pdfib_canvas_export_quality'],
	'export_format'              => $pdfib_hidden_fields['pdfib_canvas_export_format'],
	'export_transparent'         => ( '1' === $pdfib_hidden_fields['pdfib_canvas_export_transparent'] ),
	'drag_enabled'               => ( '1' === $pdfib_hidden_fields['pdfib_canvas_drag_enabled'] ),
	'resize_enabled'             => ( '1' === $pdfib_hidden_fields['pdfib_canvas_resize_enabled'] ),
	'rotate_enabled'             => ( '1' === $pdfib_hidden_fields['pdfib_canvas_rotate_enabled'] ),
	'multi_select'               => $pdfib_multi_select,
	'selection_mode'             => $pdfib_hidden_fields['pdfib_canvas_selection_mode'],
	'keyboard_shortcuts'         => ( '1' === $pdfib_hidden_fields['pdfib_canvas_keyboard_shortcuts'] ),
	'availableOrientations'      => $pdfib_available_orientations,
);
?>
<section id="contenu"
	class="settings-section contenu-settings"
	role="tabpanel"
	aria-labelledby="tab-contenu">
	<?php require_once __DIR__ . '/settings-modals.php'; ?>

	<div class="settings-content">
		<section class="contenu-canvas-section">
			<header class="pdfib-canvas-section-header">
				<h3>
					<?php
					esc_html_e(
						'Canvas et design',
						'advanced-pdf-invoice-builder'
					);
					?>
				</h3>
				<button type="button"
					id="reset-canvas-settings"
					class="button button-secondary">
					<?php
					esc_html_e(
						'Reinitialiser',
						'advanced-pdf-invoice-builder'
					);
					?>
				</button>
			</header>

			<p>
				<?php
				esc_html_e(
					'Configurez le rendu, la navigation et le comportement du canvas utilise par l editeur PDF.',
					'advanced-pdf-invoice-builder'
				);
				?>
			</p>

			<?php foreach ( $pdfib_hidden_fields as $pdfib_name => $pdfib_value ) : ?>
				<input type="hidden"
					name="pdfib_settings[<?php echo esc_attr( $pdfib_name ); ?>]"
					value="<?php echo esc_attr( $pdfib_value ); ?>">
			<?php endforeach; ?>

			<div class="pdfb-canvas-settings-grid">

				<!-- Carte Affichage -->
				<article class="pdfb-canvas-card" data-category="affichage">
					<header class="pdfb-canvas-card-header">
						<div class="pdfb-canvas-card-icon" aria-hidden="true">🖥️</div>
						<div class="pdfb-canvas-card-header-text">
							<h4><?php esc_html_e( 'Affichage', 'advanced-pdf-invoice-builder' ); ?></h4>
							<span class="pdfb-canvas-card-header-sub"><?php esc_html_e( 'Dimensions &amp; résolution', 'advanced-pdf-invoice-builder' ); ?></span>
						</div>
					</header>
					<div class="pdfb-canvas-card-content">
						<div class="pdfb-canvas-card-stats">
							<span class="pdfb-canvas-stat-badge">📐 <?php echo esc_html( $pdfib_width . '×' . $pdfib_height . 'px' ); ?></span>
							<span class="pdfb-canvas-stat-badge">🎯 <?php echo esc_html( $pdfib_dpi . ' DPI' ); ?></span>
							<span class="pdfb-canvas-stat-badge">📄 <?php echo esc_html( $pdfib_format . ' ' . $pdfib_orientation ); ?></span>
						</div>
						<p><?php echo esc_html( $pdfib_display_summary ); ?></p>
						<p><?php echo esc_html( $pdfib_display_format_summary ); ?></p>
					</div>
					<footer class="pdfb-canvas-card-actions">
						<button type="button" class="pdfb-canvas-configure-btn">
							<?php esc_html_e( 'Configurer', 'advanced-pdf-invoice-builder' ); ?>
						</button>
					</footer>
				</article>

				<!-- Carte Navigation -->
				<article class="pdfb-canvas-card" data-category="navigation">
					<header class="pdfb-canvas-card-header">
						<div class="pdfb-canvas-card-icon" aria-hidden="true">🧭</div>
						<div class="pdfb-canvas-card-header-text">
							<h4><?php esc_html_e( 'Navigation', 'advanced-pdf-invoice-builder' ); ?></h4>
							<span class="pdfb-canvas-card-header-sub"><?php esc_html_e( 'Zoom &amp; grille', 'advanced-pdf-invoice-builder' ); ?></span>
						</div>
					</header>
					<div class="pdfb-canvas-card-content">
						<div class="pdfb-canvas-card-stats">
							<span class="pdfb-canvas-stat-badge">🔍 <?php echo esc_html( $pdfib_hidden_fields['pdfib_canvas_zoom_min'] . '–' . $pdfib_hidden_fields['pdfib_canvas_zoom_max'] . '%' ); ?></span>
							<span class="pdfb-canvas-stat-badge">⚡ <?php echo esc_html( $pdfib_hidden_fields['pdfib_canvas_zoom_default'] . '% défaut' ); ?></span>
							<?php if ( $pdfib_grid_enabled ) : ?>
								<span class="pdfb-canvas-stat-badge pdfb-canvas-stat-badge--on">✅ Grille</span>
							<?php else : ?>
								<span class="pdfb-canvas-stat-badge">⬜ Grille off</span>
							<?php endif; ?>
						</div>
						<p><?php echo esc_html( $pdfib_navigation_summary ); ?></p>
						<p><?php echo esc_html( $pdfib_zoom_summary ); ?></p>
					</div>
					<footer class="pdfb-canvas-card-actions">
						<button type="button" class="pdfb-canvas-configure-btn">
							<?php esc_html_e( 'Configurer', 'advanced-pdf-invoice-builder' ); ?>
						</button>
					</footer>
				</article>

				<!-- Carte Comportement -->
				<article class="pdfb-canvas-card" data-category="comportement">
					<header class="pdfb-canvas-card-header">
						<div class="pdfb-canvas-card-icon" aria-hidden="true">⚙️</div>
						<div class="pdfb-canvas-card-header-text">
							<h4><?php esc_html_e( 'Comportement', 'advanced-pdf-invoice-builder' ); ?></h4>
							<span class="pdfb-canvas-card-header-sub"><?php esc_html_e( 'Interactions &amp; export', 'advanced-pdf-invoice-builder' ); ?></span>
						</div>
					</header>
					<div class="pdfb-canvas-card-content">
						<div class="pdfb-canvas-card-stats">
							<span class="pdfb-canvas-stat-badge">📤 <?php echo esc_html( strtoupper( $pdfib_hidden_fields['pdfib_canvas_export_format'] ) ); ?></span>
							<span class="pdfb-canvas-stat-badge">🖨️ <?php echo esc_html( ucfirst( $pdfib_hidden_fields['pdfib_canvas_export_quality'] ) ); ?></span>
							<?php if ( $pdfib_multi_select ) : ?>
								<span class="pdfb-canvas-stat-badge pdfb-canvas-stat-badge--on">✅ Multi-select</span>
							<?php endif; ?>
						</div>
						<p><?php echo esc_html( $pdfib_behavior_summary ); ?></p>
						<p><?php echo esc_html( $pdfib_export_summary ); ?></p>
					</div>
					<footer class="pdfb-canvas-card-actions">
						<button type="button" class="pdfb-canvas-configure-btn">
							<?php esc_html_e( 'Configurer', 'advanced-pdf-invoice-builder' ); ?>
						</button>
					</footer>
				</article>

			</div>
		</section>
	</div>
</section>
<?php
$pdfib_script = 'window.pdfBuilderCanvasSettings = '
	. wp_json_encode( $pdfib_canvas_js_config )
	. ';';

wp_print_inline_script_tag( $pdfib_script );

