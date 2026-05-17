<?php
/**
 * Advanced PDF Invoice Builder - AJAX Handlers.
 *
 * @package PDFIB\AJAX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

defined( 'PDFIB_SQL_TEMPLATE_DATA' ) || define( 'PDFIB_SQL_TEMPLATE_DATA', 'SELECT template_data FROM %i WHERE id = %d' );
defined( 'PDFIB_VARIABLE_MAPPER_CLASS' ) || define( 'PDFIB_VARIABLE_MAPPER_CLASS', '\\PDFIB\\Managers\\PDFBuilderVariableMapper' );

if ( ! function_exists( 'pdfib_err_nonce' ) ) {
	/**
	 * Returns the translated "invalid nonce" error message.
	 *
	 * @return string
	 */
	function pdfib_err_nonce(): string {
		return __( 'Invalid nonce.', 'advanced-pdf-invoice-builder' );
	}
}

if ( ! function_exists( 'pdfib_err_perms' ) ) {
	/**
	 * Returns the translated "insufficient permissions" error message.
	 *
	 * @return string
	 */
	function pdfib_err_perms(): string {
		return __( 'Insufficient permissions.', 'advanced-pdf-invoice-builder' );
	}
}

/**
 * Advanced PDF Invoice Builder - Classe de base pour les handlers AJAX
 * Centralise la validation commune et la gestion d'erreurs
 */

/**
 * Fonction utilitaire pour sauvegarder les rôles autorisés
 *
 * @param mixed $value Valeur brute des rôles
 * @return array Tableau des rôles traités
 */
/**
 * Préserve les champs "settings" d'un template stockés en DB lors d'une sauvegarde
 * partielle (ex: sauvegarde éditeur qui n'envoie pas category, canvas_format, etc.).
 *
 * @param int   $template_id  ID du template.
 * @param array $template_data Données en cours de sauvegarde (modifiées par référence).
 * @param \wpdb $db           Instance wpdb.
 */
function pdfib_preserve_template_settings_fields( int $template_id, array &$template_data, $db = null ): void {
	if ( null === $db ) {
		$db = pdfib_db();
	}
	$table    = $db->prefix . 'pdfib_templates';
	$existing = $db->get_row(
		$db->prepare( PDFIB_SQL_TEMPLATE_DATA, $table, $template_id ),
		ARRAY_A
	);
	if ( ! $existing ) {
		return;
	}
	$existing_data = ! empty( $existing['template_data'] ) ? json_decode( $existing['template_data'], true ) : array();
	foreach ( array( 'category', 'canvas_format', 'canvas_orientation', 'canvas_dpi', 'description' ) as $field ) {
		if ( isset( $existing_data[ $field ] ) && ! isset( $template_data[ $field ] ) ) {
			$template_data[ $field ] = $existing_data[ $field ];
		}
	}
}

/**
 * Sauvegarde les rôles autorisés.
 *
 * @param mixed $value Valeur brute des rôles.
 * @return array Tableau des rôles traités.
 */
function pdfib_save_allowed_roles( mixed $value ) {
	$roles = array();

	if ( is_array( $value ) ) {
		$roles = $value;
	} elseif ( is_string( $value ) ) {
		if ( strpos( $value, '[' ) === 0 || strpos( $value, '{' ) === 0 ) {
			$decoded = json_decode( $value, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$roles = $decoded;
			}
		} else {
				// Si c'est une liste séparée par des virgules.
			$roles = array_map( 'trim', explode( ',', $value ) );
		}
	}

	$valid_roles     = array();
	$wp_roles        = wp_roles();
	$available_roles = $wp_roles ? array_keys( $wp_roles->roles ) : array();

	foreach ( $roles as $role ) {
		if ( in_array( $role, $available_roles, true ) ) {
			$valid_roles[] = $role;
		}
	}

	return $valid_roles;
}

/**
 * Initialise les handlers AJAX pour Advanced PDF Invoice Builder
 *
 * SYSTÈME AJAX :
 * =============
 * - pdfib_save_settings / pdfib_save_all_settings : PdfBuilderUnifiedAjaxHandler
 * - pdfib_save_template / pdfib_load_template     : PdfBuilderTemplateAjaxHandler (ci-dessous)
 * - pdfib_delete_template et templates prédéfinis  : PdfBuilderTemplatesAjax
 * - Canvas, maintenance, RGPD, licence             : PdfBuilderUnifiedAjaxHandler
 */
