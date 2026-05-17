<?php
/**
 * PDF tab content.
 *
 * Updated: 2025-11-19 01:40:00
 *
 * @package PDFIB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pdfib_settings = pdfib_get_option( 'pdfib_settings', array() );
// Recuperer les parametres du Canvas.
$pdfib_canvas_format      = $pdfib_settings['pdfib_canvas_format'] ?? 'A4';
$pdfib_canvas_orientation = $pdfib_settings['pdfib_canvas_default_orientation'] ?? 'portrait';
?>

<!-- Section Principale : Configuration PDF -->
<section id="pdf-config" class="pdf-section contenu-canvas-section">
	<h3 style="display: flex; justify-content: flex-start; align-items: center;">
		<span>📄 Configuration PDF</span>
	</h3>

	<div class="pdfb-pdf-settings-wrapper">
		<div class="pdfb-pdf-settings-left">
			<h4 style="color: #495057; margin-top: 0; border-bottom: 2px solid #007cba; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
				⚙️ Paramètres principaux
				<span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: #7c3aed; color: #fff; padding: 2px 7px; border-radius: 10px; vertical-align: middle;">beta</span>
			</h4>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="pdf_quality">Qualité</label></th>
					<td>
						<select id="pdf_quality" name="pdfib_settings[pdfib_pdf_quality]">
							<option value="low" <?php selected( $pdfib_settings['pdfib_pdf_quality'] ?? 'high', 'low' ); ?>>Rapide (fichiers légers)</option>
							<option value="medium" <?php selected( $pdfib_settings['pdfib_pdf_quality'] ?? 'high', 'medium' ); ?>>Équilibré</option>
							<option value="high" <?php selected( $pdfib_settings['pdfib_pdf_quality'] ?? 'high', 'high' ); ?>>Haute qualité</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><span>Format de page</span></th>
					<td>
						<?php
						$pdfib_format_labels = array(
							'A4'     => 'A4',
							'A3'     => 'A3',
							'Letter' => 'Lettre US',
						);
						?>
						<div style="display: flex; align-items: center; gap: 10px;">
							<span style="font-weight: 600; font-size: 16px; color: #667eea;">📋 <?php echo esc_html( $pdfib_format_labels[ $pdfib_canvas_format ] ?? $pdfib_canvas_format ); ?></span>
							<button type="button" class="button button-small" onclick="if(window.PDFBuilderTabsAPI && PDFBuilderTabsAPI.switchToTab) { PDFBuilderTabsAPI.switchToTab('canvas'); return false; } else if(window.switchTab) { switchTab('canvas'); return false; } else { window.location.hash = '#canvas'; return false; }">Modifier dans Canvas →</button>
						</div>
						<p class="description" style="margin-top: 12px; color: #666; font-size: 12px;">Le format PDF est synchronisé avec le format du Canvas. Pour le modifier, accédez à l'onglet <strong>Canvas</strong>.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><span>Orientation</span></th>
					<td>
						<?php
						$pdfib_orientation_labels = array(
							'portrait'  => 'Portrait',
							'landscape' => 'Paysage',
						);
						$pdfib_orientation_emoji  = ( 'landscape' === $pdfib_canvas_orientation ) ? '📄' : '📋';
						?>
						<div style="display: flex; align-items: center; gap: 10px;">
							<span style="font-weight: 600; font-size: 16px; color: #667eea;"><?php echo esc_html( $pdfib_orientation_emoji ); ?> <?php echo esc_html( $pdfib_orientation_labels[ $pdfib_canvas_orientation ] ?? $pdfib_canvas_orientation ); ?></span>
							<button type="button" class="button button-small" onclick="if(window.PDFBuilderTabsAPI && PDFBuilderTabsAPI.switchToTab) { PDFBuilderTabsAPI.switchToTab('canvas'); return false; } else if(window.switchTab) { switchTab('canvas'); return false; } else { window.location.hash = '#canvas'; return false; }">Modifier dans Canvas →</button>
						</div>
						<p class="description" style="margin-top: 12px; color: #666; font-size: 12px;">L'orientation PDF est synchronisée avec l'orientation du Canvas. Pour la modifier, accédez à l'onglet <strong>Canvas</strong>.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pdfib_pdf_cache_enabled">Cache activé</label></th>
					<td>
						<label class="toggle-switch" aria-label="Cache activé">
							<input type="hidden" name="pdfib_settings[pdfib_pdf_cache_enabled]" value="0">
							<input type="checkbox" id="pdfib_pdf_cache_enabled" name="pdfib_settings[pdfib_pdf_cache_enabled]" value="1" <?php checked( $pdfib_settings['pdfib_pdf_cache_enabled'] ?? '0', '1' ); ?>>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">Améliorer les performances en mettant en cache les PDF</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="export_quality">Qualité export (%)</label></th>
					<td>
						<input type="number" id="export_quality" name="pdfib_settings[pdfib_canvas_export_quality]" value="<?php echo esc_attr( $pdfib_settings['pdfib_canvas_export_quality'] ?? '90' ); ?>" min="1" max="100">
						<p class="description">Qualité de l'image exportée (1-100%)</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="export_format">Format export</label></th>
					<td>
						<?php
						$pdfib_can_use_multi_format_export = \PDFIB\Managers\PdfBuilderFeatureManager::can_use_feature( 'multi_format_export' );
						$pdfib_current_export_format       = $pdfib_settings['pdfib_canvas_export_format'] ?? 'pdf';
						?>
						<select id="export_format" name="pdfib_settings[pdfib_canvas_export_format]">
							<option value="pdf" <?php selected( $pdfib_current_export_format, 'pdf' ); ?>>PDF</option>
							<?php
							do_action(
								'pdfib_canvas_export_format_premium_options',
								array(
									'export_format' => $pdfib_current_export_format,
									'can_use_multi_format_export' => $pdfib_can_use_multi_format_export,
								)
							);
							?>
						</select>
						<?php
							do_action(
								'pdfib_pdf_export_format_premium_notice',
								array(
									'can_use_multi_format_export' => $pdfib_can_use_multi_format_export,
								)
							);
							?>
						<p class="description">Format de fichier pour l'export</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="export_transparent">Fond transparent</label></th>
					<td>
						<label class="toggle-switch" aria-label="Fond transparent">
							<input type="hidden" name="pdfib_settings[pdfib_canvas_export_transparent]" value="0">
							<input type="checkbox" id="export_transparent" name="pdfib_settings[pdfib_canvas_export_transparent]" value="1" <?php checked( $pdfib_settings['pdfib_canvas_export_transparent'] ?? '0', '1' ); ?>>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">Exporter avec un fond transparent (PNG uniquement)</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pdf_compression">Compression</label></th>
					<td>
						<select id="pdf_compression" name="pdfib_settings[pdfib_pdf_compression]">
							<option value="none" <?php selected( $pdfib_settings['pdfib_pdf_compression'] ?? 'medium', 'none' ); ?>>Aucune</option>
							<option value="medium" <?php selected( $pdfib_settings['pdfib_pdf_compression'] ?? 'medium', 'medium' ); ?>>Moyenne</option>
							<option value="high" <?php selected( $pdfib_settings['pdfib_pdf_compression'] ?? 'medium', 'high' ); ?>>Élevée</option>
						</select>
						<p class="description">Réduit la taille des fichiers PDF</p>
					</td>
				</tr>
				<?php
					do_action(
						'pdfib_pdf_advanced_premium_fields',
						array(
							'settings' => $pdfib_settings,
						)
					);
					?>
			</table>
			<?php
				do_action(
					'pdfib_pdf_advanced_premium_notice',
					array(
						'settings' => $pdfib_settings,
					)
				);
				?>
		</div><!-- pdfb-pdf-settings-left -->
		<!-- Panel d'aperçu PDF -->
		<div class="pdfb-pdf-preview-panel" data-canvas-format="<?php echo esc_attr( $pdfib_canvas_format ?? 'A4' ); ?>" data-canvas-orientation="<?php echo esc_attr( $pdfib_canvas_orientation ?? 'portrait' ); ?>">
			<div class="pdfb-pdf-preview-title">
				👁️ Aperçu PDF
			</div>

			<div class="pdfb-pdf-preview-canvas">
				<div class="pdfb-pdf-preview-frame" data-format="A4" id="pdf-preview-frame" style="--preview-ratio: 210/297;">
				</div>
			</div>

			<div class="pdfb-pdf-preview-info">
				<div class="pdfb-pdf-info-item">
					<span class="pdfb-pdf-info-label">Format</span>
					<span class="pdfb-pdf-info-value" id="preview-format">A4</span>
				</div>
				<div class="pdfb-pdf-info-item">
					<span class="pdfb-pdf-info-label">Orientation</span>
					<span class="pdfb-pdf-info-value" id="preview-orientation">Portrait</span>
				</div>
				<div class="pdfb-pdf-info-item">
					<span class="pdfb-pdf-info-label">Qualité</span>
					<span class="pdfb-pdf-info-value" id="preview-quality">Haute</span>
					<div class="pdfb-pdf-quality-bar">
						<div class="pdfb-pdf-quality-fill" id="preview-quality-bar" style="width: 100%;"></div>
					</div>
				</div>
				<div class="pdfb-pdf-info-item">
					<span class="pdfb-pdf-info-label">Compression</span>
					<span class="pdfb-pdf-info-value" id="preview-compression">Moyenne</span>
				</div>
			</div>

			<div class="pdfb-pdf-file-size">
				<span style="opacity: 0.9;">Taille estimée</span>
				<div class="pdfb-pdf-file-size-value" id="preview-file-size">~850 KB</div>
				<small style="opacity: 0.8;">*(à titre indicatif)</small>
			</div>
		</div><!-- pdfb-pdf-preview-panel -->
	</div><!-- pdfb-pdf-settings-wrapper -->
</section><!-- pdf-config -->

<!-- JavaScript pour l'aperçu PDF en temps réel -->
<?php
$pdfib_script = ( static function (): string {
	ob_start();
	?>
	(function($) {
	'use strict';

	// Configuration des formats PDF
	const pdfFormats = {
	'A4': { width: 210, height: 297, ratio: '210/297' },
	'A3': { width: 297, height: 420, ratio: '297/420' },
	'Letter': { width: 215.9, height: 279.4, ratio: '215.9/279.4' }
	};

	// Configuration des qualités
	const qualityConfigs = {
	'low': { label: 'Rapide', factor: 0.6, compression: 'Élevée' },
	'medium': { label: 'Équilibré', factor: 0.8, compression: 'Moyenne' },
	'high': { label: 'Haute', factor: 1.0, compression: 'Minimale' }
	};

	// Helper: applique les mises à jour DOM de l'aperçu PDF
	function applyPdfPreviewDisplay( qualityConfig, ratio, canvasFormat, canvasOrientation, compressionLabel, qualityPercent, estimatedSize ) {
	$('#pdf-preview-frame').css('--preview-ratio', ratio);
	$('#pdf-preview-frame').attr('data-format', canvasFormat + ' ' + (canvasOrientation === 'portrait' ? '📋' : '📄'));
	$('#preview-format').text(canvasFormat);
	$('#preview-orientation').text(canvasOrientation === 'portrait' ? 'Portrait' : 'Paysage');
	$('#preview-quality').text(qualityConfig.label);
	$('#preview-compression').text(compressionLabel);
	$('#preview-quality-bar').css('width', qualityPercent + '%');
	$('#preview-file-size').text('~' + (estimatedSize < 1024 ? estimatedSize + ' KB' : (estimatedSize / 1024).toFixed(1) + ' MB' ));
		}

		// Fonction pour mettre a jour l'apercu
		function updatePdfPreview() {
		const quality=$('#pdf_quality').val() || 'high' ;
		const canvasFormat=$('.pdfb-pdf-preview-panel').attr('data-canvas-format') || 'A4' ;
		const canvasOrientation=$('.pdfb-pdf-preview-panel').attr('data-canvas-orientation') || 'portrait' ;
		const compression=$('#pdf_compression').val() || 'medium' ;
		const exportQuality=parseInt($('#export_quality').val()) || 90;
		const qualityConfig=qualityConfigs[quality] || qualityConfigs['high'];
		const formatConfig=pdfFormats[canvasFormat] || pdfFormats['A4'];
		let ratio=formatConfig.ratio;
		if (canvasOrientation==='landscape' ) {
		ratio=formatConfig.ratio.split('/').reverse().join('/');
		}
		const compressionLabel=(qualityConfigs[compression] && qualityConfigs[compression].compression) ? qualityConfigs[compression].compression : 'Moyenne' ;
		const qualityPercent=Math.round((exportQuality / 100) * 100);
		const baseSizeKb=500;
		const qualityMultiplier=qualityConfig.factor;
		const compressionFactor=compression==='high' ? 0.6 : (compression==='medium' ? 0.85 : 1.0);
		const estimatedSize=Math.round(baseSizeKb * qualityMultiplier * (exportQuality / 100) * compressionFactor);
		applyPdfPreviewDisplay( qualityConfig, ratio, canvasFormat, canvasOrientation, compressionLabel, qualityPercent, estimatedSize );
		}


		// Ecouter les changements avec support input et change
		$(document).ready(function() {

		// Selects et dropdowns (evenement change) - Format et orientation viennent du Canvas
		$('#pdf_quality, #pdf_compression').on('change', function() {
		updatePdfPreview();
		});

		// Input number (evenement input pour temps reel + change pour le fallback)
		$('#export_quality').on('input change', function() {
		updatePdfPreview();
		});

		// Initialisation
		updatePdfPreview();
		});
		})(jQuery);
		<?php
		$pdfib_ob_result = ob_get_clean();
		return false !== $pdfib_ob_result ? $pdfib_ob_result : '';
} )();
	wp_print_inline_script_tag( $pdfib_script );
?>
