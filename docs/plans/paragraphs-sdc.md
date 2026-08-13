# Plan — Paragraphes SDC

> Implémente la bibliothèque validée de **27 paragraphes** (ADR-001) en types Paragraph + SDC, responsive (pipeline images ADR-004) et accessibles (RGAA AA). Grosse brique → **livrée par vagues committables**.

## Décisions
- **Slideshows = Swiper** (vendorisé dans le thème, module a11y activé, `Drupal.behaviors` + `once`, `prefers-reduced-motion`). **Vendorisation effective en V3** (premier consommateur : `history`) comme **librairie slideshow universelle du site** ; les carousels V4 (`jumbo_home`/`news_home`/`brands_home`) la réutilisent. Chargée uniquement où utile via `libraryOverrides`.
- **Crop requis par ratio** : le widget média impose le recadrage aux crops `1:1 / 16:9 / 12:5` → ratio toujours respecté.
- **Vidéo = `video_embed_field` (contrib) + façade** (V1) : le lien embed est un champ `video_embed_field` avec **allowlist providers** (`youtube`/`vimeo`, contrôle serveur re-vérifié à la validation), distinct de la miniature (média image 16:9). Rendu en **façade** : l'iframe est placée dans un `<template>` inerte et injectée seulement au clic (behavior `driveMaticVideoFacade` + `once`, bouton accessible clavier) → aucun chargement tiers avant interaction (perf + RGPD). NB : `allowed_providers` se stocke en **map** (`youtube: youtube`), pas en liste.
- **Options d'affichage = champs `list_string`** (V1) : `image_text_50` porte `field_text_position` (`left`/`right`, inversion via `flex-direction: row-reverse`) et `field_background` (`white`/`grey`), mappés en props du SDC. Plus simple qu'un plugin de comportement Paragraphs pour 2 options binaires.
- **Liaison prop/slot** : champs scalaires (titre, légende, options) passés en **props** (`paragraph.field_x.value`, masqués dans le view display) ; champs renderable (description, lien, fichier, image, vidéo) injectés en **slots** via leurs formatters (`content.field_x`). L'image = média + mode d'affichage par ratio (`ratio_1_1`/`ratio_16_9`/`ratio_12_5`/`free`).

