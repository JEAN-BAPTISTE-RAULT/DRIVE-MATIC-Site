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
- **Thème admin** : **Gin** (`gin.settings.classic_toolbar: horizontal`), avec la **barre du cœur** (`toolbar` + `admin_toolbar`) — barre noire « Gérer / admin » surmontant le menu horizontal. ⚠️ **Deux modules doivent rester désinstallés** ([ADR-012](.claude/decisions/012-presentation-admin-front.md)) :
  - **`gin_toolbar`** : actif, il reprend la main sur la barre **du front** et rend sa propre version claire à icônes, différente de la présentation retenue. Le paquet Composer, lui, **reste sur disque** — `drupal/gin` l'exige en dépendance ferme, il est donc impossible de le supprimer ; seule la dépendance racine redondante a été retirée de `composer.json`. Un `drush pm:enable` ou une recette qui le réactiverait changerait la barre du front sans autre signe.
  - **`navigation`** (cœur) : dès qu'il est actif, `gin_toolbar` pose `gin--core-navigation` et **sort avant** d'appliquer la classe horizontale — la barre latérale verticale du cœur gagne et le réglage de Gin devient silencieusement inopérant.
- **Onglets locaux sur le front** (Voir / Modifier / Supprimer…) : carte grise posée en `fixed` en haut à droite, qui suit le défilement. La règle vit dans `src/scss/_local-tasks.scss` — **seule CSS hors SDC** en dehors des fondations, exception assumée et commentée dans `style.scss` (cf. ADR-012). Le crayon vient des **liens contextuels** du cœur, il n'est pas dessiné par le thème. Le décalage haut suit `--drupal-displace-offset-top`, la variable posée par `Drupal.displace`.
- **Formulaires de contenu** : présentation unifiée en **deux onglets horizontaux** (`field_group`), champs techniques désactivés, alias d'URL par **Pathauto** (`/[node:title]`). Convention et pièges dans [CLAUDE.md](CLAUDE.md) et `docs/active/content-types/model.md`.
- **Build front** : les **fondations** SCSS (`src/scss/` → `css/style.css`, library `drive_matic/global`) et le **CSS des SDC** (`components/**/*.scss` → `.css` co-localisé, auto-attaché par SDC) sont compilés par Dart Sass + PostCSS/autoprefixer. Scripts npm `css` (`css:foundations` + `css:components` + `css:prefix`), `build`, `css:watch`. Le `.css` généré des SDC est un **artefact** (ignoré par Prettier via `.prettierignore` — Sass impose des choix que Prettier refuse) ; seul le `.scss` source est vérifié (Stylelint + Prettier).
- **Espacement et unités** ([ADR-013](.claude/decisions/013-espacement-et-unites.md)) : trois tokens portent tout le rythme — `--dm-space-element` (24px, entre éléments d'un bloc), `--dm-space-block` (32px, `padding-block`, donc 64px entre deux blocs), `--dm-gutter` (40px, `padding-inline`). **La gouttière est un `padding-inline`, jamais un `margin-inline: auto`** : celui-ci ne fait que centrer une largeur plafonnée et ne garantit rien sous ce plafond — c'est ce qui faisait toucher le bord au jumbo, à la grille et aux actualités en responsive sans que ça se voie à 1440. Plafond = contenu + 2 gouttières. **Unités : espacement en `px`, typographie en `rem`** (le texte suit la préférence du navigateur, la mise en page ne bouge pas). ⚠️ Deux pièges : les marges du premier/dernier enfant d'un champ **texte riche** sont neutralisées dans les fondations (`.text-formatted`) — sinon les 16px du `<p>` s'ajoutent à l'écart du bloc ; et une marge ne se pose qu'avec `:not(:last-child)`, un champ optionnel vide pouvant faire du titre le dernier élément affiché (le reset ne neutralisant que les marges du `body`, un titre seul reprendrait sinon celle du navigateur).
- **Config** : versionnée dans **`config/sync/`** (`drush cex` / `drush cim`). Après avoir écrit de la config à la main, lancer `drush cim` puis **`drush cex`** pour la réécrire sous forme canonique (Drupal trie les clés) avant commit.
- **Breakpoints** : `drive_matic.breakpoints.yml` (xs→xxl, 1x/2x).
- **Modules clés** : Paragraphs, Webform (+ reCAPTCHA v3, Honeypot), Media (+ Media Library, Responsive Image) + Crop + Image Widget Crop, Metatag, Pathauto, Redirect, Simple Sitemap, Rabbit Hole (fragments sans page publique), Linkit + Link Target (liens internes/externes + cible), Video Embed Field, Symfony Mailer (+ Mailer Transport), Better Exposed Filters, Easy Breadcrumb, Twig Tweak, Rename Admin Paths.

**Base de données locale** : MAMP MySQL (8889), base `drivematic`. `settings.php` (gitignoré) porte les accès locaux + `config_sync_directory = ../config/sync`.

**E-mails** : gérés par **Symfony Mailer** (+ `mailer_transport`). Le transport par défaut versionné est `sendmail` ; en **local**, les mails sont routés vers **Mailpit** (SMTP `127.0.0.1:1025`, UI `http://localhost:8025`) via un override dans `settings.php` (gitignoré) : `$config['mailer_transport.settings']['default_transport'] = 'mailpit';`. La prod définira son propre transport SMTP.

**Anti-spam** : le webform contact utilise **reCAPTCHA v3** (+ Honeypot + time restriction). La **clé site** est versionnée (`recaptcha_v3.settings`), la **clé secrète** n'est **jamais commitée** : elle est posée dans `settings.php` (gitignoré) via `$config['recaptcha_v3.settings']['secret_key'] = '…';`. reCAPTCHA v3 étant lié au domaine, sa validation ne se teste qu'en préprod/prod (ajouter le hostname local aux domaines de la clé Google pour tester en local).

**Images** (cf. ADR-004) : toute image est une entité **Media** (bundle `image`), recadrable en BO aux ratios **1:1 / 16:9 / 12:5** (+ sans-crop). Le **ratio se choisit au rendu** via un **mode d'affichage média** (`free` / `ratio_1_1` / `ratio_16_9` / `ratio_12_5`), chacun appliquant son **responsive image style** (`dm_*`, sortie **WebP + fallback**, mappés sur les 6 breakpoints).

⚠️ Le recadrage est **manuel** : les styles `dm_<ratio>_*` enchaînent `crop_crop` (type de crop correspondant, sans `automatic_crop_provider`) puis `image_scale`. **Sans entité `crop` enregistrée sur le média**, l'effet est un **no-op silencieux** : l'image garde le ratio de sa source et le rendu n'est **pas** au ratio annoncé (un 3:2 passe pour un 16:9 approximatif — invisible à l'œil, mesurable seulement). Conséquence : tout média doit être recadré en BO pour chaque ratio auquel il sera rendu ; et un contrôle visuel de composant n'est valable que sur des médias effectivement recadrés.

