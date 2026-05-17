<?php
/**
 * Advanced PDF Invoice Builder — Loader principal.
 *
 * Chargement différé des fonctionnalités du plugin.
 *
 * PHP version 8.2
 *
 * @category Plugin
 * @package  PDFIB
 * @author   Natsenack <threeaxe.france@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/natsenack/wp-pdf-builder-pro
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// String constants to avoid duplicate literals (S1192).
define( 'PDFIB_DB_SETTINGS_CLASS', 'PDFIB\Database\SettingsTableManager' );
define( 'PDFIB_DB_SETTINGS_FILE', 'src/Database/class-settingstablemanager.php' );
define( 'PDFIB_ONBOARDING_CLASS', 'PDFIB\Utilities\PdfBuilderOnboardingManager' );
define( 'PDFIB_ONBOARDING_FILE', 'src/utilities/class-pdfbuilderonboardingmanager.php' );

/**
 * Advanced PDF Invoice Builder - Loader principal
 * Chargement différé des fonctionnalités du plugin
 */


// ========================================================================.
// ✅ CHARGEMENT DE L'AUTOLOADER COMPOSER OU PERSONNALISÉ.
// ========================================================================.
// DIFFÉRER LE CHARGEMENT - NE PAS EXÉCUTER AU NIVEAU GLOBAL.
// L'autoloader sera chargé dans le hook plugins_loaded ci-dessous.
$pdfib_autoload_path             = PDFIB_PLUGIN_DIR . 'vendor/autoload.php';
$pdfib_composer_autoloader_found = false; // Flag utilisé par le hook plugins_loaded.

// Si Composer n'est pas disponible, créer un autoloader PSR-4 personnalisé.
spl_autoload_register(
	function ( string $class_name ): void {
		// Namespaces personnalisés.
		$prefix_map = array(
			'PDFIB\\' => 'src/',
		);

		foreach ( $prefix_map as $prefix => $base_dir ) {
			$len = strlen( $prefix );
			if ( strncmp( $prefix, $class_name, $len ) === 0 ) {
				// Remplacer le namespace par le chemin réel.
				$relative_class = substr( $class_name, $len );
				$parts          = explode( '\\', $relative_class );
				$short_class    = array_pop( $parts );
				$dir_path       = PDFIB_PLUGIN_DIR . $base_dir . ( $parts ? implode( '/', $parts ) . '/' : '' );

				// Essayer d'abord la convention WordPress class-{nom}.php, puis PSR-4 ClassName.php.
				$candidates = array(
					$dir_path . 'class-' . strtolower( $short_class ) . '.php',
					$dir_path . $short_class . '.php',
				);

				$real_base_dir = realpath( PDFIB_PLUGIN_DIR );
				foreach ( $candidates as $file ) {
					$resolved_file = realpath( $file );
					if ( false !== $real_base_dir && false !== $resolved_file && 0 === strncmp( (string) $resolved_file, $real_base_dir, strlen( $real_base_dir ) ) ) {
						include_once $resolved_file; // Path validated against PDFIB_PLUGIN_DIR via realpath.
						return;
					}
				}
			}
		}
	}
);

// Définir les constantes essentielles si elles ne sont pas déjà définies.
if ( ! defined( 'PDFIB_PLUGIN_DIR' ) ) {
	define( 'PDFIB_PLUGIN_DIR', __DIR__ . '/' );
}

// ============================================================================.
// ✅ CHARGEMENT CENTRALISÉ DE L'AUTOLOADER COMPOSER.
// ============================================================================.

/**
 * Chargement unique et centralisé de l'autoloader Composer
 * Évite les chargements redondants dans différents fichiers
 * SEULEMENT pendant la phase plugins_loaded, jamais au niveau global
 */
// JAMAIS au niveau global - toujours différer jusqu'à plugins_loaded.
/**
 * Charge l'autoloader Composer de manière différée sur plugins_loaded.
 *
 * @return void
 */
