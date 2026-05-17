<?php
/**
 * PuppeteerClient - Client HMAC pour le service PDF distant threeaxe.fr.
 *
 * Protocole :
 *   POST /v2/render  { html, format, license_key, site_url }
 *   Headers : X-Pup-Timestamp, X-Pup-Nonce, X-Pup-Signature
 *
 *   HTTP 200 => PDF binaire direct
 *   HTTP 202 => { job_id } => polling GET /v2/jobs/{job_id}/result (max 30 s)
 *
 * @package PDF_Builder_Pro
 * @subpackage PDF\Engines
 * @version 2.0.0
 */

namespace PDFIB\PDF\Engines;

use PDFIB\Exception\PuppeteerException;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Client HTTP HMAC du service Puppeteer distant.
 */
class PuppeteerClient {

	const SERVICE_BASE_URL = 'https://pdf.threeaxe.fr';
	const HMAC_DEFAULT_KEY = '04abca0b6fb5a01f8854daecd90fdfe709df2e6c446cf328986b5d952a0ac27e';
	const POLL_INTERVAL_S  = 1;
	const POLL_MAX_WAIT_S  = 30;
	const UNIT_OCTETS      = ' octets';
	const UA_PREFIX        = 'PDF-Builder-Pro/';
	const HTTP_OK          = 200;
	const HTTP_ACCEPTED    = 202;
	const HTTP_CONFLICT    = 409;

	/**
	 * Initialise le client.
	 */
	public function __construct() {
	}

	/**
	 * Retourne la clé HMAC depuis les options WP (avec fallback).
	 *
	 * @return string
	 */
	private function get_hmac_key(): string {
		return (string) get_option( 'pdfib_puppeteer_hmac_key', self::HMAC_DEFAULT_KEY );
	}

	/**
	 * Détermine si la vérification SSL doit être activée.
	 *
	 * @return bool True si la vérification SSL doit être active.
	 */
	private function should_verify_ssl(): bool {
		if ( defined( 'PDFIB_SSL_VERIFY' ) ) {
			return (bool) PDFIB_SSL_VERIFY;
		}

		$site_url = strtolower( get_site_url() );
		$is_local = strpos( $site_url, 'localhost' ) !== false
			|| strpos( $site_url, '127.0.0.1' ) !== false
			|| strpos( $site_url, '.local' ) !== false
			|| strpos( $site_url, '.test' ) !== false
			|| strpos( $site_url, '.dev' ) !== false;

		return ! $is_local;
	}

	/**
	 * Génère un PDF via le service distant.
	 *
	 * @param string $html Contenu HTML à convertir.
	 * @param string $format Format papier.
	 * @param string $license_key Clé de licence EDD.
	 * @param string $site_url URL du site WordPress.
	 * @param string $request_token Token de file d'attente.
	 * @return string
	 * @throws PuppeteerException En cas d'erreur ou de timeout.
	 */
	public function render( string $html, string $format = 'A4', string $license_key = '', string $site_url = '', string $request_token = '' ): string {
		$body_data = array(
			'html'     => $html,
			'format'   => 'pdf',
			'options'  => array(
				'format'          => $format,
				'printBackground' => true,
			),
			'site_url' => '' !== $site_url ? $site_url : get_site_url(),
		);

		if ( '' !== $license_key ) {
			$body_data['license_key'] = $license_key;
		}

		$body = (string) wp_json_encode( $body_data );
		$this->log( 'render() → POST /v2/render (license=' . ( '' !== $license_key ? 'yes' : 'no' ) . ', format=' . $format . ')' );

		[ $status, $response_body, $response_headers ] = $this->http_post( '/v2/render', $body );

		if ( self::HTTP_OK === $status ) {
			$tier   = $response_headers['x-pup-tier'] ?? 'unknown';
			$job_id = $response_headers['x-pup-job-id'] ?? '';
			$this->log( 'Rendu synchrone OK - tier=' . $tier . ' job_id=' . $job_id . ' ' . strlen( $response_body ) . self::UNIT_OCTETS );
			return $response_body;
		}

		if ( self::HTTP_ACCEPTED === $status ) {
			$data = json_decode( $response_body, true );
			if ( empty( $data['job_id'] ) ) {
				throw new PuppeteerException( 'Service 202 sans job_id dans la réponse.' );
			}

			return $this->poll_job( (string) $data['job_id'], $request_token );
		}

		throw new PuppeteerException( esc_html( 'Service PDF - HTTP ' . intval( $status ) . ' : ' . substr( $response_body, 0, 500 ) ) );
	}

