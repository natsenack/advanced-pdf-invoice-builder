<?php
/**
 * Advanced PDF Invoice Builder - Notification Manager.
 * Système centralisé de gestion des notifications.
 * Version: 1.0.0.
 *
 * @package PDFIB\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

/**
 * Main class for notification management.
 */
class PdfBuilderNotificationManager {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Queued notifications.
	 *
	 * @var array
	 */
	private array $notification_queue = array();

	/**
	 * Notification settings.
	 *
	 * @var array
	 */
	private array $settings = array();

	/**
	 * Constructeur privé pour le pattern Singleton.
	 */
	private function __construct() {

		$this->init_settings();
		$this->init_hooks();
	}

	/**
	 * Obtenir l'instance unique.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialiser les paramètres.
	 */
	private function init_settings(): void {
		$this->settings = array(
			'enabled'           => pdfib_get_option( 'pdfib_notifications_enabled', true ),
			'position'          => pdfib_get_option( 'pdfib_notifications_position', 'top-right' ),
			'duration'          => pdfib_get_option( 'pdfib_notifications_duration', 5000 ),
			'max_notifications' => pdfib_get_option( 'pdfib_notifications_max', 5 ),
			'animation'         => pdfib_get_option( 'pdfib_notifications_animation', 'slide' ),
			'sound_enabled'     => pdfib_get_option( 'pdfib_notifications_sound', false ),
			'types'             => array(
				'success' => array(
					'icon'  => '✅',
					'color' => '#28a745',
					'bg'    => '#d4edda',
				),
				'error'   => array(
					'icon'  => '❌',
					'color' => '#dc3545',
					'bg'    => '#f8d7da',
				),
				'warning' => array(
					'icon'  => '⚠️',
					'color' => '#ffc107',
					'bg'    => '#fff3cd',
				),
				'info'    => array(
					'icon'  => 'ℹ️',
					'color' => '#17a2b8',
					'bg'    => '#d1ecf1',
				),
			),
		);
	}

	/**
	 * Initialiser les hooks WordPress.
	 */
	private function init_hooks(): void {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_pdfib_show_notification', array( $this, 'ajax_show_notification' ) );

		// Hooks pour les notifications automatiques.
		add_action( 'pdfib_template_saved', array( $this, 'notify_template_saved' ) );
		add_action( 'pdfib_template_deleted', array( $this, 'notify_template_deleted' ) );
		add_action( 'pdfib_settings_saved', array( $this, 'notify_settings_saved' ) );
	}

