<?php
/**
 * Advanced PDF Invoice Builder - Onboarding Manager.
 *
 * @package PDF_Builder_Pro
 * @since   1.6.12
 */

namespace PDFIB\Utilities;

use Exception;
use PdfBuilderNonceManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced PDF Invoice Builder - Onboarding Manager.
 *
 * Classe pour gérer l'onboarding et les tutoriels.
 */
class PdfBuilderOnboardingManager {


	/**
	 * Instance unique.
	 *
	 * @var ?self
	 */
	private static ?self $instance = null;
	/**
	 * Options d'onboarding.
	 *
	 * @var array
	 */
	private array $onboarding_options = array();
	/**
	 * Constructeur privé (Singleton)
	 */
	private function __construct() {
		// Vérification de sécurité - s'assurer que WordPress est chargé.
		if ( ! defined( 'ABSPATH' ) ) {
			PdfBuilderNonceManager::debug_log( 'php_errors', 'ABSPATH non défini - WordPress pas chargé' );
			return;
		}
		$this->init_hooks();
		$this->load_onboarding_options();
	}
	/**
	 * Obtenir l'instance unique avec gestion d'erreur.
	 */
	public static function get_instance(): ?self {
		if ( null === self::$instance ) {
			try {
				self::$instance = new self();
			} catch ( Exception $e ) {
				PdfBuilderNonceManager::debug_log( 'php_errors', 'Erreur lors de l\'instanciation: ' . $e->getMessage() );
				return null;
			}
		}
		return self::$instance;
	}


	/**
	 * Charger les options d'onboarding
	 */
	private function load_onboarding_options(): void {
		$this->onboarding_options = pdfib_get_option(
			'pdfib_onboarding',
			$this->build_default_onboarding_options()
		);
	}
	/**
	 * Sauvegarder les options d'onboarding
	 */
	private function save_onboarding_options(): void {
		pdfib_update_option( 'pdfib_onboarding', $this->onboarding_options );
	}

