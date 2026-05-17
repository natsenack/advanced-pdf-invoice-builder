<?php
/**
 * PDF Builder MetaBox Renderer.
 *
 * Renders the WooCommerce order meta box HTML and inline JavaScript.
 * Extracted from PdfBuilderWooCommerceIntegration to satisfy the S1448 function-length rule.
 *
 * @package PDFIB\Managers
 */

namespace PDFIB\Managers;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the WooCommerce order meta box HTML and inline JavaScript.
 *
 * @package PDFIB\Managers
 */
class PdfBuilderMetaBoxRenderer {


	/**
	 * Main entry point: renders the full meta box body and inline script.
	 *
	 * @param int        $order_id          WooCommerce order ID.
	 * @param array|null $selected_template Template data array, or null if none assigned.
	 * @param bool       $is_premium        True if the current license is premium.
	 * @param string     $status_label      Human-readable order status label.
	 * @param string     $nonce             WordPress nonce for AJAX security.
	 * @param string     $ajax_url          Admin AJAX URL.
	 * @param object     $order             WooCommerce WC_Order instance.
	 */
	public function render(
		int $order_id,
		?array $selected_template,
		bool $is_premium,
		string $status_label,
		string $nonce,
		string $ajax_url,
		object $order
	): void {
		$this->render_meta_box_body_html( $order_id, $selected_template, $is_premium, $status_label, $nonce, $ajax_url, $order );
		$this->render_meta_box_inline_script();
	}


	/**
	 * Renders the meta box main body HTML (template info + action buttons).
	 *
	 * @param int        $order_id          WooCommerce order ID.
	 * @param array|null $selected_template Template data array, or null if none assigned.
	 * @param bool       $is_premium        True if the current license is premium.
	 * @param string     $status_label      Human-readable order status label.
	 * @param string     $nonce             WordPress nonce for AJAX security.
	 * @param string     $ajax_url          Admin AJAX URL.
	 * @param object     $order             WooCommerce WC_Order instance.
	 */
	private function render_meta_box_body_html( int $order_id, ?array $selected_template, bool $is_premium, string $status_label, string $nonce, string $ajax_url, object $order ): void {
		?>
		<div style="font-size:13px;">

			<!-- Template résolu -->
			<div style="margin-bottom:10px;">
				<div style="color:#6c757d;margin-bottom:4px;">
					<?php esc_html_e( 'Statut:', 'advanced-pdf-invoice-builder' ); ?> <strong><?php echo esc_html( $status_label ); ?></strong>
					<?php if ( ! $is_premium ) : ?>
						<span style="margin-left:6px;background:#e9ecef;color:#495057;border-radius:3px;padding:1px 5px;font-size:11px;">Gratuit</span>
					<?php else : ?>
						<span style="margin-left:6px;background:#d4edda;color:#155724;border-radius:3px;padding:1px 5px;font-size:11px;">Premium</span>
					<?php endif; ?>
				</div>
				<div style="padding:8px 10px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;">
					📄 <?php if ( $selected_template ) : ?>
						<strong><?php echo esc_html( $selected_template['name'] ); ?></strong>
					<?php else : ?>
						<em style="color:#dc3545;"><?php esc_html_e( 'Aucun template assigné pour ce statut.', 'advanced-pdf-invoice-builder' ); ?></em>
						<br><small style="color:#6c757d;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pdf-builder-settings&tab=templates' ) ); ?>">
								<?php esc_html_e( 'Configurer dans les paramètres →', 'advanced-pdf-invoice-builder' ); ?>
							</a>
						</small>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $selected_template ) : ?>
				<?php $this->render_meta_box_pdf_mail_buttons( $order_id, $selected_template, $is_premium, $nonce, $ajax_url, $order ); ?>
				<?php do_action( 'pdfib_after_metabox_buttons', $order_id, $selected_template, $is_premium, $nonce, $ajax_url, $order ); ?>
				<div id="pdf-builder-meta-status" style="display:none;margin-top:8px;padding:8px;border-radius:4px;font-size:12px;"></div>
				<?php $this->render_meta_box_mail_modal(); ?>
				<?php $this->render_meta_box_queue_modal(); ?>
			<?php endif; ?>
		</div>

