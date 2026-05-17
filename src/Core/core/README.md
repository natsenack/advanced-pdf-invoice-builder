# PDF Builder Pro - Architecture de Centralisation

## Vue d'ensemble

Ce document décrit l'architecture de centralisation mise en place pour éliminer la duplication de code dans le plugin PDF Builder Pro. Au lieu d'avoir des constantes, configurations et mappings éparpillés dans de nombreux fichiers, toutes les données répétitives ont été centralisées dans des classes dédiées.

## Classes de Mappings Centralisés

### 1. PDF_Builder_Defaults
**Fichier:** `plugin/core/defaults.php`

Centralise toutes les valeurs par défaut utilisées dans le plugin :
- Couleurs (thème, canvas, éléments)
- Dimensions (largeurs, hauteurs, marges)
- Valeurs techniques (DPI, qualité, timeouts)
- Formats de papier
- Polices par défaut
- Messages système
- États et statuts

**Utilisation:**
```php
$bg_color = PDF_Builder_Defaults::get_color('canvas_background');
$paper_size = PDF_Builder_Defaults::get_dimension('a4');
$message = PDF_Builder_Defaults::get_message('save_success');
```

### 2. PDF_Builder_Config_Manager
**Fichier:** `plugin/core/config-manager.php`

Gère les configurations d'options WordPress et les réponses AJAX :
- Configurations d'options avec types et validation
- Sanitisation automatique des valeurs
- Réponses AJAX standardisées
- Gestion des erreurs de configuration

**Utilisation:**
```php
$option_config = PDF_Builder_Option_Config_Manager::get_option_config('canvas_width');
$sanitized_value = PDF_Builder_Option_Config_Manager::sanitize_value($input, 'number');
$response = PDF_Builder_Option_Config_Manager::get_response_config('success');
```

### 3. PDF_Builder_Canvas_Mappings
**Fichier:** `plugin/core/canvas-mappings.php`

Mappings entre les champs de formulaire canvas et les options WordPress :
- Mappings généraux et par catégorie
- Champs numériques vs checkbox
- Validation automatique des types

**Utilisation:**
```php
$option_name = PDF_Builder_Canvas_Mappings::get_option_name('canvas_bg_color');
$numeric_fields = PDF_Builder_Canvas_Mappings::get_numeric_fields('dimensions');
```

### 4. PDF_Builder_Template_Mappings
**Fichier:** `plugin/core/template-mappings.php`

Mappings pour les propriétés des éléments de template :
- Propriétés communes à tous les éléments
- Propriétés spécifiques par type d'élément
- Règles de validation par élément

**Utilisation:**
```php
$element_props = PDF_Builder_Template_Mappings::get_element_type_properties('text');
$required_props = PDF_Builder_Template_Mappings::get_required_properties('image');
```

### 5. PDF_Builder_JS_Mappings
**Fichier:** `plugin/core/js-mappings.php`

Constantes et objets JavaScript centralisés :
- Événements, classes CSS, sélecteurs
- Actions AJAX, types d'éléments
- Objets de configuration JavaScript
- Génération automatique de scripts

**Utilisation:**
```php
$event_name = PDF_Builder_JS_Mappings::get_js_constant('EVENT_CANVAS_READY');
$js_script = PDF_Builder_JS_Mappings::generate_js_constants_script();
```

### 6. PDF_Builder_Validation_Mappings
**Fichier:** `plugin/core/validation-mappings.php`

Règles de validation centralisées :
- Règles générales et par type d'élément
- Validation de formulaires
- Messages d'erreur standardisés

**Utilisation:**
```php
$validation = PDF_Builder_Validation_Mappings::validate_element($element_data, 'text');
$form_valid = PDF_Builder_Validation_Mappings::validate_form($form_data, 'canvas_settings');
```

### 7. PDF_Builder_Error_Mappings
**Fichier:** `plugin/core/error-mappings.php`

Gestion centralisée des erreurs :
- Codes d'erreur et messages
- Réponses d'erreur standardisées
- Messages de succès et d'avertissement

**Utilisation:**
```php
$error_response = PDF_Builder_Error_Mappings::create_error_response('VALIDATION_ERROR');
$success_msg = PDF_Builder_Error_Mappings::get_success_message('TEMPLATE_SAVED');
```

### 8. PDF_Builder_Security_Mappings
**Fichier:** `plugin/core/security-mappings.php`

Configurations de sécurité :
- Règles de sanitisation
- Permissions et capacités
- Validation de sécurité
- Nonces et rate limiting

**Utilisation:**
```php
$sanitized = PDF_Builder_Security_Mappings::sanitize_value($input, 'email');
$nonce = PDF_Builder_Security_Mappings::generate_nonce('save_template');
```

### 9. PDF_Builder_Performance_Mappings
**Fichier:** `plugin/core/performance-mappings.php`

Optimisations de performance :
- Seuils et métriques
- Stratégies d'optimisation
- Recommandations automatiques
- Configurations adaptatives

