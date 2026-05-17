/**
 * PDF Builder Settings Upgrade Modal JavaScript
 */
(function ($) {
	'use strict';

	var upgradeModalDefaults = null;

	function getUpgradeModalDefaults() {
		if (upgradeModalDefaults) {
			return upgradeModalDefaults;
		}

		var titleElement = document.getElementById( 'pdfib-upgrade-modal-title' );
		var messageElement = document.getElementById( 'pdfib-upgrade-modal-message' );
		var ctaElement = document.getElementById( 'pdfib-upgrade-modal-cta' );

		if ( ! titleElement || ! messageElement || ! ctaElement ) {
			return null;
		}

		upgradeModalDefaults = {
			title: ( titleElement.textContent || '' ).trim(),
			message: ( messageElement.textContent || '' ).trim(),
			cta: ( ctaElement.textContent || '' ).trim(),
		};

		return upgradeModalDefaults;
	}

	function getUpgradeTargetUrl() {
		return 'https://hub.threeaxe.fr/nos-produits/pdf-builder-pro-2';
	}

	function setModalVisibility( isVisible ) {
		var $overlay = $( '#pdfib-upgrade-modal-overlay' );
		if ( ! $overlay.length ) {
			return;
		}

		$overlay
			.toggleClass( 'is-open', isVisible )
			.attr( 'aria-hidden', isVisible ? 'false' : 'true' );
	}

	window.hideUpgradeModal = function () {
		setModalVisibility( false );
	};

	window.showUpgradeModal = function () {
		var modal = getUpgradeModalDefaults();

		if ( ! modal ) {
			return;
		}

		var targetUrl = getUpgradeTargetUrl();

		$( '#pdfib-upgrade-modal-title' ).text( modal.title );
		$( '#pdfib-upgrade-modal-message' ).text( modal.message );
		$( '#pdfib-upgrade-modal-cta' ).text( modal.cta );

		$( '#pdfib-upgrade-modal-cta' )
			.off( 'click.pdfibUpgradeModalCta' )
			.on( 'click.pdfibUpgradeModalCta', function () {
				if ( /^https?:\/\//i.test( targetUrl ) ) {
					window.open( targetUrl, '_blank', 'noopener,noreferrer' );
					return;
				}

				window.location.href = targetUrl;
			} );

		setModalVisibility( true );
	};

	$( document )
		.off( 'click.pdfibUpgradeModalTrigger', '[data-pdfib-upgrade-modal]' )
		.on( 'click.pdfibUpgradeModalTrigger', '[data-pdfib-upgrade-modal]', function ( event ) {
			event.preventDefault();
			window.showUpgradeModal( $( this ).attr( 'data-pdfib-upgrade-modal' ) || 'canvas_settings' );
		} )
		.off( 'click.pdfibUpgradeModalOverlay', '#pdfib-upgrade-modal-overlay' )
		.on( 'click.pdfibUpgradeModalOverlay', '#pdfib-upgrade-modal-overlay', function ( event ) {
			if ( event.target === this ) {
				window.hideUpgradeModal();
			}
		} )
		.off( 'click.pdfibUpgradeModalClose', '#pdfib-upgrade-modal-close, #pdfib-upgrade-modal-cancel' )
		.on( 'click.pdfibUpgradeModalClose', '#pdfib-upgrade-modal-close, #pdfib-upgrade-modal-cancel', function ( event ) {
			event.preventDefault();
			window.hideUpgradeModal();
		} )
		.off( 'keydown.pdfibUpgradeModal' )
		.on( 'keydown.pdfibUpgradeModal', function ( event ) {
			if ( 'Escape' === event.key ) {
				window.hideUpgradeModal();
			}
		} );

	setModalVisibility( false );
})(jQuery);