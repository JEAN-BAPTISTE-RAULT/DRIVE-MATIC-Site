# Plan — Socle technique

> Plan d'implementation. Met en place le projet Drupal 11 installable + sous-theme SDC + Gin + modules + fondations, pour debloquer toutes les features.

## 0. Environnement (detecte)
- **Local = MAMP PRO** (PHP 8.3.20 natif, MySQL/MariaDB MAMP). Pas de DDEV/Lando.
- Convention projet alignee sur les autres sites (WEBLEX/CFPA/KUBIK, D11) : Composer `recommended-project`, docroot `web/`, `config/`, `patches/`.
- Build front aligne : **Dart Sass + PostCSS/autoprefixer**, sources `src/scss/`, scripts npm.

## 1. Intention
Disposer d'un Drupal 11 installe, d'un sous-theme `drive_matic` (SDC, tokens Figma, breakpoints), du back-office Gin, et de tous les modules requis par les features deja specifiees.

## 2. Fichiers crees / modifies
- **`composer.json`** : evolution de la version actuelle (coder seul) vers un vrai projet Drupal 11 (`drupal/core-recommended ^11`, scaffold, installers, composer-patches) — coder conserve en require-dev.
- **`web/`** (scaffold), **`web/sites/default/settings.php`** + **`settings.local.php`** (gitignore, credentials MAMP + hash salt + `file_private_path` + trusted hosts).
- **`config/sync/`** (export de config, versionnee).
- **Theme custom autonome** `web/themes/custom/drive_matic/` (genere via `starterkit`, `base theme: false`, templates copies — sans `stable9`) : `drive_matic.info.yml`, `.libraries.yml`, `.theme`, `drive_matic.breakpoints.yml`, `components/` (SDC), `css/`, `js/`, `fonts/`, `screenshot.png`.
- **`src/scss/`** : fondations (`reset`, `tokens` = couleurs/typos Figma, `typography`) compilees vers `web/themes/custom/drive_matic/css/`. CSS des SDC co-localise et scope par composant.
- **`package.json`** : ajout des scripts `css`/`css:compile`/`css:prefix`/`build`/`watch` (sass + postcss autoprefixer) + browserslist, en conservant l'outillage de lint existant.
- **Config linter** : `phpcs.xml.dist` (scanner `web/modules/custom` + `web/themes/custom`), ESLint (globals `Drupal`, `drupalSettings`, `once`), Stylelint (SCSS), Prettier.
- **`.gitignore`** (aligne sur ta convention WEBLEX) : `vendor/`, `web/core`, `web/modules/contrib`, `web/profiles/contrib`, `settings.local.php`, `web/sites/*/files`, `node_modules` — **mais `web/themes/contrib` versionne** (`!web/themes/contrib`, donc Gin committe).

