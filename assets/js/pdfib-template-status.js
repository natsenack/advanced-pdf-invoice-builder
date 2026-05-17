(function () {
	'use strict';

	var config = window.pdfibTemplateStatusConfig || {};

	function getAssignedMarkup(title) {
		return '<p class="pdfb-current-template">'
			+ (config.assignedLabel || 'Assigne :')
			+ ' '
			+ title
			+ '</p>';
	}

	function getEmptyMarkup() {
		return '<p class="pdfb-no-template">'
			+ (config.emptyLabel || 'Aucun template assigne')
			+ '</p>';
	}

	function updateCardPreview(select) {
		var article = select.closest('article');
		var preview = article ? article.querySelector('.pdfb-template-preview') : null;
		var selectedOption = select.options[select.selectedIndex] || null;
		var templateTitle = selectedOption ? selectedOption.text.trim() : '';

		if (!preview) {
			return;
		}

		if (select.value && templateTitle) {
			preview.innerHTML = getAssignedMarkup(templateTitle);
			return;
		}

		preview.innerHTML = getEmptyMarkup();
	}

	function updateAllPreviews() {
		document.querySelectorAll('.pdfb-template-select').forEach(function (select) {
			updateCardPreview(select);
		});
	}

	function openPremiumPage() {
		if (config.licenceUrl) {
			window.location.href = config.licenceUrl;
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		var resetButton = document.getElementById('pdfib-reset-templates-status');
		var premiumButton = document.getElementById('pdfib-open-licence-tab');

		updateAllPreviews();

		if (resetButton) {
			resetButton.addEventListener('click', function () {
				if (
					window.PDFBuilderTabsAPI
					&& typeof window.PDFBuilderTabsAPI.resetTemplatesStatus
						=== 'function'
				) {
					window.PDFBuilderTabsAPI.resetTemplatesStatus();
				}
			});
		}

		if (premiumButton) {
			premiumButton.addEventListener('click', openPremiumPage);
		}
	});

	document.addEventListener('change', function (event) {
		if (
			event.target
			&& event.target.classList
			&& event.target.classList.contains('pdfb-template-select')
		) {
			updateCardPreview(event.target);
		}
	});

	document.addEventListener('pdfBuilderSettingsSaved', updateAllPreviews);
})();
