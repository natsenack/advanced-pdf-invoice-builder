<?php
/**
 * Enregistrement des hooks WooCommerce côté admin.
 *
 * @package PDFIB
 */

namespace PDFIB\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Registers WooCommerce-related hooks for PdfBuilderAdmin.
 */
class AdminWooCommerceHooks {

	/**
	 * Instance admin principale.
	 *
	 * @var mixed
	 */
	private mixed $admin;

	/**
	 * Constructeur.
	 *
	 * @param mixed $admin Instance admin.
	 */
	public function __construct( mixed $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Enregistre tous les hooks WooCommerce admin.
	 *
	 * @return void
	 */
	public function register_all(): void {
		$this->register_woo_commerce_admin_scripts();
		$this->register_woo_commerce_ajax_handlers();
		$this->register_order_meta_box_hooks();
	}

	/**
	 * Enregistre les scripts sur les écrans de commande WooCommerce.
	 *
	 * @return void
	 */
	private function register_woo_commerce_admin_scripts(): void {
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_order_scripts' ) );
	}

	/**
	 * Charge les scripts sur les pages de commande WooCommerce.
	 *
	 * @param string $hook Nom de la page admin courante.
	 * @return void
	 */
	public function enqueue_order_scripts( string $hook ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$is_order = false;
		if ( 'post.php' === $hook && isset( $GLOBALS['_GET']['post'] ) ) {
			$is_order = 'shop_order' === get_post_type( intval( wp_unslash( $GLOBALS['_GET']['post'] ) ) );
		}
		if ( ! $is_order && (
			strpos( $hook, 'wc-orders' ) !== false ||
			( isset( $GLOBALS['_GET']['page'] ) && 'wc-orders' === $GLOBALS['_GET']['page'] && isset( $GLOBALS['_GET']['id'] ) )
		) ) {
			$is_order = true;
		}
		if ( ! $is_order ) {
			return;
		}
		wp_enqueue_script( 'html2canvas', PDFIB_PLUGIN_URL . 'assets/js/html2canvas.min.js', array(), '1.4.1', true );
	}

	/**
	 * Enregistre les handlers AJAX WooCommerce.
	 *
	 * @return void
	 */
	private function register_woo_commerce_ajax_handlers(): void {
		$ajax_map = array(
			'pdfib_generate_order_pdf' => 'ajax_generate_order_pdf',
			'pdfib_send_order_email'   => 'ajax_send_order_email',
			'pdfib_pdf_queue_join'     => 'ajax_pdf_queue_join',
			'pdfib_pdf_queue_poll'     => 'ajax_pdf_queue_poll',
		);
		foreach ( $ajax_map as $action => $method ) {
			\add_action( 'wp_ajax_' . $action, array( $this, 'dispatch_woo_ajax_' . $method ), 1 );
		}
		\add_action( 'wp_ajax_pdfib_stream_pdf', array( $this, 'dispatch_stream_pdf' ), 1 );
		\add_action( 'wp_ajax_pdfib_pdf_queue_leave', array( $this, 'dispatch_pdf_queue_leave' ), 1 );
	}

	/**
	 * Dispatche vers ajax_generate_order_pdf de l'intégration WooCommerce.
	 *
	 * @return void
	 */
	public function dispatch_woo_ajax_ajax_generate_order_pdf(): void {
		$this->dispatch_woo_integration_method( 'ajax_generate_order_pdf' );
	}

	/**
	 * Dispatche vers ajax_send_order_email de l'intégration WooCommerce.
	 *
	 * @return void
	 */
	public function dispatch_woo_ajax_ajax_send_order_email(): void {
		$this->dispatch_woo_integration_method( 'ajax_send_order_email' );
	}

	/**
	 * Dispatche vers ajax_pdf_queue_join de l'intégration WooCommerce.
	 *
	 * @return void
	 */
	public function dispatch_woo_ajax_ajax_pdf_queue_join(): void {
		$this->dispatch_woo_integration_method( 'ajax_pdf_queue_join' );
	}

	/**
	 * Dispatche vers ajax_pdf_queue_poll de l'intégration WooCommerce.
	 *
	 * @return void
	 */
	public function dispatch_woo_ajax_ajax_pdf_queue_poll(): void {
		$this->dispatch_woo_integration_method( 'ajax_pdf_queue_poll' );
	}

	/**
	 * Dispatche vers ajax_stream_pdf de l'intégration WooCommerce.
	 *
	 * @return void
	 */
	public function dispatch_stream_pdf(): void {
		$integration = $this->admin->get_woo_commerce_integration();
		if ( $integration ) {
			$integration->ajax_stream_pdf();
		} else {
			status_header( 503 );
			esc_html_e( 'WooCommerce integration unavailable', 'advanced-pdf-invoice-builder' );
			exit;
		}
	}

	/**
	 * Dispatche vers ajax_pdf_queue_leave de l'intégration WooCommerce.
	 *
	 * @return void
	 */
	public function dispatch_pdf_queue_leave(): void {
		$integration = $this->admin->get_woo_commerce_integration();
		if ( $integration ) {
			$integration->ajax_pdf_queue_leave();
		} else {
			\wp_send_json_error( array( 'message' => 'ok' ) );
		}
	}

	/**
	 * Méthode utilitaire : dispatch vers une méthode de l'intégration WooCommerce.
	 *
	 * @param string $method Nom de la méthode à appeler.
	 * @return void
	 */
	private function dispatch_woo_integration_method( string $method ): void {
		$integration = $this->admin->get_woo_commerce_integration();
		if ( $integration ) {
			$integration->$method();
		} else {
			\wp_send_json_error( array( 'message' => 'WooCommerce integration unavailable' ) );
		}
	}

	/**
	 * Enregistre les hooks de metabox commande.
	 *
	 * @return void
	 */
	private function register_order_meta_box_hooks(): void {
		\add_action( 'add_meta_boxes_shop_order', array( $this, 'dispatch_meta_box' ), 10 );
		\add_action( 'add_meta_boxes_woocommerce_page_wc-orders', array( $this, 'dispatch_meta_box' ), 10 );
	}

	/**
	 * Dispatche l'ajout de la metabox WooCommerce si l'intégration est disponible.
	 *
	 * @return void
	 */
	public function dispatch_meta_box(): void {
		if ( ! defined( 'WC_VERSION' ) ) {
			return;
		}
		$woo_integration = $this->admin->get_woo_commerce_integration();
		if ( null === $woo_integration ) {
			return;
		}
		$woo_integration->add_woocommerce_order_meta_box();
	}
}
