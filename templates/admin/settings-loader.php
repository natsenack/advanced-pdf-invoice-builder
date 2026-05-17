<?php
/**
 * Advanced PDF Invoice Builder - Settings Loader
 *
 * Charge les styles et scripts pour la page de paramètres.
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

/**
 * Charge les assets pour la page de paramètres.
 *
 * @param string $hook Identifiant de la page courante.
 *
 * @return void
 */
function pdfib_load_settings_assets( string $hook ) {
	if ( strpos( (string) $hook, 'pdf-builder' ) === false ) {
		return;
	}

	wp_enqueue_media();

	pdfib_enqueue_wp_core_scripts();
	pdfib_enqueue_settings_tabs_script();
	pdfib_enqueue_settings_main_script();
	pdfib_enqueue_additional_scripts();
}

/**
 * Enqueue les scripts WordPress core nécessaires.
 *
 * @return void
 */
function pdfib_enqueue_wp_core_scripts() {
	$wp_scripts = array(
		'wp-date',
		'wp-element',
		'wp-components',
		'wp-api',
		'wp-data',
		'wp-hooks',
		'wp-i18n',
		'wp-url',
		'wp-keycodes',
		'wp-compose',
		'wp-html-entities',
		'wp-primitives',
		'wp-warning',
		'wp-token-list',
		'wp-core-data',
		'wp-core-commands',
		'wp-block-editor',
		'wp-rich-text',
		'wp-commands',
		'wp-blob',
		'wp-shortcode',
		'wp-media-utils',
		'wp-notices',
		'wp-preferences',
		'wp-preferences-persistence',
		'wp-editor',
		'wp-plugins',
		'wp-edit-post',
		'wp-viewport',
		'wp-interface',
		'wp-redux-routine',
		'wp-priority-queue',
		'wp-server-side-render',
		'wp-autop',
		'wp-wordcount',
		'wp-annotations',
		'wp-dom',
		'wp-a11y',
		'wp-dom-ready',
		'wp-polyfill',
	);

	foreach ( $wp_scripts as $handle ) {
		wp_enqueue_script( $handle );
	}

	$init  = "if(typeof window.wp==='undefined'){window.wp={};}";
	$init .= 'window.wp=window.wp||{};';
	$init .= 'window.wp.media=window.wp.media||null;';
	$init .= 'window.wp.ajax=window.wp.ajax||{settings:{}};';
	wp_add_inline_script( 'jquery', $init, 'after' );
}

/**
 * Enqueue le script des onglets de paramètres.
 *
 * @return void
 */
function pdfib_enqueue_settings_tabs_script() {
	$settings_tabs_js = PDFIB_PRO_ASSETS_PATH . 'js/settings-tabs.min.js';
	if ( ! file_exists( $settings_tabs_js ) ) {
		return;
	}

	$version = PDFIB_VERSION . '-' . time() . '-' . wp_rand( 1000, 9999 );

	wp_enqueue_script(
		'pdf-builder-settings-tabs',
		PDFIB_PLUGIN_URL . 'assets/js/settings-tabs.min.js',
		array(
			'jquery',
			'wp-element',
			'wp-components',
			'wp-data',
			'wp-hooks',
		),
		$version,
		false
	);

	if ( class_exists( 'PdfBuilderNonceManager' ) ) {
		$mgr   = PdfBuilderNonceManager::get_instance();
		$nonce = $mgr->generate_nonce();
	} else {
		$nonce = wp_create_nonce( 'pdfib_ajax' );
	}

	wp_localize_script(
		'pdf-builder-settings-tabs',
		'pdfBuilderAjax',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => $nonce,
		)
	);

	add_action(
		'admin_enqueue_scripts',
		'pdfib_disable_settings_tabs_defer_async',
		1
	);
}

/**
 * Désactive defer/async sur le script settings-tabs.
 *
 * @return void
 */
function pdfib_disable_settings_tabs_defer_async() {
	global $wp_scripts;
	$handle = 'pdf-builder-settings-tabs';
	if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
		return;
	}
	$wp_scripts->registered[ $handle ]->extra['defer'] = false;
	$wp_scripts->registered[ $handle ]->extra['async'] = false;
}

/**
 * Enqueue le script principal des paramètres.
 *
 * @return void
 */
