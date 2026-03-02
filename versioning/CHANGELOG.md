# Changelog ArtiCMS

Toutes les modifications notables de ce projet sont documentées dans ce fichier.  
Format basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/), versionnement [Semantic Versioning](https://semver.org/lang/fr/).

---

## [1.0.0] — 2026-02-26

### 🎉 Version initiale — Publication officielle

---

### Front-office

#### Pages & Navigation
- Pages dynamiques avec contenu riche via **TinyMCE** (éditeur WYSIWYG CDN)
- **Routeur front-end** (`app/routes.php`) avec résolution automatique des slugs
- Menu de navigation principal configurable depuis le back-office
- Rendu des pages via système de **vues partielles** (MVC maison)
- Header et footer dynamiques chargés depuis `includes/`

#### Réalisations & Galeries
- Section **portfolio / réalisations** avec images, catégories et statut de publication
- **Galeries photos** multi-images intégrables dans les pages
- Affichage conditionnel selon statut (publié / brouillon)

#### Formulaire de contact
- Formulaire de contact front-office avec traitement (`forms/contact-process.php`)
- Protection anti-spam **Google reCAPTCHA v3**
- Traitement générique des **formulaires personnalisés** (`forms/form-process.php`)
- Stockage des soumissions en base de données

#### SEO & Accessibilité
- Balises `<meta>` dynamiques (title, description, Open Graph) via `includes/seo.php`
- Génération **sitemap.xml** configurable depuis le back-office
- Gestion **robots.txt** depuis le back-office
- Permaliens propres (slugs)

#### Thèmes
- Système de **thèmes interchangeables** : `default`, `clair`, `haussmann`, `marine`
- Chaque thème possède ses propres CSS et templates HTML
- Activation et édition CSS en ligne depuis le back-office

---

### Back-office

#### Dashboard
- **KPIs en temps réel** : contacts, réalisations, médias, soumissions, clients CRM, devis
- Aperçu des dernières activités
- Accès rapide aux modules principaux

#### Gestion des pages CMS
- Liste des pages avec statut (publié / brouillon), slug, date de modification
- **Création et édition** de pages avec éditeur TinyMCE intégré
- Gestion SEO par page : titre, description, balise canonique
- Publication / dépublication instantanée
- Gestion des slugs (URL uniques)

#### Éditeur de menu
- Construction du menu de navigation principal
- Ajout de liens internes (pages CMS) et externes
- Gestion de l'ordre des éléments

#### Éditeur de homepage
- Section dédiée à la personnalisation de la page d'accueil
- Champs indépendants : titre hero, sous-titre, CTA, texte de présentation, sections activables

#### Gestionnaire de médias
- **Upload** de fichiers images (JPEG, PNG, WebP, GIF, SVG)
- **Recadrage interactif** via Cropper.js
- Édition des **métadonnées** : titre, texte alternatif, légende
- Suppression avec confirmation
- **Media picker** réutilisable dans tous les modules (pages, galeries, réalisations)
- Intégration dans TinyMCE via `admin/media-picker.php`

#### Galeries
- Création de galeries multi-images avec titre et description
- Sélection multiple via le **gallery picker**
- Association aux pages CMS

#### Réalisations (Portfolio)
- Création / édition avec image principale, catégorie, description, statut
- Gestion des catégories et de l'ordre d'affichage
- Paramètres globaux de la section (`admin/realisations-settings.php`)

#### Constructeur de formulaires
- Création de **formulaires personnalisés** : texte, email, téléphone, textarea, checkbox, select, fichier
- Gestion des labels, placeholders et champs obligatoires
- Duplication de formulaire
- **Soumissions** : liste, suppression individuelle, purge globale
- **Export CSV** des soumissions
- Intégration via **form picker** dans les pages CMS

#### Gestion des contacts
- Liste des contacts entrants avec statut (nouveau, en cours, traité, archivé)
- Ajout de **notes** internes horodatées
- Système de **tags** personnalisables
- **Archivage** des contacts traités
- Planification de **relances** (follow-up) avec marquage "fait"
- Conversion contact → client CRM (`actions/crm-convert-contact.php`)
- Export CSV des contacts

#### Thèmes
- Liste des thèmes disponibles avec prévisualisation
- **Activation** d'un thème en un clic
- **Éditeur CSS en ligne** par thème (sauvegarde en direct)

#### Paramètres système
- **Identité entreprise** : nom, adresse, téléphone, email, logo
- **SMTP** : hôte, port, utilisateur, mot de passe, expéditeur, test d'envoi intégré
- **reCAPTCHA v3** : clé site et clé secrète
- **URLs** : URL de base, URL de production
- **Robots.txt** : édition directe depuis l'interface
- **Sitemap XML** : génération et configuration des URLs incluses

#### Sauvegarde & Restauration
- **Export complet** en `.tar.gz` : base de données + médias + thèmes + configuration
- **Import / Restauration** depuis une archive `.tar.gz`
- Confirmation avant écrasement des données existantes
- Outil de migration entre serveurs

---

### CRM (module intégré)

#### Clients
- Fiche client complète : raison sociale, contact, adresse, notes
- **Historique** des interactions
- Conversion depuis un contact front-office existant
- Suppression avec confirmation

#### Devis & Factures
- Création de **devis et factures** avec lignes d'articles (description, quantité, prix unitaire, TVA)
- Calcul automatique des totaux HT / TVA / TTC
- Statuts : brouillon, envoyé, accepté, refusé, payé
- **Génération PDF** via DomPDF (mise en page professionnelle)
- **Impression** directe depuis le navigateur
- **Envoi par email** via PHPMailer
- Suppression avec confirmation

---

### Authentification & Sécurité

#### Authentification
- Connexion admin avec **hachage bcrypt** des mots de passe
- **Double authentification (2FA) TOTP** compatible Google Authenticator, Authy, etc.
  - Activation / désactivation par utilisateur depuis le profil
  - QR code d'enrôlement
  - Vérification code 6 chiffres à chaque connexion si activé
- **Réinitialisation de mot de passe** par email avec token à durée limitée
- Déconnexion sécurisée avec invalidation de session

#### Gestion des utilisateurs
- Création et édition des comptes utilisateurs admin
- Gestion des **rôles** (administrateur, éditeur…)
- Gestion des **permissions** granulaires par utilisateur
- Édition du profil personnel (avatar, informations, mot de passe)

#### Protection
- **Rate-limiting par IP** sur les endpoints sensibles (login, formulaires, reset)
- **Liste noire d'IPs** persistante (`storage/ip-blocklist.json`)
- Comptage et expiration des tentatives (`storage/rate-limit.json`)
- **Tokens CSRF** sur tous les formulaires d'administration

#### Wizard d'installation
- Vérification des prérequis système (PHP, extensions, permissions)
- Création automatique des tables via migrations SQL (CMS + CRM)
- Génération de `includes/config.php`
- Création de `install/installed.lock` pour verrouiller l'accès post-installation

---

### Architecture & Infrastructure

- **Architecture MVC maison** sans framework externe
- **Bootstrap centralisé** (`bootstrap.php`) : autoload, config, session, helpers
- **Routeur front-end** avec support des slugs dynamiques
- **Contrôleurs** séparés Admin et Front (`app/Controllers/`)
- **Modèles PDO** (`app/Models/`) avec requêtes préparées systématiques
- **Vues partielles** (`app/Views/`, `admin/partials/`)
- Migrations SQL séparées CMS (`bdd/cms_migration.sql`) et CRM (`bdd/crm_migration.sql`)
- Gestion des dépendances via **Composer** (`vendor/`)
- Intégration **DomPDF 3.x**, **PHPMailer**, **TOTP**, **Google reCAPTCHA v3**
- Script `fix-perms.sh` pour la configuration des permissions Unix
- Stockage JSON persistant dans `storage/` (rate-limit, blocklist IP)

---

*Licence MIT — ArtiCMS*
- Arborescence optimisée référencement local