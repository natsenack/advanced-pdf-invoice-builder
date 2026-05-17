=== Advanced PDF Invoice Builder ===
Contributors: natsenack
Tags: facture, pdf, constructeur, woocommerce, modèle
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.3.27
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 7.0
WC tested up to: 9.9

Constructeur de factures et documents PDF professionnel avancé pour WordPress avec éditeur visuel drag-and-drop et intégration WooCommerce.

== Description ==

Advanced PDF Invoice Builder est un plugin WordPress professionnel pour créer, personnaliser et générer des factures et documents PDF directement depuis l'interface d'administration WordPress, avec des fonctionnalités puissantes et une intégration transparente à WooCommerce.

Fonctionnalités principales :

* Éditeur visuel de modèles PDF par glisser-déposer
* Intégration WooCommerce pour la génération automatique de PDF de commandes
* Modèles prédéfinis personnalisables
* Support multilingue et localisation
* Moteur de génération PDF haute performance
* Gestion avancée des ressources et mise en cache intelligente
* Fonctionnalité intégrée de sauvegarde et restauration
* Tableau de bord analytique complet

== Installation ==

1. Téléchargez et décompressez le plugin dans le répertoire `/wp-content/plugins/`.
2. Activez le plugin via le menu "Extensions" dans WordPress.
3. Accédez à "PDF Builder" dans le menu principal de l'administration pour configurer vos réglages.

== Code Source ==

Ce plugin contient des fichiers JavaScript minifiés/compilés. Le code source complet non minifié est disponible publiquement à l'adresse :

* **Dépôt :** https://github.com/natsenack/wp-pdf-builder-pro
* **Outil de build :** webpack 5 (configuration : `webpack.config.cjs`)
* **Commande de build :** `npm install && npm run build`

Tous les fichiers source se trouvent dans le dépôt sous `src/js/` et `src/css/`. La correspondance entre les assets compilés et leurs sources est la suivante :

**Scripts admin du plugin (`plugin/assets/js/` ← source dans `src/js/admin/`) :**
* `assets/js/ajax-throttle.min.js` ← `src/js/admin/ajax-throttle.js`
* `assets/js/settings-main.min.js` ← `src/js/admin/settings-main.js`
* `assets/js/notifications.min.js` ← `src/js/admin/notifications.js`
* `assets/js/canvas-settings.min.js` ← `src/js/admin/canvas-settings.js`
* `assets/js/settings-tabs.min.js` ← `src/js/admin/settings-tabs.js`
* `assets/js/pdf-builder-react.min.js` ← compilé depuis `src/js/` (TypeScript/JSX via Babel + webpack)
* `assets/js/vendors.min.js` ← dépendances vendors bundlées par webpack (React, ReactDOM, etc.)

**Bibliothèque tierce intégrée telle quelle :**
* `assets/js/html2canvas.min.js` — html2canvas v1.4.1 (licence MIT) — https://github.com/niklasvh/html2canvas

== Foire Aux Questions ==

= Quelles versions de WordPress sont prises en charge ? =

PDF Builder Pro nécessite WordPress 5.0 ou une version ultérieure.

= Est-il compatible avec WooCommerce ? =

Oui, PDF Builder Pro propose une intégration native de WooCommerce pour la génération automatique de PDF de commandes.

= Quels formats PDF sont pris en charge ? =

Le plugin génère des fichiers PDF standard compatibles avec tous les lecteurs de documents modernes.

== Services externes ==

Ce plugin se connecte aux services tiers suivants. En utilisant ce plugin, vous acceptez leurs conditions d'utilisation et politiques de confidentialité respectives.

= Service de génération PDF (pdf.threeaxe.fr) =
Utilisé pour générer les documents PDF depuis vos modèles. Les données de votre modèle et les informations de commande sont envoyées à ce service pour le rendu. Ce service est fourni par Threeaxe et est nécessaire pour toute génération de PDF.
* URL du service : https://pdf.threeaxe.fr
* Politique de confidentialité : https://hub.threeaxe.fr/privacy-policy/
* Conditions d'utilisation : https://hub.threeaxe.fr/conditions-dutilisation

