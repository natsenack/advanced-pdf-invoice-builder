=== Advanced PDF Invoice Builder ===
Contributors: natsenack
Tags: invoice, pdf, woocommerce, generator, template
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 7.0
WC tested up to: 9.9

Professional PDF invoice and document builder for WordPress — visual drag-and-drop editor with WooCommerce integration.

== Description ==

Advanced PDF Invoice Builder lets you create, customise and generate PDF invoices or documents directly from your WordPress admin area. Build your own templates with a live drag-and-drop canvas and attach them to WooCommerce orders automatically.

**Free features:**

* Visual drag-and-drop PDF template editor (React 18, real-time preview)
* WooCommerce integration — auto-generate PDFs by order status, send to customer by email
* Up to **1 custom template** saved in the database
* 4 built-in starter templates (invoice, delivery note, quote, receipt)
* Multilingual & RTL support (`/languages`)
* High-performance PDF generation engine (Puppeteer-based remote service with local/system font stacks)
* Intelligent asset caching & backup/restore functionality
* Comprehensive analytics dashboard
* Full source code included (TypeScript + PHP, GPL v2)

**Pro edition** (available separately at hub.threeaxe.fr):

* Unlimited custom templates
* Gallery of 25+ premium pre-designed templates
* PNG / JPG image export
* Advanced canvas settings (custom margins, DPI, orientation, colours)
* Unlimited WooCommerce PDF rate limits
* Priority support

== Installation ==

1. Download and unzip the plugin to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to "PDF Builder" in the main admin menu to configure your settings.

== Source Code ==

This plugin contains minified/compiled JavaScript and CSS files. The complete unminified source code is included in the plugin package under the `sources/` directory, as required by WordPress.org guidelines.

* **Included sources:** `sources/js/` and `sources/css/`
* **Build tool:** webpack 5 (`sources/webpack.config.cjs`)
* **Build command:** `npm install && npm run build:free`
* **Repository:** https://github.com/natsenack/wp-pdf-builder

**Compiled assets and their sources:**

* `assets/js/pdf-builder-react.min.js` ← `sources/js/react/` (TypeScript/JSX)
* `assets/js/vendors.min.js` ← webpack vendor bundle (React, ReactDOM, etc.)
* `assets/js/notifications.min.js` ← `sources/js/admin/notifications.js`
* `assets/js/settings-tabs.min.js` ← `sources/js/admin/settings-tabs.js`
* `assets/js/canvas-settings.min.js` ← `sources/js/admin/canvas-settings.js`
* `assets/css/pdf-builder-react.min.css` ← `sources/css/pdf-builder-react.css`
* `assets/css/pdf-builder-admin-css.min.css` ← `sources/css/pdf-builder-admin.css`

**Third-party library bundled as-is:**
* `assets/js/html2canvas.min.js` — html2canvas v1.4.1 (MIT) — https://github.com/niklasvh/html2canvas

== Privacy Policy ==

This plugin collects anonymous deactivation feedback **only when the user explicitly clicks "Send and Deactivate"** in the optional feedback modal.

Data collected and sent to the developer (hub.threeaxe.fr):

* Deactivation reason (selected from a predefined list)
* Optional free-text comment entered by the user
* Site URL
* Plugin version
* Date and time

No passwords, no personal data, no tracking without consent.
The modal includes a "Skip and Deactivate" button that sends no data at all.

== Frequently Asked Questions ==

= Which WordPress versions are supported? =

Advanced PDF Invoice Builder requires WordPress 6.2 or later.

= Is this compatible with WooCommerce? =

Yes, Advanced PDF Invoice Builder provides native WooCommerce integration for automatic order PDF generation.

= What PDF formats are supported? =

The plugin generates standard PDF files compatible with all modern document readers.

== External Services ==

This plugin connects to the following third-party services. By using this plugin, you agree to their respective terms of service and privacy policies.

= PDF Generation Service (pdf.threeaxe.fr) =
Used to generate PDF documents from your templates. Your template data and order information may be sent to this service for rendering. This service is provided by Threeaxe and is required for all PDF generation. The generated HTML uses local/system font stacks and does not load Google Fonts.
* Service URL: https://pdf.threeaxe.fr
* Privacy Policy: https://hub.threeaxe.fr/privacy-policy/
* Terms of Service: https://hub.threeaxe.fr/conditions-dutilisation

