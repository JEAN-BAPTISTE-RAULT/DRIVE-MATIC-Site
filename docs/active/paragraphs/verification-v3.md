# Verification — Paragraphes SDC · Vague V3 (Grille & éditorial)

> Trace d'audit fin d'implémentation. Plan : `docs/plans/paragraphs-sdc.md`. Décisions : ADR-001, ADR-007 (amendé : `triptych` plafonné).

## Commandes executees

| Commande | Resultat | Notes |
|---|---|---|
| `npm run lint` | ✅ rc=0 | ESLint (`slideshow.js`, `video-facade.js`) + Stylelint (6 SDC) + PHPCS 120/120 |
| `npm run format:check` | ✅ rc=0 | Prettier (CSS généré SDC + `*.min.*` Swiper exclus) |
| `drush config:status` | ✅ propre | canonicalisé via `drush cim` puis `drush cex` (text_long → `allowed_formats`) |
| `npm test` | ⚠ placeholder | stratégie de test non définie (PRD) |
| Rendu navigateur (`drush runserver` + nodes 12–16) | ✅ | grid, triptych, history (image/vidéo, single) inspectés au DOM |

## Changements comportementaux

6 nouveaux types de paragraphe (3 paires Bloc/Élément), tous éditoriaux publics :

- **`grid` / `grid_element`** : grille responsive de cartes (image 16:9 + titre opt + **liens multiples**). Nouveau storage `field_links` (link, card -1). Réutilise `field_elements` (ADR-007).
- **`triptych` / `triptych_element`** : jusqu'à **3** blocs chiffre/accroche. Bloc **plafonné** → storage dédié `field_triptych_elements` (card 3, ADR-007 amendé). Élément = 3 textes scalaires (`field_text_top`, `field_title` en gras, `field_text_bottom`) rendus en **props**.
- **`history` / `history_element`** : frise en **slideshow Swiper** (si >1, sinon repli liste). Élément = image 16:9 **OU** vidéo (façade), + titre/description/légende.

Fondations introduites : **Swiper** vendorisé (`vendor/swiper/`, lib slideshow universelle du site) ; **façade vidéo mutualisée** (`drive_matic/video-facade`, JS générique data-attributs) — `video_centered` migré dessus.

## Risques identifies et mitigations

- **Wrapper de champ Drupal casse les layouts grille/flex** (`field__items` > `field__item`) → `display: contents` sur le wrapper (`&__items > * { display: contents }`) ; aucune classe `.field__*` en SCSS (rejetée par stylelint).
- **Cardinalité plafonnée ingérable sur storage partagé** (`field_elements` illimité) → storage dédié `field_triptych_elements` card 3 (la cardinalité vit sur le storage, ADR-007).
- **Exclusivité image/vidéo** (`history_element`) → pas de contrainte custom : `field_image` toujours requise (le visuel/miniature), `field_video` optionnelle ; sa présence bascule le rendu en façade (`block('video') is not empty`).
- **Slideshow inaccessible sans JS** → repli progressif : sous `html.no-js`, `overflow: visible` + `.swiper-wrapper { display: block }` (empilement lisible) ; flèches affichées seulement quand Swiper a démarré (`&__viewport.swiper-initialized`).
- **Chargement tiers vidéo avant interaction (RGPD/perf)** → façade : iframe dans `<template>` inerte, injectée au clic uniquement (allowlist providers `youtube`/`vimeo` re-vérifiée serveur).
- **Collision de behaviors** en mutualisant la façade → un seul `Drupal.behaviors.driveMaticVideoFacade` piloté par data-attributs (`video_centered` migré, re-vérifié sans régression).

## Edge cases testes

- **Grid responsive** : 1 col mobile, 2 col ≥576px, 3 col ≥992px ; liens multiples (interne+externe) 2/carte ; WebP responsive 16:9 généré. ✅
- **Triptych** : cardinalité storage = 3 (plafond) ; 3 col ≥768px, empilé mobile ; `text_top` vide → omis (pas d'espace cassé). ✅
- **History slideshow >1** : Swiper initialisé (3 slides), nav 0→1, flèches visibles. ✅
- **History single** : Swiper **non** initialisé, flèches masquées (`display:none`), élément visible (repli liste). ✅
- **History vidéo** : slide vidéo → façade ; clic → iframe injectée (aucune avant), `<template>` retiré. Slide image → image simple. ✅
- **Non-régression `video_centered`** : façade partagée OK (iframe injectée au clic). ✅

## Self-review

1. **Decision la plus difficile** : l'intégration Swiper avec le markup de champ Drupal. Utiliser les classes custom de Swiper obligeait à cibler `.field__item` en SCSS (interdit par stylelint) → choix de produire de vrais `.swiper-slide` en bouclant le render array (`|filter`, Twig 3), + repli `no-js` scopé.
2. **Alternatives rejetees** : contrainte de validation serveur pour l'exclusivité image/vidéo (rejetée → logique portée par la présence du champ vidéo, plus simple) ; storage `field_elements` partagé pour `triptych` (impossible, cardinalité illimitée → storage dédié) ; garder 2 façades JS dupliquées (rejeté → collision de behaviors → extraction en lib partagée + migration `video_centered`) ; `history` statique sans Swiper (rejeté → Swiper acté comme lib slideshow universelle).
3. **Point de moindre confiance** : l'esthétique fine (position des flèches, tailles pastille vidéo, gouttières) — maquettes Figma non ouvertes, à caler à l'intégration visuelle. Le ratio 16:9 n'a pas pu être vérifié visuellement (donnée de test sans crop ; le widget média l'impose en usage réel). Métadonnées de cache du champ possiblement allégées par le rendu élément par élément (`|filter`) — acceptable pour du contenu éditorial statique.
