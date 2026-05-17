<?php
/**
 * Factory pour créer des moteurs de génération PDF
 *
 * Seul PuppeteerEngine (service threeaxe.fr) est utilisé.
 *
 * @package PDF_Builder_Pro
 * @subpackage PDF\Engines
 * @version 2.0.0
 */

namespace PDFIB\PDF\Engines;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Factory des moteurs PDF.
 */
class PDFEngineFactory {

	/**
	 * Instance du moteur unique.
	 *
	 * @var PDFEngineInterface|null
	 */
	private static $engine_instance = null;

	/**
	 * Crée une instance de moteur PDF.
	 *
	 * @return PDFEngineInterface
	 */
	public static function create(): PDFEngineInterface {
		return new PuppeteerEngine();
	}

	/**
	 * Retourne le moteur singleton.
	 *
	 * @return PDFEngineInterface
	 */
	public static function get_instance(): PDFEngineInterface {
		if ( null === self::$engine_instance ) {
			self::$engine_instance = self::create();
		}
		return self::$engine_instance;
	}

	/**
	 * Réinitialise l'instance singleton.
	 */
	public static function reset_instance(): void {
		self::$engine_instance = null;
	}

	/**
	 * Liste les moteurs disponibles.
	 *
	 * @return array
	 */
	public static function list_available_engines(): array {
		$puppeteer = new PuppeteerEngine();
		return array(
			'puppeteer' => array(
				'name'        => 'Puppeteer',
				'available'   => $puppeteer->is_available(),
				'description' => 'Service Puppeteer distant (threeaxe.fr) — PDF haute fidélité',
			),
		);
	}

	/**
	 * Teste le moteur Puppeteer et retourne le résultat.
	 *
	 * @return array
	 */
	public static function test_engines(): array {
		$puppeteer = new PuppeteerEngine();
		$result    = $puppeteer->test_connection();

		return array(
			'puppeteer' => array(
				'name'          => 'Puppeteer',
				'success'       => $result['success'],
				'message'       => $result['message'],
				'response_time' => $result['response_time'] ?? null,
			),
		);
	}

	/**
	 * Alias de test_engines() pour la compatibilité.
	 *
	 * @return array
	 */
	public static function test_all_engines(): array {
		return self::test_engines();
	}

	/**
	 * Retourne des informations sur les moteurs (pour les pages de diagnostic).
	 *
	 * @return array
	 */
	public static function get_engines_status(): array {
		return self::list_available_engines();
	}
}
