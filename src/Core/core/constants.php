<?php
/**
 * Advanced PDF Invoice Builder Constants.
 *
 * Plugin constants and configuration.
 *
 * @package PDFIB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin capabilities.
if ( ! defined( 'PDFIB_ADMIN_CAPABILITY' ) ) {
	define( 'PDFIB_ADMIN_CAPABILITY', 'manage_options' );
}

// Plugin version management.
if ( ! defined( 'PDFIB_VERSION' ) ) {
	define( 'PDFIB_VERSION', '1.3.26' );
}

if ( ! defined( 'PDFIB_PRO_VERSION' ) ) {
	define( 'PDFIB_PRO_VERSION', '1.3.26' );
}

/**
 * Get the plugin version from header
 * This ensures version consistency across the plugin
 */
function pdfib_get_version() {
	static $version = null;

	if ( null === $version ) {
		if ( defined( 'PDFIB_PLUGIN_FILE' ) && file_exists( PDFIB_PLUGIN_FILE ) ) {
			$plugin_data = \get_file_data( PDFIB_PLUGIN_FILE, array( 'Version' => 'Version' ) );
			$version     = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : pdfib_get_current_version();
		} else {
			$version = pdfib_get_current_version();
		}
	}

	return $version;
}

/**
 * Get the current version based on license status
 * Returns PRO version if license is active, FREE version otherwise
 */
function pdfib_get_current_version() {
	if ( pdfib_is_pro_license_active() ) {
		return PDFIB_PRO_VERSION;
	} else {
		return PDFIB_VERSION;
	}
}

/**
 * Check if PRO license is active.
 * FREE edition always returns false.
 */
function pdfib_is_pro_license_active() {
	return false;
}

// Plugin paths.
if ( ! defined( 'PDFIB_PLUGIN_DIR' ) ) {
	define( 'PDFIB_PLUGIN_DIR', plugin_dir_path( PDFIB_PLUGIN_FILE ) );
}

if ( ! defined( 'PDFIB_PLUGIN_URL' ) ) {
	define( 'PDFIB_PLUGIN_URL', plugin_dir_url( PDFIB_PLUGIN_FILE ) );
}

// Core paths.
if ( ! defined( 'PDFIB_CORE_DIR' ) ) {
	define( 'PDFIB_CORE_DIR', PDFIB_PLUGIN_DIR . 'src/Core/' );
}

if ( ! defined( 'PDFIB_SRC_DIR' ) ) {
	define( 'PDFIB_SRC_DIR', PDFIB_PLUGIN_DIR . 'src/' );
}

if ( ! defined( 'PDFIB_ASSETS_DIR' ) ) {
	define( 'PDFIB_ASSETS_DIR', PDFIB_PLUGIN_DIR . 'assets/' );
}

if ( ! defined( 'PDFIB_PRO_ASSETS_URL' ) ) {
	define( 'PDFIB_PRO_ASSETS_URL', PDFIB_PLUGIN_URL . 'assets/' );
}

if ( ! defined( 'PDFIB_RESOURCES_DIR' ) ) {
	define( 'PDFIB_RESOURCES_DIR', PDFIB_PLUGIN_DIR . 'assets/' );
}

if ( ! defined( 'PDFIB_TEMPLATES_DIR' ) ) {
	define( 'PDFIB_TEMPLATES_DIR', PDFIB_PLUGIN_DIR . 'templates/' );
}

if ( ! defined( 'PDFIB_CONFIG_DIR' ) ) {
	define( 'PDFIB_CONFIG_DIR', PDFIB_PLUGIN_DIR . 'config/' );
}

if ( ! defined( 'PDFIB_LANGUAGES_DIR' ) ) {
	define( 'PDFIB_LANGUAGES_DIR', PDFIB_PLUGIN_DIR . 'languages/' );
}

// Upload paths.
if ( ! defined( 'PDFIB_UPLOAD_DIR' ) ) {
	define( 'PDFIB_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/pdf-builder-pro/' );
}

if ( ! defined( 'PDFIB_PRO_UPLOADS_DIR' ) ) {
	define( 'PDFIB_PRO_UPLOADS_DIR', wp_upload_dir()['basedir'] . '/pdf-builder-pro/' );
}

if ( ! defined( 'PDFIB_CACHE_DIR' ) ) {
	define( 'PDFIB_CACHE_DIR', PDFIB_UPLOAD_DIR . 'cache/' );
}

if ( ! defined( 'PDFIB_LOGS_DIR' ) ) {
	define( 'PDFIB_LOGS_DIR', PDFIB_UPLOAD_DIR . 'logs/' );
}

