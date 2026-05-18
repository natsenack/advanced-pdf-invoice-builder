<?php
/**
 * Advanced PDF Invoice Builder - React Editor Script Enqueuer.
 *
 * Handles enqueuing of React editor scripts and localizing editor data.
 *
 * @package PDFIB\Admin\Loaders
 */

namespace PDFIB\Admin\Loaders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encapsulates React editor script enqueueing logic for AdminScriptLoader.
 *
 * @package PDFIB\Admin\Loaders
 */
class ReactEditorScriptEnqueuer {

	const NOTIFICATIONS_JS      = 'js/notifications.min.js';
	const JS_DEBUG_SETTINGS_VAR = 'window.pdfBuilderDebugSettings = ';
	const MIME_TEXT_JS          = 'text/javascript';

	/**
	 * Admin controller instance.
	 *
	 * @var mixed
	 */
	private mixed $admin;

	/**
	 * Constructor.
	 *
	 * @param mixed $admin Admin controller instance.
	 */
	public function __construct( mixed $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Enqueue the React editor section when the current page matches.
	 *
	 * @param string|null $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_react_editor_section( ?string $hook = null ): void {
		// Scripts pour l'éditeur React.
		$on_react_editor_hook = 'pdf-builder_page_pdf-builder-react-editor' === $hook;
		if ( $on_react_editor_hook || ( isset( $GLOBALS['_GET']['page'] ) && 'pdf-builder-react-editor' === $GLOBALS['_GET']['page'] ) ) {
			$this->load_react_editor_scripts();

			// Add footer DOM check only once.
			\add_action(
				'admin_footer-pdf-builder_page_pdf-builder-react-editor',
				function () {
					$inline_script = "
                    (function() {
                        let scripts = document.querySelectorAll('script[src*=\"pdf-builder-react\"]');
                        scripts.forEach((script, index) => {});
                        // Manual init if not done
                        setTimeout(function() {
                            const root = document.getElementById('pdf-builder-react-root');
                            if (root && root.children.length === 0) {
                                if (window.pdfBuilderReact && window.pdfBuilderReact.initPDFBuilderReact) {
                                    window.pdfBuilderReact.initPDFBuilderReact('pdf-builder-react-root');
                                }
                            }
                        }, 1000);
                    })();
                ";
					wp_add_inline_script( 'pdf-builder-react', $inline_script );
				}
			);
		}

		// Charger aussi les scripts React si on est sur une page qui contient "react-editor" dans l'URL.
		if ( ! $on_react_editor_hook && isset( $GLOBALS['_SERVER']['REQUEST_URI'] ) && false !== strpos( sanitize_text_field( wp_unslash( $GLOBALS['_SERVER']['REQUEST_URI'] ) ), 'pdf-builder-react-editor' ) ) {
			$this->load_react_editor_scripts();
		}
	}

	/**
	 * Load all React editor scripts.
	 *
	 * @return void
	 */
	public function load_react_editor_scripts(): void {
		static $scripts_loaded = false;
		if ( $scripts_loaded ) {
			return;
		}
		$scripts_loaded = true;

		// Guard: only load for users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// CHARGER LA MÉDIATHÈQUE WordPress POUR LES COMPOSANTS REACT.
		\wp_enqueue_media();

		$cache_bust    = microtime( true ) . '-' . wp_rand( 1000, 9999 );
		$version_param = PDFIB_PRO_VERSION . '-' . $cache_bust;

		$this->enqueue_notifications_and_wrap_script( $cache_bust );
		$this->enqueue_react_core_and_main_bundle( $version_param );
		$template_id   = 0;
		$localize_data = $this->build_react_localize_data( $template_id );
		$this->add_react_inline_scripts( $localize_data );
		$this->enqueue_react_wrapper_and_diagnostic( $version_param, $cache_bust );
	}

	/**
	 * Enqueue notification and wrapper scripts.
	 *
	 * @param string $cache_bust Cache-busting token.
	 * @return void
	 */
	public function enqueue_notifications_and_wrap_script( string $cache_bust ): void {
		\wp_enqueue_script( 'pdf-builder-ajax-throttle', PDFIB_PLUGIN_URL . 'assets/js/ajax-throttle.min.js', array(), $cache_bust, true );
		\wp_enqueue_script( 'pdf-builder-notifications', PDFIB_PRO_ASSETS_URL . self::NOTIFICATIONS_JS, array( 'jquery', 'wp-element', 'wp-components', 'wp-notices' ), $cache_bust, true );
		\wp_enqueue_style( 'pdf-builder-notifications', PDFIB_PRO_ASSETS_URL . 'css/notifications.min.css', array(), $cache_bust );
		\wp_localize_script(
			'pdf-builder-notifications',
			'pdfBuilderNotifications',
			array(
				'ajax_url' => \admin_url( 'admin-ajax.php' ),
				'nonce'    => \wp_create_nonce( 'pdfib_notifications' ),
				'settings' => array(
					'enabled'           => true,
					'position'          => 'top-right',
					'duration'          => 5000,
					'max_notifications' => 5,
					'animation'         => 'slide',
					'theme'             => 'modern',
				),
				'strings'  => array(
					'success' => \__( 'Succès', 'advanced-pdf-invoice-builder' ),
					'error'   => \__( 'Erreur', 'advanced-pdf-invoice-builder' ),
					'warning' => \__( 'Avertissement', 'advanced-pdf-invoice-builder' ),
					'info'    => \__( 'Information', 'advanced-pdf-invoice-builder' ),
					'close'   => \__( 'Fermer', 'advanced-pdf-invoice-builder' ),
				),
			)
		);

		$settings       = pdfib_get_option( 'pdfib_settings', array() );
		$debug_settings = array(
			'javascript'         => isset( $settings['pdfib_debug_javascript'] ) && $settings['pdfib_debug_javascript'],
			'javascript_verbose' => isset( $settings['pdfib_debug_javascript_verbose'] ) && $settings['pdfib_debug_javascript_verbose'],
			'php'                => isset( $settings['pdfib_debug_php'] ) && $settings['pdfib_debug_php'],
			'ajax'               => isset( $settings['pdfib_debug_ajax'] ) && $settings['pdfib_debug_ajax'],
		);

		wp_add_inline_script( 'pdf-builder-notifications', self::JS_DEBUG_SETTINGS_VAR . wp_json_encode( $debug_settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
		\wp_enqueue_script( 'pdf-builder-wrap', PDFIB_PRO_ASSETS_URL . 'js/pdf-builder-wrap.min.js', array( 'pdf-builder-ajax-throttle', 'pdf-builder-notifications', 'wp-element', 'wp-components' ), $cache_bust, true );
	}

	/**
	 * Enqueue WordPress core dependencies and the main React bundle.
	 *
	 * @param string $version_param Script version.
	 * @return void
	 */
	public function enqueue_react_core_and_main_bundle( string $version_param ): void {
		// Enqueue WordPress core scripts for React editor (ESSENTIALS ONLY).
		wp_enqueue_script( 'wp-element' ); // Provides React.
		wp_enqueue_script( 'wp-components' ); // Provides React components.
		wp_enqueue_script( 'wp-data' ); // Provides Redux store.
		wp_enqueue_script( 'wp-hooks' ); // Provides hooks.
		wp_enqueue_script( 'wp-i18n' ); // Provides internationalization.
		wp_enqueue_script( 'wp-api' ); // Provides WordPress API.

		// NOTE: react-vendor.min.js n'existe pas et a été supprimé du build webpack.
		// React est fourni par WordPress core (wp-element).

		// Main React app bundle.
		$react_main_url = PDFIB_PLUGIN_URL . 'assets/js/pdf-builder-react.min.js';
		if ( ! wp_script_is( 'pdf-builder-react-main', 'enqueued' ) ) {
			\wp_enqueue_script( 'pdf-builder-react-main', $react_main_url, array( 'wp-element', 'wp-components', 'wp-data', 'wp-hooks', 'wp-api', 'media-views' ), $version_param, true );
			\wp_script_add_data( 'pdf-builder-react-main', 'type', self::MIME_TEXT_JS );
		}
	}

	/**
	 * Build localized data for the React editor.
	 *
	 * @param int $template_id Template identifier by reference.
	 * @return array
	 */
	public function build_react_localize_data( int &$template_id ): array {
		// Localize script data BEFORE enqueuing.
		$template_id = 0;
		if ( isset( $GLOBALS['_GET']['template_id'] ) ) {
			$template_id = \intval( wp_unslash( $GLOBALS['_GET']['template_id'] ) );
		} elseif ( isset( $GLOBALS['_GET']['template'] ) ) {
			$template_id = \intval( wp_unslash( $GLOBALS['_GET']['template'] ) );
		}

		$localize_data = array(
			'ajaxUrl'    => \admin_url( 'admin-ajax.php' ),
			'nonce'      => \wp_create_nonce( 'pdfib_ajax' ),
			'version'    => PDFIB_PRO_VERSION,
			'templateId' => $template_id,
			'isEdit'     => $template_id > 0,
			'features'   => array(
				'imageExport'               => false,
				'advancedColors'            => false,
				'unlimitedRate'             => false,
				'newTemplateButton'         => false,
				'predefinedTemplatesButton' => false,
				'gridNavigation'            => false,
				'editorThemes'              => false,
			),
		);

		// Ajouter les informations de licence - FREE edition.
		$pdfib_license_manager = apply_filters( 'pdfib_license_manager_instance', null );
		if ( ! is_object( $pdfib_license_manager ) && class_exists( '\PDFIB\Managers\PdfBuilderLicenseManager' ) ) {
			$pdfib_license_manager = \PDFIB\Managers\PdfBuilderLicenseManager::get_instance();
		}

		$localize_data['license'] = array(
			'isPremium' => is_object( $pdfib_license_manager )
				&& method_exists( $pdfib_license_manager, 'is_premium' )
				&& $pdfib_license_manager->is_premium(),
			'status'    => is_object( $pdfib_license_manager )
				&& method_exists( $pdfib_license_manager, 'get_license_status' )
				? (string) $pdfib_license_manager->get_license_status()
				: 'free',
		);

		// Ajouter les informations de l'entreprise depuis les paramètres du plugin ET WooCommerce.
		$localize_data['company'] = $this->build_company_data();

		// Ajouter les paramètres canvas.
		if ( class_exists( '\\PDFIB\\Canvas\\CanvasManager' ) ) {
			$canvas_manager  = \PDFIB\Canvas\CanvasManager::get_instance();
			$canvas_settings = $canvas_manager->get_all_settings();

			$localize_data['canvasSettings'] = $canvas_settings;

			// Définir aussi window.pdfBuilderCanvasSettings pour la compatibilité React.
			wp_add_inline_script(
				'pdf-builder-react-main',
				'window.pdfBuilderCanvasSettings = ' . wp_json_encode( $canvas_settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
				'before'
			);
		}

		$localize_data = $this->append_canvas_options_localize_data( $localize_data );
		$this->load_react_editor_template_data( $localize_data, $template_id );

		$localize_data                         = apply_filters( 'pdfib_editor_script_data', $localize_data );
		$pdfib_is_premium                      = (bool) ( $localize_data['isPremium'] ?? false );
		$localize_data['license']['isPremium'] = $pdfib_is_premium;
		$localize_data['license']['status']    = $pdfib_is_premium
			? (string) ( $localize_data['licenseStatus'] ?? 'active' )
			: 'free';

		return $localize_data;
	}

	/**
	 * Add inline data required by the React editor.
	 *
	 * @param array $localize_data Localized data payload.
	 * @return void
	 */
	public function add_react_inline_scripts( array $localize_data ): void {
		// Also set window.pdfBuilderData directly before React initializes.
		static $inline_scripts_added = false;
		if ( ! $inline_scripts_added ) {
			// Utiliser JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT pour échapper TOUT le HTML/JS.
			$safe_json_data = wp_json_encode( $localize_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

			wp_add_inline_script( 'pdf-builder-react-main', 'window.pdfBuilderData = ' . $safe_json_data . ';', 'before' );
			// Also set window.pdfBuilderNonce for AJAX calls.
			$nonce = \wp_create_nonce( 'pdfib_ajax' );
			wp_add_inline_script( 'pdf-builder-react-main', 'window.pdfBuilderNonce = "' . esc_js( $nonce ) . '";', 'before' );
			$inline_scripts_added = true;
		}
	}

	/**
	 * Enqueue the React wrapper and diagnostic scripts.
	 *
	 * @param string $version_param Script version.
	 * @param string $cache_bust Cache-busting token.
	 * @return void
	 */
	public function enqueue_react_wrapper_and_diagnostic( string $version_param, string $cache_bust ): void {
		// React wrapper script - waits for pdfBuilderReact to be available.
		$react_wrapper_url = PDFIB_PLUGIN_URL . 'assets/js/pdf-builder-react-wrapper.min.js';
		if ( ! wp_script_is( 'pdf-builder-react-wrapper', 'enqueued' ) ) {
			\wp_enqueue_script( 'pdf-builder-react-wrapper', $react_wrapper_url, array( 'pdf-builder-react-main' ), $version_param, true );
			\wp_script_add_data( 'pdf-builder-react-wrapper', 'type', self::MIME_TEXT_JS );
		}

		$this->enqueue_react_editor_diagnostic_scripts( $version_param, $cache_bust );
	}

	/**
	 * Load template-related data into localized data.
	 *
	 * @param array $localize_data Localized data payload by reference.
	 * @param int   $template_id Template identifier.
	 * @return void
	 */
	public function load_react_editor_template_data( array &$localize_data, int $template_id ): void {
		// Charger les données du template si template_id est fourni.
		if ( $template_id > 0 ) {
			$this->load_existing_template_if_set( $localize_data, $template_id );
		}

		// PREVIEW MODE: Charger les données réelles de la commande.
		if ( isset( $GLOBALS['_GET']['order_id'] ) && \intval( wp_unslash( $GLOBALS['_GET']['order_id'] ) ) > 0 && isset( $GLOBALS['_GET']['preview'] ) && '1' === $GLOBALS['_GET']['preview'] ) {
			$this->load_preview_mode_order_data( $localize_data );
		}

		// Charger les données du template prédéfini si predefined_template est fourni.
		if ( isset( $GLOBALS['_GET']['predefined_template'] ) && ! empty( $GLOBALS['_GET']['predefined_template'] ) ) {
			$this->load_predefined_template_if_set( $localize_data );
		}
	}

	/**
	 * Load an existing saved template into localized data.
	 *
	 * @param array $localize_data Localized data payload by reference.
	 * @param int   $template_id Template identifier.
	 * @return void
	 */
	public function load_existing_template_if_set( array &$localize_data, int $template_id ): void {
		// Utiliser le getter pour obtenir le TemplateProcessor (avec création à la demande).
		$template_processor = $this->admin->get_template_processor();
		if ( $template_processor ) {
			$existing_template_data = $template_processor->load_template_robust( $template_id );

			if ( $existing_template_data && isset( $existing_template_data['elements'] ) ) {
				$localize_data['initialElements']  = $existing_template_data['elements'];
				$localize_data['existingTemplate'] = $existing_template_data;
				$localize_data['hasExistingData']  = true;
			}
		}
	}

	/**
	 * Load preview order data into localized data.
	 *
	 * @param array $localize_data Localized data payload by reference.
	 * @return void
	 */
	public function load_preview_mode_order_data( array &$localize_data ): void {
		$order_id                        = \intval( wp_unslash( $GLOBALS['_GET']['order_id'] ) );
		$localize_data['previewMode']    = true;
		$localize_data['previewOrderId'] = $order_id;

		// Charger les vraies données de la commande WooCommerce.
		if ( class_exists( 'WC_Order' ) ) {
			$order = \wc_get_order( $order_id );
			if ( $order ) {
				// Extraire les données de la commande à injecter dans React.
				require_once PDFIB_PLUGIN_DIR . 'src/Generators/OrderDataExtractor.php';
				$data_extractor = new \PDFIB\Generators\OrderDataExtractor( $order );
				$order_data     = $data_extractor->get_all_data();

				$localize_data['previewOrderData'] = $order_data;
			}
		}
	}

	/**
	 * Load a predefined template into localized data.
	 *
	 * @param array $localize_data Localized data payload by reference.
	 * @return void
	 */
	public function load_predefined_template_if_set( array &$localize_data ): void {
		$predefined_slug = \sanitize_key( wp_unslash( $GLOBALS['_GET']['predefined_template'] ) );

		// Charger le template prédéfini.
		$predefined_manager = $this->admin->get_predefined_templates_manager();
		if ( $predefined_manager ) {
			try {
				// Simuler la requête AJAX pour charger le template prédéfini.
				$template_data = $predefined_manager->loadTemplateFromFile( $predefined_slug );

				if ( $template_data && isset( $template_data['json'] ) ) {
					$json_data = json_decode( $template_data['json'], true );
					if ( $json_data && isset( $json_data['elements'] ) ) {
						$localize_data['initialElements']        = $json_data['elements'];
						$localize_data['existingTemplate']       = $json_data;
						$localize_data['hasExistingData']        = true;
						$localize_data['isPredefinedTemplate']   = true;
						$localize_data['predefinedTemplateName'] = $template_data['name'] ?? 'Template prédéfini';
					}
				}
			} catch ( \Throwable $exception ) {
				unset( $exception ); // Intentional: fail silently when loading predefined template data.
			}
		} elseif ( class_exists( '\\PDFIB\\TemplateDefaults' ) ) {
			// Fallback: charger depuis TemplateDefaults (disponible sans dev token).
			$premium_templates = apply_filters( 'pdfib_premium_templates', array() );
			$premium_templates = is_array( $premium_templates ) ? $premium_templates : array();
			$all_predefined    = array_merge(
				\PDFIB\TemplateDefaults::get_free_templates(),
				$premium_templates
			);
			if ( isset( $all_predefined[ $predefined_slug ] ) ) {
				$template                                = $all_predefined[ $predefined_slug ];
				$localize_data['initialElements']        = $template['elements'];
				$localize_data['existingTemplate']       = array( 'elements' => $template['elements'] );
				$localize_data['hasExistingData']        = true;
				$localize_data['isPredefinedTemplate']   = true;
				$localize_data['predefinedTemplateName'] = $template['name'];
			}
		}
	}

	/**
	 * Enqueue diagnostic scripts for the React editor.
	 *
	 * @param string $version_param Script version.
	 * @param string $cache_bust Cache-busting token.
	 * @return void
	 */
	public function enqueue_react_editor_diagnostic_scripts( string $version_param, string $cache_bust ): void {
		$this->enqueue_react_executor_script( $version_param );
		$this->enqueue_react_init_scripts( $version_param, $cache_bust );
		$this->add_react_init_inline_script();
		$this->add_diagnostic_inline_script();
		$this->add_react_load_test_script();
	}

	/**
	 * Enqueue the React executor script.
	 *
	 * @param string $version_param Script version.
	 * @return void
	 */
	public function enqueue_react_executor_script( string $version_param ): void {
		// Module executor - forces execution of the React bundle.
		$react_executor_url = PDFIB_PLUGIN_URL . 'assets/js/pdf-builder-react-executor.min.js';
		if ( ! wp_script_is( 'pdf-builder-react-executor', 'enqueued' ) ) {
			\wp_enqueue_script( 'pdf-builder-react-executor', $react_executor_url, array( 'pdf-builder-react-main' ), $version_param, true );
			\wp_script_add_data( 'pdf-builder-react-executor', 'type', self::MIME_TEXT_JS );
		}

		// Add a safety check script that forces initialization.
		wp_add_inline_script(
			'pdf-builder-react-executor',
			'
            window.__pdfBuilderReactBundleLoaded = true;


            // If still not available after 100ms, something is wrong with the bundle
            setTimeout(function() {
                if (!window.pdfBuilderReact) {

                    // Check if webpack exported it as window.pdfBuilderReact
                    if (window.pdfBuilderReact) {

                    } else {

                        window.pdfBuilderReact = {
                            initPDFBuilderReact: function() {

                                return false;
                            },
                            _isFallback: true,
                            _error: "Bundle failed to export pdfBuilderReact"
                        };
                    }
                }
            }, 100);
        ',
			'after'
		);
	}

	/**
	 * Enqueue React initialization scripts.
	 *
	 * @param string $version_param Script version.
	 * @param string $cache_bust Cache-busting token.
	 * @return void
	 */
	public function enqueue_react_init_scripts( string $version_param, string $cache_bust = '' ): void {
		// Init helper.
		$init_helper_url = PDFIB_PRO_ASSETS_URL . 'js/pdf-builder-init.min.js';
		if ( ! wp_script_is( 'pdf-builder-react-init', 'enqueued' ) ) {
			\wp_enqueue_script( 'pdf-builder-react-init', $init_helper_url, array( 'pdf-builder-react-executor' ), $cache_bust, true );
		}

		// React initialization script - initializes PDFBuilderReact component.
		$react_init_url = PDFIB_PLUGIN_URL . 'assets/js/pdf-builder-react-init.min.js';
		if ( ! wp_script_is( 'pdf-builder-react-initializer', 'enqueued' ) ) {
			\wp_enqueue_script( 'pdf-builder-react-initializer', $react_init_url, array( 'pdf-builder-react-executor' ), $version_param, true );
			\wp_script_add_data( 'pdf-builder-react-initializer', 'type', self::MIME_TEXT_JS );
		}
	}

	/**
	 * Add the inline React initialization script.
	 *
	 * @return void
	 */
	public function add_react_init_inline_script(): void {
		// Script d'initialisation avec debug - exécuté immédiatement après la localisation.
		$init_script = '
        //
        //
        setTimeout(function() {
            //
            if (window.pdfBuilderData) {
                //
                //
                //
                //
                //
            } else {
                //
                //
                //
            }
        }, 100);
        ';

		wp_add_inline_script( 'pdf-builder-react-main', $init_script, 'after' );
	}

	/**
	 * Add inline diagnostic JavaScript.
	 *
	 * @return void
	 */
	public function add_diagnostic_inline_script(): void {
		wp_add_inline_script( 'jquery', 'jQuery(document).ready(function($){setTimeout(function(){if(window.pdfBuilderData){return;}var s=document.getElementsByTagName("script");for(var i=0;i<s.length;i++){if(s[i].src&&s[i].src.includes("pdf-builder-react")){}}},500);});', 'after' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wp_add_inline_script( 'jquery', '(function(){try{setTimeout(function(){var w=document.querySelector(\'script[src*="pdf-builder-react-wrapper.min.js"]\');},1000);}catch(e){}})();' );
		}
	}

	/**
	 * Add an inline script to verify React assets have loaded.
	 *
	 * @return void
	 */
	public function add_react_load_test_script(): void {
		// AJOUTER UN TEST DE CHARGEMENT DES SCRIPTS REACT.
		wp_add_inline_script(
			'jquery',
			'
            (function() {

				// Vérifier immédiatement si les scripts sont dans le DOM
                setTimeout(function() {
                    var scripts = document.getElementsByTagName("script");
                    var foundScripts = [];
                    for (var i = 0; i < scripts.length; i++) {
                        var src = scripts[i].src || "";
                        if (src.includes("pdf-builder-react")) {
                            foundScripts.push(src);
                        }
                    }
                    foundScripts.forEach(function(url, index) {
                    });

					// Tester si les scripts spécifiques sont présents
                    var initScript = document.querySelector(\'script[src*="pdf-builder-react-init.min.js"]\');
                    if (initScript) {
                    }

                    var mainScript = document.querySelector(\'script[src*="pdf-builder-react.min.js"]\');
                    if (mainScript) {
                    }
                }, 500);

				// Tester le chargement après un délai plus long
                setTimeout(function() {
                    if (window.pdfBuilderReact) {
                    }
                }, 2000);
            })();
        '
		);
	}

	/**
	 * Build company data for the editor.
	 *
	 * @return array
	 */
	private function build_company_data(): array {
		$company_data = array(
			'name'    => pdfib_get_option( 'pdfib_company_name', '' ),
			'address' => pdfib_get_option( 'pdfib_company_address', '' ),
			'phone'   => pdfib_get_option( 'pdfib_company_phone_manual', '' ),
			'email'   => pdfib_get_option( 'pdfib_company_email', '' ),
			'siret'   => pdfib_get_option( 'pdfib_company_siret', '' ),
			'vat'     => pdfib_get_option( 'pdfib_company_vat', '' ),
			'rcs'     => pdfib_get_option( 'pdfib_company_rcs', '' ),
			'capital' => pdfib_get_option( 'pdfib_company_capital', '' ),
		);

		if ( empty( $company_data['name'] ) ) {
			$company_data['name'] = \get_bloginfo( 'name', '' );
		}

		if ( empty( $company_data['address'] ) ) {
			$parts                   = array_filter(
				array(
					\get_option( 'woocommerce_store_address', '' ),
					\get_option( 'woocommerce_store_address_2', '' ),
					trim( \get_option( 'woocommerce_store_postcode', '' ) . ' ' . \get_option( 'woocommerce_store_city', '' ) ),
					\get_option( 'woocommerce_store_country', '' ),
				)
			);
			$company_data['address'] = implode( ', ', $parts );
		}

		if ( empty( $company_data['email'] ) ) {
			$company_data['email'] = \get_option( 'admin_email', '' );
		}

		$fallback = 'Non indiqué';
		foreach ( array( 'name', 'address', 'phone', 'email', 'siret', 'vat', 'rcs', 'capital' ) as $key ) {
			if ( empty( $company_data[ $key ] ) ) {
				$company_data[ $key ] = $fallback;
			}
		}

		return $company_data;
	}

	/**
	 * Append canvas option lists to localized data.
	 *
	 * @param array $data Localized data payload.
	 * @return array
	 */
	public function append_canvas_options_localize_data( array $data ): array {
		$parse = static function ( $key, $default_value ) {
			$value = pdfib_get_option( $key, $default_value );
			if ( is_string( $value ) && false !== strpos( $value, ',' ) ) {
				return array_map( 'strval', explode( ',', $value ) );
			}
			if ( is_array( $value ) ) {
				return array_map( 'strval', $value );
			}

			return array( (string) $value );
		};

		$can_use_extended_formats = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'extended_formats' );
		$can_use_high_dpi         = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'high_dpi' );

		$dpis         = $parse( 'pdfib_canvas_dpi', '72,96,150' );
		$formats      = $parse( 'pdfib_canvas_formats', 'A4' );
		$orientations = $parse( 'pdfib_canvas_orientations', 'portrait,landscape' );

		if ( ! $can_use_high_dpi ) {
			$dpis = array_values(
				array_filter(
					$dpis,
					static function ( string $dpi ): bool {
						return intval( $dpi ) <= 150;
					}
				)
			);
			if ( empty( $dpis ) ) {
				$dpis = array( '96' );
			}
		}

		if ( ! $can_use_extended_formats ) {
			$formats      = array( 'A4' );
			$orientations = array( 'portrait' );
		}

		$data['availableDpis']         = $dpis;
		$data['availableFormats']      = $formats;
		$data['availableOrientations'] = $orientations;

		// Security flags: hex-encode HTML special chars to prevent XSS in inline scripts.
		$pdfib_json_security_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		wp_add_inline_script( 'pdf-builder-react-main', 'window.availableDpis = ' . wp_json_encode( $dpis, $pdfib_json_security_flags ) . ';' );
		wp_add_inline_script( 'pdf-builder-react-main', 'window.availableFormats = ' . wp_json_encode( $formats, $pdfib_json_security_flags ) . ';' );
		wp_add_inline_script( 'pdf-builder-react-main', 'window.availableOrientations = ' . wp_json_encode( $orientations, $pdfib_json_security_flags ) . ';' );

		return $data;
	}
}
