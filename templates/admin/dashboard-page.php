<?php
/**
 * Dashboard Page - Advanced PDF Invoice Builder
 *
 * Thin shell: injects React mount point + localized data.
 *
 * PHP version 8.2
 *
 * @category Plugin
 * @package  PDFIB
 * @author   Natsenack <threeaxe.france@gmail.com>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/natsenack/wp-pdf-builder-pro
 *
 * @var array  $stats          Dashboard statistics.
 * @var string $plugin_version Plugin version.
 * @var bool   $is_premium     Premium status.
 */

// Empêcher l'accès direct.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

$pdfib_purchase_url = 'https://hub.threeaxe.fr/nos-produits/pdf-builder-pro-2';

$pdfib_compare_rows = array(
	array(
		'label' => __( 'Éditeur visuel drag & drop', 'advanced-pdf-invoice-builder' ),
		'free'  => '✓',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Temps de génération', 'advanced-pdf-invoice-builder' ),
		'free'  => __( 'Standard', 'advanced-pdf-invoice-builder' ),
		'pro'   => __( 'Prioritaire', 'advanced-pdf-invoice-builder' ),
	),
	array(
		'label' => __( 'Templates prédéfinis inclus', 'advanced-pdf-invoice-builder' ),
		'free'  => __( 'aucun modèle', 'advanced-pdf-invoice-builder' ),
		'pro'   => __( '5 modèles', 'advanced-pdf-invoice-builder' ),
	),
	array(
		'label' => __( "Qualité d'export max", 'advanced-pdf-invoice-builder' ),
		'free'  => '150 DPI',
		'pro'   => '300 & 600 DPI',
	),
	array(
		'label' => __( 'Formats d\'export', 'advanced-pdf-invoice-builder' ),
		'free'  => 'PDF',
		'pro'   => 'PDF, PNG, JPG',
	),
	array(
		'label' => __( 'Formats de page', 'advanced-pdf-invoice-builder' ),
		'free'  => __( 'A4 uniquement', 'advanced-pdf-invoice-builder' ),
		'pro'   => __( 'A3, Letter, Legal…', 'advanced-pdf-invoice-builder' ),
	),
	array(
		'label' => __( 'Variables WooCommerce', 'advanced-pdf-invoice-builder' ),
		'free'  => __( 'Basiques', 'advanced-pdf-invoice-builder' ),
		'pro'   => __( 'Avancées + perso.', 'advanced-pdf-invoice-builder' ),
	),
	array(
		'label' => __( 'Éléments premium (codes-barres, QR…)', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Grille, guides & aimantation', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Sélection & groupement multiple', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Raccourcis clavier', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Couleurs personnalisées du canvas', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Génération en masse', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Export PNG/JPG depuis métabox WC', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Compression PDF avancée', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'API REST développeur', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'White-label (sans branding)', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Analytics détaillés', 'advanced-pdf-invoice-builder' ),
		'free'  => '—',
		'pro'   => '✓',
	),
	array(
		'label' => __( 'Support', 'advanced-pdf-invoice-builder' ),
		'free'  => __( 'Communauté', 'advanced-pdf-invoice-builder' ),
		'pro'   => __( 'Support dédié', 'advanced-pdf-invoice-builder' ),
	),
);

$pdfib_compare_value_class = static function ( string $value, bool $is_pro ): string {
	$pdfib_classes = array( 'pdfb-promo-compare__value' );

	if ( '✓' === $value ) {
		$pdfib_classes[] = 'pdfb-promo-compare__value--check';
	} elseif ( '—' === $value ) {
		$pdfib_classes[] = 'pdfb-promo-compare__value--dash';
	} else {
		$pdfib_classes[] = 'pdfb-promo-compare__value--text';
	}

	$pdfib_classes[] = $is_pro ? 'pdfb-promo-compare__value--pro' : 'pdfb-promo-compare__value--free';

	return implode( ' ', $pdfib_classes );
};

