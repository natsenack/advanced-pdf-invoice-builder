<?php
/**
 * PDF Builder exceptions loader (backward-compatibility shim).
 *
 * @package PDFIB\Exception
 */

namespace PDFIB\Exception;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-pdfbuilderexception.php';
require_once __DIR__ . '/class-puppeteerexception.php';
require_once __DIR__ . '/class-templatesizeexception.php';
require_once __DIR__ . '/class-pdfbuilderwoocommerceexception.php';
