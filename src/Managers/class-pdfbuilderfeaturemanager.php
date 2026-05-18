<?php
/**
 * Gestion des fonctionnalités freemium.
 *
 * @package PDFIB\Managers
 */

namespace PDFIB\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

/**
 * Gère les fonctionnalités disponibles selon la licence et l'usage.
 */
class PdfBuilderFeatureManager {

	/**
	 * Définition des fonctionnalités et de leurs restrictions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static $features = array(
		'basic_templates'         => array(
			'free'        => true,
			'premium'     => true,
			'name'        => 'Templates de base',
			'description' => '4 templates prédéfinis (Facture, Devis, Reçu, Autre)',
		),
		'basic_elements'          => array(
			'free'        => true,
			'premium'     => true,
			'name'        => 'Éléments standards',
			'description' => 'Texte, image, ligne, rectangle',
		),
		'woocommerce_integration' => array(
			'free'        => true,
			'premium'     => true,
			'name'        => 'Intégration WooCommerce',
			'description' => 'Variables de commande et produit',
		),
		'pdf_generation'          => array(
			'free'        => true,
			'premium'     => true,
			'name'        => 'Génération PDF',
			'description' => 'Création de documents PDF',
		),
		'advanced_templates'      => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Templates avancés',
			'description' => 'Galerie de templates et modèles supplémentaires',
		),
		'premium_elements'        => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Éléments avancés',
			'description' => 'Éléments avancés de mise en page et de contenu',
		),
		'bulk_generation'         => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Génération en masse',
			'description' => 'Création multiple de PDFs',
		),
		'api_access'              => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'API développeur',
			'description' => 'Accès complet à l\'API REST',
		),
		'white_label'             => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'White-label',
			'description' => 'Rebranding et personnalisation complète',
		),
		'multi_format_export'     => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Export multi-format',
			'description' => 'PDF, PNG, JPG',
		),
		'priority_support'        => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Support prioritaire',
			'description' => 'Support 24/7 avec SLA garanti',
		),
		'advanced_analytics'      => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Analytics avancés',
			'description' => 'Tableaux de bord détaillés et rapports',
		),
		'high_dpi'                => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Résolutions élevées',
			'description' => 'DPI 300 et 600 pour haute qualité',
		),
		'extended_formats'        => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Formats étendus',
			'description' => 'A3, Letter, Legal, Étiquettes',
		),
		'custom_colors'           => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Couleurs personnalisées',
			'description' => 'Fond et bordures du canvas',
		),
		'grid_navigation'         => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Navigation grille et guides',
			'description' => 'Grille, guides et accrochage magnétique',
		),
		'advanced_selection'      => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Modes de sélection avancés',
			'description' => 'Sélection multiple et par groupe',
		),
		'keyboard_shortcuts'      => array(
			'free'        => false,
			'premium'     => true,
			'name'        => 'Raccourcis clavier',
			'description' => 'Raccourcis clavier pour navigation rapide',
		),
	);

	/**
	 * Vérifie les limites d'usage pour les utilisateurs free.
	 *
	 * @param string $feature_name Nom de la fonctionnalité.
	 * @param int    $limit Limite autorisée.
	 * @return bool
	 */
	private static function check_usage_limit( string $feature_name, int $limit ): bool {
		$usage_key     = 'pdfib_usage_' . $feature_name;
		$current_usage = get_option( $usage_key, 0 );
		$reset_time    = get_option( $usage_key . '_reset', 0 );

		$month_start = strtotime( 'first day of this month' );

		if ( $reset_time < $month_start ) {
			update_option( $usage_key, 0 );
			update_option( $usage_key . '_reset', $month_start );
			$current_usage = 0;
		}

		return $current_usage < $limit;
	}

	/**
	 * Incrémente le compteur d'usage.
	 *
	 * @param string $feature_name Nom de la fonctionnalité.
	 * @return bool
	 */
	public static function increment_usage( string $feature_name ): bool {
		if ( ! isset( self::$features[ $feature_name ] ) ) {
			return false;
		}

		// FREE edition: always allowed.
		return true;
	}

