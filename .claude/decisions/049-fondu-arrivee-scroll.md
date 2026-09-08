# ADR-049 : Fondu discret a l'arrivee au scroll (image_text_50/100, grid)

## Statut
Accepte

## Date
2026-09-09

## Contexte
Demande de l'utilisatrice : un fondu discret et sobre a l'arrivee au scroll sur
`image_text_50`/`image_text_100`, puis le meme traitement sur `grid`
(`grid_element`) avec en plus une arrivee verticale legerement decalee entre
cartes. Aucun mecanisme de reveal au scroll n'existait dans le theme
(`IntersectionObserver`, `prefers-reduced-motion` : zero occurrence avant
cette session).

## Options considerees

### Option A : comportement partage, opt-in par data-attribut
- Avantages : un seul point d'entretien (`js/reveal.js`), reutilisable par
  tout futur composant sans dupliquer la logique d'observation ; suit
  exactement le pattern deja etabli par `slideshow.js`/`video-facade.js`
  (IIFE, `Drupal.behaviors` + `once`, ciblage par `[data-dm-reveal]`
  agnostique du BEM).
- Inconvenients : aucun, le pattern est deja valide par 2 comportements
  existants.

### Option B : logique dupliquee dans chaque SDC consommateur
- Avantages : zero dependance croisee entre composants.
- Inconvenients : duplique l'observation/le debounce dans chaque SDC,
  contraire a la philosophie du projet pour un comportement generique
  (cf. `slideshow`/`video-facade`).

## Decision
**Option A.** Nouvelle librairie `drive_matic/reveal` (`js/reveal.js`),
attachee via `libraryOverrides` uniquement aux SDC qui la consomment
(`image-text-50`, `image-text-100`, `grid-element`) :

- `[data-dm-reveal]` sur l'element a reveler (racine pour `grid-element` ;
  `__media` et `__content` **separement** pour `image-text-50/100`, afin
  qu'ils revelent independamment plutot qu'en un seul bloc).
- Un `IntersectionObserver` par element (seuil 15%), pas d'unobserve : la
  classe `.is-visible` est **basculee** selon `entry.isIntersecting`, donc le
  fondu se rejoue a chaque passage dans le viewport (demande explicite,
  reprise apres un premier jet qui ne revelait qu'une fois).
- Amelioration progressive stricte : l'etat masque (`opacity: 0`,
  `transform: translateY(...)`) n'existe que sous `:where(html.js)` ; sans
  JS, tout reste visible. `prefers-reduced-motion: reduce` neutralise
  entierement l'effet (opacite 1, transform none, transition none).
- **Fondu (opacite) simultane, arrivee verticale (transform) decalee** —
  distinction demandee explicitement apres un premier jet qui decalait les
  deux ensemble (`transition-delay` sur le raccourci `transition`
  reinitialise implicitement TOUTES les sous-proprietes, y compris le delai,
  pour chaque entree de la liste). Resolu en isolant le delai du `transform`
  dans une custom property (`--dm-grid-reveal-delay`, defaut `0ms` sur la
  regle de base), que `grid.scss` seul surcharge selon la position de la
  carte (`.field__item:nth-child(N) ...`) — `opacity` garde son propre
  `transition-delay: 0s` implicite, jamais touche.

## Consequences
- Fichiers : `js/reveal.js`, lib `reveal` (`drive_matic.libraries.yml`, sans
  `version: VERSION` — cf. addendum du meme jour dans ce fichier au sujet du
  cache-busting), `libraryOverrides` sur les 3 `.component.yml` concernes,
  CSS dans chaque SDC (pas de fondation globale, seuls 3 consommateurs a ce
  jour).
- Reutilisable tel quel par un futur paragraphe qui voudrait le meme effet :
  poser `data-dm-reveal` + le CSS de fondu (opacite/transform) dans son
  propre SDC, aucune modification de `reveal.js`.
- Parametres retenus apres plusieurs allers-retours : transition 1.6s,
  translateY 48px (grid) / 16px (image_text_50/100), pas de decalage entre
  cartes 250ms (uniquement sur le `transform`).
- **Limite de verification connue** : le navigateur integre de
  developpement (Browser MCP) se signale en permanence `document.hidden:
  true`, ce qui gele `requestAnimationFrame`, les callbacks
  `IntersectionObserver` et l'avancement des transitions CSS — impossible d'y
  observer visuellement l'effet jouer. Verifie a la place par introspection
  DOM/CSSOM (selecteurs matches, specificite, valeurs `transition-delay`
  distinctes par position) ; la confirmation visuelle finale reste a faire
  par l'utilisatrice sur un navigateur reel.
