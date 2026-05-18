# Changelog - Advanced PDF Invoice Builder (FREE)

Ce fichier conserve l'historique utile pour la version FREE. L'historique détaillé de développement reste dans le dépôt git.

## Version 1.0.0 - Split FREE / PRO

- Séparation officielle du codebase en deux plugins distincts.
- FREE conserve l'éditeur visuel, les templates de base et l'intégration WooCommerce.
- Les fonctionnalités premium sont désormais exposées uniquement via le plugin PRO.
- Les hooks d'intégration PRO vers FREE restent stables via les filtres publics.

## Points de conformité et packaging

- Les fichiers de migration et de documentation non nécessaires à l'exécution sont déplacés hors du root du plugin.
- Le build de production exclut la documentation interne du ZIP livré.
- Les warnings WordPress.org liés aux fichiers markdown racine doivent être évités dans le package final.
