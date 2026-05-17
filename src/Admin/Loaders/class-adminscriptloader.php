<?php
/**
 * Advanced PDF Invoice Builder - Admin Script Loader
 *
 * Responsable du chargement des scripts et styles d'administration.
 *
 * PHP version 8.2
 *
 * @category Plugin
 * @package  PDFIB\Admin\Loaders
 * @author   Natsenack <threeaxe.france@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/natsenack/wp-pdf-builder-pro
 */

namespace PDFIB\Admin\Loaders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PDFIB_ASSETS_DIR' ) ) {
	$pdfib_constants_file = dirname( dirname( dirname( __DIR__ ) ) )
		. '/src/Core/core/constants.php';
	if ( file_exists( $pdfib_constants_file ) ) {
		include_once $pdfib_constants_file;
	}
}

/**
 * Classe responsable du chargement des scripts et styles admin.
 *
 * @category Plugin
 * @package  PDFIB\Admin\Loaders
 * @author   Natsenack <threeaxe.france@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/natsenack/wp-pdf-builder-pro
 */
class AdminScriptLoader {


	/**
	 * Chemin relatif JS des notifications.
	 */
	const NOTIFICATIONS_JS = 'js/notifications.min.js';

	/**
	 * Déclaration JS des paramètres de debug.
	 */
	const JS_DEBUG_SETTINGS_VAR = 'window.pdfBuilderDebugSettings = ';

	/**
	 * Type MIME JavaScript (texte).
	 */
	const MIME_TEXT_JS = 'text/javascript';

	/**
	 * Attribut type JS avec guillemets simples.
	 */
	const MIME_TEXT_JS_SQ = "type='text/javascript'";

	/**
	 * Attribut type JS avec guillemets doubles.
	 */
	const MIME_TEXT_JS_DQ = 'type="text/javascript"';

	/**
	 * Attribut type template avec guillemets doubles.
	 */
	const MIME_TEXT_TMPL_DQ = 'type="text/template"';

	/**
	 * Attribut type template avec guillemets simples.
	 */
	const MIME_TEXT_TMPL_SQ = "type='text/template'";

	/**
	 * Instance de la classe principale.
	 *
	 * @var mixed
	 */
	private mixed $admin;

	/**
	 * Délégué pour les scripts de l'éditeur React.
	 *
	 * @var ReactEditorScriptEnqueuer
	 */
	private ReactEditorScriptEnqueuer $react_editor_enqueuer;

