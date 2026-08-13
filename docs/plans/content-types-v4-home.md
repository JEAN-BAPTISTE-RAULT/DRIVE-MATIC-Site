# Plan — Prérequis `news`/`brand` + V4 Home (paragraphes)

> Validé par l'utilisatrice (séquencement « news+brand puis V4 complet »). Étend le plan de vagues `paragraphs-sdc.md` (vague V4). Amorce une **tranche minimale** de la brique content-types (ADR-002) : uniquement `news` + `brand` + leurs 2 Vues, prérequis des blocs home `news_home`/`brands_home`.

## Décisions actées (confirmées)
1. Tranche content-types limitée à **news + brand + 2 Vues**. Métatags news, `all_news` et les autres nodes → **différés** à la brique complète.
2. **Métatags news différés** (le meta title viendra plus tard ; pas de mapping body→description pour l'instant).
3. V4 **testé sur l'hôte `page`** ; le vrai node `homepage` et l'autorisation des 3 blocs arriveront avec la brique content-types.

## 1. Intention
Compléter la vitrine home (F3) : livrer `jumbo_home`, `news_home`, `brands_home`, après avoir créé la tranche minimale de content-types (`news` + `brand`) dont dépendent 2 des 3 blocs.

## 2. Fichiers impactés

### Étape A — prérequis content-types (tranche minimale ADR-002)
- `config/sync/node.type.news.yml`, `node.type.brand.yml`
- Champs (réutilisent storages existants `node.field_image`, `node.body`) : `field.field.node.news.body.yml`, `field.field.node.news.field_image.yml`, `field.field.node.brand.field_image.yml` ; **nouveau storage** `field.storage.node.field_caption.yml` (légende image news, opt) + `field.field.node.news.field_caption.yml`
- Form + view displays : `core.entity_form_display.node.{news,brand}.default.yml`, `core.entity_view_display.node.{news,brand}.*` (default + teaser servant de ligne aux Vues)
- `rabbit_hole.behavior_settings.node_brand.yml` (canonical `brand` bloqué 403/404)
- `simple_sitemap.bundle_settings.*` (news inclus, brand exclu)
- Vues : `views.view.news_home.yml` (5 récentes, tri `changed` desc, publiées), `views.view.brands_home.yml` (tous les brand, tri titre asc)

### Étape B — V4 paragraphes home
- `paragraphs.paragraphs_type.{jumbo_home,jumbo_home_element,news_home,brands_home}.yml`
- **Nouveau storage** `field.storage.paragraph.field_jumbo_elements.yml` (ERR, card 3) + `field.field.paragraph.jumbo_home.field_jumbo_elements.yml`
- `field.field.paragraph.jumbo_home_element.{field_title,field_link,field_image}.yml` ; `field.field.paragraph.{news_home,brands_home}.{field_title,field_link}.yml`
- SDC : `components/{jumbo-home,jumbo-home-element,news-home,brands-home}/`
- Templates : `templates/paragraph/paragraph--{jumbo-home,jumbo-home-element,news-home,brands-home}.html.twig`
- Form + view displays des 4 types de paragraphe
- **Modif** `field.field.node.page.field_paragraphs.yml` : + `jumbo_home`, `news_home`, `brands_home` aux `target_bundles` (hôte de test) ; `*_element` exclus du placement direct
- Wrapping Vue↔Swiper : `templates/views/views-view-unformatted--news-home.html.twig` (+ brands)

## 3. Interfaces publiques
- JS : aucun nouveau behavior (réutilise `drive_matic/slideshow` + `drive_matic/swiper` via `libraryOverrides`). Global `Swiper` déjà déclaré → pas de MAJ linter.
- PHP : aucun hook/export (Vues via `drupal_view()` Twig Tweak dans les templates paragraph).

## 4. Sécurité
- Contenu 100 % public (news, brands) — aucune donnée partenaire.
- `brand` fragment : canonical bloqué (Rabbit Hole 403/404) + exclu du sitemap.
- Vues filtrées `status = published` ; list cache tags automatiques (`node_list:news`/`node_list:brand`).
- Pas de `|raw` ; sortie Vue auto-échappée ; `field_link` interne/externe validé (Linkit + Link Target).

## 5. Risques / contraintes
- **Vue ↔ Swiper** (risque principal) : Vue « liste non formatée » + `views-view-unformatted--<vue>.html.twig` fournissant `.swiper-wrapper`/`.swiper-slide` ; SDC = conteneur + init ; repli liste si < 2.
- `jumbo_home` plafonné 3 → storage dédié `field_jumbo_elements` + **amendement ADR-007**.
- Type `homepage` différé → test sur hôte `page`.
- Date news = `changed` (ADR-002 #4) → tri Vue sur `changed` desc.
- A11y : slideshows déjà RGAA via behavior existant ; jumbo 1 item → pas de slideshow.
- Perf : styles `dm_16_9_*` existants (WebP responsive) ; Swiper chargé seulement où utile.

## 6. Cohérence specs
- Aligné F3, F1, ADR-001/002/007. Sous-partie d'ADR-002 (news + brand).
- Différés assumés : métatags news, `all_news`, autres nodes, `homepage`.
- E2E : ajouter scénario « blocs home : slideshow jumbo/news/marques, repli à 1, WebP responsive ».

## 7. Plan d'implémentation (chunks committables)
1. Content-types news + brand → `drush cim` propre, `config:status` clean, brand canonical = 403/404.
2. Vues news_home + brands_home (sortie liste brute) → listing + tri + cache tags OK.
3. `jumbo_home` (+element) SDC + slideshow → navigateur : 1=pas de slideshow, 2-3=Swiper clavier, 4e bloqué, 16:9 WebP.
4. `news_home` + `brands_home` SDC + wrapping Vue↔Swiper + `drupal_view()` → navigateur : items image+titre+lien, ≥2 slideshow / 1 repli / 0 vide.
5. Câblage hôte `page` + docs (ADR-007, plan, model, E2E) → placement BO, lint/format verts, `drush cex` canonique.

## 8. Tests / feedback
- Boucle : config/Twig/SCSS → `npm run build` + `drush cr` → navigateur. Pas de PHPUnit (`npm test` placeholder).
- Vérif : manuelle navigateur + statique (`npm run lint`, `format:check`, `drush config:status`).
- Seed : ~5 news + ~4 brands en BO.
- Cas d'erreur : 0 item (bloc vide propre), 1 item (pas de slideshow), 4e jumbo (refus card), URL brand anonyme (403/404), news non publiée absente.

## Statut
- [x] Plan validé
- [x] Étape 1 — content-types news + brand (RH `brand` 403, sitemap news inclus / brand exclu)
- [x] Étape 2 — Vues news_home (5, tri changed desc) + brands_home (alpha) — `node_list` OK
- [x] Étape 3 — jumbo_home (+element) — storage dédié `field_jumbo_elements` (card 3)
- [x] Étape 4 — news_home + brands_home (drupal_view_result + drupal_entity `card`, SDC news-card/brand-logo)
- [x] Étape 5 — câblage hôte page + docs (lint/format/config:status verts)

## Note d'implémentation (écart au plan)
`news_home`/`brands_home` : le plan prévoyait `drupal_view()` complet + wrapping `views-view-unformatted--*`. **Swiper exige `.swiper-wrapper` en enfant direct** ; les wrappers de Vue (`.view`, `.views-element-container`) cassaient l'init. Retenu : **`drupal_view_result()` + `drupal_entity(..., 'card')`** dans le template paragraphe (DOM propre), + `drive_matic_preprocess_paragraph` pour réattacher `node_list:news`/`node_list:brand` (cache). Nouveau fichier `drive_matic.theme`. View mode `card` + SDC `news-card`/`brand-logo` + templates `node--news--card`/`node--brand--card`.

## Test data (local, non versionné)
Seed local : 6 actualités + 4 marques (média `id=3` réutilisé) + 2 pages hôtes (nids 27 jumbo2+news+brands, 28 jumbo 1 élément). À supprimer/renouveler librement — contenu, non exporté en config.
