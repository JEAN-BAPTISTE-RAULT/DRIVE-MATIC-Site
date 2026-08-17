# Plan — Brique content-types complète (10 types restants) + convention de titre & d'URL

> Couvre F4, F5, F6, F7, F8 (liste + détail) et F9. Suite directe de la home F3.
> Arbitrages tranchés par l'utilisatrice le 2026-08-17, consignés en §0 et §6.

## 0. Convention de titre et motifs d'alias (→ ADR-011)

### Constat de départ

`field_title` **n'existe que sur l'entité `paragraph`** (`field.storage.paragraph.field_title.yml`).
Côté node, aucun champ de titre : sur `contact`, `partner` et `news`, le titre affiché est
le `title` du node, rendu par le bloc `page_title_block`. La **home est la seule exception** :
son titre visible vient d'un paragraphe, le bloc titre étant masqué sur `<front>`.

### Décision

Le `title` du node devient un **libellé d'administration** ; un nouveau champ **`field_title`
(obligatoire)** porte le **titre affiché**, et c'est **lui** qui alimente l'alias.

| Portée | Types |
|---|---|
| **Portent `field_title`** (11) | `transform`, `product`, `corporate`, `legals`, `faq`, `documents`, `brands`, `contact`, `partner`, `news`, `all_news` |
| **Exception** | `homepage` — titre porté par un paragraphe, aucun alias (chemin = `/`) |
| **Hors convention** | fragments `question`, `document`, `brand` — pas de page publique, pas d'alias : le `title` du node reste leur identité unique (**hypothèse**, à corriger si le titre affiché doit diverger du libellé admin sur ces trois-là) |

### Motifs Pathauto

| Type | Motif |
|---|---|
| `news` | `/actualites/[node:field_title]` |
| `all_news`, `transform`, `product`, `corporate`, `legals`, `faq`, `documents`, `brands`, `contact`, `partner` | `/[node:field_title]` |
| `homepage` | **aucun motif** (chemin `/`) |
| `question`, `document`, `brand` | **aucun motif** (URL bloquée Rabbit Hole) |

`all_news` étant à `/actualites`, le segment parent des détails d'actualité est un chemin
valide : le fil d'Ariane des actualités aura un vrai 3ᵉ niveau.

### Conséquences à traiter (toutes dans T0)

1. **Substitution du titre de route** dans `drive_matic_preprocess_page_title()`, plutôt que
   masquage du bloc titre : une condition de visibilité « type de contenu » rendrait le bloc
   inaccessible **hors routes de node** (contexte `node` manquant ⇒ `forbidden`), privant de
   `h1` les pages sans node. Le champ est **masqué dans le view display** pour ne pas être
   rendu deux fois. Raisonnement complet dans ADR-011.
2. **ADR-010 impacté** : le défaut Metatag `title: [node:title] | [site:name]` doit devenir
   `[node:field_title] | [site:name]`, sinon la balise `<title>` et l'onglet du navigateur
   affichent le libellé admin. Idem pour `news-card` et le futur teaser de liste.
3. **Rattrapage** sur les trois types déjà livrés (`contact`, `partner`, `news`) : ajout du
   champ, resaisie du contenu de démo, régénération des alias.
   `pathauto.update_action: 2` + `redirect.auto_redirect: true` créent les 301 automatiquement.
4. **`legals` sans metatags** (décision du modèle) : sa balise `<title>` retomberait sur le
   libellé admin. Corrigé en posant malgré tout le mapping dans son défaut Metatag.
5. `field_title` **obligatoire** ⇒ alias jamais vide, Pathauto reste fiable.
6. Vérifier que le token `[node:field_title]` est bien exposé (module Token, mode verbeux de
   Pathauto) **avant** d'écrire les 11 motifs.

### Constats de passage (hors périmètre, non corrigés)

- `easy_breadcrumb.settings` : `home_segment_title: Home` non traduit, et `alternative_title_field`
  pointe sur un `field_breadcrumb_title` inexistant (candidat naturel : `field_title`).
  **Corrigé le 2026-08-17** avec la bascule linguistique : segment « Accueil », titre affiché,
  capitalisation automatique coupée.
- `pathauto.settings.ignore_words` est une liste de mots-outils **anglais** : les alias français
  garderont « un », « en », « de ». Réglage éditorial, à arbitrer si besoin.

## 1. Intention

Livrer les **10 types de contenu restants** d'ADR-002 et les pages qui les rendent, pour que le
site public soit complet hors navigation (F2) et espace partenaire (F12-F16).

