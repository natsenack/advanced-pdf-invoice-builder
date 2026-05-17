/**
 * Canvas Modal Settings JS System
 * Extracted from plugin/templates/admin/settings-parts/settings-contenu.php
 * 
 * Dependencies:
 * - jQuery (global)
 * - pdfBuilderAjax (localized from settings-contenu.php with ajaxurl, nonce)
 */

(function() {
	'use strict';

	// ==================== MODAL CONFIGURATION ====================

	window.pdfibModalConfig = {
		'affichage':    'canvas-affichage-modal-overlay',
		'navigation':   'canvas-navigation-modal-overlay',
		'comportement': 'canvas-comportement-modal-overlay'
	};

	// Mapping des noms d'input vers les clés camelCase pour window.pdfBuilderCanvasSettings
	window.pdfibInputToSettingMap = {
		'pdfib_canvas_shadow_enabled':    'shadowEnabled',
		'pdfib_canvas_grid_enabled':      'gridEnabled',
		'pdfib_canvas_guides_enabled':    'guidesEnabled',
		'pdfib_canvas_snap_to_grid':      'snapToGrid',
		'pdfib_canvas_drag_enabled':      'dragEnabled',
		'pdfib_canvas_resize_enabled':    'resizeEnabled',
		'pdfib_canvas_rotate_enabled':    'rotateEnabled',
		'pdfib_canvas_multi_select':      'multiSelect',
		'pdfib_canvas_keyboard_shortcuts':'keyboardShortcuts',
		'pdfib_canvas_export_transparent':'exportTransparent',
		'pdfib_canvas_border_color':      'borderColor',
		'pdfib_canvas_border_width':      'borderWidth',
		'pdfib_canvas_container_bg_color':'containerBackgroundColor',
		'pdfib_canvas_show_margins':      'showMargins',
		'pdfib_canvas_margin_top':        'marginTop',
		'pdfib_canvas_margin_right':      'marginRight',
		'pdfib_canvas_margin_bottom':     'marginBottom',
		'pdfib_canvas_margin_left':       'marginLeft'
	};

	// ==================== HELPER FUNCTIONS ====================

	/**
	 * Collecte tous les inputs non-disabled d'une modal.
	 * Retourne { cleanName: value } pour tous les champs (checkbox, number, select, color, text).
	 */
	function collectModalInputs(modal) {
		var data = {};
		var seen = {};
		modal.querySelectorAll('[name]:not([disabled])').forEach(function(input) {
			var name = input.name;
			if (!name) { return; }
			var cleanName = name.endsWith('[]') ? name.slice(0, -2) : name;
			if (seen[cleanName]) { return; }

			if (input.type === 'checkbox') {
				seen[cleanName] = true;
				var group = modal.querySelectorAll('[name="' + name + '"]:not([disabled])');
				if (group.length > 1 || name.endsWith('[]')) {
					var vals = [];
					group.forEach(function(cb) { if (cb.checked) { vals.push(cb.value); } });
					data[cleanName] = vals.join(',');
				} else {
					data[cleanName] = input.checked ? '1' : '0';
				}
			} else if (input.type !== 'hidden' && input.type !== 'radio') {
				seen[cleanName] = true;
				data[cleanName] = input.value;
			}
		});
		return data;
	}

	/**
	 * Sync des hidden fields du formulaire → inputs de la modal (à l'ouverture).
	 */
	function syncModalInputsWithHiddenFields(modal) {
		if (!modal) { return; }
		var seen = {};
		modal.querySelectorAll('[name]').forEach(function(input) {
			var name = input.name;
			if (!name) { return; }
			var cleanName = name.endsWith('[]') ? name.slice(0, -2) : name;

			var hiddenField = document.querySelector('input[name="pdfib_settings[' + cleanName + ']"]');
			if (!hiddenField) { return; }

			if (input.type === 'checkbox') {
				var vals = hiddenField.value ? hiddenField.value.split(',') : [];
				input.checked = vals.indexOf(input.value) !== -1;
			} else if (!seen[cleanName] && input.type !== 'hidden' && input.type !== 'radio') {
				seen[cleanName] = true;
				input.value = hiddenField.value;
			}
		});
	}

	/**
	 * Sync des inputs de la modal → hidden fields du formulaire, puis ferme la modal.
	 */
	function saveModalToggles(category) {
		var modalId = window.pdfibModalConfig[category];
		if (!modalId) { return; }
		var modal = document.getElementById(modalId);
		if (!modal) { return; }

		var data = collectModalInputs(modal);
		Object.keys(data).forEach(function(cleanName) {
			var hiddenField = document.querySelector('input[name="pdfib_settings[' + cleanName + ']"]');
			if (hiddenField) { hiddenField.value = data[cleanName]; }
		});

		window.pdfibCloseModal(modal);
	}

	/**
	 * Show notification via unified system
	 */
	function pdfibShowNotification(message, type) {
		if (typeof pdfBuilderAjax === 'undefined') { return; }
		jQuery.ajax({
			url: pdfBuilderAjax.ajaxurl,
			type: 'POST',
			data: { action: 'pdfib_show_notification', message: message, type: type, nonce: pdfBuilderAjax.nonce }
		});
	}

	// ==================== PUBLIC API ====================

	window.pdfibOpenModal = function(category) {
		var modalId = window.pdfibModalConfig[category];
		if (!modalId) { return; }
		var modal = document.getElementById(modalId);
		if (modal) {
			syncModalInputsWithHiddenFields(modal);
			modal.style.display = 'flex';
			document.body.style.overflow = 'hidden';
		}
	};

	window.pdfibCloseModal = function(modalElement) {
		if (modalElement) {
			modalElement.style.display = 'none';
			document.body.style.overflow = '';
		}
	};

	window.pdfibApplyModalSettings = function(category) {
		var modalId = window.pdfibModalConfig[category];
		if (!modalId) { return; }
		var modal = document.getElementById(modalId);
		if (!modal) { return; }

		var data = collectModalInputs(modal);
		data.action   = 'pdfib_save_canvas_settings';
		data.nonce    = pdfBuilderAjax && pdfBuilderAjax.nonce ? pdfBuilderAjax.nonce : '';
		data.category = category;

		// Sync hidden fields + fermer la modal immédiatement
		saveModalToggles(category);

		// Mise à jour live du canvas React
		if (window.pdfBuilderCanvasSettings) {
			Object.keys(data).forEach(function(inputName) {
				if (inputName !== 'action' && inputName !== 'nonce' && inputName !== 'category' &&
					inputName.indexOf('pdfib_canvas_') === 0) {
					var settingKey = inputName.replace('pdfib_canvas_', '');
					window.pdfBuilderCanvasSettings[settingKey] = data[inputName];
				}
			});
			window.dispatchEvent(new Event('pdfBuilderCanvasSettingsUpdated'));
		}

		// Sauvegarde AJAX (action dans l'URL pour garantir $_REQUEST['action'])
		jQuery.ajax({
			url:         pdfBuilderAjax.ajaxurl + '?action=pdfib_save_canvas_settings',
			method:      'POST',
			data:        jQuery.param(data),
			contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
			success: function(resp) {
				if (resp && resp.success) {
					pdfibShowNotification('Param\u00e8tres canvas sauvegard\u00e9s', 'success');
				}
			}
		});
	};

	// ==================== INITIALIZATION ====================

	window.pdfibInitializeModals = function() {

		// Masquer toutes les modals au chargement
		document.querySelectorAll('.pdfb-canvas-modal-overlay').forEach(function(modal) {
			modal.style.display = 'none';
		});

		document.addEventListener('click', function(e) {

			// Fix toggle : span.pdfb-ts couvre l'input hidden → relayer le clic
			var ts = e.target.closest('.pdfb-canvas-modal-overlay .pdfb-toggle-switch .pdfb-ts');
			if (ts) {
				var toggleInput = ts.parentElement.querySelector('input[type="checkbox"]:not([disabled])');
				if (toggleInput) {
					toggleInput.checked = !toggleInput.checked;
					toggleInput.dispatchEvent(new Event('change', { bubbles: true }));
				}
				return;
			}

			// Ouvrir modal
			var configBtn = e.target.closest('.pdfb-canvas-configure-btn');
			if (configBtn) {
				e.preventDefault();
				var card = configBtn.closest('.pdfb-canvas-card');
				if (card) {
					var cat = card.getAttribute('data-category');
					if (cat && window.pdfibModalConfig[cat]) {
						window.pdfibOpenModal(cat);
					}
				}
				return;
			}

			// Fermer modal
			var closeBtn = e.target.closest('.pdfb-canvas-modal-close, .pdfb-canvas-modal-cancel');
			if (closeBtn) {
				e.preventDefault();
				var m = closeBtn.closest('.pdfb-canvas-modal-overlay');
				if (m) { window.pdfibCloseModal(m); }
				return;
			}

			// Appliquer paramètres
			var applyBtn = e.target.closest('.pdfb-canvas-modal-apply');
			if (applyBtn) {
				e.preventDefault();
				var applyCat = applyBtn.getAttribute('data-category');
				if (applyCat) { window.pdfibApplyModalSettings(applyCat); }
				return;
			}

			// Clic sur l'overlay (fond)
			if (e.target.classList.contains('pdfb-canvas-modal-overlay')) {
				window.pdfibCloseModal(e.target);
			}
		});

		// Sync temps réel input → hidden field
		document.addEventListener('change', function(e) {
			var input = e.target;
			if (!input.name || !input.closest('.pdfb-canvas-modal-overlay')) { return; }
			var cleanName = input.name.endsWith('[]') ? input.name.slice(0, -2) : input.name;
			var hiddenField = document.querySelector('input[name="pdfib_settings[' + cleanName + ']"]');
			if (!hiddenField) { return; }

			if (input.type === 'checkbox') {
				if (input.name.endsWith('[]')) {
					var group = input.closest('.pdfb-canvas-modal-overlay').querySelectorAll('input[name="' + input.name + '"]');
					var checked = [];
					group.forEach(function(cb) { if (cb.checked) { checked.push(cb.value); } });
					hiddenField.value = checked.join(',');
				} else {
					hiddenField.value = input.checked ? '1' : '0';
				}
			} else {
				hiddenField.value = input.value;
			}
		});

		// Sync hex display (readonly text) lié au sélecteur de couleur via data-hex-display-target
		document.addEventListener('input', function(e) {
			var input = e.target;
			if (input.type !== 'color' || !input.closest('.pdfb-canvas-modal-overlay')) { return; }
			var targetId = input.getAttribute('data-hex-display-target');
			if (!targetId) { return; }
			var displayEl = document.getElementById(targetId);
			if (displayEl) { displayEl.value = input.value; }
		});

		// Touche Échap
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				document.querySelectorAll('.pdfb-canvas-modal-overlay[style*="display: flex"]').forEach(function(modal) {
					window.pdfibCloseModal(modal);
				});
			}
		});

		// Empêcher submit si modal ouverte
		document.addEventListener('submit', function(e) {
			if (!e.target || e.target.id !== 'pdf-builder-settings-form') { return; }
			if (document.querySelector('.pdfb-canvas-modal-overlay[style*="display: flex"]')) {
				e.preventDefault();
				pdfibShowNotification('Fermez la modal canvas avant de sauvegarder les param\u00e8tres globaux.', 'warning');
			}
		});
	};

	// Auto-init
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', window.pdfibInitializeModals);
	} else {
		window.pdfibInitializeModals();
	}

})();