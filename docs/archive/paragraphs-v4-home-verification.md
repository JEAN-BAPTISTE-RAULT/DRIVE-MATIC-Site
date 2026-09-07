# Verification — Prérequis `news`/`brand` + V4 Home (paragraphes)

> Trace d'audit. Plan : `docs/plans/content-types-v4-home.md`. Vague V4 de `docs/plans/paragraphs-sdc.md`.

## Commandes executees

| Commande | Resultat | Notes |
|---|---|---|
| `npm run lint` (ESLint + Stylelint + PHPCS) | ✅ rc=0 | inclut le nouveau `drive_matic.theme` |
| `npm run format:check` | ✅ clean | — |
| `npm test` | ✅ exit 0 | placeholder (pas de test auto configuré) |
| `drush cim`/`cex` + `drush config:status` | ✅ « No differences » | 40 items config exportés/canonicalisés |
| `curl /node/<brand>` / `/node/<news>` | ✅ 403 / 200 | fragment `brand` bloqué (Rabbit Hole), `news` public |
| Vérif navigateur (drush runserver 8095) | ✅ | 3 slideshows init, cf. edge cases |

## Changements comportementaux

- **Nouveaux types de contenu** `news` (image 16:9, légende, date = `changed`) et `brand` (fragment, logo sans crop, **page canonique 403**, hors sitemap).
- **2 Vues** : `news_home` (5 récentes, `changed` desc) et `brands_home` (alpha).
- **4 nouveaux paragraphes** placés sur l'hôte `page` : `jumbo_home` (+`jumbo_home_element`, plafonné 3), `news_home`, `brands_home` — chacun en slideshow Swiper avec repli progressif.
- Nouveau **mode d'affichage node `card`** + SDC item (`news-card`, `brand-logo`) ; nouveau `drive_matic.theme` (préprocess de cache tags).

## Risques identifies et mitigations

- **Swiper ↔ Vue** (contrainte enfant direct) → rendu des diapositives via `drupal_view_result` + `drupal_entity('card')` (DOM propre), pas `drupal_view`. Mitigé et vérifié navigateur.
- **Invalidation de cache** du bloc à Vue (perte de `node_list` par `drupal_view_result`) → réattache `node_list:news`/`node_list:brand` via `drive_matic_preprocess_paragraph`. **Risque résiduel** : pas de test auto d'invalidation.
- **Exposition fragment `brand`** → canonique 403 (RH) + hors sitemap + cartes non liées. Vérifié (curl 403, 0 lien dans le DOM).
- **Cardinalité jumbo** → storage dédié `field_jumbo_elements` (card 3, ADR-007).

## Edge cases testes

- Jumbo **2** éléments → slideshow initialisé, flèches visibles. ✅
- Jumbo **1** élément (node/28) → **pas** de slideshow, flèches masquées (repli). ✅
- `news_home` **5** cartes (6e actu exclue), ordre récent-first. ✅
- `brands_home` **4** logos en ordre alpha, **0 lien** (canonique bloquée). ✅
- `brand` URL directe (anonyme) → **403**. ✅ ; `news` URL directe → 200. ✅
- Images actu = **WebP responsive 16:9** (`dm_16_9_*`, `<picture>` srcset). ✅
- Erreurs console `getComputedStyle` = **environnement du preview** (présentes même sans Swiper), pas le code. ✅

## Self-review

1. **Decision la plus difficile** : le rendu des blocs à Vue dans un slideshow Swiper — contrainte « `.swiper-wrapper` enfant direct » découverte au test, imposant un pivot d'approche (`drupal_view_result` + `drupal_entity`).
2. **Alternatives rejetees** : `display:contents` sur les wrappers de Vue (ne corrige pas la traversée DOM de Swiper) ; override `views-view` pour retirer `.view` (le `.views-element-container` du core subsiste) ; storage partagé `field_elements` pour jumbo (plafonné 3 → storage dédié, ADR-007).
3. **Point de moindre confiance** : l'invalidation `node_list` par préprocess (vérifiée par raisonnement, pas par test auto) ; rendu visuel sur thème encore minimal (pas de comparaison maquette pixel).
