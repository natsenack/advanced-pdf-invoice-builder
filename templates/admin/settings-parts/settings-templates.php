<?php
/**
 * Onglet de configuration des templates par statut de commande.
 *
 * @package AdvancedPdfInvoiceBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'pdfib_is_premium_templates_enabled' ) ) {
	/**
	 * Indique si la licence Premium est active.
	 *
	 * @return bool
	 */
	function pdfib_is_premium_templates_enabled(): bool {
		$pdfib_license_manager = apply_filters( 'pdfib_license_manager_instance', null );

		return is_object( $pdfib_license_manager )
			&& method_exists( $pdfib_license_manager, 'is_premium' )
			&& $pdfib_license_manager->is_premium();
	}
}

if ( ! function_exists( 'pdfib_get_template_status_text_map' ) ) {
	/**
	 * Retourne les libellés utilisés par l'onglet templates.
	 *
	 * @return array<string, string>
	 */
	function pdfib_get_template_status_text_map(): array {
		return array(
			'unknown_plugin' => __( 'Plugin inconnu', 'advanced-pdf-invoice-builder' ),
			'preparation'    => __( 'Plugin de preparation de commande', 'advanced-pdf-invoice-builder' ),
			'payment'        => __( 'Plugin de paiement personnalise', 'advanced-pdf-invoice-builder' ),
			'returns'        => __( 'Plugin de gestion des retours', 'advanced-pdf-invoice-builder' ),
			'marketplace'    => __( 'Plugin marketplace', 'advanced-pdf-invoice-builder' ),
			'shipping'       => __( 'Plugin d\'expedition', 'advanced-pdf-invoice-builder' ),
			'delivery'       => __( 'Plugin de livraison', 'advanced-pdf-invoice-builder' ),
			'refund'         => __( 'Plugin de remboursement personnalise', 'advanced-pdf-invoice-builder' ),
		);
	}
}

if ( ! function_exists( 'pdfib_get_template_default_statuses' ) ) {
	/**
	 * Retourne les slugs des statuts WooCommerce standards.
	 *
	 * @return string[]
	 */
	function pdfib_get_template_default_statuses(): array {
		return array(
			'pending',
			'processing',
			'on-hold',
			'completed',
			'cancelled',
			'refunded',
			'failed',
			'draft',
			'checkout-draft',
		);
	}
}

if ( ! function_exists( 'pdfib_get_template_order_statuses' ) ) {
	/**
	 * Retourne les statuts de commande disponibles.
	 *
	 * @return array<string, string>
	 */
	function pdfib_get_template_order_statuses(): array {
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			return wc_get_order_statuses();
		}

		$pdfib_statuses = get_option( 'wc_order_statuses', array() );

		if ( is_array( $pdfib_statuses ) && ! empty( $pdfib_statuses ) ) {
			return $pdfib_statuses;
		}

		return array(
			'wc-pending'    => __( 'En attente de paiement', 'advanced-pdf-invoice-builder' ),
			'wc-processing' => __( 'En cours', 'advanced-pdf-invoice-builder' ),
			'wc-on-hold'    => __( 'En attente', 'advanced-pdf-invoice-builder' ),
			'wc-completed'  => __( 'Terminee', 'advanced-pdf-invoice-builder' ),
			'wc-cancelled'  => __( 'Annulee', 'advanced-pdf-invoice-builder' ),
			'wc-refunded'   => __( 'Remboursee', 'advanced-pdf-invoice-builder' ),
			'wc-failed'     => __( 'Echec', 'advanced-pdf-invoice-builder' ),
		);
	}
}

if ( ! function_exists( 'pdfib_is_template_custom_status' ) ) {
	/**
	 * Indique si un statut est personnalise.
	 *
	 * @param string $status_key Cle du statut.
	 * @return bool
	 */
	function pdfib_is_template_custom_status( string $status_key ): bool {
		$pdfib_clean_key = str_replace( 'wc-', '', $status_key );

		return ! in_array(
			$pdfib_clean_key,
			pdfib_get_template_default_statuses(),
			true
		);
	}
}

