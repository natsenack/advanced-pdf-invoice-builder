<?php
/**
 * Advanced PDF Invoice Builder WooCommerce integration manager.
 *
 * Gère les écrans WooCommerce, les hooks AJAX et les actions PDF liées aux commandes.
 *
 * @package PDFIB
 */

namespace PDFIB\Managers;

defined( 'ABSPATH' ) || exit;

use PDFIB\Exception\PdfBuilderWooCommerceException;

/**
 * Advanced PDF Invoice Builder WooCommerce integration manager.
 *
 * Gère les écrans WooCommerce, les hooks AJAX et les actions PDF liées aux commandes.
 */
class PdfBuilderWooCommerceIntegration {
	/**
	 * I18n error message helpers — cannot use __() in class const declarations.
	 *
	 * @return string
	 */
	private static function msg_nonce_invalid(): string {
		return __( 'Nonce invalide', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns i18n handler unavailable message.
	 *
	 * @return string
	 */
	private static function msg_handler_unavailable(): string {
		return __( 'Handler PDF indisponible', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Returns i18n permission denied message.
	 *
	 * @return string
	 */
	private static function msg_permission_denied(): string {
		return __( 'Permission refusée', 'advanced-pdf-invoice-builder' );
	}

	/**
	 * Requête SQL pour récupérer un template par identifiant.
	 *
	 * @var string
	 */
	private const SQL_SELECT_TEMPLATE_BY_ID = 'SELECT id, name FROM %i WHERE id = %d';

	/**
	 * Instance principale du plugin.
	 *
	 * @var mixed
	 */
	private mixed $main_instance;

	/**
	 * Renderer de la metabox WooCommerce.
	 *
	 * @var PdfBuilderMetaBoxRenderer
	 */
	private PdfBuilderMetaBoxRenderer $renderer;

	/**
	 * Gestionnaire AJAX WooCommerce.
	 *
	 * @var PdfBuilderWooCommerceAjax
	 */
	private PdfBuilderWooCommerceAjax $ajax_handler;

	/**
	 * Helper de données de commande.
	 *
	 * @var PdfBuilderOrderDataHelper
	 */
	private PdfBuilderOrderDataHelper $data_helper;

	/**
	 * Constructeur.
	 *
	 * @param mixed $main_instance Instance principale du plugin.
	 */
	public function __construct( mixed $main_instance ) {
		$this->main_instance = $main_instance;
		$this->data_helper   = new PdfBuilderOrderDataHelper();
		$canvas_helper       = new PdfBuilderCanvasHelper();
		$this->ajax_handler  = new PdfBuilderWooCommerceAjax( $canvas_helper, $this->data_helper );
		$this->renderer      = new PdfBuilderMetaBoxRenderer();
		$this->init_hooks();
	}

	/**
	 * Enregistre le script utilisé par la metabox WooCommerce.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
			return;
		}

		wp_register_script(
			'pdfib-metabox-blob',
			PDFIB_PLUGIN_URL . 'assets/js/pdfib-metabox-blob.js',
			array(),
			PDFIB_VERSION,
			true
		);
		wp_enqueue_script( 'pdfib-metabox-blob' );

		wp_register_script( 'pdf-builder-wc-order-js', false, array( 'jquery', 'pdfib-metabox-blob' ), PDFIB_VERSION, true );
		wp_enqueue_script( 'pdf-builder-wc-order-js' );
	}

	/**
	 * Enregistre les hooks AJAX WooCommerce.
	 *
	 * @return void
	 */
	public function register_ajax_hooks(): void {
		add_action( 'wp_ajax_pdfib_generate_order_pdf', array( $this, 'ajax_generate_order_pdf' ) );
		add_action( 'wp_ajax_pdfib_send_order_email', array( $this, 'ajax_send_order_email' ) );
		add_action( 'wp_ajax_pdfib_save_order_canvas', array( $this->ajax_handler, 'ajax_save_order_canvas' ) );
		add_action( 'wp_ajax_pdfib_load_order_canvas', array( $this->ajax_handler, 'ajax_load_order_canvas' ) );
		add_action( 'wp_ajax_pdfib_get_canvas_elements', array( $this->ajax_handler, 'ajax_get_canvas_elements' ) );
		add_action( 'wp_ajax_pdfib_get_order_data', array( $this->ajax_handler, 'ajax_get_order_data' ) );
		add_action( 'wp_ajax_pdfib_get_company_data', array( $this->ajax_handler, 'ajax_get_company_data' ) );
		add_action( 'wp_ajax_pdfib_validate_order_access', array( $this->ajax_handler, 'ajax_validate_order_access' ) );
		add_action( 'wp_ajax_pdfib_pdf_queue_join', array( $this, 'ajax_pdf_queue_join' ) );
		add_action( 'wp_ajax_pdfib_pdf_queue_poll', array( $this, 'ajax_pdf_queue_poll' ) );
		add_action( 'wp_ajax_pdfib_pdf_queue_leave', array( $this, 'ajax_pdf_queue_leave' ) );
		add_action( 'wp_ajax_pdfib_stream_pdf', array( $this, 'ajax_stream_pdf' ) );
	}

	/**
	 * Ajoute la metabox PDF Builder aux commandes WooCommerce.
	 *
	 * @return void
	 */
	public function add_woocommerce_order_meta_box(): void {
		add_meta_box(
			'pdf-builder-order-actions',
			__( 'Advanced PDF Invoice Builder', 'advanced-pdf-invoice-builder' ),
			array( $this, 'render_woocommerce_order_meta_box' ),
			array( 'shop_order', 'woocommerce_page_wc-orders' ),
			'side',
			'high'
		);
	}

	/**
	 * Rend la metabox de commande WooCommerce.
	 *
	 * @param object|\WP_Post|null $post_or_order Objet WooCommerce ou post.
	 * @return void
	 */
	public function render_woocommerce_order_meta_box( mixed $post_or_order ): void {
		$table_templates = pdfib_db()->prefix . 'pdfib_templates';
		$order           = $this->resolve_order_from_param( $post_or_order );

		if ( ! $order ) {
			echo '<p style="color:#dc3545;">❌ ' . esc_html__( 'Commande introuvable.', 'advanced-pdf-invoice-builder' ) . '</p>';
			return;
		}

		$is_premium = $this->check_is_premium();

		$order_status   = $order->get_status();
		$status_key     = 'wc-' . $order_status;
		$order_statuses = function_exists( 'wc_get_order_statuses' ) ? \wc_get_order_statuses() : array();
		$status_label   = $order_statuses[ $status_key ] ?? ucfirst( $order_status );

		if ( ! $is_premium && 'completed' !== $order_status ) {
			?>
			<div style="font-size:13px;">
				<div style="margin-bottom:8px;">
					<?php esc_html_e( 'Statut:', 'advanced-pdf-invoice-builder' ); ?> <strong><?php echo esc_html( $status_label ); ?></strong>
				</div>
				<div style="padding:10px;background:#fff3cd;border:1px solid #ffc107;border-radius:4px;color:#856404;font-size:12px;">
					<?php echo wp_kses( __( 'La génération PDF automatique n\'est disponible que pour les commandes <strong>Terminées</strong>.', 'advanced-pdf-invoice-builder' ), array( 'strong' => array() ) ); ?>
				</div>
			</div>
			<?php
			return;
		}

		$order_id          = $order->get_id();
		$selected_template = $this->resolve_template_for_status( $is_premium, $status_key, $table_templates );
		$nonce             = wp_create_nonce( 'pdfib_order_actions' );
		$ajax_url          = admin_url( 'admin-ajax.php' );
		$this->renderer->render( $order_id, $selected_template, $is_premium, $status_label, $nonce, $ajax_url, $order );
	}

	/**
	 * Résout un objet WC_Order depuis un WC_Order, un WP_Post ou la requête courante.
	 *
	 * @param object|\WP_Post|null $post_or_order Objet reçu par la metabox.
	 * @return object|null
	 */
	private function resolve_order_from_param( ?object $post_or_order ): ?object {
		if ( is_a( $post_or_order, 'WC_Order' ) ) {
			return $post_or_order;
		}

		if ( is_a( $post_or_order, 'WP_Post' ) ) {
			$order_id = $post_or_order->ID;
		} else {
			$order_id = absint( filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) ?? 0 );
		}

		$order = function_exists( 'wc_get_order' ) ? \wc_get_order( $order_id ) : null;
		return ( $order instanceof \WC_Order ) ? $order : null;
	}

	/**
	 * Résout le template associé au statut courant.
	 *
	 * @param bool   $is_premium Indique si l'utilisateur est Premium.
	 * @param string $status_key Clé de statut WooCommerce.
	 * @param string $table      Nom de table du template.
	 * @return array<string, mixed>|null
	 */
	private function resolve_template_for_status( bool $is_premium, string $status_key, string $table ): ?array {
		$settings         = pdfib_get_option( 'pdfib_settings', array() );
		$status_templates = $settings['pdfib_order_status_templates'] ?? array();
		$selected         = null;

		if ( $is_premium && ! empty( $status_templates[ $status_key ] ) ) {
			$selected = pdfib_db()->get_row( pdfib_db()->prepare( self::SQL_SELECT_TEMPLATE_BY_ID, $table, (int) $status_templates[ $status_key ] ), ARRAY_A );
		} elseif ( ! $is_premium && ! empty( $status_templates['wc-completed'] ) ) {
			$selected = pdfib_db()->get_row( pdfib_db()->prepare( self::SQL_SELECT_TEMPLATE_BY_ID, $table, (int) $status_templates['wc-completed'] ), ARRAY_A );
		}

		if ( ! $selected ) {
			$mapped_id = \apply_filters( 'pdfib_get_template_for_status', null, $status_key );
			if ( $mapped_id ) {
				$selected = pdfib_db()->get_row( pdfib_db()->prepare( self::SQL_SELECT_TEMPLATE_BY_ID, $table, (int) $mapped_id ), ARRAY_A );
			}
		}

		if ( ! $selected ) {
			$selected = pdfib_db()->get_row( pdfib_db()->prepare( 'SELECT id, name FROM %i ORDER BY id ASC LIMIT 1', $table ), ARRAY_A );
		}

		return $selected;
	}

	/**
	 * Retourne les slots actifs de la file PDF et nettoie les entrées expirées.
	 *
	 * @return array<int, int>
	 */
	private function pdf_queue_get_slots(): array {
		$slots = get_option( 'pdfib_free_pdf_slots', array() );
		$now   = time();
		$slots = array_filter(
			$slots,
			static fn( $timestamp ) => ( $now - (int) $timestamp ) < 180
		);

		return $slots;
	}

	/**
	 * Sauvegarde les slots actifs de la file PDF.
	 *
	 * @param array<int, int> $slots Slots à enregistrer.
	 * @return void
	 */
	private function pdf_queue_save_slots( array $slots ): void {
		update_option( 'pdfib_free_pdf_slots', $slots, false );
	}

	/**
	 * Indique si l'utilisateur courant est Premium.
	 * Délègue au plugin PRO via filtre si disponible.
	 *
	 * @return bool
	 */
	private function check_is_premium(): bool {
		$license_manager = apply_filters( 'pdfib_license_manager_instance', null );

		if ( ! is_object( $license_manager ) && class_exists( '\PDFIB\Managers\PdfBuilderLicenseManager' ) ) {
			$license_manager = \PDFIB\Managers\PdfBuilderLicenseManager::get_instance();
		}

		return is_object( $license_manager )
			&& method_exists( $license_manager, 'is_premium' )
			&& $license_manager->is_premium();
	}

	/**
	 * Retire l'utilisateur courant de la file après génération.
	 *
	 * @return void
	 */
	public function ajax_pdf_queue_leave(): void {
		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => self::msg_permission_denied() ), 403 );
			return;
		}

		$nonce       = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) );
		$valid_nonce = wp_verify_nonce( $nonce, 'pdfib_order_actions' )
			|| wp_verify_nonce( $nonce, 'pdfib_ajax' );
		if ( ! $valid_nonce ) {
			wp_send_json_error( array( 'message' => self::msg_nonce_invalid() ), 403 );
			return;
		}

		$slots   = $this->pdf_queue_get_slots();
		$user_id = get_current_user_id();
		unset( $slots[ $user_id ] );
		$this->pdf_queue_save_slots( $slots );

		wp_send_json_success(
			array(
				'message'    => 'ok',
				'queue_size' => count( $slots ),
			)
		);
	}

	/**
	 * Stream le PDF en binaire pour un fetch() depuis le navigateur.
	 *
	 * @return void
	 */
	public function ajax_stream_pdf(): void {
		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
			status_header( 403 );
			echo esc_html( self::msg_permission_denied() );
			exit;
		}

		$nonce = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'pdfib_order_actions' ) ) {
			status_header( 403 );
			echo esc_html( self::msg_nonce_invalid() );
			exit;
		}

		$order_id    = absint( wp_unslash( $GLOBALS['_POST']['order_id'] ?? 0 ) );
		$template_id = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_id'] ?? '' ) );

		if ( ! $order_id || ! $template_id ) {
			status_header( 422 );
			/* translators: 1: Order ID, 2: Template ID */
			echo esc_html( sprintf( __( 'Paramètres manquants (order_id=%1$d template_id=%2$s)', 'advanced-pdf-invoice-builder' ), (int) $order_id, $template_id ) );
			exit;
		}

		if ( ! class_exists( 'PdfBuilderUnifiedAjaxHandler' ) ) {
			status_header( 500 );
			echo esc_html( self::msg_handler_unavailable() );
			exit;
		}

		$handler     = \PdfBuilderUnifiedAjaxHandler::get_instance();
		$pdf_content = $handler->get_pdf_buffer( $template_id, $order_id );

		if ( false === $pdf_content || 0 === strlen( $pdf_content ) ) {
			status_header( 500 );
			echo esc_html__( 'Échec génération PDF', 'advanced-pdf-invoice-builder' );
			exit;
		}

		$this->stream_pdf_to_output( $pdf_content, $order_id );
	}

	/**
	 * Envoie un buffer PDF en streaming HTTP et termine l'exécution.
	 *
	 * @param string $pdf_content Contenu PDF binaire.
	 * @param int    $order_id    Identifiant de la commande.
	 * @return void
	 */
	private function stream_pdf_to_output( string $pdf_content, int $order_id ): void {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		header_remove();
		header( 'Content-Type: application/pdf' );

		$order_number = $order_id;
		if ( function_exists( 'wc_get_order' ) ) {
			$order = \wc_get_order( $order_id );
			if ( $order ) {
				$order_number = $order->get_order_number();
			}
		}

		header( 'Content-Disposition: inline; filename="commande-' . sanitize_file_name( (string) $order_number ) . '.pdf"' );
		header( 'Content-Length: ' . strlen( $pdf_content ) );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'X-Content-Type-Options: nosniff' );

		pdfib_filesystem()->put_contents( 'php://output', $pdf_content );
		exit;
	}

	/**
	 * Génère le PDF d'une commande et le renvoie directement au navigateur.
	 *
	 * @return void
	 */
	public function ajax_generate_order_pdf(): void {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_shop_orders' ) ) {
			\wp_send_json_error( array( 'message' => self::msg_permission_denied() ) );
			return;
		}

		$nonce = \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? $GLOBALS['_GET']['nonce'] ?? '' ) );
		if ( ! \wp_verify_nonce( $nonce, 'pdfib_order_actions' ) ) {
			\wp_send_json_error( array( 'message' => self::msg_nonce_invalid() ) );
			return;
		}

		$order_id    = absint( wp_unslash( $GLOBALS['_POST']['order_id'] ?? $GLOBALS['_GET']['order_id'] ?? 0 ) );
		$template_id = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_id'] ?? $GLOBALS['_GET']['template_id'] ?? '' ) );

		if ( ! $order_id || ! $template_id ) {
			\wp_send_json_error( array( 'message' => __( 'Paramètres manquants', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		if ( ! class_exists( 'PdfBuilderUnifiedAjaxHandler' ) ) {
			\wp_send_json_error( array( 'message' => self::msg_handler_unavailable() ) );
			return;
		}

		$handler     = \PdfBuilderUnifiedAjaxHandler::get_instance();
		$pdf_content = $handler->get_pdf_buffer( $template_id, $order_id );
		if ( false === $pdf_content || '' === $pdf_content ) {
			\wp_send_json_error( array( 'message' => self::msg_handler_unavailable() ) );
			return;
		}

		$this->stream_pdf_to_output( $pdf_content, $order_id );
	}

	/**
	 * Envoie un PDF de commande par e-mail.
	 *
	 * @return void
	 */
	public function ajax_send_order_email(): void {
		try {
			[, $order_id, $template_id, $to, $subject, $message] = $this->validate_and_extract_email_request();
			$this->send_pdf_by_email( $order_id, $template_id, $to, $subject, $message );
		} catch ( \RuntimeException $e ) {
			$error_code = (int) $e->getCode();
			\wp_send_json_error( array( 'message' => $e->getMessage() ), 0 !== $error_code ? $error_code : 400 );
		}
	}

	/**
	 * Valide une requête d'envoi e-mail et retourne les paramètres utiles.
	 *
	 * @throws PdfBuilderWooCommerceException Si la requête est invalide.
	 * @return array<int, mixed>
	 */
	private function validate_and_extract_email_request(): array {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_shop_orders' ) ) {
			throw new PdfBuilderWooCommerceException( 'Permissions insuffisantes', 403 );
		}

		$nonce = \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) );
		if ( ! \wp_verify_nonce( $nonce, 'pdfib_order_actions' ) ) {
			throw new PdfBuilderWooCommerceException( esc_html( self::msg_nonce_invalid() ), 403 );
		}

		$order_id    = absint( wp_unslash( $GLOBALS['_POST']['order_id'] ?? 0 ) );
		$template_id = \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['template_id'] ?? '' ) );
		$to          = \sanitize_email( wp_unslash( $GLOBALS['_POST']['to'] ?? '' ) );
		$subject     = \sanitize_text_field( wp_unslash( $GLOBALS['_POST']['subject'] ?? '' ) );
		$message     = \sanitize_textarea_field( wp_unslash( $GLOBALS['_POST']['message'] ?? '' ) );

		if ( ! $order_id || ! $template_id || ! $to || ! $subject ) {
			throw new PdfBuilderWooCommerceException( 'Paramètres manquants (order_id, template_id, to, subject)', 422 );
		}

		if ( ! \is_email( $to ) ) {
			throw new PdfBuilderWooCommerceException( 'Adresse e-mail invalide', 422 );
		}

		if ( ! function_exists( 'wc_get_order' ) ) {
			throw new PdfBuilderWooCommerceException( 'WooCommerce non disponible', 503 );
		}

		$order = \wc_get_order( $order_id );
		if ( ! $order ) {
			throw new PdfBuilderWooCommerceException( esc_html( 'Commande #' . (int) $order_id . ' introuvable' ), 404 );
		}

		if ( ! class_exists( 'PdfBuilderUnifiedAjaxHandler' ) ) {
			throw new PdfBuilderWooCommerceException( esc_html( self::msg_handler_unavailable() ), 503 );
		}

		return array( $order, $order_id, $template_id, $to, $subject, $message );
	}

	/**
	 * Génère le PDF, l'attache au courriel et l'envoie.
	 *
	 * @param int    $order_id    Identifiant de commande.
	 * @param string $template_id Identifiant de template.
	 * @param string $to          Destinataire.
	 * @param string $subject     Sujet du courriel.
	 * @param string $message     Message du courriel.
	 * @return void
	 */
	private function send_pdf_by_email( int $order_id, string $template_id, string $to, string $subject, string $message ): void {
		$handler     = \PdfBuilderUnifiedAjaxHandler::get_instance();
		$pdf_content = $handler->get_pdf_buffer( $template_id, $order_id );

		if ( false === $pdf_content ) {
			\wp_send_json_error( array( 'message' => __( "Erreur lors de la génération du PDF. Vérifiez qu'un moteur PDF est configuré.", 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		$upload  = \wp_upload_dir();
		$tmp_dir = $upload['basedir'] . '/pdf-builder-tmp/';
		if ( ! file_exists( $tmp_dir ) ) {
			\wp_mkdir_p( $tmp_dir );
		}

		$tmp_file = $tmp_dir . 'order-' . $order_id . '-' . uniqid() . '.pdf';
		pdfib_filesystem()->put_contents( $tmp_file, $pdf_content, FS_CHMOD_FILE );

		$from_name   = get_bloginfo( 'name' );
		$from_email  = get_option( 'admin_email' );
		$headers     = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
		);
		$body        = '' !== $message ? nl2br( \esc_html( $message ) ) : '';
		$attachments = array( $tmp_file );

		$sent = \wp_mail( $to, $subject, $body, $headers, $attachments );
		wp_delete_file( $tmp_file );

		if ( $sent ) {
			// translators: %s: recipient email address.
			$success_fmt = __( 'E-mail envoyé avec succès à %s', 'advanced-pdf-invoice-builder' );
			\wp_send_json_success( array( 'message' => sprintf( $success_fmt, $to ) ) );
			return;
		}

		\wp_send_json_error( array( 'message' => __( "Échec de l'envoi. Vérifiez la configuration SMTP de WordPress.", 'advanced-pdf-invoice-builder' ) ) );
	}

	/**
	 * Vérifie la file PDF, réserve un slot et renvoie la position courante.
	 *
	 * @return void
	 */
	public function ajax_pdf_queue_poll(): void {
		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => self::msg_permission_denied() ), 403 );
			return;
		}

		$nonce       = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) );
		$valid_nonce = wp_verify_nonce( $nonce, 'pdfib_order_actions' )
			|| wp_verify_nonce( $nonce, 'pdfib_ajax' );
		if ( ! $valid_nonce ) {
			wp_send_json_error( array( 'message' => self::msg_nonce_invalid() ), 403 );
			return;
		}

		$slots   = $this->pdf_queue_get_slots();
		$user_id = get_current_user_id();

		if ( ! isset( $slots[ $user_id ] ) ) {
			$slots = array( $user_id => time() ) + $slots;
			$this->pdf_queue_save_slots( $slots );
		} else {
			$slots[ $user_id ] = time();
			$this->pdf_queue_save_slots( $slots );
		}

		$keys     = array_keys( $slots );
		$position = (int) array_search( $user_id, $keys, true );

		wp_send_json_success(
			array(
				'position'       => $position,
				'slot_available' => ( 0 === $position ),
				'queue_size'     => count( $slots ),
			)
		);
	}

	/**
	 * Initialise les hooks du manager.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', array( $this, 'register_ajax_hooks' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_woocommerce_order_meta_box' ) );
	}

	/**
	 * Ajoute l'utilisateur courant à la file PDF gratuite.
	 *
	 * @return void
	 */
	public function ajax_pdf_queue_join(): void {
		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => self::msg_permission_denied() ), 403 );
			return;
		}

		$nonce       = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) );
		$valid_nonce = wp_verify_nonce( $nonce, 'pdfib_order_actions' )
			|| wp_verify_nonce( $nonce, 'pdfib_ajax' );
		if ( ! $valid_nonce ) {
			wp_send_json_error( array( 'message' => self::msg_nonce_invalid() ), 403 );
			return;
		}

		if ( $this->check_is_premium() ) {
			wp_send_json_success(
				array(
					'is_premium'     => true,
					'position'       => 0,
					'slot_available' => true,
					'queue_size'     => 0,
				)
			);
			return;
		}

		$slots   = $this->pdf_queue_get_slots();
		$user_id = get_current_user_id();

		if ( ! isset( $slots[ $user_id ] ) ) {
			$slots[ $user_id ] = time();
			$this->pdf_queue_save_slots( $slots );
		}

		$keys     = array_keys( $slots );
		$position = (int) array_search( $user_id, $keys, true );

		wp_send_json_success(
			array(
				'is_premium'     => false,
				'position'       => $position,
				'slot_available' => ( 0 === $position ),
				'queue_size'     => count( $slots ),
			)
		);
	}
}
