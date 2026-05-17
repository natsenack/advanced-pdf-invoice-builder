/**
 * PDF Builder Pro - Security Settings JavaScript
 * Handles GDPR user actions and security status indicators on the settings page.
 * Conformité WP.org : extrait depuis settings-securite.php pour éviter les balises <script> inline.
 */
(function ($) {
    'use strict';

    // ── Indicateurs de statut ─────────────────────────────────────────────────
    function updateSecurityStatusIndicators() {
        var loggingCbEl = document.getElementById('enable_logging');
        var securityEl = document.getElementById('security-status-indicator');
        if (loggingCbEl && securityEl) {
            var loggingEnabled = loggingCbEl.checked;
            securityEl.textContent = loggingEnabled ? 'ACTIF' : 'INACTIF';
            securityEl.style.backgroundColor = loggingEnabled ? '#28a745' : '#dc3545';
        }
        var gdprCbEl = document.getElementById('gdpr_enabled');
        var rgpdEl = document.getElementById('rgpd-status-indicator');
        if (gdprCbEl && rgpdEl) {
            var gdprEnabled = gdprCbEl.checked;
            rgpdEl.textContent = gdprEnabled ? 'ACTIF' : 'INACTIF';
            rgpdEl.style.backgroundColor = gdprEnabled ? '#28a745' : '#dc3545';
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function gdprNonce() {
        return document.getElementById('export_user_data_nonce')?.value || '';
    }

    function escapeHtml( str ) {
        if ( ! str ) { return ''; }
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' )
            .replace( /"/g, '&quot;' )
            .replace( /'/g, '&#039;' );
    }

    function showResult(html, isError) {
        var $el = $('#gdpr-user-actions-result');
        var bg = isError ? '#f8d7da' : '#d4edda';
        var col = isError ? '#721c24' : '#155724';
        var $div = $( '<div>' ).css( { padding: '12px', background: bg, color: col, borderRadius: '6px', border: '1px solid ' + ( isError ? '#f5c6cb' : '#c3e6cb' ) } );
        $div.html( html );
        $el.empty().append( $div ).show();
    }

    function setLoading($btn, loading) {
        if (loading) {
            $btn.prop('disabled', true).data('orig', $btn.html()).html('⏳ Chargement…');
        } else {
            $btn.prop('disabled', false).html($btn.data('orig'));
        }
    }

    function ajaxGdpr(action, extra, onSuccess, onError) {
        $.post(ajaxurl, $.extend({ action: action, nonce: gdprNonce() }, extra), function (res) {
            if (res.success) { onSuccess(res.data); }
            else { onError(res.data?.message || 'Erreur'); }
        }).fail(function () { onError('Erreur de connexion'); });
    }

    // ── 📥 Exporter mes données ───────────────────────────────────────────────
    $('#export-my-data').on('click', function () {
        var $btn = $(this);
        var fmt = $('#export-format').val() || 'html';
        setLoading($btn, true);

        ajaxGdpr('pdf_builder_export_gdpr_data', { format: fmt }, function (data) {
            setLoading($btn, false);

            if (fmt === 'json') {
                var blob = new Blob([JSON.stringify(data.content, null, 2)], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url; a.download = 'mes-donnees-rgpd.json'; a.click();
                URL.revokeObjectURL(url);
                showResult('✅ Export JSON téléchargé.', false);
                return;
            }

            // Format HTML → nouvel onglet avec page complète + bouton télécharger
            // htmlContent est du HTML généré côté serveur (PHP plugin, admin uniquement).
            // Pour éviter l'injection via document.write(), on écrit d'abord la structure
            // statique, puis on injecte le contenu dynamique via DOM après document.close().
            var htmlContent = data.content;
            var filename = 'mes-donnees-rgpd-' + new Date().toISOString().slice(0, 10) + '.html';
            var generatedDate = new Date().toLocaleString('fr-FR');

            // Page statique avec un conteneur vide pour le contenu dynamique.
            var staticPage = '<!DOCTYPE html><html lang="fr"><head>'
                + '<meta charset="UTF-8">'
                + '<meta name="viewport" content="width=device-width,initial-scale=1">'
                + '<title>Mes données personnelles — PDF Builder Pro</title>'
                + '<style>'
                + 'body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f4f6f9;color:#333}'
                + '.header{background:#155724;color:#fff;padding:24px 40px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 8px rgba(0,0,0,.2)}'
                + '.header h1{margin:0;font-size:22px;font-weight:600;display:flex;align-items:center;gap:10px}'
                + '.header small{opacity:.8;font-size:13px;margin-top:4px;display:block}'
                + '.dl-btn{background:#fff;color:#155724;border:none;padding:10px 22px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;text-decoration:none;transition:background .2s}'
                + '.dl-btn:hover{background:#e8f5e8}'
                + '.content{max-width:860px;margin:40px auto;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.08);padding:36px;}'
                + '.badge{display:inline-block;background:#d4edda;color:#155724;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:18px}'
                + '.footer{text-align:center;color:#999;font-size:12px;margin:32px 0 20px}'
                + '</style>'
                + '</head><body>'
                + '<div class="header">'
                + '  <div><h1>📋 Mes données personnelles<br><small>PDF Builder Pro — Export RGPD</small></h1></div>'
                + '  <a class="dl-btn" id="dlBtn" href="#" download="gdpr-export.html">📥 Télécharger cette page</a>'
                + '</div>'
                + '<div class="content">'
                + '  <span class="badge" id="gdpr-date"></span>'
                + '  <div id="gdpr-dynamic-content"></div>'
                + '</div>'
                + '<div class="footer">Document généré par PDF Builder Pro · Conforme RGPD</div>'
                + '</body></html>';

            var tab = window.open('', '_blank');
            if (tab) {
                tab.document.open();
                tab.document.write(staticPage);
                tab.document.close();
                // Injection du contenu dynamique via DOM (pas de document.write avec données serveur).
                var dateEl = tab.document.getElementById('gdpr-date');
                if (dateEl) { dateEl.textContent = '✅ Généré le ' + generatedDate; }
                var dlBtn = tab.document.getElementById('dlBtn');
                if (dlBtn) { dlBtn.setAttribute('download', filename); }
                var contentEl = tab.document.getElementById('gdpr-dynamic-content');
                if (contentEl) {
                    // htmlContent provient exclusivement du plugin PHP (admin).
                    contentEl.innerHTML = htmlContent; // phpcs:ignore -- HTML généré par le serveur
                    // Bouton de téléchargement après injection du contenu.
                    dlBtn && dlBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var src = tab.document.documentElement.outerHTML;
                        var blob = new Blob([src], { type: 'text/html;charset=utf-8' });
                        var url = URL.createObjectURL(blob);
                        var a = tab.document.createElement('a');
                        a.href = url; a.download = filename; a.click();
                        setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
                    });
                }
            } else {
                showResult('⚠️ Le navigateur a bloqué l\'ouverture de l\'onglet. Veuillez autoriser les pop-ups.', true);
            }
        }, function (msg) {
            setLoading($btn, false);
            showResult('\u274C ' + escapeHtml(msg), true);
        });
    });

    // ── 🗑️ Supprimer mes données ─────────────────────────────────────────────
    $('#delete-my-data').on('click', function () {
        if (!confirm('⚠️ Êtes-vous sûr de vouloir supprimer vos données personnelles stockées par le plugin ?')) return;
        var $btn = $(this);
        setLoading($btn, true);

        ajaxGdpr('pdf_builder_delete_gdpr_data', {}, function (data) {
            setLoading($btn, false);
            showResult('\u2705 ' + escapeHtml(data.message), false);
        }, function (msg) {
            setLoading($btn, false);
            showResult('\u274C ' + escapeHtml(msg), true);
        });
    });

    // ── 👁️ Voir mes consentements ────────────────────────────────────────────
    $('#view-consent-status').on('click', function () {
        var $btn = $(this);
        setLoading($btn, true);

        ajaxGdpr('pdf_builder_get_consent_status', {}, function (data) {
            setLoading($btn, false);
            var rows = data.consents.map(function (c) {
                var icon = (c.value === true || c.value === 1) ? '\u2705' : (c.value === false ? '\u274C' : '');
                var val = (typeof c.value === 'boolean') ? (c.value ? 'Oui' : 'Non') : c.value;
                return '<tr><td style="padding:4px 10px;font-weight:600">' + escapeHtml( c.label ) + '</td><td style="padding:4px 10px">' + escapeHtml( icon ) + ' ' + escapeHtml( String( val ) ) + '</td></tr>';
            }).join('');
            showResult('<strong>\uD83D\uDC41\uFE0F \u00C9tat des consentements RGPD</strong><br><table style="margin-top:8px;width:100%">' + rows + '</table>', false);
        }, function (msg) {
            setLoading($btn, false);
            showResult('\u274C ' + escapeHtml(msg), true);
        });
    });

    // ── 🔄 Actualiser les logs ───────────────────────────────────────────────
    $('#refresh-audit-log').on('click', function () {
        var $btn = $(this);
        setLoading($btn, true);

        ajaxGdpr('pdf_builder_get_audit_log', { limit: 50 }, function (data) {
            setLoading($btn, false);
            var $container = $('#audit-log-container');
            var $content = $('#audit-log-content');
            $container.show();

            if (!data.logs || data.logs.length === 0) {
                $content.html('<p style="color:#6c757d;text-align:center;margin:10px 0">Aucune entrée de log disponible.</p>');
                return;
            }

            var rows = data.logs.map(function (e) {
                return '<tr style="border-bottom:1px solid #f0f0f0">'
                    + '<td style="padding:4px 8px;font-size:11px;color:#666;white-space:nowrap">' + escapeHtml( e.date || '' ) + '</td>'
                    + '<td style="padding:4px 8px;font-weight:600">' + escapeHtml( e.user || '' ) + '</td>'
                    + '<td style="padding:4px 8px"><code style="background:#e9ecef;padding:2px 6px;border-radius:3px;font-size:11px">' + escapeHtml( e.action || '' ) + '</code></td>'
                    + '<td style="padding:4px 8px;font-size:12px;color:#495057">' + escapeHtml( e.details || '' ) + '</td>'
                    + '</tr>';
            }).join('');

            $content.html(
                '<table style="width:100%;border-collapse:collapse">'
                + '<thead><tr style="background:#f8f9fa">'
                + '<th style="padding:6px 8px;text-align:left;font-size:12px">Date</th>'
                + '<th style="padding:6px 8px;text-align:left;font-size:12px">Utilisateur</th>'
                + '<th style="padding:6px 8px;text-align:left;font-size:12px">Action</th>'
                + '<th style="padding:6px 8px;text-align:left;font-size:12px">Détails</th>'
                + '</tr></thead><tbody>' + rows + '</tbody></table>'
            );
        }, function (msg) {
            setLoading($btn, false);
            $( '#audit-log-container' ).show();
            var $errP = $( '<p>' ).css( 'color', '#dc3545' ).text( '\u274C ' + msg );
            $( '#audit-log-content' ).empty().append( $errP );
        });
    });

    // ── 📤 Exporter les logs ─────────────────────────────────────────────────
    $('#export-audit-log').on('click', function () {
        var $btn = $(this);
        setLoading($btn, true);

        ajaxGdpr('pdf_builder_export_audit_log', {}, function (data) {
            setLoading($btn, false);
            if (!data.count) {
                showResult('ℹ️ Aucun log à exporter.', false);
                return;
            }
            var blob = new Blob([data.csv], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url; a.download = data.filename; a.click();
            URL.revokeObjectURL(url);
            showResult('\u2705 ' + escapeHtml(String(data.count)) + ' entr\u00E9e(s) export\u00E9e(s) dans <strong>' + escapeHtml(data.filename) + '</strong>', false);
        }, function (msg) {
            setLoading($btn, false);
            showResult('\u274C ' + escapeHtml(msg), true);
        });
    });

    // ── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // Ne s'exécute que si les éléments de l'onglet sécurité sont présents
        if (!document.getElementById('enable_logging') && !document.getElementById('gdpr_enabled')) {
            return;
        }
        var loggingCb = document.getElementById('enable_logging');
        var gdprCb = document.getElementById('gdpr_enabled');
        if (loggingCb) loggingCb.addEventListener('change', updateSecurityStatusIndicators);
        if (gdprCb) gdprCb.addEventListener('change', updateSecurityStatusIndicators);
        updateSecurityStatusIndicators();
    });

})(jQuery);
