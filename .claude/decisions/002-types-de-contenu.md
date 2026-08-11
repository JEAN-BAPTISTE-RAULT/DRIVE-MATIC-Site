# ADR-002 : Types de contenu editorial

## Statut
Accepte

## Date
2026-08-11

## Contexte
Apres la bibliotheque de paragraphes (ADR-001), il fallait definir le **modele de contenu editorial** : quels types de contenu (nodes) et entites structurent le site public, leurs champs, et quels paragraphes chacun accueille. Perimetre limite a l'**editorial/public** ; le modele **partenaire/devis** (F12-F17) est un chantier distinct.

## Options considerees

### Option A : fragments en entites de contenu custom
- Avantages : conceptuellement propre (pas des « pages ») ; hors sitemap par nature.
- Inconvenients : plus de developpement (entites custom, formulaires, permissions) qu'un node.

### Option B : fragments = nodes sans page publique
- Avantages : natif Drupal (Views, entity_reference, edition standard) ; sitemap propre en excluant ces types ; URL directe bloquee (Rabbit Hole / access) ; peu de dev.
- Inconvenients : ce sont techniquement des nodes (a bien configurer pour ne pas fuiter d'URL).

## Decision
**Option B**, choisie par l'utilisatrice. Convention : `Page :: X` = **node public** (URL, sitemap, metatags) ; `Element :: X` = **node « fragment »** sans page publique (**exclu du sitemap** + **URL canonique bloquee**, ex. module Rabbit Hole → 403/404), mais listable en vue et referencable.

- **12 nodes publics** : homepage, transform, product, faq, documents, corporate, brands, contact, partner, legals, news, all_news.
- **3 nodes « fragments »** (sans page publique) : question, document, brand.
- **1 taxonomie** : `categories` (Général / Auto-école / PMR — vocabulaire unifie, l'incoherence `faq_categories` des specs est ecartee).
- `product` : **pas de champ categorie** (contenu 100% manuel).
- `news` : date affichee = **derniere modification** (`changed`).
- Modele de reference complet (champs, paragraphes, vues) : `docs/active/content-types/model.md`.

Cet ADR **precise ADR-001** sur deux conventions transverses :
- **Champ « lien »** : Link interne (node) ou externe, cible onglet courant / nouvel onglet au choix de l'admin.
- **« Fichier telechargeable »** (ex-« lien de telechargement ») : champ **fichier** ; en front, afficher nom + format + poids si renseigne.

## Consequences
- Chaque node de type « page composable » porte un champ de reference de **paragraphes** avec liste de types autorisee (voir matrice du model.md) ; chaque paragraphe = 1 SDC (decision #10).
- **Metatag** : mapping body → meta description, titre → meta title par type (sauf `legals`).
- **Sitemap** : nodes publics inclus ; nodes « fragments » (question/document/brand) exclus + URL canonique bloquee (module Rabbit Hole ou equivalent).
- **Vues** : `faq` (BEF par `categories`), `brands` (alpha), `all_news` (paginee 10/page) + les vues home `news_home` / `brands_home` (ADR-001) → declarer les list cache tags.
- **Images sans crop** : `contact`, `brand` s'ajoutent aux paragraphes concernes → entree pour l'etude images.
- **PRD** : §5 (modele de donnees) et F4-F9 pointent vers ce modele.
- `legals` : **indexable, dans le sitemap, sans metatags** (tranche).
