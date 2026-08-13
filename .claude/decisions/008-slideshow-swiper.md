# ADR-008 : Slideshow — Swiper vendorisé (librairie unique du site)

## Statut
Accepte

## Date
2026-08-13

## Contexte
Plusieurs paragraphes de la bibliothèque (ADR-001) sont des **slideshows** : `history` (V3), puis `jumbo_home`, `news_home`, `brands_home` (V4, home) et le bloc « swipe » produit `product_features` (V5). Le carrousel marques (F3/F7) et les jumbos (F3) l'exigent aussi. Besoins : navigation clavier, ARIA, `prefers-reduced-motion` (RGAA/WCAG AA, décision #8) ; dégradation en liste si un seul item ; **self-contained** (pas de CDN — RGPD, cohérent avec l'auto-hébergement des polices) ; stack **vanilla JS** sans build/transpile (décision #2).

`history` (V3) est le **premier** consommateur : il force le choix.

## Options considerees

### Option A : slideshow maison (vanilla JS + CSS scroll-snap)
- Avantages : zéro dépendance, poids minimal.
- Inconvenients : réimplémenter l'accessibilité (focus, ARIA live, clavier), le drag tactile, la boucle, la pagination = coût élevé et risque a11y ; à refaire/maintenir pour ~5 composants.

### Option B : Swiper (vendorisé dans le thème)
- Avantages : module **a11y** inclus, clavier/tactile/navigation éprouvés, API simple pour `Drupal.behaviors` ; bundle navigateur exposant un global `Swiper` (pas de build) ; une seule lib pour tous les slideshows du site.
- Inconvenients : poids (~150 Ko JS min) ; dépendance tierce à vendoriser et suivre.

## Decision
**Option B — Swiper, vendorisé, comme librairie slideshow unique du site.**
- Fichiers `swiper-bundle.min.{js,css}` (paquet npm `swiper`, **copie manuelle**) dans `web/themes/custom/drive_matic/vendor/swiper/` — **self-contained, aucun CDN**. Le bundle expose le global `Swiper` (+ tous les modules, dont a11y) ; `Swiper` déclaré dans les globals ESLint.
- Library `drive_matic/swiper` (assets vendor) + `drive_matic/slideshow` (behavior `driveMaticSlideshow`, `once`) qui **initialise Swiper uniquement si ≥ 2 diapositives** `.swiper-slide` (sinon repli liste) ; `prefers-reduced-motion` → `speed: 0`. Chargée seulement où utile via `libraryOverrides` du SDC concerné.
- **Amélioration progressive** : sans JS (fondation `no-js`/`js`), la piste Swiper s'empile (`overflow: visible` + `.swiper-wrapper { display: block }`) et reste entièrement lisible ; les flèches n'apparaissent que lorsque Swiper a démarré (`.swiper-initialized`).
- Markup : chaque Élément est enveloppé dans un `.swiper-slide` (boucle Twig `|filter` sur le render array — `for…if` supprimé en Twig 3). On produit toujours de vrais `.swiper-slide` (ne pas cibler `.field__item` en SCSS, rejeté par Stylelint).

## Consequences
- Un seul point de mise à jour Swiper (re-copie depuis `node_modules` après bump npm). Reproductible via `npm install`.
- Réutilisable tel quel par V4 (jumbo/news/brands) et V5 (product_features).
- Poids maîtrisé : lib chargée uniquement sur les pages portant un slideshow.
- Fichiers : `vendor/swiper/`, `js/slideshow.js`, libs `swiper`/`slideshow` (`drive_matic.libraries.yml`), global `Swiper` (`eslint.config.mjs`), SDC `components/history/` (`libraryOverrides: drive_matic/slideshow`).
