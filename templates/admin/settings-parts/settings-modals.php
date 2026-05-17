<?php
/**
 * Advanced PDF Invoice Builder
 *
 * Canvas settings modals.
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

if ( ! function_exists( 'pdfib_get_canvas_modal_defaults' ) ) {
	/**
	 * Return canvas default values.
	 *
	 * @return array<string, string>
	 */
	function pdfib_get_canvas_modal_defaults(): array {
		return array(
			'width'              => '794',
			'height'             => '1123',
			'dpi'                => '96',
			'format'             => 'A4',
			'formats'            => 'A4',
			'orientation'        => 'portrait',
			'orientations'       => 'portrait,landscape',
			'allow_portrait'     => '1',
			'allow_landscape'    => '1',
			'bg_color'           => '#ffffff',
			'border_color'       => '#cccccc',
			'border_width'       => '1',
			'container_bg_color' => '#f8f9fa',
			'shadow_enabled'     => '0',
			'grid_enabled'       => '0',
			'grid_size'          => '10',
			'guides_enabled'     => '0',
			'snap_to_grid'       => '0',
			'zoom_min'           => '10',
			'zoom_max'           => '500',
			'zoom_default'       => '100',
			'zoom_step'          => '25',
			'margin_top'         => '28',
			'margin_right'       => '28',
			'margin_bottom'      => '10',
			'margin_left'        => '10',
			'show_margins'       => '0',
			'drag_enabled'       => '1',
			'resize_enabled'     => '1',
			'rotate_enabled'     => '0',
			'multi_select'       => '1',
			'selection_mode'     => 'single',
			'keyboard_shortcuts' => '1',
			'export_quality'     => 'print',
			'export_format'      => 'pdf',
			'export_transparent' => '0',
		);
	}
}

if ( ! function_exists( 'pdfib_validate_canvas_field_value' ) ) {
	/**
	 * Normalize canvas values.
	 *
	 * @param string     $key      Field key.
	 * @param mixed      $value    Raw value.
	 * @param string|int $fallback Default value.
	 *
	 * @return mixed
	 */
	function pdfib_validate_canvas_field_value(
		string $key,
		mixed $value,
		string|int $fallback
	): mixed {
		$pdfib_array_fields = array(
			'canvas_dpi',
			'canvas_formats',
			'canvas_orientations',
		);

		$pdfib_is_array_field        = in_array( $key, $pdfib_array_fields, true );
		$pdfib_is_empty_array_string = is_string( $value )
			&& ( '0' === $value || strpos( $value, '0,' ) === 0 );

		if ( $pdfib_is_array_field && $pdfib_is_empty_array_string ) {
			return $fallback;
		}

		return $value;
	}
}

if ( ! function_exists( 'pdfib_get_canvas_modal_value' ) ) {
	/**
	 * Get a canvas value from stored settings.
	 *
	 * @param string     $key      Field key.
	 * @param string|int $fallback Default value.
	 *
	 * @return mixed
	 */
	function pdfib_get_canvas_modal_value(
		string $key,
		string|int $fallback = ''
	): mixed {
		$pdfib_settings        = pdfib_get_option( 'pdfib_settings', array() );
		$pdfib_canvas_settings = pdfib_get_option(
			'pdfib_canvas_settings',
			array()
		);
		$pdfib_option_key      = 'pdfib_' . $key;
		$pdfib_value           = null;

		if ( isset( $pdfib_canvas_settings[ $key ] ) ) {
			$pdfib_value = $pdfib_canvas_settings[ $key ];
		}

		if ( null === $pdfib_value && isset( $pdfib_settings[ $pdfib_option_key ] ) ) {
			$pdfib_value = $pdfib_settings[ $pdfib_option_key ];
		}

		if ( null === $pdfib_value ) {
			$pdfib_value = $fallback;
		}

		return pdfib_validate_canvas_field_value( $key, $pdfib_value, $fallback );
	}
}

$pdfib_defaults = pdfib_get_canvas_modal_defaults();

$pdfib_multiple_label = __( 'Multiple', 'advanced-pdf-invoice-builder' );
$pdfib_group_label    = __( 'Groupe', 'advanced-pdf-invoice-builder' );