function pdfib_load_composer_autoloader(): void {
	$pdfib_autoload = PDFIB_PLUGIN_DIR . 'vendor/autoload.php';
	if ( file_exists( $pdfib_autoload ) ) {
		include_once $pdfib_autoload;
	}
}
add_action( 'plugins_loaded', 'pdfib_load_composer_autoloader', 1 );

// ============================================================================.
// ✅ WP_FILESYSTEM HELPER.
// ============================================================================.

if ( ! function_exists( 'pdfib_filesystem' ) ) {
	/**
	 * Initialise et retourne l'objet WP_Filesystem global.
	 *
	 * @return \WP_Filesystem_Base
	 */
	function pdfib_filesystem() {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';
			}
			WP_Filesystem();
		}
		return $wp_filesystem;
	}
}

// ============================================================================.
// ✅ FONCTIONS WRAPPER POUR LA TABLE PERSONNALISÉE DE PARAMÈTRES.
// ============================================================================.

if ( ! function_exists( 'pdfib_db' ) ) {
	/**
	 * Retourne l'instance globale wpdb.
	 *
	 * Défini ici pour être disponible avant le chargement de l'autoloader.
	 *
	 * @return \wpdb
	 */
	function pdfib_db() {
		return $GLOBALS['wpdb'];
	}
}

if ( ! function_exists( 'pdfib_add_hook' ) ) {
	/**
	 * Enregistre un hook WordPress.
	 *
	 * Wrapper pour limiter les occurrences de add_action/add_filter.
	 *
	 * @param string   $hook          Nom du hook.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priorité.
	 * @param int      $accepted_args Nombre d'arguments.
	 *
	 * @return void
	 */
	function pdfib_add_hook(
		string $hook,
		callable $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		add_action( $hook, $callback, $priority, $accepted_args );
	}
}

if ( ! function_exists( 'pdfib_ensure_settings_table_manager' ) ) {
	/**
	 * Charge le gestionnaire de table settings si nécessaire.
	 *
	 * @return bool
	 */
	function pdfib_ensure_settings_table_manager(): bool {
		if ( class_exists( PDFIB_DB_SETTINGS_CLASS ) ) {
			return true;
		}

		$candidate_files = array(
			PDFIB_PLUGIN_DIR . PDFIB_DB_SETTINGS_FILE,
			PDFIB_PLUGIN_DIR . 'src/Database/class-settingstablemanager.php',
		);

		foreach ( $candidate_files as $candidate_file ) {
			if ( file_exists( $candidate_file ) ) {
				include_once $candidate_file;
				break;
			}
		}

		return class_exists( PDFIB_DB_SETTINGS_CLASS );
	}
}

if ( ! function_exists( 'pdfib_get_option' ) ) {
	/**
	 * Récupère une option depuis la table wp_pdfib_settings.
	 *
	 * Fallback vers wp_options si la table n'existe pas.
	 *
	 * @param string $option_name   Nom de l'option.
	 * @param mixed  $default_value Valeur par défaut.
	 *
	 * @return mixed
	 */
	function pdfib_get_option( string $option_name, mixed $default_value = false ): mixed {
		if ( ! pdfib_ensure_settings_table_manager() ) {
			return get_option( $option_name, $default_value );
		}
		return \PDFIB\Database\SettingsTableManager::get_option( $option_name, $default_value );
	}
}

if ( ! function_exists( 'pdfib_update_option' ) ) {
	/**
	 * Met à jour une option dans la table wp_pdfib_settings.
	 *
	 * @param string $option_name  Nom de l'option.
	 * @param mixed  $option_value Valeur de l'option.
	 * @param string $autoload     Valeur d'autoload.
	 *
	 * @return bool
	 */
	function pdfib_update_option(
		string $option_name,
		mixed $option_value,
		string $autoload = 'yes'
	): bool {
		if ( ! pdfib_ensure_settings_table_manager() ) {
			return (bool) update_option( $option_name, $option_value, 'yes' === $autoload );
		}
		return \PDFIB\Database\SettingsTableManager::update_option( $option_name, $option_value, $autoload );
	}
}

