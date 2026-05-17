<?php
/**
 * PDF Editor Preferences Manager.
 * Remplace wp-preferences pour éviter les erreurs REST API.
 *
 * @package PDFIB\Core
 */

namespace PDFIB\Core;

use function add_action;
use function get_current_user_id;
use function sanitize_text_field;
use function wp_send_json_error;
use function wp_send_json_success;
use function current_user_can;
use function wp_register_script;
use function admin_url;


defined( 'ABSPATH' ) || exit;

/**
 * Manages editor preferences stored as user meta.
 */
class PDFEditorPreferences {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;
	/**
	 * Current user ID.
	 *
	 * @var int
	 */
	private int $user_id = 0;
	/**
	 * User meta key for preferences.
	 *
	 * @var string
	 */
	private string $preferences_key = 'pdfib_editor_preferences';




	/**
	 * Initialiser l'utilisateur et les hooks une fois WordPress chargé.
	 */
	public function init_user_and_hooks() {
		$this->user_id = get_current_user_id();
		$this->init_hooks();
	}

	/**
	 * Obtenir l'instance singleton.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialiser les hooks.
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_pdfib_editor_save_preferences', array( $this, 'ajax_save_preferences' ) );
		add_action( 'wp_ajax_pdfib_editor_get_preferences', array( $this, 'ajax_get_preferences' ) );
		// Charger AVANT les scripts wp-preferences par défaut.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1 );
	}

	/**
	 * Préférences par défaut.
	 */
	private function get_default_preferences() {
		return array(
			'canvas_zoom'                => 100,
			'canvas_grid_visible'        => true,
			'canvas_snap_to_grid'        => true,
			'toolbar_position'           => 'top',
			'theme'                      => 'light',
			'auto_save_enabled'          => true,
			'auto_save_interval'         => 30,
			'keyboard_shortcuts_enabled' => true,
			'show_element_outlines'      => true,
			'show_element_handles'       => true,
			'last_used_elements'         => array(),
			'recent_templates'           => array(),
			'editor_layout'              => 'default',
		);
	}

