<?php
/**
 * Advanced PDF Invoice Builder - Canvas Manager.
 *
 * Gère les paramètres du canvas et les applique aux générations PDF/Image.
 *
 * @package PDFIB\Canvas
 * @since 1.1.0
 */

namespace PDFIB\Canvas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestionnaire des paramètres du canvas.
 */
class CanvasManager {

	/**
	 * Instance unique.
	 *
	 * @var CanvasManager|null
	 */
	private static $instance = null;

	/**
	 * Paramètres canvas en cache.
	 *
	 * @var array<string,mixed>
	 */
	private $settings = array();

	/**
	 * Récupère l'instance unique.
	 *
	 * @return CanvasManager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructeur.
	 */
	private function __construct() {
		$this->load_settings();
		$this->register_hooks();
	}

	/**
	 * Charge les paramètres du canvas depuis WordPress.
	 */
	private function load_settings() {
		$settings = pdfib_get_option( 'pdfib_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$defaults = $this->get_default_settings();

		$this->settings = array_merge(
			$this->load_display_settings( $settings, $defaults ),
			$this->load_grid_settings( $settings, $defaults ),
			$this->load_zoom_settings( $settings, $defaults ),
			$this->load_export_settings( $settings, $defaults ),
			$this->load_pdf_settings( $settings, $defaults )
		);
	}

	/**
	 * Charge les paramètres d'affichage.
	 *
	 * @param array $settings Paramètres enregistrés.
	 * @param array $defaults Valeurs par défaut.
	 * @return array
	 */
	private function load_display_settings( array $settings, array $defaults ) {
		$can_use_extended_formats   = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'extended_formats' );
		$can_use_high_dpi           = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'high_dpi' );
		$default_canvas_format      = $settings['pdfib_canvas_format'] ?? $defaults['default_canvas_format'];
		$default_canvas_orientation = $settings['pdfib_canvas_default_orientation'] ?? $defaults['default_canvas_orientation'];
		$default_canvas_dpi         = intval( $settings['pdfib_canvas_dpi'] ?? $defaults['default_canvas_dpi'] );

		if ( ! $can_use_extended_formats ) {
			$default_canvas_format      = 'A4';
			$default_canvas_orientation = 'portrait';
		}

		if ( ! $can_use_high_dpi ) {
			$default_canvas_dpi = min( $default_canvas_dpi, 150 );
		}