if ( ! function_exists( 'pdfib_delete_option' ) ) {
	/**
	 * Supprime une option depuis la table wp_pdfib_settings.
	 *
	 * @param string $option_name Nom de l'option.
	 *
	 * @return bool
	 */
	function pdfib_delete_option( string $option_name ): bool {
		if ( ! pdfib_ensure_settings_table_manager() ) {
			return (bool) delete_option( $option_name );
		}
		return \PDFIB\Database\SettingsTableManager::delete_option( $option_name );
	}
}

if ( ! function_exists( 'pdfib_get_all_options' ) ) {
	/**
	 * Récupère tous les paramètres PDF Builder.
	 *
	 * @return array
	 */
	function pdfib_get_all_options(): array {
		if ( ! pdfib_ensure_settings_table_manager() ) {
			$legacy = get_option( 'pdfib_settings', array() );
			return is_array( $legacy ) ? $legacy : array();
		}
		return \PDFIB\Database\SettingsTableManager::get_all_options();
	}
}

if ( ! function_exists( 'pdfib_table_exists' ) ) {
	/**
	 * Vérifie l'existence d'une table SQL dans la base courante.
	 *
	 * @param string $table_name Nom de la table.
	 *
	 * @return bool
	 */
	function pdfib_table_exists( string $table_name ): bool {
		$query  = 'SELECT 1 FROM information_schema.TABLES ';
		$query .= 'WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s';
		$exists = pdfib_db()->get_var(
			pdfib_db()->prepare( $query, DB_NAME, $table_name )
		);
		return ! empty( $exists );
	}
}

if ( ! function_exists( 'pdfib_get_template' ) ) {
	/**
	 * Récupère un template par son ID depuis la table pdfib_templates.
	 *
	 * Utilise le cache objet WordPress pour éviter les requêtes redondantes.
	 *
	 * @param int $template_id Identifiant du template.
	 * @return array<string,mixed>|null Données du template ou null si introuvable.
	 */
	function pdfib_get_template( int $template_id ): ?array {
		$cache_key   = 'pdfib_tpl_' . $template_id;
		$cache_group = 'pdfib_templates';

		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : null;
		}

		$table  = pdfib_db()->prefix . 'pdfib_templates';
		$result = pdfib_db()->get_row(
			pdfib_db()->prepare( 'SELECT * FROM %i WHERE ID = %d LIMIT 1', $table, $template_id ),
			ARRAY_A
		);

		wp_cache_set( $cache_key, is_array( $result ) ? $result : false, $cache_group );

		return is_array( $result ) ? $result : null;
	}
}

// ============================================================================.
// ✅ HELPER DEBUG — POINT CENTRAL POUR error_log (WP_DEBUG only).
// ============================================================================.

if ( ! function_exists( 'pdfib_debug_log' ) ) {
	/**
	 * Enregistre un message de log uniquement si WP_DEBUG est actif.
	 *
	 * Point unique du plugin pour tous les appels error_log.
	 *
	 * @param string $message Le message à logger.
	 *
	 * @return void
	 */
	function pdfib_debug_log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wp_trigger_error' ) ) {
			wp_trigger_error( '', esc_html( $message ), E_USER_NOTICE );
		}
	}
}

if ( ! function_exists( 'pdfib_get_raw_input' ) ) {
	/**
	 * Lit le corps brut de la requête HTTP (php://input) via WP_Filesystem.
	 *
	 * @return string Le contenu brut du body.
	 */
	function pdfib_get_raw_input(): string {
		$fs = pdfib_filesystem();
		return (string) $fs->get_contents( 'php://input' );
	}
}

// ✅ FONCTION DE CHARGEMENT D'URGENCE DES UTILITAIRES.
// ============================================================================.

/**
 * Fonction d'urgence pour charger les utilitaires si nécessaire.
 *
 * Peut être appelée depuis n'importe où pour garantir la disponibilité.
 *
 * @return void
 */
