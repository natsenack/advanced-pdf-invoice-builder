# Guide de migration — 1.3.27 → 1.0.0

Ce document s'adresse aux utilisateurs qui mettent à jour depuis la version **1.3.27** (plugin monolithique) vers la nouvelle architecture **1.0.0** (édition FREE + extension PRO).

---

## Résumé des changements

| Aspect | v1.3.27 (monolithique) | v1.0.0 FREE |
|---|---|---|
| Nombre de templates | Illimité | **1** |
| Éditeur visuel | ✅ | ✅ |
| Templates prédéfinis (4) | ✅ | ✅ |
| Galerie 25+ templates premium | ✅ (avec licence) | ❌ (PRO requis) |
| Export PNG / JPG | ✅ (avec licence) | ❌ (PRO requis) |
| Options canvas avancées | ✅ (avec licence) | ❌ (PRO requis) |
| Intégration WooCommerce | ✅ | ✅ |
| Clé de licence | Obligatoire pour features premium | Gérée par le plugin PRO |

---

## Étapes de migration

### 1. Sauvegardez votre base de données

Avant toute mise à jour, exportez votre base de données WordPress. En cas de problème, vous pourrez revenir en arrière.

```bash
# Via WP-CLI
wp db export backup-before-upgrade.sql
```

### 2. Désactivez l'ancienne version

Dans _Extensions → Extensions installées_ :
- Désactivez **Advanced PDF Invoice Builder** (v1.3.27)

Ne le supprimez pas encore — vos templates sont en base de données et seront conservés.

### 3. Installez la nouvelle version FREE

- Téléchargez `advanced-pdf-invoice-builder-1.0.0.zip` depuis WordPress.org ou le dépôt
- _Extensions → Ajouter → Téléverser une extension_
- Activez le plugin

> Vos templates existants sont conservés. Seul le **premier template** sera éditable en mode FREE.

### 4. (Optionnel) Installez le plugin PRO

Si vous avez une clé de licence valide :
- Téléchargez `advanced-pdf-invoice-builder-pro-1.0.0.zip` depuis [hub.threeaxe.fr](https://hub.threeaxe.fr)
- _Extensions → Ajouter → Téléverser une extension_
- Activez le plugin PRO
- Dans _PDF Builder → Licence_, entrez votre clé de licence et cliquez **Activer**

Toutes vos fonctionnalités premium seront restaurées.

---

## Que se passe-t-il si j'ai plus d'un template en v1.3.27 ?

Vos templates sont **tous conservés en base de données** — aucune donnée n'est supprimée.

En mode FREE sans PRO :
- Vous pouvez voir la liste de tous vos templates
- Seul le **1er template** (ID le plus bas) peut être édité
- Les autres templates restent en lecture seule jusqu'à l'activation du PRO

Avec le PRO activé et une licence valide :
- Tous vos templates redeviennent éditables immédiatement

---

## Impacts sur les hooks personnalisés

Si vous utilisiez des hooks de la v1.3.27, voici les équivalents en v1.0.0 :

| v1.3.27 | v1.0.0 | Notes |
|---|---|---|
| `pdfib_admin_menu_after_home` | `pdfib_admin_menu_after_home` | Inchangé |
| `pdfib_predefined_templates_manager` | `pdfib_predefined_templates_manager` | Inchangé |
| `pdfib_can_use_feature` | `pdfib_can_use_feature` | Inchangé |
| `pdfib_license_manager_instance` | `pdfib_license_manager_instance` | Inchangé (géré par PRO) |
| `pdfib_premium_templates` | `pdfib_premium_templates` | Inchangé (géré par PRO) |

Tous les hooks publics sont identiques — pas de rupture de compatibilité si vous avez des personnalisations.

---

## Questions fréquentes

**Q : Mes PDFs générés existants seront-ils supprimés ?**  
R : Non. Les fichiers PDF déjà générés et stockés sur votre serveur ne sont pas affectés.

**Q : Ma clé de licence v1.3.27 fonctionne-t-elle avec v1.0.0 PRO ?**  
R : Oui, si votre clé est toujours valide sur hub.threeaxe.fr, elle fonctionne sans action supplémentaire.

**Q : Puis-je garder v1.3.27 et ne pas migrer ?**  
R : Oui, v1.3.27 reste fonctionnelle. Cependant, les futures mises à jour de sécurité et fonctionnalités seront uniquement publiées sur v1.0.0.

**Q : La mise à jour est-elle gratuite ?**  
R : L'édition FREE est gratuite et disponible sur WordPress.org. Si vous aviez une licence active en v1.3.27, contactez le support sur hub.threeaxe.fr pour vérifier votre accès PRO.

---

## Support

- Documentation : [plugin-free/docs/](docs/)
- Issues : [GitHub](https://github.com/natsenack/wp-pdf-builder/issues)
- Support PRO : [hub.threeaxe.fr](https://hub.threeaxe.fr)