	/**
	 * Handler AJAX pour sauvegarder les préférences.
	 */
	public function ajax_save_preferences() {
		try {
			$nonce = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) );
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'pdfib_editor_preferences' ) ) {
				wp_send_json_error( array( 'message' => __( 'Sécurité: nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$pref_input  = isset( $_POST['preferences'] ) ? sanitize_textarea_field( wp_unslash( $_POST['preferences'] ) ) : '';
			$preferences = $this->parse_preferences_from_input( $pref_input );
			if ( ! is_array( $preferences ) ) {
				wp_send_json_error( array( 'message' => __( 'Données de préférences invalides', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}
			$result = $this->save_preferences( $preferences );
			if ( $result ) {
				wp_send_json_success(
					array(
						'message'     => __( 'Préférences sauvegardées', 'advanced-pdf-invoice-builder' ),
						'preferences' => $this->get_preferences(),
						'nonce'       => wp_create_nonce( 'pdfib_editor_preferences' ),
					)
				);
			} else {
				wp_send_json_error( array( 'message' => __( 'Erreur lors de la sauvegarde', 'advanced-pdf-invoice-builder' ) ) );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Erreur: ', 'advanced-pdf-invoice-builder' ) . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Parse une valeur de préférences (JSON string) déjà sanitizée en tableau PHP.
	 *
	 * @param string $pref_input Valeur POST 'preferences' sanitizée.
	 * @return array
	 */
	private function parse_preferences_from_input( string $pref_input ): array {
		if ( '' === $pref_input ) {
			return array();
		}
		$decoded = json_decode( $pref_input, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
		return array();
	}

	/**
	 * Handler AJAX pour récupérer les préférences.
	 */
	public function ajax_get_preferences() {
		try {
			// Vérifier le nonce.
			$nonce = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) );
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'pdfib_editor_preferences' ) ) {
				wp_send_json_error( array( 'message' => __( 'Sécurité: nonce invalide', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Vérifier les permissions.
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
				return;
			}

			// Récupérer les préférences.
			$preferences = $this->get_preferences();

			wp_send_json_success(
				array(
					'preferences' => $preferences,
					'nonce'       => wp_create_nonce( 'pdfib_editor_preferences' ),
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Erreur: ', 'advanced-pdf-invoice-builder' ) . esc_html( $e->getMessage() ) ) );
		}
	}

	/**
	 * Désactiver les scripts wp-preferences par défaut.
	 */
	/**
	 * Enregistrer les scripts wp-preferences vides pour éviter les erreurs de dépendance.
	 * Appelé très tôt (plugins_loaded avec priorité -1000) pour devancer WordPress.
	 */
	public function register_empty_wp_preferences() {
		// Deregister wp-preferences scripts to prevent conflicts with the PDF editor.
		// This prevents WordPress from loading these heavy Gutenberg scripts on PDF Builder pages.
		wp_deregister_script( 'wp-preferences' );
		wp_deregister_script( 'wp-preferences-persistence' );
	}

	/**
	 * Dequeue wp-preferences scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function dequeue_wp_preferences( string $hook ) {
		unset( $hook ); // signature imposée par le hook WP.
		// Désactiver les scripts wp-preferences qui causeraient des conflits.
		wp_dequeue_script( 'wp-preferences' );
		wp_dequeue_script( 'wp-preferences-persistence' );
		wp_dequeue_style( 'wp-preferences' );
	}

	/**
	 * Enregistrer les scripts JavaScript.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( string $hook ) {
		// Limiter aux pages du plugin PDF Builder uniquement.
		if ( strpos( $hook, 'pdf-builder' ) === false ) {
			return;
		}

		// Ajouter le script des préférences.
		wp_add_inline_script( 'jquery', $this->get_javascript_code(), 'after' );
	}

	/**
	 * Générer le code JavaScript pour les préférences.
	 */
	private function get_javascript_code() {
		$nonce         = wp_create_nonce( 'pdfib_editor_preferences' );
		$ajax_url      = admin_url( 'admin-ajax.php' );
		$current_prefs = $this->get_preferences();
		$prefs_json    = wp_json_encode( $current_prefs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		return "(function(\$) { 'use strict';\n" .
			"window.PDFEditorPreferences = {\n" .
			"nonce: '" . esc_js( $nonce ) . "',\n" .
			"ajaxUrl: '" . esc_url( $ajax_url ) . "',\n" .
			'preferences: ' . $prefs_json . ",\n" .
			"_isDirty: false, _saveTimer: null, _initialized: false, _overrideRetries: 0,\n" .
			"init: function() { if (this._initialized) { return; } this._initialized = true; this.overrideWPPreferences(); this.bindEvents(); this.loadPreferences(); },\n" .
			$this->get_js_override_wp_method() .
			$this->get_js_bind_and_load_methods( $ajax_url ) .
			$this->get_js_save_and_get_methods( $ajax_url ) .
			"};\n" .
			"\$( document ).ready(function() { window.PDFEditorPreferences.init(); });\n" .
			"})(jQuery);\n";
	}

	/** JS: méthode overrideWPPreferences. */
	private function get_js_override_wp_method(): string {
		return "overrideWPPreferences: function() {
            if (typeof wp !== 'undefined' && wp.preferences) {
                var self = this;
                if (wp.preferences.__internalSetTransport) {
                    wp.preferences.__internalSetTransport({
                        get: function(key, defaultValue) { return Promise.resolve(self.getPreference(key, defaultValue)); },
                        set: function(key, value) { return new Promise(function(resolve) { self.setPreference(key, value); resolve(); }); }
                    });
                } else {
                    wp.preferences.get = function(key, defaultValue) { return self.getPreference(key, defaultValue); };
                    wp.preferences.set = function(key, value) { self.setPreference(key, value); };
                }
            } else {
                if (this._overrideRetries < 50) { this._overrideRetries++; var self=this; setTimeout(function() { self.overrideWPPreferences(); }, 10); }
            }
        },\n";
	}

	/**
	 * JS: méthodes bindEvents et loadPreferences.
	 *
	 * @param string $ajax_url Admin AJAX URL.
	 */
	private function get_js_bind_and_load_methods( string $ajax_url ): string {
		unset( $ajax_url ); // signature unifiée avec les autres générateurs JS.
		return "bindEvents: function() {
            var self=this;
            \$(document).on('pdf-editor-preference-changed', function(e, key, value) { self.setPreference(key, value); });
            if (this.preferences.auto_save_enabled) {
                setInterval(function() { if (self._isDirty) { self.savePreferences(); } }, this.preferences.auto_save_interval * 1000);
            }
        },
        loadPreferences: function() {
            var self=this;
            \$.ajax({ url: this.ajaxUrl, type: 'GET', data: { action: 'pdfib_editor_get_preferences', nonce: this.nonce },
                success: function(response) {
                    if (response.success && response.data.preferences) {
                        self.preferences=response.data.preferences;
                        if (response.data.nonce) { self.nonce=response.data.nonce; }
                        \$(document).trigger('pdf-editor-preferences-loaded', [self.preferences]);
                    }
                },
                error: function(xhr, status, error) { console.warn('[PDF Editor Preferences] Erreur chargement:', error); }
            });
        },\n";
	}

	/**
	 * JS: méthodes savePreferences, setPreference, getPreference, getAllPreferences.
	 *
	 * @param string $ajax_url Admin AJAX URL.
	 */
	private function get_js_save_and_get_methods( string $ajax_url ): string {
		unset( $ajax_url ); // signature unifiée avec les autres générateurs JS.
		return "savePreferences: function(callback) {
            var self=this;
            \$.ajax({ url: this.ajaxUrl, type: 'POST', data: { action: 'pdfib_editor_save_preferences', nonce: this.nonce, preferences: JSON.stringify(this.preferences) },
                success: function(response) {
                    if (response.success) {
                        self._isDirty=false;
                        if (response.data.nonce) { self.nonce=response.data.nonce; }
                        \$(document).trigger('pdf-editor-preferences-saved', [self.preferences]);
                        if (callback) callback(true);
                    } else { console.error('[PDF Editor Preferences] Erreur sauvegarde:', response.data.message); if (callback) callback(false); }
                },
                error: function(xhr, status, error) { console.error('[PDF Editor Preferences] Erreur AJAX:', error); if (callback) callback(false); }
            });
        },
        setPreference: function(key, value) { this.preferences[key]=value; this._isDirty=true; \$(document).trigger('pdf-editor-preference-updated', [key, value]); },
        getPreference: function(key, defaultValue) { return this.preferences[key] !== undefined ? this.preferences[key] : defaultValue; },
        getAllPreferences: function() { return this.preferences; }\n";
	}








	/**
	 * Constructeur privé pour singleton.
	 */
	private function __construct() {
		// Différer l'initialisation de l'user_id jusqu'à ce que WordPress soit prêt.
		add_action( 'init', array( $this, 'init_user_and_hooks' ) );
	}










	/**
	 * Récupérer les préférences utilisateur.
	 */
	public function get_preferences() {
		// S'assurer que user_id est défini.
		if ( ! $this->user_id ) {
			$this->user_id = get_current_user_id();
		}

		if ( ! $this->user_id ) {
			return $this->get_default_preferences();
		}

		$preferences = get_user_meta( $this->user_id, $this->preferences_key, true );

		if ( empty( $preferences ) ) {
			return $this->get_default_preferences();
		}

		return array_merge( $this->get_default_preferences(), $preferences );
	}

	/**
	 * Sauvegarder les préférences utilisateur.
	 *
	 * @param array $preferences Preferences to save.
	 */
	public function save_preferences( array $preferences ) {
		// S'assurer que user_id est défini.
		if ( ! $this->user_id ) {
			$this->user_id = get_current_user_id();
		}

		if ( ! $this->user_id ) {
			return false;
		}

		$sanitized = $this->sanitize_preferences( $preferences );

		update_user_meta( $this->user_id, $this->preferences_key, $sanitized );

		// Vérifier si la sauvegarde a réussi en comparant la valeur sauvegardée.
		$saved_value = get_user_meta( $this->user_id, $this->preferences_key, true );
		return $saved_value === $sanitized;
	}

	/**
	 * Sanitize preferences array.
	 *
	 * @param array $preferences Preferences to sanitize.
	 */
	private function sanitize_preferences( array $preferences ) {
		if ( ! is_array( $preferences ) ) {
			return array();
		}

		$sanitized = array();

		// Zoom.
		if ( isset( $preferences['canvas_zoom'] ) ) {
			$sanitized['canvas_zoom'] = max( 10, min( 500, intval( $preferences['canvas_zoom'] ) ) );
		}

		// Booléens.
		$boolean_fields = array(
			'canvas_grid_visible',
			'canvas_snap_to_grid',
			'auto_save_enabled',
			'keyboard_shortcuts_enabled',
			'show_element_outlines',
			'show_element_handles',
		);

		foreach ( $boolean_fields as $field ) {
			if ( isset( $preferences[ $field ] ) ) {
				$sanitized[ $field ] = (bool) $preferences[ $field ];
			}
		}

		// Chaînes.
		$string_fields = array( 'toolbar_position', 'theme', 'editor_layout' );
		foreach ( $string_fields as $field ) {
			if ( isset( $preferences[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_text_field( $preferences[ $field ] );
			}
		}

		// Nombres.
		if ( isset( $preferences['auto_save_interval'] ) ) {
			$sanitized['auto_save_interval'] = max( 5, min( 300, intval( $preferences['auto_save_interval'] ) ) );
		}

		// Tableaux.
		$array_fields = array( 'last_used_elements', 'recent_templates' );
		foreach ( $array_fields as $field ) {
			if ( isset( $preferences[ $field ] ) && is_array( $preferences[ $field ] ) ) {
				$sanitized[ $field ] = array_map( 'sanitize_text_field', $preferences[ $field ] );
			}
		}

		return $sanitized;
	}
}