	/**
	 * Constructeur.
	 *
	 * @param mixed $admin Instance de la classe principale.
	 */
	public function __construct( mixed $admin ) {
		$this->admin                 = $admin;
		$this->react_editor_enqueuer = new \PDFIB\Admin\Loaders\ReactEditorScriptEnqueuer(
			$admin
		);

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'pdfib_load_admin_scripts' ),
			20
		);
		// FREE edition: no upgrade modals.
	}

	/**
	 * Injecte les modaux d'upgrade premium dans le footer admin.
	 *
	 * @return void
	 */
	public function pdfib_render_upgrade_modals(): void {
		// FREE edition: no upgrade modals. Reserved for PRO version.
	}

	/**
	 * Enregistre le chunk vendors webpack partagé par les bundles React admin.
	 *
	 * @return void
	 */
	private function pdfib_register_react_vendors(): void {
		if ( wp_script_is( 'pdfib-vendors', 'registered' ) ) {
			return;
		}
		wp_register_script(
			'pdfib-vendors',
			PDFIB_PRO_ASSETS_URL . 'js/vendors.min.js',
			array(),
			PDFIB_PRO_VERSION,
			true
		);
	}

	/**
	 * Charge les scripts et styles d'administration.
	 *
	 * @param string|null $hook Identifiant de la page courante.
	 *
	 * @return void
	 */
	public function pdfib_load_admin_scripts( ?string $hook = null ) {
		add_filter(
			'script_loader_tag',
			array( $this, 'pdfib_fix_elementor_templates' ),
			10,
			3
		);

		$current_page = isset( $GLOBALS['_GET']['page'] )
			? sanitize_text_field(
				wp_unslash( $GLOBALS['_GET']['page'] )
			)
			: '';

		$admin_css_path = PDFIB_PRO_ASSETS_PATH . 'css/pdf-builder-admin.css';

		wp_enqueue_style(
			'pdf-builder-admin',
			PDFIB_PRO_ASSETS_URL . 'css/pdf-builder-admin.css',
			array(),
			file_exists( $admin_css_path ) ? (string) filemtime( $admin_css_path ) : PDFIB_PRO_VERSION
		);

		$this->pdfib_enqueue_conditional_page_styles( $current_page );

		$current_url = isset( $GLOBALS['_SERVER']['REQUEST_URI'] )
			? esc_url_raw(
				wp_unslash( $GLOBALS['_SERVER']['REQUEST_URI'] )
			)
			: '';

		if ( false !== strpos( $current_url, 'pdf-builder' ) ) {
			$this->pdfib_enqueue_common_pdf_builder_scripts( $current_page );
		}

		$this->pdfib_enqueue_react_editor_section( $hook );
	}

	/**
	 * Charge les styles CSS spécifiques à la page courante.
	 *
	 * @param string $current_page Identifiant de la page courante.
	 *
	 * @return void
	 */
	private function pdfib_enqueue_conditional_page_styles(
		string $current_page
	): void {
		if ( 'advanced-pdf-invoice-builder' === $current_page ) {
			$dashboard_css = PDFIB_PRO_ASSETS_PATH . 'css/dashboard-css.min.css';
			$dashboard_js  = PDFIB_PRO_ASSETS_PATH . 'js/dashboard-page-react.min.js';
			wp_enqueue_style(
				'pdf-builder-dashboard',
				PDFIB_PRO_ASSETS_URL . 'css/dashboard-css.min.css',
				array(),
				file_exists( $dashboard_css ) ? (string) filemtime( $dashboard_css ) : PDFIB_PRO_VERSION
			);
			$this->pdfib_register_react_vendors();
			wp_enqueue_script(
				'pdfib-dashboard-page',
				PDFIB_PRO_ASSETS_URL . 'js/dashboard-page-react.min.js',
				array( 'pdfib-vendors' ),
				file_exists( $dashboard_js ) ? (string) filemtime( $dashboard_js ) : PDFIB_PRO_VERSION,
				true
			);
		}

		if ( 'pdf-builder-templates' === $current_page ) {
			wp_enqueue_style(
				'pdf-builder-templates-page',
				PDFIB_PRO_ASSETS_URL . 'css/templates-page-css.min.css',
				array(),
				PDFIB_PRO_VERSION
			);
			wp_enqueue_script(
				'pdf-builder-admin-templates',
				PDFIB_PRO_ASSETS_URL . 'js/admin-templates.js',
				array( 'jquery' ),
				PDFIB_PRO_VERSION,
				true
			);

			// Inject canvas options (DPI / formats / orientations) from plugin settings
			// so that the template modal dropdowns reflect what the admin configured.
			$pdfib_parse_canvas_opt = static function ( $key, $fallback ) {
				$val = pdfib_get_option( $key, $fallback );
				if ( is_string( $val ) && false !== strpos( $val, ',' ) ) {
					return array_map( 'strval', explode( ',', $val ) );
				}
				if ( is_array( $val ) ) {
					return array_map( 'strval', $val );
				}
				return array( (string) $val );
			};

			$pdfib_tpl_dpis         = $pdfib_parse_canvas_opt( 'pdfib_canvas_dpi', '72,96,150' );
			$pdfib_tpl_formats      = $pdfib_parse_canvas_opt( 'pdfib_canvas_formats', 'A4' );
			$pdfib_tpl_orientations = $pdfib_parse_canvas_opt( 'pdfib_canvas_orientations', 'portrait,landscape' );
			$pdfib_json_flags       = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

			wp_add_inline_script(
				'pdf-builder-admin-templates',
				'window.availableDpis = ' . wp_json_encode( $pdfib_tpl_dpis, $pdfib_json_flags ) . ';' .
				'window.availableFormats = ' . wp_json_encode( $pdfib_tpl_formats, $pdfib_json_flags ) . ';' .
				'window.availableOrientations = ' . wp_json_encode( $pdfib_tpl_orientations, $pdfib_json_flags ) . ';',
				'before'
			);
		}
	}

	/**
	 * Charge les scripts communs à toutes les pages PDF Builder.
	 *
	 * @param string $current_page Identifiant de la page courante.
	 *
	 * @return void
	 */
	private function pdfib_enqueue_common_pdf_builder_scripts(
		string $current_page
	): void {
		$this->pdfib_enqueue_word_press_core_scripts();
		$this->pdfib_enqueue_notifications_scripts();
		$this->pdfib_enqueue_floating_save_script();
		$this->pdfib_enqueue_settings_partial_scripts( $current_page );
		$this->pdfib_enqueue_canvas_styles( $current_page );
		$this->pdfib_localize_notifications_data();
		$this->pdfib_localize_ajax_data();
	}

	/**
	 * Charge les scripts WordPress core nécessaires.
	 *
	 * @return void
	 */
	private function pdfib_enqueue_word_press_core_scripts(): void {
		$utils_js = PDFIB_PRO_ASSETS_PATH . 'js/pdf-builder-utils.js';
		if ( file_exists( $utils_js ) ) {
			wp_enqueue_script(
				'pdf-builder-utils',
				PDFIB_PRO_ASSETS_URL . 'js/pdf-builder-utils.js',
				array(
					'wp-element',
					'wp-components',
					'wp-data',
					'wp-hooks',
				),
				PDFIB_PRO_VERSION,
				true
			);
		}

		wp_enqueue_style(
			'pdf-builder-settings-tabs',
			PDFIB_PRO_ASSETS_URL . 'css/settings-tabs.css',
			array(),
			PDFIB_PRO_VERSION
		);

		if ( ! wp_script_is( 'pdf-builder-settings-tabs', 'enqueued' ) ) {
			wp_enqueue_script(
				'pdf-builder-settings-tabs',
				PDFIB_PRO_ASSETS_URL . 'js/settings-tabs.min.js',
				array(
					'jquery',
					'wp-element',
					'wp-components',
					'wp-data',
					'wp-hooks',
					'wp-i18n',
					'wp-api',
				),
				PDFIB_PRO_VERSION,
				true
			);
		}

		if ( ! wp_script_is( 'pdf-builder-settings-main', 'enqueued' ) ) {
			wp_enqueue_script(
				'pdf-builder-settings-main',
				PDFIB_PRO_ASSETS_URL . 'js/settings-main.min.js',
				array(
					'jquery',
					'wp-element',
					'wp-components',
					'wp-data',
					'wp-hooks',
					'wp-i18n',
				),
				PDFIB_PRO_VERSION,
				true
			);
		}
	}

	/**
	 * Charge les scripts du système de notifications.
	 *
	 * @return void
	 */
	private function pdfib_enqueue_notifications_scripts(): void {
		$notifications_js = PDFIB_PRO_ASSETS_PATH . self::NOTIFICATIONS_JS;
		if ( file_exists( $notifications_js ) ) {
			wp_enqueue_script(
				'pdf-builder-notifications',
				PDFIB_PRO_ASSETS_URL . self::NOTIFICATIONS_JS,
				array(
					'jquery',
					'wp-element',
					'wp-components',
					'wp-notices',
				),
				PDFIB_PRO_VERSION,
				true
			);
		}

		$notifications_css = PDFIB_PRO_ASSETS_PATH
			. 'css/notifications-css.min.css';
		if ( file_exists( $notifications_css ) ) {
			wp_enqueue_style(
				'pdf-builder-notifications-css',
				PDFIB_PRO_ASSETS_URL . 'css/notifications-css.min.css',
				array(),
				PDFIB_PRO_VERSION
			);
		}
	}

	/**
	 * Charge le script du bouton flottant de sauvegarde.
	 *
	 * @return void
	 */
	private function pdfib_enqueue_floating_save_script(): void {
		// Bouton flottant géré par JS inline dans settings-main.php.
		// Script externe désactivé pour éviter le double handler.
	}

	/**
	 * Charge les styles canvas pour les pages templates et paramètres.
	 *
	 * @param string $current_page Identifiant de la page courante.
	 *
	 * @return void
	 */
	private function pdfib_enqueue_canvas_styles( string $current_page ): void {
		$is_settings  = 'pdf-builder-settings' === $current_page;
		$is_templates = 'pdf-builder-templates' === $current_page;

		if ( ! $is_settings && ! $is_templates ) {
			return;
		}

		wp_enqueue_style(
			'pdf-builder-react',
			PDFIB_PRO_ASSETS_URL . 'css/pdf-builder-react.min.css',
			array(),
			PDFIB_PRO_VERSION
		);

		if ( ! $is_settings ) {
			return;
		}

		$canvas_modal_js = PDFIB_PRO_ASSETS_PATH
			. 'js/canvas-modal-settings.js';
		$version         = file_exists( $canvas_modal_js )
			? (string) filemtime( $canvas_modal_js )
			: PDFIB_PRO_VERSION;

		wp_enqueue_script(
			'pdf-builder-canvas-modal-settings',
			PDFIB_PRO_ASSETS_URL . 'js/canvas-modal-settings.js',
			array( 'jquery' ),
			$version,
			true
		);
		wp_localize_script(
			'pdf-builder-canvas-modal-settings',
			'pdfBuilderAjax',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pdfib_ajax' ),
			)
		);
	}

	/**
	 * Localise les données du système de notifications.
	 *
	 * @return void
	 */
	private function pdfib_localize_notifications_data(): void {
		$settings       = pdfib_get_option( 'pdfib_settings', array() );
		$debug_settings = array(
			'javascript'         => isset(
				$settings['pdfib_debug_javascript']
			) && $settings['pdfib_debug_javascript'],
			'javascript_verbose' => isset(
				$settings['pdfib_debug_javascript_verbose']
			) && $settings['pdfib_debug_javascript_verbose'],
			'php'                => isset(
				$settings['pdfib_debug_php']
			) && $settings['pdfib_debug_php'],
			'ajax'               => isset(
				$settings['pdfib_debug_ajax']
			) && $settings['pdfib_debug_ajax'],
		);

		$flags   = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		$encoded = wp_json_encode( $debug_settings, $flags );
		wp_add_inline_script(
			'pdf-builder-notifications',
			self::JS_DEBUG_SETTINGS_VAR . $encoded . ';',
			'before'
		);

		wp_localize_script(
			'pdf-builder-notifications',
			'pdfBuilderNotifications',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'pdfib_notifications' ),
				'settings' => array(
					'enabled'           => true,
					'position'          => 'top-right',
					'duration'          => 5000,
					'max_notifications' => 5,
					'animation'         => 'slide',
					'theme'             => 'modern',
				),
				'strings'  => array(
					'success' => __(
						'Succès',
						'advanced-pdf-invoice-builder'
					),
					'error'   => __(
						'Erreur',
						'advanced-pdf-invoice-builder'
					),
					'warning' => __(
						'Avertissement',
						'advanced-pdf-invoice-builder'
					),
					'info'    => __(
						'Information',
						'advanced-pdf-invoice-builder'
					),
					'close'   => __(
						'Fermer',
						'advanced-pdf-invoice-builder'
					),
				),
			)
		);
	}

	/**
	 * Localise les données AJAX.
	 *
	 * @return void
	 */
	private function pdfib_localize_ajax_data(): void {
		wp_localize_script(
			'pdf-builder-settings-tabs',
			'pdfBuilderAjax',
			array(
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'pdfib_ajax' ),
				'editor_url' => admin_url(
					'admin.php?page=pdf-builder-react-editor&editor_action=new'
				),
			)
		);

		$settings       = pdfib_get_option( 'pdfib_settings', array() );
		$debug_settings = array(
			'javascript'         => isset(
				$settings['pdfib_debug_javascript']
			) && $settings['pdfib_debug_javascript'],
			'javascript_verbose' => isset(
				$settings['pdfib_debug_javascript_verbose']
			) && $settings['pdfib_debug_javascript_verbose'],
			'php'                => isset(
				$settings['pdfib_debug_php']
			) && $settings['pdfib_debug_php'],
			'ajax'               => isset(
				$settings['pdfib_debug_ajax']
			) && $settings['pdfib_debug_ajax'],
		);

		$flags   = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		$encoded = wp_json_encode( $debug_settings, $flags );
		wp_add_inline_script(
			'pdf-builder-settings-tabs',
			self::JS_DEBUG_SETTINGS_VAR . $encoded . ';',
			'before'
		);
	}

	/**
	 * Délègue l'enqueue de la section éditeur React.
	 *
	 * @param string|null $hook Identifiant de la page courante.
	 *
	 * @return void
	 */
	private function pdfib_enqueue_react_editor_section(
		?string $hook = null
	): void {
		$this->react_editor_enqueuer->enqueue_react_editor_section( $hook );
	}

	/**
	 * Corrige les templates Elementor chargés comme des scripts JS.
	 *
	 * Les templates HTML doivent avoir type="text/template" au lieu
	 * de type="text/javascript".
	 *
	 * @param string $tag    La balise script HTML générée.
	 * @param string $handle L'identifiant du script.
	 * @param string $src    L'URL source du script.
	 *
	 * @return string
	 */
	public function pdfib_fix_elementor_templates(
		string $tag,
		string $handle,
		string $src
	) {
		$is_elementor_handle = false !== strpos( $handle, 'elementor' );
		if ( ! $is_elementor_handle && ! empty( $src ) ) {
			return $tag;
		}

		$result_tag = $tag;

		if ( empty( $src ) ) {
			if ( preg_match( '/^\s*<[^>]+>/', $tag ) ) {
				$result_tag = str_replace(
					array( self::MIME_TEXT_JS_DQ, self::MIME_TEXT_JS_SQ ),
					self::MIME_TEXT_TMPL_DQ,
					$tag
				);
			} else {
				$should_convert
					= strpos(
						$tag,
						'elementor-templates-modal__header__logo-area'
					) !== false
					|| strpos(
						$tag,
						'elementor-templates-modal__header__logo__icon'
						. '-wrapper'
					) !== false
					|| strpos(
						$tag,
						'elementor-finder__search'
					) !== false
					|| strpos(
						$tag,
						'elementor-finder__no-results'
					) !== false
					|| strpos(
						$tag,
						'elementor-finder__results__category__title'
					) !== false
					|| strpos(
						$tag,
						'elementor-finder__results__item__link'
					) !== false;

				if ( $should_convert ) {
					$result_tag = str_replace(
						array(
							self::MIME_TEXT_JS_DQ,
							self::MIME_TEXT_JS_SQ,
						),
						self::MIME_TEXT_TMPL_DQ,
						$tag
					);
				}
			}
		}

		return $result_tag;
	}

	/**
	 * Charge les scripts spécifiques à la page des paramètres.
	 *
	 * @param string $current_page Identifiant de la page courante.
	 *
	 * @return void
	 */
	private function pdfib_enqueue_settings_partial_scripts(
		string $current_page
	): void {
		if ( 'pdf-builder-settings' !== $current_page ) {
			return;
		}

		$this->pdfib_enqueue_cron_script();

		// Load the licence tab bundle only when the PRO license manager is available.
		$pdfib_license_manager = apply_filters( 'pdfib_license_manager_instance', null );
		$pdfib_is_pro_active   = function_exists( 'pdfib_is_pro_plugin_active' )
			&& pdfib_is_pro_plugin_active();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		if ( 'licence' === $current_tab && ( $pdfib_is_pro_active || is_object( $pdfib_license_manager ) ) ) {
			$this->pdfib_register_react_vendors();
			$licence_js = PDFIB_PRO_ASSETS_PATH . 'js/licence-page-react.min.js';
			wp_enqueue_script(
				'pdfib-licence-page',
				PDFIB_PRO_ASSETS_URL . 'js/licence-page-react.min.js',
				array( 'pdfib-vendors' ),
				file_exists( $licence_js ) ? (string) filemtime( $licence_js ) : PDFIB_PRO_VERSION,
				true
			);

			wp_localize_script(
				'pdfib-licence-page',
				'pdfBuilderLicense',
				array(
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'ajaxurl'         => admin_url( 'admin-ajax.php' ),
					'ajaxNonce'       => wp_create_nonce( 'pdfib_license_nonce' ),
					'deactivateNonce' => wp_create_nonce( 'pdfib_license_nonce' ),
					'btnText'         => __( 'Activer la licence', 'advanced-pdf-invoice-builder' ),
				)
			);
		}
	}

	/**
	 * Enqueue le script et les données de localisation du Cron.
	 *
	 * @return void
	 */
	private function pdfib_enqueue_cron_script(): void {
		wp_enqueue_script(
			'pdfib-settings-cron',
			PDFIB_PRO_ASSETS_URL . 'js/pdfib-cron-admin.js',
			array( 'jquery' ),
			PDFIB_PRO_VERSION,
			true
		);

		$cron_test_nonce = wp_create_nonce( 'pdfib_cron_test' );
		$cron_test_url   = admin_url(
			'admin-ajax.php?action=pdfib_cron_test&nonce='
			. $cron_test_nonce
		);

		wp_localize_script(
			'pdfib-settings-cron',
			'pdfibCronData',
			array(
				'nonce'       => wp_create_nonce( 'pdfib_ajax' ),
				'cronTestUrl' => $cron_test_url,
				'i18n'        => $this->pdfib_get_cron_i18n_strings(),
			)
		);
	}

	/**
	 * Retourne les chaînes i18n pour le script Cron.
	 *
	 * @return array
	 */
	private function pdfib_get_cron_i18n_strings(): array {
		return array(
			'diagnosing'                       => __(
				'Diagnosing...',
				'advanced-pdf-invoice-builder'
			),
			'repairing'                        => __(
				'Repairing...',
				'advanced-pdf-invoice-builder'
			),
			'loading'                          => __(
				'Loading...',
				'advanced-pdf-invoice-builder'
			),
			'diagnoseCronSystem'               => __(
				'Diagnose Cron System',
				'advanced-pdf-invoice-builder'
			),
			'repairCronSystem'                 => __(
				'Repair Cron System',
				'advanced-pdf-invoice-builder'
			),
			'ajaxErrorOccurred'                => __(
				'AJAX error occurred',
				'advanced-pdf-invoice-builder'
			),
			'errorDiagnosingCronSystem'        => __(
				'Error diagnosing cron system:',
				'advanced-pdf-invoice-builder'
			),
			'cronSystemRepairedSuccessfully'   => __(
				'Cron system repaired successfully!',
				'advanced-pdf-invoice-builder'
			),
			'errorRepairingCronSystem'         => __(
				'Error repairing cron system:',
				'advanced-pdf-invoice-builder'
			),
			'areYouSureRepairCron'             => __(
				'Are you sure you want to repair the cron system? This may restart scheduled tasks.',
				'advanced-pdf-invoice-builder'
			),
			'checkingWpCronConfiguration'      => __(
				'Checking WP Cron configuration...',
				'advanced-pdf-invoice-builder'
			),
			'checkingScheduledTasks'           => __(
				'Checking scheduled tasks...',
				'advanced-pdf-invoice-builder'
			),
			'testingCronResponse'              => __(
				'Testing cron response...',
				'advanced-pdf-invoice-builder'
			),
			'wpCronDisabled'                   => __(
				'WP Cron is DISABLED (DISABLE_WP_CRON = true)',
				'advanced-pdf-invoice-builder'
			),
			'wpCronEnabled'                    => __(
				'WP Cron is ENABLED',
				'advanced-pdf-invoice-builder'
			),
			'cannotCheckWpCronConfiguration'   => __(
				'Cannot check WP Cron configuration',
				'advanced-pdf-invoice-builder'
			),
			'errorCheckingWpCronConfiguration' => __(
				'Error checking WP Cron configuration',
				'advanced-pdf-invoice-builder'
			),
			'scheduledTasksActive'             => __(
				'Scheduled tasks active (',
				'advanced-pdf-invoice-builder'
			),
			'tasks'                            => __(
				' tasks)',
				'advanced-pdf-invoice-builder'
			),
			'noScheduledTasksFound'            => __(
				'No scheduled tasks found',
				'advanced-pdf-invoice-builder'
			),
			'cannotCheckScheduledTasks'        => __(
				'Cannot check scheduled tasks',
				'advanced-pdf-invoice-builder'
			),
			'errorCheckingScheduledTasks'      => __(
				'Error checking scheduled tasks',
				'advanced-pdf-invoice-builder'
			),
			'cronSystemRespondingCorrectly'    => __(
				'Cron system responding correctly',
				'advanced-pdf-invoice-builder'
			),
			'cronSystemRespondingWithIssues'   => __(
				'Cron system responding but with issues',
				'advanced-pdf-invoice-builder'
			),
			'cronResponseSlow'                 => __(
				'Cron response slow (timeout)',
				'advanced-pdf-invoice-builder'
			),
			'cronSystemNotResponding'          => __(
				'Cron system not responding',
				'advanced-pdf-invoice-builder'
			),
			'checking'                         => __(
				'Checking...',
				'advanced-pdf-invoice-builder'
			),
			'refreshStatus'                    => __(
				'Refresh Status',
				'advanced-pdf-invoice-builder'
			),
		);
	}
}
