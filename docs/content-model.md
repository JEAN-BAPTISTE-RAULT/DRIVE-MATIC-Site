# Modele de contenu editorial — valide (livre)

> **Etat au 2026-08-17 : les 15 types sont implementes** (12 nodes publics + 3 fragments),
> ainsi que la taxonomie `categories`. Detail des tranches et de la recette :
> `docs/plans/content-types-complet.md`.

> ⚠️ **Quatre passages sont perimes** (constate le 2026-08-19, en promouvant ce fichier depuis
> `docs/active/content-types/`). Il n'a pas ete re-audite depuis l'ADR-014 : lire ces points
> dans leur source a jour, pas ici.
>
> 1. **`field_title` cote node n'existe plus.** L'[ADR-014](../.claude/decisions/014-titre-unique-porte-par-le-title.md)
>    remplace l'ADR-011 : le `title` est la source unique du titre affiche, de l'alias, du fil
>    d'Ariane et de la balise `title`. Toute la section « Titre affiche vs libelle
>    d'administration » et chaque mention de `field_title` **ne decrivent plus le code** —
>    ⚠️ **ne pas recreer ce champ**. Etat courant : `CLAUDE.md` et l'ADR-014. (Le
>    `field_title` des **paragraphes** est un champ homonyme et distinct, toujours en place.)
> 2. **Le fragment `document` a ete supprime** le 2026-08-18 : `documents` porte desormais
>    deux champs **Fichier a iteration illimitee**, et non des references a des nodes
>    `document`. Les lignes qui le citent encore sont a lire au passe.
> 3. **`news.field_image`** : le recadrage 16:9 reste **obligatoire a l'import**, mais la page
>    de detail affiche le visuel **sans recadrage** ; le 16:9 ne sert plus qu'aux vignettes des
>    listes et de la home (F8, [ADR-016](../.claude/decisions/016-colonne-de-contenu.md)).
>    ⚠️ Le champ a en outre ete **renomme `field_photo`** et n'est plus une reference media
>    (voir point 4).
> 4. **`field_image` (paragraphes) n'est plus une storage unique.** [ADR-018](../.claude/decisions/018-images-locales-par-paragraphe.md)
>    (2026-08-19) sort les 9 paragraphes a ratio impose (`image_text_50`, `image_full`,
>    `history_element`, `grid_element`, `jumbo_home_element`, `product_cross_element`,
>    `product_image_element`, `product_video_element`, `video_centered`) de la mediatheque :
>    ils portent desormais un champ **`field_photo`** (image locale, recadrage
>    `image_widget_crop` scope au seul ratio du bundle, un fichier par usage). `field_image`
>    ne reste une reference media que pour `image_centered`, `image_text_100` et
>    `product_characteristics` (sans ratio impose) — inchanges.
> 5. **`legals` porte desormais `body` + `field_meta_tags`**, comme tout autre type public
>    ([ADR-019](../.claude/decisions/019-legals-body-metatags.md), 2026-08-20) : ses
>    paragraphes (`text_left_aligned`) sont retires, le contenu de la page CGV (node 55)
>    migre dans `body`. Les mentions « pas de body ni metatags » / « Types sans metatags :
>    legals » plus bas (lignes 55, 92, 99, 178) sont perimees. Les
>    3 autres pages legales du footer (CGU, mentions legales, donnees personnelles — F2)
>    existent desormais comme nodes `legals` distincts, body encore vide (contenu editorial
>    a saisir).
>
> Le reste (types, champs, allowlists de paragraphes, conventions de formulaire, sitemap) n'a
> pas ete verifie ligne par ligne a cette date. Une reprise complete reste a faire.


> Liste **validee par l'utilisatrice** (arbitrage direct). Reference d'implementation. Actee dans [ADR-002](../.claude/decisions/002-types-de-contenu.md). S'appuie sur la bibliotheque de paragraphes [ADR-001](../.claude/decisions/001-bibliotheque-paragraphes.md).
>
> **Convention de nommage** : `Page :: X` = **node public** (URL, sitemap, metatags) ; `Element :: X` = **node « fragment »** sans page publique (hors sitemap, **URL directe bloquee** via Rabbit Hole ou equivalent), reutilisable et listable en vue.

## Nodes (`Page :: …`)