| # | Type | Feature | Nature |
|---|---|---|---|
| 1 | `all_news` | F8 | node + vue paginée 10/page |
| 2 | `transform` | F4 | assemblage paragraphes |
| 3 | `product` | F5 | assemblage paragraphes (blocs V5 déjà intégrés) |
| 4 | `corporate` | F9 | assemblage paragraphes |
| 5 | `legals` | F2/légal | assemblage minimal (`text_left_aligned` seul) |
| 6 | `question` | F9 | **fragment** |
| 7 | `faq` | F9 | node + vue BEF filtrée par catégorie |
| 8 | `document` | F6 | **fragment** |
| 9 | `documents` | F6 | node + 2 sections référencées |
| 10 | `brands` | F7 | node + vue alpha des `brand` |

## 2. Fichiers impactés

**Gabarit commun à chaque type public** (calqué sur `news`, déjà canonique) :
`node.type.<b>` · `field.field.node.<b>.{field_title,body,field_meta_tags,field_paragraphs…}` ·
`core.entity_form_display.node.<b>.default` (onglets `group_tabs` > `group_general` + `group_content`,
`uid`/`created` désactivés) · `core.entity_view_display.node.<b>.default` ·
`pathauto.pattern.node_<b>` · `simple_sitemap.bundle_settings.default.node.<b>` ·
`metatag.metatag_defaults.node__<b>`.

**Gabarit fragment** (calqué sur `brand`) : idem **sans** metatags, sans pathauto et sans
`field_title`, **plus** `rabbit_hole.behavior_settings.node_type_<b>` (`action: access_denied`)
et sitemap `index: false`.

**Nouveaux storages de champ sur `node`** :

| Storage | Type | Porté par |
|---|---|---|
| `field_title` | string, obligatoire | les 11 types publics hors `homepage` |
| `field_link` | link | `question` |
| `field_file` | file (description obligatoire, ADR-009) | `question`, `document` |
| `field_category` | ER → taxo `categories` | `question` |
| `field_documents_school` | ER → node `document`, illimité, ordonné | `documents` |
| `field_documents_pmr` | ER → node `document`, illimité, ordonné | `documents` |

**Base field override** : `core.base_field_override.node.<b>.title` sur les 11 types
(libellé « Titre administratif », description explicite).

**Taxonomie** : `taxonomy.vocabulary.categories` (Général / Auto-école / PMR) — **n'existe pas encore**.

**Vues** : `views.view.all_news` (bloc, pager 10, tri `changed` desc),
`views.view.faq` (bloc, filtre exposé BEF sur `field_category`),
`views.view.brands` (bloc, tri `field_title` asc).
Aucune vue pour `documents` : deux champs référence rendus directement.

**Thème** : `node--{all-news,news,faq,brands,documents,product,transform,corporate,legals}.html.twig`
selon écart à la maquette · SDC `news-teaser` (photo + titre + date + « Lire la suite »),
`document-row`. Le `<h1>` reste rendu par le bloc titre de page (cf. §0).
La FAQ **réutilise le SDC `accordion`** (fermeture du précédent déjà implémentée), la page marques
réutilise `brand-logo`.

## 3. Interfaces publiques

- **PHP** : rendu de la date d'actualité (« 12 juillet 2026 » depuis `changed`) — format de date
  en configuration si possible, sinon helper dans `drive_matic.theme`. Aucun autre hook.
- **JS** : aucun nouveau behavior (accordéon FAQ = lib existante). **Pas de mise à jour du linter.**
- **Twig** : `drupal_view()` (Twig Tweak) pour les 3 vues embarquées — déjà pratiqué sur le projet.

## 4. Sécurité

- Contenu **100 % public**, aucune donnée partenaire, aucun contrôle d'accès nouveau.
- Les deux fragments (`question`, `document`) : **URL canonique bloquée côté serveur**
  (Rabbit Hole 403) **et** exclusion du sitemap — vérifié en appelant `/node/<id>` en anonyme,
  pas en se fiant à l'absence de lien.
- Vues filtrées `status = published`.
- `field_file` : extensions bornées à l'instance (PDF et équivalents), aucune extension exécutable ;
  description obligatoire (contrainte ADR-009 déjà en place).
- Aucun `|raw`, aucun asset tiers.

## 5. Risques et contraintes techniques

1. **Vues embarquées + pager / filtre exposé** — le vrai risque du lot. `drupal_view()` rend bien
   la vue, mais le **pager** (`all_news`) et le **formulaire exposé** (`faq`) passent par les
   paramètres d'URL : vérifier que la pagination navigue et que le filtre BEF se soumet sans AJAX.
   Repli si ça coince : basculer sur un display **page** de la vue — arbitrage sur constat.
2. **Cache** — `drupal_view()` propage les cache tags (contrairement à `drupal_view_result()`,
   dont le piège est documenté pour `news_home`). Contextes attendus : `url.query_args.pagers`
   (all_news), `url.query_args` (faq exposé). À vérifier **en anonyme, cache page actif**.