## 1. Architecture
- **Config** : `paragraphs.paragraphs_type.*` (×27) + champs (`field.storage`/`field.field`) + form/view displays.
- **SDC** : `web/themes/custom/drive_matic/components/<nom>/` → `<nom>.component.yml` + `.twig` + `.scss`(→`.css`) + `.js` si interactif.
- **Liaison** : `paragraph--<type>.html.twig` mappe les champs → props/slots du SDC.
- **Sous-SDC réutilisables** : `cta`, `media` (image responsive + ratio via mode d'affichage), `link` (interne/externe + cible), `file-download` (nom/format/poids), `accordion-item`, `slide`.
- **JS** (vanilla) : `accordion` (disclosure ARIA, fermeture du précédent), `slideshow` (wrapper Swiper).
- **Hôte de test** : type de contenu **`page`** composable minimal (champ paragraphes) — pont vers la brique content-types (ADR-002).

## 2. Conventions réutilisées (déjà en place)
Liens `linkit`+`link_target` ; fichier téléchargeable (nom/format/poids) ; **image = média + ratio via mode d'affichage** (`free`/`ratio_1_1`/`ratio_16_9`/`ratio_12_5`) ; Éléments imbriqués via `entity_reference_revisions` ; vidéo = thumbnail média (16:9) + embed (`video_embed_field`, allowlist providers).

## 3. Build SCSS des SDC
Extension du build sass : compilation de `components/**/*.scss` → `.css` co-localisé (les fondations restent en `style.css`). Scripts npm `css` mis à jour.

## 4. Sécurité
Twig autoescape (jamais `|raw` hors texte CKEditor filtré) ; allowlist providers vidéo ; validation des URLs ; médias publics (pas de donnée partenaire) ; **list cache tags** sur `news_home`/`brands_home` (vues).

## 5. Risques / contraintes
Volume (27) → vagues ; accessibilité interactive (clavier/ARIA/reduced-motion) ; `sizes` responsive affiné par SDC ; Swiper vendorisé (poids à surveiller, chargé seulement où utile).

## 6. Cohérence
ADR-001, décisions #10 (SDC) / #11 (images), breakpoints. Sous-tend E2E S2–S7. Met à jour `crop_types_required` (suivi ADR-004).

## 7. Vagues (chaque vague = committable + testée navigateur)
- **V0 — Socle** : build SCSS composants ; Swiper + a11y ; pattern paragraph↔SDC ; sous-SDC (`cta`/`media`/`link`/`file-download`) ; type `page` hôte ; crop requis. Validé bout-en-bout avec **`text_centered`**.
- **V1 — Textes & médias** : text_centered, text_left_aligned, image_text_50, image_text_100, image_full, image_centered, video_centered.
- **V2 — Accordéon** : `accordion` + `accordion_element` + JS disclosure. Première paire **Bloc/Élément** : storage partagé `field_elements` (ERR illimité, [ADR-007](../../.claude/decisions/007-storage-partage-elements.md)). Premier **behavior JS** du thème + fondation `no-js`/`js` (lib `drive_matic/js-detect` en `<head>`) pour un « fermé par défaut » sans flash et une amélioration progressive.
- **V3 — Grille & éditorial** : `grid`(+element), `triptych`(+element), `history`(+element). Décisions actées :
  - **`grid`** réutilise `field_elements` (ERR illimité, ADR-007) ; **`grid_element`** = titre (opt, `field_title`) + **liens multiples** via nouveau storage dédié **`field_links` (link, card -1)** + image 16:9 (`field_image`, view mode `ratio_16_9`).
  - **`triptych`** = **plafonné à 3** → nouveau storage dédié **`field_triptych_elements` (ERR, card 3)** + **amendement ADR-007** (triptych ajouté aux blocs plafonnés) ; **`triptych_element`** = texte gras obl (`field_title`) + 2 nouveaux storages string **`field_text_top`/`field_text_bottom`** (opt).
  - **`history`** réutilise `field_elements` (ERR illimité) + titre obl ; **slideshow Swiper si > 1**, dégrade en liste sinon. **`history_element`** = titre obl (`field_title`) + description opt (`field_description`) + légende opt (`field_caption`) + **image 16:9 OU vidéo (thumbnail 16:9 + embed) exclusif** : `field_image`/`field_video` optionnels, **précédence vidéo au rendu** (façade réutilisée de `video_centered`), durcissement par contrainte serveur = dette optionnelle notée.
- **V4 — Home** : `jumbo_home`(+element), `news_home`, `brands_home` (vues + Swiper). Décisions actées :
  - **Prérequis** : tranche minimale de content-types (ADR-002) créée d'abord — nodes `news` (image 16:9) et `brand` (fragment sans crop, canonique bloquée Rabbit Hole, hors sitemap) + Vues `news_home` (5 récentes, tri `changed` desc) / `brands_home` (alpha). Cf. `docs/plans/content-types-v4-home.md`.
  - **`jumbo_home`** = plafonné 3 → storage dédié **`field_jumbo_elements`** (ERR card 3, ADR-007) ; **`jumbo_home_element`** = titre (obl) + lien CTA (opt) + image 16:9 (obl). Slideshow réutilisant `drive_matic/slideshow`.
  - **`news_home` / `brands_home`** = titre (obl, prop) + lien « voir tout » (obl, slot) + slideshow d'entités rendues en **mode d'affichage `card`** (SDC `news-card` / `brand-logo` via `node--news--card` / `node--brand--card`). Les marques ne sont **pas** liées (canonique bloquée).
  - **Contrainte Swiper** : Swiper localise `.swiper-wrapper` en **enfant DIRECT** du conteneur. Une Vue embarquée (`drupal_view`) ajoute des wrappers (`.view`, `.views-element-container`) qui cassent l'init. Solution : rendre les diapositives via **`drupal_view_result()` + `drupal_entity(..., 'card')`** dans le template paragraphe, le SDC fournissant `.swiper-wrapper` directement. Le tag de cache de liste (`node_list:news` / `node_list:brand`) est réattaché par `drive_matic_preprocess_paragraph` (perdu par `drupal_view_result`).
- **V5 — Produit** : `product_arguments`, `product_features`(+image/video elements), `product_characteristics`(+element), `product_cross`(+element).

## 8. Tests / feedback
Par vague : créer une page, placer les paragraphes, vérifier en **navigateur** (markup SDC, CSS, JS accordéon/carousel au clavier, images responsive WebP), `npm run lint`, `config:status` clean.

## Statut
- [x] Plan validé (Swiper, crop requis)
- [x] V0 — Socle (build SCSS SDC, type page hôte, text_centered validé bout-en-bout)
- [x] V1 — Textes & médias (text_left_aligned, image_text_50, image_text_100, image_full, image_centered, video_centered — validés navigateur : responsive WebP par ratio, options 50/50, façade vidéo au clic)
- [x] V2 — Accordéon (`accordion` + `accordion_element` ; storage partagé `field_elements` / ADR-007 ; behavior `driveMaticAccordion` + fondation `js-detect` ; validé navigateur : fermé par défaut sans flash, fermeture du précédent, ARIA disclosure, clavier, amélioration progressive)
- [x] V3 — Grille & éditorial (`grid`+`grid_element` ; `triptych`+`triptych_element` storage plafonné card 3 ; `history`+`history_element` slideshow **Swiper vendorisé** + façade vidéo partagée `drive_matic/video-facade`. Validés navigateur : grille responsive 1/2/3 col, triptyque 3 col plafonné, slideshow Swiper si >1 / repli liste + nav masquée si 1, exclusivité image/vidéo, façade au clic, `video_centered` non-régressé)
- [x] V4 — Home (`jumbo_home`+element ; `news_home`/`brands_home` = Vues rendues en slideshow via `drupal_view_result`+`drupal_entity` mode `card`, SDC `news-card`/`brand-logo`). Prérequis content-types `news`/`brand` + Vues créés (cf. `content-types-v4-home.md`). Validé navigateur : 3 slideshows Swiper OK (2/5/4 diapos, flèches, repli à 1 sans slideshow), marques non liées, actu 16:9 WebP responsive, fragment `brand` en 403.
- [x] V5 — Produit (`product_arguments` [3 titres, ADR-001] ; `product_features` slideshow image/vidéo [storage plafonné card 5] ; `product_characteristics` image `free` + notice/doc [2 storages fichiers dédiés] ; `product_cross` grille de cartes liées [storage plafonné card 5] ; réutilise `slideshow` + `video-facade`). Validé navigateur : slideshow >1 / repli à 1, façade au clic (0 iframe avant), image sans crop `dm_free_*`, 2 caractéristiques + 2 fichiers, cross lié. **Bibliothèque ADR-001 complète (27 paragraphes).**
