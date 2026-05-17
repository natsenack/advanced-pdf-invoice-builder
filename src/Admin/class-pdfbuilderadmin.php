<?php
/**
 * Advanced PDF Invoice Builder - Interface d'administration simplifiée
 *
 * Version 5.1.0 - Éditeur React uniquement.
 * Refactorisé : Architecture modulaire avec séparation des responsabilités.
 *
 * @package PDFIB\Admin
 */

namespace PDFIB\Admin;

use function add_settings_field;
use function add_settings_error;
use function register_post_type;
use function sanitize_textarea_field;
use function absint;
use function esc_textarea;
use function wp_get_current_user;
use function wp_safe_redirect;
use function add_query_arg;
use function get_post_type;
use function status_header;
use const WC_VERSION;

defined( 'ABSPATH' ) || exit;

// Prevent multiple inclusions.
if ( defined( 'PDFIB_ADMIN_LOADED' ) ) {
	return;
}
define( 'PDFIB_ADMIN_LOADED', true );

defined( 'ABSPATH' ) || exit;

/**
 * Classe principale d'administration du Advanced PDF Invoice Builder.
 *
 * Responsabilités : Orchestration des managers, interface principale.
 * Version: 2.0.3 - Optimisée avec lazy loading et méthodes groupées.
 *
 * @package PDFIB\Admin
 */
class PdfBuilderAdmin {

	// Constantes pour optimiser les chemins.
	const PLUGIN_ROOT   = __DIR__ . '/../../..';
	const SRC_DIR       = self::PLUGIN_ROOT . '/src';
	const TEMPLATES_DIR = self::PLUGIN_ROOT . '/templates/admin';

	/**
	 * Instance unique de la classe.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Référence vers le core.
	 *
	 * @var \PDFIB\Core\PdfBuilderCore|null
	 */
	private ?\PDFIB\Core\PdfBuilderCore $core = null;

	/**
	 * Manager de templates.
	 *
	 * @var mixed
	 */
	private mixed $template_manager = null;

	/**
	 * Manager des paramètres.
	 *
	 * @var mixed
	 */
	private mixed $settings_manager = null;

	/**
	 * Intégration WooCommerce.
	 *
	 * @var mixed
	 */
	private mixed $woocommerce_integration = null;

	/**
	 * Manager de templates prédéfinis.
	 *
	 * @var mixed
	 */
	private mixed $predefined_templates_manager = null;

	/**
	 * Renderer HTML.
	 *
	 * @var mixed
	 */
	private mixed $html_renderer = null;

	/**
	 * Processeur de templates.
	 *
	 * @var mixed
	 */
	private mixed $template_processor = null;

	/**
	 * Manager de thumbnails.
	 *
	 * @var mixed
	 */
	private mixed $thumbnail_manager = null;

	/**
	 * Loader de scripts admin.
	 *
	 * @var mixed
	 */
	private mixed $script_loader = null;

	/**
	 * Provider de données du tableau de bord.
	 *
	 * @var mixed
	 */
	private mixed $dashboard_data_provider = null;

	/**
	 * Renderer des pages admin.
	 *
	 * @var mixed
	 */
	private mixed $admin_page_renderer = null;

	/**
	 * Registrar des paramètres admin.
	 *
	 * @var mixed
	 */
	private ?AdminSettingsRegistrar $settings_registrar = null;

