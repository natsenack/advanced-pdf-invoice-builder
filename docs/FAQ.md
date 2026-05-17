# FAQ — PDF Builder Pro V2

## ❓ Questions générales

### Q : Dois-je avoir des connaissances en code ?

**A :** Non ! PDF Builder Pro est conçu pour les non-développeurs. Utilisez l'éditeur drag & drop pour créer des templates sans une seule ligne de code.

Cependant, si vous êtes développeur, vous pouvez accéder à l'éditeur HTML avancé et l'API REST pour les automatisations complexes.

---

### Q : Fonctionne-t-il sur tous les sites WordPress ?

**A :** Oui, PDF Builder Pro fonctionne sur tous les sites WordPress (5.0+), indépendamment du thème ou des autres plugins.

Exceptions :

- **WooCommerce** : seulement avec WooCommerce 5.0+
- **Performance** : sur très petits serveurs (<256MB RAM), vous risquez des timeouts

---

### Q : Est-ce compatible avec les éditeurs visuels (Elementor, Divi) ?

**A :** Oui ! Vous pouvez intégrer PDF Builder Pro dans vos pages construites avec Elementor ou Divi via shortcode ou bloc Gutenberg.

```
[pdf-builder template-id="123"]
```

---

### Q : Puis-je utiliser PDF Builder Pro sur plusieurs sites ?

**A :** Oui, mais chaque site nécessite sa propre licence.

- Version Gratuite : 1 site illimité
- Version Premium : 1 licence = 1 site (multi-licences disponibles)
- Multisite WordPress : chaque installation WordPress = 1 licence

---

### Q : Quels types de documents puis-je générer ?

**A :** Tous types : factures, devis, bons de commande, certificats, contrats, rapports, étiquettes, tickets... L'éditeur est **100% flexible**.

---

## ⚙️ Installation & configuration

### Q : Combien de temps prend l'installation ?

**A :** 5 minutes pour la base. Installation complète (configuration WooCommerce, premiers templates) : 30 minutes.

---

### Q : Ai-je besoin d'un serveur spécial pour générer les PDF ?

**A :** PDF Builder Pro utilise Puppeteer (service distant threeaxe.fr) pour générer les PDF. Une clé de licence valide est requise pour accéder au service de rendu.

---

### Q : Comment ajouter mon logo ?

**A :**

1. **Paramètres > Général** : upload logo entreprise (affect tous templates)
2. **Éditeur template** : insérer un logo spécifique en drag & drop

---

### Q : Puis-je personnaliser les templates existants ?

**A :** Oui ! Dupliquer un template fourni et modifier selon vos besoins (couleurs, polices, layout).

---

### Q : Comment formatter les prix en devise différente ?

**A :** La détection de devise est automatique **uniquement lors de la génération réelle de la facture** (depuis une commande WooCommerce). Dans l'éditeur de template, les prix affichés sont des données d'exemple statiques — la devise réelle n'est pas encore connue à ce stade.

Lors de la génération réelle, vous pouvez :

1. Forcer une devise manuelle dans le template
2. Convertir en temps réel (taux live) depuis la commande
3. Afficher le symbole ou le code ($ / EUR)

---

## 🎨 Éditor & templates

### Q : Puis-je créer des templates depuis zéro ?

**A :** Oui ! Créer → design entièrement libre (drag & drop) → ajouter champs dynamiques → sauvegarder.

**Ou** : partir d'un template existant, dupliquer, modifier.

---

### Q : Comment ajouter des variables dynamiques (numéro commande, client, etc.) ?

**A :** Dans l'éditeur, panneau droit "Variables" affiche tous les champs disponibles. Ces variables proviennent des **paramètres du plugin** :

- **Paramètres > Général** : données client (nom, email, adresse...)
- **Paramètres > WooCommerce** : données commande (numéro, date, total, devise)
- **Produits** : titre, SKU, quantité, prix
- **Custom** : champs ACF, post meta

Une fois configurés dans les paramètres, ces champs s'affichent dans le panneau "Variables" de l'éditeur. Glissez-déposez le champ désiré dans le template.

---