	/**
	 * Retourne l'usage actuel d'une fonctionnalité.
	 *
	 * @param string $feature_name Nom de la fonctionnalité.
	 * @return int
	 */
	public static function get_current_usage( string $feature_name ): int {
		if ( ! isset( self::$features[ $feature_name ] ) ) {
			return 0;
		}

		return (int) pdfib_get_option( 'pdfib_usage_' . $feature_name, 0 );
	}

	/**
	 * Retourne la limite d'une fonctionnalité.
	 *
	 * @param string $feature_name Nom de la fonctionnalité.
	 * @return int
	 */
	public static function get_feature_limit( string $feature_name ): int {
		if ( ! isset( self::$features[ $feature_name ] ) || ! isset( self::$features[ $feature_name ]['limit'] ) ) {
			return -1; // Pas de limite.
		}

		return (int) self::$features[ $feature_name ]['limit'];
	}

	/**
	 * Retourne toutes les fonctionnalités.
	 *
	 * @return array
	 */
	public static function get_all_features(): array {
		return self::$features;
	}

	/**
	 * Retourne les fonctionnalités disponibles pour l'utilisateur actuel.
	 *
	 * @return array
	 */
	public static function get_available_features(): array {
		// FREE edition: always false.
		$is_premium         = false;
		$available_features = array();

		foreach ( self::$features as $key => $feature ) {
			$can_use = $is_premium ? $feature['premium'] : $feature['free'];

			if ( $can_use && ! $is_premium && isset( $feature['limit'] ) ) {
				$can_use = self::check_usage_limit( $key, $feature['limit'] );
			}

			if ( $can_use ) {
				$available_features[ $key ] = $feature;
			}
		}

		return $available_features;
	}

	/**
	 * Retourne les fonctionnalités premium.
	 *
	 * @return array
	 */
	public static function get_premium_features(): array {
		$premium_features = array();

		foreach ( self::$features as $key => $feature ) {
			if ( ! $feature['free'] && $feature['premium'] ) {
				$premium_features[ $key ] = $feature;
			}
		}

		return $premium_features;
	}

	/**
	 * Vérifie si une fonctionnalité est premium.
	 *
	 * @param string $feature_name Nom de la fonctionnalité.
	 * @return bool
	 */
	public static function is_premium_feature( string $feature_name ): bool {
		if ( ! isset( self::$features[ $feature_name ] ) ) {
			return false;
		}

		return ! self::$features[ $feature_name ]['free'] && self::$features[ $feature_name ]['premium'];
	}

	/**
	 * Retourne les détails d'une fonctionnalité.
	 *
	 * @param string $feature_name Nom de la fonctionnalité.
	 * @return array|null
	 */
	public static function get_feature_details( string $feature_name ): ?array {
		if ( ! isset( self::$features[ $feature_name ] ) ) {
			return null;
		}

		$feature = self::$features[ $feature_name ];
		// FREE edition: always false.
		$is_premium = false;

		return array(
			'name'          => $feature['name'],
			'description'   => $feature['description'],
			'isPremium'     => self::is_premium_feature( $feature_name ),
			'can_use'       => self::can_use_feature( $feature_name ),
			'current_usage' => self::get_current_usage( $feature_name ),
			'limit'         => self::get_feature_limit( $feature_name ),
			'isAvailable'   => $is_premium ? $feature['premium'] : $feature['free'],
		);
	}

	/**
	 * Vérifie si une fonctionnalité peut être utilisée.
	 *
	 * @param string $feature_name Nom de la fonctionnalité.
	 * @return bool
	 */
	public static function can_use_feature( string $feature_name ): bool {
		// FREE edition: always false unless overridden by PRO via filter.
		$can_use = false;

		if ( isset( self::$features[ $feature_name ] ) ) {
			$feature = self::$features[ $feature_name ];

			if ( isset( $feature['limit'] ) ) {
				$can_use = self::check_usage_limit( $feature_name, $feature['limit'] );
			} else {
				$can_use = $feature['free'];
			}
		}

		/**
		 * Filters whether a feature can be used.
		 * The PRO plugin hooks here to unlock features for active licenses.
		 *
		 * @param bool   $can_use      Whether the feature can be used.
		 * @param string $feature_name The feature name.
		 */
		return (bool) apply_filters( 'pdfib_can_use_feature', $can_use, $feature_name );
	}
}
