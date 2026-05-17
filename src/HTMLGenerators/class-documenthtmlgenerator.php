<?php
/**
 * Main document HTML generator.
 *
 * Orchestrates the generation of complete HTML from template data.
 *
 * @package PDFIB\HTMLGenerators
 */

namespace PDFIB\HTMLGenerators;

defined( 'ABSPATH' ) || exit;

/**
 * Generates complete HTML documents from template data.
 */
class DocumentHTMLGenerator {



	/**
	 * Template data.
	 *
	 * @var array
	 */
	private array $template_data;

	/**
	 * Order data.
	 *
	 * @var array
	 */
	private array $order_data;

	/**
	 * Company data.
	 *
	 * @var array
	 */
	private array $company_data;

	/**
	 * Constructor.
	 *
	 * @param array $template_data Template data.
	 * @param array $order_data    Order data.
	 * @param array $company_data  Company data.
	 */
	public function __construct( array $template_data = array(), array $order_data = array(), array $company_data = array() ) {
		$this->template_data = $template_data;
		$this->order_data    = $order_data;
		$this->company_data  = $company_data;
	}

	/**
	 * Generate complete HTML document
	 */
	public function generate() {
		$html  = '<!DOCTYPE html>';
		$html .= '<html>';
		$html .= '<head>';
		$html .= '<meta charset="UTF-8">';
		$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
		$html .= '<title>Document PDF</title>';
		$html .= $this->generate_styles();
		$html .= '</head>';
		$html .= '<body>';
		$html .= $this->generate_content();
		$html .= '</body>';
		$html .= '</html>';

		return $html;
	}

	/**
	 * Generate only content (for preview/modal).
	 */
	public function generate_content() {
		$canvas_width  = intval( $this->template_data['canvasWidth'] ?? 794 );
		$canvas_height = intval( $this->template_data['canvasHeight'] ?? 1123 );

		$html = '<div class="pdf-canvas" style="width:' . $canvas_width . 'px; height:' . $canvas_height . 'px; position:relative; background:white; margin:0;">';

		$elements = $this->template_data['elements'] ?? array();
		$html    .= ElementGeneratorFactory::generate_multiple( $elements, $this->order_data, $this->company_data );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Generate CSS styles for the standalone PDF HTML document.
	 * CSS rules are loaded from plugin/assets/css/pdf-document.css.
	 */
	private function generate_styles(): string {
		$css_file = plugin_dir_path( __FILE__ ) . '../../assets/css/pdf-document.css';
		if ( ! is_readable( $css_file ) ) {
			return '<style></style>';
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;
		$css = $wp_filesystem->get_contents( $css_file );
		return '<style>' . ( $css ? $css : '' ) . '</style>';
	}
}