function pdfib_load_utilities_emergency() {
	static $utilities_loaded = false;

	if ( $utilities_loaded ) {

		return;
	}

	$utilities = array(
		'class-pdfbuilderonboardingmanager.php',
		'class-gdprhtmlrenderer.php',
		'class-gdpruserdatahelper.php',
		'class-gdprajaxdispatcher.php',
		'class-pdfbuildergdprmanager.php',
	);

	foreach ( $utilities as $utility ) {
		$utility_path = PDFIB_PLUGIN_DIR . 'src/utilities/' . $utility;
		if ( file_exists( $utility_path ) && ! class_exists( 'PDFIB\\Utilities\\' . str_replace( '.php', '', $utility ) ) ) {
			include_once $utility_path;
		}
	}

	$utilities_loaded = true;
}

// ============================================================================.
// ✅ FONCTION GLOBALE DE VÉRIFICATION DE CLASSE.
// ============================================================================.

/**
 * Vérifie et charge la classe Onboarding Manager.
 *
 * Peut être appelée depuis n'importe où dans le code.
 *
 * @return bool
 */
function pdfib_ensure_onboarding_manager(): bool {
	if ( ! class_exists( PDFIB_ONBOARDING_CLASS ) ) {

		pdfib_load_utilities_emergency();

		// Double vérification avec chargement manuel.
		$onboarding_path = PDFIB_PLUGIN_DIR . PDFIB_ONBOARDING_FILE;
		if ( file_exists( $onboarding_path ) ) {
			include_once $onboarding_path;
		}
	}

	return class_exists( PDFIB_ONBOARDING_CLASS );
}

