# DRIVE-MATIC

Site corporate de detail d'equipements automobiles a destination du grand public, avec un espace partenaire authentifie (~100 partenaires).

> Documentation technique de reprise. Les specifications fonctionnelles sont dans [docs/PRD.md](docs/PRD.md), les regles pour l'agent IA dans [CLAUDE.md](CLAUDE.md).

## Stack

| Couche | Technologie |
|--------|-------------|
| CMS / backend | Drupal 11 (PHP 8.3, MariaDB 10.11) |
| Templating | Twig |
| JavaScript | Vanilla JS (comportements Drupal) |
| Styles | SCSS (architecture SMACSS + nommage BEM) |

## Prerequisites

- **PHP** >= 8.3 et **Composer** 2.x
- **Node.js** >= 20 et **npm** (outillage front : ESLint, Stylelint, Prettier)
- (Recommande) **Drush** pour l'administration Drupal en ligne de commande

## Installation

```bash
npm install        # outillage front (ESLint, Stylelint, Prettier)
composer install   # standards de code PHP (drupal/coder : Drupal + DrupalPractice)
```

## Verification (lint & format)

Ces commandes doivent **toutes** passer avant de considerer un travail termine (cf. `/done`) :

```bash
npm run lint          # JS (ESLint) + SCSS (Stylelint) + PHP/Twig (PHPCS)
npm run format:check  # verification du formatage (Prettier)
npm test              # placeholder — strategie de test a definir (voir docs/PRD.md)
```

Correction automatique :

```bash
npm run lint:fix      # eslint --fix, stylelint --fix, phpcbf
npm run format        # applique le formatage Prettier
```

Scripts granulaires : `lint:js`, `lint:css`, `lint:php`. Config : `eslint.config.mjs`, `.stylelintrc.json`, `.prettierrc.json`, `phpcs.xml.dist`.

## Architecture

Projet **Drupal 11** (`drupal/recommended-project`), docroot **`web/`**.

- **Thème front** : `drive_matic` (`web/themes/custom/drive_matic/`) — thème **custom autonome** généré via `starterkit` (`base theme: false`, sans `stable9`, D12-proof), **SDC-first** (composants dans `components/`).
- **Thème admin** : **Gin** (toolbar horizontale).
- **Build front** : les **fondations** SCSS (`src/scss/` → `css/style.css`, library `drive_matic/global`) et le **CSS des SDC** (`components/**/*.scss` → `.css` co-localisé, auto-attaché par SDC) sont compilés par Dart Sass + PostCSS/autoprefixer. Scripts npm `css` (`css:foundations` + `css:components` + `css:prefix`), `build`, `css:watch`.
- **Config** : versionnée dans **`config/sync/`** (`drush cex` / `drush cim`).
- **Breakpoints** : `drive_matic.breakpoints.yml` (xs→xxl, 1x/2x).
- **Modules clés** : Paragraphs, Webform (+ reCAPTCHA v3, Honeypot), Media (+ Media Library, Responsive Image) + Crop + Image Widget Crop, Metatag, Pathauto, Redirect, Simple Sitemap, Rabbit Hole (fragments sans page publique), Linkit + Link Target (liens internes/externes + cible), Video Embed Field, Symfony Mailer (+ Mailer Transport), Better Exposed Filters, Easy Breadcrumb, Twig Tweak, Rename Admin Paths.

**Base de données locale** : MAMP MySQL (8889), base `drivematic`. `settings.php` (gitignoré) porte les accès locaux + `config_sync_directory = ../config/sync`.

**E-mails** : gérés par **Symfony Mailer** (+ `mailer_transport`). Le transport par défaut versionné est `sendmail` ; en **local**, les mails sont routés vers **Mailpit** (SMTP `127.0.0.1:1025`, UI `http://localhost:8025`) via un override dans `settings.php` (gitignoré) : `$config['mailer_transport.settings']['default_transport'] = 'mailpit';`. La prod définira son propre transport SMTP.

**Anti-spam** : le webform contact utilise **reCAPTCHA v3** (+ Honeypot + time restriction). La **clé site** est versionnée (`recaptcha_v3.settings`), la **clé secrète** n'est **jamais commitée** : elle est posée dans `settings.php` (gitignoré) via `$config['recaptcha_v3.settings']['secret_key'] = '…';`. reCAPTCHA v3 étant lié au domaine, sa validation ne se teste qu'en préprod/prod (ajouter le hostname local aux domaines de la clé Google pour tester en local).

**Images** (cf. ADR-004) : toute image est une entité **Media** (bundle `image`), recadrable en BO aux ratios **1:1 / 16:9 / 12:5** (+ sans-crop). Le **ratio se choisit au rendu** via un **mode d'affichage média** (`free` / `ratio_1_1` / `ratio_16_9` / `ratio_12_5`), chacun appliquant son **responsive image style** (`dm_*`, sortie **WebP + fallback**, mappés sur les 6 breakpoints).

**Paragraphes / SDC** : chaque paragraphe = un type Paragraph + un **SDC** (`components/<nom>/`) ; la liaison se fait via `paragraph--<type>.html.twig` (`{% embed 'drive_matic:<nom>' %}` → props + slots accédés par `{{ block('nom') }}`).

## Structure du projet

```
web/                         docroot Drupal
  core/  modules/contrib/  themes/contrib/   (Composer, gitignorés)
  modules/custom/drivematic_forms/  cascade véhicules du webform contact
  themes/custom/drive_matic/ thème front (SDC : components/, templates/, css/, js/)
  sites/default/settings.php accès BDD + overrides locaux (gitignoré)
src/scss/                    sources SCSS (fondations : tokens, reset, typographie)
config/sync/                 configuration Drupal versionnée
docs/                        PRD, E2E, plans, études (content-types, paragraphs…)
.claude/decisions/           ADR (001 paragraphes · 002 types de contenu · 003 référentiel véhicules · 004 pipeline images · 005 config par environnement)
composer.json                projet Drupal + modules
package.json                 build front + lint
```

## Documentation

| Fichier | Contenu |
|---------|---------|
| [CLAUDE.md](CLAUDE.md) | Regles, conventions et garde-fous pour l'agent IA |
| [docs/PRD.md](docs/PRD.md) | Specifications fonctionnelles |
| [docs/E2E_SCENARIOS.md](docs/E2E_SCENARIOS.md) | Scenarios de non-regression |
| [.claude/decisions/](.claude/decisions/) | Decisions architecturales (ADR) |