function pdfib_init_ajax_handlers() {
	// Template handler - Gère save/load des templates.
	// Note: pdfib_save_all_settings géré par PdfBuilderUnifiedAjaxHandler.
	// Note: pdfib_delete_template géré par PdfBuilderTemplatesAjax (ownership check + hook).
	$template_handler = new \PDFIB\AJAX\PdfBuilderTemplateAjaxHandler();
	add_action( 'wp_ajax_pdfib_save_template', array( $template_handler, 'handleSaveDirect' ) );
	add_action( 'wp_ajax_pdfib_load_template', array( $template_handler, 'handleLoadDirect' ) );
}

// Initialiser les handlers unifiés.
add_action( 'init', 'pdfib_init_ajax_handlers' );

/**
 * Handler AJAX pour réinitialiser les paramètres canvas par défaut
 */
function pdfib_reset_canvas_defaults_handler() {
	if ( ! isset( $GLOBALS['_POST']['nonce'] ) || ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_nonce() ), 403 );
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_perms() ), 403 );
		return;
	}
	try {
		$default_canvas_settings = pdfib_get_default_canvas_settings();
		$current_settings        = pdfib_get_option( 'pdfib_settings', array() );
		$updated_settings        = array_merge( $current_settings, $default_canvas_settings );
		$success_count           = 0;
		foreach ( $default_canvas_settings as $key => $value ) {
			if ( update_option( $key, $value ) ) {
				++$success_count;
			}
		}
		pdfib_update_option( 'pdfib_settings', $updated_settings );
		if ( $success_count > 0 ) {
			wp_send_json_success(
				array(
					'message'     => __( 'Paramètres canvas réinitialisés avec succès', 'advanced-pdf-invoice-builder' ),
					'reset_count' => $success_count,
					'timestamp'   => time(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Échec de la sauvegarde des paramètres', 'advanced-pdf-invoice-builder' ) ), 500 );
		}
	} catch ( Exception $e ) {
		wp_send_json_error( array( 'message' => __( 'Erreur lors de la réinitialisation: ', 'advanced-pdf-invoice-builder' ) . esc_html( $e->getMessage() ) ), 500 );
	}
}

/**
 * Retourne les paramètres canvas par défaut
 */
function pdfib_get_default_canvas_settings(): array {
	return array(
		'pdfib_canvas_width'               => '794',
		'pdfib_canvas_height'              => '1123',
		'pdfib_canvas_dpi'                 => '96',
		'pdfib_canvas_format'              => 'A4',
		'pdfib_canvas_bg_color'            => '#ffffff',
		'pdfib_canvas_border_color'        => '#cccccc',
		'pdfib_canvas_border_width'        => '1',
		'pdfib_canvas_shadow_enabled'      => '0',
		'pdfib_canvas_container_bg_color'  => '#f8f9fa',
		'pdfib_canvas_grid_enabled'        => '1',
		'pdfib_canvas_grid_size'           => '20',
		'pdfib_canvas_guides_enabled'      => '1',
		'pdfib_canvas_snap_to_grid'        => '1',
		'pdfib_canvas_zoom_min'            => '25',
		'pdfib_canvas_zoom_max'            => '500',
		'pdfib_canvas_zoom_default'        => '100',
		'pdfib_canvas_zoom_step'           => '25',
		'pdfib_canvas_export_quality'      => '90',
		'pdfib_canvas_export_format'       => 'png',
		'pdfib_canvas_export_transparent'  => '0',
		'pdfib_canvas_drag_enabled'        => '1',
		'pdfib_canvas_resize_enabled'      => '1',
		'pdfib_canvas_rotate_enabled'      => '0',
		'pdfib_canvas_multi_select'        => '1',
		'pdfib_canvas_selection_mode'      => 'single',
		'pdfib_canvas_fps_target'          => '60',
		'pdfib_canvas_memory_limit_js'     => '256',
		'pdfib_canvas_memory_limit_php'    => '256',
		'pdfib_canvas_response_timeout'    => '30',
		'pdfib_canvas_lazy_loading_editor' => '1',
		'pdfib_canvas_lazy_loading_plugin' => '1',
		'pdfib_canvas_preload_critical'    => '1',
		'pdfib_canvas_debug_enabled'       => '0',
		'pdfib_canvas_error_reporting'     => '0',
	);
}

/**
 * Handler AJAX pour vérifier la cohérence des paramètres canvas avec la base de données
 */
function pdfib_verify_canvas_settings_consistency_handler() {
	try {
		if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
			wp_send_json_error( array( 'message' => pdfib_err_nonce() ), 403 );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => pdfib_err_perms() ) );
			return;
		}

		$settings        = pdfib_get_option( 'pdfib_settings', array() );
		$canvas_settings = array();
		foreach ( $settings as $key => $value ) {
			if ( strpos( $key, 'pdfib_canvas_' ) === 0 ) {
				$canvas_settings[ $key ] = $value;
			}
		}

		wp_send_json_success( $canvas_settings );
	} catch ( Exception $e ) {
		wp_send_json_error( array( 'message' => __( 'Erreur lors de la vérification: ', 'advanced-pdf-invoice-builder' ) . esc_html( $e->getMessage() ) ) );
	}
}

