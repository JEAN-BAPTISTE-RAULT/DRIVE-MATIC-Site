# ADR-007 : Storage partagé `field_elements` pour les paragraphes imbriqués

## Statut
Accepte

## Date
2026-08-13

## Contexte
La bibliotheque de paragraphes (ADR-001) comporte plusieurs paires **Bloc / Element** : un Bloc reference N paragraphes « Element » via un champ `entity_reference_revisions` (ERR). La vague V2 (`accordion` -> `accordion_element`) est la premiere a implementer ce pattern ; les vagues suivantes en ajouteront d'autres (`grid`, `history`, `product_characteristics` ; `jumbo_home`, `product_features`, `product_cross`).

Question : faut-il un **storage de champ dedie par Bloc** (`field_accordion_items`, `field_grid_items`, …) ou un **storage partage** reutilise par tous les Blocs, la restriction du type d'Element se faisant sur l'**instance** (field.field) via `target_bundles` ?

Contrainte Drupal structurante : la **cardinalite** d'un champ se definit au niveau du **storage**, pas de l'instance. Un storage `cardinality: -1` (illimite) ne peut pas plafonner certaines instances a 3 ou 5.

## Options considerees

### Option A : un storage dedie par Bloc
- Avantages : cardinalite reglable finement (max 3 pour `jumbo_home`, max 5 pour `product_*`).
- Inconvenients : multiplication des storages quasi identiques (dette de config), pattern non mutualise.

### Option B : un storage partage `field_elements` (illimite) pour tous les Blocs
- Avantages : un seul storage ERR mutualise ; coherent avec les autres storages partages de l'entite `paragraph` (`field_title`, `field_link`…) ; l'allowlist du type d'Element vit sur l'instance (`target_bundles`).
- Inconvenients : cardinalite figee a l'illimite -> **inapplicable aux Blocs plafonnes** (jumbo max 3, product_features/product_cross max 5).

## Decision
**Option hybride, dominante B.**

- Storage **partage `field_elements`** (`entity_reference_revisions` vers `paragraph`, **cardinalite `-1`**) pour tous les Blocs composes **non plafonnes** : `accordion`, `grid`, `history`, `product_characteristics`. La restriction du type d'Element autorise se fait par instance via `handler_settings.target_bundles`.
- Les Blocs **plafonnes** (`triptych` max 3, `jumbo_home` max 3, `product_features` max 5, `product_cross` max 5) recevront un **storage dedie** au moment de leur implementation, avec la cardinalite requise. Le plafond min (min 1) reste porte par `required: true` sur l'instance.

## Consequences
- V2 cree `field.storage.paragraph.field_elements` (illimite) et l'instance `field.field.paragraph.accordion.field_elements` restreinte a `accordion_element`.
- Les types « Element » (`accordion_element`, …) restent **exclus du placement direct** : ils n'apparaissent pas dans le `target_bundles` du champ hote (`node.page.field_paragraphs`), seulement dans celui de leur Bloc parent.
- V3 : `grid` et `history` reutilisent `field_elements` tel quel (ajout d'instances) ; `triptych` (max 3, initialement omis de la liste des plafonnes ci-dessus — corrige) recoit un storage dedie **`field_triptych_elements`** (cardinalite `3`). V4/V5 introduiront les autres storages dedies plafonnes — a tracer ici si le plafond evolue.
- V4 : `jumbo_home` (max 3) recoit son storage dedie **`field_jumbo_elements`** (cardinalite `3`, ERR vers `paragraph`, instance restreinte a `jumbo_home_element`). `jumbo_home_element` est exclu du placement direct (absent du `target_bundles` de `node.page.field_paragraphs`). Les blocs home a Vue (`news_home`, `brands_home`) n'imbriquent pas d'Element : ils rendent une liste d'entites (`node` `news`/`brand`) via une Vue, pas des paragraphes.
- Aucune donnee partenaire : paragraphes editoriaux publics.