	/**
	 * Obtenir l'instance unique de la classe (Singleton).
	 *
	 * @param mixed $core Instance du core.
	 * @return self
	 */
	public static function get_instance( mixed $core = null ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $core );
		}
		return self::$instance;
	}

	/**
	 * Constructeur privé - Initialisations regroupées et délégation.
	 *
	 * @param mixed $core Instance du core.
	 */
	private function __construct( mixed $core = null ) {

		$this->core               = $core;
		$this->settings_registrar = new AdminSettingsRegistrar();
		// initCoreManagers inlined.
		$this->settings_manager = new \PDFIB\Managers\PDFBuilderSettingsManager( $this );
		if ( class_exists( 'PDFIB\Managers\PdfBuilderTemplateManager' ) ) {
			$this->template_manager = new \PDFIB\Managers\PdfBuilderTemplateManager( $this );
		}

		// initSpecializedModules inlined.
		$this->html_renderer = new \PDFIB\Admin\Renderers\HTMLRenderer( $this );
		try {
			$this->template_processor = new \PDFIB\Admin\Processors\TemplateProcessor( $this );
		} catch ( \Exception $e ) {
			$this->template_processor = null;
		}
		// initServicesAndLoaders inlined.
		$this->script_loader       = new \PDFIB\Admin\Loaders\AdminScriptLoader( $this );
		$this->admin_page_renderer = new \PDFIB\Admin\Renderers\AdminPageRenderer( $this );
		// initConditionalModules inlined.
		// Le plugin PRO peut injecter une instance de manager via le filtre 'pdfib_predefined_templates_manager'.
		$this->predefined_templates_manager = apply_filters( 'pdfib_predefined_templates_manager', null );

		// WooCommerce hooks via delegate.
		( new AdminWooCommerceHooks( $this ) )->register_all();
		// initHooks + initAdminHooks inlined.
		\add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		if ( $this->is_admin_context() ) {
			\add_action( 'admin_init', array( $this->settings_registrar, 'register_settings' ) );
			\add_action( 'init', array( $this, 'register_template_post_type' ) );
			\add_action( 'admin_enqueue_scripts', array( $this, 'disable_problematic_preferences' ), 1 );
		}
	}

	/**
	 * Récupère l'instance du template manager.
	 *
	 * @return mixed
	 */
	public function get_template_manager() {
		return $this->template_manager;
	}

	/**
	 * Vérifie les permissions d'administration sans mise en cache.
	 *
	 * @return bool
	 */
	private function check_admin_permissions() {
		// Vérifier les rôles autorisés par défaut.
		$allowed_roles = array( 'administrator', 'editor', 'shop_manager' );

		$user       = \wp_get_current_user();
		$user_roles = $user ? $user->roles : array();

		foreach ( $user_roles as $role ) {
			if ( in_array( $role, $allowed_roles, true ) ) {
				return true;
			}
		}

		return false;
	}




	/**
	 * Compte le nombre de templates créés par un utilisateur.
	 *
	 * @param int $user_id Identifiant de l'utilisateur.
	 * @return int
	 */
	public static function count_user_templates( $user_id ) {
		// Compter depuis la table custom pdfib_templates.
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';

		// Récupérer le nombre de templates pour cet utilisateur.

		$count = pdfib_db()->get_var(
			pdfib_db()->prepare(
				'SELECT COUNT(*) FROM %i WHERE user_id = %d',
				$table_templates,
				$user_id
			)
		);

		return (int) $count;
	}



	/**
	 * Enregistre le custom post type pour les templates PDF.
	 */
	public function register_template_post_type() {
		\register_post_type(
			'pdfib_template',
			array(
				'labels'          => array(
					'name'               => \__( 'Templates PDF', 'advanced-pdf-invoice-builder' ),
					'singular_name'      => \__( 'Template PDF', 'advanced-pdf-invoice-builder' ),
					'add_new'            => \__( 'Nouveau Template', 'advanced-pdf-invoice-builder' ),
					'add_new_item'       => \__( 'Ajouter un Nouveau Template', 'advanced-pdf-invoice-builder' ),
					'edit_item'          => \__( 'Éditer le Template', 'advanced-pdf-invoice-builder' ),
					'new_item'           => \__( 'Nouveau Template', 'advanced-pdf-invoice-builder' ),
					'view_item'          => \__( 'Voir le Template', 'advanced-pdf-invoice-builder' ),
					'search_items'       => \__( 'Rechercher Templates', 'advanced-pdf-invoice-builder' ),
					'not_found'          => \__( 'Aucun template trouvé', 'advanced-pdf-invoice-builder' ),
					'not_found_in_trash' => \__( 'Aucun template dans la corbeille', 'advanced-pdf-invoice-builder' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false, // Masqué du menu principal.
				'capability_type' => 'post',
				'hierarchical'    => false,
				'supports'        => array( 'title' ),
				'has_archive'     => false,
				'rewrite'         => false,
			)
		);
	}

	/**
	 * Vérifie si on est dans le contexte d'administration.
	 *
	 * @return bool
	 */
	private function is_admin_context() {
		$request_action = sanitize_text_field( wp_unslash( $GLOBALS['_REQUEST']['action'] ?? '' ) );
		return is_admin() || ( '' !== $request_action && strpos( $request_action, 'pdf_builder' ) !== false );
	}

	/**
	 * Désactive les préférences WordPress problématiques qui causent des erreurs API REST.
	 */
	public function disable_problematic_preferences() {
		global $pagenow;
		$page = sanitize_text_field( wp_unslash( $GLOBALS['_GET']['page'] ?? '' ) );
		if ( 'pdf-builder-react-editor' !== $page ) {
			return;
		}
		$inline_js = "if(typeof wp!=='undefined'&&wp.apiFetch){var oAF=wp.apiFetch;wp.apiFetch=function(o){if(o.path&&o.path.includes('/wp/v2/users/me')){return Promise.reject({code:'blocked_endpoint',message:'Endpoint blocked to prevent errors'});}return oAF(o);};}if(typeof wp!=='undefined'&&wp.data&&wp.data.dispatch){try{if(wp.data.dispatch('core/preferences')){wp.data.dispatch('core/preferences').set=function(){};}}catch(e){}}if(typeof jQuery!=='undefined'){var JOB='{'.charCodeAt(0);var oA=jQuery.ajax;jQuery.ajax=function(o){if(o.dataType==='json'||(o.url&&o.url.includes('admin-ajax.php'))){var oS=o.success;var oE=o.error;o.success=function(d,t,x){if(typeof d==='string'&&d.trim().charCodeAt(0)!==JOB){if(oE){oE(x,'parsererror',{code:'invalid_json',message:'Invalid JSON response.'});}return;}if(oS){oS(d,t,x);}};o.error=function(x,t,e){console.warn('PDF Builder: AJAX error handled:',t,e);if(oE){oE(x,t,e);}};}return oA.call(this,o);};}";
		wp_add_inline_script( 'jquery', $inline_js );
	}

	/**
	 * Ajoute le menu d'administration.
	 */
	public function add_admin_menu() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		\add_menu_page(
			\__( 'Advanced PDF Invoice Builder', 'advanced-pdf-invoice-builder' ),
			\__( 'PDF Builder', 'advanced-pdf-invoice-builder' ),
			'manage_options',
			'advanced-pdf-invoice-builder',
			array( $this, 'admin_page' ),
			'dashicons-pdf',
			30
		);

		\add_submenu_page( 'advanced-pdf-invoice-builder', \__( 'Accueil - Advanced PDF Invoice Builder', 'advanced-pdf-invoice-builder' ), \__( 'Accueil', 'advanced-pdf-invoice-builder' ), 'manage_options', 'advanced-pdf-invoice-builder', array( $this, 'admin_page' ) );

		// Enregistrer la page éditeur en free avant do_action pour garantir l'accès même sans PRO.
		\add_submenu_page(
			'advanced-pdf-invoice-builder',
			\__( 'Éditeur PDF - Advanced PDF Invoice Builder', 'advanced-pdf-invoice-builder' ),
			\__( 'Éditeur PDF', 'advanced-pdf-invoice-builder' ),
			'manage_options',
			'pdf-builder-react-editor',
			array( $this, 'react_editor_page' )
		);

		do_action( 'pdfib_admin_menu_after_home' );

		\add_submenu_page( 'advanced-pdf-invoice-builder', \__( 'Templates PDF - Advanced PDF Invoice Builder', 'advanced-pdf-invoice-builder' ), \__( 'Templates', 'advanced-pdf-invoice-builder' ), 'manage_options', 'pdf-builder-templates', array( $this, 'templates_page' ) );
		\add_submenu_page( 'advanced-pdf-invoice-builder', \__( 'Paramètres - Advanced PDF Invoice Builder', 'advanced-pdf-invoice-builder' ), \__( 'Paramètres', 'advanced-pdf-invoice-builder' ), 'manage_options', 'pdf-builder-settings', array( $this, 'settings_page' ) );
	}



	/**
	 * Page principale d'administration - Tableau de bord.
	 */
	public function admin_page() {
		if ( ! $this->check_admin_permissions() ) {
			\wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'advanced-pdf-invoice-builder' ) );
		}

		// Utiliser le renderer pour afficher la page d'administration.
		if ( $this->admin_page_renderer ) {
			$this->admin_page_renderer->render_admin_page();
		} else {
			// Fallback si le renderer n'est pas disponible.
			echo '<div class="wrap"><h1>Advanced PDF Invoice Builder</h1><p>' . esc_html__( 'Erreur: Renderer non disponible.', 'advanced-pdf-invoice-builder' ) . '</p></div>';
		}
	}

	/**
	 * Page de l'éditeur React unifié.
	 */
	public function react_editor_page() {
		// Guard contre le double rendu si free et PRO enregistrent tous les deux le callback.
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;

		// En mode preview, autoriser les utilisateurs avec droits WooCommerce.
		$is_preview_mode = isset( $GLOBALS['_GET']['preview'] ) && '1' === $GLOBALS['_GET']['preview'];
		$has_permission  = $this->check_admin_permissions() ||
			( $is_preview_mode && \current_user_can( 'edit_shop_orders' ) );

		if ( ! $has_permission ) {
			\wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'advanced-pdf-invoice-builder' ) );
		}

		if ( ! $is_preview_mode && ! $this->pdfib_has_editor_entry_context() ) {
			\wp_safe_redirect( \admin_url( 'admin.php?page=pdf-builder-templates' ) );
			exit;
		}

		?>
		<div class="wrap pdf-builder-editor-page">
			<div id="pdf-builder-react-root"></div>
		</div>
		<?php
	}

	/**
	 * Détermine si l'accès à l'éditeur provient d'un flux interne autorisé.
	 *
	 * @return bool
	 */
	private function pdfib_has_editor_entry_context(): bool {
		$template_id = isset( $GLOBALS['_GET']['template_id'] )
			? absint( wp_unslash( $GLOBALS['_GET']['template_id'] ) )
			: 0;
		if ( $template_id > 0 ) {
			return true;
		}

		$legacy_template = isset( $GLOBALS['_GET']['template'] )
			? absint( wp_unslash( $GLOBALS['_GET']['template'] ) )
			: 0;
		if ( $legacy_template > 0 ) {
			return true;
		}

		$predefined_template = isset( $GLOBALS['_GET']['predefined_template'] )
			? sanitize_key( wp_unslash( $GLOBALS['_GET']['predefined_template'] ) )
			: '';
		if ( '' !== $predefined_template ) {
			return true;
		}

		$editor_action = isset( $GLOBALS['_GET']['editor_action'] )
			? sanitize_key( wp_unslash( $GLOBALS['_GET']['editor_action'] ) )
			: '';

		if ( 'new' === $editor_action ) {
			return self::can_create_template();
		}

		return false;
	}

	/**
	 * Page de gestion des templates.
	 */
	public function templates_page() {
		if ( ! $this->check_admin_permissions() ) {
			\wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'advanced-pdf-invoice-builder' ) );
		}

		// Inclure la page dédiée de gestion des templates.
		if ( file_exists( \plugin_dir_path( dirname( __DIR__ ) ) . 'templates/admin/templates-page.php' ) ) {
			include_once \plugin_dir_path( dirname( __DIR__ ) ) . 'templates/admin/templates-page.php';
		} else {
			// Fallback si le fichier n'existe pas.
			echo '<div class="wrap">';
			echo '<h1>' . esc_html__( 'Gestion des Templates PDF', 'advanced-pdf-invoice-builder' ) . '</h1>';
			echo '<p>' . esc_html__( 'Erreur: Fichier de templates introuvable.', 'advanced-pdf-invoice-builder' ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Page des paramètres - centralisée dans settings-main.php.
	 */
	public function settings_page() {
		if ( ! $this->check_admin_permissions() ) {
			\wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'advanced-pdf-invoice-builder' ) );
		}

		// Inclure le fichier centralisé des paramètres.
		include_once \plugin_dir_path( dirname( __DIR__ ) ) . 'templates/admin/settings-parts/settings-main.php';
	}

	/**
	 * Getter pour le Thumbnail Manager avec lazy loading.
	 *
	 * @return mixed
	 */
	/**
	 * Getter pour le Thumbnail Manager avec lazy loading.
	 *
	 * @return mixed
	 */
	public function get_thumbnail_manager() {

		if ( null === $this->thumbnail_manager ) {
			if ( ! class_exists( 'PDFIB\Managers\PdfBuilderThumbnailManager' ) ) {
				$thumbnail_manager_file = self::SRC_DIR . '/Managers/class-pdfbuilderthumbnailmanager.php';
				if ( file_exists( $thumbnail_manager_file ) ) {
					require_once $thumbnail_manager_file;
				}
			}
			$this->thumbnail_manager = \PDFIB\Managers\PdfBuilderThumbnailManager::get_instance();
		}
		return $this->thumbnail_manager;
	}
	/**
	 * Getter pour le Dashboard Data Provider avec lazy loading.
	 *
	 * @return mixed
	 */
	public function get_dashboard_data_provider() {

		if ( null === $this->dashboard_data_provider ) {
			if ( ! class_exists( '\PDFIB\Admin\Providers\DashboardDataProvider' ) ) {
				require_once self::SRC_DIR . '/Admin/Providers/class-dashboarddataprovider.php';
			}
			$this->dashboard_data_provider = new \PDFIB\Admin\Providers\DashboardDataProvider();
		}
		return $this->dashboard_data_provider;
	}
	/**
	 * Getter pour l'intégration WooCommerce avec lazy loading.
	 *
	 * @return mixed
	 */
	public function get_woo_commerce_integration() {
		if ( null === $this->woocommerce_integration && \did_action( 'plugins_loaded' ) && ( defined( 'WC_VERSION' ) || class_exists( 'WooCommerce' ) ) ) {
			if ( ! class_exists( 'PDFIB\Managers\PdfBuilderWooCommerceIntegration' ) ) {
				$woo_file = PDFIB_PLUGIN_DIR . 'src/Managers/class-pdfbuilderwoocommerceintegration.php';
				if ( file_exists( $woo_file ) ) {
					require_once $woo_file;
				}
			}
			if ( class_exists( 'PDFIB\Managers\PdfBuilderWooCommerceIntegration' ) ) {
				$this->woocommerce_integration = new \PDFIB\Managers\PdfBuilderWooCommerceIntegration( $this->core );
			}
		}
		return $this->woocommerce_integration;
	}

	/**
	 * Getter pour le Predefined Templates Manager.
	 *
	 * @return mixed
	 */
	public function get_predefined_templates_manager() {
		if ( null === $this->predefined_templates_manager ) {
			$this->predefined_templates_manager = apply_filters( 'pdfib_predefined_templates_manager', null );
		}

		return $this->predefined_templates_manager;
	}

	/**
	 * Get template processor instance.
	 *
	 * @return mixed
	 */
	public function get_template_processor() {
		if ( null === $this->template_processor ) {

			try {
				$this->template_processor = new \PDFIB\Admin\Processors\TemplateProcessor( $this );
			} catch ( \Exception $e ) {

				$this->template_processor = null;
			}
		}
		return $this->template_processor;
	}

	/**
	 * Compte les templates personnalisés d'un utilisateur.
	 *
	 * Les templates par défaut fournis au démarrage ne doivent pas consommer
	 * le quota gratuit.
	 *
	 * @param int $user_id Identifiant de l'utilisateur.
	 * @return int
	 */
	private static function count_user_custom_templates( int $user_id ): int {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		$count           = pdfib_db()->get_var(
			pdfib_db()->prepare(
				'SELECT COUNT(*) FROM %i WHERE user_id = %d AND is_default = %d',
				$table_templates,
				$user_id,
				0
			)
		);

		return (int) $count;
	}

	/**
	 * Vérifie si l'utilisateur peut créer un nouveau template.
	 *
	 * La version premium est illimitée. La version gratuite est limitée à un
	 * seul template personnalisé.
	 *
	 * @return bool
	 */
	public static function can_create_template() {
		$pdfib_license_manager = apply_filters( 'pdfib_license_manager_instance', null );
		$is_premium            = is_object( $pdfib_license_manager )
			&& method_exists( $pdfib_license_manager, 'is_premium' )
			&& $pdfib_license_manager->is_premium();

		if ( $is_premium ) {
			return true;
		}

		$user_id         = \get_current_user_id();
		$templates_count = self::count_user_custom_templates( $user_id );

		return $templates_count < 1;
	}
}



