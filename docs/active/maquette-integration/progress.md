# Progression — integration des maquettes (stylisation bloc par bloc)

> Point de reprise, pas une source de verite. L'etat des blocs **deja integres**
> se lit dans `git log` (commits `feat(<bloc>): integration maquette`) ; les
> valeurs de reference sont dans Figma. Ce fichier ne contient que ce qui n'est
> derivable de nulle part ailleurs. **A supprimer en fin de chantier**, avec le
> bac a sable.
>
> Methode, capture et pieges : cf. memoire agent (`visual-integration-loop`).
> Derniere mise a jour : 2026-08-17.

## Reste a integrer

Ordre = les 18 blocs de la bibliotheque validee (ADR-001) ; les Elements sont
stylises dans leur Bloc parent.

| Bloc | Page | Point d'attention connu |
|---|---|---|
| `brands_home` | Accueil (F3) | Slideshow alimente par une **Vue** (comme `news_home`, deja fait) ; logos **non cliquables** (page canonique du fragment `brand` en 403). Termine la home. |
| `product_features` | Produit (F5) | Slideshow, plafonne 5, **deux** bundles Element possibles (image **ou** video en facade). |
| `product_cross` | Produit (F5) | Grille de cartes liees, plafonne 5. |

Pour demarrer un bloc, il faut le **node id Figma** du frame ou de la region
(fileKey `ZmmVBSOWSsHVkok6EU2Ays`).

## Reserves ouvertes (non bloquantes)

- **Fleches de navigation dupliquees a l'identique** dans `history` et
  `news_home` (carre gris clair 44px + chevron masque, ~40 lignes), plus une
  variante blanche superposee dans `jumbo_home`. Troisieme copie : candidat a un
  style partage, ce qui demande un `--load-path=src/scss` dans `css:components`
  pour qu'un SDC puisse utiliser un mixin de fondation. A acter en ADR plutot
  qu'a decider en cours d'integration.

- **Full-bleed et barre de defilement** : voir README (« Idiome pleine largeur »).
  A verifier sous Windows en recette ; concerne `image_full`,
  `product_characteristics`, `jumbo_home`, `history`.
- **Format en majuscules** (« PDF ») dans les boutons de telechargement, la ou
  la maquette ecrit « pdf » : ecart assume pour rester coherent sur tout le site
  (ADR-009).
- **Visuel de `product_characteristics` centre verticalement** dans sa colonne,
  la ou la maquette le place ~40px plus haut : choix robuste quand la colonne de
  droite s'allonge.
- **Ratio des visuels** : ne juger un rendu que sur des medias **recadres** pour
  le ratio concerne (cf. PRD, prealables images). Les medias 3 et 4 du bac a
  sable ont un `crop_16_9`.

## Etat du bac a sable (node 33)

Enrichi bloc par bloc, non versionne (base locale) : **17 paragraphes**, tous les
blocs integres a ce jour y sont rejouables. Ajouts recents : `history`
(4 entrees), `product_characteristics` (6 caracteristiques + 2 documents),
`news_home` ; second PDF de demo `public://sandbox/dossier-general.pdf` ;
recadrages 16:9 sur les medias 3 et 4 ; descriptions de fichier saisies sur 3
des 9 champs fichier.

`news_home` est aussi place sur la **page d'accueil** (node 31), son emplacement
prevu par F3 — le bac a sable sert a le rejouer, la home a le composer. Regle a
garder : **tout bloc integre doit etre ajoute au bac a sable**, meme quand il a
deja ete verifie sur sa page reelle.