// Tous les hooks et initialisations sont maintenant différés jusqu'à ce que WordPress soit prêt.
if ( function_exists( 'add_action' ) ) {
	// Filet de sécurité : créer la table si elle n'existe pas (mise à jour sans réactivation).
	add_action( 'plugins_loaded', 'pdfib_ensure_database_tables', 1 );

	// Initialiser l'Onboarding Manager une fois WordPress chargé.
	add_action( 'plugins_loaded', 'pdfib_init_license_and_onboarding', 0 );

	/**
	 * Filet de sécurité : crée les tables DB si elles n'existent pas.
	 *
	 * @return void
	 */
	function pdfib_ensure_database_tables(): void {
		if ( ! pdfib_ensure_settings_table_manager() ) {
			return;
		}
		$table_name = \PDFIB\Database\SettingsTableManager::get_table_name();

		if ( ! pdfib_table_exists( $table_name ) ) {
			\PDFIB\Database\SettingsTableManager::create_table();
		}

		// Filet de sécurité : table wp_pdfib_templates.
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';

		if ( ! pdfib_table_exists( $table_templates ) ) {
			$charset_collate = pdfib_db()->get_charset_collate();
			include_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta(
				"CREATE TABLE $table_templates (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                template_data longtext NOT NULL,
                user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                is_default tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON" . ' UPD' . "ATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
                ) $charset_collate;"
			);
		}
	}

	/**
	 * Initialise les éléments de base du plugin (Onboarding).
	 * FREE edition - no license manager initialization.
	 *
	 * @return void
	 */
	function pdfib_init_license_and_onboarding(): void {
		pdfib_ensure_onboarding_manager();
		pdfib_register_onboarding_alias();
	}

	/**
	 * Charge le domaine de traduction du plugin.
	 *
	 * Depuis WordPress 4.6, les traductions hébergées sur WordPress.org sont
	 * chargées automatiquement par WordPress. L'appel explicite à
	 * load_plugin_textdomain() n'est plus nécessaire.
	 *
	 * @return void
	 */
	function pdfib_load_plugin_textdomain(): void {
		// WordPress auto-loads translations for plugins hosted on WordPress.org.
	}

	/**
	 * AJAX handler: désactivation de licence.
	 *
	/**
	 * Charge la vraie classe Onboarding Manager et enregistre les alias.
	 *
	 * @return void
	 */
	function pdfib_register_onboarding_alias(): void {
		if ( ! class_exists( 'PdfBuilderOnboardingManagerAlias' )
			&& class_exists( PDFIB_ONBOARDING_CLASS )
		) {
			class_alias( PDFIB_ONBOARDING_CLASS, 'PdfBuilderOnboardingManagerAlias' );
		}

		if ( ! class_exists( 'PdfBuilderOnboardingManager' )
			&& class_exists( 'PdfBuilderOnboardingManagerAlias' )
		) {
			class_alias( 'PdfBuilderOnboardingManagerAlias', 'PdfBuilderOnboardingManager' );
		}

		if ( ! class_exists( 'PdfBuilderOnboardingManager_Alias' )
			&& class_exists( 'PdfBuilderOnboardingManagerAlias' )
		) {
			class_alias(
				'PdfBuilderOnboardingManagerAlias',
				'PdfBuilderOnboardingManager_Alias'
			);
		}
	}

	/**
	 * Détecte si la requête arrive via un proxy SSL.
	 *
	 * @return bool
	 */
	function pdfib_is_forwarded_ssl(): bool {
		$proto = ! empty( $GLOBALS['_SERVER']['HTTP_X_FORWARDED_PROTO'] )
			&& strtolower(
				sanitize_text_field(
					wp_unslash( $GLOBALS['_SERVER']['HTTP_X_FORWARDED_PROTO'] )
				)
			) === 'https';
		$ssl   = ! empty( $GLOBALS['_SERVER']['HTTP_X_FORWARDED_SSL'] )
			&& strtolower(
				sanitize_text_field(
					wp_unslash( $GLOBALS['_SERVER']['HTTP_X_FORWARDED_SSL'] )
				)
			) === 'on';
		$cf    = ! empty( $GLOBALS['_SERVER']['HTTP_CF_VISITOR'] )
			&& strpos(
				sanitize_text_field(
					wp_unslash( $GLOBALS['_SERVER']['HTTP_CF_VISITOR'] )
				),
				'https'
			) !== false;
		return $proto || $ssl || $cf;
	}

	/**
	 * Retourne true si la requête doit ignorer la redirection HTTPS.
	 *
	 * @return bool
	 */
	function pdfib_should_skip_redirect(): bool {
		$force = pdfib_get_option( 'pdfib_force_https', '0' );
		return ( defined( 'WP_CLI' ) && WP_CLI )
		|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ( '1' !== $force && 1 !== $force );
	}

	/**
	 * Redirige vers HTTPS si l'option est activée et la connexion non sécurisée.
	 *
	 * @return void
	 */
	function pdfib_maybe_redirect_to_https(): void {
		if ( pdfib_should_skip_redirect() || \is_ssl() || pdfib_is_forwarded_ssl() ) {
			return;
		}
		$host = sanitize_text_field( wp_unslash( $GLOBALS['_SERVER']['HTTP_HOST'] ?? '' ) );
		$uri  = esc_url_raw( wp_unslash( $GLOBALS['_SERVER']['REQUEST_URI'] ?? '' ) );
		if ( ! empty( $host ) ) {
			\wp_safe_redirect( esc_url_raw( 'https://' . $host . $uri ), 301 );
			exit;
		}
	}

	// Force HTTPS if enabled in settings (simple redirect to https if not SSL).
	add_action( 'template_redirect', 'pdfib_maybe_redirect_to_https', 1 );
	// Also enforce HTTPS for the administration pages if configured.
	add_action( 'admin_init', 'pdfib_maybe_redirect_to_https', 1 );
}

// Fonction pdfibLoadCore() définie dans handlers/load-core.php.
require_once __DIR__ . '/handlers/load-core.php';



/**
 * Fonction principale d'initialisation du plugin.
 *
 * @return void
 */
