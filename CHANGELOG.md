# Changelog - Advanced PDF Invoice Builder

## Tous les changements notables de ce projet seront documentés dans ce fichier.

## Version 1.0.0 (Free Edition) — Split FREE / PRO

### Architecture

- 🏗️ **Split FREE / PRO** : le codebase est désormais organisé en deux plugins distincts
  - `advanced-pdf-invoice-builder` (FREE, GPL v2, WordPress.org)
  - `advanced-pdf-invoice-builder-pro` (PRO, vendu sur hub.threeaxe.fr)
- 🔗 **Système de hooks** pour l'intégration PRO → FREE sans couplage fort :
  - `pdfib_admin_menu_after_home` — PRO enregistre ses pages admin
  - `pdfib_predefined_templates_manager` — filtre pour injecter l'instance PRO
  - `pdfib_can_use_feature` — filtre de contrôle des fonctionnalités premium
  - `pdfib_license_manager_instance` — injection du LicenseManager PRO
  - `pdfib_premium_templates` — liste des templates premium disponibles
  - `pdfib_render_templates_card_editor_action` — bouton ✏️ dans les cartes de template

### Fonctionnalités FREE

- 📝 **1 template custom** maximum (quota gratuit)
- 🎨 **Éditeur React** toujours accessible via `pdf-builder-react-editor`
- 🔘 **Bouton ✏️ de fallback** sur les cartes template quand PRO est inactif
- 📄 **4 templates de démarrage** : facture, bon de livraison, devis, reçu

### Corrections

- 🐛 **Fix 403** sur la page éditeur en mode FREE — suppression du `remove_submenu_page()` qui bloquait l'accès
- 🐛 **Guard statique** dans `react_editor_page()` — empêche le double rendu quand PRO est actif

### Inclus dans la version PRO (vendu séparément)

- ∞ Templates custom illimités
- 🖼️ Galerie 25+ templates premium pré-conçus
- 📸 Export PNG / JPG
- ⚙️ Options canvas avancées (marges, DPI, orientation, couleurs)
- ∞ Limites de génération WooCommerce illimitées

---

## Version 1.3.27 (Actuelle, 8 mai 2026) — Conversion React TSX, corrections PHPCS et conformité WP.org finale

### Nouvelles fonctionnalités

- ⚛️ **Dashboard React** : page `dashboard-page.php` convertie en composant TSX (`DashboardPage.tsx`) — statistiques, cartes d'actions, guide de démarrage dynamiques
- ⚛️ **Modales d'upgrade React** : `upgrade-modals.php` converti en composant `UpgradeModals.tsx` — expose `window.showUpgradeModal(reason)` / `window.closeUpgradeModal(id)`
- ⚛️ **Page Licence React** : `settings-licence.php` converti en composant `LicencePage.tsx` — activation/désactivation AJAX, copie de clé, accordéon détails, tableau comparatif free/premium, rappels email
- 📦 **3 nouveaux bundles webpack** : `dashboard-page-react.min.js`, `upgrade-modals-react.min.js`, `licence-page-react.min.js`

### Corrections PHPCS / Intelephense

- 🔧 **Indentation** : `exit;` sans tabulation corrigé dans `upgrade-modals.php`
- 🔧 **Indentation** : 29 items du tableau `$pdfib_licence_data` réindentés dans `settings-licence.php`
- 🔧 **I18n** : commentaires `// translators:` ajoutés pour les chaînes `'Version %s'` et `'Dernière mise à jour: %s'` dans `dashboard-page.php`
- 🔧 **Intelephense P1133** : PHPDoc `@param \WC_Order` / `@param \WC_Order_Item` / `@param \WC_Order_Item_Fee` remplacés par `@param object` dans `class-htmlrenderer.php` (5 blocs) et `class-orderproducttablerenderer.php` (5 blocs)

### Conformité WordPress.org / packaging

- 📦 `build/build-plugin-zip.ps1` now packages `sources/js/` and `sources/css/` together with `webpack.config.cjs`, so the release ZIP contains the human-readable sources for all compiled assets.
- 🧾 `plugin/readme.txt` now documents the JS/CSS source mappings and all third-party services required for PDF rendering, license checks, and optional integrations.
- 🔒 The remaining `phpcs:ignore` on the cron test AJAX handler was removed; nonce validation now uses `check_ajax_referer()`.
- 🔧 `templates/admin/settings-loader.php` now hooks into `admin_enqueue_scripts` instead of `wp_enqueue_scripts`.
- 🔧 The React editor bundle no longer registers empty `react` / `react-dom` sources and now depends on `wp-element`.
- 🔧 `PDFIB_PLUGIN_FILE` is defined once in `advanced-pdf-invoice-builder.php`, avoiding duplicate fallback definitions.

## Version 1.3.26 — Conformité WordPress Plugin Check & Améliorations

### Corrections Plugin Check (PHPCS / WPCS)

