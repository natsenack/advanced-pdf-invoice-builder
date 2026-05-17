<?php
/**
 * Paramètres Généraux - Advanced PDF Invoice Builder (Version comprimée).
 *
 * Onglet principal des paramètres généraux avec informations entreprise.
 *
 * @package  PDFIB
 * @version  2.2.0
 * @since    2025-12-09
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	// Récupération sécurisée des paramètres.
	$pdfib_settings = pdfib_get_option( 'pdfib_settings', array() );

	// Récupération des informations légales individuellement.
	$pdfib_company_phone_manual = pdfib_get_option( 'pdfib_company_phone_manual', '' );
	$pdfib_company_siret        = pdfib_get_option( 'pdfib_company_siret', '' );
	$pdfib_company_vat          = pdfib_get_option( 'pdfib_company_vat', '' );
	$pdfib_company_rcs          = pdfib_get_option( 'pdfib_company_rcs', '' );
	$pdfib_company_capital      = pdfib_get_option( 'pdfib_company_capital', '' );

	// Récupération des informations WooCommerce.
	$pdfib_store_name     = get_option( 'woocommerce_store_name', get_bloginfo( 'name' ) );
	$pdfib_store_address  = get_option( 'woocommerce_store_address', '' );
	$pdfib_store_city     = get_option( 'woocommerce_store_city', '' );
	$pdfib_store_postcode = get_option( 'woocommerce_store_postcode', '' );
	$pdfib_store_country  = get_option( 'woocommerce_default_country', '' );
	$pdfib_admin_email    = get_option( 'admin_email', '' );

	// Construction de l'adresse complète.
	$pdfib_address_parts = array_filter( array( $pdfib_store_address, $pdfib_store_city, $pdfib_store_postcode, $pdfib_store_country ) );
	$pdfib_full_address  = implode( ', ', $pdfib_address_parts );

?>

<section id="general" class="settings-section pdfb-general-settings" role="tabpanel" aria-labelledby="tab-general">
	<header class="pdfb-section-header">
		<h2 style="display: flex; justify-content: flex-start; align-items: center;" class="pdfb-section-title">
			<span class="dashicons dashicons-admin-home"></span>
			<span>Paramètres Généraux</span>
		</h2>
		<p class="pdfb-section-description">
			Configuration générale et informations entreprise.
		</p>
	</header>

	<div class="pdfb-settings-content">
		<!-- Formulaire supprimé - les champs sont maintenant dans le formulaire principal -->
		<input type="hidden" name="current_tab" value="general">

			<!-- Informations WooCommerce (compact) -->
			<div class="pdfb-settings-card">
				<div class="card-header">
					<h3 class="card-title">
						<span class="dashicons dashicons-store"></span>
						Données WooCommerce
					</h3>
				</div>
				<div class="card-content">
					<div class="pdfb-woo-info-compact">
						<div><strong>Entreprise:</strong> <?php echo esc_html( $pdfib_store_name ? $pdfib_store_name : '<em>Non défini</em>' ); ?></div>
						<div><strong>Adresse:</strong> <?php echo esc_html( $pdfib_full_address ? $pdfib_full_address : '<em>Non définie</em>' ); ?></div>
						<div><strong>Email:</strong> <?php echo esc_html( $pdfib_admin_email ? $pdfib_admin_email : '<em>Non défini</em>' ); ?></div>
					</div>
					<p class="pdfb-woo-notice">
						<small>⚙️ Modifiez dans <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings' ) ); ?>" target="_blank">WooCommerce → Réglages</a></small>
					</p>
				</div>
			</div>

			<!-- Informations complémentaires (compact) -->
			<div class="pdfb-settings-card">
				<div class="card-header">
					<h3 class="card-title">
						<span class="dashicons dashicons-edit"></span>
						Informations légales
					</h3>
				</div>
				<div class="card-content">
					<div class="form-grid-compact">
						<div class="pdfb-pdf-form-field">
							<label for="company_phone_manual">📞 Téléphone</label>
							<input type="tel" id="company_phone_manual" name="company_phone_manual"
									value="<?php echo esc_attr( $pdfib_company_phone_manual ); ?>"
									placeholder="+33 1 23 45 67 89"
									autocomplete="tel"
									aria-describedby="error-company_phone_manual"
									title="Numéro de téléphone valide (ex : +33 1 23 45 67 89)"/>
							<span class="pdfb-field-error" id="error-company_phone_manual" role="alert" aria-live="polite"></span>
						</div>

						<div class="pdfb-pdf-form-field">
							<label for="company_siret">🆔 SIRET</label>
							<input type="text" id="company_siret" name="company_siret"
									value="<?php echo esc_attr( $pdfib_company_siret ); ?>"
									placeholder="12345678900012"
									autocomplete="off"
									maxlength="17"
									aria-describedby="error-company_siret"
									title="Numéro SIRET : 14 chiffres (espaces autorisés)"/>
							<span class="pdfb-field-error" id="error-company_siret" role="alert" aria-live="polite"></span>
						</div>

						<div class="pdfb-pdf-form-field">
							<label for="company_vat">💰 TVA intracommunautaire</label>
							<input type="text" id="company_vat" name="company_vat"
									value="<?php echo esc_attr( $pdfib_company_vat ); ?>"
									placeholder="FR12345678901"
									autocomplete="off"
									maxlength="15"
									aria-describedby="error-company_vat"
									title="Numéro de TVA intracommunautaire (ex : FR12345678901)"/>
							<span class="pdfb-field-error" id="error-company_vat" role="alert" aria-live="polite"></span>
						</div>

						<div class="pdfb-pdf-form-field">
							<label for="company_rcs">🏢 RCS</label>
							<input type="text" id="company_rcs" name="company_rcs"
									value="<?php echo esc_attr( $pdfib_company_rcs ); ?>"
									placeholder="Lyon B 123456789"
									autocomplete="off"
									aria-describedby="error-company_rcs"
									title="Numéro RCS (ex : Lyon B 123456789)"/>
							<span class="pdfb-field-error" id="error-company_rcs" role="alert" aria-live="polite"></span>
						</div>

						<div class="pdfb-pdf-form-field">
							<label for="company_capital">📈 Capital social</label>
							<input type="text" id="company_capital" name="company_capital"
									value="<?php echo esc_attr( $pdfib_company_capital ); ?>"
									placeholder="10 000 €"
									autocomplete="off"
									aria-describedby="error-company_capital"
									title="Capital social (ex : 10 000 € ou 10000.00)"/>
							<span class="pdfb-field-error" id="error-company_capital" role="alert" aria-live="polite"></span>
						</div>
					</div>
				</div>
			</div>
		</div>
</section>




