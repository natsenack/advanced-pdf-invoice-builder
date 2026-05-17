<?php
/**
 * Advanced PDF Invoice Builder - Settings Page (Simplified).
 *
 * Main settings dispatcher - includes modular parts.
 *
 * @package PDFIB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access not allowed' );
}

// Include settings loader (loads JavaScript and CSS assets).
require_once plugin_dir_path( __FILE__ ) . 'settings-loader.php';

// Include main settings logic (contains all navigation and tabs).
require_once plugin_dir_path( __FILE__ ) . 'settings-parts/settings-main.php';

// Add floating save button and JavaScript here for better execution context.

// All JavaScript moved to settings-main.php to avoid conflicts.