= Serveur de validation de licence (hub.threeaxe.fr) =
Utilisé pour activer et valider votre clé de licence premium. Lorsque vous saisissez votre clé de licence dans les paramètres du plugin, elle est envoyée à ce serveur pour vérifier son authenticité et activer les fonctionnalités premium. Ce serveur est exploité par Threeaxe, l'auteur du plugin.
* Données envoyées : clé de licence, URL du site, version du plugin, version de WordPress.
* Quand : uniquement lorsque vous activez ou désactivez manuellement une clé de licence dans les paramètres.
* URL du service : https://hub.threeaxe.fr
* Politique de confidentialité : https://hub.threeaxe.fr/privacy-policy/
* Conditions d'utilisation : https://hub.threeaxe.fr/conditions-dutilisation

= Google Fonts (fonts.googleapis.com) =
Utilisé pour charger les polices web lors de la génération des documents PDF. Lorsqu'un PDF est généré, le modèle HTML envoyé au service de rendu Puppeteer inclut un lien vers une feuille de style Google Fonts pour garantir un rendu typographique correct dans le PDF final.
* Données envoyées : requête HTTP standard (adresse IP, user-agent) pour récupérer le CSS et les fichiers de polices.
* Quand : à chaque génération d'un document PDF.
* URL du service : https://fonts.googleapis.com
* Politique de confidentialité : https://policies.google.com/privacy
* Conditions d'utilisation : https://developers.google.com/terms

= API WordPress.org (api.wordpress.org) =
Utilisé pour vérifier les mises à jour du plugin via le mécanisme standard de mises à jour WordPress.
* URL du service : https://api.wordpress.org
* Politique de confidentialité : https://automattic.com/privacy/
* Conditions d'utilisation : https://wordpress.org/about/license/

= Google Drive (oauth2.googleapis.com / www.googleapis.com) =
Intégration optionnelle pour exporter les PDFs générés directement vers Google Drive. Activée uniquement si vous configurez l'intégration Google Drive dans les paramètres du plugin.
* URL du service : https://oauth2.googleapis.com / https://www.googleapis.com
* Politique de confidentialité : https://policies.google.com/privacy
* Conditions d'utilisation : https://developers.google.com/terms

= Dropbox (api.dropboxapi.com / www.dropbox.com) =
Intégration optionnelle pour exporter les PDFs générés directement vers Dropbox. Activée uniquement si vous configurez l'intégration Dropbox dans les paramètres du plugin.
* URL du service : https://api.dropboxapi.com
* Politique de confidentialité : https://www.dropbox.com/privacy
* Conditions d'utilisation : https://www.dropbox.com/terms

= Microsoft OneDrive (graph.microsoft.com / login.microsoftonline.com) =
Intégration optionnelle pour exporter les PDFs générés vers OneDrive. Activée uniquement si vous configurez l'intégration OneDrive dans les paramètres du plugin.
* URL du service : https://graph.microsoft.com
* Politique de confidentialité : https://privacy.microsoft.com/fr-fr/privacystatement
* Conditions d'utilisation : https://www.microsoft.com/fr-fr/servicesagreement

= Slack (slack.com / api.slack.com) =
Intégration optionnelle pour envoyer des notifications PDF vers des canaux Slack. Activée uniquement si vous configurez l'intégration Slack dans les paramètres du plugin.
* URL du service : https://api.slack.com
* Politique de confidentialité : https://slack.com/intl/fr-fr/trust/privacy/privacy-policy
* Conditions d'utilisation : https://slack.com/intl/fr-fr/terms-of-service

