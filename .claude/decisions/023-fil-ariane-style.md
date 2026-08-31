# ADR-023 : Fil d'Ariane stylise — ecart porte par lui-meme, aligne sur le header

## Statut

Accepte

## Date

2026-08-21

## Contexte

Le fil d'Ariane (bloc `system_breadcrumb_block`, markup Easy Breadcrumb) etait
rendu sans aucun style depuis le debut du projet — c'etait un ecart assume,
documente dans `docs/active/maquette-integration/progress.md` (aucune maquette
Figma ne le montre, contrairement au header, au footer ou au titre de page).
Demande : le styliser en respectant la gouttiere gauche des pages, en ajoutant
un espace entre le menu du header et le fil, et en reprenant la typographie et
les couleurs deja etablies sur le site.

Deux difficultes non anticipees au premier jet, remontees par l'utilisatrice
apres verification visuelle :

1. **Ecart nul sous le fil sur les gabarits "hero"** (`homepage`, `transform`,
   `product`) : ces bundles masquent le bloc titre de page
   (`_drive_matic_hero_title_bundles()`), qui portait jusque-la l'ecart
   fil -> titre via son propre `padding-block-start`. Sans ce bloc, rien ne
   separait le fil du premier paragraphe (`image-full` n'a aucun
   padding-block).
2. **Desalignement horizontal avec le logo en desktop** : le bandeau du
   header (`.site-header__bar`) centre une boite plafonnee a 1440px avec une
   gouttiere responsive (24px / 40px a 992px), alors que le titre de page et
   la colonne de contenu utilisent `max(--dm-gutter, 50vw - colonne/2)`, sans
   plafond. Les deux formules ne coincident qu'en dessous d'environ 980px de
   large ; au-dela, l'ecart se fige a 200-230px selon la colonne active.

## Options considerees

### Ecart fil -> suite : sur le bloc titre (comme avant) vs sur le fil lui-meme

- **Sur le bloc titre** (etat initial) : coherent avec `--dm-space-page`,
  deja utilise ainsi partout ailleurs. Mais **absent sur les gabarits hero**
  qui n'ont pas de bloc titre — c'est exactement le bug remonte.
- **Sur le fil d'Ariane lui-meme** (retenu) : fonctionne quel que soit ce qui
  suit (bloc titre ou premier paragraphe). Necessite de retirer le padding du
  bloc titre pour ne pas cumuler les deux ecarts quand les deux sont presents
  — sain uniquement parce que le fil precede **toujours** le bloc titre quand
  celui-ci est affiche (les deux partagent la meme paire de conditions de
  visibilite/masquage, cf. commentaire dans `_page-title.scss`).

### Alignement horizontal : colonne de contenu (comme le titre) vs boite du header

- **Colonne de contenu** (essai initial) : coherent avec le titre de page
  juste en dessous, mais ne s'aligne pas avec le logo au-dela d'environ
  980px de large — l'ecart remonte par l'utilisatrice.
- **Boite du bandeau header** (retenue, demande explicite) : alignement
  pixel-parfait avec le logo a toute largeur. Consequence acceptee : le fil
  d'Ariane et le titre de page n'ont plus le meme bord gauche au-dela
  d'environ 980px (le fil suit le "chrome" du header, le titre suit la
  colonne de contenu — deux echelles differentes, assumees).

## Decision

- `.breadcrumb` porte un `padding-block` egal en haut et en bas
  (`--dm-space-element`, 24px — moitie de `--dm-space-page`, jugee trop
  genereuse une fois les deux ecarts cumules). `.block-page-title-block` ne
  pose plus son propre `padding-block-start`.
- `.breadcrumb` reprend exactement la boite de `.site-header__bar` (plafond
  1440px centre, gouttiere 24px / 40px a 992px) plutot que la colonne de
  contenu. La custom property `--site-header-gutter` etant scopee a
  `.site-header` (non heritee par un frere du DOM), les valeurs sont
  **dupliquees a la main** dans `_breadcrumb.scss`, avec un commentaire
  renvoyant a `site-header.scss` pour eviter la divergence silencieuse si
  cette gouttiere change un jour.
- Typographie et couleurs reprises du registre deja etabli par `pager`
  (autre markup de navigation secondaire du coeur) : Inter 14/28, liens gris
  texte, element courant (dernier, non lien) en acier gras. Aucune maquette
  ne guidait ce choix.
- Le style brut herite du starterkit (`css/components/breadcrumb.css`,
  categorie de librairie `component`) est neutralise sans y toucher : la
  fondation du theme (`style.css`, categorie `theme`) est agregee apres et
  l'emporte a specificite egale — comportement natif de Drupal, pas une
  astuce de specificite CSS.

## Consequences

- Le fil d'Ariane a desormais un ecart coherent (haut et bas) sur **toutes**
  les pages, y compris les gabarits hero sans bloc titre.
- Alignement pixel-parfait avec le logo du header a toutes les largeurs
  desktop (verifie a 1440px et 1920px).
- **Le fil d'Ariane et le titre de page n'ont plus le meme bord gauche
  au-dela d'environ 980px de large** — deviation deliberee, demandee
  explicitement. Si une coherence stricte est souhaitee plus tard, il
  faudrait soit retuner la colonne de contenu pour qu'elle coincide avec la
  boite du header (perd l'alignement avec le logo au-dela de 1440px), soit
  accepter cette double echelle definitivement (chrome vs contenu).
- Couplage a surveiller : si `_drive_matic_hero_title_bundles()` ou la
  condition de masquage du fil d'Ariane changent un jour de sorte qu'un bloc
  titre puisse s'afficher **sans** fil d'Ariane devant lui, ce bloc perdrait
  tout ecart au-dessus (plus de `padding-block-start` local) — a reposer
  explicitement dans `_page-title.scss` le cas echeant (commentaire deja en
  place dans le fichier).

## Alternatives rejetees

Voir les options ci-dessus.

## Addendum du 2026-08-31 : masque en mobile

Demande explicite de l'utilisatrice : le fil d'Ariane ne doit plus s'afficher
sous 992px. Seul le contenu (`.breadcrumb ol`, `display: none` puis `flex` a
`>= 992px`) est masque — pas `.breadcrumb` lui-meme, qui continue de porter
son `padding-block` (`--dm-space-element`) a toutes les largeurs. Ce choix
preserve exactement l'invariant de la section « Consequences » ci-dessus (le
bloc titre / premier paragraphe hero ne repose toujours pas son propre
`padding-block-start`) : rien a reposer ailleurs, l'ecart existe meme quand
le fil est visuellement vide en mobile.