		return array(
			'default_canvas_format'       => $default_canvas_format,
			'default_canvas_orientation'  => $default_canvas_orientation,
			'default_canvas_unit'         => $settings['pdfib_canvas_unit'] ?? $defaults['default_canvas_unit'],
			'default_canvas_dpi'          => $default_canvas_dpi,
			'default_canvas_width'        => intval( $settings['pdfib_canvas_width'] ?? $defaults['default_canvas_width'] ),
			'default_canvas_height'       => intval( $settings['pdfib_canvas_height'] ?? $defaults['default_canvas_height'] ),
			'canvas_background_color'     => $settings['pdfib_canvas_bg_color'] ?? $defaults['canvas_background_color'],
			'canvas_show_transparency'    => ( $settings['pdfib_canvas_show_transparency'] ?? ( $defaults['canvas_show_transparency'] ? '1' : '0' ) ) === '1',
			'container_background_color'  => $settings['pdfib_canvas_container_bg_color'] ?? $defaults['container_background_color'],
			'container_show_transparency' => ( $settings['pdfib_canvas_container_show_transparency'] ?? ( $defaults['container_show_transparency'] ? '1' : '0' ) ) === '1',
			'border_color'                => $settings['pdfib_canvas_border_color'] ?? $defaults['border_color'],
			'border_width'                => intval( $settings['pdfib_canvas_border_width'] ?? $defaults['border_width'] ),
			'shadow_enabled'              => ( $settings['pdfib_canvas_shadow_enabled'] ?? ( $defaults['shadow_enabled'] ? '1' : '0' ) ) === '1',
			'margin_top'                  => intval( $settings['pdfib_canvas_margin_top'] ?? $defaults['margin_top'] ),
			'margin_right'                => intval( $settings['pdfib_canvas_margin_right'] ?? $defaults['margin_right'] ),
			'margin_bottom'               => intval( $settings['pdfib_canvas_margin_bottom'] ?? $defaults['margin_bottom'] ),
			'margin_left'                 => intval( $settings['pdfib_canvas_margin_left'] ?? $defaults['margin_left'] ),
			'show_margins'                => ( $settings['pdfib_canvas_show_margins'] ?? ( $defaults['show_margins'] ? '1' : '0' ) ) === '1',
		);
	}

	/**
	 * Charge les paramètres de grille.
	 *
	 * @param array $settings Paramètres enregistrés.
	 * @param array $defaults Valeurs par défaut.
	 * @return array
	 */
	private function load_grid_settings( array $settings, array $defaults ) {
		return array(
			'show_grid'        => ( function () use ( $settings, $defaults ): bool {
				if ( ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'grid_navigation' ) ) {
					return false;
				}

				$default_val = $defaults['show_grid'] ? '1' : '0';

				return ( $settings['pdfib_canvas_grid_enabled'] ?? $default_val ) === '1';
			} )(),
			'grid_size'        => intval( $settings['pdfib_canvas_grid_size'] ?? $defaults['grid_size'] ),
			'grid_color'       => $settings['pdfib_canvas_grid_color'] ?? $defaults['grid_color'],
			'snap_to_grid'     => ( function () use ( $settings, $defaults ): bool {
				if ( ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'grid_navigation' ) ) {
					return false;
				}

				$default_val = $defaults['snap_to_grid'] ? '1' : '0';

				return ( $settings['pdfib_canvas_snap_to_grid'] ?? $default_val ) === '1';
			} )(),
			'snap_to_elements' => ( $settings['pdfib_canvas_snap_to_elements'] ?? ( $defaults['snap_to_elements'] ? '1' : '0' ) ) === '1',
			'snap_tolerance'   => intval( $settings['pdfib_canvas_snap_tolerance'] ?? $defaults['snap_tolerance'] ),
			'show_guides'      => ( function () use ( $settings, $defaults ): bool {
				if ( ! \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'grid_navigation' ) ) {
					return false;
				}

				$default_val = $defaults['show_guides'] ? '1' : '0';

				return ( $settings['pdfib_canvas_guides_enabled'] ?? $default_val ) === '1';
			} )(),
		);
	}

	/**
	 * Charge les paramètres de zoom et d'édition.
	 *
	 * @param array $settings Paramètres enregistrés.
	 * @param array $defaults Valeurs par défaut.
	 * @return array
	 */
	private function load_zoom_settings( array $settings, array $defaults ) {
		$can_use_advanced_selection = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'advanced_selection' );

		return array(
			'default_zoom'        => intval( $settings['pdfib_canvas_zoom_default'] ?? $defaults['default_zoom'] ),
			'zoom_step'           => intval( $settings['pdfib_canvas_zoom_step'] ?? $defaults['zoom_step'] ),
			'min_zoom'            => intval( $settings['pdfib_canvas_zoom_min'] ?? $defaults['min_zoom'] ),
			'max_zoom'            => intval( $settings['pdfib_canvas_zoom_max'] ?? $defaults['max_zoom'] ),
			'zoom_with_wheel'     => ( $settings['pdfib_canvas_zoom_with_wheel'] ?? ( $defaults['zoom_with_wheel'] ? '1' : '0' ) ) === '1',
			'pan_with_mouse'      => ( $settings['pdfib_canvas_pan_enabled'] ?? ( $defaults['pan_with_mouse'] ? '1' : '0' ) ) === '1',
			'show_resize_handles' => ( $settings['pdfib_canvas_show_resize_handles'] ?? ( $defaults['show_resize_handles'] ? '1' : '0' ) ) === '1',
			'handle_size'         => intval( $settings['pdfib_canvas_handle_size'] ?? $defaults['handle_size'] ),
			'handle_color'        => $settings['pdfib_canvas_handle_color'] ?? $defaults['handle_color'],
			'enable_rotation'     => ( $settings['pdfib_canvas_rotate_enabled'] ?? ( $defaults['enable_rotation'] ? '1' : '0' ) ) === '1',
			'rotation_step'       => intval( $settings['pdfib_canvas_rotation_step'] ?? $defaults['rotation_step'] ),
			'multi_select'        => $can_use_advanced_selection && ( $settings['pdfib_canvas_multi_select'] ?? ( $defaults['multi_select'] ? '1' : '0' ) ) === '1',
			'copy_paste_enabled'  => ( $settings['pdfib_canvas_copy_paste_enabled'] ?? ( $defaults['copy_paste_enabled'] ? '1' : '0' ) ) === '1',
		);
	}

	/**
	 * Charge les paramètres d'export.
	 *
	 * @param array $settings Paramètres enregistrés.
	 * @param array $defaults Valeurs par défaut.
	 * @return array
	 */
	private function load_export_settings( array $settings, array $defaults ) {
		$can_use_multi_format_export = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'multi_format_export' );
		$can_use_keyboard_shortcuts  = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'keyboard_shortcuts' );
		$can_use_advanced_selection  = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'advanced_selection' );

		return array(
			'export_quality'               => $settings['pdfib_canvas_export_quality'] ?? $defaults['export_quality'],
			'export_format'                => $can_use_multi_format_export ? ( $settings['pdfib_canvas_export_format'] ?? $defaults['export_format'] ) : 'pdf',
			'compress_images'              => ( $settings['pdfib_canvas_compress_images'] ?? ( $defaults['compress_images'] ? '1' : '0' ) ) === '1',
			'image_quality'                => intval( $settings['pdfib_canvas_image_quality'] ?? $defaults['image_quality'] ),
			'max_image_size'               => intval( $settings['pdfib_canvas_max_image_size'] ?? $defaults['max_image_size'] ),
			'include_metadata'             => ( $settings['pdfib_canvas_include_metadata'] ?? ( $defaults['include_metadata'] ? '1' : '0' ) ) === '1',
			'pdf_author'                   => $settings['pdfib_canvas_pdf_author'] ?? $defaults['pdf_author'],
			'pdf_subject'                  => $settings['pdfib_canvas_pdf_subject'] ?? $defaults['pdf_subject'],
			'auto_crop'                    => ( $settings['pdfib_canvas_auto_crop'] ?? ( $defaults['auto_crop'] ? '1' : '0' ) ) === '1',
			'embed_fonts'                  => ( $settings['pdfib_canvas_embed_fonts'] ?? ( $defaults['embed_fonts'] ? '1' : '0' ) ) === '1',
			'optimize_for_web'             => ( $settings['pdfib_canvas_optimize_for_web'] ?? ( $defaults['optimize_for_web'] ? '1' : '0' ) ) === '1',
			'enable_hardware_acceleration' => ( $settings['pdfib_canvas_enable_hardware_acceleration'] ?? ( $defaults['enable_hardware_acceleration'] ? '1' : '0' ) ) === '1',
			'limit_fps'                    => ( $settings['pdfib_canvas_limit_fps'] ?? ( $defaults['limit_fps'] ? '1' : '0' ) ) === '1',
			'max_fps'                      => intval( $settings['pdfib_canvas_fps_target'] ?? $defaults['max_fps'] ),
			'auto_save_enabled'            => ( $settings['pdfib_canvas_auto_save'] ?? ( $defaults['auto_save_enabled'] ? '1' : '0' ) ) === '1',
			'auto_save_interval'           => intval( $settings['pdfib_canvas_auto_save_interval'] ?? $defaults['auto_save_interval'] ),
			'auto_save_versions'           => intval( $settings['pdfib_canvas_auto_save_versions'] ?? $defaults['auto_save_versions'] ),
			'undo_levels'                  => intval( $settings['pdfib_canvas_undo_levels'] ?? $defaults['undo_levels'] ),
			'redo_levels'                  => intval( $settings['pdfib_canvas_redo_levels'] ?? $defaults['redo_levels'] ),
			'enable_keyboard_shortcuts'    => $can_use_keyboard_shortcuts && ( $settings['pdfib_canvas_keyboard_shortcuts'] ?? ( $defaults['enable_keyboard_shortcuts'] ? '1' : '0' ) ) === '1',
			'canvas_selection_mode'        => $can_use_advanced_selection ? ( $settings['pdfib_canvas_selection_mode'] ?? $defaults['canvas_selection_mode'] ) : 'single',
			'debug_mode'                   => ( $settings['pdfib_canvas_debug_mode'] ?? ( $defaults['debug_mode'] ? '1' : '0' ) ) === '1',
			'show_fps'                     => ( $settings['pdfib_canvas_show_fps'] ?? ( $defaults['show_fps'] ? '1' : '0' ) ) === '1',
		);
	}

	/**
	 * Charge les paramètres PDF.
	 *
	 * @param array $settings Paramètres enregistrés.
	 * @param array $defaults Valeurs par défaut.
	 * @return array
	 */
	private function load_pdf_settings( array $settings, array $defaults ) {
		return array(
			'pdf_quality'       => $settings['pdfib_pdf_quality'] ?? $defaults['pdf_quality'],
			'pdf_format'        => $settings['pdfib_default_format'] ?? $defaults['pdf_format'],
			'pdf_orientation'   => $settings['pdfib_default_orientation'] ?? $defaults['pdf_orientation'],
			'pdf_compression'   => $settings['pdfib_pdf_compression'] ?? $defaults['pdf_compression'],
			'pdf_cache_enabled' => ( $settings['pdfib_pdf_cache_enabled'] ?? ( $defaults['pdf_cache_enabled'] ? '1' : '0' ) ) === '1',
			'pdf_page_break'    => ( $settings['pdfib_pdf_page_break_enabled'] ?? ( $defaults['pdf_page_break'] ? '1' : '0' ) ) === '1',
		);
	}

	/**
	 * Récupère les paramètres par défaut.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return array_merge(
			$this->get_default_canvas_settings(),
			$this->get_default_editor_settings(),
			$this->get_default_pdf_settings()
		);
	}

	/**
	 * Retourne les paramètres canvas par défaut.
	 *
	 * @return array
	 */
	private function get_default_canvas_settings() {
		return array(
			'default_canvas_format'       => 'A4',
			'default_canvas_orientation'  => 'portrait',
			'default_canvas_unit'         => 'px',
			'default_canvas_dpi'          => 96,
			'default_canvas_width'        => 794,
			'default_canvas_height'       => 1123,
			'canvas_background_color'     => '#ffffff',
			'canvas_show_transparency'    => false,
			'container_background_color'  => '#f8f9fa',
			'container_show_transparency' => false,
			'border_color'                => '#cccccc',
			'border_width'                => 1,
			'shadow_enabled'              => false,
			'margin_top'                  => 28,
			'margin_right'                => 28,
			'margin_bottom'               => 10,
			'margin_left'                 => 10,
			'show_margins'                => false,
			'show_grid'                   => false,
			'grid_size'                   => 10,
			'grid_color'                  => '#e0e0e0',
			'snap_to_grid'                => false,
			'snap_to_elements'            => false,
			'snap_tolerance'              => 5,
			'show_guides'                 => false,
		);
	}

	/**
	 * Retourne les paramètres d'éditeur par défaut.
	 *
	 * @return array
	 */
	private function get_default_editor_settings() {
		return array(
			'default_zoom'                 => 100,
			'zoom_step'                    => 25,
			'min_zoom'                     => 10,
			'max_zoom'                     => 500,
			'zoom_with_wheel'              => false,
			'pan_with_mouse'               => false,
			'show_resize_handles'          => false,
			'handle_size'                  => 8,
			'handle_color'                 => '#007cba',
			'enable_rotation'              => false,
			'rotation_step'                => 15,
			'multi_select'                 => false,
			'copy_paste_enabled'           => false,
			'export_quality'               => 'print',
			'export_format'                => 'pdf',
			'compress_images'              => true,
			'image_quality'                => 85,
			'max_image_size'               => 2048,
			'include_metadata'             => true,
			'pdf_author'                   => 'Advanced PDF Invoice Builder',
			'pdf_subject'                  => '',
			'auto_crop'                    => false,
			'embed_fonts'                  => true,
			'optimize_for_web'             => true,
			'enable_hardware_acceleration' => true,
			'limit_fps'                    => true,
			'max_fps'                      => 60,
			'auto_save_enabled'            => true,
			'auto_save_interval'           => 5,
			'auto_save_versions'           => 10,
			'undo_levels'                  => 50,
			'redo_levels'                  => 50,
			'enable_keyboard_shortcuts'    => true,
			'canvas_selection_mode'        => 'click',
			'debug_mode'                   => false,
			'show_fps'                     => false,
			'pdf_quality'                  => 'high',
			'pdf_format'                   => 'A4',
			'pdf_orientation'              => 'portrait',
			'pdf_compression'              => 'medium',
			'pdf_cache_enabled'            => false,
			'pdf_page_break'               => false,
			'memory_limit_js'              => '',
			'memory_limit_php'             => '',
			'response_timeout'             => 30,
			'lazy_loading_editor'          => false,
			'preload_critical'             => false,
			'lazy_loading_plugin'          => false,
		);
	}

	/**
	 * Retourne les paramètres PDF par défaut.
	 *
	 * @return array
	 */
	private function get_default_pdf_settings() {
		return array(
			'pdf_quality'       => 'high',
			'pdf_format'        => 'A4',
			'pdf_orientation'   => 'portrait',
			'pdf_compression'   => 'medium',
			'pdf_cache_enabled' => false,
			'pdf_page_break'    => false,
		);
	}

	/**
	 * Récupère les paramètres actuels du canvas.
	 *
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Enregistre les hooks WordPress.
	 */
	private function register_hooks() {
		// Filtre pour appliquer les paramètres canvas à React.
		add_filter( 'pdfib_react_settings', array( $this, 'apply_canvas_settings_to_react' ), 10, 1 );
		// Action pour initialiser les paramètres canvas côté client.
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_canvas_settings_script' ), 15 );
	}

	/**
	 * Applique les paramètres canvas aux paramètres React.
	 *
	 * @param array $settings Paramètres React.
	 * @return array Paramètres modifiés.
	 */
	public function apply_canvas_settings_to_react( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings['canvas'] = $this->settings;
		return $settings;
	}

	/**
	 * Enregistre le script de paramètres canvas.
	 */
	public function enqueue_canvas_settings_script() {
		if ( ! is_admin() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && strpos( $screen->id, 'pdf-builder' ) === false ) {
			return;
		}

		$handle = 'pdfib-canvas-settings';
		if ( ! wp_script_is( $handle, 'registered' ) && ! wp_script_is( $handle, 'enqueued' ) ) {
			wp_register_script( $handle, false, array( 'jquery' ), PDFIB_VERSION, true );
		}

		$canvas_data = array(
			'settings'   => $this->settings,
			'dimensions' => $this->get_canvas_dimensions(),
			'margins'    => $this->get_canvas_margins(),
		);

		wp_add_inline_script(
			$handle,
			'window.pdfibCanvasSettings = ' . wp_json_encode( $canvas_data ) . ';',
			'before'
		);

		if ( ! wp_script_is( $handle, 'enqueued' ) ) {
			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Récupère une valeur de paramètre canvas.
	 *
	 * @param string $key Clé du paramètre.
	 * @param mixed  $fallback Valeur par défaut.
	 * @return mixed
	 */
	public function get_setting( $key, $fallback = null ) {
		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		}

		return $fallback;
	}

	/**
	 * Récupère tous les paramètres.
	 *
	 * @return array
	 */
	public function get_all_settings() {
		return $this->get_settings();
	}

	/**
	 * Récupère les dimensions du canvas.
	 *
	 * @return array
	 */
	public function get_canvas_dimensions() {
		return array(
			'width'       => $this->get_setting( 'default_canvas_width', 794 ),
			'height'      => $this->get_setting( 'default_canvas_height', 1123 ),
			'unit'        => $this->get_setting( 'default_canvas_unit', 'px' ),
			'orientation' => $this->get_setting( 'default_canvas_orientation', 'portrait' ),
		);
	}

	/**
	 * Récupère les marges du canvas.
	 *
	 * @return array
	 */
	public function get_canvas_margins() {
		return array(
			'top'    => $this->get_setting( 'margin_top', 28 ),
			'right'  => $this->get_setting( 'margin_right', 28 ),
			'bottom' => $this->get_setting( 'margin_bottom', 10 ),
			'left'   => $this->get_setting( 'margin_left', 10 ),
		);
	}

	/**
	 * Récupère les paramètres de grille.
	 *
	 * @return array
	 */
	public function get_grid_settings() {
		return array(
			'show'           => $this->get_setting( 'show_grid', false ),
			'size'           => $this->get_setting( 'grid_size', 10 ),
			'color'          => $this->get_setting( 'grid_color', '#e0e0e0' ),
			'snap_enabled'   => $this->get_setting( 'snap_to_grid', false ),
			'snap_tolerance' => $this->get_setting( 'snap_tolerance', 5 ),
		);
	}

	/**
	 * Récupère les paramètres de zoom.
	 *
	 * @return array
	 */
	public function get_zoom_settings() {
		return array(
			'default'       => $this->get_setting( 'default_zoom', 100 ),
			'step'          => $this->get_setting( 'zoom_step', 25 ),
			'min'           => $this->get_setting( 'min_zoom', 10 ),
			'max'           => $this->get_setting( 'max_zoom', 500 ),
			'wheel_enabled' => $this->get_setting( 'zoom_with_wheel', false ),
		);
	}

	/**
	 * Récupère les paramètres de sélection.
	 *
	 * @return array
	 */
	public function get_selection_settings() {
		return array(
			'multi_select'  => $this->get_setting( 'multi_select', false ),
			'copy_paste'    => $this->get_setting( 'copy_paste_enabled', false ),
			'rotation'      => $this->get_setting( 'enable_rotation', false ),
			'rotation_step' => $this->get_setting( 'rotation_step', 15 ),
			'show_handles'  => $this->get_setting( 'show_resize_handles', false ),
			'handle_size'   => $this->get_setting( 'handle_size', 8 ),
		);
	}

	/**
	 * Récupère les paramètres d'export.
	 *
	 * @return array
	 */
	public function get_export_settings() {
		return array(
			'quality'          => $this->get_setting( 'export_quality', 'print' ),
			'format'           => $this->get_setting( 'export_format', 'pdf' ),
			'compress_images'  => $this->get_setting( 'compress_images', true ),
			'image_quality'    => $this->get_setting( 'image_quality', 85 ),
			'max_image_size'   => $this->get_setting( 'max_image_size', 2048 ),
			'include_metadata' => $this->get_setting( 'include_metadata', true ),
		);
	}

	/**
	 * Récupère les paramètres d'historique.
	 *
	 * @return array
	 */
	public function get_history_settings() {
		return array(
			'undo_levels'        => $this->get_setting( 'undo_levels', 50 ),
			'redo_levels'        => $this->get_setting( 'redo_levels', 50 ),
			'auto_save'          => $this->get_setting( 'auto_save_enabled', true ),
			'auto_save_interval' => $this->get_setting( 'auto_save_interval', 5 ),
			'auto_save_versions' => $this->get_setting( 'auto_save_versions', 10 ),
		);
	}

	/**
	 * Vérifie si une fonctionnalité est activée.
	 *
	 * @param string $feature Nom de la fonctionnalité.
	 * @return bool
	 */
	public function is_feature_enabled( $feature ) {
		return (bool) $this->get_setting( $feature, false );
	}

	/**
	 * Réinitialise les paramètres aux valeurs par défaut.
	 */
	public function reset_to_defaults() {
		$this->settings = $this->get_default_settings();
		pdfib_update_option( 'pdfib_canvas_settings', $this->settings );
	}

	/**
	 * Sauvegarde les paramètres.
	 *
	 * @param array $settings Paramètres à sauvegarder.
	 * @return bool
	 */
	public function save_settings( $settings ) {
		$validated       = $this->validate_settings( $settings );
		$option_mappings = array_merge(
			$this->get_canvas_base_option_mappings(),
			$this->get_canvas_layout_option_mappings(),
			$this->get_canvas_interaction_option_mappings(),
			$this->get_canvas_export_perf_option_mappings()
		);

		foreach ( $option_mappings as $setting_key => $option_key ) {
			if ( isset( $validated[ $setting_key ] ) ) {
				$value = $validated[ $setting_key ];
				if ( is_bool( $value ) ) {
					$value = $value ? '1' : '0';
				}
				\update_option( $option_key, $value );
			}
		}

		$this->settings = array_merge( $this->settings, $validated );
		pdfib_update_option( 'pdfib_canvas_settings', $this->settings );
		do_action( 'pdfib_canvas_settings_updated', $this->settings );
		return true;
	}

	/**
	 * Retourne les correspondances des paramètres de base.
	 *
	 * @return array<string,string>
	 */
	private function get_canvas_base_option_mappings(): array {
		return array(
			'default_canvas_format'       => 'pdfib_canvas_format',
			'default_canvas_orientation'  => 'pdfib_canvas_default_orientation',
			'default_canvas_unit'         => 'pdfib_canvas_unit',
			'default_canvas_dpi'          => 'pdfib_canvas_dpi',
			'default_canvas_width'        => 'pdfib_canvas_width',
			'default_canvas_height'       => 'pdfib_canvas_height',
			'canvas_background_color'     => 'pdfib_canvas_bg_color',
			'canvas_show_transparency'    => 'pdfib_canvas_show_transparency',
			'container_background_color'  => 'pdfib_canvas_container_bg_color',
			'container_show_transparency' => 'pdfib_canvas_container_show_transparency',
			'border_color'                => 'pdfib_canvas_border_color',
			'border_width'                => 'pdfib_canvas_border_width',
			'shadow_enabled'              => 'pdfib_canvas_shadow_enabled',
			'margin_top'                  => 'pdfib_canvas_margin_top',
			'margin_right'                => 'pdfib_canvas_margin_right',
			'margin_bottom'               => 'pdfib_canvas_margin_bottom',
			'margin_left'                 => 'pdfib_canvas_margin_left',
			'show_margins'                => 'pdfib_canvas_show_margins',
		);
	}

	/**
	 * Retourne les correspondances des paramètres de disposition.
	 *
	 * @return array<string,string>
	 */
	private function get_canvas_layout_option_mappings(): array {
		return array(
			'show_grid'        => 'pdfib_canvas_grid_enabled',
			'grid_size'        => 'pdfib_canvas_grid_size',
			'grid_color'       => 'pdfib_canvas_grid_color',
			'snap_to_grid'     => 'pdfib_canvas_snap_to_grid',
			'snap_to_elements' => 'pdfib_canvas_snap_to_elements',
			'snap_tolerance'   => 'pdfib_canvas_snap_tolerance',
			'show_guides'      => 'pdfib_canvas_guides_enabled',
		);
	}

	/**
	 * Retourne les correspondances des paramètres d'interaction.
	 *
	 * @return array<string,string>
	 */
	private function get_canvas_interaction_option_mappings(): array {
		return array(
			'default_zoom'              => 'pdfib_canvas_zoom_default',
			'zoom_step'                 => 'pdfib_canvas_zoom_step',
			'min_zoom'                  => 'pdfib_canvas_zoom_min',
			'max_zoom'                  => 'pdfib_canvas_zoom_max',
			'zoom_with_wheel'           => 'pdfib_canvas_zoom_with_wheel',
			'pan_with_mouse'            => 'pdfib_canvas_pan_enabled',
			'show_resize_handles'       => 'pdfib_canvas_show_resize_handles',
			'handle_size'               => 'pdfib_canvas_handle_size',
			'handle_color'              => 'pdfib_canvas_handle_color',
			'enable_rotation'           => 'pdfib_canvas_rotate_enabled',
			'rotation_step'             => 'pdfib_canvas_rotation_step',
			'multi_select'              => 'pdfib_canvas_multi_select',
			'copy_paste_enabled'        => 'pdfib_canvas_copy_paste_enabled',
			'canvas_selection_mode'     => 'pdfib_canvas_selection_mode',
			'enable_keyboard_shortcuts' => 'pdfib_canvas_keyboard_shortcuts',
			'debug_mode'                => 'pdfib_canvas_debug_mode',
			'show_fps'                  => 'pdfib_canvas_show_fps',
		);
	}

	/**
	 * Retourne les correspondances des paramètres d'export et de performance.
	 *
	 * @return array<string,string>
	 */
	private function get_canvas_export_perf_option_mappings(): array {
		return array(
			'export_quality'               => 'pdfib_canvas_export_quality',
			'export_format'                => 'pdfib_canvas_export_format',
			'compress_images'              => 'pdfib_canvas_compress_images',
			'image_quality'                => 'pdfib_canvas_image_quality',
			'max_image_size'               => 'pdfib_canvas_max_image_size',
			'include_metadata'             => 'pdfib_canvas_include_metadata',
			'pdf_author'                   => 'pdfib_canvas_pdf_author',
			'pdf_subject'                  => 'pdfib_canvas_pdf_subject',
			'auto_crop'                    => 'pdfib_canvas_auto_crop',
			'embed_fonts'                  => 'pdfib_canvas_embed_fonts',
			'optimize_for_web'             => 'pdfib_canvas_optimize_for_web',
			'enable_hardware_acceleration' => 'pdfib_canvas_enable_hardware_acceleration',
			'limit_fps'                    => 'pdfib_canvas_limit_fps',
			'max_fps'                      => 'pdfib_canvas_fps_target',
			'auto_save_enabled'            => 'pdfib_canvas_auto_save',
			'auto_save_interval'           => 'pdfib_canvas_auto_save_interval',
			'auto_save_versions'           => 'pdfib_canvas_auto_save_versions',
			'undo_levels'                  => 'pdfib_canvas_undo_levels',
			'redo_levels'                  => 'pdfib_canvas_redo_levels',
			'pdf_quality'                  => 'pdfib_pdf_quality',
			'pdf_format'                   => 'pdfib_default_format',
			'pdf_orientation'              => 'pdfib_default_orientation',
			'pdf_compression'              => 'pdfib_pdf_compression',
			'pdf_cache_enabled'            => 'pdfib_pdf_cache_enabled',
			'pdf_page_break'               => 'pdfib_pdf_page_break_enabled',
			'memory_limit_js'              => 'pdfib_canvas_memory_limit_js',
			'memory_limit_php'             => 'pdfib_canvas_memory_limit_php',
			'response_timeout'             => 'pdfib_canvas_response_timeout',
			'lazy_loading_editor'          => 'pdfib_canvas_lazy_loading_editor',
			'preload_critical'             => 'pdfib_canvas_preload_critical',
			'lazy_loading_plugin'          => 'pdfib_canvas_lazy_loading_plugin',
		);
	}

	/**
	 * Valide les paramètres.
	 *
	 * @param array $settings Paramètres à valider.
	 * @return array
	 */
	private function validate_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return array_merge(
			$this->validate_canvas_dimensions_and_colors( $settings ),
			$this->validate_canvas_numeric_fields( $settings ),
			$this->validate_canvas_boolean_fields( $settings ),
			$this->validate_canvas_text_fields( $settings )
		);
	}

	/**
	 * Valide les dimensions et couleurs du canvas.
	 *
	 * @param array $settings Paramètres à valider.
	 * @return array<string,mixed>
	 */
	private function validate_canvas_dimensions_and_colors( array $settings ): array {
		$validated = array();

		if ( isset( $settings['default_canvas_width'] ) ) {
			$validated['default_canvas_width'] = intval( $settings['default_canvas_width'] );
		}

		if ( isset( $settings['default_canvas_height'] ) ) {
			$validated['default_canvas_height'] = intval( $settings['default_canvas_height'] );
		}

		foreach ( array( 'canvas_background_color', 'container_background_color', 'border_color', 'grid_color', 'handle_color' ) as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$validated[ $key ] = sanitize_text_field( $settings[ $key ] );
			}
		}

		if ( isset( $settings['image_quality'] ) ) {
			$validated['image_quality'] = max( 30, min( 100, intval( $settings['image_quality'] ) ) );
		}

		if ( isset( $settings['max_image_size'] ) ) {
			$validated['max_image_size'] = intval( $settings['max_image_size'] );
		}

		return $validated;
	}

	/**
	 * Valide les champs numériques du canvas.
	 *
	 * @param array $settings Paramètres à valider.
	 * @return array<string,int>
	 */
	private function validate_canvas_numeric_fields( array $settings ): array {
		$validated  = array();
		$int_fields = array( 'margin_top', 'margin_right', 'margin_bottom', 'margin_left', 'grid_size', 'snap_tolerance', 'rotation_step', 'handle_size', 'max_fps', 'border_width', 'default_zoom', 'zoom_step', 'min_zoom', 'max_zoom', 'undo_levels', 'redo_levels', 'auto_save_interval', 'auto_save_versions', 'response_timeout' );

		foreach ( $int_fields as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$validated[ $key ] = intval( $settings[ $key ] );
			}
		}

		return $validated;
	}

	/**
	 * Valide les champs booléens du canvas.
	 *
	 * @param array $settings Paramètres à valider.
	 * @return array<string,bool>
	 */
	private function validate_canvas_boolean_fields( array $settings ): array {
		$validated   = array();
		$bool_fields = array( 'shadow_enabled', 'canvas_show_transparency', 'container_show_transparency', 'show_margins', 'show_grid', 'snap_to_grid', 'snap_to_elements', 'show_guides', 'zoom_with_wheel', 'pan_with_mouse', 'show_resize_handles', 'enable_rotation', 'multi_select', 'copy_paste_enabled', 'compress_images', 'include_metadata', 'auto_crop', 'embed_fonts', 'optimize_for_web', 'enable_hardware_acceleration', 'limit_fps', 'auto_save_enabled', 'enable_keyboard_shortcuts', 'debug_mode', 'show_fps', 'pdf_cache_enabled', 'pdf_page_break', 'lazy_loading_editor', 'preload_critical', 'lazy_loading_plugin' );

		foreach ( $bool_fields as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$validated[ $key ] = (bool) $settings[ $key ];
			}
		}

		return $validated;
	}

	/**
	 * Valide les champs texte du canvas.
	 *
	 * @param array $settings Paramètres à valider.
	 * @return array<string,string>
	 */
	private function validate_canvas_text_fields( array $settings ): array {
		$validated   = array();
		$text_fields = array( 'default_canvas_format', 'default_canvas_orientation', 'default_canvas_unit', 'export_quality', 'export_format', 'pdf_author', 'pdf_subject', 'pdf_quality', 'pdf_format', 'pdf_orientation', 'pdf_compression', 'memory_limit_js', 'memory_limit_php' );

		foreach ( $text_fields as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$validated[ $key ] = sanitize_text_field( $settings[ $key ] );
			}
		}

		if ( isset( $settings['canvas_selection_mode'] ) ) {
			$mode = sanitize_text_field( $settings['canvas_selection_mode'] );
			if ( in_array( $mode, array( 'click', 'lasso', 'rectangle' ), true ) ) {
				$validated['canvas_selection_mode'] = $mode;
			}
		}

		return $validated;
	}
}