function pdfib_enqueue_settings_main_script() {
	$settings_main_js = PDFIB_PRO_ASSETS_PATH . 'js/settings-main.min.js';
	if ( file_exists( $settings_main_js ) ) {
		wp_enqueue_script(
			'pdf-builder-settings-main',
			PDFIB_PLUGIN_URL . 'assets/js/settings-main.min.js',
			array(
				'jquery',
				'wp-element',
				'wp-components',
				'wp-data',
				'wp-hooks',
			),
			PDFIB_VERSION,
			false
		);

		pdfib_maybe_enqueue_canvas_settings_script();
	}

	if ( class_exists( 'PdfBuilderNonceManager' ) ) {
		$mgr        = PdfBuilderNonceManager::get_instance();
		$main_nonce = $mgr->generate_nonce();
	} else {
		$main_nonce = wp_create_nonce( 'pdfib_ajax' );
	}

	wp_localize_script(
		'pdf-builder-settings-main',
		'pdfib_ajax',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => $main_nonce,
		)
	);
}

/**
 * Enqueue le script canvas-settings si disponible.
 *
 * @return void
 */
function pdfib_maybe_enqueue_canvas_settings_script() {
	$canvas_js = PDFIB_PRO_ASSETS_PATH . 'js/canvas-settings.min.js';
	if ( ! file_exists( $canvas_js ) ) {
		return;
	}

	wp_enqueue_script(
		'pdf-builder-canvas-settings',
		PDFIB_PLUGIN_URL . 'assets/js/canvas-settings.min.js',
		array(
			'jquery',
			'pdf-builder-settings-main',
			'wp-element',
			'wp-components',
		),
		PDFIB_VERSION,
		true
	);

	if ( class_exists( 'PdfBuilderNonceManager' ) ) {
		$mgr          = PdfBuilderNonceManager::get_instance();
		$canvas_nonce = $mgr->generate_nonce();
	} else {
		$canvas_nonce = wp_create_nonce( 'pdfib_canvas_settings' );
	}

	wp_localize_script(
		'pdf-builder-canvas-settings',
		'pdfib_canvas_settings',
		array(
			'nonce'    => $canvas_nonce,
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		)
	);
}

/**
 * Enqueue les scripts additionnels (validation, sécurité, bouton flottant).
 *
 * @return void
 */
function pdfib_enqueue_additional_scripts() {
	$general_js = PDFIB_PRO_ASSETS_PATH . 'js/settings-general-validation.js';
	if ( file_exists( $general_js ) ) {
		wp_enqueue_script(
			'pdf-builder-general-validation',
			PDFIB_PLUGIN_URL . 'assets/js/settings-general-validation.js',
			array(),
			PDFIB_VERSION,
			true
		);
	}

	$securite_js = PDFIB_PRO_ASSETS_PATH . 'js/settings-securite.js';
	if ( file_exists( $securite_js ) ) {
		wp_enqueue_script(
			'pdf-builder-settings-securite',
			PDFIB_PLUGIN_URL . 'assets/js/settings-securite.js',
			array( 'jquery', 'pdf-builder-settings-main' ),
			PDFIB_VERSION,
			true
		);
	}

	$floating_js = PDFIB_PRO_ASSETS_PATH . 'js/floating-save-button.js';
	if ( ! file_exists( $floating_js ) ) {
		return;
	}

	wp_enqueue_script(
		'pdf-builder-floating-save',
		PDFIB_PLUGIN_URL . 'assets/js/floating-save-button.js',
		array(
			'jquery',
			'pdf-builder-settings-main',
			'wp-element',
			'wp-components',
		),
		PDFIB_VERSION . '-' . time(),
		true
	);

	if ( class_exists( 'PdfBuilderNonceManager' ) ) {
		$mgr            = PdfBuilderNonceManager::get_instance();
		$floating_nonce = $mgr->generate_nonce();
	} else {
		$floating_nonce = wp_create_nonce( 'pdfib_ajax' );
	}

	$current_tab = sanitize_key( (string) ( filter_input( INPUT_GET, 'tab', FILTER_DEFAULT ) ?? 'general' ) );

	wp_localize_script(
		'pdf-builder-floating-save',
		'pdfBuilderSettings',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => $floating_nonce,
			'currentTab' => $current_tab,
			'strings'    => array(
				'successMessage'  => __(
					'Paramètres sauvegardés avec succès.',
					'advanced-pdf-invoice-builder'
				),
				'errorMessage'    => __(
					'Erreur lors de la sauvegarde.',
					'advanced-pdf-invoice-builder'
				),
				'connectionError' => __(
					'Erreur de connexion. Veuillez réessayer.',
					'advanced-pdf-invoice-builder'
				),
				'timeoutError'    => __(
					'La requête a expiré. Veuillez réessayer.',
					'advanced-pdf-invoice-builder'
				),
			),
		)
	);
}

add_action( 'admin_enqueue_scripts', 'pdfib_load_settings_assets' );