// phpcs:disable
$pdfib_modal_width           = (string) pdfib_get_canvas_modal_value(
	'canvas_width',
	$pdfib_defaults['width']
);
$pdfib_modal_height          = (string) pdfib_get_canvas_modal_value(
	'canvas_height',
	$pdfib_defaults['height']
);
$pdfib_modal_dpi             = (string) pdfib_get_canvas_modal_value(
	'canvas_dpi',
	$pdfib_defaults['dpi']
);
$pdfib_modal_format          = (string) pdfib_get_canvas_modal_value(
	'canvas_format',
	$pdfib_defaults['format']
);
$pdfib_modal_formats         = (string) pdfib_get_canvas_modal_value(
	'canvas_formats',
	$pdfib_defaults['formats']
);
$pdfib_modal_orientation     = (string) pdfib_get_canvas_modal_value(
	'canvas_orientation',
	$pdfib_defaults['orientation']
);
$pdfib_modal_orientations    = (string) pdfib_get_canvas_modal_value(
	'canvas_orientations',
	$pdfib_defaults['orientations']
);
$pdfib_modal_allow_portrait  = (string) pdfib_get_canvas_modal_value(
	'canvas_allow_portrait',
	$pdfib_defaults['allow_portrait']
);
$pdfib_modal_allow_landscape = (string) pdfib_get_canvas_modal_value(
	'canvas_allow_landscape',
	$pdfib_defaults['allow_landscape']
);
$pdfib_modal_bg_color        = (string) pdfib_get_canvas_modal_value(
	'canvas_bg_color',
	$pdfib_defaults['bg_color']
);
$pdfib_modal_border_color    = (string) pdfib_get_canvas_modal_value(
	'canvas_border_color',
	$pdfib_defaults['border_color']
);
$pdfib_modal_border_width    = (string) pdfib_get_canvas_modal_value(
	'canvas_border_width',
	$pdfib_defaults['border_width']
);
$pdfib_modal_container_color = (string) pdfib_get_canvas_modal_value(
	'canvas_container_bg_color',
	$pdfib_defaults['container_bg_color']
);
$pdfib_modal_shadow_enabled  = (string) pdfib_get_canvas_modal_value(
	'canvas_shadow_enabled',
	$pdfib_defaults['shadow_enabled']
);
$pdfib_modal_margin_top      = (string) pdfib_get_canvas_modal_value(
	'canvas_margin_top',
	$pdfib_defaults['margin_top']
);
$pdfib_modal_margin_right    = (string) pdfib_get_canvas_modal_value(
	'canvas_margin_right',
	$pdfib_defaults['margin_right']
);
$pdfib_modal_margin_bottom   = (string) pdfib_get_canvas_modal_value(
	'canvas_margin_bottom',
	$pdfib_defaults['margin_bottom']
);
$pdfib_modal_margin_left     = (string) pdfib_get_canvas_modal_value(
	'canvas_margin_left',
	$pdfib_defaults['margin_left']
);
$pdfib_modal_show_margins    = (string) pdfib_get_canvas_modal_value(
	'canvas_show_margins',
	$pdfib_defaults['show_margins']
);
$pdfib_modal_grid_enabled    = (string) pdfib_get_canvas_modal_value(
	'canvas_grid_enabled',
	$pdfib_defaults['grid_enabled']
);
$pdfib_modal_grid_size       = (string) pdfib_get_canvas_modal_value(
	'canvas_grid_size',
	$pdfib_defaults['grid_size']
);
$pdfib_modal_guides_enabled  = (string) pdfib_get_canvas_modal_value(
	'canvas_guides_enabled',
	$pdfib_defaults['guides_enabled']
);
$pdfib_modal_snap_to_grid    = (string) pdfib_get_canvas_modal_value(
	'canvas_snap_to_grid',
	$pdfib_defaults['snap_to_grid']
);
$pdfib_modal_zoom_min        = (string) pdfib_get_canvas_modal_value(
	'canvas_zoom_min',
	$pdfib_defaults['zoom_min']
);
$pdfib_modal_zoom_max        = (string) pdfib_get_canvas_modal_value(
	'canvas_zoom_max',
	$pdfib_defaults['zoom_max']
);
$pdfib_modal_zoom_default    = (string) pdfib_get_canvas_modal_value(
	'canvas_zoom_default',
	$pdfib_defaults['zoom_default']
);
$pdfib_modal_zoom_step       = (string) pdfib_get_canvas_modal_value(
	'canvas_zoom_step',
	$pdfib_defaults['zoom_step']
);
$pdfib_modal_drag_enabled    = (string) pdfib_get_canvas_modal_value(
	'canvas_drag_enabled',
	$pdfib_defaults['drag_enabled']
);
$pdfib_modal_resize_enabled  = (string) pdfib_get_canvas_modal_value(
	'canvas_resize_enabled',
	$pdfib_defaults['resize_enabled']
);
$pdfib_modal_rotate_enabled   = (string) pdfib_get_canvas_modal_value(
	'canvas_rotate_enabled',
	$pdfib_defaults['rotate_enabled']
);
$pdfib_modal_multi_select    = (string) pdfib_get_canvas_modal_value(
	'canvas_multi_select',
	$pdfib_defaults['multi_select']
);
$pdfib_modal_selection_mode  = (string) pdfib_get_canvas_modal_value(
	'canvas_selection_mode',
	$pdfib_defaults['selection_mode']
);
$pdfib_modal_keyboard_shortcuts = (string) pdfib_get_canvas_modal_value(
	'canvas_keyboard_shortcuts',
	$pdfib_defaults['keyboard_shortcuts']
);
$pdfib_modal_export_quality  = (string) pdfib_get_canvas_modal_value(
	'canvas_export_quality',
	$pdfib_defaults['export_quality']
);
$pdfib_modal_export_format   = (string) pdfib_get_canvas_modal_value(
	'canvas_export_format',
	$pdfib_defaults['export_format']
);
$pdfib_modal_export_transparent = (string) pdfib_get_canvas_modal_value(
	'canvas_export_transparent',
	$pdfib_defaults['export_transparent']
);

