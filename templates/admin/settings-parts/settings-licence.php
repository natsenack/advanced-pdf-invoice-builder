<?php
/**
 * Advanced PDF Invoice Builder
 *
 * License settings tab template.
 *
 * PHP version 8.2
 *
 * @category Plugin
 * @package  PDFIB
 * @author   PDF Invoice Builder <support@threeaxe.fr>
 * @license  GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://threeaxe.fr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

if ( ! function_exists( 'pdfib_get_licence_setting_value' ) ) {
	/**
	 * Retourne une valeur de reglage licence avec fallback option simple.
	 *
	 * @param array<string, mixed> $settings Tableau de reglages.
	 * @param string               $key      Cle recherchee.
	 * @param string               $fallback Valeur par defaut.
	 * @return string
	 */
	function pdfib_get_licence_setting_value( array $settings, string $key, string $fallback = '' ): string {
		if ( isset( $settings[ $key ] ) && '' !== (string) $settings[ $key ] ) {
			return (string) $settings[ $key ];
		}

		$pdfib_option_value = pdfib_get_option( $key, $fallback );

		return is_string( $pdfib_option_value ) ? $pdfib_option_value : $fallback;
	}
}

if ( ! function_exists( 'pdfib_mask_licence_key' ) ) {
	/**
	 * Masque une cle de licence pour l'affichage.
	 *
	 * @param string $license_key Cle brute.
	 * @return string
	 */
	function pdfib_mask_licence_key( string $license_key ): string {
		$pdfib_length = strlen( $license_key );

		if ( $pdfib_length <= 8 ) {
			return $license_key;
		}

		return substr( $license_key, 0, 5 )
			. str_repeat( '*', max( 4, $pdfib_length - 9 ) )
			. substr( $license_key, -4 );
	}
}

if ( ! function_exists( 'pdfib_format_licence_date' ) ) {
	/**
	 * Formate une date de licence pour l'interface admin.
	 *
	 * @param int  $timestamp   Timestamp Unix.
	 * @param bool $is_lifetime Indique si la licence est a vie.
	 * @return string
	 */
	function pdfib_format_licence_date( int $timestamp, bool $is_lifetime ): string {
		if ( $is_lifetime ) {
			return __( 'A vie', 'advanced-pdf-invoice-builder' );
		}

		if ( $timestamp <= 0 ) {
			return '—';
		}

		return wp_date(
			get_option( 'date_format', 'd/m/Y' ),
			$timestamp
		);
	}
}

$pdfib_settings = pdfib_get_option( 'pdfib_settings', array() );

$pdfib_license_status     = pdfib_get_licence_setting_value(
	$pdfib_settings,
	'pdfib_license_status'
);
$pdfib_stored_license_key = pdfib_get_licence_setting_value(
	$pdfib_settings,
	'pdfib_license_key'
);
$pdfib_license_manager    = null;
$pdfib_is_premium         = false;

// Permet au PRO d'injecter le LicenseManager si disponible.
$pdfib_license_manager = apply_filters( 'pdfib_license_manager_instance', $pdfib_license_manager );

if ( ! is_object( $pdfib_license_manager ) && class_exists( '\PDFIB\Managers\PdfBuilderLicenseManager' ) ) {
	$pdfib_license_manager = \PDFIB\Managers\PdfBuilderLicenseManager::get_instance();
}

if ( null !== $pdfib_license_manager ) {
	$pdfib_is_premium = (bool) $pdfib_license_manager->is_premium();
}

$pdfib_is_active_license = $pdfib_is_premium;

$pdfib_public_license_key = $pdfib_is_premium && null !== $pdfib_license_manager
	? (string) $pdfib_license_manager->get_license_key_for_links()
	: $pdfib_stored_license_key;
$pdfib_license_id         = $pdfib_is_premium && null !== $pdfib_license_manager
	? (string) $pdfib_license_manager->get_license_id()
	: '';
$pdfib_display_key        = $pdfib_public_license_key;
$pdfib_masked_license_key = '' !== $pdfib_display_key
	? pdfib_mask_licence_key( $pdfib_display_key )
	: '';

$pdfib_license_data        = $pdfib_is_premium
	? pdfib_get_option( 'pdfib_license_data', array() )
	: array();
$pdfib_license_customer    = isset( $pdfib_license_data['customer'] )
	? (string) $pdfib_license_data['customer']
	: '';
