# Verification — Brique content-types complete (T0 → T6)

> Trace d'audit des six tranches de `docs/plans/content-types-complet.md`.
> Couvre F4, F5, F6, F7, F8 (liste + detail), F9, et la convention ADR-011.

## Commandes executees

| Commande | Resultat | Notes |
|---|---|---|
| `drush cim` / `cex` puis `drush config:status` | ✅ « No differences » | apres chaque tranche ; la config ecrite a la main est recanonicalisee par `cex` |
| `npm run lint` | ✅ | un warning PHPCS (ligne > 80) et une erreur Stylelint (`media-feature-range-notation`) corriges au passage |
| `npm run format:check` | ✅ | |
| `npm run css` | ✅ | 2 nouveaux SDC (`news-teaser`, `brands-grid`) ; valeurs verifiees dans le `.css` genere |
| `drush simple-sitemap:generate` | ✅ 17 URL | aucun fragment, ni le bac a sable |
| Audit maison des 16 bundles | ✅ | croise `field_title`, defaut Metatag, motif d'alias, sitemap, blocage Rabbit Hole |
| Balayage de rendu des 12 pages publiques | ✅ 200 | `<h1>` et `<title>` corrects partout |

## Changements comportementaux

- **Le titre saisi n'est plus le meme des deux cotes** : le champ « Titre » porte ce que voit
  l'internaute (`h1`, balise `title`, alias) ; le « Titre administratif » ne sert qu'au
  back-office. Modifier le titre affiche change l'alias et cree une **301** depuis l'ancien.
- **Les actualites demenagent** sous `/actualites/<titre>`, avec la liste paginee a `/actualites`.
- **Neuf nouvelles pages publiques** : solutions, produit, corporate, mentions legales, liste
  d'actualites, FAQ, documentations, marques.
- Les fragments `question` et `document` rejoignent `brand` : **403** sur leur URL directe.
- `contact` et `partner` **entrent dans le sitemap** (ils en etaient absents).

## Risques identifies et mitigations

| Risque | Traitement |
|---|---|
| Vue embarquee : pagination et filtre expose casses en `drupal_view()` | **Leve par le test** — 10/1 sur 11 actualites, filtre BEF en pur GET |
| Titre en double (bloc titre de page + champ rendu) | Champ **masque dans le view display** ; verifie : un seul `<h1>` par page |
| Masquer le bloc titre par condition de bundle | **Ecarte** : contexte `node` manquant hors routes de node ⇒ `forbidden`. Substitution dans `preprocess_page_title`, limitee a `entity.node.canonical` |
| Balise `title` retombant sur le libelle admin | Defaut Metatag **par bundle** ; le defaut global `node` reste sur `[node:title]` pour les bundles sans `field_title` |
| Cache : liste non rafraichie a la publication | Verifie **en anonyme, cache page actif** : 6 → 5 sans vidage |
| Fragment atteignable par URL devinee | Verifie par `curl` en anonyme, pas par l'absence de lien |

## Edge cases testes

- **0 actualite publiee** → message de liste vide, pas de bloc casse. ✅
- **11 actualites** → 10 en page 1, 1 en page 2. ✅
- **Categorie FAQ inconnue** (`?categorie=9999`) → message « aucune question ». ✅
- **Section `documents` videe** → ni titre de section, ni espace mort. ✅
- **`legals`** → aucune meta description emise (pas de body), mais `<title>` correct. ✅
- **`/node/<id>`** d'une question / d'un document en anonyme → **403**. ✅
- **Alias modifie** → 301 depuis l'ancien chemin. ✅
- **`grid` sur `product`** → refuse ; autorise sur `transform`. ✅

## Ecarts ouverts (non corriges)

1. **Le site tourne en anglais** — dates, poids de fichiers, pagination. Chantier a part, lance.
2. **La page d'accueil n'a aucun `<h1>`** — antérieur a cette brique ; a trancher avec le
   placement du titre, a l'integration des maquettes.
3. **Double saisie du nom d'un document** — titre du node **et** description du fichier.
4. **URL de base du sitemap** vide : en CLI les URL sortent en `http://default/`. A verifier en preprod.

## Self-review

1. **Decision la plus difficile** : le mecanisme de substitution du titre (ADR-011). Le piege du
   contexte de bloc manquant hors routes de node n'etait pas visible au moment du plan et a
   change l'approche retenue.
2. **Alternatives rejetees** : garder `title` comme titre affiche (ne repond pas au besoin) ;
   masquer le bloc titre par condition de bundle (casse les routes sans node) ; un paragraphe
   « section de documents » (rouvrait la bibliotheque ADR-001, close a 27) ; un display « page »
   pour les vues (le node y perdait titre editorial, corps et metatags).
3. **Point de moindre confiance** : les pages de demonstration heritent des paragraphes du bac a
   sable — coherentes visuellement, **pas** editorialement. Suffisant pour la recette technique,
   a refaire pour une recette client. Et la substitution du titre vit dans le code du theme,
   donc invisible depuis l'interface d'administration.