| # | Machine name | Label | Champs propres | Paragraphes autorises (illimite) | Vue / particularite |
|---|--------------|-------|----------------|----------------------------------|---------------------|
| 1 | `homepage` | Page :: Accueil | body (obl), metatags | image_text_50, accordion, image_text_100, grid, text_centered, text_left_aligned, news_home, brands_home, jumbo_home | Node unique = page d'accueil |
| 2 | `transform` | Page :: Transformer / Équiper | body (obl), metatags | image_text_50, accordion, image_text_100, grid, text_centered, text_left_aligned, image_full | — |
| 3 | `product` | Page :: Produit | body (obl), metatags | image_text_50, accordion, image_text_100, text_centered, text_left_aligned, image_full, product_arguments, product_features, product_characteristics, product_cross | — |
| 5 | `faq` | Page :: FAQ | titre (obl), body (obl), metatags | — | Vue **BEF** filtrée par `categories`, tous les `question`, tri date de modif décroissante |
| 7 | `documents` | Page :: Documentation | titre (obl), body (obl), metatags | — | 2 sections = **2 champs référence ordonnés** (`field_documents_school`, `field_documents_pmr`) vers des `document` ; les **libellés de champ** servent de titres de section ; section vide → rien d'affiché |
| 8 | `corporate` | Page :: Corporate | titre (obl), body (obl), metatags | image_text_50, accordion, image_text_100, text_centered, text_left_aligned, triptych, history, image_centered, video_centered | Qui sommes-nous, ateliers, R&D, certifications… |
| 10 | `brands` | Page :: Marques | titre (obl), body (obl), metatags | — | Vue triée alpha de tous les `brand` |
| 11 | `contact` | Page :: Contact | titre (obl), body (obl), visuel **crop 16:9** (obl, `field_photo`), metatags | — | Webform complexe (contenu défini plus tard) |
| 12 | `simple_form` | Page :: Formulaire simple | titre (obl), body (obl), metatags | — | Webform référencé ; **mutualisé, multi-instance** depuis le 2026-08-25 ([ADR-024](../.claude/decisions/024-mutualisation-formulaire-simple.md)) — porte les nodes « Devenir partenaire » (webform `partner`) et « Demande de création de compte » (webform `account_request`) |
| 13 | `legals` | Page :: Mentions légales | titre (obl) | text_left_aligned | Pas de body ni metatags |
| 14 | `news` | Page :: Détail d'une actualité | titre (obl), body (obl), metatags, image **crop 16:9** (obl), légende image (opt) | text_left_aligned, image_centered, video_centered | Date = **dernière modification** (champ `changed`, pas de champ dédié), affichée « 12 juillet 2026 » |
| 15 | `all_news` | Page :: Toutes les actualités | titre (obl), body (obl), metatags | — | Vue paginée (10/page) de tous les `news`, tri date de modif décroissante |

Tous les nodes : **inclus au sitemap** — sauf `homepage`, dont la racine `/` est déjà déclarée en **lien personnalisé** (un réglage de bundle produirait un doublon avec `/node/<id>`).

**Corps de texte masqué à l'affichage** sur les pages entièrement composées de blocs (`homepage`, `transform`, `product`, `corporate`) : le champ y sert de source à la méta description, pas de chapeau. Il reste **affiché** sur `news`, `all_news`, `faq`, `documents`, `brands`, `contact`, `simple_form`, où il porte un vrai texte d'introduction.

## Fragments (`Element :: …`) — nodes sans page publique

Types de node **exclus du sitemap** et dont la **page canonique est bloquee** (403 via Rabbit Hole, `rabbit_hole.behavior_settings.node_type_<bundle>`). Restent listables en vue et referencables (`entity_reference`).

⚠️ L'exclusion du sitemap se fait par **absence** de `simple_sitemap.bundle_settings.default.node.<bundle>` (l'indexation est opt-in) — il n'y a donc rien a ecrire, mais rien non plus qui la rende visible en configuration. Le controle se fait sur le `sitemap.xml` genere.

Les fragments restent **hors de la convention ADR-011** : pas de `field_title` (leur `title` est leur identite unique), pas de metatags, pas d'alias.

| # | Machine name | Label | Champs |
|---|--------------|-------|--------|
| 4 | `question` | Élément :: Question / Réponse | titre (obl), body (obl), lien (opt), fichier téléchargeable (opt), catégorie → taxo `categories` (obl) |
| 6 | `document` | Élément :: Document | nom (obl), fichier (obl) |
| 9 | `brand` | Élément :: Marque | titre (obl), image **SANS CROP** (obl) |

## Taxonomies

| Vocabulaire | Label | Termes | Utilisé par |
|-------------|-------|--------|-------------|
| `categories` | Catégories | Général, Auto-école, PMR | champ de `question` + filtre BEF de la page `faq` |
| `vehicle_brand` | Marque | 29 (ADR-003) | webform contact (F10), configurateur (F14), catalogue de tarifs (F17, ADR-030) |
| `vehicle_model` | Modèle | 138 ; champs `field_brand` (obl, → `vehicle_brand`) + `field_motorisations` (obl, multi, → `motorisation`) | idem |
| `motorisation` | Motorisation | Manuelle, Automatique, Hybride, Électrique (ADR-003) | idem |