$pdfib_dashboard_data = array(
	'stats'         => array(
		'templates' => (int) ( $stats['templates'] ?? 0 ),
		'documents' => (int) ( $stats['documents'] ?? 0 ),
		'today'     => (int) ( $stats['today'] ?? 0 ),
	),
	'pluginVersion' => (string) $plugin_version,
	'isPremium'     => (bool) $is_premium,
	'date'          => gmdate( 'd/m/Y' ),
	'urls'          => array(
		'reactEditor' => esc_url( admin_url( 'admin.php?page=pdf-builder-react-editor' ) ),
		'templates'   => esc_url( admin_url( 'admin.php?page=pdf-builder-templates' ) ),
		'settings'    => esc_url( admin_url( 'admin.php?page=pdf-builder-settings' ) ),
		'dashboard'   => esc_url( admin_url( 'admin.php?page=pdf-builder-pro' ) ),
		'purchase'    => $pdfib_purchase_url,
	),
	'i18n'          => array(
		'subtitle'            => __( 'Constructeur de PDF professionnel avec éditeur visuel avancé', 'advanced-pdf-invoice-builder' ),
		// translators: %s: plugin version number.
		'version'             => __( 'Version %s', 'advanced-pdf-invoice-builder' ),
		// translators: %s: last update date.
		'lastUpdate'          => __( 'Dernière mise à jour: %s', 'advanced-pdf-invoice-builder' ),
		'statTemplates'       => __( 'Templates', 'advanced-pdf-invoice-builder' ),
		'statDocuments'       => __( 'Documents générés', 'advanced-pdf-invoice-builder' ),
		'statToday'           => __( "Aujourd'hui", 'advanced-pdf-invoice-builder' ),
		'createPdf'           => __( 'Créer un nouveau PDF', 'advanced-pdf-invoice-builder' ),
		'createPdfDesc'       => __( 'Utilisez notre éditeur React moderne pour concevoir vos documents', 'advanced-pdf-invoice-builder' ),
		'manageTemplates'     => __( 'Gérer les Templates', 'advanced-pdf-invoice-builder' ),
		'manageTemplatesDesc' => __( 'Créez, modifiez et organisez vos modèles de documents', 'advanced-pdf-invoice-builder' ),
		'viewTemplates'       => __( 'Voir les Templates', 'advanced-pdf-invoice-builder' ),
		'settingsConfig'      => __( 'Paramètres & Configuration', 'advanced-pdf-invoice-builder' ),
		'settingsDesc'        => __( "Configurez les paramètres avancés, polices, qualité d'impression et options WooCommerce", 'advanced-pdf-invoice-builder' ),
		'openSettings'        => __( 'Ouvrir les Paramètres', 'advanced-pdf-invoice-builder' ),
		'openEditor'          => __( "Ouvrir l'Éditeur React", 'advanced-pdf-invoice-builder' ),
		'premiumFeature'      => __( 'Voir les options PRO', 'advanced-pdf-invoice-builder' ),
		'premiumMsg'          => __( 'Cette option est disponible dans la version PRO séparée.', 'advanced-pdf-invoice-builder' ),
		'guideTitle'          => __( 'Guide de démarrage rapide', 'advanced-pdf-invoice-builder' ),
		'steps'               => array(
			array(
				'title' => __( 'Configuration initiale', 'advanced-pdf-invoice-builder' ),
				'desc'  => __( 'Vérifiez la version Pro/Gratuite et les statistiques de votre installation', 'advanced-pdf-invoice-builder' ),
				'hint'  => __( "La page d'accueil affiche automatiquement votre version et les métriques en temps réel", 'advanced-pdf-invoice-builder' ),
				'url'   => esc_url( admin_url( 'admin.php?page=pdf-builder-pro' ) ),
			),
			array(
				'title' => __( 'Créez votre premier template', 'advanced-pdf-invoice-builder' ),
				'desc'  => __( 'Allez dans "Templates PDF" → "Créer un nouveau template"', 'advanced-pdf-invoice-builder' ),
				'hint'  => __( "Utilisez l'éditeur React avec Canvas avancé, grille d'aimantation et guides", 'advanced-pdf-invoice-builder' ),
				'url'   => esc_url( admin_url( 'admin.php?page=pdf-builder-templates' ) ),
			),
			array(
				'title' => __( 'Concevez votre PDF', 'advanced-pdf-invoice-builder' ),
				'desc'  => __( 'Ajoutez des éléments : texte, images, formes, code-barres, variables WooCommerce', 'advanced-pdf-invoice-builder' ),
				'hint'  => __( 'Les propriétés sont organisées en accordéons pour une meilleure ergonomie', 'advanced-pdf-invoice-builder' ),
				'url'   => esc_url( admin_url( 'admin.php?page=pdf-builder-react-editor' ) ),
			),
			array(
				'title' => __( 'Intégrez WooCommerce', 'advanced-pdf-invoice-builder' ),
				'desc'  => __( 'Utilisez les variables dynamiques : {{order_number}}, {{customer_name}}, etc.', 'advanced-pdf-invoice-builder' ),
				'hint'  => __( 'Aperçu direct dans les metabox des commandes WooCommerce', 'advanced-pdf-invoice-builder' ),
				'url'   => esc_url( admin_url( 'admin.php?page=pdf-builder-settings' ) ),
			),
			array(
				'title' => __( 'Configurez les paramètres avancés', 'advanced-pdf-invoice-builder' ),
				'desc'  => __( "Ajustez les marges, la qualité d'impression, la compression PDF", 'advanced-pdf-invoice-builder' ),
				'hint'  => __( 'Paramètres Canvas complets : dimensions, orientation, grille, zoom', 'advanced-pdf-invoice-builder' ),
				'url'   => esc_url( admin_url( 'admin.php?page=pdf-builder-settings' ) ),
			),
			array(
				'title' => __( 'Générez et testez', 'advanced-pdf-invoice-builder' ),
				'desc'  => __( 'Prévisualisez votre PDF et ajustez si nécessaire', 'advanced-pdf-invoice-builder' ),
				'hint'  => __( "Utilisez l'API Preview intégrée pour des aperçus haute qualité", 'advanced-pdf-invoice-builder' ),
				'url'   => esc_url( admin_url( 'admin.php?page=pdf-builder-react-editor' ) ),
			),
			array(
				'title' => __( 'Automatisez (optionnel)', 'advanced-pdf-invoice-builder' ),
				'desc'  => __( 'Configurez des workflows automatisés pour la génération en masse', 'advanced-pdf-invoice-builder' ),
				'hint'  => __( 'Idéal pour factures, devis, reçus WooCommerce', 'advanced-pdf-invoice-builder' ),
				'url'   => esc_url( admin_url( 'admin.php?page=pdf-builder-settings' ) ),
			),
		),
	),
);
?>
<?php wp_print_inline_script_tag( 'window.pdfibDashboardData = ' . wp_json_encode( $pdfib_dashboard_data ) . ';' ); ?>
<div id="pdfib-dashboard-root"></div>
<section class="pdfb-promo-section">
	<div class="pdfb-promo-teaser">
		<div class="pdfb-promo-teaser-left">
			<div class="pdfb-promo-teaser-badge">OFFRE PRO</div>
			<h2 class="pdfb-promo-teaser-title">
				Passez à la version PRO<br />
				pour débloquer les options avancées
			</h2>
			<p class="pdfb-promo-teaser-sub">
				La version gratuite reste active. La version PRO est un plugin séparé,
				installé à part, avec une clé de licence dédiée.
			</p>
			<div class="pdfb-promo-teaser-progress">
				<div class="pdfb-promo-progress-bar">
					<div class="pdfb-promo-progress-fill" style="width:78%"></div>
				</div>
				<span>Plugin séparé • Licence dédiée • Support du vendeur</span>
			</div>
			<a href="<?php echo esc_url( $pdfib_purchase_url ); ?>" target="_blank" rel="noopener noreferrer" class="pdfb-promo-cta-button">
				<?php esc_html_e( 'Voir les formules PRO', 'advanced-pdf-invoice-builder' ); ?>
			</a>
		</div>
	</div>
