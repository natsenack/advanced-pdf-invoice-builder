<?php
/**
 * Advanced PDF Invoice Builder - Core.
 *
 * Classe principale du plugin Advanced PDF Invoice Builder.
 *
 * @package PDFIB\Core
 */

namespace PDFIB\Core;

use WP_Error;
use function add_action;
use function add_option;
use function add_settings_section;
use function dbDelta;
use function file_exists;
use function function_exists;
use function get_option;
use function pdfib_db;
use function pdfib_filesystem;
use function pdfib_get_option;
use function pdfib_log;
use function pdfib_update_option;
use function wp_mkdir_p;
use function wp_upload_dir;
use function esc_html_e;

defined( 'ABSPATH' ) || exit;

/**
 * Gestionnaire principal du plugin.
 */
class PdfBuilderCore {

	/**
	 * Fichier admin à charger.
	 *
	 * @var string
	 */
	private const PDFIB_ADMIN_FILE = 'src/Admin/class-pdfbuilderadmin.php';

	/**
	 * Classe admin à charger.
	 *
	 * @var string
	 */
	private const PDFIB_ADMIN_CLASS = 'PDFIB\\Admin\\PdfBuilderAdmin';

	/**
	 * Instance unique.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Version du plugin.
	 *
	 * @var string
	 */
	private string $version = '1.0.0';

	/**
	 * Interface d'administration.
	 *
	 * @var object|null
	 */
	private ?object $admin = null;

	/**
	 * Constructeur privé.
	 */
	private function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Charge les dépendances nécessaires.
	 */
	private function load_dependencies() {
		$managers = array(
			'PDFBuilderSettingsManager.php',
			'PdfBuilderTemplateManager.php',
		);

		$woocommerce_managers = array(
			'PDFBuilderVariableMapper.php',
			'PDF_Builder_WooCommerce_Integration.php',
		);

		foreach ( $managers as $manager ) {
			$manager_path = PDFIB_PLUGIN_DIR . 'src/Managers/' . $manager;
			if ( file_exists( $manager_path ) ) {
				require_once $manager_path;
			}
		}

		$this->load_secondary_dependencies( $woocommerce_managers );
	}

	/**
	 * Charge les dépendances secondaires.
	 *
	 * @param array $woocommerce_managers Dépendances WooCommerce.
	 */
	private function load_secondary_dependencies( array $woocommerce_managers ) {
		if ( defined( 'WC_VERSION' ) || class_exists( 'WooCommerce' ) ) {
			foreach ( $woocommerce_managers as $manager ) {
				$manager_path = PDFIB_PLUGIN_DIR . 'src/Managers/' . $manager;
				if ( file_exists( $manager_path ) ) {
					require_once $manager_path;
				}
			}
		}

		$core_classes = array(
			'PdfBuilderSecurityValidator.php',
		);

		foreach ( $core_classes as $core_class ) {
			$core_path = PDFIB_PLUGIN_DIR . 'src/Core/' . $core_class;
			if ( file_exists( $core_path ) ) {
				require_once $core_path;
			}
		}

		if ( file_exists( PDFIB_PLUGIN_DIR . self::PDFIB_ADMIN_FILE ) && ! class_exists( self::PDFIB_ADMIN_CLASS ) ) {
			require_once PDFIB_PLUGIN_DIR . self::PDFIB_ADMIN_FILE;
		}
	}

	/**
	 * Récupère l'instance unique.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialise le plugin.
	 */
	public function init() {
		add_action( 'init', array( $this, 'initialize_directories' ) );
	}

	/**
	 * Enregistre les paramètres.
	 */
	public function register_settings() {
		add_settings_section(
			'pdfib_main',
			__( 'Main Settings', 'advanced-pdf-invoice-builder' ),
			array( $this, 'settings_section_callback' ),
			'pdfib_settings'
		);
	}

	/**
	 * Affiche le bloc de section des paramètres.
	 */
	public function settings_section_callback() {
		return null;
	}

