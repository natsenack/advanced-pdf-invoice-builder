/* global ajaxurl, availableDpis, availableFormats, availableOrientations, dpiOptions, formatOptions, orientationOptions, pdfBuilderTemplatesNonce, pdfibEditorBaseUrl */

(function() {
	'use strict';

	function getAjaxUrl() {
		return (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
	}

	function getNonce() {
		return (typeof pdfBuilderTemplatesNonce !== 'undefined' ? pdfBuilderTemplatesNonce : '');
	}

	function showTemplateLimitNotice() {
		var notice = document.getElementById('template-limit-notice');
		if (notice) {
			notice.style.display = 'block';
			document.cookie = 'pdfib_template_limit_notice_dismissed=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
		}
	}

	function dismissTemplateLimitNotice() {
		var notice = document.getElementById('template-limit-notice');
		if (notice) {
			notice.style.display = 'none';
			document.cookie = 'pdfib_template_limit_notice_dismissed=true; path=/; max-age=86400';
		}
	}

	function filterTemplates(filter) {
		var cards = document.querySelectorAll('.pdfb-template-card');
		cards.forEach(function(card) {
			if (filter === 'all' || card.classList.contains('template-type-' + filter)) {
				card.style.display = 'block';
			} else {
				card.style.display = 'none';
			}
		});
	}

	function duplicateTemplate(templateId, templateName) {
		if (!confirm('Êtes-vous sûr de vouloir dupliquer le template "' + templateName + '" ?')) {
			return;
		}

		fetch(getAjaxUrl(), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: new URLSearchParams({
				action: 'pdfib_duplicate_template',
				template_id: templateId,
				template_name: templateName + ' (Copie)',
				nonce: getNonce()
			})
		})
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				if (data.success) {
					alert('Template dupliqué avec succès !');
					location.reload();
					return;
				}

				var msg = data && data.data && data.data.message
					? data.data.message
					: ((data && data.message) ? data.message : 'Erreur inconnue');
				alert('Erreur lors de la duplication: ' + msg);
			})
			.catch(function(error) {
				alert('Erreur réseau: ' + (error && error.message ? error.message : 'Erreur réseau'));
			});
	}

	function showTemplateIconPreview(container) {
		var card = container.closest('.pdfb-template-card');
		if (!card) {
			return;
		}

		var templateType = 'autre';
		var classes = card.className;

		if (classes.indexOf('template-type-facture') !== -1) {
			templateType = 'facture';
		} else if (classes.indexOf('template-type-devis') !== -1) {
			templateType = 'devis';
		} else if (classes.indexOf('template-type-commande') !== -1) {
			templateType = 'commande';
		} else if (classes.indexOf('template-type-contrat') !== -1) {
			templateType = 'contrat';
		} else if (classes.indexOf('template-type-newsletter') !== -1) {
			templateType = 'newsletter';
		}

		var colors = {
			facture: '#007cba',
			devis: '#28a745',
			commande: '#ffc107',
			contrat: '#dc3545',
			newsletter: '#6f42c1',
			autre: '#6c757d'
		};
		var icons = {
			facture: '🧾',
			devis: '📋',
			commande: '📦',
			contrat: '📑',
			newsletter: '📰',
			autre: '📄'
		};
		var color = colors[templateType] || colors.autre;
		var icon = icons[templateType] || icons.autre;

		var outer = document.createElement( 'div' );
		outer.style.cssText = 'width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:4px;';
		var inner = document.createElement( 'div' );
		inner.style.cssText = 'text-align:center;color:' + color + ';';
		var iconDiv = document.createElement( 'div' );
		iconDiv.style.fontSize = '2rem';
		iconDiv.textContent = icon;
		var typeDiv = document.createElement( 'div' );
		typeDiv.style.cssText = 'font-size:10px;text-transform:uppercase;';
		typeDiv.textContent = templateType;
		inner.appendChild( iconDiv );
		inner.appendChild( typeDiv );
		outer.appendChild( inner );
		while ( container.firstChild ) { container.removeChild( container.firstChild ); }
		container.appendChild( outer );
	}

	function loadTemplatePreview(templateId) {
		var container = document.getElementById('preview-' + templateId);
		if (!container) {
			return;
		}
		showTemplateIconPreview(container);
	}

	function handleDeleteClick(templateId, templateName) {
		if (typeof window.confirmDeleteTemplate === 'function') {
			window.confirmDeleteTemplate(templateId, templateName);
			return;
		}
		alert('Erreur: Fonction de suppression non disponible');
	}

	function toggleDefaultTemplate(templateId, templateType, templateName) {
		fetch(getAjaxUrl(), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: new URLSearchParams({
				action: 'pdfib_toggle_default_template',
				template_id: templateId,
				template_type: templateType,
				nonce: getNonce()
			})
		})
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				if (data.success) {
					alert('Statut du template mis à jour !');
					location.reload();
					return;
				}
				alert('Erreur lors de la mise à jour: ' + (data.message || 'Erreur inconnue'));
			})
			.catch(function() {
				alert('Erreur lors de la mise à jour du template');
			});
	}

	function openTemplateSettings(templateId) {
		loadTemplateSettings(templateId);
	}

	function closeTemplateSettingsModal() {
		var modal = document.getElementById('template-settings-modal');
		if (modal) {
			modal.style.display = 'none';
		}
	}

	function showTemplateSettingsLoadError(errorMsg, notificationMsg) {
		var modal = document.getElementById('template-settings-modal');
		if (modal) {
			modal.innerHTML = '<div class="pdfb-template-modal-content">'
			+ '<div class="pdfb-template-modal-header"><div><h2>\u274C Erreur</h2></div>'
			+ '<button class="pdfb-template-modal-close" onclick="closeTemplateSettingsModal()">\u00D7</button></div>'
			+ '<div class="pdfb-template-modal-body" id="pdfb-err-body" style="padding:30px;text-align:center;"></div>'
			+ '<div class="pdfb-template-modal-footer"><button onclick="closeTemplateSettingsModal()" class="button">Fermer</button></div>'
			+ '</div>';
		var errBody = modal.querySelector( '#pdfb-err-body' );
		if ( errBody ) { errBody.textContent = errorMsg; }
			modal.style.display = 'flex';
		}
		if (typeof window.showErrorNotification !== 'undefined') {
			window.showErrorNotification(notificationMsg || errorMsg);
		}
	}

	function populateSelect(select, values, map, selectedValue, emptyLabel, warningEl) {
		if (!select) {
			return false;
		}
		select.innerHTML = '';
		if (values && values.length > 0) {
			values.forEach(function(value) {
				if (!map || !map[value]) {
					return;
				}
				var option = document.createElement('option');
				option.value = value;
				option.textContent = map[value];
				if (String(selectedValue) === String(value)) {
					option.selected = true;
				}
				select.appendChild(option);
			});
			if (warningEl) {
				warningEl.style.display = 'none';
			}
			return true;
		}
		var emptyOption = document.createElement('option');
		emptyOption.value = '';
		emptyOption.textContent = emptyLabel;
		select.appendChild(emptyOption);
		if (warningEl) {
			warningEl.style.display = 'block';
		}
		return false;
	}

	function loadTemplateSettings(templateId) {
		window.currentTemplateId = templateId;

		if (typeof jQuery === 'undefined') {
			showTemplateSettingsLoadError(
				'Erreur de communication avec le serveur',
				'Erreur de communication lors du chargement des paramètres'
			);
			return;
		}

		jQuery.ajax({
			url: getAjaxUrl(),
			type: 'POST',
			data: {
				action: 'pdfib_load_template_settings',
				template_id: templateId,
				nonce: getNonce()
			},
			success: function(response) {
				if (response.success && response.data && response.data.template) {
					displayTemplateSettings(response.data.template);
					return;
				}
				var msg = response.data && response.data.message
					? response.data.message
					: 'Erreur lors du chargement des paramètres';
				showTemplateSettingsLoadError(msg, 'Erreur: ' + msg);
			},
			error: function() {
				showTemplateSettingsLoadError(
					'Erreur de communication avec le serveur',
					'Erreur de communication lors du chargement des paramètres'
				);
			}
		});
	}

	function displayTemplateSettings(template) {
		var modal = document.getElementById('template-settings-modal');
		if (!modal) {
			return;
		}

		var canvasSettings = template.canvas_settings || {};
		var canvasFormat = canvasSettings.default_canvas_format || 'A4';
		var canvasOrientation = canvasSettings.default_canvas_orientation || 'portrait';
		var canvasDpi = canvasSettings.default_canvas_dpi || 96;
		var templateData = template.template_data || {};
		var tFormat, tOrientation, tDpi;

		if (templateData.canvas_format) {
			tFormat = templateData.canvas_format;
			tOrientation = templateData.canvas_orientation || canvasOrientation;
			tDpi = templateData.canvas_dpi || canvasDpi;
		} else if (templateData.canvasWidth && templateData.canvasHeight) {
			var w = parseFloat(templateData.canvasWidth);
			var h = parseFloat(templateData.canvasHeight);
			if (Math.abs(w - 210) < 10 && Math.abs(h - 297) < 10) {
				tFormat = 'A4'; tOrientation = 'portrait';
			} else if (Math.abs(w - 297) < 10 && Math.abs(h - 210) < 10) {
				tFormat = 'A4'; tOrientation = 'landscape';
			} else if (Math.abs(w - 148) < 10 && Math.abs(h - 210) < 10) {
				tFormat = 'A5'; tOrientation = 'portrait';
			} else if (Math.abs(w - 210) < 10 && Math.abs(h - 148) < 10) {
				tFormat = 'A5'; tOrientation = 'landscape';
			} else if (Math.abs(w - 216) < 10 && Math.abs(h - 279) < 10) {
				tFormat = 'Letter'; tOrientation = 'portrait';
			} else if (Math.abs(w - 279) < 10 && Math.abs(h - 216) < 10) {
				tFormat = 'Letter'; tOrientation = 'landscape';
			} else {
				tFormat = canvasFormat; tOrientation = canvasOrientation;
			}
			tDpi = templateData.canvasDpi || canvasDpi;
		} else {
			tFormat = canvasFormat;
			tOrientation = canvasOrientation;
			tDpi = canvasDpi;
		}

		var modalFormats = (Array.isArray(typeof availableFormats !== 'undefined' ? availableFormats : []) && (typeof availableFormats !== 'undefined' ? availableFormats : []).length > 0)
			? availableFormats
			: (canvasSettings.available_formats || ['A3', 'A4', 'A5', 'Letter', 'Legal']);
		var modalOrientations = (Array.isArray(typeof availableOrientations !== 'undefined' ? availableOrientations : []) && (typeof availableOrientations !== 'undefined' ? availableOrientations : []).length > 0)
			? availableOrientations
			: (canvasSettings.available_orientations || ['portrait', 'landscape']);
		var modalDpis = (Array.isArray(typeof availableDpis !== 'undefined' ? availableDpis : []) && (typeof availableDpis !== 'undefined' ? availableDpis : []).length > 0)
			? availableDpis
			: (canvasSettings.available_dpi || [72, 96, 150, 300, 600]);

		modal.innerHTML = '<div class="pdfb-template-modal-content">'
			+ '<div class="pdfb-template-modal-header"><div><h2>\u2699\uFE0F Param\u00E8tres du Template</h2>'
			+ '<p id="pdfb-modal-subtitle"></p></div>'
			+ '<button class="pdfb-template-modal-close" onclick="closeTemplateSettingsModal()">\u00D7</button></div>'
			+ '<div class="pdfb-template-modal-body" id="template-settings-body">'
			+ '<form id="template-settings-form">'
			+ '<input type="hidden" id="settings-template-id" value="">'
			+ '<div class="pdfb-template-settings-field"><label for="template-name">\uD83D\uDCDD Nom</label>'
			+ '<input type="text" id="template-name" value=""></div>'
			+ '<div class="pdfb-template-settings-field"><label for="template-description">\uD83D\uDCD6 Description</label>'
			+ '<textarea id="template-description" rows="3"></textarea></div>'
			+ '<div class="pdfb-template-settings-section"><h4>\u2699\uFE0F Param\u00E8tres avanc\u00E9s</h4>'
			+ '<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">'
			+ '<div class="pdfb-template-settings-field"><label for="template-format">\uD83D\uDCC4 Format</label><select id="template-format"></select>'
			+ '<div id="template-format-warning" style="display:none;">\u26A0\uFE0F Aucun format configur\u00E9</div></div>'
			+ '<div class="pdfb-template-settings-field"><label for="template-orientation">\uD83D\uDD04 Orientation</label><select id="template-orientation"></select>'
			+ '<div id="template-orientation-warning" style="display:none;">\u26A0\uFE0F Aucune orientation configur\u00E9e</div></div>'
			+ '<div class="pdfb-template-settings-field" style="grid-column:span 2"><label for="template-dpi">\uD83C\uDFAF DPI</label><select id="template-dpi"></select>'
			+ '<div id="template-dpi-warning" style="display:none;">\u26A0\uFE0F Aucune r\u00E9solution configur\u00E9e</div></div>'
			+ '</div></div>'
			+ '<div class="pdfb-template-settings-field"><label for="template-category">\uD83C\uDFF7\uFE0F Cat\u00E9gorie</label>'
			+ '<select id="template-category">'
			+ '<option value="facture">\uD83E\uDDFE Facture</option>'
			+ '<option value="devis">\uD83D\uDCCB Devis</option>'
			+ '<option value="commande">\uD83D\uDCE6 Commande</option>'
			+ '<option value="contrat">\uD83D\uDCD1 Contrat</option>'
			+ '<option value="newsletter">\uD83D\uDCF0 Newsletter</option>'
			+ '<option value="autre">\uD83D\uDCC4 Autre</option>'
			+ '</select></div>'
			+ '<div class="pdfb-template-settings-field"><label>'
			+ '<input type="checkbox" id="template-is-default" value="1">'
			+ ' \u2B50 Template par d\u00E9faut</label></div>'
			+ '</form>'
			+ '</div>'
			+ '<div class="pdfb-template-modal-footer">'
			+ '<button onclick="closeTemplateSettingsModal()" class="button button-secondary">Annuler</button>'
			+ '<button id="save-template-settings-btn" class="button button-primary">\uD83D\uDCBE Enregistrer</button>'
			+ '</div>'
			+ '</div>';

		// Populate server-data fields via DOM API (avoid innerHTML injection)
		var subtitle = modal.querySelector( '#pdfb-modal-subtitle' );
		if ( subtitle ) { subtitle.textContent = 'Configuration de "' + ( template.name || 'Template' ) + '"'; }
		var idInput = document.getElementById( 'settings-template-id' );
		if ( idInput ) { idInput.value = String( window.currentTemplateId || '' ); }
		var nameInput = document.getElementById( 'template-name' );
		if ( nameInput ) { nameInput.value = template.name || ''; }
		var descInput = document.getElementById( 'template-description' );
		if ( descInput ) { descInput.value = template.description || ''; }
		var catSelect = document.getElementById( 'template-category' );
		if ( catSelect ) { catSelect.value = template.category || 'autre'; }
		var defaultCb = document.getElementById( 'template-is-default' );
		if ( defaultCb ) { defaultCb.checked = !! template.is_default; }
		modal.style.display = 'flex';
		modal.onclick = function(event) {
			if (event.target === modal) {
				closeTemplateSettingsModal();
			}
		};

		populateSelect(
			document.getElementById('template-format'),
			modalFormats,
			typeof formatOptions !== 'undefined' ? formatOptions : {},
			tFormat,
			'Aucun format disponible',
			document.getElementById('template-format-warning')
		);
		populateSelect(
			document.getElementById('template-orientation'),
			modalOrientations,
			typeof orientationOptions !== 'undefined' ? orientationOptions : {},
			tOrientation,
			'Aucune orientation disponible',
			document.getElementById('template-orientation-warning')
		);
		populateSelect(
			document.getElementById('template-dpi'),
			modalDpis,
			typeof dpiOptions !== 'undefined' ? dpiOptions : {},
			tDpi,
			'Aucune résolution disponible',
			document.getElementById('template-dpi-warning')
		);

		var saveBtn = document.getElementById('save-template-settings-btn');
		if (saveBtn) {
			saveBtn.addEventListener('click', function() {
				saveTemplateSettings();
			});
		}
	}

	function saveTemplateSettings() {
		if (!window.currentTemplateId) {
			if (typeof window.showErrorNotification !== 'undefined') {
				window.showErrorNotification('Erreur: Aucun template sélectionné');
			}
			return;
		}

		var nameEl = document.getElementById('template-name');
		var descEl = document.getElementById('template-description');
		var catEl = document.getElementById('template-category');
		var isDefaultEl = document.getElementById('template-is-default');
		var formatEl = document.getElementById('template-format');
		var orientEl = document.getElementById('template-orientation');
		var dpiEl = document.getElementById('template-dpi');

		if (!nameEl || !descEl || !catEl || !formatEl || !orientEl || !dpiEl) {
			if (typeof window.showErrorNotification !== 'undefined') {
				window.showErrorNotification('Erreur: Formulaire incomplet');
			}
			return;
		}

		var formData = new FormData();
		formData.append('action', 'pdfib_save_template_settings');
		formData.append('template_id', window.currentTemplateId);
		formData.append('nonce', getNonce());
		formData.append('template_name', nameEl.value);
		formData.append('template_description', descEl.value);
		formData.append('template_category', catEl.value);
		formData.append('is_default', isDefaultEl && isDefaultEl.checked ? '1' : '0');
		formData.append('canvas_format', formatEl.value);
		formData.append('canvas_orientation', orientEl.value);
		formData.append('canvas_dpi', dpiEl.value);

		var saveBtn = document.getElementById('save-template-settings-btn');
		var origText = saveBtn ? saveBtn.innerHTML : null;
		if (saveBtn) {
			saveBtn.innerHTML = '⏳ Sauvegarde...';
			saveBtn.disabled = true;
		}

		fetch(getAjaxUrl(), {
			method: 'POST',
			body: formData
		})
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				if (data.success) {
					closeTemplateSettingsModal();
					localStorage.setItem('pdfBuilderTemplateSuccess', 'Paramètres sauvegardés avec succès');
					setTimeout(function() {
						window.location.reload();
					}, 1000);
					return;
				}
				var errMsg = data.data && data.data.message ? data.data.message : 'Erreur lors de la sauvegarde';
				if (typeof window.showErrorNotification !== 'undefined') {
					window.showErrorNotification(errMsg);
				}
			})
			.catch(function() {
				if (typeof window.showErrorNotification !== 'undefined') {
					window.showErrorNotification('Erreur de communication lors de la sauvegarde');
				}
			})
			.finally(function() {
				if (saveBtn && origText !== null) {
					saveBtn.innerHTML = origText;
					saveBtn.disabled = false;
				}
			});
	}

	document.addEventListener('DOMContentLoaded', function() {
		var cookies = document.cookie.split(';');
		var isDismissed = false;
		cookies.forEach(function(cookie) {
			var parts = cookie.trim().split('=');
			if (parts[0] === 'pdfib_template_limit_notice_dismissed' && parts[1] === 'true') {
				isDismissed = true;
			}
		});
		if (isDismissed) {
			var notice = document.getElementById('template-limit-notice');
			if (notice) {
				notice.style.display = 'none';
			}
		}

		document.querySelectorAll('.pdfb-filter-btn').forEach(function(btn) {
			btn.addEventListener('click', function() {
				document.querySelectorAll('.pdfb-filter-btn').forEach(function(b) {
					b.classList.remove('pdfb-active');
				});
				btn.classList.add('pdfb-active');
				filterTemplates(btn.getAttribute('data-filter'));
			});
		});

		document.querySelectorAll('.pdfb-template-preview-container').forEach(function(container) {
			var templateId = container.getAttribute('data-template-id');
			if (templateId) {
				loadTemplatePreview(templateId);
			}
		});

		var successMessage = localStorage.getItem('pdfBuilderTemplateSuccess');
		if (successMessage && typeof window.showSuccessNotification !== 'undefined') {
			window.showSuccessNotification(successMessage);
			localStorage.removeItem('pdfBuilderTemplateSuccess');
		}
	});

	window.confirmDeleteTemplate = window.confirmDeleteTemplate || function(templateId, templateName) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement le template "' + templateName + '" ?\n\nCette action ne peut pas être annulée.')) {
			return;
		}

		if (typeof jQuery === 'undefined') {
			alert('Erreur: jQuery n\'est pas chargé');
			return;
		}

		jQuery.ajax({
			url: getAjaxUrl(),
			type: 'POST',
			data: {
				action: 'pdfib_delete_template',
				template_id: templateId,
				nonce: getNonce()
			},
			success: function(response) {
				if (response.success) {
					location.reload();
					return;
				}
				alert('Erreur lors de la suppression: ' + (response.data || 'Erreur inconnue'));
			},
			error: function(xhr, status, error) {
				alert('Erreur AJAX - Status: ' + status + ', Error: ' + error + ', Response: ' + xhr.responseText);
			}
		});
	};

	window.showTemplateLimitNotice = showTemplateLimitNotice;
	window.dismissTemplateLimitNotice = dismissTemplateLimitNotice;
	window.filterTemplates = filterTemplates;
	window.duplicateTemplate = duplicateTemplate;
	window.loadTemplatePreview = loadTemplatePreview;
	window.showTemplateIconPreview = showTemplateIconPreview;
	window.handleDeleteClick = handleDeleteClick;
	window.toggleDefaultTemplate = toggleDefaultTemplate;
	window.openTemplateSettings = openTemplateSettings;
	window.closeTemplateSettingsModal = closeTemplateSettingsModal;
	window.showTemplateSettingsLoadError = showTemplateSettingsLoadError;
	window.loadTemplateSettings = loadTemplateSettings;
	window.displayTemplateSettings = displayTemplateSettings;
	window.saveTemplateSettings = saveTemplateSettings;
})();