	/**
	 * Génère une image PNG ou JPG directement via le service Puppeteer.
	 *
	 * @param string $html Contenu HTML.
	 * @param string $format Format image.
	 * @param int    $width   Largeur en pixels.
	 * @param int    $height  Hauteur en pixels.
	 * @param int    $quality Qualité JPEG.
	 * @param string $license_key Clé de licence EDD.
	 * @param string $site_url URL du site WordPress.
	 * @return string
	 * @throws PuppeteerException En cas d'erreur ou de timeout.
	 */
	public function render_image( string $html, string $format = 'png', int $width = 794, int $height = 1123, int $quality = 90, string $license_key = '', string $site_url = '' ): string {
		$format_lower = 'jpg' === strtolower( $format ) ? 'jpg' : 'png';
		$options      = array(
			'width'           => $width,
			'height'          => $height,
			'printBackground' => true,
		);

		if ( 'jpg' === $format_lower ) {
			$options['quality'] = $quality;
		}

		$body_data = array(
			'html'     => $html,
			'format'   => $format_lower,
			'options'  => $options,
			'site_url' => '' !== $site_url ? $site_url : get_site_url(),
		);

		if ( '' !== $license_key ) {
			$body_data['license_key'] = $license_key;
		}

		$body = (string) wp_json_encode( $body_data );
		$this->log( 'render_image() → POST /v2/render format=' . $format_lower . ' ' . $width . 'x' . $height . ' quality=' . $quality );

		[ $status, $response_body ] = $this->http_post( '/v2/render', $body );

		if ( self::HTTP_OK === $status ) {
			$this->log( 'Image synchrone OK - ' . strlen( $response_body ) . self::UNIT_OCTETS );
			return $response_body;
		}

		if ( self::HTTP_ACCEPTED === $status ) {
			$data = json_decode( $response_body, true );
			if ( empty( $data['job_id'] ) ) {
				throw new PuppeteerException( 'Service 202 sans job_id dans la réponse.' );
			}

			return $this->poll_job( (string) $data['job_id'] );
		}

		throw new PuppeteerException( esc_html( 'Service Image - HTTP ' . intval( $status ) . ' : ' . substr( $response_body, 0, 500 ) ) );
	}