**Types de contenu** — le modèle éditorial ADR-002 est **complet** : 12 nodes publics, 3 fragments, plus le bac à sable temporaire. Détail du modèle dans `docs/active/content-types/model.md`.

| Type | Rôle | Particularités |
|---|---|---|
| `homepage` | Accueil | **seul public sans `field_title` ni alias** (titre porté par un paragraphe) ; servie à `/` |
| `transform` | Solution auto-école / PMR (F4) | 7 paragraphes autorisés, dont `grid` |
| `product` | Fiche produit (F5) | 10 paragraphes dont les 4 blocs V5 ; **pas de `grid`** ; aucun prix |
| `corporate` | Éditorial Drive Matic (F9) | 9 paragraphes, dont `triptych`, `history`, `image_centered`, `video_centered` |
| `legals` | Pages légales | **sans body ni champ « Balises meta »** ; `text_left_aligned` seul |
| `news` | Détail d'actualité (F8) | image **16:9** + légende ; date = `changed` ; alias `/actualites/…` ; 3 paragraphes |
| `all_news` | Liste des actualités (F8) | Vue `all_news` embarquée, **10 par page**, tri `changed` desc |
| `faq` | FAQ (F9) | Vue `faq` embarquée, filtre **BEF** par catégorie, rendu en accordéon |
| `documents` | Documentations (F6) | 2 champs référence ordonnés (`field_documents_school`, `field_documents_pmr`) |
| `brands` | Marques partenaires (F7) | Vue `brands` embarquée (ordre alpha), SDC `brands-grid` |
| `contact`, `partner` | Formulaires (F10, F11) | Webform référencé |
| `question`, `document`, `brand` | **fragments** | pas de page publique : Rabbit Hole → **403**, hors sitemap, sans alias ni métatags |
| `page` | **Bac à sable paragraphes** | hors modèle : seul type autorisant les 18 blocs, un seul node (`33`), disparaît en fin de chantier |