### Q : Puis-je utiliser des formules (sommes, pourcentages) ?

**A :** Oui ! Champs spéciaux "Calcul" :

- `[SUBTOTAL] + [TAX]` → total TTC
- `[TOTAL] * 0.9` → avec 10% remise
- `[PRICE] * [QTY]` → ligne total

---

### Q : Puis-je ajouter du HTML custom ?

**A :** Non, pas pour le moment. PDF Builder Pro utilise un système d'éléments pré-construits (texte, images, formes, tableaux) pour garantir la compatibilité PDF et éviter les problèmes de rendu.

Pour les besoins avancés, vous pouvez contacter le support pour discuter de cas spécifiques.

---

### Q : Comment créer des tableaux dynamiques (listes produits) ?

**A :** Les tableaux dans PDF Builder Pro utilisent des valeurs fixes. Vous pouvez créer des tableaux statiques avec des données que vous définissez manuellement dans le template.

Pour les listes de produits dynamiques depuis WooCommerce, cette fonctionnalité n'est pas encore disponible. Contactez le support pour discuter de solutions alternatives.

---

### Q : Puis-je importer des logos/images de ma médiathèque ?

**A :** Oui ! Éditeur → insérer image → choisir depuis médiathèque WordPress.

---

## 🔗 WooCommerce & e-commerce

### Q : Puis-je générer automatiquement les factures depuis WooCommerce ?

**A :** Oui ! Paramètres WooCommerce → statuts de génération automatique (paiement reçu, expédié, etc.).

Chaque changement de statut génère le PDF automatiquement.

---

### Q : Puis-je envoyer la facture au client automatiquement ?

**A :** Oui ! Paramètres WooCommerce → ✅ "Envoyer email au client" → configure l'email qui sera inclus.

---

### Q : Puis-je générer plusieurs documents depuis une commande (facture + bon livraison) ?

**A :** Oui ! Configurer différents templates pour différents statuts.

Ex :

- Statut "Payé" → template Facture
- Statut "Préparation" → template Bon de commande
- Statut "Expédié" → template Bon de livraison

---

### Q : Puis-je générer des factures pour plusieurs commandes en masse ?

**A :** Oui ! **WooCommerce > Commandes > Action en masse > "Générer PDF en masse"** (Premium).

---

### Q : Mon stock WooCommerce change-t-il après génération PDF ?

**A :** Non. PDF Builder Pro ne modifie pas le stock. C'est à vous de gérer le stock manuellement ou via plugin de sync.

---

### Q : Puis-je créer des factures proforma (prévisionnels) ?

**A :** Non, pas pour le moment. PDF Builder Pro génère les PDFs basé sur l'état réel de la commande. Pour les devis/prévisionnels, utilisez les fonctionnalités natives de WooCommerce ou un plugin dédié.

---

## 📊 Rapports & analytics

### Q : Puis-je voir combien de PDF j'ai généré ?

**A :** Non, pas pour le moment. La section statistiques/rapports est prévue dans une future version.

---

### Q : Puis-je exporter les rapports ?

**A :** Non, pas pour le moment. Cette fonctionnalité sera disponible lors de l'ajout du module statistiques.

---

### Q : Puis-je voir qui a créé/modifié les templates ?

**A :** Non, pas pour le moment. L'audit log (historique des modifications) n'est pas encore implémenté.

---

## 🔒 Sécurité & RGPD

### Q : PDF Builder Pro est-il conforme RGPD ?

**A :** Oui ! Nous proposons :

- **Audit log complet** : traçabilité 100%
- **Droit d'accès** : export données en JSON/CSV
- **Droit à l'oubli** : anonymisez données avec 1 clic
- **Consentements** : opt-in/out pour cookies/traçabilité
- **Chiffrement** : AES-256 données sensibles

---

### Q : Mes données client sont-elles sécurisées ?

**A :** Oui !

- **Chiffrement** : AES-256 au repos
- **TLS/SSL** : en transit
- **Pas d'envoi serveurs externes** : tout reste sur votre serveur
- **Backups** : automatiques et chiffrées

---

