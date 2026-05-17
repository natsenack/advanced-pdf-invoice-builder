<?php
/**
 * Désinstallation du plugin.
 *
 * PHP version 8.2
 *
 * Supprime les données uniquement si l'option dédiée l'autorise.
 * Le fichier reste autonome, sans dépendre des helpers du plugin.
 *
 * @category WordPress
 * @package  PDFIB
 * @author   Natsenack <threeaxe.france@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/natsenack/wp-pdf-builder-pro
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

if ( ! ( $wpdb instanceof wpdb ) ) {
	return;
}

$pdfib_settings = get_option( 'pdfib_settings', array() );

$pdfib_delete_data =
	is_array( $pdfib_settings )
	&& isset( $pdfib_settings['pdfib_delete_on_uninstall'] )
	&& '1' === (string) $pdfib_settings['pdfib_delete_on_uninstall'];

if ( ! $pdfib_delete_data ) {
	return;
}

/**
 * Retrieves the list of plugin tables, using object cache when available.
 *
 * @param wpdb   $pdfib_wpdb         Global wpdb instance.
 * @param string $pdfib_like_pattern LIKE pattern for matching table names.
 * @return string[] List of matching table names.
 */
function pdfib_uninstall_get_tables( wpdb $pdfib_wpdb, string $pdfib_like_pattern ): array {
	$pdfib_cache_key   = 'pdfib_tables_' . md5( $pdfib_like_pattern );
	$pdfib_cache_group = 'pdfib_uninstall';
	$pdfib_cached      = wp_cache_get( $pdfib_cache_key, $pdfib_cache_group );
	if ( false !== $pdfib_cached ) {
		return (array) $pdfib_cached;
	}
	$pdfib_result = $pdfib_wpdb->get_col(
		$pdfib_wpdb->prepare( 'SHOW TABLES LIKE %s', $pdfib_like_pattern )
	);
	wp_cache_set( $pdfib_cache_key, $pdfib_result, $pdfib_cache_group );
	return $pdfib_result;
}

/**
 * Drops a plugin table and invalidates its related object cache entry.
 *
 * @param wpdb   $pdfib_wpdb        Global wpdb instance.
 * @param string $pdfib_table       Sanitized table name to drop.
 * @param string $pdfib_cache_key   Cache key to invalidate after the operation.
 * @param string $pdfib_cache_group Cache group for the invalidation.
 * @return void
 */
function pdfib_uninstall_drop_table(
	wpdb $pdfib_wpdb,
	string $pdfib_table,
	string $pdfib_cache_key,
	string $pdfib_cache_group
): void {
	$pdfib_wpdb->query( $pdfib_wpdb->prepare( 'DROP TABLE IF EXISTS %i', $pdfib_table ) );
	wp_cache_delete( $pdfib_cache_key, $pdfib_cache_group );
}

$pdfib_like_pattern = $wpdb->esc_like( $wpdb->prefix . 'pdfib_' ) . '%';
$pdfib_cache_key    = 'pdfib_tables_' . md5( $pdfib_like_pattern );
$pdfib_cache_group  = 'pdfib_uninstall';
$pdfib_tables       = pdfib_uninstall_get_tables( $wpdb, $pdfib_like_pattern );

foreach ( $pdfib_tables as $pdfib_table_name ) {
	$pdfib_sanitized_table = preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $pdfib_table_name );

	if ( '' === $pdfib_sanitized_table ) {
		continue;
	}

	pdfib_uninstall_drop_table( $wpdb, $pdfib_sanitized_table, $pdfib_cache_key, $pdfib_cache_group );
}

$pdfib_legacy_option_names = array(
	'pdfib_version',
	'pdfib_settings',
	'pdfib_license_key',
	'pdfib_license_status',
	'pdfib_license_data',
	'pdfib_license_expires',
	'pdfib_onboarding',
	'pdfib_gdpr',
);

foreach ( $pdfib_legacy_option_names as $pdfib_option_name ) {
	delete_option( $pdfib_option_name );
	delete_site_option( $pdfib_option_name );
}
