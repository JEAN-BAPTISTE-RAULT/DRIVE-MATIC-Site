# Plan — Paragraphes V5 (Produit)

> Dernière vague de la bibliothèque de paragraphes (ADR-001), clôt le plan
> `paragraphs-sdc.md`. Réutilise les acquis V0–V4 (slideshow Swiper, façade vidéo,
> file-download nom/format/poids, view mode `free`, pattern paragraph↔SDC).

## 1. Intention
Compléter F1 avec les 8 derniers modèles « produit » pour composer les fiches
produit (F5) sans CSS.

## 2. Périmètre (8 types)
**Blocs placeables** :
- `product_arguments` — max 3 « titres » (min 1), sans média ni élément.
  Décision : biblio validée (ADR-001 #14) prime sur F5 « image/texte » → 3 titres.
- `product_features` — max 5 × (`product_image_element` **ou** `product_video_element`),
  **slideshow** Swiper si > 1.
- `product_characteristics` — image sans crop (obl) + légende/titre (opt) +
  N × `product_characteristic_element` (illimité) + notice technique + documentation
  (2 fichiers téléchargeables).
- `product_cross` — titre (obl) + max 5 × `product_cross_element` (grille de cartes liées).

**Éléments imbriqués** (exclus du placement direct) :
- `product_image_element` — image 16:9 (obl) + légende/titre/description/lien/fichier (opt).
- `product_video_element` — thumbnail 16:9 (obl) + embed (obl) + légende/titre/description/lien/fichier (opt), rendu **façade**.
- `product_characteristic_element` — titre (obl) + description (obl).
- `product_cross_element` — titre (obl) + lien (obl) + image 16:9 (obl).

## 3. Config
**Storages réutilisés** : `field_title`, `field_caption`, `field_description`,
`field_link`, `field_file`, `field_image`, `field_video`, `field_elements` (ERR
illimité pour `product_characteristics`).

**Nouveaux storages** (5) :
- `field_arguments` — string, **card 3** (`product_arguments`).
- `field_features_elements` — ERR, **card 5**, cible `product_image_element` + `product_video_element`.
- `field_cross_elements` — ERR, **card 5**, cible `product_cross_element`.
- `field_file_notice` + `field_file_doc` — file, card 1 (`product_characteristics`) —
  2 storages dédiés (ne pas élargir `field_file` partagé).

**Hôte** : ajouter les **4 Blocs** au `target_bundles` de `node.page.field_paragraphs`.
Les 4 Éléments restent exclus.

Amende ADR-007 : `field_features_elements` (5) et `field_cross_elements` (5) ajoutés
aux blocs plafonnés ; `product_characteristics` réutilise `field_elements` (illimité).

## 4. Sécurité
Paragraphes éditoriaux publics, aucune donnée partenaire. Autoescape Twig ;
allowlist providers vidéo + façade (RGPD) ; fichiers = formatter standard.

## 5. Réutilisation
`drive_matic/slideshow` (features), `drive_matic/video-facade` (video_element,
précédence vidéo au rendu comme `history-element`), view mode `free` (sans crop),
pattern `{% block download %}{{ content.field_file }}{% endblock %}`.
Aucune nouvelle interface JS publique → config linter inchangée.

## 6. Étapes (chunks committables)
1. Storages + 4 Éléments (+ form/view displays) → `drush cim`, `config:status` clean.
2. 4 Blocs + instances + displays + ajout hôte `page` → clean.
3. Templates `paragraph--*` + 8 SDC (SCSS→CSS) → `npm run lint`, `format:check`.
4. Vérif navigateur (page de test 4 blocs) : SDC, WebP responsive 16:9, slideshow
   clavier, façade au clic, 2 téléchargements nom/format/poids, cross lié.
   Edge : `product_features` à 1 (repli liste, nav masquée), plafonds 5/3.
5. `verification.md` + statut plan V5.

## 7. Tests / cas d'erreur
- Happy path : les 4 blocs rendus conformes.
- `product_features` à 1 élément → pas de slideshow, nav masquée (repli).
- `product_video_element` sans embed impossible (embed obl) ; avec embed → façade.
- `product_characteristics` sans notice/doc → pas de lien de téléchargement (opt).
- Plafonds : 6e feature/cross bloqué ; 4e titre argument bloqué.

## Statut
- [x] Plan validé (décision A : 3 titres ; décision B : 2 storages fichiers)
- [x] Étape 1 — storages + éléments (config clean)
- [x] Étape 2 — blocs + hôte (27 types au total, config clean)
- [x] Étape 3 — templates + SDC (lint + format verts)
- [x] Étape 4 — vérif navigateur (node/29 + node/30 ; cf. `docs/active/paragraphs/verification-v5-produit.md`)
- [x] Étape 5 — doc + statut

**Vague terminée — bibliothèque ADR-001 complète (27 paragraphes).**
