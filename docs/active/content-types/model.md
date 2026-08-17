# Modele de contenu editorial — valide

> Liste **validee par l'utilisatrice** (arbitrage direct). Reference d'implementation. Actee dans [ADR-002](../../../.claude/decisions/002-types-de-contenu.md). S'appuie sur la bibliotheque de paragraphes [ADR-001](../../../.claude/decisions/001-bibliotheque-paragraphes.md).
>
> **Convention de nommage** : `Page :: X` = **node public** (URL, sitemap, metatags) ; `Element :: X` = **node « fragment »** sans page publique (hors sitemap, **URL directe bloquee** via Rabbit Hole ou equivalent), reutilisable et listable en vue.

## Nodes (`Page :: …`)

| # | Machine name | Label | Champs propres | Paragraphes autorises (illimite) | Vue / particularite |
|---|--------------|-------|----------------|----------------------------------|---------------------|
| 1 | `homepage` | Page :: Accueil | body (obl), metatags | image_text_50, accordion, image_text_100, grid, text_centered, text_left_aligned, news_home, brands_home, jumbo_home | Node unique = page d'accueil |
| 2 | `transform` | Page :: Transformer / Équiper | body (obl), metatags | image_text_50, accordion, image_text_100, grid, text_centered, text_left_aligned, image_full | — |
| 3 | `product` | Page :: Produit | body (obl), metatags | image_text_50, accordion, image_text_100, text_centered, text_left_aligned, image_full, product_arguments, product_features, product_characteristics, product_cross | — |
| 5 | `faq` | Page :: FAQ | titre (obl), body (obl), metatags | — | Vue **BEF** filtrée par `categories`, tous les `question`, tri date de modif décroissante |
| 7 | `documents` | Page :: Documentation | titre (obl), body (obl), metatags | — | 2 sections (« Auto-écoles », « PMR »), chacune référence des `document` ordonnés manuellement |
| 8 | `corporate` | Page :: Corporate | titre (obl), body (obl), metatags | image_text_50, accordion, image_text_100, text_centered, text_left_aligned, triptych, history, image_centered, video_centered | Qui sommes-nous, ateliers, R&D, certifications… |
| 10 | `brands` | Page :: Marques | titre (obl), body (obl), metatags | — | Vue triée alpha de tous les `brand` |
| 11 | `contact` | Page :: Contact | titre (obl), body (obl), image **SANS CROP** (obl), metatags | — | Webform complexe (contenu défini plus tard) |
| 12 | `partner` | Page :: Devenir partenaire | titre (obl), body (obl), metatags | — | Webform (contenu défini plus tard) |
| 13 | `legals` | Page :: Mentions légales | titre (obl) | text_left_aligned | Pas de body ni metatags |
| 14 | `news` | Page :: Détail d'une actualité | titre (obl), body (obl), metatags, image **crop 16:9** (obl), légende image (opt) | text_left_aligned, image_centered, video_centered | Date = **dernière modification** (champ `changed`, pas de champ dédié), affichée « 12 juillet 2026 » |
| 15 | `all_news` | Page :: Toutes les actualités | titre (obl), body (obl), metatags | — | Vue paginée (10/page) de tous les `news`, tri date de modif décroissante |

Tous les nodes : **inclus au sitemap**.

## Fragments (`Element :: …`) — nodes sans page publique

Types de node **exclus du sitemap** et dont la **page canonique est bloquee** (403/404, ex. module Rabbit Hole). Restent listables en vue et referencables (`entity_reference`).

| # | Machine name | Label | Champs |
|---|--------------|-------|--------|
| 4 | `question` | Élément :: Question / Réponse | titre (obl), body (obl), lien (opt), fichier téléchargeable (opt), catégorie → taxo `categories` (obl) |
| 6 | `document` | Élément :: Document | nom (obl), fichier (obl) |
| 9 | `brand` | Élément :: Marque | titre (obl), image **SANS CROP** (obl) |

## Taxonomies

| Vocabulaire | Label | Termes | Utilisé par |
|-------------|-------|--------|-------------|
| `categories` | Catégories | Général, Auto-école, PMR | champ de `question` + filtre BEF de la page `faq` |

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

Pose a ce jour sur `homepage`, `news`, `contact`, `partner` — **a ajouter a chaque nouveau node public** (`transform`, `product`, `faq`, `documents`, `corporate`, `brands`, `all_news`) ; jamais sur `legals` ni sur les fragments (`question`, `document`, `brand`), ni sur `page` (hote de test).

⚠️ **Piege de la page d'accueil** : Metatag applique sur `<front>` son defaut special **`front`**, qui **remplace** les defauts `node` et `node__homepage` (`metatag.module`, branche `getSpecialMetatags()` → le `else` de la branche entite n'est pas execute). Le mapping a donc ete recopie dans le defaut `front`, en y **conservant `canonical_url: [site:url]`** : l'URL canonique de l'accueil doit rester la racine et non `/node/<id>`. La surcharge par le champ du node, elle, s'applique bien sur l'accueil (elle est fusionnee apres, hors de cette branche).

### Sitemap
**Nodes inclus**, **entités exclues** (Simple XML Sitemap ou équivalent), configuré par type.

### Images sans crop
`image_text_100`, `image_centered`, `product_characteristics` (paragraphes) + `contact`, `brand` (content) utilisent des images **sans crop** (largeur fixe maquette, hauteur proportionnelle) → entrée pour l'étude images.

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
5. ✅ **`legals`** : **indexable, dans le sitemap, sans metatags** (le meta title vient du titre du node).
6. **Webforms** (`contact`, `partner`) : contenu défini plus tard (chantier séparé, cohérent avec F10/F11).