$pdfib_can_use_high_dpi = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'high_dpi' );

$pdfib_can_use_grid_navigation     = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'grid_navigation' );
$pdfib_can_use_advanced_selection  = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'advanced_selection' );
$pdfib_can_use_keyboard_shortcuts  = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'keyboard_shortcuts' );
$pdfib_can_use_multi_format_export = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'multi_format_export' );
$pdfib_can_use_custom_colors       = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'custom_colors' );
?>
<div id="canvas-affichage-modal-overlay"
	class="pdfb-canvas-modal-overlay"
	style="display: none;">
	<div class="pdfb-canvas-modal-container">
		<div class="pdfb-canvas-modal-header">
			<h3>
				<?php
				esc_html_e(
					'Parametres d affichage',
					'advanced-pdf-invoice-builder'
				);
				?>
			</h3>
			<button type="button" class="pdfb-canvas-modal-close">
				&times;
			</button>
		</div>
		<div class="pdfb-canvas-modal-body">
			<?php
			$pdfib_can_use_extended_formats = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'extended_formats' );

			$pdfib_hidden_format       = $pdfib_can_use_extended_formats ? $pdfib_modal_format : 'A4';
			$pdfib_hidden_formats      = $pdfib_can_use_extended_formats ? $pdfib_modal_formats : 'A4';
			$pdfib_hidden_orientation  = $pdfib_can_use_extended_formats ? $pdfib_modal_orientation : 'portrait';
			$pdfib_hidden_orientations = $pdfib_can_use_extended_formats ? $pdfib_modal_orientations : 'portrait';
			$pdfib_hidden_landscape    = $pdfib_can_use_extended_formats ? $pdfib_modal_allow_landscape : '0';
			?>
			<input type="hidden" name="pdfib_canvas_width"
				value="<?php echo esc_attr( $pdfib_modal_width ); ?>">
			<input type="hidden" name="pdfib_canvas_height"
				value="<?php echo esc_attr( $pdfib_modal_height ); ?>">
			<input type="hidden" name="pdfib_canvas_format"
				value="<?php echo esc_attr( $pdfib_hidden_format ); ?>">
			<input type="hidden" name="pdfib_canvas_formats"
				value="<?php echo esc_attr( $pdfib_hidden_formats ); ?>">
			<input type="hidden" name="pdfib_canvas_orientation"
				value="<?php echo esc_attr( $pdfib_hidden_orientation ); ?>">
			<input type="hidden" name="pdfib_canvas_orientations"
				value="<?php echo esc_attr( $pdfib_hidden_orientations ); ?>">
			<input type="hidden" name="pdfib_canvas_allow_portrait" value="1">
			<input type="hidden" name="pdfib_canvas_allow_landscape"
				value="<?php echo esc_attr( $pdfib_hidden_landscape ); ?>">

			<div class="pdfb-modal-settings-grid">
				<?php
				do_action(
					'pdfib_canvas_margins_premium_fields',
					array(
						'margin_top'    => $pdfib_modal_margin_top,
						'margin_right'  => $pdfib_modal_margin_right,
						'margin_bottom' => $pdfib_modal_margin_bottom,
						'margin_left'   => $pdfib_modal_margin_left,
						'show_margins'  => $pdfib_modal_show_margins,
					)
				);
				?>

				<?php
				do_action(
					'pdfib_canvas_custom_colors_premium_fields',
					array(
						'border_color'          => $pdfib_modal_border_color,
						'border_width'          => $pdfib_modal_border_width,
						'shadow_enabled'        => $pdfib_modal_shadow_enabled,
						'container_bg_color'    => $pdfib_modal_container_color,
						'can_use_custom_colors' => $pdfib_can_use_custom_colors,
					)
				);
				?>

				<!-- DPI -->
				<div class="pdfb-setting-group">
					<label>
						<?php esc_html_e( 'Resolution d\'export', 'advanced-pdf-invoice-builder' ); ?>
					</label>
					<div class="pdfb-pill-row">
						<?php
						$pdfib_dpi_opts      = array(
							'72'  => array(
								'label'   => '72 DPI',
								'sub'     => 'Web',
								'soon'    => false,
								'premium' => false,
							),
							'96'  => array(
								'label'   => '96 DPI',
								'sub'     => 'Standard',
								'soon'    => false,
								'premium' => false,
							),
							'150' => array(
								'label'   => '150 DPI',
								'sub'     => 'Qualite',
								'soon'    => false,
								'premium' => false,
							),
						);
						$pdfib_selected_dpis = array_filter( array_map( 'trim', explode( ',', $pdfib_modal_dpi ) ) );
						if ( empty( $pdfib_selected_dpis ) ) {
							$pdfib_selected_dpis = array( '96' );
						}
						foreach ( $pdfib_dpi_opts as $pdfib_dv => $pdfib_di ) :
							$pdfib_locked = $pdfib_di['soon'] || $pdfib_di['premium'];
							$pdfib_pcls   = $pdfib_di['soon'] ? ' pdfb-pill-option--soon' : ( $pdfib_di['premium'] ? ' pdfb-pill-option--locked' : '' );
							?>
						<label class="pdfb-pill-option<?php echo esc_attr( $pdfib_pcls ); ?>">
							<?php if ( ! $pdfib_locked ) : ?>
								<input type="checkbox" name="pdfib_canvas_dpi[]" value="<?php echo esc_attr( $pdfib_dv ); ?>" <?php checked( in_array( $pdfib_dv, $pdfib_selected_dpis, true ) ); ?>>
							<?php else : ?>
								<input type="checkbox" disabled>
							<?php endif; ?>
							<?php echo esc_html( $pdfib_di['label'] ); ?>
						</label>
						<?php endforeach; ?>
						<?php
						do_action(
							'pdfib_display_dpi_premium_options',
							array(
								'selected_dpis'    => $pdfib_selected_dpis,
								'can_use_high_dpi' => $pdfib_can_use_high_dpi,
							)
						);
						// phpcs:enable
						?>
					</div>
				</div>

				<!-- Format -->
				<div class="pdfb-setting-group">
					<label>
						<?php esc_html_e( 'Format', 'advanced-pdf-invoice-builder' ); ?>
					</label>
					<div class="pdfb-pill-row">
						<?php
						$pdfib_fmt_opts       = array(
							'A4' => array(
								'label'   => 'A4',
								'soon'    => false,
								'premium' => false,
							),
						);
						$pdfib_active_formats = $pdfib_can_use_extended_formats
							? array_filter( (array) explode( ',', $pdfib_modal_formats ) )
							: array( 'A4' );
						foreach ( $pdfib_fmt_opts as $pdfib_fk => $pdfib_fi ) :
							$pdfib_format_locked = $pdfib_fi['soon'] || ! empty( $pdfib_fi['premium'] );
							$pdfib_format_cls    = $pdfib_fi['soon'] ? ' pdfb-pill-option--soon' : ( ! empty( $pdfib_fi['premium'] ) ? ' pdfb-pill-option--locked' : '' );
							?>
						<label class="pdfb-pill-option<?php echo esc_attr( $pdfib_format_cls ); ?>">
							<?php if ( ! $pdfib_format_locked ) : ?>
								<input type="checkbox"
									name="pdfib_canvas_formats[]"
									value="<?php echo esc_attr( $pdfib_fk ); ?>"
									<?php checked( in_array( $pdfib_fk, $pdfib_active_formats, true ) ); ?>>
							<?php else : ?>
								<input type="checkbox" disabled>
							<?php endif; ?>
							<?php echo esc_html( $pdfib_fi['label'] ); ?>
						</label>
						<?php endforeach; ?>
						<?php
						do_action(
							'pdfib_display_format_premium_options',
							array(
								'selected_formats'         => $pdfib_active_formats,
								'can_use_extended_formats' => $pdfib_can_use_extended_formats,
							)
						);
						?>
					</div>
				</div>

				<!-- Orientation -->
				<div class="pdfb-setting-group">
					<label>
						<?php esc_html_e( 'Orientation', 'advanced-pdf-invoice-builder' ); ?>
					</label>
					<div class="pdfb-pill-row">
						<?php $pdfib_active_orientations = array_filter( array_map( 'trim', explode( ',', $pdfib_modal_orientations ) ) ); ?>
						<?php if ( empty( $pdfib_active_orientations ) ) : ?>
							<?php $pdfib_active_orientations = array( 'portrait' ); ?>
						<?php endif; ?>
						<?php
						$pdfib_orientation_opts = array(
							'portrait' => array(
								'label'   => 'Portrait',
								'soon'    => false,
								'premium' => false,
							),
						);
						?>
						<?php foreach ( $pdfib_orientation_opts as $pdfib_ok => $pdfib_oi ) : ?>
							<?php
							$pdfib_orientation_locked = $pdfib_oi['soon'] || ! empty( $pdfib_oi['premium'] );
							$pdfib_orientation_cls    = $pdfib_oi['soon'] ? ' pdfb-pill-option--soon' : ( ! empty( $pdfib_oi['premium'] ) ? ' pdfb-pill-option--locked' : '' );
							?>
							<label class="pdfb-pill-option<?php echo esc_attr( $pdfib_orientation_cls ); ?>">
								<?php if ( ! $pdfib_orientation_locked ) : ?>
									<input type="checkbox"
										name="pdfib_canvas_orientations[]"
										value="<?php echo esc_attr( $pdfib_ok ); ?>"
										<?php checked( in_array( $pdfib_ok, $pdfib_active_orientations, true ) ); ?>>
								<?php else : ?>
									<input type="checkbox" disabled>
								<?php endif; ?>
								<?php echo esc_html( $pdfib_oi['label'] ); ?>
							</label>
						<?php endforeach; ?>
							<?php
							do_action(
								'pdfib_display_orientation_premium_options',
								array(
									'selected_orientations'     => $pdfib_active_orientations,
									'can_use_extended_formats'  => $pdfib_can_use_extended_formats,
								)
							);
							?>
					</div>
				</div>

			</div><!-- .pdfb-modal-settings-grid -->
		</div>
		<div class="pdfb-canvas-modal-footer">
			<button type="button" class="button pdfb-canvas-modal-cancel">
				<?php
				esc_html_e( 'Annuler', 'advanced-pdf-invoice-builder' );
				?>
			</button>
			<button type="button"
				class="button button-primary pdfb-canvas-modal-apply"
				data-category="affichage">
				<?php
				esc_html_e( 'Appliquer', 'advanced-pdf-invoice-builder' );
				?>
			</button>
		</div>
	</div>