if ( ! function_exists( 'pdfib_get_template_active_plugins' ) ) {
	/**
	 * Retourne la liste des plugins actifs, multisite incluse.
	 *
	 * @return string[]
	 */
	function pdfib_get_template_active_plugins(): array {
		$pdfib_active_plugins = get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$pdfib_network_plugins = array_keys(
				(array) get_site_option( 'active_sitewide_plugins', array() )
			);
			$pdfib_active_plugins  = array_merge(
				$pdfib_active_plugins,
				$pdfib_network_plugins
			);
		}

		$pdfib_active_plugins = array_unique( $pdfib_active_plugins );

		return array_values(
			array_filter(
				$pdfib_active_plugins,
				static function ( $plugin_file ) {
					return is_string( $plugin_file )
						&& '' !== $plugin_file
						&& 'woocommerce/woocommerce.php' !== $plugin_file;
				}
			)
		);
	}
}

if ( ! function_exists( 'pdfib_get_template_plugin_header_data' ) ) {
	/**
	 * Lit les metadonnees d'un plugin actif.
	 *
	 * @param string $plugin_file Fichier principal du plugin.
	 * @return array<string, string>
	 */
	function pdfib_get_template_plugin_header_data( string $plugin_file ): array {
		$pdfib_plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $plugin_file;

		if ( ! file_exists( $pdfib_plugin_path ) ) {
			return array();
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$pdfib_plugin_data = get_plugin_data( $pdfib_plugin_path, false, false );

		return is_array( $pdfib_plugin_data ) ? $pdfib_plugin_data : array();
	}
}

if ( ! function_exists( 'pdfib_format_template_plugin_name' ) ) {
	/**
	 * Formate un nom de plugin depuis son slug.
	 *
	 * @param string $plugin_file Fichier principal du plugin.
	 * @return string
	 */
	function pdfib_format_template_plugin_name( string $plugin_file ): string {
		$pdfib_plugin_slug = dirname( $plugin_file );

		if ( '.' === $pdfib_plugin_slug || '' === $pdfib_plugin_slug ) {
			$pdfib_plugin_slug = basename( $plugin_file, '.php' );
		}

		return ucwords( str_replace( array( '-', '_' ), ' ', $pdfib_plugin_slug ) );
	}
}

if ( ! function_exists( 'pdfib_template_string_contains_any' ) ) {
	/**
	 * Verifie si un texte contient au moins un mot-cle.
	 *
	 * @param string   $text Texte source.
	 * @param string[] $needles Liste de mots-cles.
	 * @return bool
	 */
	function pdfib_template_string_contains_any( string $text, array $needles ): bool {
		foreach ( $needles as $needle ) {
			if ( '' !== $needle && false !== stripos( $text, $needle ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'pdfib_template_status_matches_any' ) ) {
	/**
	 * Verifie si un slug de statut correspond a un groupe de motifs.
	 *
	 * @param string   $status_key Slug du statut.
	 * @param string[] $patterns Motifs de correspondance.
	 * @return bool
	 */
	function pdfib_template_status_matches_any( string $status_key, array $patterns ): bool {
		foreach ( $patterns as $pattern ) {
			if (
				false !== strpos( $status_key, $pattern )
				|| false !== strpos( $pattern, $status_key )
			) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'pdfib_detect_template_status_plugin' ) ) {
	/**
	 * Tente d'identifier le plugin a l'origine d'un statut personnalise.
	 *
	 * @param string $status_key Cle du statut WooCommerce.
	 * @return string
	 */
	function pdfib_detect_template_status_plugin( string $status_key ): string {
		$pdfib_text_map        = pdfib_get_template_status_text_map();
		$pdfib_clean_key       = str_replace( 'wc-', '', $status_key );
		$pdfib_active_plugins  = pdfib_get_template_active_plugins();
		$pdfib_status_managers = array(
			'woocommerce-order-status-manager'     => 'WooCommerce Order Status Manager',
			'yith-woocommerce-custom-order-status' => 'YITH WooCommerce Custom Order Status',
			'custom-order-status-for-woocommerce'  => 'Custom Order Status for WooCommerce',
			'woo-custom-order-status'              => 'Custom Order Status for WooCommerce',
			'woo-order-status-manager'             => 'WooCommerce Order Status Manager',
		);

		foreach ( $pdfib_active_plugins as $pdfib_plugin_file ) {
			foreach ( $pdfib_status_managers as $pdfib_slug => $pdfib_name ) {
				if ( false !== strpos( $pdfib_plugin_file, $pdfib_slug ) ) {
					return $pdfib_name;
				}
			}
		}

		$pdfib_groups = array(
			array(
				'statuses' => array(
					'shipped',
					'delivered',
					'ready_to_ship',
					'partial_shipment',
					'in_transit',
					'out_for_delivery',
					'shipped_partial',
				),
				'plugins'  => array( 'ship', 'shipping', 'delivery', 'tracking' ),
				'label'    => $pdfib_text_map['shipping'],
			),
			array(
				'statuses' => array( 'packed', 'packing', 'ready_for_pickup', 'prepared' ),
				'plugins'  => array( 'pack', 'prepare', 'pickup', 'fulfillment' ),
				'label'    => $pdfib_text_map['preparation'],
			),
			array(
				'statuses' => array(
					'awaiting_payment',
					'payment_pending',
					'payment_confirmed',
					'payment_failed',
					'payment_cancelled',
				),
				'plugins'  => array( 'payment', 'gateway', 'checkout', 'invoice' ),
				'label'    => $pdfib_text_map['payment'],
			),
			array(
				'statuses' => array( 'return_requested', 'return_approved', 'return_received' ),
				'plugins'  => array( 'return', 'rma', 'exchange' ),
				'label'    => $pdfib_text_map['returns'],
			),
			array(
				'statuses' => array( 'refund_pending', 'refund_issued' ),
				'plugins'  => array( 'refund', 'credit', 'wallet' ),
				'label'    => $pdfib_text_map['refund'],
			),
			array(
				'statuses' => array(
					'vendor_pending',
					'vendor_approved',
					'vendor_rejected',
					'commission_pending',
					'commission_paid',
				),
				'plugins'  => array( 'vendor', 'marketplace', 'commission', 'dokan', 'wcfm' ),
				'label'    => $pdfib_text_map['marketplace'],
			),
		);

		foreach ( $pdfib_groups as $pdfib_group ) {
			if ( ! pdfib_template_status_matches_any( $pdfib_clean_key, $pdfib_group['statuses'] ) ) {
				continue;
			}

			foreach ( $pdfib_active_plugins as $pdfib_plugin_file ) {
				$pdfib_plugin_data = pdfib_get_template_plugin_header_data( $pdfib_plugin_file );
				$pdfib_search_text = strtolower(
					implode(
						' ',
						array_filter(
							array(
								$pdfib_plugin_file,
								$pdfib_plugin_data['Name'] ?? '',
								$pdfib_plugin_data['Description'] ?? '',
							)
						)
					)
				);

				if ( ! pdfib_template_string_contains_any( $pdfib_search_text, $pdfib_group['plugins'] ) ) {
					continue;
				}

				if ( ! empty( $pdfib_plugin_data['Name'] ) ) {
					return $pdfib_plugin_data['Name'];
				}

				return pdfib_format_template_plugin_name( $pdfib_plugin_file );
			}

			return $pdfib_group['label'];
		}

		foreach ( $pdfib_active_plugins as $pdfib_plugin_file ) {
			$pdfib_plugin_data = pdfib_get_template_plugin_header_data( $pdfib_plugin_file );
			$pdfib_search_text = strtolower(
				implode(
					' ',
					array_filter(
						array(
							$pdfib_plugin_file,
							$pdfib_plugin_data['Name'] ?? '',
							$pdfib_plugin_data['Description'] ?? '',
						)
					)
				)
			);

			if ( ! pdfib_template_string_contains_any( $pdfib_search_text, array( 'status', 'order' ) ) ) {
				continue;
			}

			if ( ! empty( $pdfib_plugin_data['Name'] ) ) {
				return $pdfib_plugin_data['Name'];
			}

			return pdfib_format_template_plugin_name( $pdfib_plugin_file );
		}

		return $pdfib_text_map['unknown_plugin'];
	}
}

if ( ! function_exists( 'pdfib_get_template_list_for_statuses' ) ) {
	/**
	 * Retourne la liste des templates disponibles.
	 *
	 * @return array<string, string>
	 */
	function pdfib_get_template_list_for_statuses(): array {
		$pdfib_templates    = array();
		$pdfib_template_ids = get_posts(
			array(
				'post_type'        => 'pdfib_template',
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		foreach ( $pdfib_template_ids as $pdfib_template_id ) {
			$pdfib_templates[ (string) $pdfib_template_id ] = get_the_title( $pdfib_template_id );
		}

		$pdfib_db           = pdfib_db();
		$pdfib_custom_table = $pdfib_db->prefix . 'pdfib_templates';
		$pdfib_table_exists = $pdfib_db->get_var(
			$pdfib_db->prepare( 'SHOW TABLES LIKE %s', $pdfib_custom_table )
		);

		if ( $pdfib_table_exists === $pdfib_custom_table ) {
			$pdfib_custom_rows = $pdfib_db->get_results(
				"SELECT id, name FROM {$pdfib_custom_table} ORDER BY name ASC",
				ARRAY_A
			);

			foreach ( (array) $pdfib_custom_rows as $pdfib_row ) {
				if ( empty( $pdfib_row['id'] ) || empty( $pdfib_row['name'] ) ) {
					continue;
				}

				$pdfib_templates[ 'custom_' . $pdfib_row['id'] ] = $pdfib_row['name'];
			}
		}

		return $pdfib_templates;
	}
}

if ( ! function_exists( 'pdfib_get_template_status_mappings' ) ) {
	/**
	 * Retourne les associations template/statut valides pour l'affichage.
	 *
	 * @param array<string, string> $order_statuses Statuts disponibles.
	 * @return array<string, string>
	 */
	function pdfib_get_template_status_mappings( array $order_statuses ): array {
		$pdfib_raw_mappings = pdfib_get_option(
			'pdfib_order_status_templates',
			array()
		);

		if ( ! is_array( $pdfib_raw_mappings ) ) {
			return array();
		}

		$pdfib_valid_statuses  = array_fill_keys( array_keys( $order_statuses ), true );
		$pdfib_cleaned_mapping = array();

		foreach ( $pdfib_raw_mappings as $pdfib_status_key => $pdfib_template_id ) {
			if ( ! isset( $pdfib_valid_statuses[ $pdfib_status_key ] ) ) {
				continue;
			}

			$pdfib_cleaned_mapping[ $pdfib_status_key ] = trim(
				(string) $pdfib_template_id
			);
		}

		return $pdfib_cleaned_mapping;
	}
}

if ( ! function_exists( 'pdfib_sort_template_statuses' ) ) {
	/**
	 * Trie les statuts pour mettre ceux utilisables en premier en version gratuite.
	 *
	 * @param array<string, string> $order_statuses Statuts disponibles.
	 * @param bool                  $is_premium Indique si Premium est actif.
	 * @return array<string, string>
	 */
	function pdfib_sort_template_statuses(
		array $order_statuses,
		bool $is_premium
	): array {
		if ( $is_premium ) {
			return $order_statuses;
		}

		$pdfib_available_statuses = array();

		foreach ( $order_statuses as $pdfib_status_key => $pdfib_status_label ) {
			$pdfib_is_custom_status = pdfib_is_template_custom_status( $pdfib_status_key );
			$pdfib_is_completed     = ( 'wc-completed' === $pdfib_status_key );

			if ( $pdfib_is_custom_status || $pdfib_is_completed ) {
				$pdfib_available_statuses[ $pdfib_status_key ] = $pdfib_status_label;
			}
		}

		return $pdfib_available_statuses;
	}
}

$pdfib_text_map                 = pdfib_get_template_status_text_map();
$pdfib_is_premium               = pdfib_is_premium_templates_enabled();
$pdfib_woocommerce_active       = function_exists( 'wc_get_order_statuses' )
	|| defined( 'WC_VERSION' )
	|| class_exists( 'WooCommerce' );
$pdfib_order_statuses           = $pdfib_woocommerce_active
	? pdfib_get_template_order_statuses()
	: array();
$pdfib_sorted_statuses          = pdfib_sort_template_statuses(
	$pdfib_order_statuses,
	$pdfib_is_premium
);
$pdfib_templates                = pdfib_get_template_list_for_statuses();
$pdfib_current_mappings         = pdfib_get_template_status_mappings(
	$pdfib_order_statuses
);
$pdfib_has_templates            = ! empty( $pdfib_templates );
$pdfib_premium_locked_message   = __( 'Fonctionnalite reservee aux utilisateurs premium.', 'advanced-pdf-invoice-builder' );
$pdfib_no_template_option_label = __( '-- Aucun template --', 'advanced-pdf-invoice-builder' );
$pdfib_no_template_label        = __( 'Aucun template assigne', 'advanced-pdf-invoice-builder' );
$pdfib_assigned_label           = __( 'Assigne :', 'advanced-pdf-invoice-builder' );
$pdfib_license_manager          = apply_filters( 'pdfib_license_manager_instance', null );
$pdfib_is_pro_active            = function_exists( 'pdfib_is_pro_plugin_active' )
	&& pdfib_is_pro_plugin_active();
$pdfib_license_redirect_url     = ( $pdfib_is_pro_active || is_object( $pdfib_license_manager ) )
	? admin_url( 'admin.php?page=pdf-builder-settings&tab=licence' )
	: admin_url( 'admin.php?page=pdf-builder-pro' );
$pdfib_cards                    = array();
$pdfib_detected_plugins         = array();

foreach ( $pdfib_sorted_statuses as $pdfib_status_key => $pdfib_status_label ) {
	$pdfib_is_custom_status = pdfib_is_template_custom_status( $pdfib_status_key );
	$pdfib_requires_premium = ! $pdfib_is_premium
		&& ! $pdfib_is_custom_status
		&& 'wc-completed' !== $pdfib_status_key;
	$pdfib_plugin_name      = '';

	if ( $pdfib_is_custom_status ) {
		$pdfib_plugin_name = pdfib_detect_template_status_plugin( $pdfib_status_key );

		if ( $pdfib_text_map['unknown_plugin'] !== $pdfib_plugin_name ) {
			$pdfib_detected_plugins[ $pdfib_plugin_name ] = $pdfib_plugin_name;
		}
	}

	$pdfib_current_value = isset( $pdfib_current_mappings[ $pdfib_status_key ] )
		? $pdfib_current_mappings[ $pdfib_status_key ]
		: '';
	$pdfib_current_title = (
		'' !== $pdfib_current_value
		&& isset( $pdfib_templates[ $pdfib_current_value ] )
	)
		? $pdfib_templates[ $pdfib_current_value ]
		: '';
	$pdfib_select_id     = 'template_' . sanitize_html_class( $pdfib_status_key );
	$pdfib_select_title  = '';

	if ( ! $pdfib_has_templates ) {
		$pdfib_select_title = __( 'Aucun template disponible.', 'advanced-pdf-invoice-builder' );
	} elseif ( $pdfib_requires_premium ) {
		$pdfib_select_title = $pdfib_premium_locked_message;
	}

	$pdfib_cards[] = array(
		'status_key'       => $pdfib_status_key,
		'status_label'     => wp_strip_all_tags( (string) $pdfib_status_label ),
		'is_custom_status' => $pdfib_is_custom_status,
		'requires_premium' => $pdfib_requires_premium,
		'plugin_name'      => $pdfib_plugin_name,
		'current_value'    => $pdfib_current_value,
		'current_title'    => $pdfib_current_title,
		'select_id'        => $pdfib_select_id,
		'select_title'     => $pdfib_select_title,
		'is_select_locked' => ( ! $pdfib_has_templates || $pdfib_requires_premium ),
	);
}

$pdfib_plugins_summary = implode( ', ', $pdfib_detected_plugins );
$pdfib_script_config   = array(
	'assignedLabel' => $pdfib_assigned_label,
	'emptyLabel'    => $pdfib_no_template_label,
	'licenceUrl'    => $pdfib_license_redirect_url,
);

?>
<section class="pdfb-templates-status-wrapper">
	<header>
		<h3><?php esc_html_e( 'Templates par statut de commande', 'advanced-pdf-invoice-builder' ); ?></h3>

		<?php if ( '' !== $pdfib_plugins_summary ) : ?>
			<p class="description">
				<?php
				printf(
					/* translators: %s: list of detected plugin names. */
					esc_html__( 'Plugins detectes : %s', 'advanced-pdf-invoice-builder' ),
					esc_html( $pdfib_plugins_summary )
				);
				?>
			</p>
		<?php elseif ( $pdfib_woocommerce_active && ! empty( $pdfib_order_statuses ) ) : ?>
			<p class="description">
				<?php esc_html_e( 'Statuts WooCommerce standards uniquement.', 'advanced-pdf-invoice-builder' ); ?>
			</p>
		<?php endif; ?>

		<?php if ( ! $pdfib_is_premium ) : ?>
			<div class="notice notice-info inline">
				<p>
					<strong><?php esc_html_e( 'Edition gratuite :', 'advanced-pdf-invoice-builder' ); ?></strong>
					<?php esc_html_e( 'L\'assignation automatique est disponible pour les statuts Termine et les statuts personnalises.', 'advanced-pdf-invoice-builder' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</header>

	<main>
		<?php if ( ! $pdfib_woocommerce_active ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'WooCommerce n\'est pas actif.', 'advanced-pdf-invoice-builder' ); ?></strong>
					<?php esc_html_e( 'Installez et activez WooCommerce pour utiliser cette section.', 'advanced-pdf-invoice-builder' ); ?>
				</p>
			</div>
		<?php else : ?>
			<div class="pdfb-templates-status-grid">
				<?php foreach ( $pdfib_cards as $pdfib_card ) : ?>
					<?php
					$pdfib_card_classes = array( 'pdfb-template-status-card' );

					if ( $pdfib_card['is_custom_status'] ) {
						$pdfib_card_classes[] = 'custom-status-card';
					}

					if ( $pdfib_card['requires_premium'] ) {
						$pdfib_card_classes[] = 'premium-card';
					}

					$pdfib_card_class_name = implode( ' ', $pdfib_card_classes );
					?>
					<article class="<?php echo esc_attr( $pdfib_card_class_name ); ?>">
						<header>
							<h4>
								<?php echo esc_html( $pdfib_card['status_label'] ); ?>
								<?php if ( $pdfib_card['is_custom_status'] ) : ?>
									<span
										class="pdfb-custom-status-indicator"
										title="<?php echo esc_attr( $pdfib_card['plugin_name'] ); ?>"
									>
										🔍
									</span>
								<?php endif; ?>
								<?php if ( $pdfib_card['requires_premium'] ) : ?>
										<?php /* Premium badge hidden in FREE edition. */ ?>
								<?php endif; ?>
							</h4>

							<?php if ( $pdfib_card['is_custom_status'] ) : ?>
								<p class="description">
									<?php
									// translators: %s: name of the plugin that registered the custom status.
									printf(
										/* translators: %s: name of the plugin that registered the custom status. */
										esc_html__( 'Statut personnalise detecte depuis : %s', 'advanced-pdf-invoice-builder' ),
										esc_html( $pdfib_card['plugin_name'] )
									);
									?>
								</p>
							<?php endif; ?>
						</header>

						<div class="pdfb-template-selector">
							<label for="<?php echo esc_attr( $pdfib_card['select_id'] ); ?>">
								<?php esc_html_e( 'Template par defaut', 'advanced-pdf-invoice-builder' ); ?>
							</label>
							<select
								name="pdfib_settings[pdfib_order_status_templates][<?php echo esc_attr( $pdfib_card['status_key'] ); ?>]"
								id="<?php echo esc_attr( $pdfib_card['select_id'] ); ?>"
								class="pdfb-template-select"
								data-status-key="<?php echo esc_attr( $pdfib_card['status_key'] ); ?>"
								<?php disabled( $pdfib_card['is_select_locked'] ); ?>
								<?php if ( '' !== $pdfib_card['select_title'] ) : ?>
									title="<?php echo esc_attr( $pdfib_card['select_title'] ); ?>"
								<?php endif; ?>
							>
								<option value="">
									<?php echo esc_html( $pdfib_no_template_option_label ); ?>
								</option>

								<?php if ( $pdfib_has_templates ) : ?>
									<?php foreach ( $pdfib_templates as $pdfib_template_id => $pdfib_template_title ) : ?>
										<option
											value="<?php echo esc_attr( $pdfib_template_id ); ?>"
											<?php selected( $pdfib_card['current_value'], (string) $pdfib_template_id ); ?>
										>
											<?php echo esc_html( $pdfib_template_title ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>

						<div class="pdfb-template-preview">
							<?php if ( '' !== $pdfib_card['current_title'] ) : ?>
								<p class="pdfb-current-template">
									<?php echo esc_html( $pdfib_assigned_label . ' ' . $pdfib_card['current_title'] ); ?>
								</p>
							<?php else : ?>
								<p class="pdfb-no-template">
									<?php echo esc_html( $pdfib_no_template_label ); ?>
								</p>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<section class="pdfb-templates-status-actions">
				<button
					type="button"
					id="pdfib-reset-templates-status"
					class="button button-secondary"
				>
					<?php esc_html_e( 'Reinitialiser les parametres', 'advanced-pdf-invoice-builder' ); ?>
				</button>
				<p class="description">
					<?php esc_html_e( 'Utilisez le bouton Enregistrer flottant pour sauvegarder vos modifications.', 'advanced-pdf-invoice-builder' ); ?>
				</p>
			</section>
		<?php endif; ?>
	</main>
</section>

<?php
$pdfib_status_js_file = PDFIB_PLUGIN_DIR . 'assets/js/pdfib-template-status.js';
$pdfib_status_js      = file_exists( $pdfib_status_js_file )
	? (string) pdfib_filesystem()->get_contents( $pdfib_status_js_file )
	: '';
$pdfib_script         = 'window.pdfibTemplateStatusConfig = '
	. wp_json_encode( $pdfib_script_config )
	. ';'
	. $pdfib_status_js;

wp_print_inline_script_tag( $pdfib_script );

