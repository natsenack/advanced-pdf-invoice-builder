# Fonctionnalités détaillées — PDF Builder Pro V2

## 🎨 Système de templates

### Éditeur visuel intuitif

- **Drag & drop libre** : positionnement pixel-perfect sur la page
- **Grille et repères** : alignement automatique d'éléments
- **Undo/Redo illimutés** : marche arrière sans limite
- **Contrôle de calques** : organisez les éléments en profondeur

### Éléments supportés

- **Texte dynamique** : variables client, commande, produit
- **Images/logos** : formats PNG, JPG, SVG (redimensionnement libre)
- **Tableaux** : entêtes, pieds de page, bordures custom
- **Formes** : rectangles, cercles, lignes
- **Décimales** : formatage prix, quantités, pourcentages
- **Signatures digitales** : capture ou uploads

### Modèles professionnels inclus

**Free** (1 templates) :

- Facture
- Devis basic

**premium** (ilimté) :

- Factures
- Devis commerciaux
- ...et plus de 20 autres

---

## 💾 Gestion des données

### Variables dynamiques

L'éditeur détecte automatiquement les variables disponibles :

**Client** :

- Nom, email, téléphone
- Adresse (rue, CP, ville, pays)
- Champs personnalisés (SIREN, code comptable, etc.)

**Commande** :

- Numéro, date, statut
- Total HT/TTC, TVA, remises
- Devise, langue
- Métadonnées custom WordPress

**Produits** :

- Titre, SKU, prix
- Catégorie, description
- Images, quantité
- Attributs WooCommerce

**Entreprise** :

- Nom, SIREN, TVA
- Logo, signature
- Coordonnées complètes
- Conditions générales (auto-générées)

### Calculs et formules

- **Sommes** : total ligne, total commande, TVA totale
- **Pourcentages** : remise %, frais %
- **Conversions** : TTC depuis HT avec taux TVA
- **Formatages** : devise, langue, nombre décimales

---

## ⚡ Performance & Cache

### Cache intelligent (version Premium)

- **Transients WordPress** : cache de 60 minutes par défaut
- **Compression gzip** : économie ~70% sur stockage
- **Invalidation smart** : vide automatiquement quand les données changent
- **Métriques** : voyez ce qui est caché et gains en performance

### Performances mesurées

- **Génération PDF** : 0.5–2s (vs 5–30s sans cache)
- **Chargement template** : instant (données en cache)
- **Taille fichiers** : −40% en moyenne
- **Bande passante** : réduite de 50%

### Statistiques de cache

Dashboard affiche :

- Nombre de fichiers en cache
- Poids total en cache
- Ratio hit/miss
- Âge moyen des entrées cachées
- Bouton "vider tout" d'un clic

---

## 🔗 Intégration WooCommerce

### Automation des factures

- **Auto-génération** : une facture par statut de commande (paiement reçu, prêt à livrer, etc.)
- **Synchronisation** : produits, clients, prix actualizés en temps réel
- **Statuts intelligents** : déclenchez différentes actions par statut
- **Client email** : envoyez la facture auto au client
- **Archive** : stockez tous les PDF générés

### Intégration produits

- **Métadonnées** : SKU, catégories, attributs
- **Tarification** : prix achat, vente, promotions
- **Images** : affichage mini dans les listes commande
- **Stock** : quantités available
- **Remises** : codes coupon, volumes

### États de commande custom

- Facture
- Être payé/impayé
- Pré-facture
- Facture d'avoir (remboursement)

### Synchronization client

- Nom, adresse de facturation/livraison
- Email, téléphone
- Pays/devise (automatique)
- Historique achat
- Métadonnées custom

---

## 🌍 Multilingue & localisation

### Langues supportées

- 🇫🇷 Français (complet)
- 🇬🇧 English (complet)

### Conversion de devises

- **Taux live** : EUR, USD, GBP, JPY, etc.
- **Précision** : mise à jour quotidienne
- **Affichage** : symbole ou code devise
- **Paramètres** : décimales, séparateurs régionaux

### Formats régionaux

- **Dates** : 12/31/2024, 31 décembre 2024, 2024-12-31
- **Nombres** : 1,234.56 ou 1.234,56 selon région
- **Monnaie** : $1,234 ou 1.234 €
- **Direction texte** : LTR/RTL automatique

---

## 🔒 Sécurité & RGPD

### Conformité RGPD

- **Audit log** : chaque action est tracée (qui, quand, quoi)
- **Consentement** : opt-in/out pour chaque type de données
- **Droit d'accès** : exportez vos données en JSON/CSV
- **Droit à l'oubli** : anonymisez vos données avec 1 clic
- **Droit de rectification** : modifiez vos données stockées

### Chiffrement

- **AES-256** : données sensibles au repos
- **TLS/SSL** : en transit
- **Clés secrètes** : stockées sécurisées
- **Rotation keys** : support automatique

