# ADR-011 : Titre affiche distinct du libelle d'administration, et motifs d'alias

## Statut

Accepte

## Date

2026-08-17

## Contexte

Jusqu'ici, le `title` du node servait a la fois de **libelle d'administration**
(listes de contenu, autocompletion, journaux) et de **titre affiche** en haut de
la page publique. Les motifs Pathauto en decoulaient (`/[node:title]`).

L'utilisatrice demande de separer les deux : le titre visible par l'internaute
doit etre un champ a part entiere, et c'est **lui** qui doit alimenter l'URL.

Contraintes du moment :

- `field_title` **n'existait que sur l'entite `paragraph`** (17 instances) ;
  cote node, aucun champ de titre.
- La **page d'accueil** est deja l'exception : son titre visible vient d'un
  paragraphe, le bloc titre de page etant masque sur `<front>` (F3).
- Le bloc `page_title_block` rend le **titre de route**, c'est-a-dire le libelle
  du node — il afficherait donc le titre d'administration.
- Les defauts Metatag (ADR-010) mappent `title: [node:title] | [site:name]` :
  la balise `title` et l'onglet du navigateur afficheraient eux aussi le libelle
  d'administration.

## Options considerees

### Option A : garder `title` comme titre affiche (etat initial)

- Avantages : zero champ, zero migration, Pathauto fonctionne tel quel.
- Inconvenients : impossible de nommer une page differemment en back-office et
  en front (ex. « Contact — formulaire principal » cote admin,
  « Nous contacter » cote public) ; c'est precisement le besoin exprime.

### Option B : champ `field_title` + bloc titre masque, titre rendu dans le node

- Avantages : document order maitrise, markup du titre porte par un SDC.
- Inconvenients : masquer le bloc par une condition de visibilite
  **« type de contenu »** le rend inaccessible **hors routes de node** — quand
  le contexte `node` manque, Drupal refuse l'acces au bloc
  (`BlockAccessControlHandler` attrape `MissingValueContextException` et renvoie
  `forbidden`). Les pages sans node (connexion, 403/404, vues) perdraient leur
  `h1`. Il faudrait donc, en plus, reconstruire un titre pour ces routes.

### Option C : champ `field_title` + substitution dans `preprocess_page_title`

- Avantages : un seul endroit responsable du `h1` de la page, quel que soit le
  type de route ; aucune condition de visibilite de bloc a poser ; aucun risque
  de double titre ; les routes sans node continuent d'afficher leur titre.
- Inconvenients : la substitution est invisible en configuration (elle vit dans
  `drive_matic.theme`) ; il faut penser aux cache tags du node.

## Decision

**Option C.**

- Le `title` du node devient un **libelle d'administration** (relabellise
  « Titre administratif » par un `base_field_override`, avec une description qui
  le dit).
- Un champ **`field_title` (string, obligatoire)** porte le **titre affiche**.
- `drive_matic_preprocess_page_title()` remplace le titre de route par
  `field_title` quand le node courant en porte un ; le champ est **masque dans
  le view display** pour ne pas etre rendu deux fois.
- Les **defauts Metatag par bundle** basculent sur
  `[node:field_title] | [site:name]` (le defaut global `node` reste sur
  `[node:title]`, car les bundles sans `field_title` — `homepage`, fragments —
  s'y rabattent et un token vide donnerait « | Drive Matic »).

### Portee

| Portee | Types |
|---|---|
| Portent `field_title` (11) | `transform`, `product`, `corporate`, `legals`, `faq`, `documents`, `brands`, `contact`, `partner`, `news`, `all_news` |
| Exception | `homepage` — titre porte par un paragraphe, aucun alias (chemin `/`) |
| Hors convention | fragments `question`, `document`, `brand` — pas de page publique, pas d'alias : le `title` reste leur identite unique |

### Motifs d'alias

| Type | Motif |
|---|---|
| `news` | `/actualites/[node:field_title]` |
| `all_news`, `transform`, `product`, `corporate`, `legals`, `faq`, `documents`, `brands`, `contact`, `partner` | `/[node:field_title]` |
| `homepage` | aucun motif (chemin `/`) |
| `question`, `document`, `brand` | aucun motif (URL bloquee par Rabbit Hole) |

`all_news` vivant a `/actualites`, le segment parent des details d'actualite est
un **chemin valide** : le fil d'Ariane des actualites aura un vrai 3e niveau,
la ou les autres pages restent a plat.

## Consequences

**Plus facile**

- Nommer une page differemment pour l'equipe et pour le public.
- L'URL suit le titre **lu par l'internaute**, pas une convention interne.
- Un seul point de verite pour le `h1` (`preprocess_page_title`), quelle que
  soit la route.

**Plus difficile / a surveiller**

- **Chaque nouveau type public doit recevoir `field_title`**, son
  `base_field_override` de `title`, son defaut Metatag par bundle et son motif
  Pathauto. Un oubli se voit : la page affiche le libelle d'administration.
- Le champ est **masque dans le view display** : l'ajouter par reflexe au
  rendu produirait un titre en double.
- Tout rendu d'entite hors page canonique (cartes, teasers, resultats de vue)
  doit lire `field_title` et non `label` — corrige ici pour `news-card`.
- La substitution du titre vit dans le code du theme, pas en configuration :
  invisible depuis l'interface d'administration.

**Fichiers impactes**

- `field.storage.node.field_title` + une instance par type public.
- `core.base_field_override.node.<bundle>.title` (libelle « Titre administratif »).
- `metatag.metatag_defaults.node__<bundle>` (title sur `[node:field_title]`).
- `pathauto.pattern.node_<bundle>`.
- `drive_matic.theme` : `drive_matic_preprocess_page_title()`.
- `templates/content/node--news--card.html.twig`.

**Migration du contenu existant** — les trois types deja livres (`contact`,
`partner`, `news`) recoivent le champ ; leur `field_title` est initialise avec
la valeur du `title`, puis les alias sont regeneres.
`pathauto.update_action: 2` + `redirect.auto_redirect: true` produisent les 301
depuis les anciens chemins.