// Database tables.
if ( ! defined( 'PDFIB_TEMPLATES_TABLE' ) ) {
	global $wpdb;
	define( 'PDFIB_TEMPLATES_TABLE', $wpdb->prefix . 'pdfib_templates' );
}

if ( ! defined( 'PDFIB_SETTINGS_TABLE' ) ) {
	global $wpdb;
	define( 'PDFIB_SETTINGS_TABLE', $wpdb->prefix . 'pdfib_settings' );
}

/**
 * Retourne l'instance globale wpdb.
 * Permet d'éviter l'usage direct de $wpdb/$GLOBALS dans le code métier.
 *
 * @return \wpdb
 */
if ( ! function_exists( 'pdfib_db' ) ) {
	/**
	 * Retourne l'instance globale wpdb.
	 *
	 * @return \wpdb
	 */
	function pdfib_db() {
		global $wpdb;
		return $wpdb;
	}
}

// Capabilities.
if ( ! defined( 'PDFIB_ADMIN_CAPABILITY' ) ) {
	define( 'PDFIB_ADMIN_CAPABILITY', 'manage_options' );
}

if ( ! defined( 'PDFIB_EDITOR_CAPABILITY' ) ) {
	define( 'PDFIB_EDITOR_CAPABILITY', 'edit_pages' );
}

// AJAX actions.
if ( ! defined( 'PDFIB_AJAX_PREFIX' ) ) {
	define( 'PDFIB_AJAX_PREFIX', 'pdfib_' );
}

// ==========================================
// SÉCURITÉ - Constantes générales
// ==========================================

// Nonces pour les différents contextes.
if ( ! defined( 'PDFIB_CANVAS_NONCE' ) ) {
	define( 'PDFIB_CANVAS_NONCE', 'pdfib_canvas_nonce' );
}

if ( ! defined( 'PDFIB_ORDER_ACTIONS_NONCE' ) ) {
	define( 'PDFIB_ORDER_ACTIONS_NONCE', 'pdfib_order_actions' );
}

// Timeouts de sécurité (en secondes).
if ( ! defined( 'PDFIB_NONCE_LIFETIME' ) ) {
	define( 'PDFIB_NONCE_LIFETIME', 24 * 60 * 60 );
	// 24 heures
}

if ( ! defined( 'PDFIB_SESSION_TIMEOUT' ) ) {
	define( 'PDFIB_SESSION_TIMEOUT', 30 * 60 );
	// 30 minutes
}

if ( ! defined( 'PDFIB_CACHE_LIFETIME' ) ) {
	define( 'PDFIB_CACHE_LIFETIME', 60 * 60 );
	// 1 heure
}

// Limites de taux (requêtes par minute).
if ( ! defined( 'PDFIB_RATE_LIMIT_CANVAS' ) ) {
	define( 'PDFIB_RATE_LIMIT_CANVAS', 60 );
	// 60 actions canvas/minute
}

if ( ! defined( 'PDFIB_RATE_LIMIT_GENERATE' ) ) {
	define( 'PDFIB_RATE_LIMIT_GENERATE', 10 );
	// 10 générations/minute
}

// Limites de données.
if ( ! defined( 'PDFIB_MAX_CANVAS_ELEMENTS' ) ) {
	define( 'PDFIB_MAX_CANVAS_ELEMENTS', 100 );
	// Maximum 100 éléments par canvas.
}

if ( ! defined( 'PDFIB_MAX_ELEMENT_SIZE' ) ) {
	define( 'PDFIB_MAX_ELEMENT_SIZE', 50 * 1024 * 1024 );
	// 50MB max par élément
}

// Sanitisation et validation.
if ( ! defined( 'PDFIB_ALLOWED_HTML_TAGS' ) ) {
	define( 'PDFIB_ALLOWED_HTML_TAGS', 'strong,em,u,br,p,span' );
}

if ( ! defined( 'PDFIB_ALLOWED_PROTOCOLS' ) ) {
	define( 'PDFIB_ALLOWED_PROTOCOLS', 'http,https,data' );
}

// Logging de sécurité.
if ( ! defined( 'PDFIB_SECURITY_LOG_ENABLED' ) ) {
	define( 'PDFIB_SECURITY_LOG_ENABLED', true );
}

if ( ! defined( 'PDFIB_SECURITY_LOG_LEVEL' ) ) {
	define( 'PDFIB_SECURITY_LOG_LEVEL', 'warning' );
	// error, warning, info.
}

// Meta keys sécurisées pour le stockage.
if ( ! defined( 'PDFIB_CANVAS_META_KEY' ) ) {
	define( 'PDFIB_CANVAS_META_KEY', '_pdfib_canvas_data' );
}