### Contrôle d'accès

- **Rôles WordPress** : admin, éditeur, contributeur, subscriber
- **Permissions granulaires** : éditer, publier, supprimer templates
- **IP whitelist** : restrictions d'accès par IP pour admins
- **Sessions** : timeout automatique (15 minutes inactivité)

### Sauvegardes & backups

- **Sauvegardes auto** : quotidiennes incluant templates et paramètres
- **Historique** : 30 jours de versions précédentes
- **Restauration** : 1-click rollback à n'importe quel point

---

## 🚀 API REST

### Authentification

- **API Keys** : création simple dans paramètres
- **OAuth2** : pour usage réseau sécurisé (Premium)
- **JWT tokens** : valides 1 heure
- **IP whitelist** : restrictions optionnelles

### Endpoints (100+ au total)

#### Templates

```
GET  /api/v1/templates              # Lister tous les templates
GET  /api/v1/templates/{id}         # Afficher un template
POST /api/v1/templates              # Créer un template
PUT  /api/v1/templates/{id}         # Modifier un template
DELETE /api/v1/templates/{id}       # Supprimer un template
```

#### Génération PDF

```
POST /api/v1/generate               # Générer un PDF
POST /api/v1/generate/batch         # Batch (100+ PDFs)
GET  /api/v1/jobs/{job_id}         # Status d'un travail
```

#### Commandes WooCommerce

```
GET  /api/v1/orders                 # Lister commandes
GET  /api/v1/orders/{id}/invoice   # Récupérer facture PDF
POST /api/v1/orders/{id}/invoice   # Créer facture
```

#### Clientes

```
GET  /api/v1/customers             # Lister clients
POST /api/v1/customers/{id}/contacts  # Ajouter contact
```

### Webhooks

**Événements disponibles** :

- `template.created` — template créé
- `template.updated` — template modifié
- `pdf.generated` — PDF généré
- `pdf.failed` — éroration de génération
- `order.invoiced` — commande facturée
- `customer.updated` — client mis à jour

**Payload** : JSON complet avec contexte entier

### Limitations (selon plan)

| Limite           | **Gratuit** | **Premium** |
| ---------------- | ----------- | ----------- |
| Appels/jour      | 100         | 1,000       |
| Templates access | 3           | 25+         |
| Webhooks         | non         | 5           |
| Rétention job    | 7j          | 30j         |

---

## 📊 Rapports & analytique

Fonctionnalité en développement. Actuellement disponible :

- Historique des PDF générés dans WordPress
- Logs d'erreur accessibles via paramètres système
- Export manuel des templates et données

---

## 🎯 Automation & triggers

### Auto-génération

- **Sur statut commande** : génération PDF au changement de statut WooCommerce
  - Paiement reçu → Facture générée
  - En préparation → Bon de commande
  - Expédié → Bon de livraison
- **Email automatique** : envoi du PDF au client via WooCommerce email system
- **Archive locale** : tous les PDFs conservés sur le serveur

### Actions post-génération supportées

- **Email au client** : intégration WooCommerce
- **Sauvegarde serveur** : archivage automatique
- **Téléchargement** : client peut télécharger le PDF généré

---

## 🌐 Multisite WordPress

**Compatibilité** : PDF Builder Pro peut être installé sur un réseau multisite WordPress. Chaque site fonctionne indépendamment avec ses propres templates et paramètres.

---

## 📱 Responsive & impression

### Responsive

- **Écrans** : desktop, tablette, mobile (mode lecture)
- **Impression** : optimisée pour A4, Letter, custom sizes
- **Zoom** : d'édition, d'aperçu, d'impression

### Formats papier & impression

- **Format A4** : dimension standard, optimisée pour tous les templates
- **Orientation** : portrait supportés
- **Marges personnalisables** : contrôle fin des espacements
- **Mode économique** : réduction d'encre pour impression

---

## 🔄 Intégrations disponibles

### E-commerce

- **WooCommerce** : intégration native et complète (5.0+)
  - Auto-génération factures
  - Sync produits, clients, commandes
  - Statuts custom

### Autres extensions WordPress

- **Éditeurs visuels** : Elementor, Divi (via shortcode)
- **Constructeurs** : Gutenberg natif
- **Champs personnalisés** : support ACF et post meta

### Notes

PDF Builder Pro est conçu pour WordPress et WooCommerce. Les intégrations externes (Salesforce, HubSpot, Slack, etc.) ne sont pas supportées actuellement.

---

## 🎓 Documentation & support

### Documentation

- **Docs officielles** : https://github.com/natsenack/wp-pdf-builder-pro

### Support

- 📧 **Email** : threeaxe.france@gmail.com (réponse <12h)
- 🎥 **Live chat** : https://github.com/natsenack/wp-pdf-builder-pro
