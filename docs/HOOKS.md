# Hook System — Advanced PDF Invoice Builder

Ce document référence tous les hooks d'action et de filtre exposés par le plugin FREE à destination du plugin PRO (ou de toute extension tierce).

---

## Architecture d'intégration

```
plugins_loaded (priority 5)  → FREE plugin initialise son admin, enregistre ses menus
plugins_loaded (priority 20) → PRO plugin se connecte aux hooks FREE via PremiumHooks::register_hooks()
```

Le plugin FREE **n'importe aucune classe PRO** directement. Toute communication se fait uniquement via ces hooks.

---

## Actions

### `pdfib_admin_menu_after_home`

Déclenché après l'enregistrement de la page d'accueil FREE dans `add_admin_menu()`. Permet au PRO (ou tout add-on) d'enregistrer ses propres pages dans le menu admin WordPress.

```php
add_action( 'pdfib_admin_menu_after_home', function() {
    add_submenu_page(
        'advanced-pdf-invoice-builder',
        'Ma page PRO',
        'Ma page PRO',
        'manage_options',
        'mon-slug',
        'ma_callback'
    );
} );
```

**Fichier source :** `plugin-free/src/Admin/class-pdfbuilderadmin.php`

---

### `pdfib_canvas_settings_updated`

Déclenché après la sauvegarde des réglages canvas.

**Paramètres :**
| # | Type | Description |
|---|------|-------------|
| 1 | `array` | Nouveau tableau de paramètres canvas |

```php
add_action( 'pdfib_canvas_settings_updated', function( array $settings ) {
    // Réagir à la mise à jour des paramètres canvas.
} );
```

---

### `pdfib_after_metabox_buttons`

Déclenché dans la meta-box WooCommerce après les boutons PDF natifs. Utilisé par le PRO pour ajouter les boutons d'export PNG/JPG.

**Paramètres :**
| # | Type | Description |
|---|------|-------------|
| 1 | `int` | ID de la commande WooCommerce |
| 2 | `int` | ID du template sélectionné |
| 3 | `bool` | `true` si la licence premium est active |
| 4 | `string` | Nonce de sécurité |
| 5 | `string` | URL AJAX |
| 6 | `\WC_Order` | Objet commande WooCommerce |

```php
add_action( 'pdfib_after_metabox_buttons', function( $order_id, $template_id, $is_premium, $nonce, $ajax_url, $order ) {
    // Ajouter des boutons supplémentaires dans la meta-box.
}, 10, 6 );
```

---

### `pdfib_render_templates_card_editor_action`

Déclenché dans la carte d'un template custom sur la page liste. Permet au PRO d'afficher son bouton d'édition avancé. Si aucun hook n'est enregistré, le FREE affiche un bouton ✏️ de fallback.

**Paramètres :**
| # | Type | Description |
|---|------|-------------|
| 1 | `int` | ID du template |
| 2 | `string` | Nom du template |
| 3 | `array` | Données complètes du template |

```php
add_action( 'pdfib_render_templates_card_editor_action', function( $template_id, $template_name, $template ) {
    $url = admin_url( 'admin.php?page=pdf-builder-react-editor&template_id=' . $template_id );
    echo '<a href="' . esc_url( $url ) . '" class="button button-primary">✏️ Éditer</a>';
}, 10, 3 );
```

**Fichier source :** `plugin-free/templates/admin/templates-page.php`

---

### Hooks canvas premium (actions de rendu UI)

Ces hooks permettent au PRO d'injecter des champs ou notices dans les sections premium de l'éditeur canvas. Chaque action reçoit un paramètre : le contexte courant (tableau ou objet selon le cas).

| Hook | Description |
|------|-------------|
| `pdfib_display_dpi_premium_options` | Options DPI premium dans les réglages d'affichage |
| `pdfib_display_format_premium_options` | Options de format premium |
| `pdfib_display_orientation_premium_options` | Options d'orientation premium |
| `pdfib_pdf_advanced_premium_fields` | Champs PDF avancés |
| `pdfib_pdf_export_format_premium_notice` | Notice sur le format d'export |
| `pdfib_pdf_advanced_premium_notice` | Notice avancée PDF |
| `pdfib_canvas_margins_premium_fields` | Champs marges canvas |
| `pdfib_canvas_custom_colors_premium_fields` | Champs couleurs personnalisées |
| `pdfib_canvas_navigation_premium_fields` | Champs navigation canvas |
| `pdfib_canvas_behavior_premium_fields` | Champs comportement canvas |
| `pdfib_canvas_export_format_premium_options` | Options format d'export canvas |

---

### `pdfib_template_deleted`

Déclenché quand un template custom est supprimé.

**Paramètres :**
| # | Type | Description |
|---|------|-------------|
| 1 | `int` | ID du template supprimé |
| 2 | `string` | Nom du template |

---