### Q : Comment puis-je me conformer à RGPD pour la facturation ?

**A :** Paramètres RGPD :

1. ✅ Activer audit log
2. ✅ Configurer consentements
3. ✅ Définir durée conservation (ex : 10 ans pour factures légales)
4. ✅ Mettre à jour CGV/politique privacy

---

### Q : Puis-je anonymiser les données client ?

**A :** Oui ! **Sécurité > Droit à l'oubli** :

- Sélectionner client
- Cliquer "Anonymiser"
- Toutes les données confidentielles sont supprimées

---

## ⚡ Performance & cache

### Q : How fast are PDFs generated?

**A :** Dépend du complexité :

- **Simple** (texte + chiffres) : 0,5–1s
- **Moyen** (images, tableaux) : 1–2s
- **Complexe** (beaucoup images, styles) : 2–5s

**Avec cache activé** : instant (après 1ère génération, 1h retention)

---

### Q : Comment activer le cache ?

**A :** **Paramètres > Système > Cache** → ✅ Activé

- TTL par défaut : 3600 secondes (1h)
- Économie : 40–60% temps génération
- Auto-invalidation : quand template/données changent

---

### Q : Le cache ralentira-t-il mon site ?

**A :** Non ! Le cache l'accélère en réduisant calculs PDF. Impact mémoire : ~5 MB par 100 templates.

---

### Q : Puis-je vider le cache manuellement ?

**A :** Oui ! **Paramètres > Système > Bouton "Vider cache"** → 1 clic.

---

## 🚀 API & intégrations

### Q : Puis-je générer des PDF via API ?

**A :** Oui ! Endpoint :

```
POST /wp-json/api/v1/generate
{
  "template_id": 123,
  "customer_id": 456,
  "order_id": 789
}
```

Retourne l'URL du PDF généré.

---

### Q : Puis-je intégrer PDF Builder avec mon CRM externe ?

**A :** Non, pas pour le moment et ce n'est pas prévu. PDF Builder Pro est conçu pour fonctionner uniquement avec WordPress/WooCommerce. Les intégrations externes (CRM, ERP) ne sont pas supportées.

---

## 💰 Tarification & licences

### Q : Qu'est-ce que je gagne en version Premium vs Gratuite ?

**A :** Voir [PRICING.md](./PRICING.md) pour tableau complet.

**Résumé** :

- (+) templates illimité
- (+) WooCommerce intégration complète
- (+) API REST avancée (1000 appels/jour)
- (+) Webhooks & automation
- (+) Support email prioritaire

---

### Q : Comment renouveler une licence Pro ?

**A :** Renouvelle automatiquement chaque année (sauf désabonnement). Facturation annuelle ou mensuelle.

Gérez subscription sur votre compte client.

---

### Q : Puis-je annuler la licence ?

**A :** Oui ! Accès à la page de gestion compte → "Annuler l'abonnement". Arrête au prochain cycle de facturation.

---

## 🆘 Problèmes & troubleshooting

### Q : "PDF ne génère pas" — quoi faire ?

**A :** Voir [INSTALLATION.md — Troubleshooting](./INSTALLATION.md#-troubleshooting).

Checklist : PHP ≥7.4, mémoire >256MB, Chromium installé.

---

### Q : "Licence invalide" — solution ?

**A :** Vérifier clé exacte (pas d'espaces), domaine autorisé. Contacter support : threeaxe.france@gmail.com

---

### Q : Où trouver les logs d'erreur ?

**A :** **debug.log** dans `/wp-content/` (si WP_DEBUG = true)

Ou **Paramètres > Système > Logs** affiche les erreurs PDF Builder.

---

### Q : Qui contacter pour support ?

**A :**

- 📧 **Email** : threeaxe.france@gmail.com
- 📖 **Docs** : https://github.com/natsenack/wp-pdf-builder-pro

---

## 📞 Encore des questions ?

Consultez la **[documentation complète](https://github.com/natsenack/wp-pdf-builder-pro)** ou **[contactez support](mailto:threeaxe.france@gmail.com)**.

Nous sommes disponibles lun-ven 9h-18h CET 💪