</section>
<section class="pdfb-dashboard-guide">
	<h3><?php esc_html_e( 'Comment obtenir la version PRO et activer la licence', 'advanced-pdf-invoice-builder' ); ?></h3>
	<p>
		<?php esc_html_e( 'La version PRO est un plugin séparé. Après l\'achat, téléchargez le ZIP, installez-le dans WordPress, puis saisissez la clé de licence reçue dans la page licence du plugin PRO.', 'advanced-pdf-invoice-builder' ); ?>
	</p>
	<ol style="margin:0;padding-left:20px;color:#334155;line-height:1.7;">
		<li><?php esc_html_e( 'Achetez la formule PRO qui correspond à votre besoin.', 'advanced-pdf-invoice-builder' ); ?></li>
		<li><?php esc_html_e( 'Téléchargez le fichier ZIP du plugin PRO depuis votre espace client.', 'advanced-pdf-invoice-builder' ); ?></li>
		<li><?php esc_html_e( 'Dans WordPress, allez dans Extensions > Ajouter > Téléverser un plugin, puis activez le plugin PRO.', 'advanced-pdf-invoice-builder' ); ?></li>
		<li><?php esc_html_e( 'Ouvrez ensuite la page licence du plugin PRO et collez la clé envoyée après l\'achat.', 'advanced-pdf-invoice-builder' ); ?></li>
	</ol>
	<a href="<?php echo esc_url( $pdfib_purchase_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary" style="margin-top:16px;">
		<?php esc_html_e( 'Voir les formules PRO', 'advanced-pdf-invoice-builder' ); ?>
	</a>
