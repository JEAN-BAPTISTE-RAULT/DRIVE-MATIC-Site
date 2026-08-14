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
- **Options par diapositive, via data-attributs (integration maquette `jumbo_home`, 2026-08-14)** : plutot que dupliquer `slideshow.js` par composant ou passer par `drupalSettings`, le behavior lit ses options sur l'element `[data-dm-slideshow]` — `data-dm-slideshow-per-view` (nombre ou `auto`, defaut `1`) et `data-dm-slideshow-space` (px, defaut `24`). La **pagination** s'active en presence d'un `[data-dm-slideshow-pagination]` **dans le conteneur du composant** (frere de `.swiper`, resolu via `el.parentElement`), sinon le module reste desactive. Le behavior reste unique et les SDC restent declaratifs ; aucun couplage JS par composant.
- **Fleches hors de la piste (integration maquette `history`, 2026-08-14)** : la maquette place la paire de fleches dans l'**en-tete du bloc**, hors du `.swiper`. Le behavior resout donc `[data-dm-slideshow-prev/next]` sur le **conteneur du composant** (`el.parentElement`), comme deja fait pour la pagination — et non sur le `.swiper`. Un `scope.querySelector` retrouve aussi les fleches imbriquees **dans** la piste (`jumbo_home` inchange). Limite acceptee : deux slideshows freres dans un meme conteneur se disputeraient les memes fleches ; un composant = un slideshow.
- **Etat « demarre » quand les fleches sont hors de la piste** : `.swiper-initialized` est pose sur le `.swiper`, qui est un frere **suivant** de l'en-tete → pas de selecteur de frere possible. On gate donc avec `:has()` (`&:has(&__viewport.swiper-initialized) &__nav`). Degradation acceptable sur un navigateur sans `:has()` : les fleches restent masquees (le glisser et le clavier fonctionnent).
- **⚠️ Piege de specificite `.swiper` (decouvert sur `history`, 2026-08-14 — corrige aussi `jumbo_home`)** : le bundle pose `.swiper { margin-left: auto; margin-right: auto }` a la **meme specificite (0,1,0)** qu'une classe BEM, et il est agrege **apres** le CSS des SDC → un `&__viewport { margin-right: calc(50% - 50vw) }` (piste debordant a droite, aperçu coupe de la diapo suivante) est **silencieusement ecrase** : la piste s'arrete au bord du conteneur et l'aperçu, bien que present, semble « juste un peu court ». C'est indetectable a l'oeil sans mesurer, et `jumbo_home` a ete livre ainsi. Regle : **toute** propriete que le bundle declare sur `.swiper` (`margin-*`, `overflow`) doit etre reprise en repetant la classe — `&__viewport.swiper { … }` — y compris dans les replis `:where(html.no-js)`.
- **Pieges CSS du bundle (integration maquette)** : le bundle impose deux regles plus specifiques que du BEM simple. (1) `.swiper-pagination-horizontal` force `position: absolute` en (0,2,0) — si le conteneur de pagination est hors d'un ancetre positionne, les points se detachent et flottent ailleurs dans la page ; repasser en flux en repetant la classe (`&__pagination.swiper-pagination`). (2) chaque puce recoit `margin: 0 4px` via une regle imbriquee en (0,3,0), qui s'ajoute silencieusement a un `gap` flex ; l'annuler par sa propre variable `--swiper-pagination-bullet-horizontal-gap: 0` plutot qu'en surencherissant sur la specificite (robuste aux montees de version).
- **Contrainte enfant direct (V4)** : Swiper localise `.swiper-wrapper` en **enfant DIRECT** du conteneur ; un wrapper intercalé casse l'init (`getComputedStyle(null)`, slideshow non initialisé). Conséquence pour un **slideshow alimenté par une Vue** (`news_home`/`brands_home`) : ne pas embarquer la Vue via `drupal_view()` (ajoute `.view` + `.views-element-container` non désactivable du core) mais rendre les diapositives via **`drupal_view_result()` + `drupal_entity(node, id, 'card')`** dans le template paragraphe, le SDC fournissant `.swiper-wrapper` directement. `drupal_view_result()` ne fait pas remonter les list cache tags → réattacher `node_list:<bundle>` via `drive_matic_preprocess_paragraph` (`drive_matic.theme`). Cf. mémoire `swiper-view-slideshow`.