- 🐛 **Syntaxe PHP** : ajout des `}` manquants dans `render()` et `render_image()` de `Puppeteer_Client.php` — les méthodes étaient mal fermées, causant un PHP Parse Error et des faux warnings NonPrefixedVariable
- 🔧 **NonEnqueuedStylesheet** : remplacement de `<link href="fonts.googleapis.com">` par un `@import url(...)` dans le bloc `<style>` du HTML généré
- 🔧 **DevelopmentFunctions** : `wp_debug_backtrace_summary()` remplacé par `(new \Exception())->getTraceAsString()` dans `Core_Logger.php` et `Error_Handler.php`
- 🔧 **DiscouragedFunctions** : suppression des ajustements globaux du temps d'exécution PHP et de `set_error_handler()` dans les classes concernées
- 🔧 **NonPrefixedHookname** : filtre `plugin_locale` remplacé par `determine_locale()` dans `PDF_Builder_Localization.php`
- 🔧 **SlowDBQuery** : suppression des `meta_query` dans `get_users()` et `get_posts()` — remplacés par SQL direct ou filtrage PHP
- 🔧 **NonPrefixedVariable** : renommage de toutes les variables globales en `$pdfib_*` dans les templates admin (`canvas-monitor-diagnostic.php`, `settings-*.php`, `templates-page.php`, `predefined-templates-manager.php`) et les pages (`admin-editor.php`, `settings.php`, `admin-system-check.php`)
- 🔧 **NonPrefixedFunction (stubs)** : suppression des déclarations stub de fonctions WP sans préfixe dans `settings-contenu.php`, `Ajax_Handlers.php`, `Notification_Manager.php`
- 🔧 **lib/ stubs** : ajout de `phpcs:disable PrefixAllGlobals` en tête de `pdf-builder-stubs.php` et `vendor-stubs.php` — les noms de classes/fonctions/namespaces doivent correspondre aux API tierces
- 🐛 **Bug PHP** : balise `?>` manquante corrigée dans `predefined-templates-manager.php` et `settings-licence.php`
- 🔧 **VariableConstantNameFound** : `$pdfib_wc_ver_const` supprimé — `define('WC_VERSION', ...)` utilisé directement

### Logs et diagnostic