3. **Changement d'alias** — après T0, lancer la mise à jour groupée Pathauto puis vérifier
   qu'un 301 existe depuis chaque ancien chemin.
4. **Champ non rangé dans un onglet** = affiché hors onglets en haut du formulaire (piège connu) :
   contrôle sur **chacun** des formulaires, `field_title` compris.
5. **`simple_sitemap` / `url_redirects`** s'ajoutent aux formulaires sans consulter le form display :
   le `#after_build` de `drivematic_forms` les retire — confirmer qu'il couvre les nouveaux types.
6. **Double titre** : après T0, contrôler qu'aucune page ne rend à la fois le libellé admin et
   le titre affiché (bloc titre masqué + template).
7. **A11y** : un seul `<h1>` par page, filtre exposé FAQ étiqueté, pagination navigable au clavier,
   `<time datetime>` sur les dates d'actualité.
8. **`hook_update_N`** : aucun. Tout passe par config + `drush cim`.
9. **i18n** : libellés de types, de champs et de vues en français, chaînes de template via `|t`.
10. ~~La langue du site est l'anglais~~ — **resolu le 2026-08-17** : `language` + `locale`
    installes, francais par defaut, 15 309 chaines importees. Dates, poids de fichiers et
    chaines du cœur sont en francais. Les deux pieges rencontres (reference circulaire d'une
    `ConfigFactoryOverride`, alias casses par le changement de langcode) sont decrits dans
    docs/PRD.md, section 7.
11. ⚠️ **La page d'accueil n'a aucun `<h1>`** — constaté en T6, **antérieur à cette brique**
    (hérité de F3). Le bloc titre de page y est masqué (`request_path: <front>`) et le titre
    visible, « Bienvenue chez DRIVE-MATIC », vient du SDC `text_centered` qui rend un `<h2>`.
    La home part donc directement en `h2`. Contredit la décision #8 du PRD (RGAA / WCAG 2.1 AA).
    **Non corrigé ici** : changer le niveau de titre de `text_centered` toucherait toutes les
    pages qui l'utilisent. À trancher avec le placement du titre, à l'intégration des maquettes.

## 6. Cohérence avec les spécifications

Conforme à ADR-002 / `docs/active/content-types/model.md`. Ne contredit aucune décision verrouillée.
**Écarts et arbitrages tranchés** (à répercuter dans le modèle) :

- **Convention de titre** (§0) : nouvelle, elle amende le modèle pour les 11 types publics
  et impacte ADR-010. → **ADR-011 à rédiger**.
- **`documents`** : deux champs référence `field_documents_school` + `field_documents_pmr`
  (et non un paragraphe « section ») — la bibliothèque ADR-001 reste close à 27.
  Les titres de section sont les **libellés des deux champs**, donc modifiables en
  configuration mais pas par l'éditeur.
- **`document` = titre administratif + fichier + libellé de bouton, les trois obligatoires**
  (arbitrage utilisatrice, 2026-08-17). Ce n'est **pas** une double saisie : c'est la même
  séparation que `title` / `field_title` sur les types publics — un libellé pour
  l'administration (listes, recherche, autocomplétion depuis la page Documentations), un pour
  le public (le bouton). J'avais d'abord signalé cette structure comme un défaut d'ergonomie et
  recommandé une exemption d'ADR-009 : recommandation retirée, elle allait contre la convention.
  **Reste à faire** : les trois fragments (`document`, `question`, `brand`) n'ont pas de
  `core.base_field_override` sur `title`, donc leur champ s'affiche « Title » au lieu de
  « Titre administratif » — seul écart réel avec le modèle.
- **`product`** : **pas** de `grid` — on s'en tient à l'allowlist du modèle.
- **« Bloc configurateur »** (F3, F4, F5) : il n'existe pas comme paragraphe ; ce contenu se
  construit avec **`image_text_100`**, déjà autorisé sur `homepage`, `transform` et `product`.
  Aucune config à changer ; le lien reste un placeholder tant que F14 n'existe pas.

Scénarios E2E rendus rejouables : **S3** (page solution), **S4** (fiche produit),
**S5** (documentations), **S6** (marques), **S7** (actualités). Aucun nouveau scénario à écrire.

## 7. Plan d'implémentation

Chaque tranche est committable et laisse le site fonctionnel. `field_title` est ajouté par la
tranche qui crée le type ; T0 ne rattrape que les types déjà livrés.