= License Validation Server (hub.threeaxe.fr) =
Used to activate, deactivate, and periodically verify your premium license key. This server is operated by Threeaxe, the plugin author.
* Data sent: license key, site URL, plugin name, item ID.
* When: (1) when you manually activate or deactivate a license key in the plugin settings; (2) automatically once per day on admin pages, but only when an active license key has been entered — no data is sent if no license key is configured.
* Service URL: https://hub.threeaxe.fr
* Privacy Policy: https://hub.threeaxe.fr/privacy-policy/
* Terms of Service: https://hub.threeaxe.fr/conditions-dutilisation

= WordPress.org API (api.wordpress.org) =
Used to check for plugin updates through the standard WordPress update mechanism.
* Service URL: https://api.wordpress.org
* Privacy Policy: https://automattic.com/privacy/
* Terms of Service: https://wordpress.org/about/license/

= Google Drive (oauth2.googleapis.com / www.googleapis.com) =
Optional integration to export generated PDFs directly to Google Drive. Only activated when you configure Google Drive integration in the plugin settings.
* Service URL: https://oauth2.googleapis.com / https://www.googleapis.com
* Privacy Policy: https://policies.google.com/privacy
* Terms of Service: https://developers.google.com/terms

= Dropbox (api.dropboxapi.com / www.dropbox.com) =
Optional integration to export generated PDFs directly to Dropbox. Only activated when you configure Dropbox integration in the plugin settings.
* Service URL: https://api.dropboxapi.com
* Privacy Policy: https://www.dropbox.com/privacy
* Terms of Service: https://www.dropbox.com/terms

= Microsoft OneDrive (graph.microsoft.com / login.microsoftonline.com) =
Optional integration to export generated PDFs to OneDrive. Only activated when you configure OneDrive integration in the plugin settings.
* Service URL: https://graph.microsoft.com
* Privacy Policy: https://privacy.microsoft.com/en-us/privacystatement
* Terms of Service: https://www.microsoft.com/en-us/servicesagreement

= Slack (slack.com / api.slack.com) =
Optional integration to send PDF notifications to Slack channels. Only activated when you configure Slack integration in the plugin settings.
* Service URL: https://api.slack.com
* Privacy Policy: https://slack.com/privacy-policy
* Terms of Service: https://slack.com/terms-of-service

= HubSpot (api.hubapi.com) =
Optional CRM integration to attach generated PDFs to HubSpot contacts and deals. Only activated when you configure HubSpot integration in the plugin settings.
* Service URL: https://api.hubapi.com
* Privacy Policy: https://legal.hubspot.com/privacy-policy
* Terms of Service: https://legal.hubspot.com/terms-of-service

= Salesforce (login.salesforce.com / .salesforce.com) =
Optional CRM integration to attach generated PDFs to Salesforce records. Only activated when you configure Salesforce integration in the plugin settings.
* Service URL: https://login.salesforce.com
* Privacy Policy: https://www.salesforce.com/company/privacy/
* Terms of Service: https://www.salesforce.com/company/legal/sfdc-website-terms-of-service/

= Deactivation Feedback (threeaxe.france@gmail.com) =
When you deactivate the plugin, a modal dialog may appear and invite you to optionally share the reason for deactivation. If you choose to submit feedback, the following data is sent by email directly to the plugin author:
* Data sent: deactivation reason, optional comment, site URL, site admin email address, server software, date/time.
* When: only if you click the "Send feedback" button in the deactivation modal. No data is sent if you skip the modal or close it.
* Recipient: threeaxe.france@gmail.com (plugin author, Threeaxe)
This is entirely optional. You can deactivate the plugin without submitting any feedback.

**Note:** All third-party integrations are strictly opt-in and require explicit configuration by the site administrator. No data is sent to any third-party service without your consent and active configuration.

== Changelog ==