**Un seul titre : le `title`** ([ADR-014](.claude/decisions/014-titre-unique-porte-par-le-title.md), qui remplace l'ADR-011) : le `title` du node alimente l'affichage, le fil d'Ariane, l'alias et la balise `title`. `field_title` a été **supprimé des 12 types de contenu** (storage `field.storage.node.field_title` compris) ; ⚠️ `field.storage.paragraph.field_title` est un champ homonyme distinct — titre des blocs, 21 bundles — et reste en place. Tout rendu hors page canonique (carte, teaser, ligne de Vue) lit le `title`.

**Où s'affiche le `<h1>`** : sur `homepage`, `transform` et `product`, la maquette place le titre dans un bloc — le bloc titre de page y est masqué (condition `entity_bundle:node` négée) et le premier paragraphe **portant un titre** rend le `<h1>` via la prop `heading_level` des SDC `image-full` / `text-centered`. Partout ailleurs, le bloc titre rend le `title` en `<h1>` : il est en région **`content`, poids -10**, donc au-dessus du contenu (il était en `sidebar_first`, rendue après le contenu → `<h1>` en bas de page). `_drive_matic_hero_title_bundles()` et la visibilité du bloc doivent rester alignées.

**Alias** : motif Pathauto `/[node:title]` sur tous les types publics ; **exception** `news` → `/actualites/[node:title]` (la page `all_news` vivant à `/actualites`, le segment parent est un chemin valide, ce qui donne un vrai fil d'Ariane à 3 niveaux). Aucun motif sur `homepage` ni sur les fragments.

**Sitemap** : l'inclusion est **opt-in** — un bundle sans `simple_sitemap.bundle_settings.default.node.<bundle>` n'est **pas** indexé. C'est ainsi que les 3 fragments et le bac à sable en sont exclus. `homepage` n'a volontairement pas de réglage de bundle : la racine est déjà déclarée en **lien personnalisé** (`simple_sitemap.custom_links.default`, priorité 1.0), un réglage de bundle produirait un doublon.

**Front page** : le node unique servi à `/` est résolu **dynamiquement** par le module custom **`drivematic_home`** (service `FrontPageOverride`, un `ConfigFactoryOverride`) qui surcharge `system.site:page.front` vers l'unique node `homepage` publié — **sans ID de node en dur, sans alias**, portable au seed. Les surcharges n'étant jamais exportées, la valeur versionnée reste `/node` (aucune dérive de config). Invalidation via le tag `node_list:homepage`. Fil d'Ariane et titre de page **masqués sur `<front>`** (visibilité de bloc `request_path`).

⚠️ **`FrontPageOverride` ne doit injecter aucun service** : elle résout le gestionnaire d'entités **paresseusement**, dans la méthode qui s'en sert. Une `ConfigFactoryOverride` est construite par `config.factory`, qui devient une dépendance du traducteur de chaînes dès que `locale` est installé — injecter referme la boucle (`config.factory` → surcharge → `entity_type.manager` → `string_translation` → `locale.lookup` → `config.factory`) et **le conteneur refuse de se construire** : plus une seule page, plus même de bootstrap Drush. C'est la seule dérogation à l'injection de dépendances du projet, déclarée par un `phpcs:ignore` ciblé.

**Langue** : site **monolingue français** (décision #6), `language` + `locale` installés, français seule langue et langue par défaut. Traductions d'interface importées par `drush locale:update` (15 309 chaînes, 27 projets) ; les traductions de **configuration** sont versionnées dans `config/sync/language/fr/` (193 fichiers). Fil d'Ariane : Easy Breadcrumb a désormais `alternative_title_field` **vide** et retombe donc sur le label du node, c'est-à-dire le `title` (ADR-014) ; la capitalisation automatique est désactivée.

⚠️ **Migrer une base antérieure à la bascule casse toutes les URL** : les alias portent le langcode du contenu. Sur un site passé en `fr`, les alias restés en `en` ne sont plus trouvés et chaque page retombe sur `/node/N`. Il faut repasser le contenu en français (nodes, médias, paragraphes) — l'enregistrement régénère les alias — puis **forcer** ceux des nodes marqués « alias défini manuellement » (`pathauto = 0`), que rien ne régénère. Détail dans docs/PRD.md, section 7.

⚠️ **Conséquence non traitée** : la page d'accueil n'a donc **aucun `<h1>`** — son titre visible vient du SDC `text_centered`, qui rend un `<h2>`. Contredit la décision #8 du PRD (RGAA / WCAG 2.1 AA) ; à trancher avec le placement du titre, à l'intégration des maquettes.

**Vues embarquées** (`all_news`, `faq`, `brands`) : rendues par `drupal_view()` (Twig Tweak) dans le template du node hôte, et non par un display « page » — le node garde ainsi son titre éditorial, son corps de texte et ses métatags surchargeables. Contrairement à `drupal_view_result()` (cf. blocs home), `drupal_view()` **propage les cache tags** ; pagination et filtre exposé fonctionnent en pur GET. Les lignes sont enveloppées par un SDC via un template de Vue : `views-view-unformatted--faq.html.twig` → `accordion`, `views-view-unformatted--brands.html.twig` → `brands-grid`. Le formulaire exposé de la FAQ reste **hors** de l'accordéon.

**Balises meta** ([ADR-010](.claude/decisions/010-metatags.md)) : remplissage **automatique** par les défauts Metatag + champ de **surcharge** `field_meta_tags` (widget `metatag_firehose` en barre latérale du formulaire, masqué au rendu) sur tous les nodes publics **sauf `legals`** — **à ajouter à chaque nouveau node public**. Deux étages de défauts : le défaut global `node` reste sur `title: [node:title] | [site:name]` et **chaque bundle public a son propre défaut** `metatag.metatag_defaults.node__<bundle>` en `[node:title] | [site:name]` (ADR-014). `legals`, qui n'a ni body ni champ de surcharge, garde malgré tout un défaut de bundle limité au **titre** — sans quoi sa balise `<title>` afficherait le libellé d'administration. ⚠️ Sur `<front>`, Metatag applique son défaut spécial **`front`** qui **remplace** les défauts `node`/`node__homepage` : le mapping y est donc recopié, en gardant `canonical_url: [site:url]` (la canonique de l'accueil doit rester la racine, pas `/node/<id>`). La surcharge par le champ du node s'applique malgré tout sur l'accueil. Détail et raison dans `docs/active/content-types/model.md`.

**Shell de page (SDC)** : l'ossature globale passe par deux SDC — `site-header` (slots branding + menu) et `site-footer` (bandeau sobre, prop `site_name` + slot menu ; le footer riche relève de F2). `templates/layout/page.html.twig` les embarque (`{% embed 'drive_matic:site-header' %}` / `site-footer`). Le menu principal multi-niveaux et le footer riche = **F2 Navigation** (à venir).

**Paragraphes / SDC** : chaque paragraphe = un type Paragraph + un **SDC** (`components/<nom>/`) ; la liaison se fait via `paragraph--<type>.html.twig` (`{% embed 'drive_matic:<nom>' %}` → props + slots accédés par `{{ block('nom') }}`).

**SDC hors paragraphes** : `site-header`, `site-footer` (shell) ; `news-card` (carte du bloc home) et **`news-teaser`** (ligne de la liste `all_news` — même contenu **plus la date**) ; `brand-logo` (tuile) et **`brands-grid`** (grille de la page Marques, là où la home défile) ; `video-play` (pastille inclue par les façades vidéo). Un même SDC peut servir un paragraphe **et** une Vue : la FAQ réutilise `accordion` + `accordion-element` en rendant chaque node `question` en mode `teaser` — zéro JS ajouté, le behavior existant s'applique.

- **Convention prop/slot** : les champs **scalaires** (titre, légende, options) sont passés en **props** (`paragraph.field_x.value`) et **masqués** dans le view display ; les champs **renderable** (description, lien, fichier, image, vidéo) sont injectés en **slots** via leurs formatters (`content.field_x`, donc présents dans le view display).
- **Storages de champ partagés** sur l'entité `paragraph` : `field_title` (string), `field_description` (text_long), `field_link` (link), `field_file` (file), `field_image` (ref media), `field_caption` (string), `field_video` (video_embed_field), `field_text_position` / `field_background` (list_string, options 50/50). **V3** ajoute : `field_links` (link, **card -1** — liens multiples de `grid_element`), `field_triptych_elements` (ERR, **card 3** — bloc plafonné), `field_text_top` / `field_text_bottom` (string — textes du triptyque). **V4** ajoute : `field_jumbo_elements` (ERR, **card 3** — bloc `jumbo_home` plafonné). **V5** ajoute : `field_arguments` (string, **card 3** — titres de `product_arguments`), `field_features_elements` (ERR, **card 5** — `product_features`, cible `product_image_element` **+** `product_video_element`), `field_cross_elements` (ERR, **card 5** — `product_cross`), `field_file_notice` / `field_file_doc` (file — notice technique + documentation de `product_characteristics`).
- **Image** = média + **mode d'affichage par ratio** (`ratio_1_1` / `ratio_16_9` / `ratio_12_5` / `free`), cf. ADR-004. Crop **requis** à l'import.
- **Vidéo** (`video_centered`) = **façade** : champ `video_embed_field` (allowlist providers `youtube`/`vimeo`, re-vérifiée serveur) + miniature média 16:9 ; l'iframe est rendue dans un `<template>` inerte et injectée seulement au clic (behavior `driveMaticVideoFacade` + `once`, bouton accessible clavier). Cf. [ADR-006](.claude/decisions/006-video-embed-facade.md).
- **Bibliothèque** (ADR-001) livrée par vagues (`docs/plans/paragraphs-sdc.md`) : **V0** `text_centered` ; **V1** `text_left_aligned`, `image_text_50` (options texte G/D + fond gris/blanc), `image_text_100`, `image_centered`, `image_full` (bannière 12:5), `video_centered` ; **V2** `accordion` + `accordion_element` (première paire **Bloc/Élément**) ; **V3** `grid` + `grid_element` (grille responsive 16:9 + liens multiples), `triptych` + `triptych_element` (3 blocs chiffre/accroche, **plafonné 3**), `history` + `history_element` (frise **slideshow Swiper**, image **ou** vidéo) ; **V4 (home)** `jumbo_home` + `jumbo_home_element` (bannière slideshow, plafonné 3), `news_home` / `brands_home` (blocs à **Vue** en slideshow, cf. ci-dessous) ; **V5 (produit)** `product_arguments` (1 à 3 titres), `product_features` + `product_image_element` / `product_video_element` (présentation « swipe » en **slideshow**, plafonné 5, image **ou** vidéo en façade), `product_characteristics` + `product_characteristic_element` (image **sans crop** + N caractéristiques + notice/documentation téléchargeables), `product_cross` + `product_cross_element` (cross-selling en **grille de cartes liées**, plafonné 5). **Bibliothèque ADR-001 complète (27 paragraphes).**
- **Blocs home à Vue** (`news_home`, `brands_home`, V4) : titre (prop) + lien « voir tout » (slot) + un **slideshow d'entités** rendues en **mode d'affichage `card`** (SDC `news-card` / `brand-logo` via `templates/content/node--{news,brand}--card.html.twig`). ⚠️ **Swiper exige `.swiper-wrapper` en enfant DIRECT** : on **n'embarque pas** la Vue complète (`drupal_view`, qui ajoute `.view` / `.views-element-container`) mais on rend les diapositives via **`drupal_view_result()` + `drupal_entity(node, id, 'card')`** dans le template paragraphe, le SDC fournissant `.swiper-wrapper`. Le tag de cache de liste (`node_list:news` / `node_list:brand`, perdu par `drupal_view_result`) est réattaché par **`drive_matic_preprocess_paragraph`** (`drive_matic.theme`). Les marques ne sont **pas** liées (page canonique du fragment bloquée).
- **Paires Bloc/Élément** (ADR-007) : le Bloc référence ses Éléments via le **storage partagé `field_elements`** (`entity_reference_revisions`, cardinalité illimitée) ; le type d'Élément autorisé est fixé par instance (`target_bundles`). Les types « Élément » sont **exclus du placement direct** (absents du `target_bundles` de `node.page.field_paragraphs`). Réutilisable par les Blocs non plafonnés (accordion, grid, history…). Les Blocs **plafonnés** reçoivent un **storage dédié** à la cardinalité voulue : `triptych` (`field_triptych_elements`, card 3 — V3) ; `jumbo_home` (`field_jumbo_elements`, card 3 — V4) ; `product_features` (`field_features_elements`, card 5, **deux** bundles Élément) et `product_cross` (`field_cross_elements`, card 5) — V5. `product_characteristics` (non plafonné) **réutilise** `field_elements`.
  - ⚠️ **Supprimer un node ne supprime pas ses paragraphes** : `entity_reference_revisions` ne cascade pas, les paragraphes deviennent **orphelins** en base (c'est la raison d'être du purgeur d'orphelins du module). Mesuré en supprimant 11 nodes de test : 0 paragraphe supprimé, 35 orphelins à purger — et il faut **boucler**, car purger un Bloc orphelin orpheline à son tour ses Éléments.
- **Ligne cliquable d'une carte cross-selling** : `product_cross_element` porte un **titre** et un **lien**, tous deux obligatoires, alors que la maquette n'affiche qu'une ligne. Le helper **`_drive_matic_titled_link()`** (`drive_matic.theme`) compose le lien à partir du **titre** (libellé) et de l'**objet `Url` du champ** (destination), ce qui conserve les options saisies en back-office — dont la cible d'ouverture (ADR-002). Le champ lien n'est donc pas rendu par son formatter et **son libellé n'est jamais affiché**.
- **Boutons de téléchargement** ([ADR-009](.claude/decisions/009-telechargements-nommes.md)) : le **libellé est éditorial** sur **tous** les champs fichier — c'est la **description du fichier** saisie en back-office (`description_field: true` sur les 9 instances `field_file*` des paragraphes) ; **format et poids** restent calculés. Elle est **obligatoire dès qu'un document est joint** : le cœur n'ayant pas ce réglage, `drivematic_forms` ajoute un `#process` après celui de `FileWidget` (`hook_field_widget_single_element_file_generic_form_alter`) qui la passe `#required` et la renomme « Libellé du bouton de téléchargement ». Comme le cœur ne construit cet élément qu'avec un fichier joint, un champ fichier vide reste facultatif. Deux rendus, un seul comportement :
  - **document unique** → override global `templates/field/file-link.html.twig` (+ `drive_matic_preprocess_file_link()`, qui expose `download_url`, `file_extension` et `download_label`).
  - **bloc multi-documents** (`product_characteristics`) → le formatter ne convient pas (les boutons seraient indiscernables) : le helper **`_drive_matic_document_downloads()`** parcourt les champs fichier et renvoie `label/url/format/size`, le SDC rend le bouton lui-même (prop `downloads`, plus de slots `notice`/`documentation`). Comme le fichier n'est pas rendu par son formatter, ses **cache tags sont réattachés** explicitement. Point d'entrée à reprendre pour F6 Documentations.
  - Les replis en l'absence de description (« Télécharger » / nom du fichier) subsistent comme **filet** pour le contenu créé avant la contrainte ou inséré par script — plus comme un chemin éditorial.
- **Accordéon** (`accordion`) : composant **interactif** — premier behavior JS du thème (`components/accordion/accordion.js`, `Drupal.behaviors.driveMaticAccordion` + `once`, auto-attaché par le SDC, deps `core/drupal`+`core/once` via `libraryOverrides`). Pattern **disclosure ARIA** (`<button aria-expanded>` ↔ panneau `role="region"`), **fermeture du précédent** à l'ouverture, clavier natif. **Fermé par défaut sans flash** grâce à la fondation `no-js`/`js` (cf. ci-dessous) ; **amélioration progressive** : le markup serveur ne pose ni `aria-expanded` ni `hidden`, donc sans JS tous les panneaux restent ouverts et lisibles.
- **Fondation `no-js`/`js`** : `html.html.twig` pose `class="no-js"` sur `<html>` ; la lib `drive_matic/js-detect` (chargée en `<head>`, `header: true`) bascule en `js` avant peinture. Le CSS des composants à amélioration progressive gate son état « JS actif » sous `html.js` → pas de flash. NB : Drupal core ajoute aussi sa propre classe `js` (via `misc/drupal.init.js`, chargé en **footer** — trop tard pour éviter le flash) ; d'où le doublon inoffensif `class="js js"`.
- **Slideshow** (ADR-008) : **Swiper vendorisé** (`vendor/swiper/swiper-bundle.min.{js,css}`, global `Swiper` + a11y, **self-contained**/RGPD). Libs `drive_matic/swiper` (assets) + `drive_matic/slideshow` (behavior `driveMaticSlideshow` : init Swiper **si ≥ 2** `.swiper-slide`, sinon repli liste ; `prefers-reduced-motion` → `speed: 0` ; flèches masquées tant que non initialisé ; empilement sans JS). Global `Swiper` déclaré dans `eslint.config.mjs`. Premier usage : `history` (réutilisé par V4 home + V5 produit).
  - **Options par data-attribut** sur `[data-dm-slideshow]` : `data-dm-slideshow-per-view` (nombre ou `auto`, défaut `1`) et `data-dm-slideshow-space` (px entre diapositives, défaut `24`). La **pagination** (points) s'active en plaçant un `[data-dm-slideshow-pagination]` **dans le conteneur du composant** (frère de `.swiper`, cherché via `el.parentElement`) — absent = module désactivé. Utilisés par `jumbo_home` (`auto` + `20` + pagination).
  - ⚠️ **Pièges CSS du bundle Swiper** (rencontrés sur `jumbo_home`) : `.swiper-pagination-horizontal` force `position: absolute` en spécificité (0,2,0) — répéter la classe (`&__pagination.swiper-pagination`) pour la repasser en flux ; et chaque puce reçoit `margin: 0 4px` via une règle imbriquée (0,3,0) — l'annuler par `--swiper-pagination-bullet-horizontal-gap: 0` plutôt qu'en surenchérissant.
  - ⚠️ **`.swiper` bat le BEM à égalité de spécificité** : le bundle déclare `margin-left/right: auto` et `overflow: hidden` sur `.swiper` (0,1,0), agrégé **après** le CSS des SDC. Toute reprise de ces propriétés doit **répéter la classe** — `&__viewport.swiper { … }`, replis `no-js` compris — sinon elle est écrasée sans erreur (c'est ce qui neutralisait la piste débordante de `jumbo_home` et `history`).
  - **Flèches de défilement — une seule apparence pour tous les paragraphes** : les deux assets `images/icons/slideshow-{next,prev}.svg` sont l'**export des calques « Next »** de la maquette Home (`Group 4` = suivant, `Group 5` = précédent) et portent **la plaque gris clair à 80 % et le chevron**. Chaque composant ne déclare donc plus que la taille (44px), sa **position** et l'asset — plus de plaque ni de chevron masqué à redessiner, et plus de `scaleX(-1)` pour la flèche gauche (le calque « précédent » a son propre chevron). Le jumbo, dont la maquette montrait une plaque **blanche**, suit la même flèche que les autres (consigne « même flèche partout »).
  - **Flèches hors de la piste** : `jumbo_home` les superpose au visuel (dans le `.swiper`), `history` et `news_home` les placent dans l'en-tête du bloc (`news_home` avec un titre **centré** : grille `1fr auto 1fr`, la colonne vide de gauche équilibre le titre sans élément de remplissage), `brands_home` les superpose **aux extrémités de la rangée** — donc dans un wrapper positionné **hors** du `.swiper`, qui masque son débordement.
  - **Flèche centrée sur une partie de la diapositive** (`product_features`, dont la diapositive porte un visuel 16:9 **puis** du texte) : la largeur de diapositive est déclarée en **`vw`** (`--dm-features-slide: min(900px, 70vw)`) et non en `%`, pour pouvoir la réutiliser dans `top: calc(var(--dm-features-slide) * 9 / 32)` — un `%` s'y résoudrait sur la **hauteur** du conteneur. Une seule source de vérité pour la largeur de diapo et le centrage de la flèche.
  - ⚠️ **Ordre des règles imposé par Stylelint** : `no-descending-specificity` exige que la règle `:has(… .swiper-initialized)` (spécificité élevée) soit déclarée **après** toutes les règles simples du même élément (`:focus-visible`, `.swiper-button-disabled`) — sinon le build lint échoue. Le behavior cherche flèches **et** pagination dans le conteneur du composant, donc les deux placements marchent sans JS dédié ; quand elles sont hors de la piste, l'état « démarré » se lit via `:has()` (cf. ADR-008).
- **Idiome pleine largeur (full-bleed)** : `width: 100vw` + `margin-inline: calc(50% - 50vw)` — utilisé par `image_full`, `product_characteristics` (bandeau sombre), et à droite seulement par les pistes de `jumbo_home` / `history`. ⚠️ **Réserve non traitée** : `vw` compte la **barre de défilement**, donc sur un OS à barres classiques (Windows) l'élément dépasse de sa largeur (~8-15px) et le document gagne un léger défilement horizontal. Invisible sous macOS (barres en surimpression), invisible aussi en capture headless. Correctif habituel si le besoin se confirme : `overflow-x: hidden` global — non posé, car il a ses propres effets de bord (`position: sticky`). **À vérifier sur Windows en recette.**
- **Façade vidéo mutualisée** (ADR-006 maj) : lib `drive_matic/video-facade` (`js/video-facade.js`, behavior générique piloté par data-attributs `[data-dm-video-facade]`/`[data-dm-video-play]`/`template[data-dm-video-embed]`), partagée par `video_centered`, `history_element` et `product_video_element` ; l'iframe embed n'est injectée qu'au **clic** (perf + RGPD).
  - **Pastille de lecture** : SDC dédié **`video-play`** (markup + style), inclus par les trois façades via `{{ include('drive_matic:video-play') }}` dans leur `<button>`. La CSS d'un SDC **inclus** est bien attachée — pas de dépendance à déclarer. Le visuel est l'**export du calque maquette** `images/icons/video-play.svg` (plaque blanche 70px, rayon 8, 70 % d'opacité + glyphe acier) : les couleurs sont dans le SVG, contrairement aux icônes appliquées en `mask`. Le conteneur de la miniature doit être **positionné** (la plaque se centre en absolu).

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
.claude/decisions/           ADR (001 paragraphes · 002 types de contenu · 003 référentiel véhicules · 004 pipeline images · 005 config par environnement · 006 vidéo embed + façade · 007 storage partagé Éléments · 008 slideshow Swiper · 009 téléchargements nommés · 010 métatags · 011 titre affiché + alias)
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