| # | Tranche | Vérification |
|---|---|---|
| **T0** ✅ | ADR-011 · storage `field_title` + override du `title` en « Titre administratif » · substitution dans `preprocess_page_title` · mapping Metatag · rattrapage `contact`/`partner`/`news` · motifs d'alias | ✅ Token `[node:field_title]` résolu ; un seul `<h1>` par page ; `<h1>` **et** `<title>` = titre affiché (vérifié avec un libellé admin distinct) ; 301 depuis l'ancien alias ; `drush config:status` clean ; lint + format verts |
| **T1** ✅ | `all_news` (F8) + vue paginée + `node--news` (détail : date, visuel 16:9, légende) + `field_paragraphs` sur `news` | ✅ `/actualites` sert la liste, le lien « voir toutes » de la home y mène ; 10 en page 1 / 1 en page 2 sur 11 actualités ; 0 actualité → message propre ; dépublier une actualité met la liste à jour **sans vidage de cache** ; lint + format verts |
| **T2** ✅ | `transform` (F4) + `product` (F5) | ✅ Les deux pages rendent tous leurs blocs (fiche produit = les 4 blocs V5 + bannière + configurateur en `image_text_100`) ; `grid` **refusé** sur `product`, autorisé sur `transform` ; les 9 champs rangés dans leurs onglets sur les deux formulaires ; alias, `<h1>` et `<title>` depuis `field_title` ; lint + format verts. **Aucun template dédié nécessaire** : le template générique ne rend pas le libellé en pleine page |
| **T3** ✅ | `corporate` (F9) + `legals` | ✅ `corporate` rend ses 6 blocs de démo (dont `triptych`, `history`, `image_centered`, `video_centered`) ; `legals` **sans body ni champ « Balises meta »** (absents du formulaire), dans le sitemap, aucune méta description émise, et `<title>` = titre affiché grâce à un défaut Metatag limité au titre ; lint + format verts |
| **T4** ✅ | taxo `categories` + `question` (fragment) + `faq` + vue BEF | ✅ `/node/<question>` → **403** ; filtre BEF rendu en **liens** (un par catégorie), `?categorie=479` → 2 questions, catégorie inconnue → message propre ; les 6 questions rendues en `accordion-element` dans le SDC `accordion`, dont les assets (CSS + JS) sont bien attachés depuis un template de Vue ; formulaire `question` sans `field_title` ni métatags ; lint + format verts |
| **T5** ✅ | `document` (fragment) + `documents` (F6) + `brands` (F7) | ✅ `/documentations` rend deux sections (libellés de champ « Auto-écoles » / « PMR ») et 4 boutons « Nom (PDF poids) » ; **section vide → rien d'affiché**, pas de titre orphelin ; `/node/<document>` → **403** ; `/marques-partenaires` rend les 16 tuiles dans le SDC `brands-grid`, ordre alpha, non cliquables ; lint + format verts. ⚠️ Double saisie du nom de document (cf. §6) |
| **T6** ✅ | Recette : sitemap par bundle, metatags des 8 nouveaux publics, contenu de démo, `drush cex`, lint, format | ✅ `sitemap.xml` = 17 URL, **aucun fragment**, ni le bac à sable ; les 12 pages publiques répondent 200 avec le bon `<h1>` et le bon `<title>` ; méta description partout sauf `legals` (voulu) ; aucun node publié sans alias ; `config:status` clean, lint + format verts. **Corrigé au passage** : `contact` et `partner` étaient absents du sitemap. ⚠️ La home n'a **aucun `<h1>`** (cf. §5.11) |

## 8. Stratégie de test et boucle de feedback

`npm test` est un placeholder — **aucun test automatisé** sur ce lot ; vérification manuelle outillée.

- **Boucle la plus rapide** : écrire la config → `drush cim` → `drush cex` (canonicalisation) →
  `drush config:status` = « No differences » → `drush cr` → rechargement navigateur.
  C'est la boucle déjà rodée sur V4/V5.
- **Rendu** : capture Chrome headless (le MCP Browser est bloqué sur ce poste), sur des médias
  **effectivement recadrés** au ratio concerné — sinon le contrôle visuel ne prouve rien.
- **Cache** : chaque vue embarquée testée **en anonyme, cache page actif** (une actualité publiée
  doit apparaître sans vidage manuel).
- **Contrôles d'erreur, en plus du chemin nominal** :
  - node sans aucun paragraphe → pas de trou dans la page ;
  - `all_news` à 0 puis 11 actualités (bloc vide propre / pagination) ;
  - FAQ sans question, et filtre ne retournant rien → message, pas de page cassée ;
  - section `documents` vide → titre de section masqué, pas d'espace mort ;
  - `/node/<id>` d'un `question` / `document` en anonyme → **403** ;
  - deux nodes de même `field_title` → alias suffixé, aucune collision ;
  - `field_title` vidé en BO → refus à l'enregistrement (champ obligatoire) ;
  - navigation clavier sur la pagination et le filtre exposé.
