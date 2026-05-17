/**
 * PDF Builder Pro - Validation temps réel des champs de l'onglet Général
 * Validation client-side : feedback inline immédiat (blur + input)
 * La validation serveur dans PdfBuilderUnifiedAjaxHandler::saveGeneralSettings() constitue le second rempart.
 */
(function () {
	'use strict';

	// ── Règles de validation par champ ────────────────────────────────────

	/** Téléphone : international, 7-20 caractères utiles */
	var PHONE_RE = /^\+?[\d\s\-().]{7,20}$/;

	/** SIRET : exactement 14 chiffres (espaces acceptés à la saisie) */
	var SIRET_RE = /^\d{14}$/;

	/**
	 * TVA intracommunautaire : map regex par préfixe pays EU.
	 * Format général de référence : 2 lettres ISO + 4-13 alphanum.
	 */
	var VAT_PATTERNS = {
		AT: /^ATU\d{8}$/,
		BE: /^BE0?\d{9}$/,
		BG: /^BG\d{9,10}$/,
		CH: /^CHE-?\d{3}\.?\d{3}\.?\d{3}(MWST|TVA|IVA)?$/i,
		CY: /^CY\d{8}[A-Z]$/,
		CZ: /^CZ\d{8,10}$/,
		DE: /^DE\d{9}$/,
		DK: /^DK\d{8}$/,
		EE: /^EE\d{9}$/,
		EL: /^EL\d{9}$/,
		ES: /^ES[0-9A-Z]\d{7}[0-9A-Z]$/,
		FI: /^FI\d{8}$/,
		FR: /^FR[0-9A-Z]{2}\d{9}$/,
		GB: /^GB(\d{9}|\d{12}|GD\d{3}|HA\d{3})$/,
		HR: /^HR\d{11}$/,
		HU: /^HU\d{8}$/,
		IE: /^IE\d[0-9A-Z+*]\d{5}[A-Z]{1,2}$/,
		IT: /^IT\d{11}$/,
		LT: /^LT(\d{9}|\d{12})$/,
		LU: /^LU\d{8}$/,
		LV: /^LV\d{11}$/,
		MT: /^MT\d{8}$/,
		NL: /^NL\d{9}B\d{2}$/,
		PL: /^PL\d{10}$/,
		PT: /^PT\d{9}$/,
		RO: /^RO\d{2,10}$/,
		SE: /^SE\d{12}$/,
		SI: /^SI\d{8}$/,
		SK: /^SK\d{10}$/,
	};

	/** Fallback générique pour les pays non listés */
	var VAT_GENERIC_RE = /^[A-Z]{2}[0-9A-Z]{4,13}$/;

	/**
	 * RCS : « Ville [A|B] 123456789 »
	 * Ville = lettres + espaces/tirets, registre = A ou B, numéro = 9 chiffres
	 */
	var RCS_RE = /^[A-ZÀ-Ÿa-zà-ÿ][A-ZÀ-Ÿa-zà-ÿ\s\-]+ [AB] \d{9}$/;

	/** Capital social : nombre décimal (virgule ou point), optionnellement suivi de € */
	var CAPITAL_RE = /^\d[\d\s]*([,.]?\d{1,2})?\s*€?$/;

	// ── Messages d'erreur ─────────────────────────────────────────────────
	var MESSAGES = {
		company_phone_manual: 'Numéro invalide. Exemple : +33 1 23 45 67 89',
		company_siret: 'SIRET invalide : 14 chiffres requis.',
		company_vat: 'TVA intracommunautaire invalide. Exemple : FR12345678901',
		company_rcs: 'Format RCS invalide. Exemple : Lyon B 123456789',
		company_capital: 'Capital invalide. Exemple : 10 000 € ou 10000.00',
	};

	// ── Fonctions utilitaires ─────────────────────────────────────────────

	function stripSpaces(str) {
		return str.replace(/\s/g, '');
	}

	/**
	 * Valide la valeur d'un champ.
	 * Retourne null si valide (ou vide), sinon le message d'erreur.
	 * Tous les champs sont optionnels — seul un format incorrect est rejeté.
	 */
	function validate(fieldName, rawValue) {
		var v = rawValue.trim();

		// Tous les champs sont optionnels : vide = toujours valide
		if (v === '') {
			return null;
		}

		switch (fieldName) {
			case 'company_phone_manual':
				return PHONE_RE.test(v) ? null : MESSAGES.company_phone_manual;

			case 'company_siret':
				return SIRET_RE.test(stripSpaces(v)) ? null : MESSAGES.company_siret;

			case 'company_vat': {
				var upper = v.toUpperCase().replace(/\s/g, '');
				var prefix = upper.slice(0, 2);
				var pattern = VAT_PATTERNS[prefix] || VAT_GENERIC_RE;
				return pattern.test(upper) ? null : MESSAGES.company_vat;
			}

			case 'company_rcs':
				return RCS_RE.test(v) ? null : MESSAGES.company_rcs;

			case 'company_capital':
				return CAPITAL_RE.test(v) ? null : MESSAGES.company_capital;

			default:
				return null;
		}
	}

	// ── Affichage / effacement des erreurs ────────────────────────────────

	function showError(input, message) {
		input.classList.add('pdfb-input-invalid');
		input.setAttribute('aria-invalid', 'true');
		var errorEl = document.getElementById('error-' + input.name);
		if (errorEl) {
			errorEl.textContent = message;
		}
	}

	function clearError(input) {
		input.classList.remove('pdfb-input-invalid');
		input.removeAttribute('aria-invalid');
		var errorEl = document.getElementById('error-' + input.name);
		if (errorEl) {
			errorEl.textContent = '';
		}
	}

	function validateInput(input) {
		var error = validate(input.name, input.value);
		if (error) {
			showError(input, error);
			return false;
		}
		clearError(input);
		return true;
	}

	// ── API publique (utilisée par floating-save-button.js) ───────────────

	window.pdfibGeneralValidation = {
		/**
		 * Valide tous les champs de l'onglet général.
		 * Retourne true si tous les champs sont valides.
		 */
		validateAll: function () {
			var fields = ['company_phone_manual', 'company_siret', 'company_vat', 'company_rcs', 'company_capital'];
			var allValid = true;
			fields.forEach(function (name) {
				var input = document.getElementById(name);
				if (input) {
					if (!validateInput(input)) {
						allValid = false;
					}
				}
			});
			return allValid;
		},

		/**
		 * Affiche les erreurs retournées par le serveur.
		 * @param {Object} fields — ex: { company_siret: "SIRET invalide" }
		 */
		showServerErrors: function (fields) {
			Object.keys(fields).forEach(function (name) {
				var input = document.getElementById(name);
				if (input) {
					showError(input, fields[name]);
				}
			});

			// Scroll vers le premier champ en erreur
			var first = document.querySelector('.pdfb-input-invalid');
			if (first) {
				first.scrollIntoView({ behavior: 'smooth', block: 'center' });
				first.focus();
			}
		},
	};

	// ── Initialisation des listeners ──────────────────────────────────────
	// Le script est chargé en footer (in_footer:true) : DOMContentLoaded a déjà
	// été déclenché. On init directement ; si le DOM n'est pas encore prêt
	// (cas rare), on se rabat sur l'événement.

	function initListeners() {
		var fieldNames = ['company_phone_manual', 'company_siret', 'company_vat', 'company_rcs', 'company_capital'];

		fieldNames.forEach(function (name) {
			var input = document.getElementById(name);
			if (!input) return;

			// Validation au blur (quand l'utilisateur quitte le champ)
			input.addEventListener('blur', function () {
				validateInput(input);
			});

			// Effacement de l'erreur dès que l'utilisateur retape (feedback positif immédiat)
			input.addEventListener('input', function () {
				if (input.classList.contains('pdfb-input-invalid')) {
					var error = validate(input.name, input.value);
					if (!error) {
						clearError(input);
					}
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initListeners);
	} else {
		initListeners();
	}
}());