= HubSpot (api.hubapi.com) =
Intégration CRM optionnelle pour joindre les PDFs générés aux contacts et deals HubSpot. Activée uniquement si vous configurez l'intégration HubSpot dans les paramètres du plugin.
* URL du service : https://api.hubapi.com
* Politique de confidentialité : https://legal.hubspot.com/privacy-policy
* Conditions d'utilisation : https://legal.hubspot.com/terms-of-service

= Salesforce (login.salesforce.com / .salesforce.com) =
Intégration CRM optionnelle pour joindre les PDFs générés aux enregistrements Salesforce. Activée uniquement si vous configurez l'intégration Salesforce dans les paramètres du plugin.
* URL du service : https://login.salesforce.com
* Politique de confidentialité : https://www.salesforce.com/fr/company/privacy/
* Conditions d'utilisation : https://www.salesforce.com/fr/company/legal/sfdc-website-terms-of-service/

= Feedback de désactivation (threeaxe.france@gmail.com) =
Lors de la désactivation du plugin, une fenêtre modale peut apparaître et vous inviter à partager optionnellement la raison de cette désactivation. Si vous choisissez d'envoyer un feedback, les données suivantes sont transmises par email directement à l'auteur du plugin :
* Données envoyées : raison de désactivation, commentaire optionnel, URL du site, adresse email de l'admin du site, serveur, date/heure.
* Quand : uniquement si vous cliquez sur le bouton « Envoyer le feedback » dans la modale de désactivation. Aucune donnée n'est envoyée si vous ignorez ou fermez la modale.
* Destinataire : threeaxe.france@gmail.com (auteur du plugin, Threeaxe)
Cette démarche est entièrement facultative. Vous pouvez désactiver le plugin sans soumettre de feedback.

**Note :** Toutes les intégrations tierces sont strictement optionnelles et nécessitent une configuration explicite par l'administrateur du site. Aucune donnée n'est envoyée à un service tiers sans votre consentement et votre configuration active.

== Journal des modifications ==

= 1.3.27 =
* Conformité WP.org : ajout de `License URI` dans l'en-tête principal du plugin
* Conformité WP.org : correction de la clé HMAC externalisée via `get_option('pdfib_puppeteer_hmac_key')` (plus de secret hardcodé)
* Conformité WP.org : URL service PDF migré de HTTP vers HTTPS
* Conformité WP.org : remplacement de `FILTER_UNSAFE_RAW` par accès direct `$_POST` avec `wp_unslash()` dans 3 fichiers PHP
* Conformité WP.org : correction XSS DOM JavaScript dans 4 fichiers JS admin (innerHTML avec données serveur remplacé par DOM API / escapeHtml)
* Conformité WP.org : nettoyage du fichier .pot (suppression des entrées dupliquées corrompues avec suffixe `, 'advanced-pdf-invoice-builder`)
* Conformité WP.org : ajout des en-têtes WooCommerce `WC requires at least` et `WC tested up to` dans readme-fr_FR.txt

= 1.3.26 =
* Correctif : PHP Parse error dans Puppeteer_Client.php — accolades fermantes manquantes dans render() et render_image() causant des erreurs fatales
* Correctif : NonEnqueuedStylesheet — remplacement du tag <link> Google Fonts par un @import CSS dans le HTML généré
* Correctif : DevelopmentFunctions — wp_debug_backtrace_summary() remplacé par Exception::getTraceAsString()
* Correctif : DiscouragedFunctions — suppression des appels set_time_limit() et set_error_handler()
* Correctif : NonPrefixedHooknameFound — filtre plugin_locale remplacé par determine_locale()
* Correctif : SlowDBQuery — meta_query supprimé de get_users() / get_posts(), remplacé par SQL direct ou filtrage PHP
* Correctif : NonPrefixedVariableFound — toutes les variables globales préfixées en pdfib_ dans les templates et pages admin
* Correctif : NonPrefixedFunctionFound — suppression des fonctions WP sans préfixe dans settings-contenu.php et les handlers
* Correctif : bugs de syntaxe PHP dans predefined-templates-manager.php et settings-licence.php
* Amélioration : PuppeteerEngine et Puppeteer_Client journalisent sous WP_DEBUG (statut licence, code HTTP visible dans les logs)

