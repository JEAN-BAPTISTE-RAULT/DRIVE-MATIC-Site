# Verification — V5 Produit (paragraphes)

> Trace d'audit. Plan : `docs/plans/paragraphs-v5-produit.md` (vague V5 de
> `docs/plans/paragraphs-sdc.md`). Clôt la bibliothèque ADR-001 (27 paragraphes).

## Commandes exécutées

| Commande | Résultat | Notes |
|---|---|---|
| `drush cim` / `cex` (×2) | ✅ importé + canonicalisé | 8 types, 5 storages, instances + displays |
| `drush config:status` | ✅ « No differences » | DB == sync |
| `npm run lint` (ESLint + Stylelint + PHPCS) | ✅ rc=0 | 2 erreurs stylelint corrigées (nav features alignée sur le pattern jumbo) |
| `npm run format:check` | ✅ clean | — |
| `npm run css:components` + `css:prefix` | ✅ | 8 nouveaux CSS co-localisés compilés |
| Vérif navigateur (drush runserver 8096, node/29 + node/30) | ✅ | cf. edge cases |

## Changements comportementaux

- **8 nouveaux paragraphes** clôturant la bibliothèque : blocs `product_arguments`,
  `product_features`, `product_characteristics`, `product_cross` + éléments
  `product_image_element`, `product_video_element`, `product_characteristic_element`,
  `product_cross_element` (éléments exclus du placement direct).
- **5 nouveaux storages** : `field_arguments` (string, card 3), `field_features_elements`
  (ERR, card 5), `field_cross_elements` (ERR, card 5), `field_file_notice` + `field_file_doc`
  (file, card 1). `product_characteristics` réutilise `field_elements` (ERR illimité).
- **8 SDC** + 8 templates de liaison. Réutilisation : `drive_matic/slideshow`
  (`product_features`), `drive_matic/video-facade` (`product_video_element`),
  view mode `free` (image sans crop de `product_characteristics`), formatter fichier
  partagé (nom/format via `drive_matic/file`).
- Les 4 blocs ajoutés au `target_bundles` de `node.page.field_paragraphs`.

## Décisions

- **`product_arguments`** = 1 à 3 **titres** seuls (biblio validée ADR-001 #14),
  arbitré contre F5 (« image/texte ») par l'utilisatrice. Storage dédié `field_arguments`.
- **Fichiers `product_characteristics`** : 2 storages dédiés (`field_file_notice`,
  `field_file_doc`) plutôt que d'élargir la cardinalité de `field_file` (partagé, card 1).
- **ADR-007 amendé** : `field_features_elements` (5) et `field_cross_elements` (5)
  ajoutés aux blocs plafonnés à storage dédié.

## Edge cases testés (navigateur)

- `product_arguments` **3 titres** → grille 3 colonnes. ✅
- `product_features` **2 éléments** (image + vidéo) → **Swiper initialisé**, flèches
  visibles (`nav display:block`), 2 `.swiper-slide`. ✅
- `product_features` **1 élément** (node/30) → **pas** de Swiper (`swiper-initialized`
  absent), flèches masquées (`display:none`) — repli progressif. ✅
- `product_video_element` → **façade** : embed dans `<template>` inerte, **0 iframe**
  tierce avant clic ; **1 iframe** `youtube.com` injectée au clic (RGPD). ✅
- `product_characteristics` → image **`dm_free_*`** (sans crop) WebP responsive ;
  éléments 16:9 en **`dm_16_9_*`** ; **2** caractéristiques ; **2** fichiers (nom/format). ✅
- `product_cross` → **2** cartes liées (image 16:9 + titre + lien « Voir la fiche »). ✅
- **0 erreur console** ; `html.js` actif (js-detect). ✅

## Self-review

1. **Décision la plus difficile** : l'écart biblio ADR-001 (« 3 titres ») vs PRD F5
   (« image/texte ») pour `product_arguments` — levé par arbitrage utilisatrice avant code.
2. **Alternatives rejetées** : élargir `field_file` à card 2 pour notice+doc (aurait
   desserré tous les autres paragraphes → 2 storages dédiés) ; requalifier
   `product_arguments` en paire Bloc/Élément (s'écartait de la biblio verrouillée) ;
   `html.js .nav` en sélecteur plat (violait le BEM stylelint → pattern `&__viewport.swiper-initialized &__nav` de jumbo).
3. **Point de moindre confiance** : le rendu « poids » du fichier téléchargeable — non
   visible dans le markup actuel (formatter/lib partagés `drive_matic/file`), **identique
   à tous les paragraphes existants** ; à traiter globalement (hors V5) si le poids doit
   apparaître. Rendu visuel encore sur thème minimal (pas de comparaison maquette pixel).
