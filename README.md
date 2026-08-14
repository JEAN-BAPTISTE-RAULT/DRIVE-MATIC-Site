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
  - ⚠️ **Contrainte réelle, pas indicative** : `sass ^1.80` est **ESM-only**. Sous un Node < 20, `npm run css` s'interrompt sur `ERR_REQUIRE_ESM` — et si la sortie est redirigée, le build **paraît réussir** alors que les `.css` compilés restent périmés : on debugge alors un rendu qui ne correspond plus au SCSS. Vérifier `node -v` avant tout build front ; avec nvm : `nvm use` (voir `.nvmrc`).
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
- **Build front** : les **fondations** SCSS (`src/scss/` → `css/style.css`, library `drive_matic/global`) et le **CSS des SDC** (`components/**/*.scss` → `.css` co-localisé, auto-attaché par SDC) sont compilés par Dart Sass + PostCSS/autoprefixer. Scripts npm `css` (`css:foundations` + `css:components` + `css:prefix`), `build`, `css:watch`. Le `.css` généré des SDC est un **artefact** (ignoré par Prettier via `.prettierignore` — Sass impose des choix que Prettier refuse) ; seul le `.scss` source est vérifié (Stylelint + Prettier).
- **Config** : versionnée dans **`config/sync/`** (`drush cex` / `drush cim`). Après avoir écrit de la config à la main, lancer `drush cim` puis **`drush cex`** pour la réécrire sous forme canonique (Drupal trie les clés) avant commit.
- **Breakpoints** : `drive_matic.breakpoints.yml` (xs→xxl, 1x/2x).
- **Modules clés** : Paragraphs, Webform (+ reCAPTCHA v3, Honeypot), Media (+ Media Library, Responsive Image) + Crop + Image Widget Crop, Metatag, Pathauto, Redirect, Simple Sitemap, Rabbit Hole (fragments sans page publique), Linkit + Link Target (liens internes/externes + cible), Video Embed Field, Symfony Mailer (+ Mailer Transport), Better Exposed Filters, Easy Breadcrumb, Twig Tweak, Rename Admin Paths.

**Base de données locale** : MAMP MySQL (8889), base `drivematic`. `settings.php` (gitignoré) porte les accès locaux + `config_sync_directory = ../config/sync`.

**E-mails** : gérés par **Symfony Mailer** (+ `mailer_transport`). Le transport par défaut versionné est `sendmail` ; en **local**, les mails sont routés vers **Mailpit** (SMTP `127.0.0.1:1025`, UI `http://localhost:8025`) via un override dans `settings.php` (gitignoré) : `$config['mailer_transport.settings']['default_transport'] = 'mailpit';`. La prod définira son propre transport SMTP.

**Anti-spam** : le webform contact utilise **reCAPTCHA v3** (+ Honeypot + time restriction). La **clé site** est versionnée (`recaptcha_v3.settings`), la **clé secrète** n'est **jamais commitée** : elle est posée dans `settings.php` (gitignoré) via `$config['recaptcha_v3.settings']['secret_key'] = '…';`. reCAPTCHA v3 étant lié au domaine, sa validation ne se teste qu'en préprod/prod (ajouter le hostname local aux domaines de la clé Google pour tester en local).

**Images** (cf. ADR-004) : toute image est une entité **Media** (bundle `image`), recadrable en BO aux ratios **1:1 / 16:9 / 12:5** (+ sans-crop). Le **ratio se choisit au rendu** via un **mode d'affichage média** (`free` / `ratio_1_1` / `ratio_16_9` / `ratio_12_5`), chacun appliquant son **responsive image style** (`dm_*`, sortie **WebP + fallback**, mappés sur les 6 breakpoints).

**Types de contenu** (modèle éditorial ADR-002, livré par tranches) : `page` (hôte composable de test), `contact`, `partner` (existants) ; **tranche V4** : `news` (Page :: actualité — body, image **16:9**, légende ; date = `changed`) et `brand` (**fragment** :: marque — image **sans crop** ; page canonique **bloquée** via Rabbit Hole → 403, **exclu du sitemap**). Deux Vues home associées : `news_home` (5 récentes, tri `changed` desc) et `brands_home` (toutes, ordre alpha). **Tranche F3 home** : `homepage` (Page :: Accueil — body obl + metatags ; allowlist paragraphes = `text_centered`, `text_left_aligned`, `image_text_50/100`, `grid`, `accordion`, `jumbo_home`, `news_home`, `brands_home` ; template dédié `node--homepage.html.twig` plein largeur, sans titre de node). **Node unique servi à `/`** : la front page est résolue **dynamiquement** par le module custom **`drivematic_home`** (service `FrontPageOverride`, un `ConfigFactoryOverride`) qui surcharge `system.site:page.front` vers l'unique node `homepage` publié — **sans ID de node en dur, sans alias**, portable au seed (le node recréé peut avoir n'importe quel ID). Les surcharges n'étant jamais exportées, la valeur versionnée reste `/node` (aucune dérive de config). Invalidation via le tag `node_list:homepage`. Fil d'Ariane et titre de page **masqués sur `<front>`** (visibilité de bloc `request_path`). Les autres nodes du modèle (`transform`, `product`, `all_news`…) restent à implémenter.

