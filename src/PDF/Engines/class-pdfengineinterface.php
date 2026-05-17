<?php
/**
 * Interface pour les moteurs de génération PDF
 *
 * @package PDF_Builder_Pro
 * @subpackage PDF\Engines
 * @version 1.0.0
 */

namespace PDFIB\PDF\Engines;

defined( 'ABSPATH' ) || exit;

interface PDFEngineInterface {


	/**
	 * Génère un PDF à partir de HTML.
	 *
	 * @param string $html Contenu HTML.
	 * @param array  $options Options de génération [width, height, orientation, etc.].
	 * @return string|false Contenu PDF binaire ou false en cas d'erreur.
	 */
	public function generate( $html, $options = array() );

	/**
	 * Génère une image (PNG/JPG) à partir de HTML.
	 *
	 * @param string $html Contenu HTML.
	 * @param array  $options Options [format => 'png'|'jpg', width, height, quality].
	 * @return string|false Contenu image binaire ou false.
	 */
	public function generate_image( $html, $options = array() );

	/**
	 * Retourne le nom du moteur.
	 *
	 * @return string Nom du moteur.
	 */
	public function get_name();

	/**
	 * Vérifie si le moteur est disponible.
	 *
	 * @return bool True si disponible.
	 */
	public function is_available();
}
