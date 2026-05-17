<?php
/**
 * Advanced PDF Invoice Builder - Custom Exception.
 *
 * @package PDFIB\Api
 */

namespace PDFIB\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Exception personnalisée pour les APIs.
 */
class Exception extends \Exception {

	/**
	 * Constructeur
	 *
	 * @param string     $message Message d'erreur
	 * @param int        $code Code d'erreur
	 * @param \Throwable $previous Exception précédente
	 */
}
