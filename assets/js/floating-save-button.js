/**
 * PDF Builder Pro Settings - Floating Save Button Handler
 * Gère la sauvegarde des paramètres via AJAX
 */
(function($) {
	'use strict';

	$(document).ready(function() {

		// S'assurer qu'ajaxurl est défini
		if (typeof ajaxurl === 'undefined') {
			ajaxurl = pdfBuilderSettings.ajaxUrl;
		}

		var $saveBtn = $('#pdf-builder-save-settings');
		var $saveStatus = $('#pdf-builder-save-status');
		var currentTab = pdfBuilderSettings.currentTab;


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

			// Validation client-side pour l'onglet général
			if (currentTab === 'general' && typeof window.pdfibGeneralValidation !== 'undefined') {
				if (!window.pdfibGeneralValidation.validateAll()) {
					return false;
				}
			}

			// Désactiver le bouton pendant la sauvegarde
			$btn.addClass('saving').prop('disabled', true).find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-update dashicons-spin');

			// Collecter les données du formulaire actif
			var formData = new FormData();
			formData.append('action', 'pdfib_save_settings');
			formData.append('tab', currentTab);

			// Chercher le nonce depuis plusieurs sources
			var nonce = '';

			// Source 1: pdf_builder_ajax.nonce (depuis settings-loader.php)
			if (typeof pdf_builder_ajax !== 'undefined' && pdf_builder_ajax && pdf_builder_ajax.nonce) {
				nonce = pdf_builder_ajax.nonce;
			}
			// Source 2: window.pdf_builder_ajax.nonce
			else if (typeof window.pdf_builder_ajax !== 'undefined' && window.pdf_builder_ajax && window.pdf_builder_ajax.nonce) {
				nonce = window.pdf_builder_ajax.nonce;
			}
			// Source 3: pdfBuilderAjax.nonce (fallback camelCase)
			else if (typeof pdfBuilderAjax !== 'undefined' && pdfBuilderAjax && pdfBuilderAjax.nonce) {
				nonce = pdfBuilderAjax.nonce;
			}
			// Source 4: window.pdfBuilderNonce (legacy)
			else if (typeof window.pdfBuilderNonce !== 'undefined' && window.pdfBuilderNonce) {
				nonce = window.pdfBuilderNonce;
			}
			// Source 5: pdfBuilderSettings.nonce (depuis wp_localize_script)
			else if (typeof pdfBuilderSettings !== 'undefined' && pdfBuilderSettings && pdfBuilderSettings.nonce) {
				nonce = pdfBuilderSettings.nonce;
			}

			formData.append('_wpnonce', nonce);

			// Collecter les champs du formulaire actif
			var $activeForm = $('#pdf-builder-settings-form-' + currentTab);

			if ($activeForm.length > 0) {
				var fieldCount = 0;
				$activeForm.find('input, select, textarea').each(function() {
					var $field = $(this);
					var fieldName = $field.attr('name');
					if (fieldName) {
						if ($field.attr('type') === 'checkbox') {
							formData.append(fieldName, $field.is(':checked') ? '1' : '0');
						} else if ($field.attr('type') === 'radio') {
							if ($field.is(':checked')) {
								formData.append(fieldName, $field.val());
							}
						} else {
							formData.append(fieldName, $field.val() || '');
						}
						fieldCount++;
					}
				});
			}


			// Envoyer la requête AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				timeout: 30000, // 30 secondes timeout
				success: function(response) {
					if (response.success) {
						showStatus(pdfBuilderSettings.strings.successMessage, 'success');
						$btn.removeClass('saving').addClass('saved');
					} else {
						// Afficher les erreurs de validation par champ si le serveur les retourne
						if (response.data && response.data.fields && typeof window.pdfibGeneralValidation !== 'undefined') {
							window.pdfibGeneralValidation.showServerErrors(response.data.fields);
						}
						var message = (response.data && response.data.message) ? response.data.message : pdfBuilderSettings.strings.errorMessage;
						showStatus(message, 'error');
						$btn.removeClass('saving').addClass('error');
					}
				},
				error: function(xhr, status, error) {
					var errorMsg = pdfBuilderSettings.strings.connectionError;
					if (status === 'timeout') {
						errorMsg = pdfBuilderSettings.strings.timeoutError;
					}
					showStatus(errorMsg, 'error');
					$btn.removeClass('saving').addClass('error');
				},
				complete: function() {
					// Réactiver le bouton après un délai
					setTimeout(function() {
						$btn.removeClass('saving saved error').prop('disabled', false)
							.find('.dashicons').removeClass('dashicons-update dashicons-spin').addClass('dashicons-yes');
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

})(jQuery);