**Shell de page (SDC)** : l'ossature globale passe par deux SDC — `site-header` (slots branding + menu) et `site-footer` (bandeau sobre, prop `site_name` + slot menu ; le footer riche relève de F2). `templates/layout/page.html.twig` les embarque (`{% embed 'drive_matic:site-header' %}` / `site-footer`). Le menu principal multi-niveaux et le footer riche = **F2 Navigation** (à venir).

**Paragraphes / SDC** : chaque paragraphe = un type Paragraph + un **SDC** (`components/<nom>/`) ; la liaison se fait via `paragraph--<type>.html.twig` (`{% embed 'drive_matic:<nom>' %}` → props + slots accédés par `{{ block('nom') }}`).

- **Convention prop/slot** : les champs **scalaires** (titre, légende, options) sont passés en **props** (`paragraph.field_x.value`) et **masqués** dans le view display ; les champs **renderable** (description, lien, fichier, image, vidéo) sont injectés en **slots** via leurs formatters (`content.field_x`, donc présents dans le view display).
- **Storages de champ partagés** sur l'entité `paragraph` : `field_title` (string), `field_description` (text_long), `field_link` (link), `field_file` (file), `field_image` (ref media), `field_caption` (string), `field_video` (video_embed_field), `field_text_position` / `field_background` (list_string, options 50/50). **V3** ajoute : `field_links` (link, **card -1** — liens multiples de `grid_element`), `field_triptych_elements` (ERR, **card 3** — bloc plafonné), `field_text_top` / `field_text_bottom` (string — textes du triptyque). **V4** ajoute : `field_jumbo_elements` (ERR, **card 3** — bloc `jumbo_home` plafonné). **V5** ajoute : `field_arguments` (string, **card 3** — titres de `product_arguments`), `field_features_elements` (ERR, **card 5** — `product_features`, cible `product_image_element` **+** `product_video_element`), `field_cross_elements` (ERR, **card 5** — `product_cross`), `field_file_notice` / `field_file_doc` (file — notice technique + documentation de `product_characteristics`).
- **Image** = média + **mode d'affichage par ratio** (`ratio_1_1` / `ratio_16_9` / `ratio_12_5` / `free`), cf. ADR-004. Crop **requis** à l'import.
- **Vidéo** (`video_centered`) = **façade** : champ `video_embed_field` (allowlist providers `youtube`/`vimeo`, re-vérifiée serveur) + miniature média 16:9 ; l'iframe est rendue dans un `<template>` inerte et injectée seulement au clic (behavior `driveMaticVideoFacade` + `once`, bouton accessible clavier). Cf. [ADR-006](.claude/decisions/006-video-embed-facade.md).
- **Bibliothèque** (ADR-001) livrée par vagues (`docs/plans/paragraphs-sdc.md`) : **V0** `text_centered` ; **V1** `text_left_aligned`, `image_text_50` (options texte G/D + fond gris/blanc), `image_text_100`, `image_centered`, `image_full` (bannière 12:5), `video_centered` ; **V2** `accordion` + `accordion_element` (première paire **Bloc/Élément**) ; **V3** `grid` + `grid_element` (grille responsive 16:9 + liens multiples), `triptych` + `triptych_element` (3 blocs chiffre/accroche, **plafonné 3**), `history` + `history_element` (frise **slideshow Swiper**, image **ou** vidéo) ; **V4 (home)** `jumbo_home` + `jumbo_home_element` (bannière slideshow, plafonné 3), `news_home` / `brands_home` (blocs à **Vue** en slideshow, cf. ci-dessous) ; **V5 (produit)** `product_arguments` (1 à 3 titres), `product_features` + `product_image_element` / `product_video_element` (présentation « swipe » en **slideshow**, plafonné 5, image **ou** vidéo en façade), `product_characteristics` + `product_characteristic_element` (image **sans crop** + N caractéristiques + notice/documentation téléchargeables), `product_cross` + `product_cross_element` (cross-selling en **grille de cartes liées**, plafonné 5). **Bibliothèque ADR-001 complète (27 paragraphes).**
- **Blocs home à Vue** (`news_home`, `brands_home`, V4) : titre (prop) + lien « voir tout » (slot) + un **slideshow d'entités** rendues en **mode d'affichage `card`** (SDC `news-card` / `brand-logo` via `templates/content/node--{news,brand}--card.html.twig`). ⚠️ **Swiper exige `.swiper-wrapper` en enfant DIRECT** : on **n'embarque pas** la Vue complète (`drupal_view`, qui ajoute `.view` / `.views-element-container`) mais on rend les diapositives via **`drupal_view_result()` + `drupal_entity(node, id, 'card')`** dans le template paragraphe, le SDC fournissant `.swiper-wrapper`. Le tag de cache de liste (`node_list:news` / `node_list:brand`, perdu par `drupal_view_result`) est réattaché par **`drive_matic_preprocess_paragraph`** (`drive_matic.theme`). Les marques ne sont **pas** liées (page canonique du fragment bloquée).
- **Paires Bloc/Élément** (ADR-007) : le Bloc référence ses Éléments via le **storage partagé `field_elements`** (`entity_reference_revisions`, cardinalité illimitée) ; le type d'Élément autorisé est fixé par instance (`target_bundles`). Les types « Élément » sont **exclus du placement direct** (absents du `target_bundles` de `node.page.field_paragraphs`). Réutilisable par les Blocs non plafonnés (accordion, grid, history…). Les Blocs **plafonnés** reçoivent un **storage dédié** à la cardinalité voulue : `triptych` (`field_triptych_elements`, card 3 — V3) ; `jumbo_home` (`field_jumbo_elements`, card 3 — V4) ; `product_features` (`field_features_elements`, card 5, **deux** bundles Élément) et `product_cross` (`field_cross_elements`, card 5) — V5. `product_characteristics` (non plafonné) **réutilise** `field_elements`.
- **Accordéon** (`accordion`) : composant **interactif** — premier behavior JS du thème (`components/accordion/accordion.js`, `Drupal.behaviors.driveMaticAccordion` + `once`, auto-attaché par le SDC, deps `core/drupal`+`core/once` via `libraryOverrides`). Pattern **disclosure ARIA** (`<button aria-expanded>` ↔ panneau `role="region"`), **fermeture du précédent** à l'ouverture, clavier natif. **Fermé par défaut sans flash** grâce à la fondation `no-js`/`js` (cf. ci-dessous) ; **amélioration progressive** : le markup serveur ne pose ni `aria-expanded` ni `hidden`, donc sans JS tous les panneaux restent ouverts et lisibles.
- **Fondation `no-js`/`js`** : `html.html.twig` pose `class="no-js"` sur `<html>` ; la lib `drive_matic/js-detect` (chargée en `<head>`, `header: true`) bascule en `js` avant peinture. Le CSS des composants à amélioration progressive gate son état « JS actif » sous `html.js` → pas de flash. NB : Drupal core ajoute aussi sa propre classe `js` (via `misc/drupal.init.js`, chargé en **footer** — trop tard pour éviter le flash) ; d'où le doublon inoffensif `class="js js"`.
- **Slideshow** (ADR-008) : **Swiper vendorisé** (`vendor/swiper/swiper-bundle.min.{js,css}`, global `Swiper` + a11y, **self-contained**/RGPD). Libs `drive_matic/swiper` (assets) + `drive_matic/slideshow` (behavior `driveMaticSlideshow` : init Swiper **si ≥ 2** `.swiper-slide`, sinon repli liste ; `prefers-reduced-motion` → `speed: 0` ; flèches masquées tant que non initialisé ; empilement sans JS). Global `Swiper` déclaré dans `eslint.config.mjs`. Premier usage : `history` (réutilisé par V4 home + V5 produit).
- **Façade vidéo mutualisée** (ADR-006 maj) : lib `drive_matic/video-facade` (`js/video-facade.js`, behavior générique piloté par data-attributs `[data-dm-video-facade]`/`[data-dm-video-play]`/`template[data-dm-video-embed]`), partagée par `video_centered` et `history_element` ; l'iframe embed n'est injectée qu'au **clic** (perf + RGPD).

## Structure du projet

```
web/                         docroot Drupal
  core/  modules/contrib/  themes/contrib/   (Composer, gitignorés)
  modules/custom/drivematic_forms/  cascade véhicules du webform contact
  modules/custom/drivematic_home/   front page dynamique → node homepage (ConfigFactoryOverride)
  themes/custom/drive_matic/ thème front (SDC : components/, templates/, css/, js/, vendor/ = libs tierces vendorisées ex. Swiper)
  sites/default/settings.php accès BDD + overrides locaux (gitignoré)
src/scss/                    sources SCSS (fondations : tokens, reset, typographie)
config/sync/                 configuration Drupal versionnée
docs/                        PRD, E2E, plans, études (content-types, paragraphs…)
.claude/decisions/           ADR (001 paragraphes · 002 types de contenu · 003 référentiel véhicules · 004 pipeline images · 005 config par environnement · 006 vidéo embed + façade · 007 storage partagé Éléments · 008 slideshow Swiper)
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
