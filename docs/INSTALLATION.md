# Guide d'installation — PDF Builder Pro V2

## ⚙️ Prérequis système

### Serveur WordPress
- **WordPress** : 5.0 ou plus récent
- **PHP** : 7.4 ou plus (8.0+ recommandé)
- **MySQL/MariaDB** : 5.7 ou plus
- **Espace disque** : minimum 500 MB (1 GB recommandé)
- **Mémoire** : 256 MB alloué à WordPress (512 MB+ recommandé)

### Plugins recommandés
- **WooCommerce** 5.0+ (pour intégration e-commerce)
- **Elementor** ou **Divi** : optionnel (intégration native)
- **Caching plugin** : WP Super Cache, W3 Total Cache (optionnel mais recommandé)

### Thème
- Tous les thèmes WordPress sont compatibles
- Pas de dépendance de thème

### Permissions
- FTP ou accès SSH pour téléversement plugins
- Accès admin WordPress
- Dossier `/wp-content/plugins/` inscriptible

---

## 📦 Installation — 5 minutes

### Étape 1 : Télécharger le plugin

#### Depuis WordPress.org (Gratuit)
1. Aller à **Extensions > Ajouter**
2. Rechercher "PDF Builder Pro"
3. Cliquer **Installer maintenant**
4. Cliquer **Activer**

#### Depuis le site officiel (Pro/Entreprise)
1. Télécharger le fichier `.zip` depuis votre compte
2. Aller à **Extensions > Ajouter**
3. Cliquer **Téléverser une extension**
4. Sélectionner le fichier `.zip`
5. Cliquer **Installer maintenant**
6. Cliquer **Activer**

#### Via SFTP
1. Extraire le fichier `.zip`
2. Téléverser le dossier `pdf-builder-pro/` vers `/wp-content/plugins/`
3. Aller à **Extensions** et cliquer **Activer**

---

### Étape 2 : Configuration initiale

#### A. Paramètres de base
1. Aller à **Extensions > PDF Builder Pro > Paramètres**
2. Remplir les informations entreprise :
   - Nom de l'entreprise
   - Logo (upload image)
   - Coordonnées (adresse, email, téléphone)
3. Sauvegarder

#### B. Configuration WooCommerce (si applicable)
1. Aller à **PDF Builder > WooCommerce**
2. Activer les statuts de génération automatique :
   - ✅ Commande payée → générer facture
   - ✅ Expédiée → générer bon de livraison
3. Configurer l'email automatique :
   - ✅ Envoyer facture au client
4. Sauvegarder

#### C. Sécurité & RGPD
1. Aller à **PDF Builder > Sécurité**
2. Configurer les consentements RGPD :
   - ✅ Analytics cookies
   - ✅ Marketing cookies
3. Configurer l'audit log (conserve 90 jours par défaut)
4. Sauvegarder

#### D. Performance (optionnel)
1. Aller à **PDF Builder > Système**
2. Activer le cache : ✅
3. Définir TTL : 3600 secondes (1h) recommandé
4. Sauvegarder

---

### Étape 3 : Créer votre premier template

1. Aller à **PDF Builder > Templates**
2. Cliquer **Créer un nouveau template**
3. Nommer le template : "Ma première facture"
4. Cliquer **Ouvrir l'éditeur**
5. Dans l'éditeur visuel :
   - **Glisser-déposer** "Logo" en haut
   - **Ajouter** "Adresse entreprise" en haut droit
   - **Insérer** tableau pour les lignes de produits
   - **Ajouter** champs dynamiques : numéro commande, date, total
6. Cliquer **Aperçu** pour voir le résultat
7. Cliquer **Sauvegarder**

---

### Étape 4 : Tester la génération

#### Test manuel
1. Créer une commande de test dans WooCommerce
2. Aller à **Commandes > Modifier**
3. Cliquer **Générer PDF** (bouton dans métaboîte PDF Builder)
4. Le PDF télécharge immédiatement

#### Auto-génération
1. Paramétrer l'état de commande (Voir Étape 2B)
2. Changer l'état à "Payée"
3. Vérifier que le PDF s'est généré automatiquement :
   - Métaboîte PDF Builder affiche le PDF
   - Client a reçu email avec facture

---

## 🔑 Activation & Licences

### Version Gratuite
- Aucune activation requise
- Utilisation illimitée sur 1 site
- 3 templates fournis

### Version Premium
1. Recevoir la clé de licence
2. Aller à **PDF Builder > Licences**
3. Entrer la clé et le code activation
4. Cliquer **Vérifier la licence**
5. ✅ Licence activée — accès à 25+ templates