	/**
	 * Vérifie la disponibilité du service.
	 *
	 * @return bool True si le service est joignable.
	 */
	public function is_available(): bool {
		$response = wp_remote_get(
			self::SERVICE_BASE_URL . '/v2/health',
			array(
				'timeout'    => 5,
				'user-agent' => self::UA_PREFIX . PDFIB_PRO_VERSION,
				'sslverify'  => $this->should_verify_ssl(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'is_available() → WP_Error : ' . $response->get_error_message(), 'WARNING' );
			return false;
		}

		return (int) wp_remote_retrieve_response_code( $response ) < 500;
	}

	/**
	 * Teste la disponibilité du service.
	 *
	 * @return array{success: bool, message: string, response_time?: int}.
	 */
	public function test_connection(): array {
		$start = microtime( true );
		try {
			$available     = $this->is_available();
			$response_time = (int) round( ( microtime( true ) - $start ) * 1000 );
			if ( $available ) {
				return array(
					'success'       => true,
					'message'       => __( 'Service Puppeteer joignable.', 'advanced-pdf-invoice-builder' ),
					'response_time' => $response_time,
				);
			}

			return array(
				'success' => false,
				'message' => __( 'Service non disponible.', 'advanced-pdf-invoice-builder' ),
			);
		} catch ( \Exception $exception ) {
			return array(
				'success' => false,
				'message' => $exception->getMessage(),
			);
		}
	}

	/**
	 * Interroge /v2/jobs/{job_id}/result jusqu'à obtenir le PDF ou un timeout.
	 *
	 * @param string $job_id Identifiant du job.
	 * @param string $request_token Token de file d'attente.
	 * @return string
	 * @throws PuppeteerException En cas d'erreur ou de timeout.
	 */
	private function poll_job( string $job_id, string $request_token = '' ): string {
		$path     = '/v2/jobs/' . $job_id . '/result';
		$deadline = time() + self::POLL_MAX_WAIT_S;
		$attempts = 0;

		while ( time() < $deadline ) {
			++$attempts;
			$response = wp_remote_get(
				self::SERVICE_BASE_URL . $path,
				array(
					'timeout'    => 10,
					'user-agent' => self::UA_PREFIX . PDFIB_PRO_VERSION,
					'headers'    => $this->build_get_headers( $path ),
					'sslverify'  => $this->should_verify_ssl(),
				)
			);

			if ( is_wp_error( $response ) ) {
				$this->log( 'Polling WP_Error : ' . $response->get_error_message(), 'WARNING' );
				sleep( self::POLL_INTERVAL_S );
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );

			if ( self::HTTP_OK === $code ) {
				if ( '' !== $request_token ) {
					delete_transient( 'pdfib_queue_' . $request_token );
				}

				return $body;
			}

			if ( self::HTTP_ACCEPTED !== $code && self::HTTP_CONFLICT !== $code ) {
				if ( '' !== $request_token ) {
					delete_transient( 'pdfib_queue_' . $request_token );
				}

				throw new PuppeteerException( esc_html( 'Polling job ' . $job_id . ' - HTTP ' . intval( $code ) . ' : ' . substr( $body, 0, 200 ) ) );
			}

			if ( '' !== $request_token ) {
				$data           = json_decode( $body, true );
				$queue_position = isset( $data['queue_position'] ) ? (int) $data['queue_position'] : null;
				set_transient(
					'pdfib_queue_' . $request_token,
					array(
						'queue_position' => $queue_position,
						'attempt'        => $attempts,
						'job_id'         => $job_id,
						'updated_at'     => time(),
					),
					60
				);
			}

			sleep( self::POLL_INTERVAL_S );
		}

		if ( '' !== $request_token ) {
			delete_transient( 'pdfib_queue_' . $request_token );
		}

		throw new PuppeteerException( 'Timeout après ' . intval( self::POLL_MAX_WAIT_S ) . ' s (job_id=' . esc_html( $job_id ) . ')' );
	}

	/**
	 * Effectue un POST authentifié et retourne le code, le body et les headers.
	 *
	 * @param string $path Chemin de l'endpoint.
	 * @param string $body JSON encodé.
	 * @return array{int, string, array}.
	 * @throws PuppeteerException En cas d'erreur réseau.
	 */
	private function http_post( string $path, string $body ): array {
		$timestamp = (string) time();
		$nonce     = $this->generate_nonce();
		$signature = $this->compute_signature( 'POST', $path, $timestamp, $nonce, $body );

		$response = wp_remote_post(
			self::SERVICE_BASE_URL . $path,
			array(
				'method'    => 'POST',
				'timeout'   => 35,
				'headers'   => array(
					'Content-Type'    => 'application/json',
					'X-Pup-Timestamp' => $timestamp,
					'X-Pup-Nonce'     => $nonce,
					'X-Pup-Signature' => $signature,
					'User-Agent'      => self::UA_PREFIX . PDFIB_PRO_VERSION,
				),
				'body'      => $body,
				'sslverify' => $this->should_verify_ssl(),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new PuppeteerException( 'wp_remote_post échoué : ' . esc_html( $response->get_error_message() ) );
		}

		$headers = wp_remote_retrieve_headers( $response );
		if ( method_exists( $headers, 'getAll' ) ) {
			$headers = $headers->getAll();
		}

		return array(
			(int) wp_remote_retrieve_response_code( $response ),
			(string) wp_remote_retrieve_body( $response ),
			array_change_key_case( (array) $headers, CASE_LOWER ),
		);
	}

	/**
	 * Calcule la signature HMAC-SHA256.
	 *
	 * @param string $method Méthode HTTP.
	 * @param string $path Chemin de l'URL.
	 * @param string $timestamp Timestamp Unix.
	 * @param string $nonce Nonce UUID.
	 * @param string $body Corps brut de la requête.
	 * @return string Signature HMAC préfixée.
	 */
	private function compute_signature( string $method, string $path, string $timestamp, string $nonce, string $body ): string {
		$canonical = implode( "\n", array( $method, $path, $timestamp, $nonce, hash( 'sha256', $body ) ) );
		return 'v1=' . hash_hmac( 'sha256', $canonical, $this->get_hmac_key() );
	}

	/**
	 * Headers HMAC pour une requête GET.
	 *
	 * @param string $path Chemin de l'endpoint.
	 * @return array
	 */
	private function build_get_headers( string $path ): array {
		$timestamp = (string) time();
		$nonce     = $this->generate_nonce();

		return array(
			'X-Pup-Timestamp' => $timestamp,
			'X-Pup-Nonce'     => $nonce,
			'X-Pup-Signature' => $this->compute_signature( 'GET', $path, $timestamp, $nonce, '' ),
		);
	}

	/**
	 * Génère un nonce UUID v4 via WordPress.
	 *
	 * @return string UUID.
	 */
	private function generate_nonce(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}

		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0x0fff ) | 0x4000,
			wp_rand( 0, 0x3fff ) | 0x8000,
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff )
		);
	}

	/**
	 * Log interne.
	 *
	 * @param string $message Message à journaliser.
	 * @param string $level Niveau de journalisation.
	 */
	private function log( string $message, string $level = 'INFO' ): void {
		if ( 'ERROR' === $level || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			pdfib_debug_log( '[PuppeteerClient ' . $level . '] ' . $message );
		}
	}
}