	/**
	 * Charger les scripts et styles.
	 */
	public function enqueue_scripts(): void {
		if ( ! $this->settings['enabled'] ) {
			return;
		}

		// Charger les styles et scripts seulement si les fichiers existent.
		$notifications_css = \plugin_dir_path( dirname( __DIR__ ) ) . 'assets/css/notifications-css.min.css';
		if ( file_exists( $notifications_css ) ) {
			\wp_enqueue_style(
				'pdf-builder-notifications',
				plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/notifications-css.min.css',
				array(),
				'1.0.0'
			);
		}

		$notifications_js = \plugin_dir_path( dirname( __DIR__ ) ) . 'assets/js/notifications.js';
		if ( file_exists( $notifications_js ) ) {
			wp_enqueue_script(
				'pdf-builder-notifications',
				plugin_dir_url( dirname( __DIR__ ) ) . 'assets/js/notifications.js',
				array( 'jquery' ),
				'1.0.0-' . time(),
				false
			);
		}

		// Localiser le script avec les paramètres.
		wp_localize_script(
			'pdf-builder-notifications',
			'pdfBuilderNotifications',
			array(
				'settings' => $this->settings,
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'pdfib_notifications' ),
				'strings'  => array(
					'close'       => __( 'Fermer', 'advanced-pdf-invoice-builder' ),
					'dismiss_all' => __( 'Tout fermer', 'advanced-pdf-invoice-builder' ),
				),
			)
		);
	}

	/**
	 * Afficher une notification.
	 *
	 * @param string $message Notification message.
	 * @param string $type    Notification type (info|success|warning|error).
	 * @param array  $options Additional options.
	 */
	public function show_notification( string $message, string $type = 'info', array $options = array() ): ?array {
		if ( ! $this->settings['enabled'] ) {
			return null;
		}

		$allowed_types = array_keys( $this->settings['types'] );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			$type = 'info';
		}

		$notification = array_merge(
			array(
				'message'     => $message,
				'type'        => $type,
				'duration'    => $this->settings['duration'],
				'dismissible' => true,
				'position'    => $this->settings['position'],
				'timestamp'   => time(),
			),
			$options
		);

		// Ajouter à la file d'attente.
		$this->notification_queue[] = $notification;

		// Limiter le nombre de notifications.
		if ( count( $this->notification_queue ) > $this->settings['max_notifications'] ) {
			array_shift( $this->notification_queue );
		}

		// Si on est en AJAX, retourner les données.
		if ( wp_doing_ajax() ) {
			return $notification;
		}

		// Sinon, ajouter au footer.
		add_action( 'wp_footer', array( $this, 'render_notifications' ) );
		add_action( 'admin_footer', array( $this, 'render_notifications' ) );
		return $notification;
	}




	/**
	 * Rendre une notification individuelle.
	 *
	 * @param array $notification Notification data array.
	 */
	private function render_single_notification( array $notification ): void {
		$type_config = isset( $this->settings['types'][ $notification['type'] ] )
			? $this->settings['types'][ $notification['type'] ]
			: $this->settings['types']['info'];

		$classes = array(
			'pdf-builder-notification',
			'pdf-builder-notification-' . $notification['type'],
			'pdf-builder-notification-' . $this->settings['animation'],
		);

		if ( $notification['dismissible'] ) {
			$classes[] = 'dismissible';
		}

		$style = sprintf(
			'background-color: %s; color: %s; border-left-color: %s;',
			$type_config['bg'],
			$type_config['color'],
			$type_config['color']
		);

		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( $style ); ?>" data-duration="<?php echo esc_attr( $notification['duration'] ); ?>">
			<div class="notification-content">
				<span class="notification-icon"><?php echo esc_html( $type_config['icon'] ); ?></span> <span class="notification-message"><?php echo wp_kses_post( $notification['message'] ); ?></span>
				<?php if ( $notification['dismissible'] ) : ?>
					<button class="notification-close" aria-label="<?php esc_attr_e( 'Fermer', 'advanced-pdf-invoice-builder' ); ?>">
						<span class="dashicons dashicons-no"></span>
					</button>
				<?php endif; ?>
			</div>

			<?php if ( $notification['duration'] > 0 ) : ?>
				<div class="notification-progress-bar">
					<div class="notification-progress" style="background-color: <?php echo esc_attr( $type_config['color'] ); ?>"></div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handler AJAX pour afficher une notification.
	 */
	public function ajax_show_notification(): void {

		// Vérifier le nonce (accepter le nonce général ajax, spécifique aux notifications, ou aux paramètres).
		$nonce       = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['nonce'] ?? '' ) );
		$nonce_valid = wp_verify_nonce( $nonce, 'pdfib_notifications' ) ||
			wp_verify_nonce( $nonce, 'pdfib_ajax' ) ||
			wp_verify_nonce( $nonce, 'pdfib_settings' );

		if ( ! $nonce_valid ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Nonce invalide', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permissions insuffisantes', 'advanced-pdf-invoice-builder' ) ), 403 );
			return;
		}

		$message  = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['message'] ?? '' ) );
		$type     = sanitize_text_field( wp_unslash( $GLOBALS['_POST']['type'] ?? 'info' ) );
		$duration = max( 0, intval( wp_unslash( $GLOBALS['_POST']['duration'] ?? $this->settings['duration'] ) ) );

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Message requis', 'advanced-pdf-invoice-builder' ) ) );
			return;
		}

		$notification = $this->show_notification( $message, $type, array( 'duration' => $duration ) );

		wp_send_json_success(
			array(
				'notification' => $notification,
			)
		);
	}

	/**
	 * Notification automatique lors de la sauvegarde d'un template.
	 *
	 * @param int $template_id Template ID.
	 */
	public function notify_template_saved( int $template_id ): void {
		$message = sprintf(
			/* translators: %d: template ID. */
			__( 'Template #%d sauvegardé avec succès !', 'advanced-pdf-invoice-builder' ),
			$template_id
		);
		$this->show_notification( $message, 'success' );
	}

	/**
	 * Notification automatique lors de la suppression d'un template.
	 *
	 * @param int $template_id Template ID.
	 */
	public function notify_template_deleted( int $template_id ): void {
		$message = sprintf(
			/* translators: %d: template ID. */
			__( 'Template #%d supprimé avec succès.', 'advanced-pdf-invoice-builder' ),
			$template_id
		);
		$this->show_notification( $message, 'info' );
	}

	/**
	 * Notification automatique lors de la sauvegarde des paramètres.
	 */
	public function notify_settings_saved(): void {
		$message = __( 'Paramètres sauvegardés avec succès !', 'advanced-pdf-invoice-builder' );
		$this->show_notification( $message, 'success' );
	}

	/**
	 * Méthodes utilitaires.
	 *
	 * @param string $message Notification message.
	 * @param array  $options Additional options.
	 */
	public function success( string $message, array $options = array() ): ?array {
		return $this->show_notification( $message, 'success', $options );
	}

	/**
	 * Send error notification.
	 *
	 * @param string $message Notification message.
	 * @param array  $options Additional options.
	 */
	public function error( string $message, array $options = array() ): ?array {
		return $this->show_notification( $message, 'error', $options );
	}

	/**
	 * Send warning notification.
	 *
	 * @param string $message Notification message.
	 * @param array  $options Additional options.
	 */
	public function warning( string $message, array $options = array() ): ?array {
		return $this->show_notification( $message, 'warning', $options );
	}

	/**
	 * Send info notification.
	 *
	 * @param string $message Notification message.
	 * @param array  $options Additional options.
	 */
	public function info( string $message, array $options = array() ): ?array {
		return $this->show_notification( $message, 'info', $options );
	}

	/**
	 * Obtenir les paramètres actuels.
	 */
	public function get_settings(): array {
		return $this->settings;
	}

	/**
	 * Mettre à jour les paramètres.
	 *
	 * @param array $new_settings New settings to merge.
	 */
	public function update_settings( array $new_settings ): void {
		$this->settings = array_merge( $this->settings, $new_settings );

		// Sauvegarder en base.
		foreach ( $new_settings as $key => $value ) {
			pdfib_update_option( 'pdfib_notifications_' . $key, $value );
		}
	}

	/**
	 * Rendre les notifications HTML.
	 */
	public function render_notifications(): void {
		if ( empty( $this->notification_queue ) ) {
			return;
		}

		echo '<div class="pdf-builder-notifications-container" data-position="' . esc_attr( $this->settings['position'] ) . '">';
		foreach ( $this->notification_queue as $notification ) {
			$this->render_single_notification( $notification );
		}

		echo '</div>'; // Vider la file d'attente après rendu.
		$this->notification_queue = array();
	}
}