- 📊 `PuppeteerEngine` et `Puppeteer_Client` journalisent désormais aussi sous `WP_DEBUG` (cohérent avec l'Ajax handler) — permet de voir `license=yes/no` et le code HTTP retourné par le service sans activer le mode debug du plugin

### Documentation

- 📄 Ajout de `docs/PDF_GENERATION_OPTIMIZATIONS.md` — 15 optimisations techniques classées P1→P3 (réduction HTML, gzip, cache PDF, UX, sécurité)
- 📄 Ajout de `docs/MARKETING_PLAN.md` — stratégie complète acquisition, conversion, fidélisation
- 📄 Ajout de `docs/CAHIER_DES_CHARGES.md` — spécifications fonctionnelles et techniques du plugin
- 📄 Ajout de `docs/PAGE_ACCUEIL.md` et `docs/PAGE_PARAMETRES.md` — spécifications des pages admin

---

## Version 1.0.3.26 — Maintenance & Conformité WordPress.org (archive)

- 🔒 **Préfixe global** : Renommage du préfixe `pdf` (3 chars) → `pdfib` (5 chars) sur l'ensemble du codebase
  - 🔄 **176 fichiers PHP** : namespaces `PDF_Builder\` → `PDFIB\`, constantes `PDF_BUILDER_` → `PDFIB_`, hooks/options `pdf_builder_` → `pdfib_`
  - 🔄 **16 fichiers JS/TS** : variables et nonces `pdf_builder_` → `pdfib_`, `PDF_BUILDER_` → `PDFIB_`
  - 🔄 **composer.json** : PSR-4 autoloader `"PDF_Builder\\"` → `"PDFIB\\"` (namespace principal)
  - 📊 **Impact** : 449 occurrences `PDFIB_`, 214 hooks `pdfib_*`

- 🔧 **Correctifs** :
  - `wp_register_script('wp-preferences')` remplacé par `wp_deregister_script()` dans `PDFEditorPreferences.php`
  - XXE protection added to sensitive template files
  - Conformité WordPress.org : optimisations et améliorations de sécurité
- 🏗️ **Build** : `lib/vendor-stubs.php` exclu du ZIP de production
- 📊 **Résultat** : Plugin reste à version 1.0.3.26 ; améliorations de conformité et optimisations de performance

## Version 1.0.3.25 — Conformité WordPress.org complète (Pass 1–4)

- 🗑️ **Cache supprimé** : Système de cache (PDF_Builder_Cache_Manager) complètement retiré
  - ✂️ Classe `PDF_Builder_Cache_Manager.php` supprimée (400+ lignes)
  - ✂️ Initialisation du Cache Manager désactivée dans `bootstrap.php`
  - ✂️ Cron `pdf_builder_cache_cleanup` retiré du Task Scheduler
  - ✂️ 4 AJAX handlers de cache supprimés (get_metrics, test_integration, clear_cache)
  - ✂️ Paramètres admin de cache nettoyés de PDF_Builder_Admin
  - ✂️ Section UI "Cache & Performance" complètement retirée
  - ✂️ Styling CSS du cache supprimé (~70 lignes)
  - ✅ **Impact** : Aucun — le cache était optionnel, plugin fonctionne normalement
  - 📊 **Simplification** : Réduction de ~15 KB du code, architecture allégée
- 🔒 **WP.org Pass 1 — Sécurité entrées** : Sanitisation complète (map_deep + wp_kses_post), suppression logs `$_POST` de débogage, correction wrapper `wp_verify_nonce`, `sanitize_file_name` pour uploads
- 🔒 **WP.org Pass 2 — Échappement sorties** : `esc_html()` sur toutes les chaînes traduites, `esc_js()` pour variables JS, `sanitize_*` pour les valeurs CSS dans `generate_theme_css()`, `wp_kses_post()` pour `render_step_content()`
- 🔒 **WP.org Pass 3 — Préfixe/Namespace** :
  - Renommage post type `pdf_template` → `pdf_builder_template` (7 fichiers + meta keys, nonces, meta box IDs)
  - Constantes globales préfixées : `ELEMENT_PROPERTY_RESTRICTIONS` / `ELEMENT_TYPE_MAPPING` → `PDF_BUILDER_*`
  - Fonctions globales préfixées : `isPropertyAllowed` / `getPropertyDefault` / `validateProperty` / `fixInvalidProperty` → `pdf_builder_*`
  - Classe `PDF_Template_Status_Manager` → `PDF_Builder_Template_Status_Manager`
  - Actions AJAX `pdf_editor_*` → `pdf_builder_editor_*`, option key `pdf_editor_preferences` → `pdf_builder_editor_preferences`
- 🔒 **WP.org Pass 4 — Fonctions découragées & BOM** :
  - Suppression du BOM UTF-8 dans 8 fichiers (causait des erreurs syntaxe PHP namespace)
  - `shell_exec("php -l")` remplacé par `token_get_all()` natif PHP (Health Monitor)
  - `exec('tar ...')` × 3 remplacé par `ZipArchive` + `RecursiveIteratorIterator` (Backup Recovery)
  - Suppression des **credentials Gmail hardcodés** et de la fonction `decode_pass()` XOR/base64 (Deactivation Feedback) — remplacé par `wp_mail()` standard
  - `urlencode()` → `rawurlencode()` (settings-licence.php)

- 🐛 **Fix marges** : Les marges du document et la case « Afficher les marges » ne se sauvegardaient pas
  - JS envoie désormais `'0'` pour les cases non cochées (au lieu de ne rien envoyer)
  - PHP : ajout des patterns `_show_` (booléen) et `_margin_` (entier clampé 0–500) dans la validation `handle_save_canvas_settings()`
- 🐛 **Fix DPI modale modèles** : La liste DPI affichait tous les DPI au lieu des seuls DPI activés dans « Paramètres d'Affichage »
  - `PDF_Builder_Templates_Ajax` lit désormais `pdf_builder_canvas_dpi` (CSV) au lieu de l'ancienne clé `pdf_builder_available_dpi`
- 🐛 **Fix sélection multiple DPI** : Un seul DPI était sauvegardé même avec plusieurs cases cochées
  - Suppression du `name.slice(0,-2)` dans la boucle FormData — le suffixe `[]` est conservé pour que PHP reçoive un tableau
- 🐛 **Fix écrasement DPI après sauvegarde** : Le DPI était réinitialisé à `'0'` lors de la sauvegarde du formulaire principal
  - Exclusion de `pdf_builder_canvas_dpi`, `pdf_builder_canvas_formats`, `pdf_builder_canvas_orientations` du `foreach` dans `save_content_settings()`
  - Suppression du doublon `canvas_dpi => intval(...)` dans `$general_settings`
- 🔒 **Fix 403 WooCommerce** : Erreur 403 sur la génération PNG/JPG dans la métabox de commande WooCommerce
  - `handle_generate_image()` accepte désormais les deux nonces `pdf_builder_ajax` et `pdf_builder_order_actions`
  - Ajout du fallback `manage_options` dans la vérification des permissions
- ✅ **Conformité WP.org — Feedback désactivation** : L'email de feedback est désormais envoyé à l'auteur du plugin via `apply_filters('pdf_builder_feedback_email', 'threeaxe.france@gmail.com')` au lieu de l'email admin du site ; divulgation ajoutée dans `readme.txt` et `readme-fr_FR.txt`

## Version 1.0.3.24 (2026-03-24)

- ⚡ **Performance** : Suppression de 6 crons inutiles pour plugin factures (60% de réduction des tâches planifiées):
  - ✂️ `pdf_builder_health_monitor` (5 min) — Monitoring continu non nécessaire
  - ✂️ `pdf_builder_performance_monitor` (1h) — Métriques de santé non utiles pour l'invoice builder
  - ✂️ `pdf_builder_error_monitor` (1j) — Monitoring d'erreurs supprimé
  - ✂️ `pdf_builder_hourly_aggregation`, `pdf_builder_daily_aggregation`, `pdf_builder_weekly_aggregation` — Rapports analytics supprimés
  - ✂️ `pdf_builder_security_check` (2x/j) — Health checks supprimés
  - ✂️ `pdf_builder_performance_cleanup` (1x/s) — Nettoyage de metrics non nécessaire
  - ✅ **Gardés** : `cache_cleanup` (1h), `log_rotation` (1j), `optimize_database` (1x/s), `gdpr_cleanup` (1j), `license_check` (1j)- � **Qualité du code** : Correction de 630 erreurs PHPStan (niveau 5) → 0 erreurs — analyse statique complète du codebase
  - Types/casts manquants : `uniqid((string)wp_rand())`, `esc_attr((string)$dpi)`, `esc_attr((string)$opacity)`, `esc_attr((string)$border_radius)`
  - `formatCurrency(float $amount)` : signature fortement typée dans `Variable_Mapper`
  - `spl_autoload_register` : closure typée `(string): void` dans `bootstrap.php`
  - `wp_enqueue_script('react', '')` : remplacement de `false` par chaîne vide
  - Initialisation explicite des variables avant les blocs `try` (`$deployment_id`, `$rollback_id`, `$template`, `$template_data`)
  - Suppression de code mort dans `Drag_Drop_Manager`, `JSON_Optimizer`, `Onboarding_Manager`
  - Garde `is_string($setting_value)` avant comparaisons `=== '1' || === '0'` dans `Unified_Ajax_Handler`
  - PHPDoc corrigées : `Auto_Update_Manager`, `PuppeteerEngine` (paramètre inutilisé intentionnel)
  - Stubs complétés : `WC_Countries`, `WooCommerce::$countries`, `WC_Order_Item_Fee::get_amount/get_total_tax`, `pdf_builder_log()`
  - `phpstan.neon` : niveau 5, `reportUnmatchedIgnoredErrors: false`, patterns `ignoreErrors` pour code WP idiomatique
- �🐛 **Fix** : Correction de la race condition `NONCE_INVALID` dans `PDFEditorPreferences` — guard `_initialized`, plafond de retry, rotation du nonce depuis la réponse AJAX
- 🔒 **Debug logs PHP** : `error_log` sur toutes les zones sensibles — `PDF_Builder_Ajax_Handler`, `Ajax_Base`, `PDF_Builder_Security_Validator`, `PDF_Builder_User_Manager`, `NonceManager`, `PDF_Builder_Templates_Ajax` (8 handlers), `PDF_Builder_Template_Manager` (SECURITY WARNING nonce bypass), `Database_Initializer`
- 🔒 **Debug logs JS/TS** : `console.log/warn/error` dans `ClientNonceManager` (refresh, setNonce, addToFormData) et `ajax-throttle.js`
- 🔧 **IDE** : Stubs WP complets dans `pdf-builder-stubs.php` — 30+ fonctions et constantes manquantes (corrige P1005/P1006/P1010/P1011 Intelephense)
- 🔧 **Fix** : Suppression du BOM UTF-8 dans `templates/admin/templates-page.php`
- 🔒 **Conformité WP.org T2+T3** : Remplacement de toutes les occurrences `WP_CONTENT_DIR` par `wp_upload_dir()` (backups, logs, cache, temp) dans 12+ fichiers
- 🔒 **Conformité WP.org T2+T3** : Conversion de tous les `json_encode()` en `wp_json_encode()` dans les fichiers PHP core et templates
- 🔒 **Conformité WP.org T2+T3** : Conversion des `echo "<script>"` inline en `wp_add_inline_script()` dans PDF_Builder_Admin, AdminScriptLoader et ReactAssetsV2
- 🔒 **Conformité WP.org T2+T3** : Sanitisation complète des variables `` (REQUEST_URI, HTTP_USER_AGENT, REMOTE_ADDR, SERVER_SOFTWARE, CONTENT_TYPE, HTTP_X_HUB_SIGNATURE)
- 🔒 **Sécurité** : Toutes les notices admin disposent maintenant de l'attribut `is-dismissible`
- 📋 **Conformité WP.org §4** : Documentation du code source et du processus de build dans le readme
- 🔧 **Conformité WP.org** : Remplacement de `move_uploaded_file` par `wp_handle_upload`
- 🔧 **Conformité WP.org** : Refactorisation PHPMailer via hook `phpmailer_init`
- 🔧 **Conformité WP.org** : Suppression des références CDN directes (jsdelivr.net)
- 🔧 **Conformité WP.org** : Renforcement des vérifications de nonce avec `wp_unslash + sanitize_text_field`
- 🔧 **Conformité WP.org** : Exclusion de `lib/pdf-builder-stubs.php` (fichier IDE uniquement) du ZIP
- 🔧 **Conformité WP.org** : Suppression de l'action `wp_ajax_test_ajax` sans préfixe
- 🔧 **Conformité WP.org** : Renommage de `wp_ajax_verify_canvas_settings_consistency` avec préfixe `pdf_builder_`
- 🔧 **Conformité WP.org** : suppression des ajustements globaux du temps d'exécution PHP du hook `plugins_loaded`
- 🔧 **Conformité WP.org** : Conversion de `ob_start()` avec callback pour garantir la fermeture du buffer
- 🔧 **Conformité WP.org** : Remplacement de `/wp-admin/admin-ajax.php` codé en dur par `wp_parse_url(admin_url('admin-ajax.php'), PHP_URL_PATH)`
- ✅ **Conformité WP.org** : Suppression complète du système de mise à jour EDD — mises à jour gérées exclusivement par WordPress.org
- 🔒 **Sécurité** : Remplacement de `exec("tar ...")` par `PharData::extractTo()` dans PDF_Builder_Backup_Recovery_System
- 🔒 **Sécurité** : Nettoyage des IP clients (AnalyticsTracker) via `sanitize_text_field(wp_unslash())`
- 🔧 **Architecture** : Création des classes manquantes `AnalyticsInterface`, `ModeSwitcher`, `CanvasModeProvider`, `MetaboxModeProvider`
- 🔧 **Architecture** : Mapping PSR-4 `PDF_Builder\Analytics\` ajouté dans composer.json, autoload régénéré (86 classes)
- 🔧 **Intelephense** : `isAnalyticsEnabled()` rendu public pour conformité avec `AnalyticsInterface`
- 🔧 **Intelephense** : Ajout du stub `wp_parse_url()` dans `pdf-builder-stubs.php` (correction erreur P1010 dans `PDF_Builder_Security_Monitor`)
- 📄 Documentation de tous les services tiers dans readme.txt

==================================================================================================================

## Version 1.0.3.23 (2026-03-15)

- ✨ **UX** : Le bouton flottant « Enregistrer » est maintenant grisé tant qu'aucune modification n'a été effectuée dans les paramètres
  - Bouton désactivé au chargement de la page
  - Réactivé dès qu'un champ est modifié
  - Grisé à nouveau après une sauvegarde réussie

## Version 1.0.3.22 (2026-03-15)

- ✅ 2 requêtes corrigées : `SELECT ID, post_title FROM wp_posts` et `SELECT id, name FROM wp_pdf_builder_templates`
- ✅ Le dossier `templates/` n'était pas couvert par le scan automatique de la v1.0.3.21

==================================================================================================================

## Version 1.0.3.21 (2026-03-15)

- ✅ 63 occurrences corrigées automatiquement dans 11 fichiers via scan complet
- ✅ Fichiers corrigés : `AjaxHandler.php`, `PDF_Builder_Auto_Update_System.php`, `PDF_Builder_Backup_Recovery_System.php`, `PDF_Builder_Diagnostic_Tool.php`, `PDF_Builder_Health_Monitor.php`, `PDF_Builder_Unified_Ajax_Handler.php`, `PDF_Builder_Task_Scheduler.php`, `PDF_Builder_Security_Monitor.php`, `PDF_Builder_GDPR_Manager.php`, `PDF_Builder_Continuous_Deployment.php`, `PDF_Builder_Metrics_Analytics.php`, `PDF_Builder_Error_Handler.php`

==================================================================================================================

## Version 1.0.3.20 (2026-03-11)

- 🔒 **Conformité WordPress.org** : Correction des erreurs de sécurité détectées
  - ✅ Escaping output avec `wp_kses_post()`, `esc_url()`, `esc_js()`, `esc_html()` pour tous les outputs
  - ✅ Vérification nonce avec suppression des warnings de sécurité
  - ✅ Sanitization des données `$_GET` avec `wp_unslash()` + `sanitize_text_field()`
  - ✅ Amélioration escaping des timestamps et listes de plugins

  - ✅ Fix `stable_tag` dans `readme.txt` et `readme-fr_FR.txt` (1.0.3.19 → 1.0.3.20)
  - ✅ Fix ZIP : exclusion des fichiers JS vides et des entrées de répertoires vides

- 🧹 **Nettoyage repo** : Audit complet du plugin
  - ✅ Suppression des 4 fichiers JS vides du repo (`assets/js/dashboard-css.min.js`, etc.)
  - ✅ `PDF_Builder_Analytics_Manager.php` (vide) remplacé par un stub PHP valide

  - ✅ `AjaxHandler.php` : 2 requêtes corrigées (templates listing)
  - ✅ Scan automatique de tous les fichiers PHP — 63 occurrences corrigées dans 10+ fichiers
  - ✅ Fichiers concernés : `PDF_Builder_Auto_Update_System.php`, `PDF_Builder_Backup_Recovery_System.php`, `PDF_Builder_Diagnostic_Tool.php`, `PDF_Builder_Health_Monitor.php`, `PDF_Builder_Unified_Ajax_Handler.php`, `PDF_Builder_Task_Scheduler.php`, `PDF_Builder_Security_Monitor.php`, `PDF_Builder_GDPR_Manager.php`, `PDF_Builder_Continuous_Deployment.php`, `PDF_Builder_Metrics_Analytics.php`, `PDF_Builder_Error_Handler.php`

==================================================================================================================

## Version 1.0.3.19 (2026-02-24)

- 🔧 **Fix Message MAJ Fantôme** : Délai de 10 min après mise à jour pour ignorer les suggestions
  - ✅ Permet au plugin d'être correctement rechargé par WordPress
  - ✅ Évite l'affichage de "nouvelle version disponible" juste après l'installation
- ✅ **Purge transients post-MAJ** : Force WordPress à recalculer l'état des mises à jour

==================================================================================================================

## Version 1.0.3.18 (2026-02-24)

- 🔄 **Fix Transient Post-MAJ** : Hook `upgrader_process_complete` pour purger les transients immédiatement après mise à jour
  - ✅ Élimine le message "mise à jour disponible" qui persiste après l'installation
  - ✅ Force WordPress à recalculer le statut des MAJ disponibles

==================================================================================================================

## Version 1.0.3.17 (2026-02-24)

- 🧹 **Auto-cleanup Transients** : Nettoyage automatique des transients corrompus au premier accès admin
- ⚡ **Stabilité Mise à jour** : Refactorisation pour éviter les appels récursifs des hooks WordPress
- ✅ **Validation Système** : Version de test pour valider le cycle de mise à jour en production

==================================================================================================================

## Version 1.0.3.16 (2026-02-24)

- 🔧 **Hotfix Mises à jour** : Correction du système dual (utilisateurs avec/sans licence)
  - ✅ Vérification du statut de licence avant envoi à EDD
  - ✅ Fallback automatique vers mu-plugin `edd-free-update.php` si licence inactive/expirée
  - ✅ Tous les utilisateurs reçoivent les mises à jour, même avec licence expirée
- 🔍 **License Manager** : Amélioration du `check_license_status()` avec détection rapide d'expiration
- 🐛 **Namespace Fix** : Correction de la déclaration de namespace dans `PDF_Builder_WooCommerce_Integration.php`
- ✅ **Plugin Check** : Résolution de l'erreur fatale de namespace

==================================================================================================================

## Version 1.0.3.12 (2026-02-23)

- 🔒 **Plugin Check** : Correction de toutes les erreurs `EscapeOutput` — `_e()` → `esc_html_e()`, `echo __()` → `esc_html__()`, `echo admin_url()` → `esc_url()`, variables HTML échappées avec `esc_html()`/`esc_attr()`
- 🛡️ **Sécurité** : Ajout de protections ABSPATH manquantes (`settings-developpeur.php`, `bootstrap.php`)
- 🔧 **wp_redirect** : Remplacement par `wp_safe_redirect()` dans `builtin-editor-page.php`
- 🧹 **Conformité WordPress.org** : 13 fichiers mis en conformité avec les standards WordPress

==================================================================================================================

## Version 1.0.3.11 (2026-02-23)

- 🎉 **EDD Free Updates** : Mise en place du mu-plugin `/wp-content/mu-plugins/edd-free-update.php` sur hub.threeaxe.fr
- 📦 **Utilisateurs gratuits** : Téléchargement des mises à jour sans clé de licence depuis `/downloads/`
- 🔗 **Intégration EDD** : Le mu-plugin injecte l'URL du package dans les réponses get_version
- ✅ **Test validé** : HTTP 200 sur package ZIP, auto-mise à jour fonctionnelle

==================================================================================================================

## Version 1.1.3.0 (À venir)

==================================================================================================================

## Version 1.2.0.0 (À venir)

- **global** - optimisation du code et performance(gzip)
- **stat** - mise en place d'un systeme de statistique du nombre de création ???

### 📊 Système de rapports avancé

- **Tableaux de bord** : vue d'ensemble des documents générés
- **Statistiques** : nombre de PDF/mois, poids moyen, usage API
- **Logs d'audit** : qui, quand, quoi — 100% transparent
- **Exports** : CSV, JSON pour vos outils BI
- **langue** - mise en pla de la langue espagnile et allement

### Fonctionnalités (Features)

- [] Fonction 1 (à définir)
- [ ] Fonction 2 (à définir)
- [ ] Fonction 3 (à définir)

==================================================================================================================

## Version 1.1.2.0 (À venir)

### Fonctionnalités (Features)

- [] Fonction 1 (à définir)
- [ ] Fonction 2 (à définir)
- [ ] Fonction 3 (à définir)

==================================================================================================================

## **_Version 1.1.1.0_** (À venir)

### Fonctionnalités (Features)

- [] Fonction 1 (à définir)
- [ ] Fonction 2 (à définir)
- [ ] Fonction 3 (à définir)

==================================================================================================================

## **_Version 1.1.0.0_** (À venir)(juillet/aout)

### Fonctionnalités (Features)

- 🆕 **Nouveaux éléments dans la liste React** : Ajout de nouveaux types d'éléments disponibles dans le panneau d'insertion
  - [ajouter les fonctions dans le toolbar du menu contextuel] Élément 2 (à définir)
  - [ajout de la personnalisation du choix du moteur pdf] Élément 3 (à définir)
- **Français, anglais, espagnol, allemand** : switchez en un clic
- **Convertisseur de devises** : EUR, USD, GBP, JPY…
- **Formats régionaux** : dates, nombres, symboles monétaires
- **RTL support** : arabe, hébreu compatible
- **Intégration ERP/CRM**

### Extensibilité & intégrations

- **Hooks WordPress** : intégrez PDF Builder à vos workflows
- **Stockage flexible** : local ou compatible S3
- **Compatible tiers** : CRM, email, outils business

==================================================================================================================

## **_Version 1.0.4.0_** (À venir)

### Fonctionnalités (Features)

- 🆕 **Format A3 activé** : Le format papier A3 (297×420mm) est désormais disponible et sélectionnable dans les paramètres du template

### Restrictions en cours

> ⚠️ Les formats et options suivants sont **temporairement désactivés** dans le plugin et seront activés dans une prochaine version :

- 🔒 **Format désactivé** — 🇺🇸 Letter (8.5×11")
- 🔒 **Format désactivé** — ⚖️ Legal (8.5×14")
- 🔒 **Format désactivé** — 📦 Étiquette Colis (100×150mm)
- 🔒 **Orientation désactivée** — Paysage (seul le **Portrait** est disponible)
- **onglet "configuration pdf"** - correction et optimisation des fonctions
- # **langue** - vérifier la langue anglais si bien traduit à 100%

## **_Version 1.0.3.12_** — 23 février 2026

### 🔧 Corrections

- **[Updates] Mises à jour gratuites EDD** : Les utilisateurs sans clé de licence envoient maintenant une requête EDD **sans le paramètre `license`**, ce qui permet à EDD de retourner le lien de téléchargement public pour la version gratuite/libre. Auparavant, envoyer `license=` vide bloquait la réponse.

==================================================================================================================

## **_Version 1.0.3.11_** — 11 février 2026

### 🔧 Corrections

- **[Updates] Clé de licence EDD en contexte cron** : Correction du check auto-update. `getLicenseKeyForLinks()` ne vérifie plus `current_user_can('manage_options')` qui retourne toujours false en contexte transient (pas de requête HTTP). Les clients peuvent maintenant auto-mettre à jour sans erreur `download_link` vide.

==================================================================================================================

## **_Version 1.0.3.10_** — 23 février 2026

### 🔧 Corrections

- **[Updates] Purge transient WordPress** : L'action de diagnostic vide maintenant aussi `site_transient('update_plugins')` pour garantir un token de téléchargement EDD toujours frais lors des mises à jour.

==================================================================================================================

## **_Version 1.0.3.9_** — 23 février 2026

### 🔧 Corrections

- **[UI] Logo plugin** : Ajout des icones `plugin-icon.png` et `plugin-icon-2x.png` affichées dans la page mises à jour WordPress.

==================================================================================================================

## **_Version 1.0.3.8_** — 23 février 2026

### 🔧 Corrections

- **[Updates] Logs de diagnostic** : Ajout de logs `error_log` détaillés dans `get_remote_version()` et `check_for_updates()` pour tracer l'appel EDD, la réponse HTTP, le parsing JSON et le résultat de comparaison de version.

==================================================================================================================

## **_Version 1.0.3.7_** — 23 février 2026

### 🔧 Corrections

- **[Updates] Correctif système de mises à jour automatiques** : Le système de check EDD retournait `false` car il cherchait `version`/`package` alors qu'EDD Software Licensing retourne `new_version`/`download_link`. Normalisation des deux champs.
- **[Updates] `item_name` ajouté** à la requête `get_version` vers `hub.threeaxe.fr` pour conformité EDD SL.
- **[Updates] Gestion JSON/sérialisé** : Support des deux formats de réponse EDD (JSON et PHP sérialisé).
- **[Updates] Cron sans utilisateur** : Suppression du guard `current_user_can()` dans `get_license_key()` qui bloquait les checks en contexte wp-cron (aucun user connecté).
- **[Updates] Logs verbeux supprimés** : Retrait des `error_log()` systématiques dans `PDF_Builder_Unified_Ajax_Handler` (constructor, init_hooks, handle_save_settings) et `bootstrap.php` qui polluaient les logs PHP à chaque requête.
- **[Updates] Action AJAX de diagnostic** `pdf_builder_test_update_check` ajoutée pour tester la connexion EDD depuis la console navigateur.

==================================================================================================================

## **_Version 1.0.3.6_** — 24 février 2026

### 🔒 Sécurité & Conformité Plugin Check WordPress

- **[Security] `missing_direct_file_access_protection`** : Ajout du garde ABSPATH (`if (!defined('ABSPATH')) { exit; }`) dans 11 fichiers PHP sans protection d'accès direct : `pages/settings.php`, `pages/admin-editor.php`, `pages/welcome.php`, `settings-securite.php`, `settings-pdf.php`, `settings-systeme.php`, `settings-licence.php`, `settings-templates.php`, `settings-cron.php` (déjà présent), `settings-modals.php`, `settings-pdf-fixed.php`.
- **[Security] `EscapeOutput.UnsafePrintingFunction`** : Remplacement de tous les `_e()` par `esc_html_e()` et des `echo __()` par `echo esc_html__()` dans `pages/settings.php` et `settings-main.php` (onglets de navigation, boutons, messages JS).
- **[Security] `EscapeOutput.OutputNotEscaped`** : Enveloppement de toutes les variables échappées manquantes : `echo esc_html($var)` pour texte, `echo esc_attr($var)` pour attributs HTML, `echo esc_url(admin_url(...))` pour URL, `echo esc_attr(wp_create_nonce(...))` pour nonces dans champs hidden, `echo esc_js(wp_create_nonce(...))` pour nonces dans blocs JavaScript.
- **[Security] `SafeRedirect`** : Remplacement de `wp_redirect()` par `wp_safe_redirect()` dans `pages/welcome.php` et `settings-main.php`.

- **[Security] Nonces JS systeme** : 13 occurrences de `echo wp_create_nonce('pdf_builder_ajax')` dans `settings-systeme.php` migrées vers `esc_js()` pour éviter les injections dans du code JavaScript.
- **[Security] admin-system-check.php** : Échappement de `wp_nonce_url()` → `esc_url()`, `size_format()` → `esc_html()`, `PHP_OS` → `esc_html()`, `PHP_VERSION` → `esc_html()`.

==================================================================================================================

## **_Version 1.0.3.5_** — 23 février 2026

### 🐛 Corrections (Bug Fixes)

- **[i18n] `MissingArgDomain`** : Ajout du paramètre `'pdf-builder-pro'` manquant dans les appels `__()` de `predefined-templates-manager.php`, `builtin-editor-page.php`, `PDF_Builder_Template_Manager`, `PDF_Builder_Settings_Manager`.
- **[i18n] `MissingTranslatorsComment`** : Ajout des commentaires `// translators:` requis par WordPress avant tous les appels `sprintf()` / `printf()` / `_n()` contenant des placeholders (`%s`, `%d`) dans 10+ fichiers.
- **[i18n] `UnorderedPlaceholdersText`** : Remplacement de `%s, %s` / `%d, %s` par `%1$s, %2$s` / `%1$d, %2$s` pour les chaînes à plusieurs placeholders (`PDF_Builder_API_Helper`, `MaintenanceManager`, `MaintenanceActionHandler`, `Backup_Restore_Manager`).
- **[i18n] `TextDomainMismatch`** : Correction du domaine `'pdf-builder'` → `'pdf-builder-pro'` dans `PDF_Builder_Auto_Update_Manager`.
- **[i18n] `MissingSingularPlaceholder`** : Ajout du placeholder `%d` dans la forme singulière des appels `_n()` de `PDF_Builder_Auto_Update_Manager` (mises à jour + correctifs sécurité).

==================================================================================================================

## **_Version 1.0.3.4_** — 23 février 2026

### 🔧 Maintenance & Qualité du code

- **[Code] Reformatage global (Prettier)** : Unification du style de code JS/TSX sur tout le projet (guillemets doubles, indentation 2 espaces, trailing commas).
- **[UI Admin] Modal de désactivation refactorisé** : Le JS du modal de désactivation a été entièrement réécrit — sélecteurs `#pbp-modal` plus légers, validation obligatoire de raison avant envoi, bouton "Annuler" sans désactivation.
- **[React] Reformatage Canvas.tsx** : Réorganisation du rendu des lignes de marges en JSX multi-lignes lisible.
- **[React] Reformatage BuilderContext.tsx** : Correctifs lint sur les lignes `marginLeft`/`marginRight` trop longues.
- **[React] Reformatage useTemplate.ts** : Wrapping de `margin_bottom` en multi-lignes pour conformité ESLint.

==================================================================================================================

## **_Version 1.0.3.3_** — 23 février 2026

### 🐛 Corrections (Bug Fixes)

- **[Critique] Génération PNG/JPG — erreur 403 `tier_restriction`** : La clé de licence n'était pas transmise au service Puppeteer. Ajout d'un mécanisme de récupération en 3 étapes (LicenseManager → ligne séparée → blob JSON `pdf_builder_settings`).
- **[Critique] Chemin FTP incorrect** : Les déploiements ciblaient `/wp-pdf-builder-pro/` au lieu du chemin réel `/pdf-builder-pro/`, rendant tous les correctifs précédents inopérants.
- **[BDD] Préfixe de table dynamique** : `Settings_Table_Manager` lit désormais `$table_prefix` directement depuis `wp-config.php` via la variable globale, toutes les méthodes centralisées sur `get_table_name()`.
- **[UI React] TypeError `lineHeight.toFixed`** : `element.lineHeight` peut être une string (`"1.1"`) — ajout de `parseFloat(String(...))` dans `CustomerInfoProperties` et `CompanyInfoProperties` pour éviter le crash de l'éditeur.
- **[UI] Message moteur image** : Correction du message affiché lors de la génération d'image (suppression de la mention "fallback Imagick" — le moteur est toujours Puppeteer).
- **[Logging] LicenseManager** : Ajout de logs détaillés dans `decrypt_key()` pour diagnostiquer les échecs de déchiffrement AES.

==================================================================================================================

## **_Version 1.0.3.2_** — 22 février 2026

### 🐛 Corrections (Bug Fixes)

- **[BDD] Migration table settings** : Correction de la logique de migration dans `Settings_Table_Manager::create_table()` — suppression du bloc ciblant une table inexistante `wp_pdf_builder_settings`.
- **[BDD] `get_option()` simplifié** : Suppression du fallback incorrect vers une ancienne table hardcodée.
- **[Logging] PuppeteerEngine** : Ajout de logs de diagnostic sur la clé de licence (`get_license_key()`) pour identifier les situations où la clé est vide.

==================================================================================================================

## **_Version 1.0.3.1_** — 21 février 2026

### 🐛 Corrections (Bug Fixes)

- **[Licence] Correction du bug d'activation de licence** : La clé de licence n'était pas correctement sauvegardée lors de l'activation, entraînant un retour au mode gratuit après rechargement.

==================================================================================================================

## Version 1.0.3.0 (Mars/avril 2026)

### Corrections (Bug Fixes)

- [correction des affichage des modale dans l'onglet canvas ] **Bug 1**
- [réparation du menu contextuel] **Bug 2**
- [ ] **Bug 3** : À définir

## [1.0.2.0] - 2026-02-20

### ✨ Nouvelles fonctionnalités

- **Système de mises à jour automatiques** via EDD intégré à WordPress
- Vérification automatique des mises à jour (cache 12h)
- Hooks WordPress standards: `plugins_api`, `pre_set_site_transient_update_plugins`
- Notifications de mise à jour dans l'interface d'administration WordPress

### 🔒 Sécurité

- Chiffrement AES-256-CBC de la clé de licence en base de données
- Affichage masqué des clés (format: 5 caractères + 18 points)
- Décryption lazy-loaded au démarrage du plugin

### 📊 Améliorations

- Table de comparaison des fonctionnalités Gratuit vs Premium
  - Section visible: 6 fonctionnalités clés
  - Section cachée: 19 fonctionnalités supplémentaires
  - Total: 25 fonctionnalités listées
- Informations détaillées d'expiration et calcul des jours restants
- Couleur d'alerte des jours expiration (vert/orange/rouge)
- Boutons "Renouveler" et "Se désabonner" avec URLs EDD sécurisées
- Section "Informations détaillées" collapsible

### 🐛 Corrections

- Corrigé: bouton "Configurer le canevas" sur pages d'édition
- Corrigé: désactivation correcte des licences
- Corrigé: récupération des informations clients (nom, email, activations)

### 📝 Documentation

- Ajout du `changelog.json` pour servir les changelogs au client
- Ajout du `CHANGELOG.md` (ce fichier) pour la documentation

---

## [1.0.1.0] - 2026-01-15

### 🔧 Corrections

- Corrections de bugs critiques
- Optimisations de performance de l'éditeur

### 🎨 Amélioration UI

- Améliorations mineures de l'interface utilisateur

---

## [1.0.0.0] - 2025-12-01

### 🎉 Lancement initial

- Générateur de PDF professionnel avec éditeur visuel
- Templates professionnels inclus
- Support des éléments de base (texte, images, formes)
- Gestion des licences EDD intégrée
- Mode gratuit et premium

---

## Format de versioning

Le plugin utilise le format de versioning: `MAJOR.MINOR.PATCH.BUILD`

Exemple: `1.0.2.0`

- `1` = Majeure (changements majeurs)
- `0` = Mineure (nouvelles fonctionnalités)
- `2` = Patch (corrections de bugs)
- `0` = Build (numéro de build)

## Procédure de release

1. Mettre à jour la version dans `plugin/advanced-pdf-invoice-builder.php` (header `Version:`)
2. Créer une entry dans `CHANGELOG.md`
3. Créer une entry dans `plugin/changelog.json`
4. Lancer `('.\build\deploy-simple.ps1` pour générer le ZIP versionné
5. Uploader le ZIP en EDD
6. Committer les changements: `git commit -am "Release v1.0.2.0"`
