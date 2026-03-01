# ArtiCMS

**ArtiCMS** est un CMS PHP léger et modulaire, conçu pour les sites vitrine d'artisans et de TPE. Il embarque un back-office complet, un mini-CRM, un générateur de formulaires et un gestionnaire de médias, le tout installable en quelques minutes via un wizard web.

> Version actuelle : **1.0.0** — 26/02/2026

---

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Structure du projet](#structure-du-projet)
- [Thèmes](#thèmes)
- [Configuration](#configuration)
- [Sécurité](#sécurité)
- [Technologies](#technologies)
- [Licence](#licence)

---

## Fonctionnalités

### Front-office
- Pages dynamiques avec éditeur riche (TinyMCE)
- Menu de navigation configurable
- Galeries photos
- Section réalisations (portfolio)
- Formulaire de contact avec protection reCAPTCHA v3
- SEO intégré (balises meta, sitemap XML, robots.txt)
- Thèmes interchangeables

### Back-office (Admin)
- **Dashboard** avec KPIs en temps réel (contacts, réalisations, médias, soumissions…)
- **Gestion des pages CMS** : création, édition, publication
- **Éditeur de menus** (navigation principale)
- **Gestionnaire de médias** : upload, recadrage, métadonnées, suppression
- **Galeries** : création de galeries multi-images
- **Réalisations** : portfolio avec images, catégories, statut de publication
- **Formulaires** : constructeur de formulaires personnalisés, export CSV des soumissions
- **Contacts** : suivi des demandes (statut, notes, tags, archivage, relance)
- **Thèmes** : sélection et édition CSS en ligne
- **Paramètres** : identité, SMTP, reCAPTCHA, URLs
- **Sauvegarde / Restauration** : export complet en `.tar.gz` (BDD + médias + thèmes + config) et import pour migration entre serveurs

### CRM (module optionnel)
- Fiche clients avec historique
- Création de **devis** et **factures** au format PDF (via DomPDF)
- Impression / envoi par email
- Conversion contact → client
- Suivi des relances

### Authentification & Sécurité
- Authentification admin avec hachage bcrypt
- **Double authentification (2FA)** via TOTP (compatible Google Authenticator)
- Gestion des rôles et permissions par utilisateur
- Limitation de débit (rate-limiting) et liste noire d'IPs
- Réinitialisation de mot de passe par email
- Wizard d'installation avec verrouillage (`installed.lock`)

---

## Prérequis

| Dépendance | Version minimale |
|---|---|
| PHP | 8.1 |
| MySQL / MariaDB | 5.7 / 10.3 |
| Extension PDO + PDO_MySQL | — |
| Extension mbstring | — |
| Extension openssl | — |
| Extension json | — |
| Composer | 2.x |
| Serveur web | Apache / Nginx |

> **Apache** : le fichier `.htaccess` est pris en charge nativement.  
> **Nginx** : adapter la configuration pour rediriger vers `index.php`.

---

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/<votre-pseudo>/articms.git
cd articms
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Permissions fichiers

```bash
chmod +x fix-perms.sh && ./fix-perms.sh
```

Ou manuellement :

```bash
chmod 664 includes/config.php
chmod 775 uploads/ install/ storage/
```

### 4. Wizard d'installation web

Ouvrez votre navigateur sur :

```
http://votre-domaine.tld/install/
```

Le wizard vérifie les prérequis système, crée les tables en base de données (CMS + CRM), génère `includes/config.php` et crée `install/installed.lock` pour verrouiller l'accès.

> ⚠️ Une fois l'installation terminée, le répertoire `install/` est automatiquement verrouillé. Ne le supprimez pas, mais ne le rendez jamais accessible publiquement si le lock est absent.

### 5. Connexion

Rendez-vous sur `/admin/` et connectez-vous avec les identifiants définis lors de l'installation.

---

## Structure du projet

```
articms/
├── admin/              # Back-office (pages + actions + partials)
├── app/
│   ├── Controllers/    # Contrôleurs Admin & Front (architecture MVC)
│   ├── Models/         # Modèles de données
│   ├── Views/          # Vues partielles
│   └── routes.php      # Routeur front-end
├── assets/             # CSS, JS, images globales
├── bdd/                # Scripts SQL de migration
├── classes/            # PHPMailer, TOTP, MailSender
├── forms/              # Traitement des formulaires front
├── includes/           # config.php, db.php, header/footer, SEO
├── install/            # Wizard d'installation
├── storage/            # Rate-limit & IP blocklist (JSON)
├── themes/             # Thèmes (clair, default, haussmann, marine)
├── uploads/            # Médias uploadés
├── vendor/             # Dépendances Composer
├── versioning/         # CHANGELOG.md, VERSION.txt
├── bootstrap.php       # Point d'entrée commun
└── index.php           # Front-end principal
```

---

## Thèmes

Quatre thèmes sont inclus :

| Thème | Description |
|---|---|
| `default` | Thème générique de base |
| `clair` | Design épuré, tons clairs |
| `haussmann` | Style élégant, typographie soignée |
| `marine` | Palette bleue marine |

L'activation et l'édition CSS se font depuis **Admin > Thèmes**.

---

## Configuration

Le fichier `includes/config.php` est généré automatiquement par le wizard. Les constantes principales sont :

```php
define('BASE_URL',          'https://votre-domaine.tld');
define('CMS_VERSION',       '1.0.0');
define('COMPANY_NAME',      'Mon Entreprise');
define('DB_HOST',           '127.0.0.1');
define('DB_NAME',           'articms');
define('SMTP_HOST',         'smtp.example.com');
define('CAPTCHA_SITE_KEY',  '...');
define('CAPTCHA_SECRET_KEY','...');
```

Toutes les valeurs sont également modifiables depuis **Admin > Paramètres**.

---

## Sécurité

- Mots de passe stockés en `bcrypt`
- 2FA TOTP activable par utilisateur
- Rate-limiting par IP sur les endpoints sensibles
- Liste noire d'IPs (`storage/ip-blocklist.json`)
- Tokens CSRF sur tous les formulaires d'administration
- Wizard d'installation verrouillé après premier usage

---

## Technologies

- **PHP 8.1+** — sans framework, architecture MVC maison
- **MySQL / MariaDB** — PDO
- **DomPDF 3.x** — génération de PDF (devis/factures)
- **PHPMailer** — envoi d'emails SMTP
- **TinyMCE** — éditeur WYSIWYG (CDN)
- **Google reCAPTCHA v3** — protection anti-spam

---

## Licence

Ce projet est distribué sous licence **MIT**. Voir le fichier [LICENSE](LICENSE) pour plus de détails.