= 1.0.3.25 =
* Conformité WP.org : suppression de toutes les violations wp_enqueue restantes (echo "<script>" / "<style>") dans 15+ fichiers — conversion en wp_add_inline_script() ou ajout de protections adaptées
* Conformité WP.org : suppression de l'appel HTTP externe httpbin.org dans PDF_Builder_Test_Suite — remplacé par un appel local home_url()
* Conformité WP.org : remplacement des constantes WP codées en dur WP_CONTENT_DIR / ABSPATH / WP_LANG_DIR par les fonctions WP appropriées (wp_upload_dir(), plugin_dir_path(), load_plugin_textdomain())
* Conformité WP.org : suppression des URLs externes dans l'en-tête de licence de html2canvas.min.js (bibliothèque locale, aucun appel distant)
* Conformité WP.org : mise à jour des URLs de politique de confidentialité et des CGU vers hub.threeaxe.fr dans le readme et les pages de réglages
* Architecture : suppression de PDF_Builder_Cache_Manager — système de cache (400+ lignes, cron, 4 handlers AJAX, UI admin) entièrement retiré ; le plugin fonctionne de manière identique sans lui
* Correctif : résolution de l'erreur JavaScript console causée par les références résiduelles au cache manager supprimé
* Correctif : marges du document non sauvegardées — JS envoie désormais '0' pour les cases non cochées ; PHP valide `_show_` (booléen) et `_margin_` (entier clampé 0-500)
* Correctif : liste DPI dans la modale des modèles affichait tous les DPI au lieu des DPI actifs uniquement — lecture de `pdf_builder_canvas_dpi` (CSV) au lieu de l'ancienne clé `pdf_builder_available_dpi`
* Correctif : un seul DPI sauvegardé avec plusieurs cases cochées — suppression du `name.slice(0,-2)` dans la boucle FormData ; le suffixe `[]` est conservé pour que PHP reçoive un tableau
* Correctif : DPI écrasé à '0' lors de la sauvegarde du formulaire principal — exclusion de `pdf_builder_canvas_dpi/formats/orientations` du foreach dans `save_content_settings()` ; suppression du doublon `canvas_dpi` dans `$general_settings`
* Correctif : erreur 403 sur la génération PNG/JPG dans la métabox WooCommerce — `handle_generate_image()` accepte désormais les deux nonces `pdf_builder_ajax` et `pdf_builder_order_actions` ; ajout du fallback `manage_options` dans la vérification des permissions
* Conformité WP.org : email de feedback de désactivation envoyé à l'auteur du plugin via `apply_filters('pdf_builder_feedback_email', ...)` au lieu de l'email admin du site ; divulgation ajoutée dans le readme