**Utilisation:**
```php
$level = PDF_Builder_Performance_Mappings::get_performance_level('memory_usage', 80);
$recommendations = PDF_Builder_Performance_Mappings::generate_recommendations($metrics);
```

### 10. PDF_Builder_Compatibility_Mappings
**Fichier:** `plugin/core/compatibility-mappings.php`

Gestion de la compatibilité :
- Support navigateur
- Versions WordPress/PHP
- Polyfills et fallbacks
- Détection de fonctionnalités

**Utilisation:**
```php
$compatible = PDF_Builder_Compatibility_Mappings::is_browser_supported('chrome', '90');
$polyfills = PDF_Builder_Compatibility_Mappings::generate_polyfills_js();
```

### 11. PDF_Builder_I18n_Mappings
**Fichier:** `plugin/core/i18n-mappings.php`

Internationalisation :
- Chaînes de traduction
- Formats régionaux
- Formes plurielles
- Génération de fichiers POT

**Utilisation:**
```php
$text = PDF_Builder_I18n_Mappings::__('save', 'pdf-builder-pro');
$currency = PDF_Builder_I18n_Mappings::format_currency(123.45);
```

### 12. PDF_Builder_Config_Mappings
**Fichier:** `plugin/core/config-mappings.php`

Configurations générales du plugin :
- Chemins et URLs
- Configuration base de données
- Paramètres AJAX
- Cache, sécurité, performance

**Utilisation:**
```php
$ajax_action = PDF_Builder_Config_Mappings::get_ajax_action('save_template');
$cache_ttl = PDF_Builder_Config_Mappings::get_cache_ttl('template');
```

### 13. PDF_Builder_API_Mappings
**Fichier:** `plugin/core/api-mappings.php`

Configuration de l'API REST :
- Endpoints et schémas
- Codes de réponse
- Authentification
- Webhooks

**Utilisation:**
```php
$endpoint = PDF_Builder_API_Mappings::get_full_endpoint_url('templates', 'list');
$validation = PDF_Builder_API_Mappings::validate_api_data($data, 'template');
```

## Architecture Générale

### Structure des Classes
Toutes les classes suivent le même pattern :
- Propriétés privées statiques pour les données
- Méthodes statiques publiques pour l'accès
- Méthodes utilitaires pour la manipulation
- Validation et sanitisation intégrées

### Inclusion des Classes
Le fichier `plugin/core/mappings.php` sert de point d'entrée unique :
```php
require_once 'plugin/core/mappings.php';

// Accès via la classe principale
$defaults_class = PDF_Builder_Core_Mappings::get('defaults');
$value = PDF_Builder_Core_Mappings::call('defaults', 'get_color', 'primary');

// Ou directement via les classes
$value = PDF_Builder_Defaults::get_color('primary');
```

## Avantages de cette Architecture

### 1. Élimination de la Duplication
- Toutes les données répétitives sont définies une seule fois
- Mise à jour centralisée des valeurs
- Réduction significative du code dupliqué

### 2. Maintenance Facilitée
- Modifications centralisées
- Validation automatique
- Détection d'erreurs simplifiée

### 3. Performance Améliorée
- Cache automatique des données
- Chargement optimisé
- Réduction de la mémoire utilisée

### 4. Évolutivité
- Ajout facile de nouvelles fonctionnalités
- Extension simple des mappings existants
- Architecture modulaire

### 5. Sécurité Renforcée
- Sanitisation automatique
- Validation des entrées
- Gestion centralisée des permissions

## Migration Existante

Pour migrer le code existant :

1. **Identifier les données dupliquées** dans les fichiers existants
2. **Remplacer les constantes** par des appels aux classes centralisées
3. **Utiliser les méthodes de validation** pour les entrées utilisateur
4. **Mettre à jour les appels AJAX** pour utiliser les nouvelles configurations

### Exemple de Migration
**Avant:**
```php
// Dans settings-ajax.php
$option_configs = [
    'canvas_width' => ['type' => 'number', 'default' => 595, 'min' => 100, 'max' => 2000],
    'canvas_height' => ['type' => 'number', 'default' => 842, 'min' => 100, 'max' => 2000],
    // ... beaucoup de duplication
];
```

**Après:**
```php
// Dans settings-ajax.php
$option_configs = PDF_Builder_Option_Config_Manager::get_option_configs();
```

## Tests et Validation

Chaque classe inclut des méthodes de validation :
- `validate_config()` pour vérifier les configurations
- `validate_data()` pour valider les données utilisateur
- `check_security()` pour la sécurité
- `generate_recommendations()` pour les optimisations

## Documentation et Support

- Chaque classe est auto-documentée avec des commentaires PHP
- Les méthodes incluent des exemples d'utilisation
- Validation automatique des paramètres
- Messages d'erreur descriptifs

Cette architecture transforme un code fragmenté et dupliqué en un système centralisé, maintenable et évolutif.