		<?php
	}



	/**
	 * Renders the PDF, Mail, PNG, and JPG action buttons.
	 *
	 * @param int    $order_id          WooCommerce order ID.
	 * @param array  $selected_template Template data array.
	 * @param bool   $is_premium        True if the current license is premium.
	 * @param string $nonce             WordPress nonce for AJAX security.
	 * @param string $ajax_url          Admin AJAX URL.
	 * @param object $order             WooCommerce WC_Order instance.
	 */
	private function render_meta_box_pdf_mail_buttons( int $order_id, array $selected_template, bool $is_premium, string $nonce, string $ajax_url, object $order ): void {
		?>
		<div style="display:flex;flex-direction:column;gap:6px;margin-top:2px;">
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
				<button type="button"
					class="button button-primary pdf-builder-action-btn"
					data-action-type="pdf"
					data-order-id="<?php echo esc_attr( $order_id ); ?>"
					data-template-id="<?php echo esc_attr( $selected_template['id'] ); ?>"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					data-ajax="<?php echo esc_attr( $ajax_url ); ?>"
					data-is-premium="<?php echo esc_attr( $is_premium ? '1' : '0' ); ?>"
					style="font-size:12px;padding:5px 8px;">
					📥 <?php esc_html_e( 'PDF', 'advanced-pdf-invoice-builder' ); ?>
				</button>
				<button type="button"
					id="pdf-builder-mail-btn"
					class="button button-secondary"
					data-order-id="<?php echo esc_attr( $order_id ); ?>"
					data-template-id="<?php echo esc_attr( $selected_template['id'] ); ?>"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					data-ajax="<?php echo esc_attr( $ajax_url ); ?>"
					data-order-email="<?php echo esc_attr( $order->get_billing_email() ); ?>"
					data-order-number="<?php echo esc_attr( $order->get_order_number() ); ?>"
					style="font-size:12px;padding:5px 8px;">
					✉️ <?php esc_html_e( 'Mail', 'advanced-pdf-invoice-builder' ); ?>
				</button>
			</div>
		</div>
		<?php
	}




	/** Rend la modale d'envoi par e-mail */
	private function render_meta_box_mail_modal(): void {
		?>
		<dialog id="pdf-builder-mail-modal" style="display:none;position:fixed;inset:0;z-index:100000;width:100%;height:100%;background:rgba(0,0,0,.55);border:none;padding:0;max-width:none;margin:0;" aria-labelledby="pdf-builder-mail-title">
			<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:8px;width:400px;max-width:95vw;box-shadow:0 10px 40px rgba(0,0,0,.35);">
				<div style="padding:14px 18px;border-bottom:1px solid #dee2e6;display:flex;justify-content:space-between;align-items:center;background:#f8f9fa;border-radius:8px 8px 0 0;">
					<strong id="pdf-builder-mail-title" style="font-size:13px;">✉️ <?php esc_html_e( 'Envoyer par e-mail', 'advanced-pdf-invoice-builder' ); ?></strong>
					<button type="button" id="pdf-builder-mail-close" style="background:none;border:none;font-size:20px;cursor:pointer;color:#6c757d;line-height:1;padding:0 4px;" aria-label="Fermer">&times;</button>
				</div>
				<div style="padding:18px;">
					<div style="margin-bottom:12px;">
						<label for="pdf-builder-mail-to" style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;color:#495057;">
							<?php esc_html_e( 'Destinataire', 'advanced-pdf-invoice-builder' ); ?> <span style="color:#dc3545;">*</span>
						</label>
						<input type="email" id="pdf-builder-mail-to" class="widefat" style="font-size:13px;" placeholder="email@exemple.com">
					</div>
					<div style="margin-bottom:12px;">
						<label for="pdf-builder-mail-subject" style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;color:#495057;">
							<?php esc_html_e( 'Sujet', 'advanced-pdf-invoice-builder' ); ?> <span style="color:#dc3545;">*</span>
						</label>
						<input type="text" id="pdf-builder-mail-subject" class="widefat" style="font-size:13px;" aria-label="<?php esc_attr_e( 'Sujet du mail', 'advanced-pdf-invoice-builder' ); ?>">
					</div>
					<div style="margin-bottom:16px;">
						<label for="pdf-builder-mail-message" style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;color:#495057;">
							<?php esc_html_e( 'Message', 'advanced-pdf-invoice-builder' ); ?>
						</label>
						<textarea id="pdf-builder-mail-message" class="widefat" rows="4" style="font-size:13px;resize:vertical;"></textarea>
					</div>
					<div id="pdf-builder-mail-status" style="display:none;margin-bottom:12px;padding:8px 10px;border-radius:4px;font-size:12px;"></div>
					<div style="display:flex;gap:8px;justify-content:flex-end;">
						<button type="button" id="pdf-builder-mail-cancel" class="button button-secondary">
							<?php esc_html_e( 'Annuler', 'advanced-pdf-invoice-builder' ); ?>
						</button>
						<button type="button" id="pdf-builder-mail-send" class="button button-primary">
							✉️ <?php esc_html_e( 'Envoyer', 'advanced-pdf-invoice-builder' ); ?>
						</button>
					</div>
				</div>
			</div>
		</dialog>
		<?php
	}



	/** Rend la modale de file d'attente PDF (utilisateurs gratuits) */
	private function render_meta_box_queue_modal(): void {
		$pdfib_svg_ns = esc_url( 'http://www.w3.org/2000/svg' );
		?>
		<dialog id="pdfb-queue-modal" style="display:none;position:fixed;inset:0;z-index:100001;width:100%;height:100%;background:rgba(0,0,0,.6);border:none;padding:0;max-width:none;margin:0;" aria-labelledby="pdfb-queue-modal-title">
			<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:10px;width:340px;max-width:95vw;box-shadow:0 12px 48px rgba(0,0,0,.40);overflow:hidden;">
				<div style="padding:16px 20px;border-bottom:1px solid #e0e0e0;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:10px 10px 0 0;">
					<div id="pdfb-queue-modal-title" style="font-size:15px;font-weight:700;margin-bottom:2px;">⏳ <?php esc_html_e( 'Génération du PDF', 'advanced-pdf-invoice-builder' ); ?></div>
					<div style="font-size:12px;opacity:.85;"><?php esc_html_e( 'Fichier gratuit · File d\'attente active', 'advanced-pdf-invoice-builder' ); ?></div>
				</div>
				<div style="padding:24px 20px;text-align:center;">
					<div style="margin-bottom:18px;">
						<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="<?php echo esc_attr( $pdfib_svg_ns ); ?>" style="animation:pdfb-spin 1.2s linear infinite">
							<circle cx="28" cy="28" r="22" stroke="#e9ecef" stroke-width="6" />
							<path d="M28 6a22 22 0 0 1 22 22" stroke="#667eea" stroke-width="6" stroke-linecap="round" />
						</svg>
					</div>
					<div id="pdfb-queue-section">
					<div id="pdfb-queue-pos-text" style="font-size:28px;font-weight:800;color:#667eea;line-height:1;margin-bottom:6px;">#<span id="pdfb-queue-pos-num">…</span></div>
					<div style="font-size:13px;color:#6c757d;margin-bottom:16px;"><?php esc_html_e( 'dans la file d\'attente', 'advanced-pdf-invoice-builder' ); ?></div>
				</div>
				<div id="pdfb-countdown-section" style="display:none;">
					<div style="font-size:52px;font-weight:800;color:#667eea;line-height:1;margin-bottom:6px;"><span id="pdfb-countdown-num">10</span></div>
					<div style="font-size:13px;color:#6c757d;margin-bottom:16px;"><?php esc_html_e( 'secondes', 'advanced-pdf-invoice-builder' ); ?></div>
				</div>
					<div style="background:#e9ecef;border-radius:6px;height:8px;overflow:hidden;margin-bottom:14px;">
						<div id="pdfb-queue-progress-bar" style="height:100%;background:linear-gradient(90deg,#667eea,#764ba2);width:0%;transition:width 0.5s ease;border-radius:6px;"></div>
					</div>
					<div id="pdfb-queue-status-text" style="font-size:12px;color:#868e96;"><?php esc_html_e( 'Patience, votre PDF sera généré automatiquement…', 'advanced-pdf-invoice-builder' ); ?></div>
				</div>
				<div style="padding:12px 20px;border-top:1px solid #e0e0e0;text-align:center;">
					<button type="button" id="pdfb-queue-cancel" class="button button-secondary" style="font-size:12px;">
						<?php esc_html_e( 'Annuler', 'advanced-pdf-invoice-builder' ); ?>
					</button>
				</div>
			</div>
		</dialog>
		<?php
	}



	/**
	 * Captures and registers the meta box inline JavaScript via wp_add_inline_script().
	 */
	private function render_meta_box_inline_script(): void {
		ob_start(); /* Inline script captured for wp_add_inline_script(). */
		?>
		(function($) {
		<?php
		$this->render_meta_box_js_utils();
		$this->render_meta_box_js_action_handlers();
		$this->render_meta_box_js_mail_handlers();
		?>
		})(jQuery);
		<?php
		$pdfb_wc_js = ob_get_clean();
		wp_add_inline_script( 'pdf-builder-wc-order-js', $pdfb_wc_js );
	}



	/**
	 * Outputs the JavaScript utility functions used by the meta box.
	 */
	private function render_meta_box_js_utils(): void {
		// openPdfBlob() and openImageInTab() are now in pdfib-metabox-blob.js (wp_enqueue_script).
		$this->render_meta_box_js_generate_pdf();
		$this->render_meta_box_js_queue_functions();
	}

	/**
	 * Outputs the doGeneratePdf() JavaScript function that fetches and opens the PDF blob.
	 */
	private function render_meta_box_js_generate_pdf(): void {
		?>
		/** Fetch le PDF et l'ouvre en blob */
		function doGeneratePdf(ajaxUrl, orderId, templateId, nonce, btn, orig, callback) {
		_generateAbortController = new AbortController();
		var fd = new FormData();
		fd.append('action', 'pdfib_stream_pdf');
		fd.append('order_id', orderId);
		fd.append('template_id', templateId);
		fd.append('nonce', nonce);

		fetch(ajaxUrl, {
		method: 'POST',
		body: fd,
		signal: _generateAbortController.signal
		})
		.then(function(res) {
		var ct = res.headers.get('Content-Type') || '';
		if (!res.ok || ct.indexOf('application/pdf') === -1) {
		return res.text().then(function(t) {
		throw new Error(t || 'Erreur ' + res.status);
		});
		}
		return res.blob();
		})
		.then(function(blob) {
		btn.prop('disabled', false).html(orig);
		openPdfBlob(blob, orderId);
		if (callback) callback(null);
		})
		.catch(function(err) {
		if (err.name === 'AbortError') return; // Annulé par l'utilisateur
		btn.prop('disabled', false).html(orig);
		alert('Erreur génération PDF :\n' + err.message);
		if (callback) callback(err);
		});
		}
		<?php
	}



	/** JS inline: fonctions pollQueue et leaveQueue */
	private function render_meta_box_js_queue_poll_leave(): void {
		?>

		function pollQueue(ajaxUrl, nonce, onReady) {
		if (_queueCancelled) return;
		var fd = new FormData();
		fd.append('action', 'pdfib_pdf_queue_poll');
		fd.append('nonce', nonce);
		fetch(ajaxUrl, {
		method: 'POST',
		body: fd
		})
		.then(function(r) {
		return r.json();
		})
		.then(function(data) {
		if (_queueCancelled) return;
		if (data.success) {
		var pos = data.data.position;
		var size = data.data.queue_size;
		updateQueueModal(pos, size);
		if (pos === 0) {
		onReady();
		} else {
		_queuePollTimer = setTimeout(function() {
		pollQueue(ajaxUrl, nonce, onReady);
		}, 3000);
		}
		} else {
		_queuePollTimer = setTimeout(function() {
		pollQueue(ajaxUrl, nonce, onReady);
		}, 3000);
		}
		})
		.catch(function() {
		if (!_queueCancelled) {
		_queuePollTimer = setTimeout(function() {
		pollQueue(ajaxUrl, nonce, onReady);
		}, 5000);
		}
		});
		}

		function leaveQueue(ajaxUrl, nonce) {
		var fd = new FormData();
		fd.append('action', 'pdfib_pdf_queue_leave');
		fd.append('nonce', nonce);
		fetch(ajaxUrl, {
		method: 'POST',
		body: fd
		});
		}

		function startCountdown(onDone) {
		var remaining = 10;
		$('#pdfb-queue-section').hide();
		$('#pdfb-countdown-section').show();
		$('#pdfb-countdown-num').text(remaining);
		$('#pdfb-queue-status-text').text('<?php echo esc_js( __( '🚀 Votre PDF est en cours de préparation…', 'advanced-pdf-invoice-builder' ) ); ?>');
		_countdownTimer = setInterval(function() {
		if (_queueCancelled) {
		clearInterval(_countdownTimer);
		_countdownTimer = null;
		return;
		}
		remaining--;
		$('#pdfb-countdown-num').text(remaining);
		if (remaining <= 0) {
		clearInterval(_countdownTimer);
		_countdownTimer = null;
		onDone();
		}
		}, 1000);
		}
		<?php
	}



	/**
	 * Outputs the click-event handlers for the PDF/image action buttons.
	 */
	private function render_meta_box_js_action_handlers(): void {
		?>
		// Bouton Annuler du modal
		$('#pdfb-queue-cancel').on('click', function() {
		_queueCancelled = true;
		if (_queueBtn) { _queueBtn.prop('disabled', false).html(_queueBtnOrig); }
		if (_queueAjaxUrl) { leaveQueue(_queueAjaxUrl, _queueNonce); }
		hideQueueModal();
		});

		$('.pdf-builder-action-btn').on('click', function() {
		var btn = $(this);
		var type = btn.data('action-type'); // pdf | png | jpg
		var orderId = btn.data('order-id');
		var templateId = btn.data('template-id');
		var nonce = btn.data('nonce');
		var ajaxUrl = btn.data('ajax');
		var isPremium = btn.data('is-premium') === 1 || btn.data('is-premium') === '1';
		var orig = btn.html();
		_queueBtn = btn;
		_queueBtnOrig = orig;
		_queueAjaxUrl = ajaxUrl;
		_queueNonce = nonce;
		btn.prop('disabled', true).html('⏳');
		if (type === 'pdf') {
			if (isPremium) {
				doGeneratePdf(ajaxUrl, orderId, templateId, nonce, btn, orig);
			} else {
				<?php $this->render_meta_box_js_free_queue_flow(); ?>
			}
		} else {
			<?php $this->render_meta_box_js_image_handler(); ?>
		}
		});
		<?php
	}



	/** JS inline: flux de file d'attente gratuite pour génération PDF */
	private function render_meta_box_js_free_queue_flow(): void {
		?>
		var fd = new FormData();
		fd.append('action', 'pdfib_pdf_queue_join');
		fd.append('nonce', nonce);
		fetch(ajaxUrl, {
		method: 'POST',
		body: fd
		})
		.then(function(r) {
		return r.json();
		})
		.then(function(data) {
		if (!data.success) {
		btn.prop('disabled', false).html(orig);
		alert('<?php echo esc_js( __( 'Impossible de rejoindre la file : ', 'advanced-pdf-invoice-builder' ) ); ?>' + (data.data && data.data.message ? data.data.message : ''));
		return;
		}
		var pos = data.data.position;
		if (pos === 0) {
		showQueueModal(0);
		updateQueueModal(0, 1);
		// Laisser 1 s pour que l'utilisateur voie sa position (#1) avant le compte à rebours
		setTimeout(function() {
		if (_queueCancelled) return;
		startCountdown(function() {
		doGeneratePdf(ajaxUrl, orderId, templateId, nonce, btn, orig, function() {
		leaveQueue(ajaxUrl, nonce);
		hideQueueModal();
		});
		});
		}, 1000);
		} else {
		showQueueModal(pos);
		pollQueue(ajaxUrl, nonce, function() {
		startCountdown(function() {
		doGeneratePdf(ajaxUrl, orderId, templateId, nonce, btn, orig, function() {
		leaveQueue(ajaxUrl, nonce);
		hideQueueModal();
		});
		});
		});
		}
		})
		.catch(function(err) {
		btn.prop('disabled', false).html(orig);
		alert('<?php echo esc_js( __( 'Erreur réseau : ', 'advanced-pdf-invoice-builder' ) ); ?>' + err.message);
		});
		<?php
	}



	/** JS inline: génération image PNG/JPG et ouverture dans un onglet avec prévisualisation */
	private function render_meta_box_js_image_handler(): void {
		?>
		var formData = new FormData();
		formData.append('action', 'pdfib_generate_image');
		formData.append('template_id', templateId);
		formData.append('order_id', orderId);
		formData.append('format', type);
		formData.append('nonce', nonce);

		fetch(ajaxUrl, {
		method: 'POST',
		body: formData
		})
		.then(function(response) {
		if (!response.ok) {
			return response.text().then(function(text) {
			var message = text || ('Erreur ' + response.status);
			try {
				var err = JSON.parse(text);
				if (err && err.data) {
					message = err.data.details || err.data.message || err.message || message;
				}
			} catch (parseError) {
				message = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || message;
			}
			throw new Error(message);
			});
		}
		return response.blob();
		})
		.then(function(blob) {
		btn.prop('disabled', false).html(orig);
		openImageInTab(blob, type, orderId);
		})
		.catch(function(err) {
		btn.prop('disabled', false).html(orig);
		alert('Erreur lors de la génération ' + type.toUpperCase() + '\n\n' + err.message);
		});
		<?php
	}



	/**
	 * Outputs the JavaScript handlers for the mail modal (open, close, send).
	 */
	private function render_meta_box_js_mail_handlers(): void {
		?>
		// --- Modal Mail ---
		var $modal = $('#pdf-builder-mail-modal');
		var $mstatus = $('#pdf-builder-mail-status');

		$('#pdf-builder-mail-btn').on('click', function() {
		var btn = $(this);
		var num = btn.data('order-number');
		var email = btn.data('order-email');
		$('#pdf-builder-mail-to').val(email);
		$('#pdf-builder-mail-subject').val('<?php echo esc_js( __( 'Votre document - Commande', 'advanced-pdf-invoice-builder' ) ); ?> #' + num);
		$('#pdf-builder-mail-message').val('<?php echo esc_js( __( 'Bonjour,\n\nVeuillez trouver ci-joint votre document relatif à la commande #', 'advanced-pdf-invoice-builder' ) ); ?>' + num + '.\n\n<?php echo esc_js( __( 'Cordialement.', 'advanced-pdf-invoice-builder' ) ); ?>');
		$mstatus.hide();
		$modal.fadeIn(200);
		});

		function closeModal() {
		$modal.fadeOut(150);
		}
		$('#pdf-builder-mail-close, #pdf-builder-mail-cancel').on('click', closeModal);
		$modal.on('click', function(e) {
		if ($(e.target).is($modal)) closeModal();
		});
		$(document).on('keydown.pdfBuilderMail', function(e) {
		if (e.key === 'Escape') closeModal();
		});

		<?php $this->render_meta_box_js_mail_send_handler(); ?>
		<?php
	}



	/** JS inline: handler d'envoi du formulaire mail */
	private function render_meta_box_js_mail_send_handler(): void {
		?>
		$('#pdf-builder-mail-send').on('click', function() {
		var sendBtn = $(this);
		var mailBtn = $('#pdf-builder-mail-btn');
		var to = $('#pdf-builder-mail-to').val().trim();
		var subject = $('#pdf-builder-mail-subject').val().trim();
		var message = $('#pdf-builder-mail-message').val().trim();
		var orderId = mailBtn.data('order-id');
		var templateId = mailBtn.data('template-id');
		var nonce = mailBtn.data('nonce');
		var ajaxUrl = mailBtn.data('ajax');

		if (!to || !subject) {
		$mstatus.css({
		display: 'block',
		background: '#f8d7da',
		color: '#721c24',
		border: '1px solid #f5c6cb'
		})
		.text('<?php echo esc_js( __( 'Le destinataire et le sujet sont obligatoires.', 'advanced-pdf-invoice-builder' ) ); ?>');
		return;
		}
		$mstatus.css({
		display: 'block',
		background: '#d1ecf1',
		color: '#0c5460',
		border: '1px solid #bee5eb'
		})
		.text('⏳ <?php echo esc_js( __( 'Génération du PDF et envoi en cours…', 'advanced-pdf-invoice-builder' ) ); ?>');
		sendBtn.prop('disabled', true).text('⏳');

		$.post(ajaxUrl, {
		action: 'pdfib_send_order_email',
		order_id: orderId,
		template_id: templateId,
		nonce: nonce,
		to: to,
		subject: subject,
		message: message
		}, function(response) {
		sendBtn.prop('disabled', false).html('✉️ <?php echo esc_js( __( 'Envoyer', 'advanced-pdf-invoice-builder' ) ); ?>');
		if (response.success) {
		$mstatus.css({
		background: '#d4edda',
		color: '#155724',
		border: '1px solid #c3e6cb'
		})
		.text('✅ ' + (response.data.message || '<?php echo esc_js( __( 'E-mail envoyé !', 'advanced-pdf-invoice-builder' ) ); ?>'));
		setTimeout(closeModal, 2500);
		} else {
		var msg = (response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'Erreur lors de l\'envoi.', 'advanced-pdf-invoice-builder' ) ); ?>';
		$mstatus.css({
		background: '#f8d7da',
		color: '#721c24',
		border: '1px solid #f5c6cb'
		}).text('❌ ' + msg);
		}
		}).fail(function() {
		sendBtn.prop('disabled', false).html('✉️ <?php echo esc_js( __( 'Envoyer', 'advanced-pdf-invoice-builder' ) ); ?>');
		$mstatus.css({
		display: 'block',
		background: '#f8d7da',
		color: '#721c24',
		border: '1px solid #f5c6cb'
		})
		.text('❌ <?php echo esc_js( __( 'Erreur réseau.', 'advanced-pdf-invoice-builder' ) ); ?>');
		});
		});
		<?php
	}



	/** JS: fonctions de gestion de la file d'attente PDF */
	private function render_meta_box_js_queue_functions(): void {
		?>
		// ----- File d'attente (utilisateurs gratuits) -----
		var _queuePollTimer = null;
		var _queueCancelled = false;
		var _generateAbortController = null;
		var _countdownTimer = null;
		var _queueBtn = null;
		var _queueBtnOrig = '';
		var _queueAjaxUrl = '';
		var _queueNonce = '';

		function showQueueModal(position) {
		_queueCancelled = false;
		$('#pdfb-queue-section').show();
		$('#pdfb-countdown-section').hide();
		$('#pdfb-queue-pos-num').text(position + 1);
		$('#pdfb-queue-progress-bar').css('width', '0%');
		$('#pdfb-queue-status-text').text('<?php echo esc_js( __( 'Patience, votre PDF sera généré automatiquement…', 'advanced-pdf-invoice-builder' ) ); ?>');
		$('#pdfb-queue-modal').fadeIn(200);
		}

		var updateQueueModal = function(position, queueSize) {
		$('#pdfb-queue-pos-num').text(position + 1);
		var pct = queueSize > 1 ? Math.max(5, Math.round((1 - position / queueSize) * 100)) : 80;
		$('#pdfb-queue-progress-bar').css('width', pct + '%');
		if (position === 0) {
		$('#pdfb-queue-status-text').text('<?php echo esc_js( __( 'C\'est votre tour ! Génération en cours…', 'advanced-pdf-invoice-builder' ) ); ?>');
		}
		}

		function hideQueueModal() {
		$('#pdfb-queue-modal').fadeOut(150);
		if (_queuePollTimer) {
		clearTimeout(_queuePollTimer);
		_queuePollTimer = null;
		}
		if (_countdownTimer) {
		clearInterval(_countdownTimer);
		_countdownTimer = null;
		}
		if (_generateAbortController) {
		_generateAbortController.abort();
		_generateAbortController = null;
		}
		}

		<?php $this->render_meta_box_js_queue_poll_leave(); ?>
		<?php
	}
}