### Multi-site
- Une clé = 1 licence
- Pour 2 sites = 2 clés
- Gestion depuis **Extensions > Licences**

---

## 🔧 Configuration avancée

### Chemins et dossiers
```
wp-content/plugins/pdf-builder-pro/
├── assets/          # CSS, JS, images
├── src/             # Code source PHP
├── templates/       # Templates WordPress
├── config/          # Configuration
└── languages/       # Traductions
```

### Fichier de configuration `wp-config.php`
Optionnel : ajouter des constantes customs :
```php
// Répertoire de stockage PDF
define('PDF_BUILDER_STORAGE_DIR', '/var/www/pdfs/');

// Chemis vers Chromium (si généré depuis Puppeteer)
define('PDF_BUILDER_CHROMIUM_PATH', '/usr/bin/chromium');

// Désactiver cache (debug)
define('PDF_BUILDER_CACHE_DISABLED', false);
```

### Cache & Performance
Activer avec **Paramètres > Système > Cache** :
- TTL par défaut : 3600 secondes (1h)
- Format : compressi WP transients
- Vider cache : **Système > Bouton "Vider le cache"**

---

## 📋 Checklist post-installation

- [ ] Settings basiques remplis (nom empresa, logo)
- [ ] WooCommerce configuré (si applicable)
- [ ] 1er template créé et testé
- [ ] Email automatique testé (envoi facture client)
- [ ] Backups configurées
- [ ] SSL/HTTPS activé sur site
- [ ] Licence activée (Premium)
- [ ] Cache activé
- [ ] Audit log révélé (RGPD compliant)

---

## 🚨 Troubleshooting

### Problème : "Extension inactive ou erreur"

**Cause** : Conflit plugin ou version PHP incompatible

**Solution** :
1. Vérifier PHP ≥ 7.4 : `php -v`
2. Désactiver plugins récemment ajoutés
3. Vérifier espace disque : `df -h`
4. Réactiver PDF Builder Pro

---

### Problème : "PDF ne génère pas"

**Cause** : Mémoire épuisée ou Chromium absent

**Solution** :
1. Augmenter mémoire PHP dans `wp-config.php` :
   ```php
   define('WP_MEMORY_LIMIT', '512M');
   ```
2. Vérifier Chromium installé : `which chromium` ou `which google-chrome`
3. Vérifier les droits d'accès dossier `/tmp`

---

### Problème : "Email ne s'envoie pas"

**Cause** : Serveur SMTP mal configuré

**Solution** :
1. Tester email WordPress : **Outils > Envoyer test email**
2. Si échec, installer plugin SMTP : WP Mail SMTP
3. Configurer credentials email dans plugin SMTP
4. Réessayer

---

### Problème : "Cache pas efficace"

**Cause** : Plugin de cache WordPress inactive

**Solution** :
1. Installer un cache plugin : WP Super Cache
2. Activer cache de page entière
3. Exclure `/pdf-builder/` de la suppression du cache
4. Testé avec DevTools (vérifier headers Cache-Control)

---

### Problème : "Licence invalide"

**Cause** : Clé saisie incorrectement ou site non autorisé

**Solution** :
1. Vérifier la clé exacte reçue (copier sans espaces)
2. Vérifier le domaine est autorisé dans compte
3. Réinitialiser licence : contacter support@pdfbuilder.pro

---

## 📞 Support & aide

### Documentation
- 📖 **Docs officielles** : https://docs.pdfbuilder.pro
- 🎥 **Video tutorials** : https://youtube.com/@pdfbuilderofficial
- 💬 **Community forum** : https://community.pdfbuilder.pro

### Support direct
- 📧 **Email** : support@pdfbuilder.pro (réponse <4h)
- 💬 **Live chat** : sur site (lun-ven 9h-17h CET)
- 🐛 **Bug reporting** : github.com/pdfbuilder/issues

### Devis & setup custom
- Pour entreprises ayant des besoin spécifiques
- Contact : sales@pdfbuilder.pro
- Devis gratuit en <24h

---

## ✅ Vérification finale

Une fois installation complète, vérifier :

1. **Dashboard** : "PDF Builder Pro activé ✓"
2. **Tests PDF** : généré avec succès
3. **Emails** : reçu facture test avec PDF
4. **Cache** : statistiques affichent données cachées
5. **RGPD** : toggle visible dans Sécurité

**Bravo ! Vous êtes prêt à utiliser PDF Builder Pro V2 !** 🎉

---

## 🆘 Besoin d'aide ?

Vous avez une question ? Consultez la FAQ complète ou contactez support@pdfbuilder.pro

Nous sommes là pour vous aider ! 💪