`vehicle_brand`/`vehicle_model` sont la **source secondaire** du référentiel véhicules : le
combinatoire Excel fait foi, importé via `/admin/content/catalogue-tarifs/import` (module
`drivematic_catalog`, ADR-030) qui rapproche les termes par nom (upsert, pas de suppression
totale — préserve les ID référencés par les soumissions webform existantes).

## Catalogue de tarifs (F17)

Entité de contenu custom `equipment_price` (module `drivematic_catalog`, ADR-030) — une ligne
par combinaison tarifée des 4 équipements du configurateur (télécommande VOR, pédalier,
rétrovision extérieure, rétrovision intérieure). Champs : `type_equipement` (liste fermée),
`vehicle_model` / `motorisation` (références, selon le type), `tarif_ht`, `reference`,
`type_chassis`. Entièrement vidée et recréée à chaque import du combinatoire (pas de
rapprochement — rien ne la référence ailleurs). Liste en lecture seule :
`/admin/content/catalogue-tarifs` (pas d'écran d'édition ligne par ligne : corriger = corriger
le fichier Excel et réimporter).

## Conventions transverses

### Champ « lien » (partout en BO)
Tout champ **lien** = champ **Link** pouvant pointer vers un **node interne** (référence) **ou** une **URL externe**, avec **cible au choix de l'admin** : onglet courant ou nouvel onglet. → composant/champ de lien réutilisable (module *Link attributes* ou équivalent pour la cible `target`). **Ne concerne pas** les fichiers téléchargeables.

### Champ « fichier téléchargeable » (ex-« lien de téléchargement »)
= champ **fichier** (media/file). En front, si le champ est renseigné, afficher un **lien de téléchargement avec nom + format + poids** (calcul auto par le CMS). Concerne les paragraphes (ADR-001) et l'entité `question`.

### Metatags
Module **Metatag** : par type de node, mapper **body → meta description** et **titre → meta title** (tokens). Types sans metatags : `legals`.

**Mise en œuvre (2026-08-17)** — deux etages :

1. **Remplissage automatique** par les defauts Metatag : `node` porte `title: [node:title] | [site:name]` et `description: [node:summary]` (`[node:summary]` = le resume s'il est saisi, sinon un extrait tronque du body — evite une description a rallonge). Rien a saisir par defaut.
2. **Surcharge editoriale** : champ **`field_meta_tags`** (type Metatag, libelle « Balises meta ») sur les nodes publics, widget `metatag_firehose` en **barre laterale** du formulaire, **masque au rendu** (les balises partent dans le `<head>`, pas dans le contenu). Vide = remplissage automatique.

Pose a ce jour sur `homepage`, `news`, `contact`, `simple_form` — **a ajouter a chaque nouveau node public** (`transform`, `product`, `faq`, `documents`, `corporate`, `brands`, `all_news`) ; jamais sur `legals` ni sur les fragments (`question`, `document`, `brand`), ni sur `page` (hote de test).

⚠️ **Piege de la page d'accueil** : Metatag applique sur `<front>` son defaut special **`front`**, qui **remplace** les defauts `node` et `node__homepage` (`metatag.module`, branche `getSpecialMetatags()` → le `else` de la branche entite n'est pas execute). Le mapping a donc ete recopie dans le defaut `front`, en y **conservant `canonical_url: [site:url]`** : l'URL canonique de l'accueil doit rester la racine et non `/node/<id>`. La surcharge par le champ du node, elle, s'applique bien sur l'accueil (elle est fusionnee apres, hors de cette branche).

### Formulaire back-office (convention transverse, 2026-08-17)

Tous les types de contenu presentent **le meme formulaire** : deux **onglets horizontaux** (module `field_group`).

| Onglet | Contenu |
|---|---|
| **Informations generales** | `title`, `path`, `field_meta_tags`, `status` |
| **Contenu** | tous les autres champs, **paragraphes en dernier** |

**Desactives** (jamais proposes a la saisie) : `uid`, `created`, `simple_sitemap`, `url_redirects`.

⚠️ Deux pieges rencontres a la mise en place :

1. `simple_sitemap` et `url_redirects` declarent bien leur champ dans « Gerer le formulaire », mais leurs modules **ajoutent l'element sans consulter le form display** : les desactiver en configuration ne suffit pas. Ils sont retires en **`#after_build`** (`drivematic_forms`), et non dans un `hook_form_alter` — verifie : leurs alters passent **apres** le notre meme avec un poids de module superieur.
2. Un champ ajoute a un type de contenu et **non range dans un groupe** apparait hors onglets, en haut du formulaire. A verifier apres chaque ajout de champ.