= 1.0.0 =
* Architecture: Plugin split into FREE + PRO editions — free core stays GPL v2, PRO add-on sold separately
* Free: up to 1 custom template (was unlimited in 1.3.27)
* Free: editor page (`pdf-builder-react-editor`) always accessible, no hidden sub-menu trick
* Free: fallback edit button (✏️) on template cards when PRO is not active
* Free: lazy-initialisation of PredefinedTemplatesManager via `pdfib_predefined_templates_manager` filter
* Pro: unlimited templates, 25+ gallery templates, PNG/JPG export, advanced canvas options
* Hook system: `pdfib_admin_menu_after_home`, `pdfib_predefined_templates_manager`, `pdfib_can_use_feature`, `pdfib_license_manager_instance`, `pdfib_premium_templates`
* Fix: 403 on editor page when PRO inactive — removed `remove_submenu_page()` call
* Fix: static guard in `react_editor_page()` prevents double render when PRO is active
* Maintenance: version numbers reset to 1.0.0 for both editions

= 1.3.27 =
* New: Dashboard page converted to React TSX component (DashboardPage) — dynamic stats, action cards, getting-started guide
* New: Upgrade modals converted to React TSX component (UpgradeModals) — exposes window.showUpgradeModal(reason) / window.closeUpgradeModal(id)
* New: Licence tab converted to React TSX component (LicencePage) — AJAX activate/deactivate, key copy, details accordion, free/premium comparison table, email reminders
* New: 3 new webpack bundles: dashboard-page-react.min.js, upgrade-modals-react.min.js, licence-page-react.min.js
* Fix: Missing tab indentation before exit; in upgrade-modals.php
* Fix: Array items in $pdfib_licence_data re-indented in settings-licence.php
* Fix: Added translators comments for sprintf strings in dashboard-page.php
* Fix: PHPDoc @param WC_Order / WC_Order_Item / WC_Order_Item_Fee replaced with @param object in HtmlRenderer (5) and OrderProductTableRenderer (5)
* Compliance: Remaining `phpcs:ignore` removed from the cron test AJAX handler; nonce validation now uses `check_ajax_referer()`
* Compliance: `templates/admin/settings-loader.php` now uses `admin_enqueue_scripts` instead of `wp_enqueue_scripts`
* Compliance: React editor bundle now depends on `wp-element`; empty `react` / `react-dom` sources removed
* Compliance: `PDFIB_PLUGIN_FILE` is defined once in `advanced-pdf-invoice-builder.php`; loader/constants use that single source of truth
* Compliance: release ZIP now includes `sources/js/` and `sources/css/`, matching the documented source mappings for compiled JS and CSS assets
* Compliance: external services and opt-in feedback collection remain documented below in the dedicated sections for WP.org transparency