</div>

<div id="canvas-navigation-modal-overlay"
	class="pdfb-canvas-modal-overlay"
	style="display: none;">
	<div class="pdfb-canvas-modal-container">
		<div class="pdfb-canvas-modal-header">
			<h3>
				<?php
				esc_html_e(
					'Parametres de navigation',
					'advanced-pdf-invoice-builder'
				);
				?>
			</h3>
			<button type="button" class="pdfb-canvas-modal-close">
				&times;
			</button>
		</div>
		<div class="pdfb-canvas-modal-body">
			<div class="pdfb-modal-settings-grid">
				<?php
				do_action(
					'pdfib_canvas_navigation_premium_fields',
					array(
						'grid_enabled'            => $pdfib_modal_grid_enabled,
						'grid_size'               => $pdfib_modal_grid_size,
						'guides_enabled'          => $pdfib_modal_guides_enabled,
						'snap_to_grid'            => $pdfib_modal_snap_to_grid,
						'can_use_grid_navigation' => $pdfib_can_use_grid_navigation,
					)
				);
				?>
				<div class="pdfb-setting-group">
					<label for="modal_canvas_zoom_min">
						<?php
						esc_html_e(
							'Zoom minimum',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<input type="number"
						id="modal_canvas_zoom_min"
						name="pdfib_canvas_zoom_min"
						value="<?php echo esc_attr( $pdfib_modal_zoom_min ); ?>">
				</div>
				<div class="pdfb-setting-group">
					<label for="modal_canvas_zoom_max">
						<?php
						esc_html_e(
							'Zoom maximum',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<input type="number"
						id="modal_canvas_zoom_max"
						name="pdfib_canvas_zoom_max"
						value="<?php echo esc_attr( $pdfib_modal_zoom_max ); ?>">
				</div>
				<div class="pdfb-setting-group">
					<label for="modal_canvas_zoom_default">
						<?php
						esc_html_e(
							'Zoom par defaut',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<input type="number"
						id="modal_canvas_zoom_default"
						name="pdfib_canvas_zoom_default"
						value="<?php echo esc_attr( $pdfib_modal_zoom_default ); ?>">
				</div>
				<div class="pdfb-setting-group">
					<label for="modal_canvas_zoom_step">
						<?php
						esc_html_e(
							'Pas de zoom',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<input type="number"
						id="modal_canvas_zoom_step"
						name="pdfib_canvas_zoom_step"
						value="<?php echo esc_attr( $pdfib_modal_zoom_step ); ?>">
				</div>
			</div>
		</div>
		<div class="pdfb-canvas-modal-footer">
			<button type="button" class="button pdfb-canvas-modal-cancel">
				<?php
				esc_html_e( 'Annuler', 'advanced-pdf-invoice-builder' );
				?>
			</button>
			<button type="button"
				class="button button-primary pdfb-canvas-modal-apply"
				data-category="navigation">
				<?php
				esc_html_e( 'Appliquer', 'advanced-pdf-invoice-builder' );
				?>
			</button>
		</div>
	</div>
</div>

<div id="canvas-comportement-modal-overlay"
	class="pdfb-canvas-modal-overlay"
	style="display: none;">
	<div class="pdfb-canvas-modal-container">
		<div class="pdfb-canvas-modal-header">
			<h3>
				<?php
				esc_html_e(
					'Parametres de comportement',
					'advanced-pdf-invoice-builder'
				);
				?>
			</h3>
			<button type="button" class="pdfb-canvas-modal-close">
				&times;
			</button>
		</div>
		<div class="pdfb-canvas-modal-body">
			<div class="pdfb-modal-settings-grid">
				<div class="pdfb-setting-group">
					<label for="modal_canvas_drag_enabled">
						<?php
						esc_html_e(
							'Glisser deposer',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<div class="pdfb-toggle-switch">
						<input type="checkbox"
							id="modal_canvas_drag_enabled"
							name="pdfib_canvas_drag_enabled"
							value="1" 
							<?php
							checked( $pdfib_modal_drag_enabled, '1' );
							?>
							>
						<span class="pdfb-ts" aria-hidden="true"></span>
					</div>
				</div>
				<div class="pdfb-setting-group">
					<label for="modal_canvas_resize_enabled">
						<?php
						esc_html_e(
							'Redimensionnement',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<div class="pdfb-toggle-switch">
						<input type="checkbox"
							id="modal_canvas_resize_enabled"
							name="pdfib_canvas_resize_enabled"
							value="1" 
							<?php
							checked( $pdfib_modal_resize_enabled, '1' );
							?>
							>
						<span class="pdfb-ts" aria-hidden="true"></span>
					</div>
				</div>
				<div class="pdfb-setting-group">
					<label for="modal_canvas_rotate_enabled">
						<?php
						esc_html_e( 'Rotation', 'advanced-pdf-invoice-builder' );
						?>
					</label>
					<div class="pdfb-toggle-switch">
						<input type="checkbox"
							id="modal_canvas_rotate_enabled"
							name="pdfib_canvas_rotate_enabled"
							value="1" 
							<?php
							checked( $pdfib_modal_rotate_enabled, '1' );
							?>
							>
						<span class="pdfb-ts" aria-hidden="true"></span>
					</div>
				</div>
				<?php
				do_action(
					'pdfib_canvas_behavior_premium_fields',
					array(
						'multi_select'               => $pdfib_modal_multi_select,
						'selection_mode'             => $pdfib_modal_selection_mode,
						'keyboard_shortcuts'         => $pdfib_modal_keyboard_shortcuts,
						'can_use_advanced_selection' => $pdfib_can_use_advanced_selection,
						'can_use_keyboard_shortcuts' => $pdfib_can_use_keyboard_shortcuts,
					)
				);
				?>
				<div class="pdfb-setting-group">
					<label for="modal_canvas_export_quality">
						<?php
						esc_html_e(
							'Qualite export',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<select id="modal_canvas_export_quality"
						name="pdfib_canvas_export_quality">
						<option value="screen" 
						<?php
						selected( $pdfib_modal_export_quality, 'screen' );
						?>
						>
							<?php
							esc_html_e( 'Ecran', 'advanced-pdf-invoice-builder' );
							?>
						</option>
						<option value="web" 
						<?php
						selected( $pdfib_modal_export_quality, 'web' );
						?>
						>
							<?php
							esc_html_e( 'Web', 'advanced-pdf-invoice-builder' );
							?>
						</option>
						<option value="print" 
						<?php
						selected( $pdfib_modal_export_quality, 'print' );
						?>
						>
							<?php
							esc_html_e(
								'Impression',
								'advanced-pdf-invoice-builder'
							);
							?>
						</option>
					</select>
				</div>
				<div class="pdfb-setting-group">
					<label for="modal_canvas_export_format">
						<?php
						esc_html_e(
							'Format export',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<select id="modal_canvas_export_format"
						name="pdfib_canvas_export_format">
						<option value="pdf" 
						<?php
						selected( $pdfib_modal_export_format, 'pdf' );
						?>
						>PDF</option>
						<?php
						do_action(
							'pdfib_canvas_export_format_premium_options',
							array(
								'export_format' => $pdfib_modal_export_format,
								'can_use_multi_format_export' => $pdfib_can_use_multi_format_export,
							)
						);
						?>
					</select>
				</div>
				<div class="pdfb-setting-group">
					<label for="modal_canvas_export_transparent">
						<?php
						esc_html_e(
							'Fond transparent',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<div class="pdfb-toggle-switch">
						<input type="checkbox"
							id="modal_canvas_export_transparent"
							name="pdfib_canvas_export_transparent"
							value="1" 
							<?php
							checked( $pdfib_modal_export_transparent, '1' );
							?>
							>
						<span class="pdfb-ts" aria-hidden="true"></span>
					</div>
				</div>
			</div>
		</div>
		<div class="pdfb-canvas-modal-footer">
			<button type="button" class="button pdfb-canvas-modal-cancel">
				<?php
				esc_html_e( 'Annuler', 'advanced-pdf-invoice-builder' );
				?>
			</button>
			<button type="button"
				class="button button-primary pdfb-canvas-modal-apply"
				data-category="comportement">
				<?php
				esc_html_e( 'Appliquer', 'advanced-pdf-invoice-builder' );
				?>
			</button>
		</div>
	</div>
</div>

