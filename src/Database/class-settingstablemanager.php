<?php
/**
 * Advanced PDF Invoice Builder - Database Table Management.
 * Gère la table {prefix}pdfib_settings personnalisée.
 *
 * @package PDFIB\Database
 */

namespace PDFIB\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the {prefix}pdfib_settings custom database table.
 */
class SettingsTableManager {



	/**
	 * Nom de la table SANS préfixe (le préfixe est toujours ajouté via pdfib_db()->prefix).
	 * Ancienne constante gardée pour compatibilité ascendante — ne jamais l'utiliser dans des queries SQL.
	 */
	const TABLE_SUFFIX = 'pdfib_settings';
	const TABLE_NAME   = 'pdfib_settings'; // Sans préfixe — ex: {prefix}pdfib_settings.

	/**
	 * Nom de l'ancienne table hardcodée avec préfixe "wp_" (migration).
	 * Certains sites avaient cette table créée avec le préfixe wp_ littéral
	 * au lieu du préfixe WordPress réel (pdfib_db()->prefix).
	 */
	const LEGACY_TABLE_NAME = 'wp_pdfib_settings';

	const LEGACY_OPTION_KEY = 'pdfib_settings';
	const ARRAY_A           = 2; // WordPress constant for associative array results.

	/**
	 * Retourne le nom complet de la table avec le bon préfixe WordPress.
	 * Le préfixe est lu directement depuis $table_prefix (défini dans wp-config.php).
	 */
	public static function get_table_name(): string {
		global $table_prefix;
		return $table_prefix . 'pdfib_settings';
	}

	/**
	 * Créer la table lors de l'activation.
	 */
	public static function create_table() {
		$table_name      = self::get_table_name();
		$charset_collate = pdfib_db()->get_charset_collate();

		// Vérifier si la table existe déjà.

		$table_exists = pdfib_db()->get_var(
			pdfib_db()->prepare(
				'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$table_name
			)
		);

		if ( $table_exists ) {
			return true;
		}

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            option_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            option_name varchar(191) NOT NULL DEFAULT '',
            option_value longtext NOT NULL,
            autoload varchar(20) NOT NULL DEFAULT 'yes',
            PRIMARY KEY (option_id),
            UNIQUE KEY option_name (option_name)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$table_exists = pdfib_db()->get_var(
			pdfib_db()->prepare(
				'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$table_name
			)
		);

		return (bool) $table_exists;
	}

	/**
	 * Récupérer une option depuis la table {prefix}pdfib_settings.
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $fallback    Default value if not found.
	 */
	public static function get_option( string $option_name, mixed $fallback = false ) {
		$cache_key   = 'pdfib_option_' . md5( $option_name );
		$cache_group = 'pdfib_settings';

		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( false !== $cached ) {
			// Special sentinel for cached NULL/default (avoids repeated DB queries for absent options).
			return ( '__pdfib_not_found__' === $cached ) ? $fallback : $cached;
		}

		$table_name = self::get_table_name();

		$option_value = pdfib_db()->get_var(
			pdfib_db()->prepare( 'SELECT option_value FROM %i WHERE option_name = %s', $table_name, $option_name )
		);

		if ( null === $option_value ) {
			wp_cache_set( $cache_key, '__pdfib_not_found__', $cache_group );
			return $fallback;
		}

		// Source sûre : données issues de la table BDD du plugin.
		$unserialized = maybe_unserialize( $option_value );
		$value        = is_string( $unserialized ) ? sanitize_text_field( $unserialized ) : $unserialized;

		wp_cache_set( $cache_key, $value, $cache_group );

		return $value;
	}

	/**
	 * Mettre à jour une option dans la table personnalisée.
	 *
	 * @param string $option_name  Option name.
	 * @param mixed  $option_value Option value.
	 * @param string $autoload     Whether to autoload.
	 */
	public static function update_option( string $option_name, mixed $option_value, string $autoload = 'yes' ) {
		$table_name = self::get_table_name();

		// Sérialiser si nécessaire.
		$serialized_value = maybe_serialize( $option_value );

		// Retry logic with exponential backoff to handle database deadlocks.
		$max_retries = 3;
		$retry_count = 0;
		$result      = false;

		while ( $retry_count < $max_retries ) {
			$result = pdfib_db()->replace(
				$table_name,
				array(
					'option_name'  => $option_name,
					'option_value' => $serialized_value,
					'autoload'     => $autoload,
				),
				array( '%s', '%s', '%s' )
			);

			// If successful, break out of retry loop.
			if ( false !== $result ) {
				break;
			}

			// Check if the error is a deadlock.
			$last_error = pdfib_db()->last_error;
			if ( false !== strpos( $last_error, 'Deadlock' ) ) {
				// Exponential backoff: 100ms, 200ms, 400ms.
				$backoff_ms = 100 * ( 2 ** $retry_count );
				usleep( $backoff_ms * 1000 );
				++$retry_count;
			} else {
				// Non-deadlock error, stop retrying.
				break;
			}
		}

		// Invalidate the object cache so subsequent get_option calls reflect the new value.
		if ( false !== $result ) {
			wp_cache_delete( 'pdfib_option_' . md5( $option_name ), 'pdfib_settings' );
		}

		return false !== $result;
	}

	/**
	 * Supprimer une option depuis la table personnalisée.
	 *
	 * @param string $option_name Option name.
	 */
	public static function delete_option( string $option_name ) {
		$table_name = self::get_table_name();

		$result = pdfib_db()->delete(
			$table_name,
			array( 'option_name' => $option_name ),
			array( '%s' )
		);

		// Invalidate the object cache.
		if ( false !== $result ) {
			wp_cache_delete( 'pdfib_option_' . md5( $option_name ), 'pdfib_settings' );
		}

		return false !== $result;
	}

	/**
	 * Récupérer tous les options PDF Builder.
	 */
	public static function get_all_options() {
		$table_name = self::get_table_name();

		$options = pdfib_db()->get_results(
			pdfib_db()->prepare( 'SELECT option_name, option_value FROM %i', $table_name ),
			self::ARRAY_A
		);

		if ( empty( $options ) ) {
			return array();
		}

		$result = array();
		foreach ( $options as $option ) {
			// Source sûre : données issues de la table BDD du plugin.
			$value                            = maybe_unserialize( $option['option_value'] );
			$result[ $option['option_name'] ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
		}

		return $result;
	}
}
