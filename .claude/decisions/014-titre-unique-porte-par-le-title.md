# ADR-014 : Titre unique porte par le `title`, et `<h1>` porte par le bloc d'ouverture

## Statut

Accepte — **remplace l'[ADR-011](011-titre-affiche-et-alias.md)**

## Date

2026-08-18

## Contexte

L'ADR-011 avait separe le titre en deux champs : le `title` du node comme
**libelle d'administration** et un `field_title` comme **titre affiche**, ce
dernier alimentant l'alias, le fil d'Ariane et la balise `title`.

A l'integration des maquettes, trois constats ont invalide ce montage.

1. **Le titre s'affichait deux fois.** Les maquettes des pages `product` placent
   le titre **sur** la banniere (donc dans le paragraphe `image_full`), celles des
   pages `transform` **sous** la banniere (paragraphe `text_centered`). Le bloc
   titre de page rendait en plus le `field_title` : sur la page « Telecommande VOR
   auto-ecole », le meme texte apparaissait deux fois.
2. **Le second titre sortait en bas de page.** Le bloc titre etait place dans la
   region `sidebar_first`, que `page.html.twig` rend **apres** `page.content` :
   le `<h1>` tombait juste avant le footer. C'etait l'ecart #3 du PRD, et il
   rendait la duplication meconnaissable — on croyait a un residu.
3. **La home n'avait aucun `<h1>`.** La visibilite du bloc titre excluait
   `<front>` : ecart #2 du PRD, contraire a la decision #8 (RGAA / WCAG 2.1 AA).

Les deux champs ne divergeaient en pratique que sur **trois** contenus sur 48
(`all_news`, `faq`, `configurator`). Le cout de la separation — un champ
obligatoire de plus sur 12 types, un preprocess de substitution, un doublon de
titre a arbitrer sur chaque maquette — n'etait donc pas paye par son usage.

## Options considerees

### Option A : garder `field_title` et vider le titre des blocs heros

Aucun SDC touche. Mais on perd les accroches redigees des maquettes
(« Equipez, formez, roulez… ») au profit du nom court de la page, et la home
reste sans `<h1>` — ou affiche « Accueil ».

### Option B : garder `field_title`, masquer le bloc titre sur les types a heros

Conforme visuellement, mais les paragraphes rendent des `<h2>` : ces pages
n'auraient **aucun** `<h1>`. Regression RGAA assumee, ecartee.

### Option C (retenue) : supprimer `field_title`, le `<h1>` au bloc d'ouverture

Le `title` redevient la source unique. Sur les types dont la maquette place le
titre dans un bloc, le bloc titre de page est masque et c'est le paragraphe qui
rend le `<h1>`.

## Decision

1. **`field_title` est supprime des 12 types de contenu**, avec son storage
   `field.storage.node.field_title`. ⚠️ `field.storage.paragraph.field_title`
   (21 bundles, 21 templates SDC) est un champ **homonyme et distinct** : il
   porte le titre des blocs et reste en place.
2. Le `title` alimente desormais l'affichage, le fil d'Ariane
   (`easy_breadcrumb.alternative_title_field` vide → retour au label), les motifs
   Pathauto (`/[node:title]`) et les defauts metatag (`[node:title] | [site:name]`).
   Son `base_field_override` est reintitule « Titre » sur les 12 types.
3. Le bloc `drive_matic_page_title` passe en region **`content`, poids -10** : il
   est rendu **avant** le contenu, jamais plus en bas de page.
4. Sa visibilite porte une condition `entity_bundle:node` **negee** sur
   `homepage`, `transform`, `product` : les types dont un paragraphe titre la page.
5. Les SDC `image-full` et `text-centered` recoivent une prop **`heading_level`**
   (`enum: [1, 2]`, defaut `2`). `_drive_matic_heading_level()` la met a `1` pour
   le **premier paragraphe qui porte effectivement un titre** — et non le premier
   paragraphe tout court, puisque sur une page transform le bloc d'ouverture est
   une `image_full` sans titre.
6. `drive_matic_preprocess_page_title()` est supprime : sans `field_title`, il
   n'avait plus rien a substituer.

Les trois titres divergents ont ete realignes sur leur ancienne valeur affichee
(« Actualites », « Questions frequentes », « Configurez votre vehicule et obtenez
votre tarif ») : les alias `/actualites`, `/questions-frequentes` et `/configurer`
sont donc **conserves a l'identique**, sans redirection a creer.

## Consequences

**Positif**

- Un seul `<h1>` par page, verifie sur les 10 types rendus. La home en a enfin un :
  **ecart #2 du PRD resolu**, et le symptome de l'ecart #3 disparait.
- Un champ obligatoire de moins a la saisie sur 12 types ; l'onglet
  « Informations generales » n'a plus qu'un seul titre.
- Les accroches des maquettes sont preservees, puisque c'est le bloc qui titre.
- Aucun changement visuel : les deux SDC affichaient deja leur titre a la taille
  H1 (`--dm-h1-size`, Exo 2 45/58). Le changement est purement semantique.

**Negatif / vigilance**

- **Le libelle d'administration et le titre affiche ne peuvent plus differer.**
  Le node 69 porte donc « Configurez votre vehicule et obtenez votre tarif »
  comme nom en back-office, ce qui est long dans les listes de contenu.
- **Deux listes de bundles a garder alignees** : `_drive_matic_hero_title_bundles()`
  et la condition de visibilite du bloc titre. Un ecart entre les deux donne une
  page a deux `<h1>` ou a aucun.
- Le `title` pilotant l'URL, **le renommer change l'alias** : prevoir une
  redirection sur un contenu deja publie.
- Un nouveau type public dont la maquette titre par un bloc doit etre ajoute
  **aux deux** listes, et son bloc d'ouverture doit utiliser un SDC portant la
  prop `heading_level`.

## Notes

Le bloc `config` des maquettes (F14, configurateur en 3 etapes) n'existe pas
encore en paragraphe : il est tenu par un `image_text_100` pointant vers
`/configurer`. Rien a arbitrer ici, mais le jour ou ce bloc sera construit, il
n'aura pas a porter de titre de page — `configurator` n'est pas dans la liste des
bundles a titre-heros, son `<h1>` vient du bloc titre.