	/** Verifie nonce + permissions pour les endpoints AJAX onboarding. */
	private function assert_onboarding_ajax_access(): void {
		check_ajax_referer( 'pdfib_onboarding', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) );
		}
	}

	/** Cree la structure d'options onboarding par defaut.
	 *
	 * @param bool $with_reset_timestamp Inclure un timestamp de reinitialisation.
	 */
	private function build_default_onboarding_options( bool $with_reset_timestamp = false ): array {
		$options = array(
			'completed'       => false,
			'current_step'    => 0,
			'steps_completed' => array(),
			'skipped'         => false,
			'first_login'     => time(),
			'last_activity'   => time(),
		);

		if ( $with_reset_timestamp ) {
			$options['reset_at'] = time();
		}

		return $options;
	}

	/** Met a jour un champ onboarding, puis sauvegarde et renvoie success.
	 *
	 * @param string $key   Cle de l'option.
	 * @param mixed  $value Valeur a sauvegarder.
	 */
	private function update_onboarding_field_and_respond( string $key, mixed $value ): void {
		$this->onboarding_options[ $key ]          = $value;
		$this->onboarding_options['last_activity'] = time();
		$this->save_onboarding_options();
		wp_send_json_success();
	}


	/**
	 * Verifier le statut d'onboarding (appele via admin_enqueue_scripts).
	 *
	 * @param string $hook Nom du hook admin courant.
	 */
	public function check_onboarding_status( string $hook ): void {
		// Enqueue, localize et wizard — temporairement désactivés (à réimplémenter).
	}
	/**
	 * Vérifier si l'onboarding est terminé
	 */
	public function is_onboarding_completed(): bool {
		return $this->onboarding_options['completed'];
	}
	/**
	 * Verifier si l'onboarding a ete ignore.
	 */
	public function is_onboarding_skipped(): bool {
		return $this->onboarding_options['skipped'];
	}

	/**
	 * Obtenir toutes les étapes d'onboarding
	 */
	public function get_onboarding_steps(): array {
		return $this->get_onboarding_steps_impl();
	}

	/**
	 * Retourne les etapes d'onboarding.
	 */
	private function get_onboarding_steps_impl(): array {
		$steps = array(
			1 => array(
				'id'          => 'welcome',
				'title'       => __( 'Bienvenue dans Advanced PDF Invoice Builder', 'advanced-pdf-invoice-builder' ),
				'description' => __( 'Découvrez le constructeur de PDF professionnel le plus avancé pour WordPress.', 'advanced-pdf-invoice-builder' ),
				'content'     => $this->get_step_content( 'welcome' ),
				'action'      => __( 'Suivant', 'advanced-pdf-invoice-builder' ),
				'action_type' => 'next',
				'can_skip'    => false,
			),
			2 => array(
				'id'                 => 'first_template',
				'title'              => __( 'Choisissez votre template de départ', 'advanced-pdf-invoice-builder' ),
				'description'        => __( 'Sélectionnez un template professionnel pour commencer.', 'advanced-pdf-invoice-builder' ),
				'content'            => $this->get_step_content( 'first_template' ),
				'action'             => __( 'Suivant', 'advanced-pdf-invoice-builder' ),
				'action_type'        => 'next',
				'can_skip'           => true,
				'skip_text'          => __( 'Ignorer l\'étape', 'advanced-pdf-invoice-builder' ),
				'requires_selection' => true,
			),
			3 => array(
				'id'                 => 'assign_template',
				'title'              => __( 'Configurez votre template', 'advanced-pdf-invoice-builder' ),
				'description'        => __( 'Assignez et personnalisez votre template sélectionné.', 'advanced-pdf-invoice-builder' ),
				'content'            => $this->get_step_content( 'assign_template' ),
				'action'             => __( 'Suivant', 'advanced-pdf-invoice-builder' ),
				'action_type'        => 'next',
				'can_skip'           => true,
				'skip_text'          => __( 'Configurer plus tard', 'advanced-pdf-invoice-builder' ),
				'requires_selection' => true,
			),
		);
		// Étape WooCommerce ajoutée dynamiquement si disponible.
		$steps[4] = array(
			'id'          => 'completed',
			'title'       => __( 'Configuration terminée !', 'advanced-pdf-invoice-builder' ),
			'description' => __( 'Votre Advanced PDF Invoice Builder est prêt à être utilisé.', 'advanced-pdf-invoice-builder' ),
			'content'     => $this->get_step_content( 'completed' ),
			'action'      => __( 'Commencer à créer', 'advanced-pdf-invoice-builder' ),
			'action_type' => 'finish',
			'can_skip'    => false,
		);
		return $steps;
	}
	/**
	 * Obtenir le contenu d'une etape.
	 *
	 * @param string $step_id Identifiant de l'etape.
	 */
	private function get_step_content( string $step_id ) {
		$resolver = $this->resolve_step_content_method( $step_id );
		if ( null === $resolver ) {
			return '';
		}

		return $this->{$resolver}();
	}

	/**
	 * Retourne le nom de methode de rendu associe a un step_id.
	 *
	 * @param string $step_id Identifiant de l'etape.
	 */
	private function resolve_step_content_method( string $step_id ): ?string {
		$method_map = array(
			'welcome'           => 'get_step_content_welcome',
			'environment_check' => 'get_step_content_environment_check',
			'first_template'    => 'get_step_content_first_template',
			'assign_template'   => 'get_step_content_assign_template',
			'woocommerce_setup' => 'get_step_content_woocommerce_setup',
			'completed'         => 'get_step_content_completed',
		);

		return $method_map[ $step_id ] ?? null;
	}

	/**
	 * Genere le contenu HTML de l'etape de bienvenue.
	 */
	private function get_step_content_welcome(): string {
		return '
                    <div class="onboarding-welcome">
                        <div class="welcome-features">
                            <div class="feature-item">
                                <span class="feature-icon">🎨</span>
                                <h4>' . __( 'Éditeur Visuel Avancé', 'advanced-pdf-invoice-builder' ) . '</h4>
                                <p>' . __( 'Interface drag & drop intuitive avec canvas interactif', 'advanced-pdf-invoice-builder' ) . '</p>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">🛒</span>
                                <h4>' . __( 'Intégration WooCommerce', 'advanced-pdf-invoice-builder' ) . '</h4>
                                <p>' . __( 'Génération automatique de factures, devis et bons de livraison', 'advanced-pdf-invoice-builder' ) . '</p>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">⚡</span>
                                <h4>' . __( 'Performance Optimisée', 'advanced-pdf-invoice-builder' ) . '</h4>
                                <p>' . __( 'Rendu rapide et fiable avec aperçu temps réel', 'advanced-pdf-invoice-builder' ) . '</p>
                            </div>
                        </div>
                    </div>
                ';
	}

	/**
	 * Genere le contenu HTML de l'etape de verification d'environnement.
	 */
	private function get_step_content_environment_check(): string {
		$checks  = $this->perform_environment_checks();
		$content = '<div class="environment-checks">';
		foreach ( $checks as $check ) {
			$status_class = $check['status'] ? 'success' : 'warning';
			$status_icon  = $check['status'] ? '✅' : '⚠️';
			$content     .= '
                        <div class="check-item ' . $status_class . '">
                            <span class="check-icon">' . $status_icon . '</span>
                            <div class="check-content">
                                <h5>' . esc_html( $check['title'] ) . '</h5>
                                <p>' . esc_html( $check['description'] ) . '</p>
                            </div>
                        </div>
                    ';
		}
		$content .= '</div>';
		return $content;
	}



	/**
	 * Genere le contenu HTML de l'etape de selection du premier template.
	 */
	private function get_step_content_first_template(): string {
		$predefined_templates = $this->get_predefined_templates();
		$template_cards       = '';
		foreach ( $predefined_templates as $template ) {
			$template_cards .= '
                        <div class="template-card" data-template="' . esc_attr( $template['id'] ) . '" data-tooltip="' . esc_attr( $template['description'] ) . '">
                            <div class="pdfb-template-preview">
                                <span class="template-icon">' . esc_html( $template['icon'] ) . '</span>
                            </div>
                            <h4>' . esc_html( $template['name'] ) . '</h4>
                            <p>' . esc_html( $template['short_description'] ) . '</p>
                        </div>
                    ';
		}
		$template_cards .= '
                    <div class="template-card" data-template="blank" data-tooltip="Canvas vierge pour créer votre propre design personnalisé">
                        <div class="pdfb-template-preview">
                            <span class="template-icon">✨</span>
                        </div>
                        <h4>' . __( 'Template Vierge', 'advanced-pdf-invoice-builder' ) . '</h4>
                        <p>' . __( 'Commencez depuis zéro', 'advanced-pdf-invoice-builder' ) . '</p>
                    </div>
                ';
		return '
                    <div class="first-template-setup">
                        <p>' . __( 'Choisissez un template de départ pour commencer votre premier PDF :', 'advanced-pdf-invoice-builder' ) . '</p>
                        <div class="template-suggestions">
                            ' . $template_cards . '
                        </div>
                        <div class="template-tip" style="margin-top:16px;padding:12px;background:#f0f9ff;border-left:4px solid #3b82f6;border-radius:4px;">
                            <strong>💡 Conseil :</strong> Vous pourrez personnaliser complètement ce template plus tard dans l\'éditeur.
                        </div>
                    </div>
                ';
	}

	/** HTML des actions disponibles pour l'étape assign_template */
	private function get_assign_template_actions_html(): string {
		return '<div class="template-actions"><h4>🎯 Actions disponibles</h4><div class="action-options">
                                <label class="action-option"><input type="checkbox" name="template_actions" value="auto_generate" checked><div class="option-content"><strong>Génération automatique</strong><span>Créer le PDF automatiquement lors des changements de statut</span></div></label>
                                <label class="action-option"><input type="checkbox" name="template_actions" value="email_attach" checked><div class="option-content"><strong>Pièce jointe email</strong><span>Joindre automatiquement le PDF aux emails WooCommerce</span></div></label>
                                <label class="action-option"><input type="checkbox" name="template_actions" value="download_link"><div class="option-content"><strong>Lien de téléchargement</strong><span>Ajouter un lien de téléchargement dans la commande client</span></div></label>
                            </div></div>';
	}

	/**
	 * Genere le contenu HTML de l'etape d'assignation du template.
	 */
	private function get_step_content_assign_template(): string {
		$order_statuses = function_exists( '\wc_get_order_statuses' ) ? \wc_get_order_statuses() : array();
		$status_options = '';
		foreach ( $order_statuses as $status_key => $status_label ) {
			$status_options .= '<label class="status-option"><input type="checkbox" name="assigned_statuses" value="' . esc_attr( $status_key ) . '"><span class="status-badge status-' . esc_attr( str_replace( 'wc-', '', $status_key ) ) . '">' . esc_html( $status_label ) . '</span></label>';
		}
		$header = '<div class="assign-template-setup"><div class="selected-template-preview"><div class="template-header"><div class="template-icon-large"><span id="selected-template-icon">📄</span></div><div class="template-info"><h3 id="selected-template-title">Template sélectionné</h3><p id="selected-template-description">Aucun template sélectionné</p></div></div></div>';
		$custom = '<div class="template-customization"><h4>✨ Personnalisez votre template</h4><div class="customization-fields"><div class="field-group"><label for="template_custom_name">Nom du template</label><input type="text" id="template_custom_name" placeholder="Ex: Facture Pro 2025" maxlength="100" aria-label="Nom du template"></div><div class="field-group"><label for="template_custom_description">Description (optionnel)</label><textarea id="template_custom_description" placeholder="Décrivez l\'usage de ce template..." maxlength="255" rows="2"></textarea></div></div></div>';
		$assign = '<div class="woocommerce-assignment"><h4>🛒 Assignation WooCommerce</h4><p class="assignment-description">Sélectionnez les statuts de commande pour lesquels ce template sera automatiquement généré :</p><div class="status-selection">' . $status_options . '</div><div class="assignment-notice"><div class="notice-icon">💡</div><div class="notice-content"><strong>Configuration automatique :</strong> Le template sera généré automatiquement pour les commandes atteignant ces statuts.</div></div></div>';
		$footer = $this->get_assign_template_actions_html() . '<div class="setup-complete-notice"><div class="notice-icon">✅</div><div class="notice-content"><strong>Configuration terminée !</strong> Votre template est prêt à être utilisé. Vous pourrez le modifier à tout moment depuis l\'éditeur.</div></div></div>';
		return $header . $custom . $assign . $footer;
	}

	/** HTML des 3 option-cards pour WooCommerce setup (cas WC actif) */
	private function get_woocommerce_integration_cards_html(): string {
		return '
                                <div class="integration-options">
                                    <div class="option-card" data-tooltip="Les clients recevront automatiquement leurs PDFs joints aux emails WooCommerce">
                                        <div class="option-header"><input type="checkbox" name="woocommerce_emails" checked id="woocommerce_emails"><label for="woocommerce_emails" class="option-toggle"></label></div>
                                        <div class="option-content"><div class="option-icon">📧</div><div class="option-text">
                                            <h6>' . __( 'PDFs dans les emails de commande', 'advanced-pdf-invoice-builder' ) . '</h6>
                                            <p>' . __( 'Vos clients recevront automatiquement leurs documents PDF (factures, bons de livraison...) directement dans leurs emails de confirmation de commande.', 'advanced-pdf-invoice-builder' ) . '</p>
                                        </div></div>
                                    </div>
                                    <div class="option-card" data-tooltip="Aperçu rapide des PDFs générés dans l\'interface d\'admin WooCommerce">
                                        <div class="option-header"><input type="checkbox" name="admin_preview" checked id="admin_preview"><label for="admin_preview" class="option-toggle"></label></div>
                                        <div class="option-content"><div class="option-icon">👁️</div><div class="option-text">
                                            <h6>' . __( 'Aperçu PDF dans l\'admin', 'advanced-pdf-invoice-builder' ) . '</h6>
                                            <p>' . __( 'Affichez un bouton d\'aperçu rapide dans l\'interface d\'administration pour visualiser les PDFs générés sans quitter la page de commande.', 'advanced-pdf-invoice-builder' ) . '</p>
                                        </div></div>
                                    </div>
                                    <div class="option-card" data-tooltip="Utiliser automatiquement les données WooCommerce dans vos templates PDF">
                                        <div class="option-header"><input type="checkbox" name="variables" checked id="variables"><label for="variables" class="option-toggle"></label></div>
                                        <div class="option-content"><div class="option-icon">🔧</div><div class="option-text">
                                            <h6>' . __( 'Variables WooCommerce', 'advanced-pdf-invoice-builder' ) . '</h6>
                                            <p>' . __( 'Activez l\'utilisation automatique des données WooCommerce (prix, produits, adresse client, numéro de commande...) dans vos templates PDF.', 'advanced-pdf-invoice-builder' ) . '</p>
                                        </div></div>
                                    </div>
                                </div>';
	}

	/**
	 * Genere le contenu HTML de l'etape de configuration WooCommerce.
	 */
	private function get_step_content_woocommerce_setup(): string {
		if ( \did_action( 'init' ) && defined( 'WC_VERSION' ) ) {
			return '
                        <div class="woocommerce-setup">
                            <div class="setup-notice success">
                                <span class="notice-icon">✅</span>
                                <div class="notice-content">
                                    <h4>' . __( 'WooCommerce détecté', 'advanced-pdf-invoice-builder' ) . '</h4>
                                    <p>' . __( 'Votre boutique WooCommerce est prête pour l\'intégration PDF.', 'advanced-pdf-invoice-builder' ) . '</p>
                                </div>
                            </div>
                            <div class="setup-section">
                                <h5>' . __( 'Options d\'intégration', 'advanced-pdf-invoice-builder' ) . '</h5>
                                <p class="pdfb-section-description">' . __( 'Configurez comment Advanced PDF Invoice Builder s\'intègre avec votre boutique WooCommerce.', 'advanced-pdf-invoice-builder' ) . '</p>' .
				$this->get_woocommerce_integration_cards_html() . '
                            </div>
                            <div class="setup-benefits">
                                <div class="benefit-item"><span class="benefit-icon">🚀</span><span class="benefit-text">' . __( 'Automatisation complète des documents', 'advanced-pdf-invoice-builder' ) . '</span></div>
                                <div class="benefit-item"><span class="benefit-icon">⚡</span><span class="benefit-text">' . __( 'Génération instantanée', 'advanced-pdf-invoice-builder' ) . '</span></div>
                                <div class="benefit-item"><span class="benefit-icon">🎯</span><span class="benefit-text">' . __( 'Expérience client améliorée', 'advanced-pdf-invoice-builder' ) . '</span></div>
                            </div>
                        </div>
                    ';
		}
		return '
                        <div class="woocommerce-setup">
                            <div class="setup-notice info">
                                <span class="notice-icon">ℹ️</span>
                                <div class="notice-content">
                                    <h4>' . __( 'WooCommerce non détecté', 'advanced-pdf-invoice-builder' ) . '</h4>
                                    <p>' . __( 'Installez WooCommerce pour bénéficier de l\'intégration complète.', 'advanced-pdf-invoice-builder' ) . '</p>
                                </div>
                            </div>
                            <div class="setup-actions">
                                <a href="' . admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) . '" class="button button-primary" target="_blank">' . __( 'Installer WooCommerce', 'advanced-pdf-invoice-builder' ) . '</a>
                                <button class="button button-secondary skip-woocommerce">' . __( 'Ignorer pour le moment', 'advanced-pdf-invoice-builder' ) . '</button>
                            </div>
                        </div>
                    ';
	}

	/** Retourne le summary-grid (3 cards) de la page completed.
	 *
	 * @param bool $has_woocommerce Si WooCommerce est actif.
	 * @param int  $template_count  Nombre de templates disponibles.
	 */
	private function get_completed_summary_html( bool $has_woocommerce, int $template_count ): string {
		$wc_class = $has_woocommerce ? 'active' : 'inactive';
		$wc_icon  = $has_woocommerce ? '🛒' : '❌';
		$wc_title = $has_woocommerce ? __( 'WooCommerce intégré', 'advanced-pdf-invoice-builder' ) : __( 'WooCommerce non détecté', 'advanced-pdf-invoice-builder' );
		$wc_desc  = $has_woocommerce ? __( 'Vos PDFs seront automatiquement joints aux emails', 'advanced-pdf-invoice-builder' ) : __( 'Installez WooCommerce pour une intégration automatique', 'advanced-pdf-invoice-builder' );
		// translators: %d: number of available templates.
		$tpl_count_label = sprintf(
			/* translators: %d: number of available templates */
			_n( '%d Template disponible', '%d Templates disponibles', $template_count, 'advanced-pdf-invoice-builder' ),
			$template_count
		);
		return '<div class="configuration-summary"><h4>' . __( '📋 Résumé de votre configuration', 'advanced-pdf-invoice-builder' ) . '</h4><div class="summary-grid">
                                <div class="summary-card"><div class="card-icon">📄</div><div class="card-content"><h5>' . esc_html( $tpl_count_label ) . '</h5><p>' . __( 'Templates professionnels prêts à utiliser', 'advanced-pdf-invoice-builder' ) . '</p></div></div>
                                <div class="summary-card ' . $wc_class . '"><div class="card-icon">' . $wc_icon . '</div><div class="card-content"><h5>' . $wc_title . '</h5><p>' . $wc_desc . '</p></div></div>
                                <div class="summary-card active"><div class="card-icon">⚡</div><div class="card-content"><h5>' . __( 'Éditeur React activé', 'advanced-pdf-invoice-builder' ) . '</h5><p>' . __( 'Interface moderne et intuitive pour créer vos PDFs', 'advanced-pdf-invoice-builder' ) . '</p></div></div>
                            </div></div>';
	}

	/** Retourne quick-actions + resources + feedback + footer de la page completed.
	 *
	 * @param string $user_display_name Nom affiche de l'utilisateur courant.
	 */
	private function get_completed_bottom_sections_html( string $user_display_name ): string {
		// translators: %s: user display name.
		$welcome_msg = sprintf( __( 'Bienvenue dans la communauté Advanced PDF Invoice Builder, %s !', 'advanced-pdf-invoice-builder' ), esc_html( $user_display_name ) );
		return '
                        <div class="quick-actions"><h4>' . __( '🚀 Commencez dès maintenant', 'advanced-pdf-invoice-builder' ) . '</h4><div class="actions-grid">
                                <a href="' . admin_url( 'admin.php?page=pdf-builder-templates' ) . '" class="action-card primary"><div class="action-icon">🎨</div><div class="action-content"><h5>' . __( 'Créer un nouveau PDF', 'advanced-pdf-invoice-builder' ) . '</h5><p>' . __( 'Utilisez l\'éditeur visuel pour concevoir votre document', 'advanced-pdf-invoice-builder' ) . '</p></div><div class="action-arrow">→</div></a>
                                <a href="' . admin_url( 'admin.php?page=pdf-builder-settings' ) . '" class="action-card secondary"><div class="action-icon">⚙️</div><div class="action-content"><h5>' . __( 'Configurer les paramètres', 'advanced-pdf-invoice-builder' ) . '</h5><p>' . __( 'Ajustez les options générales et les intégrations', 'advanced-pdf-invoice-builder' ) . '</p></div><div class="action-arrow">→</div></a>
                                <a href="' . admin_url( 'edit.php?post_type=pdf_template' ) . '" class="action-card secondary"><div class="action-icon">📁</div><div class="action-content"><h5>' . __( 'Gérer les templates', 'advanced-pdf-invoice-builder' ) . '</h5><p>' . __( 'Modifiez ou dupliquez vos templates existants', 'advanced-pdf-invoice-builder' ) . '</p></div><div class="action-arrow">→</div></a>
                            </div></div>
                        <div class="resources-section"><h4>' . __( '📚 Ressources et support', 'advanced-pdf-invoice-builder' ) . '</h4><div class="resources-grid">
                                <div class="resource-item"><div class="resource-icon">📖</div><div class="resource-content"><h6>' . __( 'Documentation complète', 'advanced-pdf-invoice-builder' ) . '</h6><p>' . __( 'Guides détaillés et tutoriels vidéo', 'advanced-pdf-invoice-builder' ) . '</p><a href="#" class="resource-link">' . __( 'Consulter la doc', 'advanced-pdf-invoice-builder' ) . ' →</a></div></div>
                                <div class="resource-item"><div class="resource-icon">💬</div><div class="resource-content"><h6>' . __( 'Support technique', 'advanced-pdf-invoice-builder' ) . '</h6><p>' . __( 'Notre équipe est là pour vous aider', 'advanced-pdf-invoice-builder' ) . '</p><a href="#" class="resource-link">' . __( 'Contacter le support', 'advanced-pdf-invoice-builder' ) . ' →</a></div></div>
                                <div class="resource-item"><div class="resource-icon">🎓</div><div class="resource-content"><h6>' . __( 'Webinaires gratuits', 'advanced-pdf-invoice-builder' ) . '</h6><p>' . __( 'Apprenez les meilleures pratiques', 'advanced-pdf-invoice-builder' ) . '</p><a href="#" class="resource-link">' . __( 'Voir le planning', 'advanced-pdf-invoice-builder' ) . ' →</a></div></div>
                            </div></div>
                        <div class="feedback-section"><div class="feedback-content"><div class="feedback-icon">👍</div><div class="feedback-text">
                                <h5>' . __( 'Votre avis compte !', 'advanced-pdf-invoice-builder' ) . '</h5>
                                <p>' . __( 'Aidez-nous à améliorer Advanced PDF Invoice Builder en partageant votre expérience.', 'advanced-pdf-invoice-builder' ) . '</p>
                                <div class="feedback-actions">
                                    <button class="feedback-btn positive" onclick="this.innerHTML=\'Merci pour votre retour ! ⭐\'">' . __( 'J\'adore !', 'advanced-pdf-invoice-builder' ) . '</button>
                                    <button class="feedback-btn suggestion" onclick="this.innerHTML=\'Suggestion notée ! 💡\'">' . __( 'Une suggestion ?', 'advanced-pdf-invoice-builder' ) . '</button>
                                </div></div></div></div>
                        <div class="welcome-footer">
                            <p class="welcome-message">' . $welcome_msg . '</p>
                            <p class="welcome-tip">💡 ' . __( 'Astuce : Utilisez Ctrl+S (Cmd+S sur Mac) pour sauvegarder automatiquement vos modifications.', 'advanced-pdf-invoice-builder' ) . '</p>
                        </div>';
	}

	/**
	 * Genere le contenu HTML de l'etape de finalisation.
	 */
	private function get_step_content_completed(): string {
		$has_woocommerce = function_exists( 'pdfib_is_woocommerce_active' ) && pdfib_is_woocommerce_active();
		$template_count  = count( glob( plugin_dir_path( __DIR__ ) . '../../templates/predefined/*.json' ) );
		$current_user    = wp_get_current_user();
		return '
                    <div class="onboarding-completed">
                        <div class="celebration-header">
                            <div class="celebration-icon">🎉</div>
                            <h3>' . __( 'Félicitations !', 'advanced-pdf-invoice-builder' ) . '</h3>
                            <p class="celebration-subtitle">' . __( 'Votre Advanced PDF Invoice Builder est maintenant configuré et prêt à l\'emploi', 'advanced-pdf-invoice-builder' ) . '</p>
                        </div>' .
			$this->get_completed_summary_html( $has_woocommerce, $template_count ) .
			$this->get_completed_bottom_sections_html( $current_user->display_name ) . '
                    </div>
                ';
	}
	/**
	 * Rendre le wizard d'onboarding.
	 */
	public function render_onboarding_wizard() {
		$steps       = $this->get_onboarding_steps();
		$forced_step = isset( $GLOBALS['_GET']['pdf_onboarding_step'] ) ? \intval( wp_unslash( $GLOBALS['_GET']['pdf_onboarding_step'] ) ) : null;
		if ( $forced_step && $forced_step >= 1 && $forced_step <= count( $steps ) ) {
			$this->onboarding_options['current_step'] = $forced_step;
			$this->save_onboarding_options();
			$current_step = $forced_step;
		} else {
			$found_step   = $this->get_current_step();
			$current_step = $found_step ? $found_step : 1;
		}
		$current_step_data = $steps[ $current_step ] ?? $steps[1];
		$steps_count       = count( $steps );
		?>
		<div id="pdf-builder-onboarding-modal" class="pdf-builder-onboarding-modal">
			<div class="modal-content">
				<?php $this->render_onboarding_modal_header( $current_step, $steps_count ); ?>
				<div class="modal-body">
					<div class="step-content">
						<?php echo wp_kses_post( $this->render_step_content( $current_step_data ) ); ?>
					</div>
				</div>
				<div class="modal-footer">
					<?php if ( $current_step_data['can_skip'] ) : ?>
						<button class="button button-secondary" data-action="skip-step">
							<?php echo esc_html( $current_step_data['skip_text'] ?? __( 'Ignorer', 'advanced-pdf-invoice-builder' ) ); ?>
						</button>
					<?php else : ?>
						<button class="button button-secondary" data-action="skip-onboarding">
							<?php esc_html_e( 'Ignorer l\'assistant', 'advanced-pdf-invoice-builder' ); ?>
						</button>
					<?php endif; ?>
					<?php if ( $current_step_data['action'] ) : ?>
						<button class="button button-primary complete-step"
							data-step="<?php echo intval( $current_step ); ?>"
							data-action-type="<?php echo esc_attr( $current_step_data['action_type'] ); ?>"
							<?php echo esc_attr( ( $current_step_data['requires_selection'] ?? false ) ? 'disabled' : '' ); ?>>
							<?php echo esc_html( $current_step_data['action'] ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/** HTML: en-tete modal onboarding (bouton precedent, barre de progression, bouton fermer).
	 *
	 * @param int $current_step Etape courante.
	 * @param int $steps_count  Nombre total d'etapes.
	 */
	private function render_onboarding_modal_header( int $current_step, int $steps_count ): void {
		?>
		<div class="modal-header">
			<?php if ( $current_step > 1 ) : ?>
				<button class="button button-previous"><span class="dashicons dashicons-arrow-left-alt"></span></button>
			<?php endif; ?>
			<div class="progress-indicator">
				<div class="progress-bar">
					<div class="progress-fill" style="width: <?php echo intval( ( $current_step / $steps_count ) * 100 ); ?>%"></div>
				</div>
				<div class="progress-text">
				<?php
				/* translators: 1: Current step number, 2: Total number of steps */
				printf( esc_html__( 'Étape %1$d sur %2$d', 'advanced-pdf-invoice-builder' ), intval( $current_step ), intval( $steps_count ) );
				?>
				</div>
				<div class="progress-steps">
					<?php for ( $i = 1; $i <= $steps_count; $i++ ) : ?>
						<?php
						$pdfib_step_class = '';
						if ( $i < $current_step ) {
							$pdfib_step_class = 'completed';
						} elseif ( $i === $current_step ) {
							$pdfib_step_class = 'active';
						}
						?>
						<div class="progress-step <?php echo esc_attr( $pdfib_step_class ); ?>" data-step="<?php echo intval( $i ); ?>"></div>
					<?php endfor; ?>
				</div>
			</div>
			<button class="modal-close" data-action="skip-onboarding" data-tooltip="Quitter l'assistant">
				<span class="dashicons dashicons-no"></span>
			</button>
		</div>
		<?php
	}
	/**
	 * Charger les scripts d'onboarding.
	 *
	 * @param string $hook Nom du hook admin courant.
	 */
	public function enqueue_onboarding_scripts( string $hook ) {
		// Charger seulement sur les pages pertinentes.
		if ( ! in_array(
			$hook,
			array(
				'toplevel_page_pdf-builder-pro',
				'pdf-builder_page_pdf-builder-templates',
				'pdf-builder_page_pdf-builder-settings',
			),
			true
		) ) {
			return;
		}
		// Charger le CSS d'onboarding.
		wp_enqueue_style(
			'pdf-builder-onboarding',
			PDFIB_PRO_ASSETS_URL . 'css/onboarding-css.min.css',
			array(),
			PDFIB_PRO_VERSION
		);
		wp_enqueue_script(
			'pdf-builder-onboarding',
			PDFIB_PRO_ASSETS_URL . 'js/onboarding.js',
			array( 'jquery' ),
			PDFIB_PRO_VERSION,
			true
		);
		wp_localize_script(
			'pdf-builder-onboarding',
			'pdfBuilderOnboarding',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'pdfib_onboarding' ),
				'strings'  => array(
					'confirm_skip'         => __( 'Êtes-vous sûr de vouloir ignorer l\'assistant de configuration ?', 'advanced-pdf-invoice-builder' ),
					'step_completed'       => __( 'Étape terminée !', 'advanced-pdf-invoice-builder' ),
					'onboarding_completed' => __( 'Configuration terminée !', 'advanced-pdf-invoice-builder' ),
				),
			)
		);
	}
	/**
	 * AJAX - Compléter une étape d'onboarding
	 */
	public function ajax_complete_onboarding_step() {
		$this->assert_onboarding_ajax_access();
		$step = \intval( wp_unslash( $GLOBALS['_POST']['step'] ?? 0 ) );

		$validation_error = $this->validate_step_completion( $step );
		if ( $validation_error ) {
			wp_send_json_error( array( 'message' => $validation_error ) );
			return;
		}

		$this->onboarding_options['steps_completed'][] = $step;
		$all_steps                                     = $this->get_onboarding_steps();
		$max_step                                      = max( array_keys( $all_steps ) );
		if ( $step < $max_step ) {
			$this->onboarding_options['current_step'] = $step + 1;
		}
		$this->onboarding_options['last_activity'] = time();
		unset( $this->onboarding_options['redirect_to'] );

		$current_step_data = $all_steps[ $step ] ?? null;
		if ( $current_step_data ) {
			$this->apply_step_side_effects( $current_step_data );
		}

		$this->save_onboarding_options();
		wp_send_json_success(
			array(
				'next_step'   => $this->onboarding_options['current_step'],
				'completed'   => $this->onboarding_options['completed'],
				'redirect_to' => $this->onboarding_options['redirect_to'] ?? null,
			)
		);
	}

	/**
	 * Applique les effets de bord specifiques a une etape d'onboarding.
	 *
	 * @param array $step_data Donnees de l'etape courante.
	 */
	private function apply_step_side_effects( array $step_data ): void {
		switch ( $step_data['id'] ) {
			case 'first_template':
				if ( ! empty( $GLOBALS['_POST']['selected_template'] ) ) {
					$this->onboarding_options['selected_template'] = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['selected_template'] ) );
				}
				break;
			case 'assign_template':
				if ( isset( $GLOBALS['_POST']['template_usage'] ) ) {
					$this->onboarding_options['template_usage'] = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_usage'] ) );
				}
				break;
			case 'woocommerce_setup':
				if ( isset( $GLOBALS['_POST']['woocommerce_options'] ) ) {
					$woocommerce_options = $this->sanitize_woo_commerce_options( wp_unslash( $GLOBALS['_POST']['woocommerce_options'] ) );
					pdfib_update_option( 'pdfib_woocommerce_integration', $woocommerce_options );
				}
				break;
			case 'completed':
				$this->onboarding_options['completed']    = true;
				$this->onboarding_options['completed_at'] = time();
				$this->onboarding_options['redirect_to']  = admin_url( 'admin.php?page=pdf-builder-react-editor&editor_action=new' );
				break;
			default:
				break;
		}
	}
	/**
	 * AJAX - Sauvegarder la sélection de template
	 */
	public function ajax_save_template_selection() {
		$this->assert_onboarding_ajax_access();
		$selected_template = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['selected_template'] ?? '' ) );
		$this->update_onboarding_field_and_respond( 'selected_template', $selected_template );
	}
	/**
	 * AJAX - Mettre à jour l'étape actuelle
	 */
	public function ajax_update_onboarding_step() {
		$this->assert_onboarding_ajax_access();
		$step = \intval( wp_unslash( $GLOBALS['_POST']['step'] ?? 0 ) );
		$this->update_onboarding_field_and_respond( 'current_step', $step );
	}
	/**
	 * AJAX - Marquer l'onboarding comme terminé
	 */
	public function ajax_mark_onboarding_complete() {
		$this->assert_onboarding_ajax_access();
		$this->onboarding_options['completed']    = true;
		$this->onboarding_options['completed_at'] = time();
		$this->save_onboarding_options();
		wp_send_json_success();
	}
	/**
	 * Valider la completion d'une etape.
	 *
	 * @param int $step Numero de l'etape a valider.
	 */
	private function validate_step_completion( int $step ): ?string {
		$all_steps = $this->get_onboarding_steps();
		$unknown   = __( 'Étape inconnue.', 'advanced-pdf-invoice-builder' );
		// Vérifier si l'étape existe.
		if ( ! isset( $all_steps[ $step ] ) ) {
			return $unknown;
		}

		$step_id = $all_steps[ $step ]['id'] ?? '';

		return match ( $step_id ) {
			'first_template' => $this->validate_required_step_field( 'selected_template', __( 'Veuillez sélectionner un template.', 'advanced-pdf-invoice-builder' ) ),
			'welcome', 'assign_template', 'woocommerce_setup', 'completed' => null,
			default => $unknown,
		};
	}

	/**
	 * Verifie la presence d'un champ POST requis pour une etape.
	 *
	 * @param string $field_name    Nom du champ POST requis.
	 * @param string $error_message Message d'erreur si le champ est absent.
	 */
	private function validate_required_step_field( string $field_name, string $error_message ): ?string {
		if ( empty( $GLOBALS['_POST'][ $field_name ] ) ) {
			return $error_message;
		}

		return null;
	}
	/**
	 * AJAX - Ignorer l'onboarding
	 */
	public function ajax_skip_onboarding() {
		$this->assert_onboarding_ajax_access();
		$this->onboarding_options['skipped']    = true;
		$this->onboarding_options['skipped_at'] = time();
		$this->save_onboarding_options();
		wp_send_json_success();
	}
	/**
	 * AJAX - Réinitialiser l'onboarding
	 */
	public function ajax_reset_onboarding() {
		$this->assert_onboarding_ajax_access();
		$this->onboarding_options = $this->build_default_onboarding_options( true );
		$this->save_onboarding_options();
		wp_send_json_success();
	}
	/**
	 * Generer le contenu HTML d'une etape.
	 *
	 * @param array $step_data Donnees de l'etape a afficher.
	 */
	private function render_step_content( array $step_data ) {
		ob_start();
		?>
		<div class="onboarding-step-content" data-step-id="<?php echo esc_attr( $step_data['id'] ); ?>">
			<div class="step-header">
				<h2><?php echo esc_html( $step_data['title'] ); ?></h2>
				<p class="step-description"><?php echo esc_html( $step_data['description'] ); ?></p>
			</div>
			<div class="step-body">
				<?php echo wp_kses_post( $step_data['content'] ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
	/**
	 * Reset l'onboarding (méthode publique pour usage externe)
	 */
	public function reset_onboarding(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$this->onboarding_options = $this->build_default_onboarding_options( true );
		$this->save_onboarding_options();
		return true;
	}
	/**
	 * Obtenir les statistiques d'onboarding
	 */
	public function get_onboarding_stats() {
		return array(
			'completed'       => $this->is_onboarding_completed(),
			'skipped'         => $this->is_onboarding_skipped(),
			'current_step'    => $this->get_current_step(),
			'steps_completed' => count( $this->onboarding_options['steps_completed'] ),
			'total_steps'     => count( $this->get_onboarding_steps() ),
			'first_login'     => $this->onboarding_options['first_login'],
			'last_activity'   => $this->onboarding_options['last_activity'],
		);
	}
	/**
	 * AJAX handler pour sauvegarder l'assignation de template
	 */
	public function ajax_save_template_assignment() {
		check_ajax_referer( 'pdfib_onboarding', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}
		$assignment_data = json_decode( sanitize_textarea_field( wp_unslash( $GLOBALS['_POST']['assignment_data'] ?? '' ) ), true );
		if ( ! $assignment_data || ! isset( $assignment_data['template_id'] ) ) {
			wp_send_json_error( array( 'message' => esc_html( __( 'Données d\'assignation invalides', 'advanced-pdf-invoice-builder' ) ) ) );
			return;
		}
		// Sauvegarder les données d'assignation.
		$this->onboarding_options['template_assignment'] = array(
			'template_id'        => sanitize_text_field( $assignment_data['template_id'] ),
			'custom_name'        => sanitize_text_field( $assignment_data['custom_name'] ?? '' ),
			'custom_description' => sanitize_textarea_field( $assignment_data['custom_description'] ?? '' ),
			'assigned_statuses'  => array_map( 'sanitize_text_field', $assignment_data['assigned_statuses'] ?? array() ),
			'template_actions'   => array_map( 'sanitize_text_field', $assignment_data['template_actions'] ?? array() ),
			'assigned_at'        => \current_time( 'mysql' ),
		);
		// Marquer l'etape 4 comme completee.
		if ( ! in_array( 4, $this->onboarding_options['steps_completed'], true ) ) {
			$this->onboarding_options['steps_completed'][] = 4;
		}
		// Sauvegarder les options.
		pdfib_update_option( 'pdfib_onboarding', $this->onboarding_options );
		// Créer une configuration WooCommerce si nécessaire.
		if ( ! empty( $assignment_data['assigned_statuses'] ) ) {
			$this->create_woocommerce_template_config( $assignment_data );
		}
		wp_send_json_success(
			array(
				'message'    => __( 'Configuration de template sauvegardée avec succès', 'advanced-pdf-invoice-builder' ),
				'assignment' => $this->onboarding_options['template_assignment'],
			)
		);
	}
	/**
	 * Creer la configuration WooCommerce pour le template.
	 *
	 * @param array $assignment_data Donnees d'assignation du template.
	 */
	private function create_woocommerce_template_config( array $assignment_data ) {
		if ( ! function_exists( 'pdfib_is_woocommerce_active' ) || ! pdfib_is_woocommerce_active() ) {
			return;
		}
		// Récupérer ou créer les options WooCommerce.
		$wc_options = pdfib_get_option( 'pdfib_woocommerce', array() );
		// Configuration pour les statuts assignés.
		foreach ( $assignment_data['assigned_statuses'] as $status ) {
			$clean_status = str_replace( 'wc-', '', $status );
			if ( ! isset( $wc_options[ $clean_status ] ) ) {
				$wc_options[ $clean_status ] = array(
					'enabled'               => true,
					'template_id'           => $assignment_data['template_id'],
					'custom_name'           => $assignment_data['custom_name'] ? $assignment_data['custom_name'] : $assignment_data['template_id'],
					'auto_generate'         => in_array( 'auto_generate', $assignment_data['template_actions'], true ),
					'email_attach'          => in_array( 'email_attach', $assignment_data['template_actions'], true ),
					'download_link'         => in_array( 'download_link', $assignment_data['template_actions'], true ),
					'created_by_onboarding' => true,
					'created_at'            => \current_time( 'mysql' ),
				);
			}
		}
		pdfib_update_option( 'pdfib_woocommerce', $wc_options );
	}
	/**
	 * Obtenir la liste des templates prédéfinis disponibles
	 */
	private function get_predefined_templates() {
		$templates    = array();
		$template_dir = apply_filters( 'pdfib_predefined_templates_dir', plugin_dir_path( PDFIB_PLUGIN_FILE ) . 'templates/predefined/' );
		// Scanner les fichiers .json dans le dossier predefined.
		$template_files = glob( $template_dir . '*.json' );
		foreach ( $template_files as $file_path ) {
			$filename = basename( $file_path, '.json' );
			// Essayer de lire le fichier JSON pour extraire les métadonnées.
			$template_data = json_decode( pdfib_filesystem()->get_contents( $file_path ), true );
			if ( $template_data && isset( $template_data['metadata'] ) ) {
				$metadata    = $template_data['metadata'];
				$templates[] = array(
					'id'                => $filename,
					'name'              => $metadata['name'] ?? $this->format_template_name( $filename ),
					'description'       => $metadata['description'] ?? __( 'Template professionnel prêt à l\'emploi', 'advanced-pdf-invoice-builder' ),
					'short_description' => $metadata['short_description'] ?? __( 'Template prédéfini', 'advanced-pdf-invoice-builder' ),
					'icon'              => $metadata['icon'] ?? '📄',
					'category'          => $metadata['category'] ?? 'general',
				);
			} else {
				// Fallback si pas de métadonnées.
				$templates[] = array(
					'id'                => $filename,
					'name'              => $this->format_template_name( $filename ),
					'description'       => __( 'Template professionnel prêt à l\'emploi', 'advanced-pdf-invoice-builder' ),
					'short_description' => __( 'Template prédéfini', 'advanced-pdf-invoice-builder' ),
					'icon'              => '📄',
					'category'          => 'general',
				);
			}
		}
		return $templates;
	}
	/**
	 * Formate le nom d'un template depuis son filename.
	 *
	 * @param string $filename Nom du fichier sans extension.
	 */
	private function format_template_name( string $filename ) {
		// Convertir les tirets et underscores en espaces, puis capitaliser.
		$name = str_replace( array( '-', '_' ), ' ', $filename );
		$name = ucwords( $name );
		return $name;
	}

	/**
	 * Sanitize le tableau d'options WooCommerce.
	 *
	 * @param mixed $options Donnees brutes a sanitizer.
	 */
	private function sanitize_woo_commerce_options( mixed $options ) {
		if ( ! is_array( $options ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $options as $key => $value ) {
			$sanitized_key = sanitize_key( $key );
			if ( is_array( $value ) ) {
				$sanitized[ $sanitized_key ] = $this->sanitize_woo_commerce_options( $value );
			} elseif ( is_string( $value ) ) {
				$sanitized[ $sanitized_key ] = sanitize_text_field( $value );
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $sanitized_key ] = $value;
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $sanitized_key ] = $value;
			}
		}
		return $sanitized;
	}
	/**
	 * AJAX - Charger le contenu d'une etape d'onboarding.
	 */
	public function ajax_load_onboarding_step() {
		$this->assert_onboarding_ajax_access();
		$step  = \intval( wp_unslash( $GLOBALS['_POST']['step'] ?? 0 ) );
		$steps = $this->get_onboarding_steps();
		if ( ! isset( $steps[ $step ] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Étape non trouvée', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}
		$step_data = $steps[ $step ];
		// Générer le contenu HTML de l'étape.
		$html = $this->render_step_content( $step_data );
		wp_send_json_success(
			array(
				'step'               => $step,
				'title'              => $step_data['title'],
				'description'        => $step_data['description'],
				'content'            => $html,
				'action'             => $step_data['action'],
				'action_type'        => $step_data['action_type'] ?? 'next',
				'can_skip'           => $step_data['can_skip'] ?? false,
				'skip_text'          => $step_data['skip_text'] ?? __( 'Ignorer', 'advanced-pdf-invoice-builder' ),
				'requires_selection' => $step_data['requires_selection'] ?? false,
				'auto_advance'       => $step_data['auto_advance'] ?? false,
				'auto_advance_delay' => $step_data['auto_advance_delay'] ?? 3000,
			)
		);
	}

	/**
	 * Initialiser les hooks.
	 */
	private function init_hooks() {
		// Verifier que WordPress est charge avant d'ajouter les hooks.
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}
		// Utiliser admin_enqueue_scripts au lieu de admin_init pour une meilleure synchronisation.
		add_action( 'admin_enqueue_scripts', array( $this, 'check_onboarding_status' ) );
		add_action( 'wp_ajax_pdfib_complete_onboarding_step', array( $this, 'ajax_complete_onboarding_step' ) );
		add_action( 'wp_ajax_pdfib_skip_onboarding', array( $this, 'ajax_skip_onboarding' ) );
		add_action( 'wp_ajax_pdfib_reset_onboarding', array( $this, 'ajax_reset_onboarding' ) );
		add_action( 'wp_ajax_pdfib_load_onboarding_step', array( $this, 'ajax_load_onboarding_step' ) );
		add_action( 'wp_ajax_pdfib_save_template_selection', array( $this, 'ajax_save_template_selection' ) );
		add_action( 'wp_ajax_pdfib_update_onboarding_step', array( $this, 'ajax_update_onboarding_step' ) );
		add_action( 'wp_ajax_pdfib_save_template_assignment', array( $this, 'ajax_save_template_assignment' ) );
		add_action( 'wp_ajax_pdfib_mark_onboarding_complete', array( $this, 'ajax_mark_onboarding_complete' ) );
	}

	/**
	 * Retourne l'etape d'onboarding courante.
	 *
	 * @inheritDoc
	 */
	public function get_current_step(): int {
		$current_step = $this->onboarding_options['current_step'] ?? 1;
		$all_steps    = $this->get_onboarding_steps();
		// S'assurer que l'etape actuelle existe dans les etapes disponibles.
		if ( ! isset( $all_steps[ $current_step ] ) ) {
			// Si l'etape n'existe pas, prendre la premiere etape disponible.
			$current_step = min( array_keys( $all_steps ) );
			// Mettre a jour les options pour eviter ce probleme a l'avenir.
			$this->onboarding_options['current_step'] = $current_step;
			$this->save_onboarding_options();
		}
		return $current_step;
	}

	/**
	 * Effectue les verifications d'environnement WordPress/PHP.
	 */
	private function perform_environment_checks() {
		$checks = array();
		// Vérification PHP.
		$checks[] = array(
			'title'       => __( 'Version PHP', 'advanced-pdf-invoice-builder' ),
			// translators: %s: current PHP version number.
			'description' => sprintf( __( 'Version actuelle : %s (Minimum requis : 8.2)', 'advanced-pdf-invoice-builder' ), PHP_VERSION ),
			'status'      => version_compare( PHP_VERSION, '8.2', '>=' ),
		);
		// Vérification WordPress.
		global $wp_version;
		// translators: %s: current WordPress version number.
		$wp_ver_fmt = __( 'Version actuelle : %s (Minimum requis : 6.0)', 'advanced-pdf-invoice-builder' );
		$checks[]   = array(
			'title'       => __( 'Version WordPress', 'advanced-pdf-invoice-builder' ),
			'description' => sprintf( $wp_ver_fmt, $wp_version ),
			'status'      => version_compare( $wp_version, '6.0', '>=' ),
		);
		// Vérification WooCommerce.
		$checks[] = array(
			'title'       => __( 'WooCommerce', 'advanced-pdf-invoice-builder' ),
			'description' => function_exists( 'pdfib_is_woocommerce_active' ) && pdfib_is_woocommerce_active() ?
				__( 'WooCommerce détecté et compatible', 'advanced-pdf-invoice-builder' ) :
				__( 'WooCommerce non détecté - Installation recommandée', 'advanced-pdf-invoice-builder' ),
			'status'      => function_exists( 'pdfib_is_woocommerce_active' ) && pdfib_is_woocommerce_active(),
		);
		// Vérification mémoire.
		$memory_limit = ini_get( 'memory_limit' );
		$memory_bytes = wp_convert_hr_to_bytes( $memory_limit );
		// translators: %s: PHP memory limit value (e.g. 128M).
		$mem_limit_fmt = __( 'Limite actuelle : %s (Recommandé : 128M)', 'advanced-pdf-invoice-builder' );
		$checks[]      = array(
			'title'       => __( 'Mémoire PHP', 'advanced-pdf-invoice-builder' ),
			'description' => sprintf( $mem_limit_fmt, $memory_limit ),
			'status'      => $memory_bytes >= 134217728, // 128M
		);
		// Vérification permissions écriture.
		$upload_dir = wp_upload_dir();
		$writable   = pdfib_filesystem()->is_writable( $upload_dir['basedir'] );
		$checks[]   = array(
			'title'       => __( 'Permissions d\'écriture', 'advanced-pdf-invoice-builder' ),
			'description' => $writable ?
				__( 'Le dossier uploads est accessible en écriture', 'advanced-pdf-invoice-builder' ) :
				__( 'Problème de permissions sur le dossier uploads', 'advanced-pdf-invoice-builder' ),
			'status'      => $writable,
		);
		return $checks;
	}
}



