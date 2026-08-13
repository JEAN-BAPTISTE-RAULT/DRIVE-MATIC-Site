# Plan — Shell minimal + F3 Home page

> Livre la première vraie page publique : squelette de page (header logo + footer minimal + fil d'Ariane) + home page (`homepage`) qui assemble les paragraphes V4 (jumbos, actus, marques) et les blocs éditoriaux existants. **Assemblage pur — aucun nouveau paragraphe/storage.**

## Décisions actées (validées utilisatrice)
- **Front page = `/`** : la home est le node `homepage`, servi à la racine. **Pas d'alias `/accueil`** (la home vit uniquement à `/`).
- **Footer minimal sobre** (logo + copyright + placeholders réseaux/légaux). Le footer riche = F2 (plus tard).
- **Header/footer = SDC** (`site-header`, `site-footer`) ; `page.html.twig`/`region.html.twig` restent le squelette (fondation).
- Masquage breadcrumb + titre de node sur la home via **visibilité de bloc** (`request_path: <front>`), pas de preprocess.

## Fichiers impactés
**Config** : `node.type.homepage`, `field.field.node.homepage.{body,field_paragraphs}` (storages partagés réutilisés ; allowlist = image_text_50, accordion, image_text_100, grid, text_centered, text_left_aligned, news_home, brands_home, jumbo_home), form/view displays, `metatag.metatag_defaults.node__homepage`, `system.site` (front → home node), `block.block.drive_matic_breadcrumbs` + `…page_title` (visibilité <front>).
**Thème** : `components/site-header/`, `components/site-footer/` (SDC), `templates/layout/page.html.twig` (modif), `templates/content/node--homepage.html.twig` (nouveau), build SCSS.

## Étapes (chunks committables)
1. Type de contenu `homepage` → vérifier `config:status` clean + form d'édition (body + allowlist paragraphes).
2. SDC `site-header`/`site-footer` + `page.html.twig` → vérifier `/contact` affiche header/footer sans régression.
3. `node--homepage` + front page `/` + visibilité breadcrumb/titre → breadcrumb absent home / présent interne.
4. Contenu de démo (home réelle) → vérif navigateur : jumbos, news_home (5), brands_home (alpha non cliquable), accordéons SEO fermeture-précédent, responsive, WebP.
5. `drush cex` + `npm run lint` + `format:check` verts.

## Sécurité / cache
Page 100 % publique, aucune donnée partenaire. Cache tags `node_list:news`/`node_list:brand` réattachés par `drive_matic_preprocess_paragraph` (acquis V4). Aucun asset tiers, aucun `|raw`.

## Portabilité front page — RÉSOLU
Module custom **`drivematic_home`** : service `FrontPageOverride` (`ConfigFactoryOverride`) surcharge `system.site:page.front` → `/node/{id}` de l'unique node `homepage` publié, à l'exécution. **Sans ID en dur, sans alias `/accueil`**, portable au seed. La valeur versionnée reste `/node` (les overrides ne sont pas exportés → aucune dérive). Cache invalidé par `node_list:homepage`. Robuste à l'install (try/catch → pas de surcharge si entités indisponibles).
Reste optionnel : redirect `/node/{id}` → `<front>` si l'on veut interdire l'accès canonique `/node/N` (la home reste servie à `/` dans tous les cas).

## E2E
Rend **S2** rejouable pour de vrai. Liens « solutions »/« configurateur » = CTA placeholder tant que product/transform/configurateur (F4/F5/F14) n'existent pas.