= 1.3.26 =
* Fix: PHP Parse error in Puppeteer_Client.php — missing closing braces in render() and render_image() caused fatal errors
* Fix: NonEnqueuedStylesheet — removed the Google Fonts import in generated HTML and switched to local/system font stacks
* Fix: DevelopmentFunctions — replaced wp_debug_backtrace_summary() with Exception::getTraceAsString()
* Fix: DiscouragedFunctions — removed global PHP execution-time adjustments and `set_error_handler()` calls
* Fix: NonPrefixedHooknameFound — replaced plugin_locale filter with determine_locale()
* Fix: SlowDBQuery — removed meta_query from get_users() / get_posts(), replaced with SQL / PHP filtering
* Fix: NonPrefixedVariableFound — all global variables prefixed with pdfib_ across templates and pages
* Fix: NonPrefixedFunctionFound — removed unprefixed helper functions from settings-contenu.php and handlers
* Fix: PHP syntax bugs in predefined-templates-manager.php and settings-licence.php
* Improve: PuppeteerEngine and Puppeteer_Client log under WP_DEBUG (license status, HTTP response visible)
* Maintenance: Plugin prefix renamed from `pdf` (3 chars) to `pdfib` (5 chars) across 176 PHP files and 16 JS/TS source files — namespaces `PDF_Builder\` → `PDFIB\`, constants `PDF_BUILDER_` → `PDFIB_`, hooks/options `pdf_builder_` → `pdfib_`
* Maintenance: composer.json PSR-4 autoloader mappings updated
* Security: `wp_register_script('wp-preferences')` replaced with `wp_deregister_script()` — no longer re-registering WordPress core script handles
* Security: Enhanced XXE protection on sensitive template files
* Maintenance: IDE helper files excluded from production ZIP build
* Compliance: Conformité WordPress.org optimization and code quality improvements
* Status: Remains at version 1.3.26 — internal maintenance release

= 1.0.3.25 =
* Security: Ajax_Handlers – added `current_user_can('manage_options')` to `pdf_builder_test_roles` handler; added `check_ajax_referer()` to `pdf_builder_verify_canvas_settings_consistency` handler; defined missing `pdf_builder_get_allowed_roles_ajax_handler` function with nonce and capability checks (was registered but never defined – would cause fatal PHP error)
* Security: Unified_Ajax_Handler – added `check_ajax_referer('pdf_builder_ajax', 'nonce')` to `handle_generate_pdf()`, `handle_generate_image()`, and `handle_get_preview_html()` (had capability checks but no nonce verification)
* Security: Frontend PDF GET URL (PDFBuilderContent.tsx) – added `nonce` parameter to direct PDF generation URL; webpack rebuild
* Security: Nonce_Manager – sanitize_text_field + wp_unslash on nonce input before verification
* Security: Sanitized all remaining `$_GET` / `$_REQUEST` / `$_POST` inputs across 34 files (wp_unslash + sanitize_text_field / absint / sanitize_key as appropriate)
* Compliance: Remaining `phpcs:ignore` removed from the cron test AJAX handler; nonce validation now uses `check_ajax_referer()`
* Compliance: `templates/admin/settings-loader.php` now uses `admin_enqueue_scripts` instead of `wp_enqueue_scripts`
* Compliance: React editor bundle now depends on `wp-element`; empty `react` / `react-dom` sources removed
* Compliance: `PDFIB_PLUGIN_FILE` is defined once in `advanced-pdf-invoice-builder.php`; loader/constants use that single source of truth
* Compliance: release ZIP now includes `sources/js/` and `sources/css/`, matching the documented source mappings for compiled JS and CSS assets
* Compliance: external services and opt-in feedback collection remain documented below in the dedicated sections for WP.org transparency
* Security: Sanitized `$_POST` array deep-accesses across 16 files
* Compliance: esc_attr() applied on 15 output points in settings-developpeur template; absint() on license ID parameter; wp_send_json_success return values normalized
* Compliance: Added `ABSPATH` direct-access guard to 43 PHP files missing the standard protection
* Compliance: all `$wpdb` DirectDatabaseQuery and PreparedSQL calls reviewed (100+ occurrences)
* Compliance: ~335 static HTML echo statements reviewed (no user input, no XSS risk)
* Compliance: Renamed option key `pdfb_free_pdf_slots` → `pdf_builder_free_pdf_slots` to comply with WP.org prefix requirements
* Compliance: TransientDebugger – all debug output now gated behind `WP_DEBUG` constant
* Compliance: PHP filesystem functions used with justification in 10 files
* Compliance: ZIP build script – excluded test files, IDE helper files, and development-only files from plugin archive
* Audit: Full security scan – 0 errors remaining for EscapeOutput, NonceVerification, ValidatedSanitizedInput sniffs across all 178 PHP files
  * WordPress.org Pass 1 (security): sanitize all inputs (map_deep + wp_kses_post), remove debug $_POST logs, fix wp_verify_nonce wrapping, sanitize_file_name for uploads
  * WordPress.org Pass 2 (escape): esc_html on translated format strings, esc_js for hook variable, sanitize CSS values in generate_theme_css(), wp_kses_post for render_step_content()
  * WordPress.org Pass 3 (prefix): rename post type pdf_template → pdf_builder_template across 7 files; prefix global constants ELEMENT_PROPERTY_RESTRICTIONS / ELEMENT_TYPE_MAPPING (→ PDF_BUILDER_*); prefix global functions isPropertyAllowed / getPropertyDefault / validateProperty / fixInvalidProperty (→ pdf_builder_*); rename class PDF_Template_Status_Manager → PDF_Builder_Template_Status_Manager; rename AJAX actions pdf_editor_* → pdf_builder_editor_*
  * WordPress.org Pass 4 (discouraged functions): remove UTF-8 BOM from 8 files; replace shell_exec("php -l") with token_get_all() (Health Monitor); replace exec('tar') x3 with ZipArchive (Backup Recovery); remove hardcoded Gmail credentials and XOR/base64 decode_pass() (Deactivation Feedback) – replaced with wp_mail(); urlencode() → rawurlencode() in licence settings; legitimate AES-256-CBC base64 usage retained with justification
* WordPress.org compliance: removed httpbin.org external HTTP test call in PDF_Builder_Test_Suite – replaced with home_url() local self-request
* WordPress.org compliance: replaced hardcoded WP_CONTENT_DIR / ABSPATH / WP_LANG_DIR constants with proper WP API functions (wp_upload_dir(), plugin_dir_path(), load_plugin_textdomain())
* WordPress.org compliance: stripped external URLs from html2canvas.min.js license comment header (local bundled library, no remote call)
* WordPress.org compliance: privacy policy and terms of service URLs updated to hub.threeaxe.fr across readme and settings pages
* Architecture: removed PDF_Builder_Cache_Manager – cache system (400+ lines, cron, 4 AJAX handlers, admin UI) completely withdrawn; plugin operates identically without it
* Fix: corrected residual JS console error caused by removed cache manager references
* Compliance: hook name renamed `pdfBuilderCanvasSettingsUpdated` → `pdf_builder_canvas_settings_updated` (WP naming convention: lowercase snake_case)
* Compliance: WooCommerce capabilities `manage_woocommerce` and `edit_shop_orders` declared as known custom capabilities – removes 30 false-positive warnings
* Compliance: intentional `@` error suppression documented in 6 files (FileSystemHelper, MU_Plugin_Blocker, Backup_Restore_Manager, WooCommerce_Integration, settings-helpers)
* Compliance: local PHP filesystem functions used with justification in 11 additional files (file_get_contents, file_put_contents on local paths – wp_remote_get() is not appropriate)
* Compliance: `WordPress.PHP.StrictInArray` – added `true` as strict third argument to all `in_array()` and `array_search()` calls across 51 files (116+ occurrences) – prevents type-coercion bypass
* Compliance: PHPCBF auto-fixed 450 formatting violations in 86 files (`==`→`===`, `!=`→`!==`, line endings, indentation, string quoting, spacing)
* Fix: removed UTF-8 BOM accidentally introduced by batch processing script – all 51 affected files cleaned
* Audit: 0 errors remaining for EscapeOutput, NonceVerification, ValidatedSanitizedInput, ByteOrderMark, PHP Syntax, ValidHookName, Capabilities, NoSilencedErrors, StrictInArray, AlternativeFunctions across 178 PHP files
* Fix: canvas margins not saving – JS now sends '0' for unchecked single checkboxes; PHP validation added patterns for `_show_` (boolean) and `_margin_` (intval clamped 0-500)
* Fix: template modal DPI list showed all DPIs instead of only active ones – `PDF_Builder_Templates_Ajax` now reads `pdf_builder_canvas_dpi` CSV instead of legacy `pdf_builder_available_dpi`
* Fix: only last DPI value saved when multiple checked – removed `name.slice(0,-2)` stripping in FormData loop; `[]` suffix now preserved so PHP receives an array
* Fix: DPI overwritten to '0' after saving main settings form – excluded `pdf_builder_canvas_dpi`, `pdf_builder_canvas_formats`, `pdf_builder_canvas_orientations` from `save_content_settings()` foreach; removed duplicate `canvas_dpi` from `$general_settings`
* Fix: 403 error on PNG/JPG generation in WooCommerce order metabox – `handle_generate_image()` now accepts both `pdf_builder_ajax` and `pdf_builder_order_actions` nonces; added `manage_options` fallback in permission check
* Compliance: deactivation feedback email now sent to plugin author via `apply_filters('pdf_builder_feedback_email', ...)` instead of site admin email; disclosed in readme (wp.org data collection transparency)

= 1.0.3.24 =
* Code quality: fixed 630 PHPStan errors (level 5) to 0 – complete static analysis now passes cleanly
* Fix: corrected NONCE_INVALID race condition in PDFEditorPreferences (_initialized guard, nonce rotation, retry cap)
* Security: added error_log debug coverage on all sensitive PHP areas (Ajax_Handler, Ajax_Base, Security_Validator, User_Manager, NonceManager, Templates_Ajax x8 handlers, Template_Manager nonce bypass warning, Database_Initializer)
* Security: added console.log/warn/error in ClientNonceManager (refresh, setNonce, addToFormData) and ajax-throttle.js
* IDE: completed WordPress helper declarations in phpdoc support files - 30+ missing functions and constants resolved for Intelephense
* Fix: removed UTF-8 BOM from templates/admin/templates-page.php
* WordPress.org compliance (T2+T3): replaced all WP_CONTENT_DIR usages with wp_upload_dir() across 12+ files
* WordPress.org compliance (T2+T3): converted all json_encode() to wp_json_encode() in PHP core classes and templates
* WordPress.org compliance (T2+T3): converted echo "<script>" inline scripts to wp_add_inline_script() in PDF_Builder_Admin, AdminScriptLoader, ReactAssetsV2
* WordPress.org compliance (T2+T3): sanitized all $_SERVER variables (REQUEST_URI, HTTP_USER_AGENT, REMOTE_ADDR, SERVER_SOFTWARE, CONTENT_TYPE, HTTP_X_HUB_SIGNATURE)
* WordPress.org compliance: all admin notices are now dismissible (is-dismissible)
* WordPress.org compliance: documented source code and build process in readme
* WordPress.org compliance: replaced move_uploaded_file with wp_handle_upload
* WordPress.org compliance: refactored PHPMailer usage via phpmailer_init hook
* WordPress.org compliance: removed direct CDN references (jsdelivr.net polyfills)
* WordPress.org compliance: hardened nonce checks with wp_unslash + sanitize_text_field
* WordPress.org compliance: excluded IDE-only helper files from plugin ZIP
* WordPress.org compliance: removed unprefixed wp_ajax_test_ajax action
* WordPress.org compliance: renamed wp_ajax_verify_canvas_settings_consistency with proper pdf_builder_ prefix
* WordPress.org compliance: removed global PHP execution-time adjustments from the `plugins_loaded` hook
* WordPress.org compliance: converted ob_start() to callback pattern to guarantee buffer closure
* WordPress.org compliance: replaced hardcoded /wp-admin/admin-ajax.php with wp_parse_url(admin_url())
* WordPress.org compliance: removed EDD update system - updates now exclusively handled by WordPress.org
* Security: replaced exec("tar ...") with PharData::extractTo() in PDF_Builder_Backup_Recovery_System
* Security: sanitized client IP extraction in AnalyticsTracker via sanitize_text_field(wp_unslash())
* Code quality: replaced rename() with WP_Filesystem::move() in backup/restore manager
* Code quality: normalized line endings (LF) in templates-page.php
* Architecture: created missing classes AnalyticsInterface, ModeSwitcher, CanvasModeProvider, MetaboxModeProvider
* Architecture: added PSR-4 mapping for PDF_Builder\Analytics\ in composer.json, regenerated autoload (86 classes)
* Documentation: documented all external third-party services in readme.txt
* Intelephense: added wp_parse_url() helper declaration (fixes P1010 error in Security_Monitor)

= 1.0.3.23 =
* UX: Save button is now disabled (greyed) on page load and after successful save, re-enabled on field change

= 1.0.3.22 =
* Fix: corrected comments embedded in SQL strings in settings-templates.php (PDF_Template_Status_Manager->load_templates)

= 1.0.3.21 =
* Fix: corrected 63 occurrences of comments embedded in multiline SQL strings across 11 PHP files

= 1.0.3.20 =
* WordPress.org compliance: extended output escaping with wp_kses_post(), esc_url(), esc_js(), esc_html()
* WordPress.org compliance: sanitized GET data with wp_unslash() + sanitize_text_field()
* WordPress.org compliance: synchronized stable_tag to 1.0.3.20 in readme.txt and readme-fr_FR.txt
* Fix: removed 4 empty JS files from repository
* Fix: corrected comments embedded in SQL strings (63 occurrences, 11 files)

= 1.0.3.15 =
* Performance improvements and bug fixes
* Updated third-party dependencies
* Enhanced security features
* Improved documentation

= 1.0.0 =
* Initial public release

== Upgrade Notice ==

= 1.3.27 =
WordPress.org compliance: nonce + capability checks on all AJAX handlers, strict comparisons, no silenced errors, PHPCBF auto-fixes, BOM cleanup. 0 security violations across 178 PHP files.

= 1.0.3.24 =
Important update: full WordPress.org compliance revision (T2+T3). WP_CONTENT_DIR replaced by wp_upload_dir(), inline scripts via wp_add_inline_script(), exec() removed. Removed EDD update system.

= 1.0.3.15 =
Recommended update with important fixes and improvements.