</section>
<section class="pdfb-promo-section">
	<details class="pdfb-promo-compare" open>
		<summary class="pdfb-promo-compare__toggle">
			<span class="pdfb-promo-compare__toggle-label pdfb-promo-compare__toggle-label--open"><?php esc_html_e( 'Voir moins', 'advanced-pdf-invoice-builder' ); ?></span>
			<span class="pdfb-promo-compare__toggle-label pdfb-promo-compare__toggle-label--closed"><?php esc_html_e( 'Voir plus de fonctionnalités', 'advanced-pdf-invoice-builder' ); ?></span>
			<span class="pdfb-promo-compare__toggle-icon pdfb-promo-compare__toggle-icon--open" aria-hidden="true">▲</span>
			<span class="pdfb-promo-compare__toggle-icon pdfb-promo-compare__toggle-icon--closed" aria-hidden="true">▼</span>
		</summary>
		<div class="pdfb-promo-compare__header">
			<h3><?php esc_html_e( 'Gratuit vs PRO', 'advanced-pdf-invoice-builder' ); ?></h3>
		</div>
		<div class="pdfb-promo-compare__table-wrap">
			<table class="pdfb-promo-compare__table">
				<colgroup>
					<col class="pdfb-promo-compare__col pdfb-promo-compare__col--feature" />
					<col class="pdfb-promo-compare__col pdfb-promo-compare__col--free" />
					<col class="pdfb-promo-compare__col pdfb-promo-compare__col--pro" />
				</colgroup>
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Fonctionnalité', 'advanced-pdf-invoice-builder' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Gratuit', 'advanced-pdf-invoice-builder' ); ?></th>
						<th scope="col"><?php esc_html_e( '★ PRO', 'advanced-pdf-invoice-builder' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pdfib_compare_rows as $pdfib_compare_row ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $pdfib_compare_row['label'] ); ?></th>
							<td>
								<span class="<?php echo esc_attr( $pdfib_compare_value_class( (string) $pdfib_compare_row['free'], false ) ); ?>">
									<?php echo esc_html( $pdfib_compare_row['free'] ); ?>
								</span>
							</td>
							<td>
								<span class="<?php echo esc_attr( $pdfib_compare_value_class( (string) $pdfib_compare_row['pro'], true ) ); ?>">
									<?php echo esc_html( $pdfib_compare_row['pro'] ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</details>
</section>
