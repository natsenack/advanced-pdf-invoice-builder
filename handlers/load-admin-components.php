<?php
/**
 * Chargement des composants d'administration PDF Builder.
 *
 * PHP version 8.2
 *
 * @category Plugin
 * @package  PDFIB
 * @author   Natsenack <threeaxe.france@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/natsenack/wp-pdf-builder-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Charge les composants d'administration du plugin PDF Builder.
 * Extrait pour respecter la limite de complexité des fonctions.
 *
 * @return void
 */
function pdfib_load_admin_components() {
	pdfib_load_admin_files();
	pdfib_init_admin_managers();
	pdfib_register_preference_helpers();
	pdfib_init_deferred_admin_managers();
	pdfib_register_essential_ajax_hooks();
	pdfib_init_admin_interface();
}

/**
 * Charge les fichiers PHP d'administration non couverts par l'autoloader PSR-4.
 *
 * @return void
 */
function pdfib_load_admin_files(): void {
	$files = array(
		'src/Managers/class-pdfbuilderthumbnailmanager.php',
		'src/Security/class-securitylimitshandler.php',
		'src/Core/class-pdfbuildernotificationmanager.php',
	);
	foreach ( $files as $f ) {
		$path = PDFIB_PLUGIN_DIR . $f;
		if ( file_exists( $path ) ) {
			include_once $path;
		}
	}
}

/**
 * Initialise les managers admin (sécurité, canvas, notifications, préférences).
 *
 * @return void
 */
function pdfib_init_admin_managers(): void {
	// Validateur de sécurité.
	if ( class_exists( 'PDFIB\\Core\\PdfBuilderSecurityValidator' ) ) {
		\PDFIB\Core\PdfBuilderSecurityValidator::get_instance()->init();
	}

	// Hooks AJAX Canvas.
	if ( class_exists( 'PDFIB\\Admin\\CanvasAJAXHandler' ) ) {
		\PDFIB\Admin\CanvasAJAXHandler::register_hooks();
	}

	// Notifications.
	if ( class_exists( 'PdfBuilderNotificationManager' ) ) {
		PdfBuilderNotificationManager::get_instance();
	}

	// Préférences éditeur.
	if ( class_exists( 'PDFIB\\Core\\PDFEditorPreferences' ) ) {
		\PDFIB\Core\PDFEditorPreferences::get_instance();
	}
}

/**
 * Définit les fonctions globales de préférences utilisateur si absentes.
 *
 * @return void
 */
function pdfib_register_preference_helpers(): void {
	if ( function_exists( 'pdfib_get_user_preference' ) ) {
		return;
	}

	/**
	 * Retourne une préférence utilisateur par clé.
	 *
	 * @param string $key     Clé de préférence.
	 * @param mixed  $initial Valeur par défaut.
	 *
	 * @return mixed
	 */
	function pdfib_get_user_preference( string $key, mixed $initial = null ): mixed {
		$all_prefs = \PDFIB\Core\PDFEditorPreferences::get_instance()
			->get_preferences();
		return isset( $all_prefs[ $key ] ) ? $all_prefs[ $key ] : $initial;
	}

	/**
	 * Définit une préférence utilisateur.
	 *
	 * @param string $key   Clé de préférence.
	 * @param mixed  $value Valeur à enregistrer.
	 *
	 * @return mixed
	 */
	function pdfib_set_user_preference( string $key, mixed $value ): mixed {
		$preferences     = \PDFIB\Core\PDFEditorPreferences::get_instance();
		$current         = $preferences->get_preferences();
		$current[ $key ] = $value;
		return $preferences->save_preferences( $current );
	}

	/**
	 * Retourne toutes les préférences utilisateur.
	 *
	 * @return array
	 */
	function pdfib_get_all_user_preferences(): array {
		return \PDFIB\Core\PDFEditorPreferences::get_instance()
			->get_preferences();
	}
}

/**
 * Enregistre l'init différée onboarding et gestionnaire RGPD (hook 'init').
 *
 * @return void
 */
function pdfib_init_deferred_admin_managers(): void {
	add_action( 'init', 'pdfib_init_onboarding_deferred', 5 );
	add_action( 'init', 'pdfib_init_gdpr_managers', 5 );
}

/**
 * Initialise l'Onboarding Manager de manière différée.
 *
 * @return void
 */
function pdfib_init_onboarding_deferred(): void {
	if ( pdfib_ensure_onboarding_manager() ) {
		\PDFIB\Utilities\PdfBuilderOnboardingManager::get_instance();
	}
}

/**
 * Initialise les gestionnaires RGPD de manière différée.
 *
 * @return void
 */
function pdfib_init_gdpr_managers(): void {
	$utilities_dir = PDFIB_PLUGIN_DIR . 'src/utilities/';
	$gdpr_files    = array(
		'class-gdprhtmlrenderer.php',
		'class-gdpruserdatahelper.php',
		'class-gdprajaxdispatcher.php',
		'class-pdfbuildergdprmanager.php',
	);
	foreach ( $gdpr_files as $f ) {
		$path = $utilities_dir . $f;
		if ( file_exists( $path ) ) {
			include_once $path;
		}
	}
	if ( class_exists( 'PDFIB\\Utilities\\PdfBuilderGdprManager' ) ) {
		\PDFIB\Utilities\PdfBuilderGdprManager::get_instance();
	}
}

/**
 * Initialise l'interface d'administration (PdfBuilderCore + PdfBuilderAdmin).
 *
 * @return void
 */
function pdfib_init_admin_interface(): void {
	if ( ! did_action( 'init' ) ) {
		add_action( 'init', 'pdfib_init_admin_interface', 5 );
		return;
	}

	if ( ! class_exists( 'PDFIB\\Core\\PdfBuilderCore' ) ) {
		return;
	}

	$core = \PDFIB\Core\PdfBuilderCore::get_instance();
	if ( method_exists( $core, 'init' ) ) {
		$core->init();
	}

	if ( ! class_exists( 'PDFIB\Admin\PdfBuilderAdmin' ) ) {
		$admin_file = PDFIB_PLUGIN_DIR . 'src/Admin/class-pdfbuilderadmin.php';
		if ( file_exists( $admin_file ) ) {
			include_once $admin_file;
		}
	}

	if ( class_exists( 'PDFIB\Admin\PdfBuilderAdmin' ) ) {
		try {
			\PDFIB\Admin\PdfBuilderAdmin::get_instance( $core );
		} catch ( Exception $e ) {
			wp_die(
				esc_html( $e->getMessage() ),
				'',
				array( 'response' => 500 )
			);
		}
	}
}
