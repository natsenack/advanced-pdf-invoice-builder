<?php
/**
 * Advanced PDF Invoice Builder
 *
 * Security settings tab template.
 *
 * PHP version 8.2
 *
 * @category Plugin
 * @package  PDFIB
 * @author   PDF Invoice Builder <support@threeaxe.fr>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://threeaxe.fr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

$pdfib_settings = pdfib_get_option( 'pdfib_settings', array() );

$pdfib_security_level          = isset( $pdfib_settings['pdfib_security_level'] )
	? (string) $pdfib_settings['pdfib_security_level']
	: 'medium';
$pdfib_enable_logging          = isset( $pdfib_settings['pdfib_enable_logging'] )
	? (string) $pdfib_settings['pdfib_enable_logging']
	: '1';
$pdfib_gdpr_enabled            = isset( $pdfib_settings['pdfib_gdpr_enabled'] )
	? (string) $pdfib_settings['pdfib_gdpr_enabled']
	: '1';
$pdfib_gdpr_consent_required   = isset(
	$pdfib_settings['pdfib_gdpr_consent_required']
)
	? (string) $pdfib_settings['pdfib_gdpr_consent_required']
	: '1';
$pdfib_gdpr_data_retention     = isset(
	$pdfib_settings['pdfib_gdpr_data_retention']
)
	? (string) $pdfib_settings['pdfib_gdpr_data_retention']
	: '2555';
$pdfib_gdpr_audit_enabled      = isset(
	$pdfib_settings['pdfib_gdpr_audit_enabled']
)
	? (string) $pdfib_settings['pdfib_gdpr_audit_enabled']
	: '1';
$pdfib_gdpr_encryption_enabled = isset(
	$pdfib_settings['pdfib_gdpr_encryption_enabled']
)
	? (string) $pdfib_settings['pdfib_gdpr_encryption_enabled']
	: '1';
$pdfib_gdpr_consent_analytics  = isset(
	$pdfib_settings['pdfib_gdpr_consent_analytics']
)
	? (string) $pdfib_settings['pdfib_gdpr_consent_analytics']
	: '1';
$pdfib_gdpr_consent_templates  = isset(
	$pdfib_settings['pdfib_gdpr_consent_templates']
)
	? (string) $pdfib_settings['pdfib_gdpr_consent_templates']
	: '1';
$pdfib_gdpr_consent_marketing  = isset(
	$pdfib_settings['pdfib_gdpr_consent_marketing']
)
	? (string) $pdfib_settings['pdfib_gdpr_consent_marketing']
	: '0';

$pdfib_logging_is_enabled = ( '1' === $pdfib_enable_logging );
$pdfib_gdpr_is_enabled    = ( '1' === $pdfib_gdpr_enabled );

$pdfib_security_status_text  = $pdfib_logging_is_enabled
	? __( 'ACTIF', 'advanced-pdf-invoice-builder' )
	: __( 'INACTIF', 'advanced-pdf-invoice-builder' );
$pdfib_security_status_color = $pdfib_logging_is_enabled
	? '#28a745'
	: '#dc3545';
$pdfib_gdpr_status_text      = $pdfib_gdpr_is_enabled
	? __( 'ACTIF', 'advanced-pdf-invoice-builder' )
	: __( 'INACTIF', 'advanced-pdf-invoice-builder' );
$pdfib_gdpr_status_color     = $pdfib_gdpr_is_enabled
	? '#28a745'
	: '#dc3545';

$pdfib_gdpr_nonce = wp_create_nonce( 'pdfib_gdpr' );
?>
<section id="securite" class="pdfib-settings-section">
	<header class="pdfib-settings-section__header">
		<h3>
			<?php
			esc_html_e(
				'Securite et conformite',
				'advanced-pdf-invoice-builder'
			);
			?>
		</h3>
	</header>

	<div class="pdfib-settings-card pdfib-settings-card--security">
		<h4>
			<?php esc_html_e( 'Securite', 'advanced-pdf-invoice-builder' ); ?>
			<span id="security-status-indicator"
				class="pdfib-status-badge"
				style="background-color: 
				<?php
				echo esc_attr( $pdfib_security_status_color );
				?>
				;">
				<?php echo esc_html( $pdfib_security_status_text ); ?>
			</span>
		</h4>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="security_level">
							<?php
							esc_html_e(
								'Niveau de securite',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<select id="security_level"
							name="pdfib_settings[pdfib_security_level]">
							<option value="low" 
							<?php
							selected( $pdfib_security_level, 'low' );
							?>
							>
								<?php
								esc_html_e(
									'Faible',
									'advanced-pdf-invoice-builder'
								);
								?>
							</option>
							<option value="medium" 
							<?php
							selected( $pdfib_security_level, 'medium' );
							?>
							>
								<?php
								esc_html_e(
									'Moyen',
									'advanced-pdf-invoice-builder'
								);
								?>
							</option>
							<option value="high" 
							<?php
							selected( $pdfib_security_level, 'high' );
							?>
							>
								<?php
								esc_html_e(
									'Eleve',
									'advanced-pdf-invoice-builder'
								);
								?>
							</option>
						</select>
						<p class="description">
							<?php
							esc_html_e(
								'Definit le niveau de controle applique a la generation des PDF.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="enable_logging">
							<?php
							esc_html_e(
								'Journalisation activee',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<input type="hidden"
							name="pdfib_settings[pdfib_enable_logging]"
							value="0">
						<label class="toggle-switch" for="enable_logging">
							<input type="checkbox"
								id="enable_logging"
								name="pdfib_settings[pdfib_enable_logging]"
								value="1" 
								<?php
								checked( $pdfib_enable_logging, '1' );
								?>
								>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">
							<?php
							esc_html_e(
								'Active la journalisation des actions pour l audit et le diagnostic.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="pdfib-settings-card pdfib-settings-card--gdpr">
		<h4>
			<?php
			esc_html_e(
				'Gestion RGPD et conformite',
				'advanced-pdf-invoice-builder'
			);
			?>
			<span id="rgpd-status-indicator"
				class="pdfib-status-badge"
				style="background-color: 
				<?php
				echo esc_attr( $pdfib_gdpr_status_color );
				?>
				;">
				<?php echo esc_html( $pdfib_gdpr_status_text ); ?>
			</span>
		</h4>

		<h5>
			<?php
			esc_html_e(
				'Parametres RGPD',
				'advanced-pdf-invoice-builder'
			);
			?>
		</h5>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="gdpr_enabled">
							<?php
							esc_html_e(
								'RGPD active',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<input type="hidden"
							name="pdfib_settings[pdfib_gdpr_enabled]"
							value="0">
						<label class="toggle-switch" for="gdpr_enabled">
							<input type="checkbox"
								id="gdpr_enabled"
								name="pdfib_settings[pdfib_gdpr_enabled]"
								value="1" 
								<?php
								checked( $pdfib_gdpr_enabled, '1' );
								?>
								>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">
							<?php
							esc_html_e(
								'Active les controles de conformite RGPD pour le plugin.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="gdpr_consent_required">
							<?php
							esc_html_e(
								'Consentement requis',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<input type="hidden"
							name="pdfib_settings[pdfib_gdpr_consent_required]"
							value="0">
						<label class="toggle-switch"
							for="gdpr_consent_required">
							<input type="checkbox"
								id="gdpr_consent_required"
								name="pdfib_settings[pdfib_gdpr_consent_required]"
								value="1" 
								<?php
								checked( $pdfib_gdpr_consent_required, '1' );
								?>
								>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">
							<?php
							esc_html_e(
								'Exige un consentement avant la generation des documents.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="gdpr_data_retention">
							<?php
							esc_html_e(
								'Retention des donnees (jours)',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<input type="number"
							id="gdpr_data_retention"
							name="pdfib_settings[pdfib_gdpr_data_retention]"
							value="
							<?php
							echo esc_attr( $pdfib_gdpr_data_retention );
							?>
							"
							min="30"
							max="3650">
						<p class="description">
							<?php
							esc_html_e(
								'Nombre de jours avant suppression automatique des donnees utilisateur.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="gdpr_audit_enabled">
							<?php
							esc_html_e(
								'Audit logging',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<input type="hidden"
							name="pdfib_settings[pdfib_gdpr_audit_enabled]"
							value="0">
						<label class="toggle-switch"
							for="gdpr_audit_enabled">
							<input type="checkbox"
								id="gdpr_audit_enabled"
								name="pdfib_settings[pdfib_gdpr_audit_enabled]"
								value="1" 
								<?php
								checked( $pdfib_gdpr_audit_enabled, '1' );
								?>
								>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">
							<?php
							esc_html_e(
								'Conserve les traces utiles pour les audits RGPD.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="gdpr_encryption_enabled">
							<?php
							esc_html_e(
								'Chiffrement des donnees',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<input type="hidden"
							name="pdfib_settings[pdfib_gdpr_encryption_enabled]"
							value="0">
						<label class="toggle-switch"
							for="gdpr_encryption_enabled">
							<input type="checkbox"
								id="gdpr_encryption_enabled"
								name="pdfib_settings[pdfib_gdpr_encryption_enabled]"
								value="1" 
								<?php
								checked( $pdfib_gdpr_encryption_enabled, '1' );
								?>
								>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">
							<?php
							esc_html_e(
								'Chiffre les donnees sensibles traitees par le plugin.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<h5>
			<?php
			esc_html_e(
				'Types de consentement',
				'advanced-pdf-invoice-builder'
			);
			?>
		</h5>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="gdpr_consent_analytics">
							<?php
							esc_html_e(
								'Consentement analytics',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<input type="hidden"
							name="pdfib_settings[pdfib_gdpr_consent_analytics]"
							value="0">
						<label class="toggle-switch"
							for="gdpr_consent_analytics">
							<input type="checkbox"
								id="gdpr_consent_analytics"
								name="pdfib_settings[pdfib_gdpr_consent_analytics]"
								value="1" 
								<?php
								checked( $pdfib_gdpr_consent_analytics, '1' );
								?>
								>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">
							<?php
							esc_html_e(
								'Autorise la collecte de donnees anonymes pour ameliorer le service.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="gdpr_consent_templates">
							<?php
							esc_html_e(
								'Consentement templates',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<input type="hidden"
							name="pdfib_settings[pdfib_gdpr_consent_templates]"
							value="0">
						<label class="toggle-switch"
							for="gdpr_consent_templates">
							<input type="checkbox"
								id="gdpr_consent_templates"
								name="pdfib_settings[pdfib_gdpr_consent_templates]"
								value="1" 
								<?php
								checked( $pdfib_gdpr_consent_templates, '1' );
								?>
								>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">
							<?php
							esc_html_e(
								'Autorise la sauvegarde des templates sur le serveur.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="gdpr_consent_marketing">
							<?php
							esc_html_e(
								'Consentement marketing',
								'advanced-pdf-invoice-builder'
							);
							?>
						</label>
					</th>
					<td>
						<input type="hidden"
							name="pdfib_settings[pdfib_gdpr_consent_marketing]"
							value="0">
						<label class="toggle-switch"
							for="gdpr_consent_marketing">
							<input type="checkbox"
								id="gdpr_consent_marketing"
								name="pdfib_settings[pdfib_gdpr_consent_marketing]"
								value="1" 
								<?php
								checked( $pdfib_gdpr_consent_marketing, '1' );
								?>
								>
							<span class="toggle-slider"></span>
						</label>
						<p class="description">
							<?php
							esc_html_e(
								'Autorise la reception d informations sur les nouvelles fonctionnalites.',
								'advanced-pdf-invoice-builder'
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<h5>
			<?php
			esc_html_e(
				'Actions utilisateur RGPD',
				'advanced-pdf-invoice-builder'
			);
			?>
		</h5>

		<div class="pdfib-gdpr-panel">
			<p>
				<strong>
					<?php
					esc_html_e(
						'Droits RGPD :',
						'advanced-pdf-invoice-builder'
					);
					?>
				</strong>
				<?php
				esc_html_e(
					'Vous pouvez exporter, consulter ou supprimer vos donnees personnelles gerees par le plugin.',
					'advanced-pdf-invoice-builder'
				);
				?>
			</p>

			<div class="pdfib-gdpr-actions">
				<div class="pdfib-gdpr-actions__group">
					<label for="export-format" class="screen-reader-text">
						<?php
						esc_html_e(
							'Format d export',
							'advanced-pdf-invoice-builder'
						);
						?>
					</label>
					<select id="export-format">
						<option value="html">
							<?php
							esc_html_e(
								'HTML lisible',
								'advanced-pdf-invoice-builder'
							);
							?>
						</option>
						<option value="json">
							<?php
							esc_html_e(
								'JSON brut',
								'advanced-pdf-invoice-builder'
							);
							?>
						</option>
					</select>
					<button type="button"
						id="export-my-data"
						class="button button-secondary">
						<?php
						esc_html_e(
							'Exporter mes donnees',
							'advanced-pdf-invoice-builder'
						);
						?>
					</button>
				</div>

				<button type="button"
					id="delete-my-data"
					class="button button-secondary">
					<?php
					esc_html_e(
						'Supprimer mes donnees',
						'advanced-pdf-invoice-builder'
					);
					?>
				</button>

				<button type="button"
					id="view-consent-status"
					class="button button-secondary">
					<?php
					esc_html_e(
						'Voir mes consentements',
						'advanced-pdf-invoice-builder'
					);
					?>
				</button>
			</div>

			<div id="gdpr-user-actions-result"
				style="display: none; margin-top: 16px;"></div>
			<input type="hidden"
				id="export_user_data_nonce"
				value="<?php echo esc_attr( $pdfib_gdpr_nonce ); ?>">
			<input type="hidden"
				id="delete_user_data_nonce"
				value="<?php echo esc_attr( $pdfib_gdpr_nonce ); ?>">
			<input type="hidden"
				id="audit_log_nonce"
				value="<?php echo esc_attr( $pdfib_gdpr_nonce ); ?>">
		</div>

		<h5>
			<?php
			esc_html_e(
				'Logs d audit RGPD',
				'advanced-pdf-invoice-builder'
			);
			?>
		</h5>

		<div class="pdfib-gdpr-panel">
			<p>
				<?php
				esc_html_e(
					'Consultez et exportez les logs pour verifier la conformite.',
					'advanced-pdf-invoice-builder'
				);
				?>
			</p>

			<div class="pdfib-gdpr-actions">
				<button type="button"
					id="refresh-audit-log"
					class="button button-secondary">
					<?php
					esc_html_e(
						'Actualiser les logs',
						'advanced-pdf-invoice-builder'
					);
					?>
				</button>
				<button type="button"
					id="export-audit-log"
					class="button button-primary">
					<?php
					esc_html_e(
						'Exporter les logs',
						'advanced-pdf-invoice-builder'
					);
					?>
				</button>
			</div>

			<div id="audit-log-container"
				style="display: none; margin-top: 20px; max-height: 300px;
				overflow-y: auto;">
				<div id="audit-log-content"></div>
			</div>
		</div>
	</div>
</section>