	/**
	 * Active le plugin.
	 */
	public function activate() {
		if ( ! class_exists( 'PDFIB\\Database\\SettingsTableManager' ) ) {
			require_once PDFIB_PLUGIN_DIR . 'src/Database/Settings_Table_Manager.php';
		}

		\PDFIB\Database\SettingsTableManager::create_table();
		$this->create_database_tables();
		add_option( 'pdfib_version', $this->version );

		if ( function_exists( 'pdfib_log' ) ) {
			pdfib_log( 'Advanced PDF Invoice Builder activated', 1 );
		}
	}

	/**
	 * Désactive le plugin.
	 */
	public function deactivate() {
		if ( function_exists( 'pdfib_log' ) ) {
			pdfib_log( 'Advanced PDF Invoice Builder deactivated', 1 );
		}
	}

	/**
	 * Crée les tables de base de données.
	 */
	private function create_database_tables() {
		$charset_collate = pdfib_db()->get_charset_collate();

		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		$sql_templates   = "CREATE TABLE $table_templates (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			template_data longtext NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			is_default tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		$table_order_canvases = pdfib_db()->prefix . 'pdfib_order_canvases';
		$sql_order_canvases   = "CREATE TABLE $table_order_canvases (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			order_id bigint(20) NOT NULL,
			canvas_data longtext NOT NULL,
			template_id mediumint(9) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY order_id (order_id),
			KEY template_id (template_id)
		) $charset_collate;";

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_templates );
		dbDelta( $sql_order_canvases );
	}

	/**
	 * Affiche la notice de version PHP.
	 */
	public function php_version_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Advanced PDF Invoice Builder requires PHP 8.2 or higher.', 'advanced-pdf-invoice-builder' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Affiche la notice de version WordPress.
	 */
	public function wp_version_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Advanced PDF Invoice Builder requires WordPress 5.0 or higher.', 'advanced-pdf-invoice-builder' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Retourne la version du plugin.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Génère un PDF pour une commande WooCommerce.
	 *
	 * @param int $order_id ID de la commande.
	 * @param int $template_id ID du template.
	 * @return mixed
	 */
	public function generate_order_pdf( $order_id, $template_id = 0 ) {
		if ( is_object( $this->admin ) && method_exists( $this->admin, 'generate_order_pdf' ) ) {
				return $this->admin->generate_order_pdf( $order_id, $template_id );
		}

		return new WP_Error( 'admin_not_initialized', __( 'Interface d\'administration non initialisée', 'advanced-pdf-invoice-builder' ) );
	}

	/**
	 * Initialise les répertoires de travail.
	 */
	public function initialize_directories() {
		try {
			$upload_dir = wp_upload_dir();
			if ( isset( $upload_dir['error'] ) && $upload_dir['error'] ) {
				return;
			}

			$base_dir = $upload_dir['basedir'] ?? '';
			if ( empty( $base_dir ) || ! pdfib_filesystem()->is_writable( $base_dir ) ) {
				return;
			}

			$directories = array(
				$base_dir . '/pdf-builder',
				$base_dir . '/pdf-builder/templates',
				$base_dir . '/pdf-builder/previews',
				$base_dir . '/pdf-builder/orders',
				$base_dir . '/pdf-builder/temp',
				$base_dir . '/pdf-builder/cache',
				$base_dir . '/pdf-builder/logs',
			);

			foreach ( $directories as $directory ) {
				if ( ! file_exists( $directory ) ) {
					wp_mkdir_p( $directory );

					$htaccess_path = $directory . '/.htaccess';
					if ( ! file_exists( $htaccess_path ) ) {
						$htaccess_content = "# Sécuriser l'accès aux fichiers PDF Builder\n<FilesMatch \"\\.(php|php3|php4|php5|phtml)$\">\nOrder Deny,Allow\nDeny from all\n</FilesMatch>\n";
						pdfib_filesystem()->put_contents( $htaccess_path, $htaccess_content, FS_CHMOD_FILE );
					}

					$index_path = $directory . '/index.php';
					if ( ! file_exists( $index_path ) ) {
						pdfib_filesystem()->put_contents( $index_path, '<?php // Silence is golden', FS_CHMOD_FILE );
					}
				}
			}
		} catch ( \Exception $exception ) {
			unset( $exception );
		}
	}
}
