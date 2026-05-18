<?php
/**
 * Advanced PDF Invoice Builder - Admin Page Renderer.
 * Responsable du rendu HTML de la page d'administration (Tableau de bord).
 *
 * @package PDFIB\Admin\Renderers
 */

namespace PDFIB\Admin\Renderers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Rend la page dashboard admin.
 */
class AdminPageRenderer {

	/**
	 * Instance admin principale.
	 *
	 * @var mixed
	 */
	private mixed $admin;

	/**
	 * Constructeur.
	 *
	 * @param mixed $admin Instance admin.
	 */
	public function __construct( mixed $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Affiche la page admin dashboard.
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		$pdfib_tpl = PDFIB_PLUGIN_DIR . 'templates/admin/dashboard-page.php';
		if ( ! file_exists( $pdfib_tpl ) ) {
			echo '<div class="wrap"><h1>Advanced PDF Invoice Builder</h1><p>' . esc_html__( 'Erreur: Template dashboard introuvable.', 'advanced-pdf-invoice-builder' ) . '</p></div>';
			return;
		}
		$provider       = $this->admin->get_dashboard_data_provider();
		$stats          = $provider ? $provider->get_dashboard_stats() : array(
			'templates' => 0,
			'documents' => 0,
			'today'     => 0,
		);
		$plugin_version = $provider ? $provider->get_plugin_version() : '1.0.0';
		$is_premium     = \PDFIB\Admin\PdfBuilderAdmin::is_premium_active();
		// $stats, $plugin_version et $is_premium sont utilisées par le template inclus ci-dessous.
		include $pdfib_tpl;
	}
}