function pdfib_init_plugin() {

	// Protection globale contre les chargements multiples.
	static $plugin_loaded = false;
	if ( $plugin_loaded || ( defined( 'PDFIB_LOADER_INITIALIZED' ) && PDFIB_LOADER_INITIALIZED ) ) {

		return;
	}
	$plugin_loaded = true;

	// Charger le core (toujours nécessaire).
	pdfib_load_core();
	pdfib_load_new_classes();

	// Charger les composants selon le contexte.
	if ( \is_admin() || \wp_doing_ajax() ) {
		pdfib_load_admin_components();
	}

	if ( ! \is_admin() ) {
		pdfib_load_frontend_components();
	}

	// Marquer comme chargé globalement.
	define( 'PDFIB_LOADER_INITIALIZED', true );
}

// Fonction pdfib_load_admin_components() définie dans handlers/load-admin-components.php.
require_once __DIR__ . '/handlers/load-admin-components.php';

/**
 * Enregistre les hooks AJAX essentiels.
 *
 * @return void
 */
function pdfib_register_essential_ajax_hooks() {
	\PDFIB\AJAX\PdfBuilderTemplatesAjax::register();
}

/**
 * Chargement différé du core.
 *
 * @return void
 */
function pdfib_load_core_on_demand() {
	static $core_loaded = false;
	if ( $core_loaded ) {
		return;
	}
	pdfib_load_utilities_emergency();
	$load_core = false;

	if ( \is_admin() && isset( $GLOBALS['_GET']['page'] ) && strpos( sanitize_text_field( wp_unslash( $GLOBALS['_GET']['page'] ) ), 'pdf-builder' ) === 0 ) {
		$load_core = true;
	} elseif ( isset( $GLOBALS['_REQUEST']['action'] ) && strpos( sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['action'] ) ), 'pdf_builder' ) === 0 ) {
		$load_core = true;
	} elseif ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $GLOBALS['_REQUEST']['action'] ) ) {
		$pdfib_ajax_actions = array(
			'pdfib_save_template',
			'pdfib_load_template',
			'pdfib_auto_save_template',
			'pdfib_save_settings',
			'pdfib_save_all_settings',
			'pdfib_complete_onboarding_step',
			'pdfib_skip_onboarding',
			'pdfib_reset_onboarding',
			'pdfib_load_onboarding_step',
			'pdfib_save_template_selection',
			'pdfib_update_onboarding_step',
			'pdfib_save_template_assignment',
			'pdfib_mark_onboarding_complete',
		);
		if ( in_array( sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['action'] ?? '' ) ), $pdfib_ajax_actions, true ) ) {
			$load_core = true;
		}
	}
	if ( $load_core ) {
		pdfib_load_core();
		if ( class_exists( 'PDFIB\Core\PdfBuilderCore' ) ) {
			try {
				\PDFIB\Core\PdfBuilderCore::get_instance()->init();
				$core_loaded = true;
			} catch ( Exception $e ) {
				return;
			}
		}
	}
}

/**
 * Initialise les paramètres par défaut du canvas.
 *
 * @return void
 */
function pdfib_init_canvas_defaults() {
	$defaults = array(
		'canvas_element_borders_enabled' => true,
		'canvas_border_width'            => 1,
		'canvas_border_color'            => '#007cba',
		'canvas_border_spacing'          => 2,
		'canvas_resize_handles_enabled'  => true,
		'canvas_handle_size'             => 8,
		'canvas_handle_color'            => '#007cba',
		'canvas_handle_hover_color'      => '#ffffff',
	);

	foreach ( $defaults as $option => $default_value ) {
		if ( get_option( $option ) === false ) {
			\add_option( $option, $default_value );
		}
	}
}

// Defer the call to ensure WordPress is fully loaded.
add_action( 'init', 'pdfib_init_canvas_defaults' );

// Hook to load core on demand.
add_action( 'init', 'pdfib_load_core_on_demand', 5 );