## 3. Modules contrib (require)
- **Contenu** : `paragraphs` (+ `entity_reference_revisions`), `field_group`
- **Media/images** : `crop`, `image_widget_crop` (crop BO par ratio), Media & Responsive Image (core)
- **Vidéo** : `video_embed_field` (YouTube/Vimeo/Dailymotion)
- **SEO** : `metatag`, `pathauto` (+ `token`), `redirect`, `simple_sitemap`
- **Formulaires / e-mails** : `webform`, `recaptcha_v3` (+ `recaptcha`, `captcha`), `honeypot`, `symfony_mailer`
- **Vues** : `better_exposed_filters` (Views core)
- **Fragments** : `rabbit_hole` (nodes sans page publique — notre ajout, absent de CFPA)
- **Liens** : `linkit` (interne autocomplete) + `link_target` (cible d'onglet) + `menu_link_attributes`
- **Fil d'Ariane / Twig** : `easy_breadcrumb`, `twig_tweak`
- **Durcissement** : `rename_admin_paths`
- **Admin** : `gin` (theme), `gin_toolbar`, `admin_toolbar`
- **Dev (require-dev)** : `drush/drush`, `devel`, `cl_devel`, `sdc_devel`
- **Écartés de CFPA** (hors périmètre) : `commerce`, `online-payments`, `search_api`

## 4. Interfaces publiques / conventions
- Theme machine name **`drive_matic`** ; modules custom prefixes **`drivematic_*`**.
- SDC : namespace de composants du theme ; CSS/Twig co-localises (decision #10).
- Assets attaches via `*.libraries.yml` (pas d'inline).

## 5. Securite
- **Aucun secret commite** : DB, hash salt, cle secrete reCAPTCHA → `settings.local.php` (gitignore) / variables d'env.
- `trusted_host_patterns`, **fichiers prives** hors docroot (`file_private_path`), `settings.php` durci.
- Base de compat : PHP 8.3 / MariaDB 10.11 (decision #1).

## 6. Risques et contraintes
- **MAMP** : l'install requiert MySQL/MariaDB MAMP **demarre** + une base creee. Web servi soit par vhost MAMP, soit rapidement par `drush runserver` (evite la config de vhost).
- `composer create-project`/`update` = telechargement volumineux (reseau, plusieurs minutes).
- **SDC + SCSS** : fondations globales compilees via `src/scss` ; CSS des composants co-localise (compilation par composant a cadrer a l'implementation).
- Workflow config : `drush cex`/`cim` ; ne pas committer de secrets via la config.
- Repo actuel : `vendor/` + `composer.lock` (coder seul) seront remplaces par le projet complet.

## 7. Coherence specs / PRD
Implemente les decisions **#1** (D11/PHP/MariaDB), **#2** (SDC/BEM/sans framework JS), **#7** (sous-theme Olivero), **#9** (Gin + toolbar horizontale), **#10** (SDC), **#11** (media/crop/WebP). Fournit les modules requis par ADR-001 (paragraphs) et ADR-002 (rabbit_hole, metatag, sitemap, BEF) et les webforms (F10/F11). Infra → pas de nouveau parcours E2E.

## 8. Etapes d'implementation (chunks verifiables)
1. **Composer → projet Drupal 11** : faire evoluer `composer.json` (core-recommended + scaffold + installers + composer-patches), conserver coder. *Verif : `composer install` OK, `web/` genere.*
2. **Requerir les modules** (section 3) + drush. *Verif : `composer` resout, `vendor/bin/drush` present.*
3. **settings.local.php** (MAMP DB) + `.gitignore` + fichiers prives + trusted hosts. *Verif : `drush status` lit la config.*
4. **Installation Drupal** (`drush site:install`) sur la BD MAMP (si MySQL accessible ; sinon commande fournie). *Verif : `drush status` = Connected/Installed, page servie.*
5. **Activer/configurer** : Gin (admin + toolbar horizontale), Admin Toolbar, Media/Crop, Paragraphs, Webform, Metatag, Pathauto, Redirect, Simple Sitemap, Rabbit Hole, reCAPTCHA v3 (cle site en config, secrete en env). `drush cex`. *Verif : config exportee propre, back-office Gin OK.*
6. **Theme `drive_matic`** : genere via `starterkit` puis rendu autonome (`base theme: false`), libraries, `breakpoints.yml`, structure SDC, `src/scss` (reset/tokens/typographie), `package.json` build. Activer comme theme par defaut. *Verif : `npm run css` compile, page front rendue, theme actif.*
7. **Config linter + doc** : phcs/eslint/stylelint/prettier paths + globals ; MAJ README (Structure/Architecture). *Verif : `npm run lint` + `npm run format:check` passent.*
8. **Commit**. *Verif : `git status` propre, arbo coherente.*

## 9. Tests / boucle de feedback
- **Rapide** : `composer install`, `drush status`, `drush cr`, navigateur (front + `/admin`), `npm run css`, `npm run lint`.
- **Verif manuelle** : back-office Gin avec **toolbar horizontale** ; theme `drive_matic` actif ; tokens/breakpoints presents ; un composant SDC de test rendu.
- **Cas d'erreur** : MySQL MAMP eteint (message clair, ne pas boucler) ; conflit de version module ; echec compilation SCSS ; secret oublie en config (verifier `config/sync` avant commit).

## 10. A confirmer avant execution
1. **MAMP demarre** + une **base de donnees** dediee (nom + acces) — ou je la cree via la CLI MySQL de MAMP si accessible.
2. Web local : **`drush runserver`** (simple, recommande pour demarrer) ou **vhost MAMP PRO** (`drive-matic.local`) ?
3. ✅ **Git** : gitignore `vendor`/core/contrib-modules, versionne `web/themes/contrib` (aligne sur ta convention WEBLEX).

## Statut
- [ ] Plan valide
- [ ] Scaffold Composer + modules
- [ ] Install Drupal (MAMP)
- [ ] Sous-theme drive_matic + build
- [ ] Config linter + doc
- [ ] Commit