$pdfib_license_email       = isset( $pdfib_license_data['email'] )
	? (string) $pdfib_license_data['email']
	: '';
$pdfib_license_activations = isset( $pdfib_license_data['activations'] )
	? (int) $pdfib_license_data['activations']
	: null;
$pdfib_license_expires_raw = isset( $pdfib_license_data['expires_raw'] )
	? (string) $pdfib_license_data['expires_raw']
	: (string) pdfib_get_option( 'pdfib_license_expires', '' );

$pdfib_license_is_lifetime = 'lifetime' === strtolower( $pdfib_license_expires_raw );
$pdfib_license_expires_ts  = 0;

if ( ! $pdfib_license_is_lifetime && '' !== $pdfib_license_expires_raw ) {
	$pdfib_parsed_expires = strtotime( $pdfib_license_expires_raw );

	if ( false !== $pdfib_parsed_expires ) {
		$pdfib_license_expires_ts = (int) $pdfib_parsed_expires;
	}
}

$pdfib_license_days_left = null;

if ( $pdfib_license_expires_ts > 0 ) {
	$pdfib_license_days_left = (int) floor(
		( $pdfib_license_expires_ts - time() ) / DAY_IN_SECONDS
	);
}

$pdfib_license_expired       = null !== $pdfib_license_days_left
	&& $pdfib_license_days_left < 0;
$pdfib_license_expiring_soon = null !== $pdfib_license_days_left
	&& $pdfib_license_days_left >= 0
	&& $pdfib_license_days_left <= 30;
$pdfib_license_expires_label = pdfib_format_licence_date(
	$pdfib_license_expires_ts,
	$pdfib_license_is_lifetime
);

if ( $pdfib_license_is_lifetime ) {
	$pdfib_days_left_label = __( 'A vie', 'advanced-pdf-invoice-builder' );
	$pdfib_days_left_class = 'license-days-ok';
} elseif ( null === $pdfib_license_days_left ) {
	$pdfib_days_left_label = '—';
	$pdfib_days_left_class = 'license-days-neutral';
} elseif ( $pdfib_license_days_left < 0 ) {
	$pdfib_days_left_label = sprintf(
		/* translators: %d: number of days since expiry. */
		__( 'Expiree depuis %d jours', 'advanced-pdf-invoice-builder' ),
		abs( $pdfib_license_days_left )
	);
	$pdfib_days_left_class = 'license-days-error';
} elseif ( $pdfib_license_days_left <= 14 ) {
	$pdfib_days_left_label = sprintf(
		/* translators: %d: number of days remaining. */
		__( '%d jours restants', 'advanced-pdf-invoice-builder' ),
		$pdfib_license_days_left
	);
	$pdfib_days_left_class = 'license-days-error';
} elseif ( $pdfib_license_days_left <= 60 ) {
	$pdfib_days_left_label = sprintf(
		/* translators: %d: number of days remaining. */
		__( '%d jours restants', 'advanced-pdf-invoice-builder' ),
		$pdfib_license_days_left
	);
	$pdfib_days_left_class = 'license-days-warning';
} else {
	$pdfib_days_left_label = sprintf(
		/* translators: %d: number of days remaining. */
		__( '%d jours restants', 'advanced-pdf-invoice-builder' ),
		$pdfib_license_days_left
	);
	$pdfib_days_left_class = 'license-days-ok';
}

$pdfib_license_badge_class = $pdfib_is_active_license
	? 'badge-premium'
	: 'badge-free';

if ( $pdfib_is_premium ) {
	$pdfib_license_badge_text = __( 'PRO', 'advanced-pdf-invoice-builder' );
} else {
	$pdfib_license_badge_text = __( 'Version gratuite', 'advanced-pdf-invoice-builder' );
}

$pdfib_activate_button_text      = $pdfib_is_active_license
	? __( 'Changer', 'advanced-pdf-invoice-builder' )
	: __( 'Activer', 'advanced-pdf-invoice-builder' );
$pdfib_activate_title            = $pdfib_is_active_license
	? __( 'Remplacer la licence actuelle', 'advanced-pdf-invoice-builder' )
	: __( 'Activer une licence PRO', 'advanced-pdf-invoice-builder' );
$pdfib_activate_help             = $pdfib_is_active_license
	? __( 'Une nouvelle cle remplacera la licence actuelle.', 'advanced-pdf-invoice-builder' )
	: __( 'Saisissez votre cle pour activer l\'extension PRO.', 'advanced-pdf-invoice-builder' );
