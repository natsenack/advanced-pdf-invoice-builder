<?php
/**
 * Main plugin file for Advanced PDF Invoice Builder.
 *
 * PHP version 8.2
 *
 * @category Plugin
 * @package  PDFIB
 * @author   Natsenack <threeaxe.france@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/natsenack/wp-pdf-builder-pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Name: Advanced PDF Invoice Builder
 * Plugin URI: https://github.com/natsenack/wp-pdf-builder
 * Description: PDF invoice builder with drag-and-drop editor for WooCommerce
 * Version: 1.0.0
 * Author: Natsenack
 * Author URI: https://github.com/natsenack
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-pdf-invoice-builder
 * Domain Path: /languages
 * Requires at least: 6.2
 * Tested up to: 6.9
 * Requires PHP: 8.2
 * WC requires at least: 7.0
 * WC tested up to: 9.9
 */

// Définir les constantes du plugin.
if ( ! defined( 'PDFIB_PLUGIN_FILE' ) ) {
	define( 'PDFIB_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'PDFIB_PLUGIN_DIR' ) ) {
	define( 'PDFIB_PLUGIN_DIR', __DIR__ . '/' );
}
if ( ! defined( 'PDFIB_PLUGIN_URL' ) ) {
	define( 'PDFIB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'PDFIB_PRO_ASSETS_URL' ) ) {
	define( 'PDFIB_PRO_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets/' );
}
if ( ! defined( 'PDFIB_PRO_ASSETS_PATH' ) ) {
	define( 'PDFIB_PRO_ASSETS_PATH', plugin_dir_path( __FILE__ ) . 'assets/' );
}
// Version lue directement depuis le header (source unique de vérité).
// Ne pas modifier ces constantes : changer uniquement "Version:" ci-dessus.
$pdfib_file_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
if ( ! defined( 'PDFIB_VERSION' ) ) {
	define( 'PDFIB_VERSION', $pdfib_file_data['Version'] );
}
// PDFIB_PRO_VERSION est défini par le plugin PRO, pas par le FREE.
unset( $pdfib_file_data );

// Edition marker for feature gating.
if ( ! defined( 'PDFIB_EDITION' ) ) {
	define( 'PDFIB_EDITION', 'free' );
}

// Hook d'activation : créer toutes les tables requises.
register_activation_hook(
	__FILE__,
	function () {
		if ( ! class_exists( 'PDFIB\Database\SettingsTableManager' ) ) {
			include_once PDFIB_PLUGIN_DIR
				. 'src/Database/Settings_Table_Manager.php';
		}
		\PDFIB\Database\SettingsTableManager::create_table();

		// Table wp_pdfib_templates.
		$charset_collate = pdfib_db()->get_charset_collate();
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		$table_check     = pdfib_db()->prepare(
			'SELECT 1 FROM information_schema.TABLES'
			. ' WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
			DB_NAME,
			$table_templates
		);
		if ( pdfib_db()->get_var( $table_check ) === null ) {
			include_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta(
				"CREATE TABLE $table_templates (\n"
				. "id mediumint(9) NOT NULL AUTO_INCREMENT,\n"
				. "name varchar(255) NOT NULL,\n"
				. "template_data longtext NOT NULL,\n"
				. "user_id bigint(20) unsigned NOT NULL DEFAULT 0,\n"
				. "is_default tinyint(1) NOT NULL DEFAULT 0,\n"
				. "created_at datetime DEFAULT CURRENT_TIMESTAMP,\n"
				. 'updated_at datetime DEFAULT CURRENT_TIMESTAMP'
				. ' ON UPD' . "ATE CURRENT_TIMESTAMP,\n"
				. "PRIMARY KEY (id)\n"
				. ") $charset_collate;"
			);
		}

		update_option( 'pdfib_version', PDFIB_VERSION, false );
	}
);

// Déclarer la compatibilité avec les fonctionnalités WooCommerce.
add_action( 'before_woocommerce_init', 'pdfib_declare_woocommerce_compatibility' );

/**
 * Déclare la compatibilité du plugin avec les fonctionnalités WooCommerce.
 *
 * Déclare la compatibilité avec HPOS (custom_order_tables) et les blocs
 * panier/commande (cart_checkout_blocks).
 *
 * @return void
 */
function pdfib_declare_woocommerce_compatibility(): void {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			__FILE__,
			true
		);
	}
}

// Charger l'initialiseur du plugin.
require_once PDFIB_PLUGIN_DIR . 'class-pdfib-loader.php';