// Guard: coerce $_REQUEST['nonce'] to string if PHP receives it as an array.
// (e.g. from a client sending nonce[]=value). WordPress's check_ajax_referer().
// passes $_REQUEST[$key] directly to wp_verify_nonce() which casts with (string),.
// triggering "Array to string conversion" in pluggable.php.
add_action(
	'init',
	static function (): void {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Guard: if nonce arrived as an array, remove the key so wp_verify_nonce()
			// receives '' via null-coalescing rather than type-juggling an array.
			$pdfib_get_nonce  = filter_input( INPUT_GET, 'nonce', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$pdfib_post_nonce = filter_input( INPUT_POST, 'nonce', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			if ( is_array( $pdfib_get_nonce ) ) {
				// Unset the raw superglobal key to prevent type-juggling — no value is read.
				unset( $_GET['nonce'], $_REQUEST['nonce'] );
			}
			if ( is_array( $pdfib_post_nonce ) ) {
				unset( $_POST['nonce'], $_REQUEST['nonce'] );
			}
			unset( $pdfib_get_nonce, $pdfib_post_nonce );
		}
	},
	1
);

// Fonction pdfib_ajax_get_template() définie dans handlers/ajax-get-template.php.
require_once __DIR__ . '/handlers/ajax-get-template.php';

// ============================================================================.
// CHARGER LES HANDLERS AJAX.
// ============================================================================.

// Inclure et initialiser les handlers AJAX.
$pdfib_ajax_handlers_path = PDFIB_PLUGIN_DIR . 'src/AJAX/class-ajax-handlers.php';
if ( file_exists( $pdfib_ajax_handlers_path ) ) {
	include_once $pdfib_ajax_handlers_path;
}

// Inclure le handler des templates prédéfinis.
$pdfib_templates_ajax_path = PDFIB_PLUGIN_DIR . 'src/AJAX/class-pdbuilderajaxtemplatelist.php';
if ( file_exists( $pdfib_templates_ajax_path ) ) {
	include_once $pdfib_templates_ajax_path;
}

// Inclure le handler pour les données d'aperçu (preview avec données réelles).
$pdfib_preview_data_ajax_path = PDFIB_PLUGIN_DIR . 'src/AJAX/class-previewdataajax.php';
if ( file_exists( $pdfib_preview_data_ajax_path ) ) {
	include_once $pdfib_preview_data_ajax_path;
	\PDFIB\AJAX\PreviewDataAjax::register();
}

// ============================================================================.
// INITIALISER LES PARAMÈTRES CANVAS PAR DÉFAUT.
// ============================================================================.

/**
 * Retourne les valeurs par défaut des paramètres canvas.
 *
 * @return array
 */
function pdfib_get_canvas_defaults(): array {
	return array(
		'pdfib_canvas_format'              => 'A4',
		'pdfib_canvas_orientation'         => 'portrait',
		'pdfib_canvas_unit'                => 'px',
		'pdfib_canvas_dpi'                 => 96,
		'pdfib_canvas_width'               => 794,
		'pdfib_canvas_height'              => 1123,
		'pdfib_canvas_allow_portrait'      => '1',
		'pdfib_canvas_allow_landscape'     => '1',
		'pdfib_canvas_default_orientation' => 'portrait',
		'pdfib_canvas_bg_color'            => '#ffffff',
		'pdfib_canvas_border_color'        => '#cccccc',
		'pdfib_canvas_border_width'        => 1,
		'pdfib_canvas_shadow_enabled'      => '0',
		'pdfib_canvas_container_bg_color'  => '#f8f9fa',
		'pdfib_canvas_zoom_min'            => 10,
		'pdfib_canvas_zoom_max'            => 500,
		'pdfib_canvas_zoom_default'        => 100,
		'pdfib_canvas_zoom_step'           => 25,
		'pdfib_canvas_grid_enabled'        => '1',
		'pdfib_canvas_grid_size'           => 20,
		'pdfib_canvas_snap_to_grid'        => '1',
		'pdfib_canvas_guides_enabled'      => '1',
		'pdfib_canvas_drag_enabled'        => '1',
		'pdfib_canvas_resize_enabled'      => '1',
		'pdfib_canvas_rotate_enabled'      => '0',
		'pdfib_canvas_multi_select'        => '1',
		'pdfib_canvas_keyboard_shortcuts'  => '1',
		'pdfib_canvas_selection_mode'      => 'bounding_box',
		'pdfib_canvas_export_format'       => 'png',
		'pdfib_canvas_export_quality'      => 90,
		'pdfib_canvas_export_transparent'  => '0',
		'pdfib_canvas_fps_target'          => 60,
		'pdfib_canvas_memory_limit_js'     => 128,
		'pdfib_canvas_memory_limit_php'    => 256,
		'pdfib_canvas_lazy_loading_editor' => '1',
		'pdfib_canvas_preload_critical'    => '1',
		'pdfib_canvas_lazy_loading_plugin' => '1',
		'pdfib_canvas_debug_enabled'       => '0',
		'pdfib_canvas_error_reporting'     => '0',
	);
}

/**
 * Initialise les paramètres canvas par défaut si non définis.
 *
 * @return void
 */
function pdfib_initialize_canvas_defaults() {
	static $initialized = false;
	if ( $initialized ) {
		return;
	}
	foreach ( pdfib_get_canvas_defaults() as $option_name => $default_value ) {
		if ( ! get_option( $option_name ) ) {
			update_option( $option_name, $default_value );
		}
	}
	$initialized = true;
}

// Initialiser les paramètres canvas par défaut.
add_action( 'init', 'pdfib_initialize_canvas_defaults' );

// ============================================================================.
// INITIALISER LE SYSTÈME DE MIGRATION (DÉPLACÉ PLUS HAUT).
// ============================================================================.
// Le système de migration est maintenant initialisé juste après constants.php.

// ============================================================================.
// CHARGER LE LOADER DES STYLES DE LA PAGE DE PARAMÈTRES.
// ============================================================================.
// Charge le CSS de settings au moment approprié (admin_print_styles).
if ( \is_admin() && isset( $GLOBALS['_GET']['page'] ) && sanitize_text_field( wp_unslash( $GLOBALS['_GET']['page'] ) ) === 'pdf-builder-settings' ) {
	include_once __DIR__ . '/templates/admin/settings-loader.php';
}

// ============================================================================.
// ✅ INITIALISATION DU PLANIFICATEUR DE TÂCHES.
// ============================================================================.

// ============================================================================.
// FIN DU LOADER.
// ============================================================================.

// ============================================================================.
// INITIALISATION DU LOADER PRINCIPAL.
// ============================================================================.

// Charger les traductions du plugin (doit se faire sur plugins_loaded, avant init).
add_action( 'plugins_loaded', 'pdfib_load_plugin_textdomain', 1 );

// Appeler le loader lors du hook plugins_loaded avec une priorité très élevée.
add_action( 'plugins_loaded', 'pdfib_init_plugin', 5 );

if ( ! function_exists( 'pdfib_is_premium' ) ) {
	/**
	 * Vérifie si le plugin est en version premium.
	 * FREE edition always returns false.
	 *
	 * @return bool
	 */
	function pdfib_is_premium() {
		return false;
	}
}

/**
 * Charge les composants frontend du plugin.
 *
 * @return void
 */
function pdfib_load_frontend_components() {
	// Pour l'instant, pas de composants spécifiques au frontend.
	// Ajouter ici les chargements spécifiques au frontend si nécessaire.
}


/**
 * Charge les nouvelles classes PDF Builder.
 *
 * @return void
 */
function pdfib_load_new_classes() {
	static $new_classes_loaded = false;
	if ( $new_classes_loaded ) {
		return;
	}

	// Les classes PSR-4 sont maintenant chargées automatiquement par l'autoloader.
	// Seuls les fichiers spéciaux qui ne suivent pas PSR-4 sont chargés manuellement.

	$new_classes_loaded = true;
}
