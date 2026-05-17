/* global pdfBuilderLicense */
( function() {
	'use strict';

	// ── Expand / Collapse générique ─────────────────────────────────────
	function pdfbToggleExpand( btn, targetId ) {
		var target   = document.getElementById( targetId );
		var chevron  = btn.querySelector( '.pdfb-chevron' );
		var expanded = btn.getAttribute( 'aria-expanded' ) === 'true';
		if ( ! target ) { return; }

		if ( expanded ) {
			target.style.display = 'none';
			btn.setAttribute( 'aria-expanded', 'false' );
			if ( chevron ) { chevron.style.transform = ''; }
			var label = btn.querySelector( 'span:first-child' );
			if ( label && label.textContent.indexOf( 'Moins' ) !== -1 ) {
				label.textContent = 'Voir plus de fonctionnalités';
			}
		} else {
			target.style.display = '';
			btn.setAttribute( 'aria-expanded', 'true' );
			if ( chevron ) { chevron.style.transform = 'rotate(180deg)'; }
			var labelExpand = btn.querySelector( 'span:first-child' );
			if ( labelExpand && labelExpand.textContent.indexOf( 'Voir plus' ) !== -1 ) {
				labelExpand.textContent = 'Voir moins';
			}
		}
	}

	// Make pdfbToggleExpand globally accessible (called from inline HTML onclick)
	window.pdfbToggleExpand = pdfbToggleExpand;

	// ── Validation et activation de licence (EDD) ──────────────────────────
	( function() {
		var btn   = document.getElementById( 'activate-license-btn' );
		var input = document.getElementById( 'license_key_input' );
		if ( ! btn || ! input ) { return; }

		// Format attendu par EDD Software Licensing : 32 hex lowercase
		var EDD_REGEX = /^[a-f0-9]{32}$/i;

		// Zone de notification sous l'input
		var notice = document.createElement( 'p' );
		notice.id  = 'license-key-notice';
		notice.style.cssText = 'margin:6px 0 0; font-size:13px; display:none;';
		input.parentNode.parentNode.insertBefore( notice, input.parentNode.nextSibling );

		function showNotice( msg, type ) {
			notice.textContent = msg;
			notice.style.display    = 'block';
			notice.style.color      = type === 'error'   ? '#cc1818' :
									  type === 'success' ? '#1a7e2e' : '#888';
			notice.style.fontWeight = type === 'loading' ? 'normal' : '600';
		}

		function hideNotice() {
			notice.style.display = 'none';
		}

		input.addEventListener( 'input', function() {
			var val = this.value.trim();
			if ( ! val ) { hideNotice(); return; }
			if ( ! EDD_REGEX.test( val ) ) {
				showNotice( '⚠ Format invalide — une clé EDD comporte 32 caractères hexadécimaux (0-9, a-f).', 'error' );
				btn.disabled = true;
			} else {
				showNotice( '✓ Format valide.', 'success' );
				btn.disabled = false;
			}
		} );

		btn.addEventListener( 'click', function() {
			var key = input.value.trim();

			if ( ! key ) {
				showNotice( '⚠ Veuillez saisir votre clé de licence.', 'error' );
				input.focus();
				return;
			}

			if ( ! EDD_REGEX.test( key ) ) {
				showNotice( '⚠ Format invalide — une clé EDD comporte 32 caractères hexadécimaux (0-9, a-f).', 'error' );
				input.focus();
				return;
			}

			btn.disabled = true;
			btn.querySelector( '.pdfb-license-btn-text' ).textContent = 'Activation…';
			showNotice( '⏳ Vérification auprès du serveur de licences…', 'loading' );

			var formData = new FormData();
			formData.append( 'action',      'pdfib_activate_license' );
			formData.append( 'nonce',       window.pdfBuilderLicense.ajaxNonce );
			formData.append( 'license_key', key );

			fetch( window.pdfBuilderLicense.ajaxUrl, {
				method: 'POST',
				body:   formData,
			} )
			.then( function( r ) { return r.json(); } )
			.then( function( data ) {
				if ( data.success ) {
					showNotice( '✓ ' + data.data.message, 'success' );
					setTimeout( function() { window.location.reload(); }, 1500 );
				} else {
					showNotice( '✗ ' + ( data.data && data.data.message ? data.data.message : 'Erreur inconnue' ), 'error' );
					btn.disabled = false;
					btn.querySelector( '.pdfb-license-btn-text' ).textContent = window.pdfBuilderLicense.btnText;
				}
			} )
			.catch( function( err ) {
				showNotice( '✗ Erreur réseau : ' + err.message, 'error' );
				btn.disabled = false;
				btn.querySelector( '.pdfb-license-btn-text' ).textContent = window.pdfBuilderLicense.btnText;
			} );
		} );
	} )();

	// Fonctions JavaScript pour les modals de licence
	var LICENCE_HTTP_OK = 200;

	function showDeactivateModal() {
		if ( ! document.getElementById( 'deactivate-modal-overlay' ) ) {
			document.body.insertAdjacentHTML( 'beforeend', buildDeactivateModalHTML() );
		} else {
			document.getElementById( 'deactivate-modal-overlay' ).style.display = 'flex';
		}
	}

	function closeDeactivateModal() {
		var modal = document.getElementById( 'deactivate-modal-overlay' );
		if ( modal ) {
			modal.style.display = 'none';
		}
	}

	function insertAdminNotice( cssClass, message ) {
		var el = document.createElement( 'div' );
		el.className = 'notice ' + cssClass;
		el.style.cssText = 'margin: 20px 0; padding: 12px; border-left: 4px solid ' +
			( cssClass === 'notice-success' ? '#28a745' : '#dc3545' ) + ';';
		var p = document.createElement( 'p' );
		p.textContent = message;
		el.appendChild( p );
		document.body.insertBefore( el, document.body.firstChild );
	}

	function handleDeactivateXhrLoad( xhr ) {
		closeDeactivateModal();
		if ( xhr.status !== LICENCE_HTTP_OK ) {
			insertAdminNotice( 'notice-error', 'Erreur serveur (statut : ' + xhr.status + ')' );
			return;
		}
		var response = {};
		try { response = JSON.parse( xhr.responseText ); } catch ( e ) { console.error( 'Réponse AJAX invalide:', xhr.responseText ); }
		if ( response.success ) {
			insertAdminNotice( 'notice-success', 'Licence désactivée avec succès. Rafraîchissement…' );
			setTimeout( function() { window.location.reload(); }, 1500 );
		} else {
			insertAdminNotice( 'notice-error', response.message || 'Erreur lors de la désactivation' );
		}
	}

	function confirmDeactivateLicense() {
		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', window.pdfBuilderLicense.ajaxUrl, true );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		var data = 'action=pdfib_deactivate_license&nonce=' + encodeURIComponent( window.pdfBuilderLicense.deactivateNonce );
		xhr.onload  = function() { handleDeactivateXhrLoad( xhr ); };
		xhr.onerror = function() {
			closeDeactivateModal();
			insertAdminNotice( 'notice-error', 'Erreur de connexion' );
		};
		xhr.send( data );
	}

	function buildDeactivateModalHTML() {
		return `
			<div id="deactivate-modal-overlay" class="pdfb-canvas-modal-overlay" style="display: flex; z-index: 10002;">
				<div class="pdfb-canvas-modal-container" style="max-width: 450px;">
					<div class="pdfb-canvas-modal-header">
						<h3>⚠️ Confirmer la désactivation</h3>
						<button type="button" class="pdfb-canvas-modal-close" onclick="closeDeactivateModal()">&times;</button>
					</div>
					<div class="pdfb-canvas-modal-body" style="text-align: center; padding: 30px;">
						<div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
						<h4 style="margin-bottom: 15px; color: #23282d;">Êtes-vous sûr de vouloir désactiver la licence ?</h4>
						<p style="margin-bottom: 20px; color: #666; line-height: 1.5;">Cette action va :</p>
						<ul style="text-align: left; color: #666; margin: 0 0 25px 0; padding-left: 20px;">
							<li>Supprimer votre clé de licence</li>
							<li>Repasser en mode gratuit</li>
							<li>Perdre l'accès aux fonctionnalités premium</li>
						</ul>
						<div style="display: flex; gap: 10px; justify-content: center;">
							<button type="button" class="button button-secondary" onclick="closeDeactivateModal()" style="padding: 10px 20px;">Annuler</button>
							<button type="button" class="button button-danger" onclick="confirmDeactivateLicense()" style="padding: 10px 20px; background: #dc3545; border-color: #dc3545;">Désactiver</button>
						</div>
					</div>
				</div>
			</div>
		`;
	}

	// Make modal functions globally accessible (called from inline HTML onclick)
	window.showDeactivateModal      = showDeactivateModal;
	window.closeDeactivateModal     = closeDeactivateModal;
	window.confirmDeactivateLicense = confirmDeactivateLicense;
} )();
