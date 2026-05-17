<?php
/**
 * Chargement du noyau PDF Builder.
 *
 * @package PDFIB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Charge le noyau du plugin PDF Builder.
 * Extrait du fichier d'initialisation pour respecter la limite de complexité des fonctions.
 */
function pdfib_load_core() {
	static $loaded = false;
	if ( $loaded ) {
		return;
	}

	pdfib_load_core_files();
	pdfib_register_wp_api_polyfill();
	pdfib_register_settings_page_scripts();
	pdfib_register_react_editor_scripts();

	$loaded = true;
}

/**
 * Charge tous les fichiers PHP nécessaires au noyau.
 */
function pdfib_load_core_files(): void {
	// Constantes et configuration.
	foreach ( array( 'src/Core/core/constants.php' ) as $f ) {
		if ( file_exists( PDFIB_PLUGIN_DIR . $f ) ) {
			require_once PDFIB_PLUGIN_DIR . $f;
		}
	}

	// Classe principale PdfBuilderCore.
	if ( file_exists( PDFIB_PLUGIN_DIR . 'src/Core/class-pdfbuildercore.php' ) && ! class_exists( 'PDFIB\\Core\\PdfBuilderCore' ) ) {
		require_once PDFIB_PLUGIN_DIR . 'src/Core/class-pdfbuildercore.php';
	}

	// Utilitaires essentiels.
	foreach ( array( 'class-pdfbuilderonboardingmanager.php', 'class-gdprhtmlrenderer.php', 'class-gdpruserdatahelper.php', 'class-gdprajaxdispatcher.php', 'class-pdfbuildergdprmanager.php' ) as $u ) {
		$path = PDFIB_PLUGIN_DIR . 'src/utilities/' . $u;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}

	pdfib_load_core_classes_and_support();
}

/**
 * Charge les classes Core, le handler AJAX unifié et les fichiers de support.
 */
function pdfib_load_core_classes_and_support(): void {
	// Classes Core essentielles.
	foreach (
		array(
			'class-pdfbuildersecurityvalidator.php',
			'class-pdfbuildernoncemanager.php',
			'class-pdfbuilderunifiedajaxhandler.php',
		) as $cls
	) {
		$path = PDFIB_PLUGIN_DIR . 'src/Core/' . $cls;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}

	// Initialiser le handler AJAX unifié.
	if ( class_exists( 'PdfBuilderUnifiedAjaxHandler' ) ) {
		PdfBuilderUnifiedAjaxHandler::get_instance();
	}

	// Fichiers de support (TemplateDefaults, sanitizer, mappings).
	foreach (
		array(
			'src/Core/class-templatedefaults.php',
		) as $f
	) {
		if ( file_exists( PDFIB_PLUGIN_DIR . $f ) ) {
			require_once PDFIB_PLUGIN_DIR . $f;
		}
	}

	// Administration et Canvas handler.
	if ( file_exists( PDFIB_PLUGIN_DIR . 'src/Admin/class-pdfbuilderadmin.php' ) && ! class_exists( 'PDFIB\Admin\PdfBuilderAdmin' ) ) {
		require_once PDFIB_PLUGIN_DIR . 'src/Admin/class-pdfbuilderadmin.php';
	}
	if ( file_exists( PDFIB_PLUGIN_DIR . 'src/Admin/class-canvasajaxhandler.php' ) ) {
		require_once PDFIB_PLUGIN_DIR . 'src/Admin/class-canvasajaxhandler.php';
	}
}

/**
 * Enregistre le polyfill d'initialisation de l'objet wp global.
 * Évite les erreurs "wp is not defined" dans le JS front.
 */
function pdfib_register_wp_api_polyfill(): void {
	$pdfib_wp_init_js = 'if(typeof window.wp==="undefined"){window.wp=' .
		'{api:{models:{},collections:{},views:{}},' .
		'ajax:{send:function(){return{done:function(){},fail:function(){}};}},' .
		'media:{controller:{Library:function(){},FeaturedImage:function(){}},' .
		'view:{MediaFrame:{Select:function(){},Post:function(){}}}},' .
		'util:{parseArgs:function(){return{};}},template:function(){return"";}' .
		'};}';

	add_action( 'admin_enqueue_scripts', 'pdfib_enqueue_wp_polyfill', 0 );
}