$pdfib_license_input_placeholder = '' !== $pdfib_masked_license_key
	? $pdfib_masked_license_key
	: __( 'Entrez votre cle de licence PRO', 'advanced-pdf-invoice-builder' );

$pdfib_purchase_url    = 'https://hub.threeaxe.fr/nos-produits/pdf-builder-pro-2';
$pdfib_support_url     = 'https://hub.threeaxe.fr/index.php/ticket/';
$pdfib_docs_url        = 'https://github.com/natsenack/wp-pdf-builder-pro';
$pdfib_renewal_url     = '' !== $pdfib_public_license_key
	? 'https://hub.threeaxe.fr/index.php/checkout/?edd_license_key='
		. rawurlencode( $pdfib_public_license_key )
		. '&download_id=19'
	: $pdfib_purchase_url;
$pdfib_unsubscribe_url = 'https://hub.threeaxe.fr?edd_action=license_unsubscribe'
	. ( '' !== $pdfib_license_id
		? '&license_id=' . rawurlencode( $pdfib_license_id )
		: '' )
	. ( '' !== $pdfib_public_license_key
		? '&license_key=' . rawurlencode( $pdfib_public_license_key )
		: '' );

$pdfib_email_reminders_enabled = isset(
	$pdfib_settings['pdfib_license_email_reminders']
) && '1' === (string) $pdfib_settings['pdfib_license_email_reminders'];
$pdfib_reminder_email          = isset( $pdfib_settings['pdfib_license_reminder_email'] )
	? (string) $pdfib_settings['pdfib_license_reminder_email']
	: '';

$pdfib_license_tab_script = array(
	'purchaseUrl' => $pdfib_purchase_url,
	'copySuccess' => __( 'Cle copiee', 'advanced-pdf-invoice-builder' ),
	'copyError'   => __( 'Copie impossible', 'advanced-pdf-invoice-builder' ),
);

$pdfib_json_flags   = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$pdfib_licence_data = array(
	'isPremium'             => $pdfib_is_premium,
	'isActive'              => $pdfib_is_active_license,
	'badgeClass'            => $pdfib_license_badge_class,
	'badgeText'             => $pdfib_license_badge_text,
	'maskedKey'             => $pdfib_masked_license_key,
	'displayKey'            => $pdfib_display_key,
	'licenseStatus'         => $pdfib_license_status,
	'licenseCustomer'       => $pdfib_license_customer,
	'licenseEmail'          => $pdfib_license_email,
	'licenseActivations'    => $pdfib_license_activations,
	'expiresLabel'          => $pdfib_license_expires_label,
	'daysLeftLabel'         => $pdfib_days_left_label,
	'daysLeftClass'         => $pdfib_days_left_class,
	'expired'               => $pdfib_license_expired,
	'expiringSoon'          => $pdfib_license_expiring_soon,
	'isLifetime'            => $pdfib_license_is_lifetime,
	'activateButtonText'    => $pdfib_activate_button_text,
	'activateTitle'         => $pdfib_activate_title,
	'activateHelp'          => $pdfib_activate_help,
	'inputPlaceholder'      => $pdfib_license_input_placeholder,
	'purchaseUrl'           => $pdfib_purchase_url,
	'supportUrl'            => $pdfib_support_url,
	'docsUrl'               => $pdfib_docs_url,
	'renewalUrl'            => $pdfib_renewal_url,
	'unsubscribeUrl'        => $pdfib_unsubscribe_url,
	'emailRemindersEnabled' => $pdfib_email_reminders_enabled,
	'reminderEmail'         => $pdfib_reminder_email,
	'adminEmail'            => (string) get_option( 'admin_email', '' ),
	'siteUrl'               => (string) home_url(),
);

// Hook pour que le PRO puisse modifier les données avant le rendu.
$pdfib_licence_data['isProInstalled'] = null !== $pdfib_license_manager;
$pdfib_licence_data                   = apply_filters( 'pdfib_license_tab_data', $pdfib_licence_data );
?>
<?php wp_print_inline_script_tag( 'window.pdfibLicenceData = ' . wp_json_encode( $pdfib_licence_data, $pdfib_json_flags ) . ';' ); ?>
<div id="pdfib-licence-root"></div>


