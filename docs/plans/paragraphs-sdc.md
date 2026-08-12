# Plan — Paragraphes SDC

> Implémente la bibliothèque validée de **27 paragraphes** (ADR-001) en types Paragraph + SDC, responsive (pipeline images ADR-004) et accessibles (RGAA AA). Grosse brique → **livrée par vagues committables**.

## Décisions
- **Slideshows = Swiper** (vendorisé dans le thème, module a11y activé, `Drupal.behaviors` + `once`, `prefers-reduced-motion`).
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
- **V2 — Accordéon** : `accordion` + `accordion_element` + JS disclosure.
- **V3 — Grille & éditorial** : `grid`(+element), `triptych`(+element), `history`(+element).
- **V4 — Home** : `jumbo_home`(+element), `news_home`, `brands_home` (vues + Swiper).
- **V5 — Produit** : `product_arguments`, `product_features`(+image/video elements), `product_characteristics`(+element), `product_cross`(+element).

## 8. Tests / feedback
Par vague : créer une page, placer les paragraphes, vérifier en **navigateur** (markup SDC, CSS, JS accordéon/carousel au clavier, images responsive WebP), `npm run lint`, `config:status` clean.

## Statut
- [x] Plan validé (Swiper, crop requis)
- [x] V0 — Socle (build SCSS SDC, type page hôte, text_centered validé bout-en-bout)
- [x] V1 — Textes & médias (text_left_aligned, image_text_50, image_text_100, image_full, image_centered, video_centered — validés navigateur : responsive WebP par ratio, options 50/50, façade vidéo au clic)
- [ ] V2 — Accordéon
- [ ] V3 — Grille & éditorial
- [ ] V4 — Home
- [ ] V5 — Produit