### Titre affiché vs libellé d'administration (2026-08-17)

Voir [ADR-011](../.claude/decisions/011-titre-affiche-et-alias.md). Le `title` du node est
un **libellé d'administration** (relabellisé « Titre administratif » via
`core.base_field_override.node.<bundle>.title`) ; le **titre affiché** est porté par
**`field_title`** (string, obligatoire).

- Rendu : `drive_matic_preprocess_page_title()` substitue `field_title` au titre de route sur
  `entity.node.canonical` ; le champ est **masqué dans le view display** (sinon titre en double).
- Métatags : défaut **par bundle** `metatag.metatag_defaults.node__<bundle>` en
  `[node:field_title] | [site:name]`. Le défaut global `node` **reste** sur `[node:title]`, car
  les bundles sans `field_title` s'y rabattent (un token vide donnerait « | Drive Matic »).
- Hors page canonique (cartes, teasers, lignes de vue) : lire `field_title`, jamais `label`.
- **Portent `field_title`** : `transform`, `product`, `corporate`, `legals`, `faq`, `documents`,
  `brands`, `contact`, `partner`, `news`, `all_news`.
  **Exceptions** : `homepage` (titre porté par un paragraphe, pas d'alias) et les trois fragments
  (`question`, `document`, `brand` — pas de page publique, le `title` reste leur identité unique).

**Alias d'URL** : motif Pathauto `/[node:field_title]` sur tout node public (fait pour `contact`,
`partner`) ; `news` fait exception avec `/actualites/[node:field_title]`, `all_news` vivant à
`/actualites`. Chaque nouveau node public a besoin de son `pathauto.pattern.node_<bundle>`.

### Sitemap
**Nodes inclus**, **entités exclues** (Simple XML Sitemap ou équivalent), configuré par type.

### Images sans crop
`image_text_100`, `image_centered`, `product_characteristics` (paragraphes) + `brand` (content) utilisent des images **sans crop** (largeur fixe maquette, hauteur proportionnelle) → entrée pour l'étude images. `contact` en est sorti le 2026-08-20 ([ADR-018 addendum](../.claude/decisions/018-images-locales-par-paragraphe.md)) : son visuel impose désormais un crop 16:9 (`field_photo`, même mécanisme que `news`).

## Mapping content type → paragraphes

| Paragraphe (ADR-001) | homepage | transform | product | corporate | legals | news |
|----------------------|:--------:|:---------:|:-------:|:---------:|:------:|:----:|
| image_text_50 | ✅ | ✅ | ✅ | ✅ | | |
| accordion | ✅ | ✅ | ✅ | ✅ | | |
| image_text_100 | ✅ | ✅ | ✅ | ✅ | | |
| grid | ✅ | ✅ | | | | |
| text_centered | ✅ | ✅ | ✅ | ✅ | | |
| text_left_aligned | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| image_full | | ✅ | ✅ | | | |
| news_home | ✅ | | | | | |
| brands_home | ✅ | | | | | |
| jumbo_home | ✅ | | | | | |
| product_arguments | | | ✅ | | | |
| product_features | | | ✅ | | | |
| product_characteristics | | | ✅ | | | |
| product_cross | | | ✅ | | | |
| triptych | | | | ✅ | | |
| history | | | | ✅ | | |
| image_centered | | | | ✅ | | ✅ |
| video_centered | | | | ✅ | | ✅ |

> Paragraphes non rattachés à un type ci-dessus : `product_*` sont couverts ; les Éléments (`accordion_element`, `grid_element`, etc.) ne sont pas placés directement (imbriqués).

## Décisions complémentaires (tranchées)

1. ✅ `question` / `document` / `brand` = **nodes sans page publique** (hors sitemap + URL directe bloquée), et non des entités custom.
2. ✅ **Vocabulaire unifié sur `categories`** (l'incohérence `faq_categories` des specs est écartée).
3. ✅ **`product` sans champ catégorie** : tout le contenu de la page est saisi manuellement, la distinction auto-école/PMR ne nécessite pas de champ.
4. ✅ **`news`** : date affichée = **dernière modification** (`changed`).
5. ✅ **`legals`** : **indexable, dans le sitemap, sans champ « Balises meta »** — mais il garde un **defaut Metatag de bundle limite au titre** (`[node:field_title] | [site:name]`), sans quoi sa balise `<title>` afficherait le libelle d'administration (consequence d'ADR-011). Pas de description, faute de body.
6. **Webforms** (`contact`, `simple_form`) : contenu défini plus tard (chantier séparé, cohérent avec F10/F11). `simple_form` mutualisé et multi-instance depuis le 2026-08-25 ([ADR-024](../.claude/decisions/024-mutualisation-formulaire-simple.md)).
