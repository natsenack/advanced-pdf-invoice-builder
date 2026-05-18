<?php
/**
 * Advanced PDF Invoice Builder - Feature comparison renderer.
 *
 * Shared handler for the Free vs PRO table used across admin screens.
 *
 * PHP version 8.2
 *
 * @package PDFIB\Admin\Renderers
 */

namespace PDFIB\Admin\Renderers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the shared Free vs PRO comparison table.
 */
class PdfBuilderFeatureComparisonRenderer {

	/**
	 * Returns the shared comparison rows.
	 *
	 * @return array<int, array<string, string|bool>>
	 */
	public static function get_rows(): array {
		return array(
			array(
				'label'   => __( 'Éditeur visuel drag & drop', 'advanced-pdf-invoice-builder' ),
				'free'    => '✓',
				'pro'     => '✓',
				'visible' => true,
			),
			array(
				'label'   => __( 'Génération PDF', 'advanced-pdf-invoice-builder' ),
				'free'    => 'PDF',
				'pro'     => 'PDF',
				'visible' => true,
			),
			array(
				'label'   => __( 'Éléments standards', 'advanced-pdf-invoice-builder' ),
				'free'    => __( 'Texte, image, ligne, rectangle', 'advanced-pdf-invoice-builder' ),
				'pro'     => __( 'Texte, image, ligne, rectangle', 'advanced-pdf-invoice-builder' ),
				'visible' => true,
			),
			array(
				'label'   => __( 'Types de document', 'advanced-pdf-invoice-builder' ),
				'free'    => __( 'Facture, devis', 'advanced-pdf-invoice-builder' ),
				'pro'     => __( 'Facture, devis, bon de commande, avoir, relevé, contrat', 'advanced-pdf-invoice-builder' ),
				'visible' => true,
			),
			array(
				'label'   => __( 'Modèles de texte dynamique', 'advanced-pdf-invoice-builder' ),
				'free'    => __( '3 modèles', 'advanced-pdf-invoice-builder' ),
				'pro'     => __( '36 modèles', 'advanced-pdf-invoice-builder' ),
				'visible' => true,
			),
			array(
				'label'   => __( 'Modèles de mentions', 'advanced-pdf-invoice-builder' ),
				'free'    => __( '3 modèles', 'advanced-pdf-invoice-builder' ),
				'pro'     => __( '21 modèles', 'advanced-pdf-invoice-builder' ),
				'visible' => true,
			),
			array(
				'label'   => __( 'Intégration WooCommerce', 'advanced-pdf-invoice-builder' ),
				'free'    => '✓',
				'pro'     => '✓',
				'visible' => true,
			),
			array(
				'label'   => __( 'Export multi-format', 'advanced-pdf-invoice-builder' ),
				'free'    => 'PDF',
				'pro'     => 'PDF, PNG, JPG',
				'visible' => false,
			),
			array(
				'label'   => __( 'Fond transparent à l\'export', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
			array(
				'label'   => __( 'Résolution d\'export', 'advanced-pdf-invoice-builder' ),
				'free'    => __( '72, 96, 150 DPI', 'advanced-pdf-invoice-builder' ),
				'pro'     => __( '72, 96, 150, 300, 600 DPI', 'advanced-pdf-invoice-builder' ),
				'visible' => false,
			),
			array(
				'label'   => __( 'Formats de page', 'advanced-pdf-invoice-builder' ),
				'free'    => __( 'A4 portrait', 'advanced-pdf-invoice-builder' ),
				'pro'     => __( 'A3, Letter, Legal + paysage', 'advanced-pdf-invoice-builder' ),
				'visible' => false,
			),
			array(
				'label'   => __( 'Galerie de modèles avancés', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
			array(
				'label'   => __( 'Dupliquer un template', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
			array(
				'label'   => __( 'Définir un template par défaut', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
			array(
				'label'   => __( 'Bouton "Nouveau" dans l\'éditeur', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
			array(
				'label'   => __( 'Navigation grille, snap & guides', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
			array(
				'label'   => __( 'Sélection multiple & mode groupe', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
			array(
				'label'   => __( 'Raccourcis clavier', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
			array(
				'label'   => __( 'Couleurs canvas avancées', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
			array(
				'label'   => __( 'Thèmes prédéfinis de l\'éditeur', 'advanced-pdf-invoice-builder' ),
				'free'    => '—',
				'pro'     => '✓',
				'visible' => false,
			),
		);
	}

	/**
	 * Returns the CSS class used by the comparison values.
	 *
	 * @param string $value Feature value.
	 * @param bool   $is_pro Whether the value is for the PRO column.
	 * @return string
	 */
	private static function get_value_class( string $value, bool $is_pro ): string {
		if ( '✓' === $value ) {
			return $is_pro ? 'pdfib-cmp-val pdfib-cmp-val--yes-pro' : 'pdfib-cmp-val pdfib-cmp-val--yes-free';
		}
		if ( '—' === $value ) {
			return 'pdfib-cmp-val pdfib-cmp-val--no';
		}
		return $is_pro ? 'pdfib-cmp-val pdfib-cmp-val--text-pro' : 'pdfib-cmp-val pdfib-cmp-val--text-free';
	}

	/**
	 * Render the shared comparison card.
	 *
	 * @return void
	 */
	public static function render_card(): void {
		$rows                  = self::get_rows();
		static $style_printed  = false;
		static $script_printed = false;

		if ( ! $style_printed ) {
			$style_printed = true;
			?>
			<style>
				.pdfib-cmp-table {
					width: 100%;
					border-collapse: collapse;
					font-size: 13px;
				}

				.pdfib-cmp-table thead th {
					padding: 10px 14px;
					background: #f1f5f9;
					border-bottom: 2px solid #e2e8f0;
					font-weight: 700;
					font-size: 11px;
					text-transform: uppercase;
					letter-spacing: 0.05em;
					color: #475569;
				}

				.pdfib-cmp-table thead th:first-child {
					text-align: left;
				}

				.pdfib-cmp-table thead th:nth-child(2),
				.pdfib-cmp-table thead th:nth-child(3) {
					text-align: center;
					width: 22%;
				}

				.pdfib-cmp-table tbody tr {
					border-bottom: 1px solid #f1f5f9;
				}

				.pdfib-cmp-table tbody tr:last-child {
					border-bottom: 0;
				}

				.pdfib-cmp-table tbody th {
					padding: 11px 14px;
					font-weight: 500;
					color: #1e293b;
					text-align: left;
				}

				.pdfib-cmp-table tbody td {
					padding: 11px 14px;
					text-align: center;
					vertical-align: middle;
				}

				.pdfib-cmp-val {
					font-size: 13px;
					font-weight: 500;
				}

				.pdfib-cmp-val--yes-free  { color: #2563eb; }
				.pdfib-cmp-val--yes-pro   { color: #16a34a; font-weight: 700; }
				.pdfib-cmp-val--no        { color: #94a3b8; }
				.pdfib-cmp-val--text-free { color: #475569; }
				.pdfib-cmp-val--text-pro  { color: #1d4ed8; font-weight: 600; }

				.pdfib-cmp-toggle {
					display: block;
					width: 100%;
					padding: 12px;
					background: none;
					border: none;
					border-top: 1px solid #e2e8f0;
					color: #2563eb;
					font-size: 13px;
					font-weight: 600;
					cursor: pointer;
				}

				.pdfib-cmp-toggle:hover {
					background: #f8fafc;
				}
			</style>
			<?php
		}
		?>
		<div class="pdfib-cmp-wrap" data-pdfib-comparison-card="1" data-pdfib-comparison-open="0">
			<table class="pdfib-cmp-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Fonctionnalité', 'advanced-pdf-invoice-builder' ); ?></th>
						<th><?php esc_html_e( 'Gratuit', 'advanced-pdf-invoice-builder' ); ?></th>
						<th><?php esc_html_e( 'PRO', 'advanced-pdf-invoice-builder' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr<?php echo ! empty( $row['visible'] ) ? '' : ' data-pdfib-comparison-extra="1" style="display:none"'; ?>>
							<th scope="row"><?php echo esc_html( (string) $row['label'] ); ?></th>
							<td><span class="<?php echo esc_attr( self::get_value_class( (string) $row['free'], false ) ); ?>"><?php echo esc_html( (string) $row['free'] ); ?></span></td>
							<td><span class="<?php echo esc_attr( self::get_value_class( (string) $row['pro'], true ) ); ?>"><?php echo esc_html( (string) $row['pro'] ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<button type="button" class="pdfib-cmp-toggle" data-pdfib-comparison-toggle="1" aria-expanded="false">
				<span data-pdfib-comparison-label-open><?php esc_html_e( 'Voir plus de fonctionnalités', 'advanced-pdf-invoice-builder' ); ?></span>
				<span data-pdfib-comparison-label-closed style="display:none"><?php esc_html_e( 'Voir moins', 'advanced-pdf-invoice-builder' ); ?></span>
			</button>
		</div>
		<?php if ( ! $script_printed ) : ?>
			<?php $script_printed = true; ?>
			<script>
				document.addEventListener('click', function (event) {
					var button = event.target.closest('[data-pdfib-comparison-toggle]');
					if (!button) {
						return;
					}

					var card = button.closest('[data-pdfib-comparison-card]');
					if (!card) {
						return;
					}

					var isOpen = '1' === card.getAttribute('data-pdfib-comparison-open');
					var nextOpen = ! isOpen;
					card.setAttribute('data-pdfib-comparison-open', nextOpen ? '1' : '0');

					card.querySelectorAll('[data-pdfib-comparison-extra="1"]').forEach(function (row) {
						row.style.display = nextOpen ? '' : 'none';
					});

					var openLabel = button.querySelector('[data-pdfib-comparison-label-open]');
					var closedLabel = button.querySelector('[data-pdfib-comparison-label-closed]');
					if (openLabel && closedLabel) {
						openLabel.style.display = nextOpen ? 'none' : '';
						closedLabel.style.display = nextOpen ? '' : 'none';
					}

					button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
				});
			</script>
		<?php endif; ?>
		<?php
	}
}
