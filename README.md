# ArtiCMS

**ArtiCMS** est un CMS PHP léger et modulaire, conçu pour les sites vitrine d'artisans et de TPE. Il embarque un back-office complet, un mini-CRM, un générateur de formulaires et un gestionnaire de médias, le tout installable en quelques minutes via un wizard web.

> Version actuelle : **1.0.0** — 26/02/2026

---

## Sommaire

- [Captures d'écran](#captures-décran)
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

## Captures d'écran

### Dashboard

<table>
  <tr>
    <td align="center" width="100%">
      <img src="screen/01%20-%20dashboard.png" alt="Dashboard" width="900"/><br/>
      <sub><b>Dashboard</b> — KPIs en temps réel (contacts, réalisations, médias, soumissions)</sub>
    </td>
  </tr>
</table>

---

### CRM — Contacts & Clients

<table>
  <tr>
    <td align="center" width="50%">
      <img src="screen/02%20-%20contact.png" alt="Contacts" width="440"/><br/>
      <sub><b>Liste des contacts</b> — statut, tags, archivage, relances</sub>
    </td>
    <td align="center" width="50%">
      <img src="screen/03%20-%20contact-edit.png" alt="Contact – fiche" width="440"/><br/>
      <sub><b>Fiche contact</b> — notes, historique, conversion en client</sub>
    </td>
  </tr>
  <tr>
    <td align="center" width="50%">
      <img src="screen/04%20-%20client.png" alt="Clients" width="440"/><br/>
      <sub><b>Liste des clients</b></sub>
    </td>
    <td align="center" width="50%">
      <img src="screen/04%20-%20client-edit.png" alt="Client – fiche" width="440"/><br/>
      <sub><b>Fiche client</b> — historique complet</sub>
    </td>
  </tr>
</table>

---

### CRM — Devis & Factures

<table>
  <tr>
    <td align="center" width="33%">
      <img src="screen/05-devis%26invoice.png" alt="Devis – liste" width="280"/><br/>
      <sub><b>Liste devis / factures</b></sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/06-devis%26invoice-edit.png" alt="Devis – édition" width="280"/><br/>
      <sub><b>Édition de devis</b></sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/07-devis%26invoice-pdf.png" alt="Devis – PDF" width="280"/><br/>
      <sub><b>Aperçu PDF</b> — export et impression</sub>
    </td>
  </tr>
</table>

---

### CMS — Pages & Menus

<table>
  <tr>
    <td align="center" width="50%">
      <img src="screen/08-pages.png" alt="Pages CMS" width="440"/><br/>
      <sub><b>Gestion des pages</b> — création, publication, ordre</sub>
    </td>
    <td align="center" width="50%">
      <img src="screen/09-pages-edit.png" alt="Éditeur de page" width="440"/><br/>
      <sub><b>Éditeur de page</b> — TinyMCE, SEO, slug</sub>
    </td>
  </tr>
  <tr>
    <td align="center" width="50%">
      <img src="screen/18-menu.png" alt="Menu" width="440"/><br/>
      <sub><b>Éditeur de menu</b> — navigation configurable</sub>
    </td>
    <td align="center" width="50%">
      <img src="screen/21-theme-homepage.png" alt="Homepage" width="440"/><br/>
      <sub><b>Éditeur de homepage</b></sub>
    </td>
  </tr>
</table>

---

### Médias & Galeries

<table>
  <tr>
    <td align="center" width="50%">
      <img src="screen/10-medias.png" alt="Médias" width="440"/><br/>
      <sub><b>Gestionnaire de médias</b> — upload, filtres, suppression</sub>
    </td>
    <td align="center" width="50%">
      <img src="screen/11-medias-edit.png" alt="Média – édition" width="440"/><br/>
      <sub><b>Édition de média</b> — recadrage, métadonnées</sub>
    </td>
  </tr>
  <tr>
    <td align="center" width="50%">
      <img src="screen/14-gallery.png" alt="Galeries" width="440"/><br/>
      <sub><b>Liste des galeries</b></sub>
    </td>
    <td align="center" width="50%">
      <img src="screen/15-gallery-edit.png" alt="Galerie – édition" width="440"/><br/>
      <sub><b>Édition de galerie</b> — sélection multi-images</sub>
    </td>
  </tr>
</table>

---

### Réalisations

<table>
  <tr>
    <td align="center" width="50%">
      <img src="screen/16-realisations.png" alt="Réalisations" width="440"/><br/>
      <sub><b>Liste des réalisations</b> — statut, catégories</sub>
    </td>
    <td align="center" width="50%">
      <img src="screen/17-realisations-edit.png" alt="Réalisation – édition" width="440"/><br/>
      <sub><b>Édition d'une réalisation</b></sub>
    </td>
  </tr>
</table>

---

### Formulaires

<table>
  <tr>
    <td align="center" width="50%">
      <img src="screen/12-forms.png" alt="Formulaires" width="440"/><br/>
      <sub><b>Liste des formulaires</b> — soumissions, export CSV</sub>
    </td>
    <td align="center" width="50%">
      <img src="screen/13-fomrs-edit.png" alt="Formulaire – constructeur" width="440"/><br/>
      <sub><b>Constructeur de formulaire</b> — champs personnalisés</sub>
    </td>
  </tr>
</table>

---

### Thèmes

<table>
  <tr>
    <td align="center" width="33%">
      <img src="screen/19-themes.png" alt="Thèmes" width="280"/><br/>
      <sub><b>Sélection de thème</b></sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/20-theme-edit.png" alt="Thème – éditeur CSS" width="280"/><br/>
      <sub><b>Éditeur CSS en ligne</b></sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/21-theme-homepage.png" alt="Homepage" width="280"/><br/>
      <sub><b>Éditeur de homepage</b></sub>
    </td>
  </tr>
</table>

---

### Paramètres

<table>
  <tr>
    <td align="center" width="33%">
      <img src="screen/22-setting-compagny.png" alt="Paramètres – Entreprise" width="280"/><br/>
      <sub><b>Identité entreprise</b></sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/23-setting-smtp.png" alt="Paramètres – SMTP" width="280"/><br/>
      <sub><b>Configuration SMTP</b></sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/24-setting-captcha.png" alt="Paramètres – Captcha" width="280"/><br/>
      <sub><b>reCAPTCHA v3</b></sub>
    </td>
  </tr>
  <tr>
    <td align="center" width="33%">
      <img src="screen/25-settting-robots.png" alt="Paramètres – Robots" width="280"/><br/>
      <sub><b>Robots.txt</b></sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/26-setting-sitemap.png" alt="Paramètres – Sitemap" width="280"/><br/>
      <sub><b>Sitemap XML</b></sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/29-backup-save-restore.png" alt="Sauvegarde" width="280"/><br/>
      <sub><b>Sauvegarde & Restauration</b></sub>
    </td>
  </tr>
</table>

---

### Utilisateurs & Profil

<table>
  <tr>
    <td align="center" width="33%">
      <img src="screen/26-users.png" alt="Utilisateurs" width="280"/><br/>
      <sub><b>Gestion des utilisateurs</b> — rôles et permissions</sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/27-users-edit.png" alt="Utilisateur – édition" width="280"/><br/>
      <sub><b>Édition utilisateur</b></sub>
    </td>
    <td align="center" width="33%">
      <img src="screen/28-user-profil.png" alt="Profil" width="280"/><br/>
      <sub><b>Profil & 2FA</b> — double authentification TOTP</sub>
    </td>
  </tr>
</table>

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
