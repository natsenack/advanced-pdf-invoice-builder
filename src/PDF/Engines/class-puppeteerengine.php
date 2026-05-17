<?php
/**
 * PuppeteerEngine - Moteur PDF utilisant PuppeteerClient (service distant threeaxe.fr).
 * Toute la logique HTTP / HMAC est déléguée à PuppeteerClient.
 *
 * @package PDF_Builder_Pro
 * @subpackage PDF\Engines
 */

namespace PDFIB\PDF\Engines;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Moteur PDF basé sur le service Puppeteer distant.
 */
class PuppeteerEngine implements PDFEngineInterface {

	/**
	 * Client HTTP HMAC.
	 *
	 * @var PuppeteerClient
	 */
	private PuppeteerClient $client;

	/**
	 * Indique si le debug est activé.
	 *
	 * @var bool
	 */
	private bool $debug_enabled;

	/**
	 * Initialise le moteur Puppeteer.
	 *
	 * @param array $config Ignoré, conservé pour compatibilité avec les anciens appels.
	 * @phpstan-ignore constructor.unusedParameter
	 */
	public function __construct( $config = array() ) {
		unset( $config );
		$this->client        = new PuppeteerClient();
		$this->debug_enabled = (bool) pdfib_get_option( 'pdfib_debug_enabled', false );
	}


	/**
	 * Génère un PDF à partir de HTML.
	 *
	 * @param string $html Contenu HTML.
	 * @param array  $options Options de rendu.
	 * @return string|false
	 */
	public function generate( $html, $options = array() ) {
		$this->log( '========== GÉNÉRATION PDF (PuppeteerEngine v2) ==========' );
		$this->log( 'HTML size : ' . strlen( $html ) . ' octets' );

		$format        = $this->resolve_format( $options );
		$license_key   = $this->get_license_key();
		$site_url      = get_site_url();
		$request_token = isset( $options['request_token'] ) ? (string) $options['request_token'] : '';

		$this->log( 'format=' . $format . '  license=' . ( '' !== $license_key ? 'yes' : 'no' ) . ( '' !== $request_token ? '  token=' . $request_token : '' ) );

		try {
			$pdf = $this->client->render( $html, $format, $license_key, $site_url, $request_token );
			$this->log( 'PDF généré – ' . strlen( $pdf ) . ' octets' );
			return $pdf;
		} catch ( \Exception $exception ) {
			$this->log( 'Erreur : ' . $exception->getMessage(), 'ERROR' );
			return false;
		}
	}

	/**
	 * Retourne le nom du moteur.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Puppeteer';
	}

	/**
	 * Vérifie si le service distant est joignable.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->client->is_available();
	}

	/**
	 * Génère une image (PNG/JPG) à partir de HTML.
	 *
	 * This stub method satisfies the PDFEngineInterface contract.
	 *
	 * @param string $html Contenu HTML.
	 * @param array  $options Options [format => 'png'|'jpg'].
	 * @return never
	 * @throws \Exception Always - image export is not exposed in this build.
	 */
	public function generate_image( $html, $options = array() ): never {
		unset( $html, $options );
		throw new \Exception(
			esc_html__( 'Image export is not available in this build.', 'advanced-pdf-invoice-builder' )
		);
	}

	/**
	 * Teste la connexion au service.
	 *
	 * @return array{success: bool, message: string, response_time?: int}
	 */
	public function test_connection(): array {
		$start = microtime( true );

		try {
			$available = $this->client->is_available();
			$ms        = (int) round( ( microtime( true ) - $start ) * 1000 );

			if ( $available ) {
				return array(
					'success'       => true,
					'message'       => 'Service Puppeteer joignable.',
					'response_time' => $ms,
				);
			}

			return array(
				'success' => false,
				'message' => 'Service non disponible.',
			);
		} catch ( \Exception $exception ) {
			return array(
				'success' => false,
				'message' => $exception->getMessage(),
			);
		}
	}

	/**
	 * Déduit le format papier depuis les options.
	 *
	 * @param array $options Options de rendu.
	 * @return string
	 */
	private function resolve_format( array $options ): string {
		if ( ! empty( $options['format'] ) && is_string( $options['format'] ) ) {
			return strtoupper( $options['format'] );
		}

		$width  = (int) ( $options['width'] ?? 794 );
		$height = (int) ( $options['height'] ?? 1123 );

		if ( $width >= 1100 && $height >= 1550 ) {
			return 'A3';
		}

		return 'A4';
	}

	/**
	 * Récupère la clé de licence EDD active.
	 * FREE edition always returns empty string.
	 *
	 * @return string
	 */
	private function get_license_key(): string {
		return '';
	}

	/**
	 * Log interne.
	 *
	 * @param string $message Message à journaliser.
	 * @param string $level Niveau de journalisation.
	 */
	private function log( string $message, string $level = 'INFO' ): void {
		$wp_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		if ( $this->debug_enabled || 'ERROR' === $level || $wp_debug ) {
			pdfib_debug_log( '[PuppeteerEngine ' . $level . '] ' . $message );
		}
	}
}
