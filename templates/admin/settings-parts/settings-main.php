<?php
/**
 * Page principale des paramètres Advanced PDF Invoice Builder - VERSION SIMPLIFIÉE.
 *
 * @package PDFIB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Accès refusé. Vous devez être administrateur pour accéder à cette page.', 'advanced-pdf-invoice-builder' ) );
}

// Récupération des paramètres.
$pdfib_settings         = pdfib_get_option( 'pdfib_settings', array() );
$pdfib_current_tab      = sanitize_text_field( wp_unslash( $GLOBALS['_GET']['tab'] ?? 'general' ) );
$pdfib_license_manager  = apply_filters( 'pdfib_license_manager_instance', null );
$pdfib_is_pro_active    = function_exists( 'pdfib_is_pro_plugin_active' )
	&& pdfib_is_pro_plugin_active();
$pdfib_show_licence_tab = $pdfib_is_pro_active || is_object( $pdfib_license_manager );

$pdfib_valid_tabs = array( 'general', 'securite', 'pdf', 'contenu', 'templates' );
if ( $pdfib_show_licence_tab ) {
	$pdfib_valid_tabs[] = 'licence';
}
if ( ! in_array( $pdfib_current_tab, $pdfib_valid_tabs, true ) ) {
	$pdfib_current_tab = 'general';
}

// Enregistrer les paramètres - UTILISE LE SYSTÈME PERSONNALISÉ.
if ( isset( $GLOBALS['_POST']['submit'] ) && isset( $GLOBALS['_POST']['pdfib_settings'] ) ) {
	check_admin_referer( 'pdfib_settings-options' );

		// Déterminer si c'est une sauvegarde flottante.
	$pdfib_is_floating_save = isset( $GLOBALS['_POST']['pdfib_floating_save'] ) && '1' === $GLOBALS['_POST']['pdfib_floating_save'];
	$pdfib_save_type        = $pdfib_is_floating_save ? 'FLOATING SAVE' : 'REGULAR SAVE';

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Accès refusé', 'advanced-pdf-invoice-builder' ) );
	}

	// Sanitize and save settings.

	$pdfib_raw_settings = wp_unslash( $GLOBALS['_POST']['pdfib_settings'] );
	$pdfib_settings     = array();
	foreach ( $pdfib_raw_settings as $pdfib_key => $pdfib_value ) {
		if ( is_array( $pdfib_value ) ) {
			$pdfib_settings[ $pdfib_key ] = array_map( 'sanitize_text_field', $pdfib_value );
			continue;
		}

		$pdfib_sanitized_value = sanitize_text_field( $pdfib_value );
		if ( 'pdfib_canvas_dpi' === $pdfib_key && strpos( $pdfib_sanitized_value, ',' ) !== false ) {
			$pdfib_dpi_parts              = array_map( 'trim', explode( ',', $pdfib_sanitized_value ) );
			$pdfib_dpi_parts              = array_filter(
				array_map( 'intval', $pdfib_dpi_parts ),
				function ( $dpi ) {
					return in_array( $dpi, array( 72, 96, 150, 200, 300, 600, 1200 ), true );
				}
			);
			$pdfib_settings[ $pdfib_key ] = implode( ',', $pdfib_dpi_parts );
			continue;
		}

		$pdfib_settings[ $pdfib_key ] = $pdfib_sanitized_value;
	}
	$pdfib_save_result = pdfib_update_option( 'pdfib_settings', $pdfib_settings );

		// Redirection pour éviter la resoumission avec message de succès.
	$pdfib_redirect_url = add_query_arg(
		array(
			'page'    => 'pdf-builder-settings',
			'tab'     => $pdfib_current_tab,
			'updated' => '1',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $pdfib_redirect_url );
	exit;
}

// Afficher le message de succès si la mise à jour a réussi.
if ( isset( $GLOBALS['_GET']['updated'] ) && '1' === $GLOBALS['_GET']['updated'] ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Paramètres sauvegardés avec succès !', 'advanced-pdf-invoice-builder' ) . '</p></div>';
		}
	);
}

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Paramètres Advanced PDF Invoice Builder', 'advanced-pdf-invoice-builder' ); ?></h1>

	<form method="post" action="" id="pdf-builder-settings-form">
		<?php wp_nonce_field( 'pdfib_settings-options' ); ?>
		<!-- Champ caché pour la soumission manuelle du formulaire -->
		<input type="hidden" name="submit" value="1">

		<!-- Navigation par onglets -->
		<h2 class="pdfb-nav-tab-wrapper">
			<div class="pdfb-tabs-container">
				<?php $pdfib_active_class = ' pdfb-nav-tab-active'; ?>
				<a href="?page=pdf-builder-settings&tab=general" class="pdfb-nav-tab<?php echo esc_attr( 'general' === $pdfib_current_tab ? $pdfib_active_class : '' ); ?>">
					<span class="pdfb-tab-icon">⚙️</span>
					<span class="pdfb-tab-text"><?php esc_html_e( 'Général', 'advanced-pdf-invoice-builder' ); ?></span>
				</a>
				<?php if ( $pdfib_show_licence_tab ) : ?>
					<a href="?page=pdf-builder-settings&tab=licence" class="pdfb-nav-tab<?php echo esc_attr( 'licence' === $pdfib_current_tab ? $pdfib_active_class : '' ); ?>">
						<span class="pdfb-tab-icon">🔑</span>
						<span class="pdfb-tab-text"><?php esc_html_e( 'Licence', 'advanced-pdf-invoice-builder' ); ?></span>
					</a>
				<?php endif; ?>
				<a href="?page=pdf-builder-settings&tab=securite" class="pdfb-nav-tab<?php echo esc_attr( 'securite' === $pdfib_current_tab ? $pdfib_active_class : '' ); ?>">
					<span class="pdfb-tab-icon">🔒</span>
					<span class="pdfb-tab-text"><?php esc_html_e( 'Sécurité', 'advanced-pdf-invoice-builder' ); ?></span>
				</a>
				<a href="?page=pdf-builder-settings&tab=pdf" class="pdfb-nav-tab<?php echo esc_attr( 'pdf' === $pdfib_current_tab ? $pdfib_active_class : '' ); ?>">
					<span class="pdfb-tab-icon">📄</span>
					<span class="pdfb-tab-text"><?php esc_html_e( 'Configuration PDF', 'advanced-pdf-invoice-builder' ); ?></span>
				</a>
				<a href="?page=pdf-builder-settings&tab=contenu" class="pdfb-nav-tab<?php echo esc_attr( 'contenu' === $pdfib_current_tab ? $pdfib_active_class : '' ); ?>">
					<span class="pdfb-tab-icon">🎨</span>
					<span class="pdfb-tab-text"><?php esc_html_e( 'Canvas & Design', 'advanced-pdf-invoice-builder' ); ?></span>
				</a>
				<a href="?page=pdf-builder-settings&tab=templates" class="pdfb-nav-tab<?php echo esc_attr( 'templates' === $pdfib_current_tab ? $pdfib_active_class : '' ); ?>">
					<span class="pdfb-tab-icon">📋</span>
					<span class="pdfb-tab-text"><?php esc_html_e( 'Templates', 'advanced-pdf-invoice-builder' ); ?></span>
				</a>
			</div>
		</h2>

		<div class="settings-content-wrapper">
			<?php
			switch ( $pdfib_current_tab ) {
				case 'general':
					include_once __DIR__ . '/settings-general.php';
					break;
				case 'licence':
					include_once __DIR__ . '/settings-licence.php';
					break;
				case 'securite':
					include_once __DIR__ . '/settings-securite.php';
					break;
				case 'pdf':
					include_once __DIR__ . '/settings-pdf.php';
					break;
				case 'contenu':
					include_once __DIR__ . '/settings-contenu.php';
					break;
				case 'templates':
					include_once __DIR__ . '/settings-templates.php';
					break;

				default:
					echo '<p>' . esc_html__( 'Onglet non valide.', 'advanced-pdf-invoice-builder' ) . '</p>';
					break;
			}
			?>

			<!-- Bouton flottant Enregistrer -->
			<div id="pdf-builder-floating-save" class="pdfb-pdf-builder-floating-save">
				<button type="button" id="pdf-builder-save-settings" class="pdfb-pdf-builder-save-btn">
					<span class="dashicons dashicons-yes"></span>
					<?php esc_html_e( 'Enregistrer', 'advanced-pdf-invoice-builder' ); ?>
				</button>
				<div id="pdf-builder-save-status" class="pdfb-pdf-builder-save-status"></div>
			</div>
		</div>
	</form>
</div>

<?php
$pdfib_script = ( static function () use ( $pdfib_current_tab ): string {
	ob_start();
	try {
		?>
	jQuery(document).ready(function($) {

	// S'assurer qu'ajaxurl est défini
	if (typeof ajaxurl === 'undefined') {
	ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	}

	var $saveBtn = $('#pdf-builder-save-settings');
	var $saveStatus = $('#pdf-builder-save-status');
	var currentTab = '<?php echo esc_js( $pdfib_current_tab ); ?>';

	// --- Suivi des modifications (dirty-state) ---
	function captureFormState() {
	var state = [];
	var $form = $('#pdf-builder-settings-form');
	if ($form.length === 0) {
	return 'ERROR';
	}
	$form.find('input, select, textarea').each(function() {
	var $f = $(this);
	var fieldName = $f.attr('name');
	if (!fieldName || fieldName === '_wpnonce') return; // Skip nonce
	var type = $f.attr('type');
	var value = '';
	if (type === 'checkbox') {
	value = $f.is(':checked') ? '1' : '0';
	} else if (type === 'radio') {
	if ($f.is(':checked')) value = $f.val();
	else return; // Skip non-checked radios
	} else {
	value = $f.val() || '';
	}
	state.push(fieldName + '=' + value);
	});
	return state.join('&');
	}

	var initialFormState = captureFormState();

	function updateSaveBtnState() {
	var isDirty = captureFormState() !== initialFormState;
	$saveBtn.prop('disabled', !isDirty);
	}

	// Désactiver le bouton au chargement
	updateSaveBtnState();

	// Activer le bouton dès qu'un champ est modifié
	$('#pdf-builder-settings-form').on('change input keyup', 'input, select, textarea', updateSaveBtnState);


	// Fonction pour afficher le statut
	function showStatus(message, type) {
	$saveStatus.removeClass('success error').addClass(type + ' show').text(message);

	setTimeout(function() {
	$saveStatus.removeClass('show');
	}, 3000);
	}

	// Gestionnaire du clic sur le bouton Enregistrer
	$saveBtn.on('click', function(event) {
	event.preventDefault();
	event.stopImmediatePropagation(); // Arrêter tous les autres gestionnaires

	var $btn = $(this);

	// Vérifier si déjà en cours de sauvegarde
	if ($btn.hasClass('saving')) {
	return false;
	}

	// Désactiver le bouton pendant la sauvegarde
	$btn.addClass('saving').prop('disabled', true).find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-update dashicons-spin');

	// Collecter les données du formulaire actif
	var ajaxData = {
	action: 'pdfib_save_settings',
	tab: currentTab,
	nonce: '<?php echo esc_js( wp_create_nonce( 'pdfib_ajax' ) ); ?>'
	};

	// Collecter les champs du formulaire actif
	var $activeForm = $('#pdf-builder-settings-form');

	if ($activeForm.length > 0) {
	var fieldCount = 0;
	var templateFields = {};
	$activeForm.find('input, select, textarea').each(function() {
	var $field = $(this);
	var fieldName = $field.attr('name');
	if (fieldName && fieldName !== '_wpnonce') { // Skip _wpnonce as we set it explicitly
	var fieldValue = '';
	if ($field.attr('type') === 'checkbox') {
	fieldValue = $field.is(':checked') ? '1' : '0';
	} else if ($field.attr('type') === 'radio') {
	if ($field.is(':checked')) {
	fieldValue = $field.val();
	} else {
	return; // Skip non-checked radios
	}
	} else {
	fieldValue = $field.val() || '';
	}
	ajaxData[fieldName] = fieldValue;
	fieldCount++;

	// Log des champs templates spécifiquement
	if (fieldName.indexOf('order_status_templates') !== -1) {
	templateFields[fieldName] = fieldValue;
	}
	}
	});
	if (Object.keys(templateFields).length > 0) {
	}
	}

	// Log détaillé des données AJAX avant envoi


	// Envoyer la requête AJAX
	$.ajax({
	url: ajaxurl,
	type: 'POST',
	data: ajaxData,
	timeout: 30000, // 30 secondes timeout
	success: function(response) {
	if (response.success) {
	showStatus('<?php echo esc_js( __( 'Paramètres sauvegardés', 'advanced-pdf-invoice-builder' ) ); ?>', 'success');
	$btn.removeClass('saving').addClass('saved');

	// Réinitialiser la baseline après sauvegarde
	initialFormState = captureFormState();

	// Notification unifiée
	if (typeof showSuccessNotification !== 'undefined') {
	showSuccessNotification(response.data.message || '<?php echo esc_js( __( 'Paramètres sauvegardés avec succès', 'advanced-pdf-invoice-builder' ) ); ?>');
	}
	} else {
	showStatus(response.data.message || '<?php echo esc_js( __( 'Erreur lors de la sauvegarde', 'advanced-pdf-invoice-builder' ) ); ?>', 'error');
	$btn.removeClass('saving').addClass('error');

	// Notification unifiée d'erreur
	if (typeof showErrorNotification !== 'undefined') {
	showErrorNotification(response.data.message || '<?php echo esc_js( __( 'Erreur lors de la sauvegarde', 'advanced-pdf-invoice-builder' ) ); ?>');
	}
	}
	},
	error: function(xhr, status, error) {
	var errorMsg = '<?php echo esc_js( __( 'Erreur de connexion', 'advanced-pdf-invoice-builder' ) ); ?>';
	if (status === 'timeout') {
	errorMsg = '<?php echo esc_js( __( 'Timeout - Réessayez', 'advanced-pdf-invoice-builder' ) ); ?>';
	}
	showStatus(errorMsg, 'error');
	$btn.removeClass('saving').addClass('error');

	// Notification unifiée d'erreur
	if (typeof showErrorNotification !== 'undefined') {
	showErrorNotification(errorMsg);
	}
	},
	complete: function() {
	// Réactiver le bouton après un délai (état selon dirty-state)
	setTimeout(function() {
	$btn.removeClass('saving saved error')
	.find('.dashicons').removeClass('dashicons-update dashicons-spin').addClass('dashicons-yes');
	updateSaveBtnState();
	}, 2000);
	}
	});

	return false; // Sécurité supplémentaire
	});

	// Changer d'onglet
	$('.nav-tab').on('click', function(e) {
	e.preventDefault();
	var tab = $(this).attr('href').split('tab=')[1];
	if (tab) {
	currentTab = tab;
	window.location.href = $(this).attr('href');
	}
	});

	});
		<?php
			$pdfib_ob_result = ob_get_clean();
			return false !== $pdfib_ob_result ? $pdfib_ob_result : '';
	} catch ( \Throwable $e ) {
		ob_end_clean();
		return '';
	}
} )();
wp_print_inline_script_tag( $pdfib_script );
?>

<?php
// Inclure les modales canvas à la fin pour éviter les conflits de structure.
require_once __DIR__ . '/settings-modals.php';
?>