### `pdfib_reschedule_cron_events`

Déclenché pour forcer la reprogrammation des événements cron. Utile si le plugin PRO ajoute ses propres tâches planifiées.

---

## Filtres

### `pdfib_predefined_templates_manager`

Permet au PRO d'injecter son instance de `PredefinedTemplatesManager`. Le FREE appelle ce filtre pour obtenir le gestionnaire de templates prédéfinis.

**Valeur par défaut :** `null`  
**Valeur attendue :** objet implémentant les méthodes nécessaires, ou `null`

```php
add_filter( 'pdfib_predefined_templates_manager', function( $manager ) {
    return MyProTemplatesManager::get_instance();
} );
```

**Fichier source :** `plugin-free/src/Admin/class-pdfbuilderadmin.php`

---

### `pdfib_can_use_feature`

Contrôle si une fonctionnalité donnée est accessible. Le FREE retourne `false` pour les features premium. Le PRO filtre et retourne `true` si la licence est valide.

**Paramètres :**
| # | Type | Description |
|---|------|-------------|
| 1 | `bool` | Valeur actuelle (décidée par la logique FREE) |
| 2 | `string` | Nom de la fonctionnalité |

**Features connues :**

| Feature | Description |
|---------|-------------|
| `advanced_templates` | Accès aux templates personnalisés supplémentaires |
| `image_export` | Export PNG/JPG |
| `advanced_canvas` | Options canvas avancées (marges, DPI, orientation) |
| `unlimited_wc_pdfs` | Génération WooCommerce sans limite de taux |

```php
add_filter( 'pdfib_can_use_feature', function( bool $can_use, string $feature_name ): bool {
    if ( my_license_is_active() ) {
        return true;
    }
    return $can_use;
}, 10, 2 );
```

**Fichier source :** `plugin-free/src/Managers/class-pdfbuilderfeaturemanager.php`

---

### `pdfib_license_manager_instance`

Injection de l'instance du `LicenseManager`. Le FREE demande ce filtre pour savoir si une licence est active ; c'est le PRO qui l'implémente.

**Valeur par défaut :** `null`  
**Valeur attendue :** instance de `\PDFIB\Managers\PdfBuilderLicenseManager`, ou `null`

```php
add_filter( 'pdfib_license_manager_instance', function( $instance ) {
    return PdfBuilderLicenseManager::get_instance();
} );
```

---

### `pdfib_premium_templates`

Filtre retournant la liste des templates premium disponibles dans la galerie.

**Valeur par défaut :** `[]`  
**Valeur attendue :** `array` de templates (même structure que les templates prédéfinis)

```php
add_filter( 'pdfib_premium_templates', function( array $templates ): array {
    $templates[] = [ 'id' => 'pro-invoice-001', 'name' => 'Invoice Premium', ... ];
    return $templates;
} );
```

---

### `pdfib_predefined_templates_dir`

Chemin vers le dossier des templates prédéfinis. Le PRO peut substituer son propre dossier (si licence active).

**Valeur par défaut :** `plugin-free/templates/predefined/`

```php
add_filter( 'pdfib_predefined_templates_dir', function( string $dir ): string {
    if ( is_license_active() ) {
        return plugin_dir_path( __DIR__ ) . 'templates/predefined/';
    }
    return $dir;
} );
```

---

### `pdfib_editor_script_data`

Filtre sur les données JSON localisées injectées dans l'éditeur React. Permet d'ajouter des données (licence, features) accessibles côté JS.

**Valeur :** `array` associatif

```php
add_filter( 'pdfib_editor_script_data', function( array $data ): array {
    $data['isPremium'] = true;
    $data['licenseKey'] = 'XXXX-XXXX-XXXX';
    return $data;
} );
```

---

### `pdfib_license_tab_data`

Données utilisées par l'onglet Licence dans les réglages. Permet au PRO d'enrichir l'affichage.

---

### `pdfib_get_template_for_status`

Filtre pour mapper un statut WooCommerce à un ID de template. Utile si le PRO gère son propre mapping.

**Paramètres :**
| # | Type | Description |
|---|------|-------------|
| 1 | `int\|null` | ID du template mappé (null = pas de mapping) |
| 2 | `string` | Clé du statut WooCommerce (ex: `wc-completed`) |

---

## Ordre de chargement recommandé pour un add-on

```php
// Mon add-on : priority 20 pour se connecter APRÈS le FREE (priority 5)
add_action( 'plugins_loaded', function() {
    if ( ! defined( 'PDFIB_PLUGIN_FILE' ) ) {
        return; // FREE n'est pas actif
    }
    // Enregistrer mes hooks ici
    add_filter( 'pdfib_can_use_feature', ... );
    add_filter( 'pdfib_license_manager_instance', ... );
    add_action( 'pdfib_admin_menu_after_home', ... );
}, 20 );
```