add_action( 'wp_ajax_pdfib_get_allowed_roles', 'pdfib_get_allowed_roles_ajax_handler' );
if ( ! function_exists( 'pdfib_get_allowed_roles_ajax_handler' ) ) {
	/**
	 * Handler AJAX pour récupérer les rôles autorisés
	 */
	function pdfib_get_allowed_roles_ajax_handler() {
		if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce' ) ) {
			wp_send_json_error( array( 'message' => pdfib_err_nonce() ), 403 );
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => pdfib_err_perms() ), 403 );
			return;
		}
		$settings      = pdfib_get_option( 'pdfib_settings', array() );
		$allowed_roles = isset( $settings['pdfib_allowed_roles'] ) ? (array) $settings['pdfib_allowed_roles'] : array( 'administrator' );
		$allowed_roles = array_values( array_filter( $allowed_roles ) );
		wp_send_json_success( array( 'allowed_roles' => $allowed_roles ) );
	}
}


add_action( 'wp_ajax_pdfib_verify_canvas_settings_consistency', 'pdfib_verify_canvas_settings_consistency_handler' );
add_action( 'wp_ajax_pdfib_cron_test', 'pdfib_cron_test_handler' );
add_action( 'wp_ajax_pdfib_check_wp_cron_config', 'pdfib_check_wp_cron_config_handler' );
add_action( 'wp_ajax_pdfib_check_scheduled_tasks', 'pdfib_check_scheduled_tasks_handler' );
add_action( 'wp_ajax_pdfib_diagnose_cron', 'pdfib_diagnose_cron_handler' );
add_action( 'wp_ajax_pdfib_repair_cron', 'pdfib_repair_cron_handler' );


/**
 * Handler AJAX pour tester le cron WordPress.
 */
function pdfib_cron_test_handler(): void {
	if ( ! check_ajax_referer( 'pdfib_cron_test', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_nonce() ), 403 );
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_perms() ), 403 );
		return;
	}
	wp_send_json_success(
		array(
			'cron_accessible' => true,
			'timestamp'       => time(),
		)
	);
}

/**
 * Handler AJAX pour vérifier la configuration WP-Cron.
 */
function pdfib_check_wp_cron_config_handler(): void {
	if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_nonce() ), 403 );
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_perms() ), 403 );
		return;
	}
	wp_send_json_success( array( 'cron_disabled' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) );
}

/**
 * Handler AJAX pour vérifier les tâches planifiées.
 */
function pdfib_check_scheduled_tasks_handler(): void {
	if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_nonce() ), 403 );
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_perms() ), 403 );
		return;
	}
	$cron_array  = _get_cron_array();
	$pdfib_tasks = array();
	if ( is_array( $cron_array ) ) {
		foreach ( $cron_array as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $events ) {
				if ( strpos( $hook, 'pdfib' ) !== false ) {
					$pdfib_tasks[] = array(
						'hook'     => $hook,
						'next_run' => $timestamp,
					);
				}
			}
		}
	}
	wp_send_json_success( array( 'scheduled_tasks' => $pdfib_tasks ) );
}

/**
 * Handler AJAX pour diagnostiquer le cron.
 */
function pdfib_diagnose_cron_handler(): void {
	if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_nonce() ), 403 );
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_perms() ), 403 );
		return;
	}
	$cron_array = _get_cron_array();
	$tasks      = array();
	if ( is_array( $cron_array ) ) {
		foreach ( $cron_array as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $events ) {
				if ( strpos( $hook, 'pdfib' ) !== false ) {
					$tasks[ $hook ] = array(
						'next_run' => wp_date( 'Y-m-d H:i:s', $timestamp ),
						'count'    => count( $events ),
					);
				}
			}
		}
	}
	wp_send_json_success(
		array(
			'cron_disabled' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'tasks'         => $tasks,
			'total_tasks'   => count( $tasks ),
		)
	);
}

/**
 * Handler AJAX pour réparer le cron.
 */
function pdfib_repair_cron_handler(): void {
	if ( ! check_ajax_referer( 'pdfib_ajax', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_nonce() ), 403 );
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => pdfib_err_perms() ), 403 );
		return;
	}
	do_action( 'pdfib_reschedule_cron_events' );
	wp_send_json_success(
		array(
			'message'   => 'Cron events rescheduled',
			'timestamp' => time(),
		)
	);
}