/**
 * Enregistre le polyfill wp global si absent.
 *
 * @return void
 */
function pdfib_enqueue_wp_polyfill(): void {
	$pdfib_wp_init_js = 'if(typeof window.wp==="undefined"){window.wp=' .
		'{api:{models:{},collections:{},views:{}},' .
		'ajax:{send:function(){return{done:function(){},fail:function(){}};}},' .
		'media:{controller:{Library:function(){},FeaturedImage:function(){}},' .
		'view:{MediaFrame:{Select:function(){},Post:function(){}}}},' .
		'util:{parseArgs:function(){return{};}},template:function(){return"";}' .
		'};}';
	if ( ! \wp_script_is( 'pdfib-wp-polyfill', 'registered' ) ) {
		\wp_register_script( 'pdfib-wp-polyfill', false, array(), PDFIB_VERSION, false );
	}
	\wp_enqueue_script( 'pdfib-wp-polyfill' );
	\wp_add_inline_script( 'pdfib-wp-polyfill', $pdfib_wp_init_js );
}

/**
 * Enregistre les scripts pour la page de paramètres (pdf-builder-settings).
 */
function pdfib_register_settings_page_scripts(): void {
	add_action( 'admin_enqueue_scripts', 'pdfib_localize_settings_page_scripts' );
}

/**
 * Localise les scripts de la page de paramètres.
 *
 * @return void
 */
function pdfib_localize_settings_page_scripts(): void {
	if ( ! isset( $GLOBALS['_GET']['page'] ) || 'pdf-builder-settings' !== $GLOBALS['_GET']['page'] ) {
		return;
	}

	\wp_localize_script(
		'jquery',
		'pdfib_ajax',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'pdfib_ajax' ),
		)
	);

	\wp_localize_script(
		'jquery',
		'pdfBuilderAjax',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pdfib_ajax' ),
			'debug'   => array(
				'enabled' => true,
				'level'   => 'info',
				'console' => true,
				'server'  => false,
			),
			'strings' => array(
				'loading' => __( 'Chargement...', 'advanced-pdf-invoice-builder' ),
				'error'   => __( 'Erreur', 'advanced-pdf-invoice-builder' ),
				'success' => __( 'Succès', 'advanced-pdf-invoice-builder' ),
				'saving'  => __( 'Sauvegarde en cours...', 'advanced-pdf-invoice-builder' ),
			),
		)
	);
}

/**
 * Enregistre les scripts pour l'éditeur React (pdf-builder-react-editor).
 */
function pdfib_register_react_editor_scripts(): void {
	add_action(
		'admin_enqueue_scripts',
		function ( $hook ) {
			$on_react_editor = 'pdf-builder_page_pdf-builder-react-editor' === $hook ||
				( isset( $GLOBALS['_GET']['page'] ) && 'pdf-builder-react-editor' === $GLOBALS['_GET']['page'] );
			if ( ! $on_react_editor ) {
				return;
			}

			$bundle_url = PDFIB_PLUGIN_URL . 'assets/js/pdf-builder-react-wrapper.min.js';
			\wp_enqueue_script(
				'pdf-builder-react-bundle',
				$bundle_url,
				array( 'wp-element', 'jquery' ),
				PDFIB_VERSION . '-' . time(),
				true
			);

			$template_id   = isset( $GLOBALS['_GET']['template_id'] ) ? intval( wp_unslash( $GLOBALS['_GET']['template_id'] ) ) : 1;
			$localize_data = array(
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'pdfib_ajax' ),
				'version'    => PDFIB_VERSION,
				'templateId' => $template_id,
				'isEdit'     => $template_id > 0,
				'timestamp'  => time(),
				'debug'      => WP_DEBUG,
				'strings'    => array(
					'loading' => __( 'Chargement...', 'advanced-pdf-invoice-builder' ),
					'error'   => __( 'Erreur', 'advanced-pdf-invoice-builder' ),
					'success' => __( 'Succès', 'advanced-pdf-invoice-builder' ),
				),
			);

			\wp_localize_script( 'pdf-builder-react-bundle', 'pdfBuilderAjax', $localize_data );
		}
	);
}
