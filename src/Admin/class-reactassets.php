<?php
/**
 * Advanced PDF Invoice Builder V2 - Enregistrement des assets React.
 *
 * @package PDFIB\V2
 */

namespace PDFIB\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Gère l'enregistrement des assets React de l'éditeur.
 */
class ReactAssets {

	/**
	 * Enregistre les hooks nécessaires.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts' ), 1 );
		\add_action( 'admin_print_scripts', array( self::class, 'add_wp_util_dependency' ) );
	}

	/**
	 * Ajoute wp-util comme dépendance du script general_script si présent.
	 *
	 * @return void
	 */
	public static function add_wp_util_dependency(): void {
		global $wp_scripts;

		if ( isset( $wp_scripts->registered['general_script'] ) ) {
			$wp_scripts->registered['general_script']->deps[] = 'wp-util';
		}
	}

	/**
	 * Enregistre les scripts et styles React V2.
	 *
	 * @param string $page Page d'administration courante.
	 * @return void
	 */
	public static function enqueue_scripts( string $page ): void {
		\wp_enqueue_script( 'wp-util' );
		\wp_enqueue_script( 'wp-api' );

		self::register_wp_preferences_compat();

		if ( 'admin.php?page=pdf-builder-react-editor' !== $page ) {
			return;
		}

		\wp_enqueue_media();
		$plugin_url = PDFIB_PLUGIN_URL;
		$version    = '2.0.0';
		self::enqueue_editor_core_assets( $plugin_url, $version );
		self::enqueue_editor_utility_scripts( $plugin_url, $version );
	}

	/**
	 * Enregistre un polyfill pour WP Preferences.
	 *
	 * @return void
	 */
	private static function register_wp_preferences_compat(): void {
		$wp_prefs_script_file = PDFIB_PLUGIN_DIR . 'assets/js/pdfib-wp-prefs-compat.js';
		$wp_prefs_script      = file_exists( $wp_prefs_script_file )
			? (string) pdfib_filesystem()->get_contents( $wp_prefs_script_file )
			: '';

		wp_add_inline_script( 'wp-util', $wp_prefs_script, 'before' );
	}

	/**
	 * Enregistre les assets principaux de l'éditeur.
	 *
	 * @param string $plugin_url URL du plugin.
	 * @param string $version Version du plugin.
	 * @return void
	 */
	private static function enqueue_editor_core_assets( string $plugin_url, string $version ): void {
		\wp_enqueue_style(
			'pdf-builder-react-v2',
			$plugin_url . 'assets/css/pdf-builder-react.min.css',
			array(),
			$version
		);

		\wp_enqueue_script( 'jquery' );

		\wp_register_script(
			'pdf-preview-api-client',
			$plugin_url . 'assets/js/pdf-preview-api-client.min.js',
			array( 'jquery' ),
			$version,
			false
		);
		\wp_enqueue_script( 'pdf-preview-api-client' );

		\wp_enqueue_script(
			'pdf-builder-vendors-v2',
			$plugin_url . 'assets/js/vendors.min.js',
			array(),
			$version,
			true
		);

		\wp_enqueue_script(
			'pdf-builder-react-app-v2',
			$plugin_url . 'assets/js/pdf-builder-react.min.js',
			array( 'pdf-builder-vendors-v2', 'wp-util', 'pdf-preview-api-client' ),
			$version,
			true
		);

		\wp_enqueue_script(
			'pdf-builder-react-wrapper-v2',
			$plugin_url . 'assets/js/pdf-builder-react-wrapper.min.js',
			array( 'pdf-builder-react-app-v2', 'wp-util' ),
			$version,
			true
		);
	}

	/**
	 * Enregistre les scripts utilitaires de l'éditeur.
	 *
	 * @param string $plugin_url URL du plugin.
	 * @param string $version Version du plugin.
	 * @return void
	 */
	private static function enqueue_editor_utility_scripts( string $plugin_url, string $version ): void {
		\wp_enqueue_script(
			'pdf-builder-ajax-throttle',
			$plugin_url . 'assets/js/ajax-throttle.min.js',
			array( 'jquery' ),
			$version,
			true
		);

		\wp_enqueue_script(
			'pdf-builder-notifications',
			$plugin_url . 'assets/js/notifications.min.js',
			array( 'jquery' ),
			$version,
			true
		);

		\wp_enqueue_script(
			'pdf-builder-wrap',
			$plugin_url . 'assets/js/pdf-builder-wrap.min.js',
			array( 'jquery' ),
			$version,
			true
		);

		\wp_enqueue_script(
			'pdf-builder-init',
			$plugin_url . 'assets/js/pdf-builder-init.min.js',
			array( 'jquery' ),
			$version,
			true
		);

		\wp_enqueue_script(
			'pdf-builder-react-init',
			$plugin_url . 'assets/js/pdf-builder-react-init.min.js',
			array( 'pdf-builder-react-wrapper-v2' ),
			$version,
			true
		);
	}
}

ReactAssets::register();