= 1.0.3.24 =
* Qualité du code : correction de 630 erreurs PHPStan (niveau 5) → 0 erreurs — l'analyse statique complète passe maintenant sans erreur
* Correctif : correction de la race condition NONCE_INVALID dans PDFEditorPreferences (guard _initialized, rotation du nonce, plafond de retry)
* Sécurité : error_log sur toutes les zones sensibles PHP (Ajax_Handler, Ajax_Base, Security_Validator, User_Manager, NonceManager, Templates_Ajax x8 handlers, avertissement SECURITY WARNING nonce bypass dans Template_Manager, Database_Initializer)
* Sécurité : console.log/warn/error dans ClientNonceManager (refresh, setNonce, addToFormData) et ajax-throttle.js
* IDE : déclarations d'aide WordPress complètes dans les fichiers IDE — 30+ fonctions et constantes manquantes (correctifs P1005/P1006/P1010/P1011 Intelephense)
* Correctif : suppression du BOM UTF-8 dans templates/admin/templates-page.php
* Conformité WP.org : toutes les notices admin disposent maintenant de l'attribut is-dismissible
* Conformité WP.org : documentation du code source et du processus de build dans le readme
* Conformité WP.org : remplacement de json_encode par wp_json_encode
* Conformité WP.org : remplacement de move_uploaded_file par wp_handle_upload
* Conformité WP.org : refactorisation de PHPMailer via le hook phpmailer_init
* Conformité WP.org : suppression des références CDN directes (polyfills jsdelivr.net)
* Conformité WP.org : renforcement des vérifications de nonce avec wp_unslash + sanitize_text_field
* Conformité WP.org : exclusion des fichiers d'aide IDE du ZIP
* Conformité WP.org : suppression de l'action wp_ajax_test_ajax sans préfixe
* Conformité WP.org : renommage de wp_ajax_verify_canvas_settings_consistency avec préfixe pdf_builder_
* Conformité WP.org : suppression de set_time_limit() du hook global plugins_loaded
* Conformité WP.org : sanitisation de toutes les variables $_SERVER (REQUEST_URI, HTTP_USER_AGENT, REMOTE_ADDR, SERVER_SOFTWARE, CONTENT_TYPE)
* Conformité WP.org : conversion de ob_start() avec callback pour garantir la fermeture du buffer
* Conformité WP.org : réorganisation du code avant la déclaration namespace dans WooCommerce_Integration.php
* Conformité WP.org : remplacement de /wp-admin/admin-ajax.php codé en dur par wp_parse_url(admin_url())
* Conformité WP.org : suppression complète du système de mise à jour EDD (PDF_Builder_Updates_Manager + edd-free-update.php) — les mises à jour sont désormais gérées exclusivement par WordPress.org
* Qualité du code : remplacement de rename() par WP_Filesystem::move() dans le gestionnaire de sauvegarde/restauration
* Qualité du code : normalisation des fins de ligne (LF) dans templates-page.php
* Architecture : création des classes manquantes AnalyticsInterface, ModeSwitcher, CanvasModeProvider, MetaboxModeProvider
* Architecture : mapping PSR-4 PDF_Builder\Analytics\ ajouté dans composer.json, autoload régénéré (86 classes)
* Documentation : documentation de tous les services tiers dans readme.txt
* Intelephense : ajout de l'aide wp_parse_url() (correction erreur P1010 dans Security_Monitor)

= 1.0.3.23 =
* UX : Le bouton « Enregistrer » est désactivé (grisé) au chargement et après une sauvegarde réussie, réactivé dès qu'un champ est modifié

= 1.0.3.22 =
* Correctif : correction des commentaires embarqués dans les chaînes SQL de settings-templates.php

= 1.0.3.21 =
* Correctif : correction de 63 occurrences de commentaires embarqués dans des chaînes SQL multilignes — 11 fichiers concernés

= 1.0.3.20 =
* Conformité WP.org : escaping étendu avec wp_kses_post(), esc_url(), esc_js(), esc_html()
* Conformité WP.org : sanitisation des données GET avec wp_unslash() + sanitize_text_field()
* Conformité WP.org : stable_tag synchronisé à 1.0.3.20
* Correctif : suppression des 4 fichiers JS vides du dépôt
* Correctif : correction des commentaires embarqués dans les chaînes SQL (63 occurrences, 11 fichiers)

= 1.0.3.15 =
* Améliorations des performances et corrections de bugs
* Mise à jour des dépendances tierces
* Fonctionnalités de sécurité renforcées
* Documentation améliorée

= 1.0.0 =
* Première version publique

== Avis de mise à jour ==

= 1.3.26 =
Conformité WordPress.org : préfixe du plugin renommé en pdfib/PDFIB dans 176 fichiers PHP. Toutes les vérifications sont passées. Traces debug error_log supprimées.

= 1.0.3.24 =
Mise à jour importante : révision complète de la conformité WordPress.org. Suppression du système de mise à jour EDD ; les mises à jour sont désormais gérées par WordPress.org.

= 1.0.3.15 =
Mise à jour recommandée avec des correctifs et améliorations importants.
